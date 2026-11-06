/* Keep native controls authoritative; add search only when more than six media exist. */
(function () {
  'use strict';
  var serial = 0;
  function enhance(container, options, navigation) {
    if (options.length <= 6 || typeof HTMLDialogElement === 'undefined') return;
    var dialog = document.createElement('dialog');
    if (typeof dialog.showModal !== 'function') return;
    var id = 'cas-medium-title-' + (++serial);
    dialog.className = 'cas-medium-dialog';
    dialog.setAttribute('aria-labelledby', id);
    dialog.innerHTML = '<header><h2 id="' + id + '">Choose Medium</h2><button type="button" class="cas-medium-close" aria-label="Close Medium picker">✕</button></header><label class="cas-medium-search">Search media<input type="search" autocomplete="off"></label><p class="cas-medium-help"></p><div class="cas-medium-selected" aria-label="Selected media"></div><div class="cas-medium-options"></div><p class="cas-medium-none" role="status" hidden>No media match your search.</p><footer><button type="button" class="cas-medium-clear">Clear selection</button><button type="button" class="cas-medium-done">Done</button></footer>';
    var search = dialog.querySelector('input'), list = dialog.querySelector('.cas-medium-options');
    var selected = dialog.querySelector('.cas-medium-selected'), none = dialog.querySelector('.cas-medium-none');
    var all = navigation ? container.querySelector('.is-all') : container.querySelector('[data-val="all"]');
    var multiple = !navigation && !all;
    var opener = document.createElement('button');
    var summary = document.createElement('div'); summary.className = 'cas-medium-current';
    opener.type = 'button'; opener.className = 'cas-medium-open'; opener.textContent = 'Choose Medium';
    opener.setAttribute('aria-haspopup', 'dialog');
    dialog.id = 'cas-medium-picker-' + serial; opener.setAttribute('aria-controls', dialog.id);
    dialog.querySelector('.cas-medium-help').textContent = navigation ? 'Choose a medium to browse its courses.' : multiple ? 'Choose one or more media. Clear selection shows all media.' : 'Choose one medium. Clear selection shows all media.';
    var rows = options.map(function (original) {
      var row = document.createElement(navigation ? 'a' : 'label');
      var input;
      if (navigation) { row.href = original.href; }
      else {
        input = document.createElement('input'); input.type = 'checkbox';
        row.appendChild(input);
        input.addEventListener('change', function () { toggle(original); });
      }
      var name = document.createElement('span'); name.textContent = original.textContent.trim(); row.appendChild(name);
      list.appendChild(row);
      return { original: original, row: row, input: input, name: name.textContent };
    });
    function active(original) { return original.getAttribute('aria-pressed') === 'true'; }
    function toggle(original) {
      if (!multiple && active(original)) all.click();
      else original.click();
      sync();
    }
    function sync() {
      selected.replaceChildren(); summary.replaceChildren();
      rows.forEach(function (entry) {
        if (!entry.input) return;
        entry.input.checked = active(entry.original);
        if (entry.input.checked) {
          var remove = document.createElement('button');
          remove.type = 'button'; remove.textContent = entry.name + ' ×';
          remove.setAttribute('aria-label', 'Remove Medium: ' + entry.name);
          remove.addEventListener('click', function () { toggle(entry.original); search.focus(); });
          selected.appendChild(remove);
          if (entry.original.hidden) {
            var current = remove.cloneNode(true);
            current.addEventListener('click', function () { toggle(entry.original); opener.focus(); });
            summary.appendChild(current);
          }
        }
      });
    }
    function filter() {
      var query = search.value.trim().toLocaleLowerCase(), count = 0;
      rows.forEach(function (entry) {
        entry.row.hidden = entry.name.toLocaleLowerCase().indexOf(query) < 0;
        if (!entry.row.hidden) count++;
      });
      none.hidden = count > 0;
    }
    var returnFocus;
    function close() { dialog.close(); if (returnFocus && returnFocus.getClientRects().length) returnFocus.focus(); }
    opener.addEventListener('click', function () {
      returnFocus = opener; search.value = ''; filter(); sync(); dialog.showModal(); search.focus();
    });
    search.addEventListener('input', filter);
    dialog.querySelector('.cas-medium-close').addEventListener('click', close);
    dialog.querySelector('.cas-medium-done').addEventListener('click', close);
    dialog.querySelector('.cas-medium-clear').addEventListener('click', function () {
      if (all) all.click(); else options.forEach(function (option) { if (active(option)) option.click(); });
      sync(); search.value = ''; filter(); search.focus();
    });
    if (navigation) {
      selected.hidden = true;
      var clear = dialog.querySelector('.cas-medium-clear');
      clear.hidden = true;
      if (all) { var link = all.cloneNode(true); link.hidden = false; list.prepend(link); }
    }
    dialog.addEventListener('cancel', function (event) { event.preventDefault(); close(); });
    if (window.CasA11y) window.CasA11y.trapFocus(dialog, function () { return dialog.open; });
    dialog.addEventListener('keydown', function (event) { if (event.key === 'Escape') event.stopPropagation(); });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) { var rect = dialog.getBoundingClientRect(); if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) close(); }
    });
    options.slice(6).forEach(function (option) { option.hidden = true; option.classList.add('cas-medium-overflow'); });
    container.appendChild(opener); if (!navigation) container.appendChild(summary);
    document.body.appendChild(dialog);
    if (!navigation) {
      new MutationObserver(sync).observe(container, {subtree:true,attributes:true,attributeFilter:['aria-pressed']});
      sync();
    }
  }
  document.querySelectorAll('.fgroup[data-group="medium"]').forEach(function (group) {
    enhance(group, Array.from(group.querySelectorAll('.fopt:not([data-val="all"])')), false);
  });
  document.querySelectorAll('.mega-cats').forEach(function (group) {
    enhance(group, Array.from(group.querySelectorAll('a.mega-cat:not(.is-all)')), true);
  });
}());
