'use strict';

/*
 * Behavioral contract for the WordPress Blog list controller in
 * cas-blog-templates.php.  The harness extracts and executes the emitted
 * JavaScript instead of asserting that the PHP file merely contains tokens.
 */

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const phpPath = path.join(
  root,
  'wp-content',
  'themes',
  'hello-elementor-child',
  'inc',
  'cas-blog-templates.php'
);
const phpSource = fs.readFileSync(phpPath, 'utf8');
const perPageMatch = phpSource.match(/const CAS_BLOG_PER_PAGE = (\d+);/);
assert.ok(perPageMatch, 'Blog list page size must remain discoverable');
const perPage = Number(perPageMatch[1]);
const scriptMatch = phpSource.match(/return <<<JS\r?\n([\s\S]*?)\r?\nJS;/);
assert.ok(scriptMatch, 'cas_blog_list_script() must emit a JavaScript body');
const script = scriptMatch[1].replace(/\{\$per_page\}/g, String(perPage));

class FakeNode {
  constructor(document, options = {}) {
    this.document = document;
    this.id = options.id || '';
    this.kind = options.kind || 'generic';
    this.top = options.top == null ? 100 : options.top;
    this.children = options.children || [];
    this.style = Object.create(null);
    this.attributes = Object.assign({}, options.attributes || {});
    this.listeners = Object.create(null);
    this.motion = Object.create(null);
    this.className = options.className || '';
    this.classList = {
      add: (...names) => names.forEach((name) => {
        if (!this.className.split(/\s+/).includes(name)) {
          this.className = `${this.className} ${name}`.trim();
        }
      }),
      remove: (...names) => {
        this.className = this.className
          .split(/\s+/)
          .filter((name) => name && !names.includes(name))
          .join(' ');
      },
      contains: (name) => this.className.split(/\s+/).includes(name),
    };
    this.textContent = options.textContent || '';
    this.isConnected = true;
    this._hidden = false;
    Object.defineProperty(this, 'hidden', {
      enumerable: true,
      get: () => this._hidden,
      set: (value) => {
        this._hidden = Boolean(value);
        this.style.display = this._hidden ? 'none' : '';
      },
    });
  }

  getAttribute(name) {
    if (name === 'class') return this.className;
    return Object.prototype.hasOwnProperty.call(this.attributes, name)
      ? String(this.attributes[name])
      : null;
  }

  setAttribute(name, value) {
    this.attributes[name] = String(value);
    if (name === 'class') this.className = String(value);
  }

  addEventListener(type, callback, options) {
    if (!this.listeners[type]) this.listeners[type] = [];
    this.listeners[type].push({ callback, options });
  }

  dispatch(type, target = this) {
    (this.listeners[type] || []).forEach(({ callback }) => callback({
      target,
      currentTarget: this,
    }));
  }

  contains(node) {
    return node === this || this.children.some((child) => child.contains(node));
  }

  appendChild(node) {
    this.children.push(node);
    return node;
  }

  focus() {
    this.document.activeElement = this;
  }

  getBoundingClientRect() {
    if (this.hidden) return { top: 0, bottom: 0 };
    return { top: this.top, bottom: this.top + 100 };
  }

  querySelectorAll(selector) {
    if (this.kind === 'filters' && selector === '.chip') return this.children;
    if (this.kind === 'grid' && selector === '.blog-card') return this.children;
    if (this.kind === 'grid' && selector === '.blog-card:not([hidden])') {
      return this.children.filter((node) => !node.hidden);
    }
    return [];
  }

  querySelector(selector) {
    if (this.kind === 'filters' && selector === '.chip.active') {
      return this.children.find((node) => node.classList.contains('active')) || null;
    }
    return null;
  }
}

class FakeDocument {
  constructor() {
    this.activeElement = null;
    this.listeners = Object.create(null);
    this.documentElement = { clientHeight: 800 };
    this.body = new FakeNode(this, { kind: 'body' });
    this.nodes = Object.create(null);
    this.features = [];
  }

  register(node) {
    if (node.id) this.nodes[node.id] = node;
    return node;
  }

  getElementById(id) {
    return this.nodes[id] || null;
  }

