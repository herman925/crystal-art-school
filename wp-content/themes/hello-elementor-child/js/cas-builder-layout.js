/* Native fields keep their nodes, listeners and React parent; only sibling order/layout changes. */
(function () {
  'use strict';
  const root = document.getElementById('tutor-course-builder');
  if (!root) return;
  let scheduled = false, difficulty, attempts = 0;
  const config = window.casBuilderDifficulty;
  function registerDifficulty() {
    if (!config) return;
    const basic = window.Tutor && window.Tutor.CourseBuilder && window.Tutor.CourseBuilder.Basic;
    if (!basic) { if (++attempts < 100) setTimeout(registerDifficulty, 100); return; }
    root.classList.add('cas-builder-difficulty-enabled');
    basic.registerField('after_settings', {name: 'course_level', type: 'text', defaultValue: config.value});
  }
  function arrangeDifficulty() {
    if (!config) return;
    const input = root.querySelector('input[name="course_level"]');
    const label = Array.from(root.querySelectorAll('label')).find(n => n.textContent.trim() === 'Medium');
    const medium = label && label.closest('[data-cy="form-field-wrapper"]');
    if (!input || !medium) { if (difficulty) difficulty.remove(); return; }
    if (!difficulty) {
      difficulty = document.createElement('div');
      difficulty.className = 'cas-builder-difficulty';
      const label = document.createElement('label');
      label.htmlFor = 'cas-builder-difficulty'; label.textContent = 'Difficulty Level';
      const select = document.createElement('select');
      select.id = label.htmlFor;
      Object.entries(config.options).forEach(([value, text]) => select.add(new Option(text, value)));
      select.addEventListener('change', function () {
        const field = root.querySelector('input[name="course_level"]');
        if (!field || !Object.prototype.hasOwnProperty.call(config.options, select.value)) return;
        // Tutor owns the registered field and serializes it with the ordinary course save.
        Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set.call(field, select.value);
        field.dispatchEvent(new Event('input', {bubbles: true}));
        field.dispatchEvent(new Event('change', {bubbles: true}));
      });
      difficulty.append(label, select);
    }
    if (medium.previousElementSibling !== difficulty) medium.before(difficulty);
    difficulty.querySelector('select').value = input.value;
  }
  function arrange() {
    scheduled = false;
    arrangeDifficulty();
    const title = root.querySelector('input[name="post_title"]');
    const subtitle = root.querySelector('input[name="post_excerpt"]');
    const field = subtitle && subtitle.closest('[data-cy="form-field-wrapper"]');
    if (!title || !field) return;
    const parent = field.parentElement;
    let titleGroup = title.closest('[data-cy="form-field-wrapper"]');
    while (titleGroup && titleGroup.parentElement !== parent) titleGroup = titleGroup.parentElement;
    if (!titleGroup || titleGroup === field) return;
    // Same-parent movement preserves native unmount/removal and makes Tab order match the page.
    if (titleGroup.nextElementSibling !== field) parent.insertBefore(field, titleGroup.nextSibling);
    parent.classList.add('cas-builder-photo-layout');
    const labels = new Set((window.casCoursePhotoFields || []).filter(f => /^_cas_course_photo_/.test(f.name)).map(f => f.label));
    Array.from(parent.children).forEach(function (child) {
      const label = child.querySelector('label');
      if (child.matches('[data-cy="form-field-wrapper"]') && label && labels.has(label.textContent.trim())) child.classList.add('cas-builder-gallery-field');
    });
  }
  const observer = new MutationObserver(function () {
    if (!scheduled) { scheduled = true; setTimeout(arrange, 0); }
  });
  function start() { observer.observe(root, {childList: true, subtree: true, attributes: true, attributeFilter: ['value']}); arrange(); }
  window.addEventListener('pagehide', function () { observer.disconnect(); });
  window.addEventListener('pageshow', start);
  registerDifficulty();
  start();
}());
