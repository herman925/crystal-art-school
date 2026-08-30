'use strict';

/* Behavioral contract for the guarded Courses intro fold controller. */

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const scriptPath = path.join(
  root,
  'wp-content',
  'themes',
  'hello-elementor-child',
  'js',
  'cas-courses.js'
);
const source = fs.readFileSync(scriptPath, 'utf8');

const listeners = Object.create(null);
const attributes = {
  'aria-controls': 'cas-courses-intro-more',
  'aria-expanded': 'false',
};
const toggle = {
  textContent: 'Read More',
  addEventListener(type, callback) {
    listeners[type] = callback;
  },
  getAttribute(name) {
    return attributes[name] || null;
  },
  setAttribute(name, value) {
    attributes[name] = value;
  },
};
const more = { hidden: true };

const context = {
  window: {},
  document: {
    querySelector(selector) {
      return selector === '[data-cas-courses-intro-toggle]' ? toggle : null;
    },
    getElementById(id) {
      return id === 'cas-courses-intro-more' ? more : null;
    },
  },
};
context.window.window = context.window;
context.globalThis = context;

vm.runInNewContext(source, context, { filename: scriptPath });
assert.equal(typeof listeners.click, 'function', 'intro toggle must bind a click handler');

listeners.click();
assert.equal(more.hidden, false, 'first click must reveal the marked continuation');
assert.equal(attributes['aria-expanded'], 'true');
assert.equal(toggle.textContent, 'Read Less');

listeners.click();
assert.equal(more.hidden, true, 'second click must hide the marked continuation');
assert.equal(attributes['aria-expanded'], 'false');
assert.equal(toggle.textContent, 'Read More');

console.log('Courses intro fold behavior passed');
