/* Browser-local choices. Account Showcase preferences are saved separately by WordPress. */
(function () {
  "use strict";
  var key = "cas-privacy-v1";
  var banner = document.getElementById("cas-cookie-banner");
  var dialog = document.getElementById("cas-privacy-modal");
  if (!banner || !dialog) {
    return;
  }
  var checkbox = document.getElementById("cas-privacy-media");
  var choice = null,
    opener = null,
    previousOverflow = "",
    siblings = [],
    expiryTimer;
  var placeholders = new Map();

  function readChoice(raw) {
    try {
      var value = JSON.parse(raw);
      return value &&
        value.version === 1 &&
        typeof value.media === "boolean" &&
        Number.isFinite(value.expires) &&
        value.expires > Date.now() &&
        value.expires <= Date.now() + 187 * 86400000
        ? value
        : null;
    } catch (e) {
      return null;
    }
  }
  try {
    choice = readChoice(localStorage.getItem(key));
  } catch (e) {
    /* Session-only when storage is blocked. */
  }
  function allowed() {
    return !!(choice && choice.media && choice.expires > Date.now());
  }
  function announce(text) {
    if (window.CasAnnounce) {
      window.CasAnnounce(text);
    }
  }
  function bannerHeight() {
    document.body.style.setProperty(
      "--cas-cookie-height",
      banner.hidden ? "0px" : banner.getBoundingClientRect().height + 12 + "px",
    );
  }
  function frameFor(node) {
    return node.tagName === "SOURCE" ? node.closest("video") : node;
  }
  function applyMedia() {
    var videos = new Set();
    document.querySelectorAll("[data-cas-media-src]").forEach(function (node) {
      var frame = frameFor(node);
      if (!frame) {
        return;
      }
      if (allowed()) {
        var url = node.getAttribute("data-cas-media-src");
        if (node.getAttribute("src") !== url) {
          node.setAttribute("src", url);
          if (frame.tagName === "VIDEO") {
            videos.add(frame);
          }
        }
        frame.removeAttribute("data-cas-media-blocked");
        if (placeholders.has(frame)) {
          placeholders.get(frame).remove();
          placeholders.delete(frame);
        }
      } else {
        if (frame.tagName === "VIDEO") {
          frame.pause();
        }
        if (node.hasAttribute("src")) {
          node.removeAttribute("src");
          if (frame.tagName === "VIDEO") {
            videos.add(frame);
          }
        }
        frame.setAttribute("data-cas-media-blocked", "");
        if (!placeholders.has(frame)) {
          var notice = document.createElement("div");
          notice.className = "cas-media-choice";
          var text = document.createElement("p");
          text.textContent =
            "External video is off. Allow video players to connect to the video host and play this film.";
          var button = document.createElement("button");
          button.type = "button";
          button.className = "btn primary";
          button.textContent = "Allow video players";
          button.setAttribute("data-cas-privacy-allow", "");
          var settings = document.createElement("button");
          settings.type = "button";
          settings.className = "btn";
          settings.textContent = "Privacy choices";
          settings.setAttribute("data-cas-privacy-open", "");
          notice.append(text, button, settings);
          frame.insertAdjacentElement("afterend", notice);
          placeholders.set(frame, notice);
        }
      }
    });
    videos.forEach(function (video) {
      video.load();
    });
  }
  function refresh() {
    banner.hidden = !!choice;
    bannerHeight();
    applyMedia();
    clearTimeout(expiryTimer);
    if (choice) {
      // Browser timeout integers only support about 24 days; check long preferences in bounded steps.
      expiryTimer = setTimeout(
        function () {
          if (choice && choice.expires <= Date.now()) {
            choice = null;
          }
          refresh();
        },
        Math.min(choice.expires - Date.now() + 10, 86400000),
      );
    }
  }
  function save(media) {
    var sourceButton = opener || document.activeElement;
    var notice = sourceButton && sourceButton.closest(".cas-media-choice");
    var player = notice && notice.previousElementSibling;
    var expiry = new Date();
    expiry.setMonth(expiry.getMonth() + 6);
    choice = { version: 1, media: media, expires: expiry.getTime() };
    var persisted = true;
    try {
      localStorage.setItem(key, JSON.stringify(choice));
    } catch (e) {
      persisted = false;
    }
    refresh();
    closeDialog();
    if (media && player && player.isConnected) {
      player.focus();
    }
    announce(
      persisted
        ? "Privacy choices saved."
        : "Privacy choice applied for this page. Browser storage is unavailable.",
    );
  }
  function openDialog(button) {
    if (!dialog.hidden) {
      return;
    }
    opener = button || document.activeElement;
    checkbox.checked = allowed();
    previousOverflow = document.body.style.overflow;
    siblings = Array.from(document.body.children)
      .filter(function (node) {
        return (
          node !== dialog &&
          node.tagName !== "SCRIPT" &&
          node.tagName !== "STYLE"
        );
      })
      .map(function (node) {
        var previous = { node: node, inert: node.inert };
        node.inert = true;
        return previous;
      });
    dialog.hidden = false;
    dialog.inert = false;
    document.body.style.overflow = "hidden";
    dialog.querySelector("[data-cas-privacy-close]").focus();
  }
  function closeDialog() {
    if (dialog.hidden) {
      return;
    }
    dialog.hidden = true;
    dialog.inert = true;
    siblings.forEach(function (item) {
      item.node.inert = item.inert;
    });
    siblings = [];
    document.body.style.overflow = previousOverflow;
    if (opener && opener.isConnected) {
      opener.focus();
    }
    opener = null;
  }
  document.addEventListener("click", function (event) {
    var button = event.target.closest(
      "[data-cas-privacy-open],[data-cas-privacy-close],[data-cas-privacy-save],[data-cas-privacy-reject],[data-cas-privacy-allow]",
    );
    if (!button) {
      return;
    }
    event.preventDefault();
    if (button.hasAttribute("data-cas-privacy-open")) {
      openDialog(button);
    } else if (button.hasAttribute("data-cas-privacy-close")) {
      closeDialog();
    } else {
      save(
        button.hasAttribute("data-cas-privacy-allow") ||
          (button.hasAttribute("data-cas-privacy-save") && checkbox.checked),
      );
    }
  });
  document.addEventListener(
    "keydown",
    function (event) {
      if (dialog.hidden) {
        return;
      }
      if (event.key === "Escape") {
        event.preventDefault();
        event.stopImmediatePropagation();
        closeDialog();
      }
      if (event.key === "Tab") {
        event.stopImmediatePropagation();
        var nodes = Array.from(
          dialog.querySelectorAll("button,input,a[href]"),
        ).filter(function (node) {
          return !node.disabled && node.getClientRects().length;
        });
        var first = nodes[0],
          last = nodes[nodes.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    },
    true,
  );
  // Existing artwork/cart/video dialogs also trap focus. The upper dialog owns it while open.
  document.addEventListener(
    "focusin",
    function (event) {
      if (!dialog.hidden) {
        event.stopImmediatePropagation();
        if (!dialog.contains(event.target)) {
          dialog.querySelector("button").focus();
        }
      }
    },
    true,
  );
  window.addEventListener("storage", function (event) {
    if (event.key === key || event.key === null) {
      choice = event.key === null ? null : readChoice(event.newValue);
      refresh();
      if (!dialog.hidden) {
        checkbox.checked = allowed();
      }
    }
  });
  window.CasPrivacy = {
    loadFrame: function (frame, url) {
      if (!url || url === "about:blank") {
        frame.removeAttribute("data-cas-media-src");
        frame.removeAttribute("data-cas-media-blocked");
        frame.removeAttribute("src");
        if (placeholders.has(frame)) {
          placeholders.get(frame).remove();
          placeholders.delete(frame);
        }
      } else {
        frame.setAttribute("data-cas-media-src", url);
        applyMedia();
      }
    },
  };
  if (window.ResizeObserver) {
    new ResizeObserver(bannerHeight).observe(banner);
  }
  window.addEventListener("resize", bannerHeight);
  refresh();
})();
