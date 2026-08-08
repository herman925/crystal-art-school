/* Crystal's Art School — SINGLE SOURCE OF TRUTH for the site footer.
   Edit the footer HERE, nowhere else.

   Before this file existed the footer was copy-pasted into 13 pages and had
   drifted into three different variants (one with real social links, one with
   dead href="#" buttons, one with no social row at all). Every page now ships
   an empty <footer class="site-footer"> and this script fills it, the same way
   js/site-nav.js owns the top nav.

   Checkout keeps a different 4th column ("Account" instead of "Contact"); mark
   that up with <footer class="site-footer" data-footer-variant="checkout">
   rather than hand-rolling a second copy. */
(function () {
  if (window.__casFooter) return;
  window.__casFooter = true;

  /* ---- the one place Crystal's contact details are written down ---- */
  var SOCIAL = {
    instagram: 'https://www.instagram.com/crystalauart',
    facebook: 'https://www.facebook.com/crystal430',
    /* 2.4: the official YouTube channel joins every social area site-wide.
       Supplied by the client 2026-08-08. The pending-placeholder branch below
       stays in place for the next time a link is outstanding. */
    youtube: 'https://youtube.com/@crystallauart',
    email: 'crystalau430@gmail.com'
  };
  window.CAS_SOCIAL = SOCIAL;

  var ICON = {
    instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"></circle></svg>',
    facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14v-1.5c0-.3.2-.5.5-.5z"></path></svg>',
    youtube: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="4"></rect><path d="M10.5 9.2l5 2.8-5 2.8z" fill="currentColor" stroke="none"></path></svg>',
    email: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path></svg>'
  };

  /* Shared by the footer and by index.html's "Follow along" strip, so the two
     can never disagree. cls is 'social-btn' or 'ss-btn'. */
  function socialLinks(cls) {
    return '' +
      '<a class="' + cls + '" href="' + SOCIAL.instagram + '" target="_blank" rel="noopener" aria-label="Instagram">' + ICON.instagram + '</a>' +
      '<a class="' + cls + '" href="' + SOCIAL.facebook + '" target="_blank" rel="noopener" aria-label="Facebook">' + ICON.facebook + '</a>' +
      (SOCIAL.youtube
        ? '<a class="' + cls + '" href="' + SOCIAL.youtube + '" target="_blank" rel="noopener" aria-label="YouTube">' + ICON.youtube + '</a>'
        : '<span class="' + cls + ' is-pending" role="img" aria-label="YouTube — channel link still to be supplied" title="YouTube — channel link still to be supplied">' + ICON.youtube + '</span>') +
      '<a class="' + cls + '" href="mailto:' + SOCIAL.email + '" aria-label="Email">' + ICON.email + '</a>';
  }
  window.CAS_SOCIAL_LINKS = socialLinks;

  var COLUMNS = {
    contact:
      '<h4>Contact</h4>' +
      '<a href="mailto:' + SOCIAL.email + '">' + SOCIAL.email + '</a>' +
      '<a href="' + SOCIAL.instagram + '" target="_blank" rel="noopener">Instagram</a>' +
      '<a href="' + SOCIAL.facebook + '" target="_blank" rel="noopener">Facebook</a>' +
      (SOCIAL.youtube ? '<a href="' + SOCIAL.youtube + '" target="_blank" rel="noopener">YouTube</a>' : '') +
      '<a href="About.dc.html#terms">Terms &amp; privacy</a>',
    account:
      '<h4>Account</h4>' +
      '<a href="Login.dc.html">Log in</a>' +
      '<a href="Signup.dc.html">Sign up</a>' +
      '<a href="Checkout.dc.html">Basket</a>'
  };

  function buildFooter(variant) {
    var lastCol = variant === 'checkout' ? COLUMNS.account : COLUMNS.contact;
    return '' +
      '<div class="inner">' +
        '<div class="footer-grid">' +
          '<div>' +
            /* Her own signature, the same file the About hero uses, in place of
               the typeset word CRYSTAL'S. (client feedback 2026-08-08) The
               signature is decorative here — the studio name is written out in
               the copyright line below and in the paragraph under it — so it is
               marked aria-hidden with empty alt rather than read out twice. */
            '<div class="footer-brand footer-sig"><img src="img/signature.png" alt="" aria-hidden="true" loading="lazy" decoding="async"></div>' +
            /* Facts here come from assets/06 - About/AboutMe.docx. The previous
               line ("watercolour, acrylic & drawing … a small studio of plaster,
               wood and iron") was invented placeholder copy — she works in
               watercolour and oil, and runs SKY GALLERY. */
            '<p style="font-size:14px;opacity:.85;max-width:34ch;margin:0;">Watercolour &amp; oil painting, taught step by step by Crystal Lau — artist and founder of SKY GALLERY.</p>' +
            '<div class="social-row">' + socialLinks('social-btn') + '</div>' +
          '</div>' +
          '<div>' +
            '<h4>Learn</h4>' +
            '<a href="Lessons.dc.html">All courses</a>' +
            '<a href="Lessons.dc.html#membership">Membership</a>' +
            '<a href="FreeVideos.dc.html">Free videos</a>' +
          '</div>' +
          '<div>' +
            '<h4>Explore</h4>' +
            '<a href="Gallery.dc.html">Gallery</a>' +
            '<a href="Showcase.dc.html">Student showcase</a>' +
            '<a href="Blog.dc.html">Blog</a>' +
            '<a href="About.dc.html">About</a>' +
          '</div>' +
          '<div>' + lastCol + '</div>' +
        '</div>' +
        '<div class="footer-meta"><span>© 2026 Crystal\'s Art School</span></div>' +
      '</div>';
  }
  window.CAS_BUILD_FOOTER = buildFooter;

  /* ---- replace each page's footer with the canonical one ----
     Polled rather than fired on DOMContentLoaded: the dc-runtime streams the
     page in and would otherwise overwrite whatever we injected too early.
     Same guard/So-far pattern as js/site-nav.js. */
  /* Any container marked data-cas-social="<btn class>" gets the same links,
     so the home page's "Follow along" strip cannot drift from the footer. */
  function fillSocialSlots() {
    var slots = document.querySelectorAll('[data-cas-social]');
    var n = 0;
    for (var i = 0; i < slots.length; i++) {
      if (slots[i].getAttribute('data-cas-synced')) continue;
      slots[i].innerHTML = socialLinks(slots[i].getAttribute('data-cas-social') || 'social-btn');
      slots[i].setAttribute('data-cas-synced', '1');
      n++;
    }
    return n;
  }

  function fillFooters() {
    var feet = document.querySelectorAll('footer.site-footer');
    var n = 0;
    for (var i = 0; i < feet.length; i++) {
      var f = feet[i];
      if (f.getAttribute('data-cas-synced')) continue;
      f.innerHTML = buildFooter(f.getAttribute('data-footer-variant') || '');
      f.setAttribute('data-cas-synced', '1');
      n++;
    }
    return n;
  }

  /* Stopping condition. An earlier version cleared the interval the moment ANY
     footer was found, which meant a [data-cas-social] slot that streamed in
     after the footer was never filled — the page silently rendered an empty
     social strip. Instead: keep polling until we have synced something AND a
     further QUIET_TICKS have passed with nothing new to do, so late arrivals
     are still picked up. MAX_TRIES is the hard backstop. */
  var QUIET_TICKS = 40;   // 2s of no new work
  var MAX_TRIES = 300;    // 15s ceiling
  var tries = 0, quiet = 0, syncedAny = false;

  var iv = setInterval(function () {
    tries++;
    var did = fillSocialSlots() + fillFooters();
    if (did > 0) syncedAny = true;
    quiet = (syncedAny && did === 0) ? quiet + 1 : 0;
    if (quiet >= QUIET_TICKS || tries > MAX_TRIES) clearInterval(iv);
  }, 50);
})();