  querySelector(selector) {
    if (selector === '.chip.active') return this.nodes['blog-filters'].querySelector(selector);
    return null;
  }

  querySelectorAll(selector) {
    if (selector === '.blog-feature') return this.features;
    return [];
  }

  createElement() {
    return new FakeNode(this);
  }

  addEventListener(type, callback, options) {
    if (!this.listeners[type]) this.listeners[type] = [];
    this.listeners[type].push({ callback, options });
  }
}

function createGsap() {
  const calls = [];
  const each = (target, callback) => {
    (Array.isArray(target) ? target : [target]).forEach(callback);
  };
  const apply = (target, vars) => {
    each(target, (node) => Object.entries(vars).forEach(([key, value]) => {
      if (key === 'opacity') node.style.opacity = String(value);
      else if (key === 'transform') node.style.transform = String(value);
      else if (key !== 'clearProps') node.motion[key] = value;
    }));
  };

  return {
    calls,
    set(target, vars) {
      calls.push({ method: 'set', targets: (Array.isArray(target) ? target : [target]).map((node) => node.id), vars });
      apply(target, vars);
    },
    fromTo(target, fromVars, toVars) {
      calls.push({ method: 'fromTo', targets: (Array.isArray(target) ? target : [target]).map((node) => node.id), fromVars, toVars });
      apply(target, toVars);
    },
  };
}

function makeFixture({ reduced = false, positions = 'visible' } = {}) {
  const document = new FakeDocument();
  const windowListeners = Object.create(null);
  const gsap = createGsap();
  const top = (id) => (positions === 'below' && /^C/.test(id) ? 1000 : 100);

  const filters = document.register(new FakeNode(document, { id: 'blog-filters', kind: 'filters' }));
  const chips = ['All', 'Tools', 'Colour'].map((tag, index) => new FakeNode(document, {
    kind: 'chip',
    attributes: { 'data-tag': tag, 'aria-pressed': index === 0 ? 'true' : 'false' },
    className: index === 0 ? 'chip active' : 'chip',
  }));
  filters.children = chips;

  const grid = document.register(new FakeNode(document, { id: 'blog-grid', kind: 'grid' }));
  const cards = Array.from({ length: 9 }, (_, index) => {
    const id = `C${index + 1}`;
    const tag = index === 8 ? 'Colour' : 'Tools';
    return new FakeNode(document, {
      id,
      kind: 'card',
      top: top(id),
      attributes: { 'data-id': id, 'data-tag': tag },
    });
  });
  grid.children = cards;

  const features = [
    new FakeNode(document, {
      id: 'F1',
      kind: 'feature',
      attributes: { 'data-id': 'F1', 'data-for': 'All|Tools' },
    }),
    new FakeNode(document, {
      id: 'F2',
      kind: 'feature',
      attributes: { 'data-id': 'F2', 'data-for': 'Colour' },
    }),
  ];
  document.features = features;
  features.forEach((feature) => document.register(feature));

  const empty = document.register(new FakeNode(document, { id: 'blog-empty' }));
  const moreWrap = document.register(new FakeNode(document, { id: 'blog-more' }));
  const moreBtn = document.register(new FakeNode(document, { id: 'blog-more-btn' }));

  const setWindowListener = (type, callback, options) => {
    if (!windowListeners[type]) windowListeners[type] = [];
    windowListeners[type].push({ callback, options });
  };
  const context = {
    console,
    document,
    setTimeout: (callback) => {
      callback();
      return 1;
    },
    window: {
      innerHeight: 800,
      document,
      gsap,
      matchMedia: () => ({ matches: reduced }),
      addEventListener: setWindowListener,
      listeners: windowListeners,
    },
  };
  context.window.window = context.window;
  context.globalThis = context;
  vm.runInNewContext(script, context, { filename: phpPath });

  return {
    context,
    document,
    window: context.window,
    gsap,
    filters,
    chips,
    grid,
    cards,
    features,
    empty,
    moreWrap,
    moreBtn,
  };
}

function targetIds(calls) {
  return calls.flatMap((call) => call.targets);
}

