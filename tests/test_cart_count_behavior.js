const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const theme = path.join(__dirname, '../wp-content/themes/hello-elementor-child');
const header = fs.readFileSync(path.join(theme, 'js/cas-header.js'), 'utf8');
const courses = fs.readFileSync(path.join(theme, 'js/cas-courses.js'), 'utf8');

function environment(configured = true) {
  const listeners = {document: {}, window: {}};
  const requests = [];
  const events = [];
  const button = {label: 'Shopping cart', setAttribute(key, value) { this.label = value; }};
  const badge = {textContent: '', hidden: true, style: {}, closest() { return button; }};
  const document = {
    body: {getAttribute() { return 'D'; }},
    addEventListener(name, callback) { (listeners.document[name] ||= []).push(callback); },
    dispatchEvent(event) { events.push(event); (listeners.document[event.type] || []).forEach(fn => fn(event)); },
    querySelector(selector) { return selector === '.cart-badge' ? badge : null; },
    querySelectorAll(selector) { return selector === '.cart-badge' ? [badge] : []; },
    getElementById() { return null; },
  };
  const window = {
    addEventListener(name, callback) { (listeners.window[name] ||= []).push(callback); },
    setTimeout() {},
    casCourses: {action: 'tutor_add_course_to_cart', ajaxUrl: '/ajax', nonce: 'native-nonce'},
  };
  if (configured) window.casCartCount = {ajaxUrl: '/ajax', nonce: 'count-nonce'};
  const context = vm.createContext({
    document, window, casCourses: window.casCourses, URLSearchParams,
    CustomEvent: class { constructor(type, options = {}) { this.type = type; this.detail = options.detail; } },
    localStorage: {getItem() { throw new Error('Never read browser cart authority'); }, setItem() {}},
    fetch(url, options) { return new Promise((resolve, reject) => requests.push({url, options, resolve, reject})); },
    setInterval() { throw new Error('No polling'); },
  });
  vm.runInContext(header, context);
  return {context, badge, button, requests, events, document,
    emit(name, detail = {}) { (listeners.window[name] || []).forEach(fn => fn(detail)); },
    cartEvent(name, count) { document.dispatchEvent({type: name, detail: {cart_count: count}}); },
  };
}

async function respond(request, count, options = {}) {
  request.resolve({ok: options.ok ?? true,
    json: async () => options.payload ?? {success: true, data: {known: true, count}},
    text: async () => JSON.stringify(options.payload ?? {}),
  });
  await new Promise(setImmediate);
}

function unknown(env) {
  assert.equal(env.badge.hidden, true);
  assert.equal(env.badge.textContent, '');
  assert.equal(env.button.label, 'Shopping cart, item count unavailable');
}

(async () => {
  const e = environment();
  unknown(e);
  assert.equal(e.requests.length, 1);
  const initial = e.requests[0];
  assert.equal(initial.options.method, 'POST');
  assert.equal(initial.options.cache, 'no-store');
  assert.equal(initial.options.credentials, 'same-origin');
  assert.equal(initial.options.redirect, 'error');
  assert.equal(initial.options.body.get('action'), 'cas_cart_count');
  assert.equal(initial.options.body.get('nonce'), 'count-nonce');
  await respond(initial, 2);
  assert.equal(e.badge.textContent, 2);
  assert.equal(e.button.label, 'Shopping cart, 2 items');
  assert.equal(e.badge.hidden, false);

  e.cartEvent('tutorAddToCartEvent', 999);
  unknown(e);
  e.cartEvent('tutorRemoveCartEvent', 999);
  await respond(e.requests[2], 1);
  await respond(e.requests[1], 99);
  assert.equal(e.badge.textContent, 1);
  assert.equal(e.button.label, 'Shopping cart, 1 item');

  e.emit('focus');
  unknown(e);
  await respond(e.requests[3], 5);
  assert.equal(e.button.label, 'Shopping cart, 5 items');
  e.emit('pageshow', {persisted: true});
  await respond(e.requests[4], 0);
  assert.equal(e.badge.hidden, true);
  assert.equal(e.button.label, 'Shopping cart, 0 items');

  e.cartEvent('tutorAddToCartEvent', 100);
  e.emit('pagehide');
  await respond(e.requests[5], 8);
  unknown(e);
  e.emit('pageshow', {persisted: true});
  await respond(e.requests[6], 3);
  assert.equal(e.button.label, 'Shopping cart, 3 items');

  for (const payload of [
    {success: false, data: {known: false}},
    {success: true, data: {known: true, count: '8'}},
    {success: true, data: {known: true, count: -1}},
    {success: true, data: {known: true, count: 1.5}},
  ]) {
    e.emit('focus');
    await respond(e.requests.at(-1), null, {payload});
    unknown(e);
  }
  e.emit('focus');
  await respond(e.requests.at(-1), null, {ok: false});
  unknown(e);
  e.emit('focus');
  e.requests.at(-1).reject(new Error('Network failed'));
  await new Promise(setImmediate);
  unknown(e);
  const unavailable = environment(false);
  assert.equal(unavailable.requests.length, 0);
  unknown(unavailable);

  const c = environment();
  await respond(c.requests[0], 2);
  vm.runInContext(courses, c.context);
  const cartButton = {
    disabled: false, innerHTML: 'Add',
    getAttribute() { return '34'; },
    classList: {add(name) { cartButton.added = name; }, contains() { return true; }},
    closest(selector) { return selector === '[data-cas-add-to-cart]' ? this : null; },
  };
  c.document.dispatchEvent({type: 'click', target: cartButton});
  assert.equal(c.requests[1].options.body.includes('action=tutor_add_course_to_cart'), true);
  await respond(c.requests[1], null, {payload: {status_code: 201, data: {cart_count: 999}}});
  assert.equal(c.events.filter(event => event.type === 'tutorAddToCartEvent').length, 1);
  assert.equal(c.events.at(-1).detail.cart_count, 999);
  unknown(c);
  assert.equal(c.requests[2].options.body.get('action'), 'cas_cart_count');
  await respond(c.requests[2], 3);
  assert.equal(c.button.label, 'Shopping cart, 3 items');
  assert.equal(cartButton.innerHTML, 'Added');
  console.log('Prepared cart-count behavior checks completed');
})().catch(error => { console.error(error); process.exitCode = 1; });
