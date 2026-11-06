const fs = require('node:fs'), vm = require('node:vm'), assert = require('node:assert/strict');
class Input {
  constructor() { this.listeners = {}; this.checked = false; }
  get value() { return this._value || ''; }
  set value(v) { this._value = v; }
  dispatchEvent() {}
  setAttribute() {}
  addEventListener(type, fn) { this.listeners[type] = fn; }
}
const field = new Input(), sale = new Input(), regular = new Input();
field.value = JSON.stringify({ on: '0', amount: '', version: 'current' });
sale.matches = () => true;
let control, tick, inputEvent;
const row = { contains: node => node === regular, insertAdjacentElement: (_where, node) => { control = node; node.previousElementSibling = row; } };
sale.parentElement = row;
const document = {
  body: {},
  querySelector: selector => selector.includes('_cas_builder_sale') ? field : selector.includes('course_sale_price') ? sale : regular,
  addEventListener: (type, fn) => { if (type === 'input') inputEvent = fn; },
  createElement: tag => tag === 'input' ? new Input() : {
    append(...nodes) { this.children = nodes; },
    querySelector() { return this.children[0]; },
    remove() { this.previousElementSibling = null; }
  }
};
const window = { casBuilderSale: { value: field.value }, Tutor: { CourseBuilder: { Basic: { registerField() {} } } }, addEventListener() {} };
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../wp-content/themes/hello-elementor-child/js/cas-builder-sale.js'), 'utf8'), {
  window, document, HTMLInputElement: Input, Event: class {}, setInterval: fn => { tick = fn; return 1; }, clearInterval() {}, setTimeout() {}
});
sale.value = '0'; inputEvent({ target: sale });
sale.value = ''; tick(); // Tutor's controlled input renders zero as empty.
const toggle = control.querySelector('input'); toggle.checked = true; toggle.listeners.change();
assert.equal(JSON.parse(field.value).amount, '0');
assert.equal(JSON.parse(field.value).on, '1');
field.value = JSON.stringify({ on: '1', amount: '0.00', version: 'saved' }); tick();
assert.equal(JSON.parse(field.value).amount, '0.00');
sale.value = ''; inputEvent({ target: sale });
assert.equal(JSON.parse(field.value).amount, '', 'An explicit clear must remain blank.');
sale.value = '1.25'; inputEvent({ target: sale });
assert.equal(JSON.parse(field.value).amount, '1.25');
console.log('Builder zero input: explicit zero survives controlled rendering and toggling; blank/positive input remains distinct.');
