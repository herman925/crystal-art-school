/* Register a native form field; place its switch beside Tutor's own price inputs. */
(function () {
  'use strict';
  let control, timer, attempts = 0, registered = false;
  const name = '_cas_builder_sale';
  function field() { return document.querySelector('input[name="' + name + '"]'); }
  function value() { try { return JSON.parse(field().value); } catch (_) { return null; } }
  function bridge(on, explicitAmount) {
    const input = field(), state = value();
    if (!input || !state) return;
    state.on = on ? '1' : '0';
    const sale = document.querySelector('input[name="course_sale_price"]');
    // Tutor can render numeric zero as an empty input; keep the explicit input event's value.
    if (explicitAmount !== undefined) state.amount = explicitAmount;
    else if (sale && sale.value !== '') state.amount = sale.value;
    Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(input, JSON.stringify(state));
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }
  document.addEventListener('input', function (event) {
    if (event.target.matches('input[name="course_sale_price"]')) {
      const state = value(); if (state) bridge(state.on === '1', event.target.value);
    }
  }, true);
  function paint() {
    const sale = document.querySelector('input[name="course_sale_price"]');
    const regular = document.querySelector('input[name="course_price"]');
    const state = value();
    if (!sale || !regular || !state) { if (control) control.remove(); return; }
    if (sale.value !== '' && state.amount !== sale.value) bridge(state.on === '1');
    let row = sale.parentElement;
    while (row && !row.contains(regular)) row = row.parentElement;
    if (!row || row === document.body) return;
    if (!control) {
      control = document.createElement('label');
      control.className = 'cas-builder-sale-control';
      const toggle = document.createElement('input');
      toggle.type = 'checkbox'; toggle.setAttribute('role', 'switch');
      toggle.setAttribute('aria-label', 'On sale');
      toggle.addEventListener('change', function () { bridge(toggle.checked); });
      const text = document.createElement('span'); text.textContent = 'On sale';
      control.append(toggle, text);
      control.title = 'Use Save as Draft, Update or Publish to apply this setting.';
    }
    if (control.previousElementSibling !== row) row.insertAdjacentElement('afterend', control);
    control.querySelector('input').checked = state.on === '1';
  }
  function register() {
    const basic = window.Tutor && window.Tutor.CourseBuilder && window.Tutor.CourseBuilder.Basic;
    if (!basic) { if (++attempts < 100) setTimeout(register, 100); return; }
    basic.registerField('after_settings', { name, type: 'text', defaultValue: window.casBuilderSale.value });
    registered = true;
    clearInterval(timer); timer = setInterval(paint, 150); paint();
  }
  window.addEventListener('pagehide', function () { clearInterval(timer); if (control) control.remove(); });
  window.addEventListener('pageshow', function () { if (registered) { clearInterval(timer); timer = setInterval(paint, 150); paint(); } });
  register();
})();
