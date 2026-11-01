/* Move the existing catalogue controls; their filter state and handlers stay authoritative. */
(function () {
  "use strict";
  function init() {
    var rail = document.querySelector(
      ".catalog-layout > .filter-col, .store-layout > .filter-col",
    );
    if (
      !rail ||
      !window.CasA11y ||
      !rail.querySelector("button,input,select")
    ) {
      return;
    }
    var layout = rail.parentElement;
    var courses = layout.classList.contains("catalog-layout");
    var label = courses ? "Filter courses" : "Filter artwork";
    var count = document.getElementById(
      courses ? "lesson-count" : "store-count",
    );
    var results = count
      ? count.parentElement
      : document.querySelector(courses ? "#lesson-grid" : "#store-grid");
    var marker = document.createComment("Catalogue filter position");
    rail.before(marker);
    var handle = document.createElement("button");
    handle.type = "button";
    handle.className = "cas-filter-handle btn primary";
    handle.textContent = label;
    handle.setAttribute("aria-haspopup", "dialog");
    handle.setAttribute("aria-controls", "cas-mobile-filter-drawer");
    handle.setAttribute("aria-expanded", "false");
    var backdrop = document.createElement("div");
    backdrop.className = "cas-filter-backdrop";
    var drawer = document.createElement("section");
    drawer.id = "cas-mobile-filter-drawer";
    drawer.className = "cas-filter-drawer";
    drawer.setAttribute("role", "dialog");
    drawer.setAttribute("aria-modal", "true");
    drawer.setAttribute("aria-labelledby", "cas-mobile-filter-title");
    drawer.innerHTML =
      '<header class="cas-filter-head"><h2 id="cas-mobile-filter-title"></h2>' +
      '<button class="cas-filter-close" type="button" aria-label="Close filters">✕</button></header>' +
      '<div class="cas-filter-body"></div><footer class="cas-filter-foot"><button class="btn primary full" type="button"></button></footer>';
    drawer.querySelector("h2").textContent = label;
    var body = drawer.querySelector(".cas-filter-body");
    var closeButton = drawer.querySelector(".cas-filter-close");
    var showButton = drawer.querySelector(".cas-filter-foot button");
    var mobile = window.matchMedia("(max-width: 880px)");
    var open = false;
    var overflow = "";
    function hide(el, hidden) {
      el.hidden = hidden;
      window.CasA11y.setHidden(el, hidden);
    }
    hide(drawer, true);
    hide(backdrop, true);
    hide(handle, true);
    document.body.append(backdrop, drawer, handle);
    if (results) {
      results.classList.add("cas-filter-results");
    }
    function updateCount() {
      showButton.textContent =
        "Show " +
        (count ? count.textContent.trim() : courses ? "courses" : "artwork");
    }
    updateCount();
    if (count) {
      new MutationObserver(updateCount).observe(count, {
        childList: true,
        characterData: true,
        subtree: true,
      });
    }
    function close(focus) {
      if (!open) {
        return;
      }
      open = false;
      hide(drawer, true);
      hide(backdrop, true);
      handle.setAttribute("aria-expanded", "false");
      document.body.style.overflow = overflow;
      if (focus && mobile.matches) {
        handle.focus({ preventScroll: true });
      }
    }
    function adapt() {
      var focused = document.activeElement;
      if (mobile.matches) {
        var hidingFocusedControl = rail.contains(focused);
        body.appendChild(rail);
        layout.classList.add("cas-filter-enabled");
        hide(handle, false);
        if (hidingFocusedControl) {
          handle.focus({ preventScroll: true });
        }
      } else {
        var restoreFocus = focused === handle || drawer.contains(focused);
        close(false);
        marker.after(rail);
        layout.classList.remove("cas-filter-enabled");
        hide(handle, true);
        if (restoreFocus) {
          var target = rail.contains(focused)
            ? focused
            : rail.querySelector("button,input,select");
          if (target) {
            target.focus({ preventScroll: true });
          }
        }
      }
    }
    window.CasA11y.trapFocus(drawer, function () {
      return open;
    });
    handle.addEventListener("click", function () {
      if (!mobile.matches || open) {
        return;
      }
      open = true;
      overflow = document.body.style.overflow;
      document.body.style.overflow = "hidden";
      hide(drawer, false);
      hide(backdrop, false);
      body.scrollTop = 0;
      handle.setAttribute("aria-expanded", "true");
      closeButton.focus({ preventScroll: true });
    });
    closeButton.addEventListener("click", function () {
      close(true);
    });
    backdrop.addEventListener("click", function () {
      close(true);
    });
    showButton.addEventListener("click", function () {
      close(true);
      if (results) {
        results.scrollIntoView({ block: "start", behavior: "auto" });
      }
    });
    document.addEventListener("keydown", function (event) {
      if (open && event.key === "Escape") {
        event.preventDefault();
        close(true);
      }
    });
    window.addEventListener("pagehide", function () {
      close(false);
    });
    mobile.addEventListener("change", adapt);
    adapt();
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
