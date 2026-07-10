/* Crystal's Art School — dynamic filter tags (single source of truth).
   Every filterable page (Courses catalog, Gallery, Showcase, Blog) builds its
   filter options THROUGH this file, from the actual content on the page —
   a filter chip can only exist if at least one item carries that tag.
   Add a tag to a course/artwork/post and its chip appears; remove the last
   item with a tag and the chip disappears (empty groups hide themselves). */
window.CasFilters = (function () {
  var LABELS = {
    watercolour: 'Watercolour', oil: 'Oil', oils: 'Oils', pencils: 'Pencils',
    pastel: 'Pastel', ink: 'Ink', acrylic: 'Acrylic', poster: 'Poster',
    landscape: 'Landscape', cityscape: 'Cityscape', 'still-life': 'Still life',
    portrait: 'Portrait', animal: 'Animal', illustration: 'Illustration',
    perspective: 'Perspective', abstract: 'Abstract',
    beginner: 'Beginner', intermediate: 'Intermediate', advanced: 'Advanced',
    'all-levels': 'All levels',
    short: 'Under 1h', medium: '1–2h', long: '2h+',
    sale: 'On sale'
  };
  var esc = function (s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;'); };

  /* unique tag values from the items, in first-seen order */
  function collect(items, get) {
    var out = [];
    (items || []).forEach(function (it) {
      var vals = get(it);
      if (vals == null || vals === '') return;
      if (!Array.isArray(vals)) vals = [vals];
      vals.forEach(function (v) {
        if (v != null && v !== '' && out.indexOf(v) < 0) out.push(v);
      });
    });
    return out;
  }

  function label(v) {
    return LABELS[String(v).toLowerCase()] || (String(v).charAt(0).toUpperCase() + String(v).slice(1));
  }

  function chip(val, text, isActive) {
    return '<button class="fopt' + (isActive ? ' active' : '') + '" type="button" data-val="' + esc(val) + '">' + esc(text) + '</button>';
  }

  /* html for a chip row: optional "All/Any" first, then one chip per value */
  function chipsHTML(values, opts) {
    opts = opts || {};
    var html = '';
    if (opts.allLabel) html += chip(opts.allValue || 'all', opts.allLabel, opts.active == null || opts.active === (opts.allValue || 'all'));
    values.forEach(function (v) { html += chip(v, opts.labelOf ? opts.labelOf(v) : label(v), opts.active === v); });
    return html;
  }

  /* collect + render into a container; hides the whole group when empty.
     groupEl (optional) = the wrapper to hide (e.g. the .fgroup). */
  function populate(el, items, get, opts) {
    if (!el) return [];
    var vals = collect(items, get);
    el.innerHTML = chipsHTML(vals, opts);
    var group = (opts && opts.groupEl) || null;
    if (group) group.style.display = vals.length ? '' : 'none';
    return vals;
  }

  return { collect: collect, chipsHTML: chipsHTML, populate: populate, label: label };
})();