function clickChip(fixture, tag) {
  const chip = fixture.chips.find((node) => node.getAttribute('data-tag') === tag);
  assert.ok(chip, `missing ${tag} chip`);
  chip.dispatch('click');
}

function testInitialAlternatingRevealAndPassiveScroll() {
  const fixture = makeFixture();
  const reveals = fixture.gsap.calls.filter((call) => call.method === 'fromTo');
  assert.deepEqual(
    reveals.map((call) => call.targets[0]),
    ['F1', 'C1', 'C2', 'C3', 'C4', 'C5', 'C6'],
    'initial arm must animate the visible feature and first page only'
  );
  assert.deepEqual(
    reveals.map((call) => call.fromVars.x),
    [-60, 60, -60, 60, -60, 60, -60],
    'feature and cards must enter from alternating sides'
  );
  assert.equal(fixture.window.listeners.scroll.length, 1, 'scroll driver must bind once');
  assert.equal(fixture.window.listeners.resize.length, 1, 'resize driver must bind once');
  assert.equal(fixture.window.listeners.scroll[0].options.passive, true);
}

function testFilterRearmsVisibleFeatureAndCards() {
  const fixture = makeFixture();
  fixture.gsap.calls.length = 0;
  clickChip(fixture, 'Colour');

  assert.equal(fixture.features[0].hidden, true);
  assert.equal(fixture.features[1].hidden, false);
  assert.equal(fixture.chips[2].classList.contains('active'), true);
  assert.equal(fixture.chips[2].getAttribute('aria-pressed'), 'true');
  assert.equal(fixture.cards[8].hidden, false);
  assert.equal(fixture.cards[0].hidden, true);
  assert.deepEqual(
    targetIds(fixture.gsap.calls),
    ['F2', 'C9', 'F2', 'C9'],
    'filter changes must re-arm only the newly visible feature and cards'
  );
  assert.equal(fixture.window.listeners.scroll.length, 1, 'filter changes must not add scroll listeners');
}

function testLoadMoreArmsOnlyTheNewTailAndPreservesLanding() {
  const fixture = makeFixture({ positions: 'below' });
  fixture.gsap.calls.length = 0;
  fixture.moreBtn.dispatch('click');

  assert.deepEqual(
    targetIds(fixture.gsap.calls),
    ['C7', 'C8', 'C9'],
    'Load more must arm only the new tail, never replay earlier cards'
  );
  assert.equal(fixture.moreWrap.style.display, 'none');
  assert.equal(fixture.document.activeElement, fixture.cards[6], 'last Load more must land on the first appended card');
  assert.equal(fixture.cards[0].style.opacity, '0', 'existing below-fold cards remain pending');
}

function testKeyboardFocusCompletesBelowFoldReveal() {
  const fixture = makeFixture({ positions: 'below' });
  const card = fixture.cards[0];
  assert.equal(card.style.opacity, '0', 'precondition: below-fold card is visually hidden but tabbable');
  card.focus();
  fixture.grid.dispatch('focusin', card);
  assert.equal(card.style.opacity, '1', 'focused card must become visible immediately');
  assert.equal(card.motion.x, 0);
  assert.equal(card.motion.y, 0);
  assert.equal(card.motion.rotateZ, 0);
}

function testReducedMotionSkipsAnimationAndListeners() {
  const fixture = makeFixture({ reduced: true, positions: 'below' });
  [fixture.features[0], ...fixture.cards.slice(0, 6)].forEach((node) => {
    assert.equal(node.style.opacity, '1');
    assert.equal(node.style.transform, 'none');
  });
  assert.equal(fixture.window.listeners.scroll, undefined, 'reduced motion must not bind scroll work');
  assert.equal(fixture.window.listeners.resize, undefined, 'reduced motion must not bind resize work');
}

for (const test of [
  testInitialAlternatingRevealAndPassiveScroll,
  testFilterRearmsVisibleFeatureAndCards,
  testLoadMoreArmsOnlyTheNewTailAndPreservesLanding,
  testKeyboardFocusCompletesBelowFoldReveal,
  testReducedMotionSkipsAnimationAndListeners,
]) {
  test();
}

process.stdout.write('Blog list motion behavior passed\n');
