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
    xs: 'Under 30m', short: '30m–1h', medium: '1–2h', long: '2–4h', xl: '4h+',
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
    opts = opts || {};
    var present = collect(items, get);
    /* `scale` is a fixed run of values that should always be offered, in order,
       even where nothing on the page falls into one of them — a length or price
       band only reads as a scale if the whole scale is visible. Bands with no
       items render disabled rather than being dropped, so the row still says
       what the range is. Groups without a scale keep the original behaviour:
       a chip exists only if something carries that value. */
    var vals = opts.scale ? opts.scale.slice() : present;
    var html = "";
    if (opts.allLabel) {
      html += chip(opts.allValue || "all", opts.allLabel,
                   opts.active == null || opts.active === (opts.allValue || "all"));
    }
    vals.forEach(function (v) {
      var text = opts.labelOf ? opts.labelOf(v) : label(v);
      var c = chip(v, text, opts.active === v);
      if (present.indexOf(v) < 0) {
        c = c.replace("<button ", "<button disabled aria-disabled=\"true\" ")
             .replace('class="fopt', 'class="fopt is-empty');
      }
      html += c;
    });
    /* Rebuild only when the markup actually changes. populate() holds no state
       and binds no listeners of its own — 50 consecutive calls add and remove
       exactly zero — but it does replace the chip nodes, and callers bind their
       click handlers to those nodes directly (Lessons.dc.html:390 and :449),
       so an unconditional rewrite would silently unbind the whole rail. Caching
       the emitted string makes a repeat call a true no-op. */
    if (el.__casChipsHTML !== html) { el.innerHTML = html; el.__casChipsHTML = html; }
    var group = opts.groupEl || null;
    if (group) group.style.display = (opts.scale || present.length) ? "" : "none";
    return present;
  }

  return { collect: collect, chipsHTML: chipsHTML, populate: populate, label: label };
})();
