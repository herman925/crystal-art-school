(function () {
  'use strict';

  function initDashboard(root) {
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));

    if (!tabs.length || !panels.length) {
      return;
    }

    function panelFor(key) {
      return panels.find(function (panel) {
        return panel.getAttribute('data-panel') === key;
      });
    }

    function activate(tab, moveFocus, writeHash) {
      var key = tab.getAttribute('data-panel');
      var panel = panelFor(key);

      if (!panel) {
        return;
      }

      tabs.forEach(function (item) {
        var selected = item === tab;
        item.classList.toggle('active', selected);
        item.setAttribute('aria-selected', selected ? 'true' : 'false');
        item.setAttribute('tabindex', selected ? '0' : '-1');
      });

      panels.forEach(function (item) {
        var selected = item === panel;
        item.classList.toggle('active', selected);
        item.hidden = !selected;
      });

      if (writeHash && window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.hash = panel.id;
        window.history.replaceState(null, '', url.pathname + url.search + url.hash);
      }

      if (moveFocus) {
        tab.focus();
      }
    }

    function tabFromHash() {
      var hash = window.location.hash.replace(/^#/, '');
      return tabs.find(function (tab) {
        return tab.getAttribute('aria-controls') === hash;
      });
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function () {
        activate(tab, false, true);
      });

      tab.addEventListener('keydown', function (event) {
        var next = index;
        var horizontal = event.key === 'ArrowRight' || event.key === 'ArrowLeft';
        var vertical = event.key === 'ArrowDown' || event.key === 'ArrowUp';

        if (horizontal || vertical) {
          event.preventDefault();
          if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
            next = (index + 1) % tabs.length;
          } else {
            next = (index - 1 + tabs.length) % tabs.length;
          }
          activate(tabs[next], true, true);
        } else if (event.key === 'Home' || event.key === 'End') {
          event.preventDefault();
          activate(tabs[event.key === 'Home' ? 0 : tabs.length - 1], true, true);
        }
      });
    });

    activate(tabFromHash() || tabs[0], false, false);
  }

  function init() {
    document.querySelectorAll('[data-cas-dashboard]').forEach(initDashboard);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
