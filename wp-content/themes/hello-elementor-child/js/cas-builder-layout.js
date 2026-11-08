/* Native fields keep their nodes, listeners and React parent; only sibling order/layout changes. */
(function () {
  'use strict';
  const root = document.getElementById('tutor-course-builder');
  if (!root) return;
  let scheduled = false;
  function arrange() {
    scheduled = false;
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
  function start() { observer.observe(root, {childList: true, subtree: true}); arrange(); }
  window.addEventListener('pagehide', function () { observer.disconnect(); });
  window.addEventListener('pageshow', start);
  start();
}());
