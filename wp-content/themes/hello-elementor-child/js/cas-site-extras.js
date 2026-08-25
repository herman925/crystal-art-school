/* Crystal's Art School — site-wide extras.
   Ported from the prototype's js/site-extras.js, which was never carried across.

   It repairs two site-wide defects, both silent:

   1. window.CasAnnounce was CALLED by three shipped modules — js/cas-free-videos.js,
      js/cas-showcase.js and the inline script in inc/cas-about.php — and DEFINED
      nowhere. Every call site is written `if (window.CasAnnounce) …`, so nothing ever
      threw and every screen-reader announcement on the site was dropped on the floor.
   2. Nothing added `.in-view`. The reveal animations in industrial-1.css are opt-in
      through that class (`.reveal-up.in-view`, `.reveal-one.in-view`,
      `.reveal-brush.in-view`, `.section-title.in-view::before`). Three page modules
      each rolled their own one-liner; every other page — the blog, the homepage's
      community band and testimonial cards, the category and search archives — had no
      motion at all.

   Nothing here is required for the page to be readable. `.reveal-up` and `.reveal-one`
   are `opacity: 1` in the stylesheet, so this file adds the entrance animation and
   never the visibility. A failure leaves the page rendered and still, not blank.

   What was deliberately NOT ported is listed at the foot of the file. */
( function () {
	'use strict';

	/* The prototype's own run-once flag. Kept under the same name so this and
	   js/site-extras.js can never both install themselves on one page. */
	if ( window.__casExtras ) {
		return;
	}

	window.__casExtras = true;

	/* ------------------------------------------------------------------
	   Styles

	   One rule, injected rather than added to a stylesheet, so the live region is
	   never briefly visible on a page whose CSS has not arrived — and so this file
	   stays self-contained. This is the prototype's rule verbatim; it exists nowhere
	   else in the theme.
	   ------------------------------------------------------------------ */

	( function injectStyles() {
		if ( document.getElementById( 'cas-extras-styles' ) ) {
			return;
		}

		var s = document.createElement( 'style' );

		s.id = 'cas-extras-styles';
		s.textContent = '.cas-sr-live{position:absolute!important;width:1px;height:1px;'
			+ 'margin:-1px;padding:0;overflow:hidden;clip:rect(0 0 0 0);clip-path:inset(50%);'
			+ 'white-space:nowrap;border:0;}'
			/* The homepage owns the About column's transform choreography (its `--i:2`
			   stagger is timed for the row it sits in); keep the shared fallback reveal
			   from restarting the 16px rise on an element already in view, which is
			   the visible reverse-jump this file introduced. The prototype's rule,
			   verbatim, minus its .cas-root prefix — this theme puts that class on
			   <body>, so the selectors here need no scope. */
			+ '.about-col.reveal-up.in-view{animation:none;}'
			/* Chalk cursor and dust trail (theme D, fine pointers only). The
			   prototype's rules verbatim, adapted to this theme: .cas-root IS
			   <body>, so `body[data-theme="D"]` is the whole scope and there is
			   no wrapper to prefix. Ported on 2026-08-25 per issue #31 — the
			   file header above records this being left out on purpose until
			   Herman made the call. */
			+ '@media (hover: hover) and (pointer: fine){'
			+ 'body[data-theme="D"]{cursor:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'26\' height=\'26\'%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'5\' fill=\'%23f3ede1\' fill-opacity=\'.92\'/%3E%3Ccircle cx=\'13\' cy=\'13\' r=\'9\' fill=\'none\' stroke=\'%23f3ede1\' stroke-opacity=\'.35\'/%3E%3C/svg%3E") 13 13, auto;}'
			+ 'body[data-theme="D"] a,body[data-theme="D"] button,body[data-theme="D"] [role="button"],body[data-theme="D"] .cover-card,body[data-theme="D"] summary{cursor:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'30\' height=\'30\'%3E%3Ccircle cx=\'15\' cy=\'15\' r=\'7\' fill=\'%23d98a5c\' fill-opacity=\'.95\'/%3E%3Ccircle cx=\'15\' cy=\'15\' r=\'12\' fill=\'none\' stroke=\'%23d98a5c\' stroke-opacity=\'.5\'/%3E%3C/svg%3E") 15 15, pointer;}'
			+ '.cas-chalk-dust{position:fixed;width:7px;height:7px;border-radius:50%;background:#f3ede1;pointer-events:none;z-index:2000;opacity:.0;}'
			+ '}';

		( document.head || document.documentElement ).appendChild( s );
	} )();

	/* ------------------------------------------------------------------
	   The shared polite live region

	   One region for the whole site, created here and appended to <body>. No PHP
	   template prints it and none needs to: a live region has to be present and empty
	   before the message is written into it, which is exactly what ensureLive() does.
	   ------------------------------------------------------------------ */

	var liveEl = null;
	var liveTimer = null;
	var liveQueue = [];

	function ensureLive() {
		if ( liveEl || ! document.body ) {
			return liveEl;
		}

		liveEl = document.createElement( 'div' );
		liveEl.className = 'cas-sr-live';
		liveEl.setAttribute( 'aria-live', 'polite' );
		liveEl.setAttribute( 'aria-atomic', 'true' );
		liveEl.setAttribute( 'role', 'status' );
		document.body.appendChild( liveEl );

		return liveEl;
	}

	/**
	 * Announce a message to screen readers.
	 *
	 * @param {string} msg        The message. Empty and null are ignored.
	 * @param {string} politeness 'assertive' interrupts; anything else is polite.
	 */
	function announce( msg, politeness ) {
		if ( msg === null || msg === undefined || msg === '' ) {
			return;
		}

		msg = String( msg );

		/* Called before <body> existed — hold it and flush when it does. */
		if ( ! ensureLive() ) {
			liveQueue.push( [ msg, politeness ] );

			return;
		}

		liveEl.setAttribute( 'aria-live', 'assertive' === politeness ? 'assertive' : 'polite' );

		/* Clear first. Writing an identical string twice is not a change, so it is
		   not re-announced — which is exactly the case a filter that returns the same
		   count twice runs into. */
		liveEl.textContent = '';
		clearTimeout( liveTimer );
		liveTimer = setTimeout( function () {
			if ( liveEl ) {
				liveEl.textContent = msg;
			}
		}, 60 );
	}

	function flushLive() {
		var q = liveQueue.slice();

		liveQueue.length = 0;
		q.forEach( function ( a ) {
			announce( a[ 0 ], a[ 1 ] );
		} );
	}

	/* ------------------------------------------------------------------
	   Shared overlay plumbing

	   js/cas-free-videos.js and the About lightbox each carry their own copy of the
	   focusable-element test. These are the prototype's versions, exported so the next
	   overlay does not write a fourth. Nothing is retro-fitted to the existing modules
	   here — that is an edit to files this file does not own.
	   ------------------------------------------------------------------ */

	/**
	 * Match `inert` and `aria-hidden` to the visual state.
	 *
	 * `inert` alone is enough in current Chromium; aria-hidden keeps older engines and
	 * the accessibility tree honest as well.
	 *
	 * @param {Element} el
	 * @param {boolean} hidden
	 */
	function setHidden( el, hidden ) {
		if ( ! el ) {
			return;
		}

		if ( hidden ) {
			el.setAttribute( 'inert', '' );
			el.setAttribute( 'aria-hidden', 'true' );
		} else {
			el.removeAttribute( 'inert' );
			el.removeAttribute( 'aria-hidden' );
		}
	}

	var FOCUS_SEL = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),'
		+ 'textarea:not([disabled]),iframe,[tabindex]:not([tabindex="-1"])';

	/**
	 * Focusable descendants that are genuinely reachable right now.
	 *
	 * getClientRects() is the real test, not the selector: a modal's iframe is in the
	 * markup on every page and display:none until a film is loaded, so a trap built on
	 * the selector alone lands focus nowhere.
	 *
	 * @param {Element} root
	 * @return {Array} Focusable elements, in document order.
	 */
	function focusables( root ) {
		if ( ! root ) {
			return [];
		}

		return Array.prototype.filter.call( root.querySelectorAll( FOCUS_SEL ), function ( el ) {
			if ( 'true' === el.getAttribute( 'aria-hidden' ) || el.hasAttribute( 'inert' ) ) {
				return false;
			}

			var cs = window.getComputedStyle( el );

			if ( 'hidden' === cs.visibility || 'none' === cs.display ) {
				return false;
			}

			return el.getClientRects().length > 0;
		} );
	}

	/**
	 * Bind a Tab trap. `isOpen` is read live, so the handler stays bound for the life
	 * of the element rather than being attached and detached around each open.
	 *
	 * @param {Element}  el
	 * @param {Function} isOpen
	 */
	function trapFocus( el, isOpen ) {
		if ( ! el || typeof isOpen !== 'function' ) {
			return;
		}

		el.addEventListener( 'keydown', function ( e ) {
			if ( 'Tab' !== e.key || ! isOpen() ) {
				return;
			}

			var f = focusables( el );

			if ( ! f.length ) {
				e.preventDefault();

				return;
			}

			var first = f[ 0 ];
			var last = f[ f.length - 1 ];
			var a = document.activeElement;

			if ( e.shiftKey && ( a === first || ! el.contains( a ) ) ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && a === last ) {
				e.preventDefault();
				first.focus();
			}
		} );

		/* Focus can also arrive from outside the page — the address bar, an extension.
		   Pull it back in rather than letting it land behind the overlay. */
		document.addEventListener( 'focusin', function ( e ) {
			if ( ! isOpen() || el.contains( e.target ) ) {
				return;
			}

			var f = focusables( el );

			if ( f.length ) {
				f[ 0 ].focus();
			}
		} );
	}

	/* ------------------------------------------------------------------
	   Scroll reveal

	   The prototype ran this two different ways: sub-pages added `.in-view` to
	   everything the moment the page script ran, and index.html ran a scroll-driven
	   check limited to `.section-title, .arch-duo`. The scroll-driven one is the real
	   behaviour, so it is what is generalised here, with the prototype's own threshold
	   (index.html: `r.top < vh * 0.92 && r.bottom > 0`).

	   Deliberately no IntersectionObserver and no requestAnimationFrame. Both are
	   inert in an automated tab, where document.hidden is true — and a reveal that
	   cannot be verified is a reveal that gets reported as broken every few weeks.
	   A rect read on a passive scroll listener works everywhere and is measurable.
	   ------------------------------------------------------------------ */

	var REVEAL_SEL = '.reveal-up, .reveal-one, .reveal-brush, .arch-duo, .section-title';
	var watching = [];

	function collect() {
		Array.prototype.forEach.call( document.querySelectorAll( REVEAL_SEL ), function ( el ) {
			if ( el.classList.contains( 'in-view' ) || watching.indexOf( el ) !== -1 ) {
				return;
			}

			watching.push( el );
		} );
	}

	function check() {
		if ( ! watching.length ) {
			return;
		}

		var vh = window.innerHeight || document.documentElement.clientHeight;
		var still = [];

		for ( var i = 0; i < watching.length; i++ ) {
			var el = watching[ i ];
			var r = el.getBoundingClientRect();

			if ( r.top < vh * 0.92 && r.bottom > 0 ) {
				el.classList.add( 'in-view' );
			} else {
				still.push( el );
			}
		}

		/* Revealed elements drop out of the list, so a long page settles to a few
		   rect reads per scroll rather than one per reveal on the page. */
		watching = still;
	}

	function revealAll() {
		Array.prototype.forEach.call( document.querySelectorAll( REVEAL_SEL ), function ( el ) {
			el.classList.add( 'in-view' );
		} );
		watching = [];
	}

	function startReveal() {
		/* industrial-1.css already collapses every animation to .01ms under reduced
		   motion, so this is not what honours the preference — it just stops the
		   scroll work from running at all when it can have no visible effect. */
		if ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			revealAll();

			return;
		}

		collect();
		check();

		window.addEventListener( 'scroll', check, { passive: true } );
		window.addEventListener( 'resize', check, { passive: true } );

		/* Scroll containers other than the document — the body was one of these until
		   `overflow-x: clip` fixed it, and Elementor sections can still be one. */
		document.addEventListener( 'scroll', check, true );

		/* Late arrivals: images finishing, fonts settling, a module that renders after
		   DOMContentLoaded. Two cheap passes rather than a permanent observer. */
		window.addEventListener( 'load', function () {
			collect();
			check();
		} );

		setTimeout( function () {
			collect();
			check();
		}, 400 );
	}

	/* ------------------------------------------------------------------
	   Chalk dust trail (theme D, fine pointers only)

	   The pointer-following motes that belong to the theme-D chalk cursor.
	   Ported from the prototype's chalkTrail() on 2026-08-25 per issue #31;
	   the CSS half lives in the injected style above. The prototype read the
	   theme through its own curTheme(); here the theme is an attribute on
	   <body> (set by functions.php on wp_body_open), so that is what is read.
	   ------------------------------------------------------------------ */

	function chalkTrail() {
		var finePointer = null;

		try {
			finePointer = window.matchMedia( '(hover: hover) and (pointer: fine)' );
		} catch ( e ) {}

		if ( ! finePointer || ! finePointer.matches ) {
			return;
		}

		var pool = [], N = 6, idx = 0, last = 0;

		for ( var k = 0; k < N; k++ ) {
			var d = document.createElement( 'div' );

			d.className = 'cas-chalk-dust';
			document.body.appendChild( d );
			pool.push( d );
		}

		window.addEventListener( 'pointermove', function ( e ) {
			if ( ! finePointer.matches || 'D' !== document.body.getAttribute( 'data-theme' ) || ( e.pointerType && 'touch' === e.pointerType ) ) {
				return;
			}

			var now = Date.now();

			if ( now - last < 38 ) {
				return;
			}

			last = now;

			var mote = pool[ idx % N ];
			var jx = ( Math.random() - .5 ) * 8;
			var jy = ( Math.random() - .5 ) * 8;

			idx++;

			mote.style.left = ( e.clientX + jx - 3 ) + 'px';
			mote.style.top = ( e.clientY + jy - 3 ) + 'px';
			mote.style.transition = 'none';
			mote.style.opacity = ( 0.35 + Math.random() * 0.4 );
			mote.style.transform = 'scale(' + ( 0.6 + Math.random() * 0.7 ) + ')';

			requestAnimationFrame( function () {
				mote.style.transition = 'opacity .6s ease, transform .6s ease';
				mote.style.opacity = '0';
				mote.style.transform = 'scale(.2) translateY(6px)';
			} );
		}, { passive: true } );
	}

	/* ------------------------------------------------------------------
	   Public surface
	   ------------------------------------------------------------------ */

	/* The name three shipped modules already call. Do not rename it. */
	window.CasAnnounce = announce;

	/* The rest, namespaced. New code should use these rather than write a fourth copy
	   of the focus trap. */
	window.CasA11y = {
		announce: announce,
		setHidden: setHidden,
		focusables: focusables,
		trapFocus: trapFocus,
		reveal: revealAll
	};

	function init() {
		ensureLive();
		flushLive();
		startReveal();
		chalkTrail();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();

/* ------------------------------------------------------------------------------
   DELIBERATELY NOT PORTED from the prototype's js/site-extras.js
   ------------------------------------------------------------------------------

   window.casOpenPrivacy — the cookie bar and the "privacy choices & data rights"
   modal. Its copy is invented placeholder legal text: it tells the reader "You're in
   the UK", offers UK GDPR export / rectify / erase buttons that store nothing and send
   nothing, and answers each one with a written promise ("we'll email a copy of your
   data within 30 days", "your data will be deleted within 30 days"). That is the same
   fabricated wording already withheld from About #terms — see HANDOFF §6.5. Shipping a
   consent banner that records a choice in localStorage and acts on it nowhere is worse
   than having none. If a cookie notice is wanted, the copy has to come from Crystal or
   her adviser and the toggles have to gate something real.

   window.casApplyAuthNav — rewrites the header from a fake `cas-user` localStorage
   object and links to Login.dc.html / Signup.dc.html / Dashboard.dc.html. WordPress has
   real sessions and Tutor has a real dashboard; this would put a fake logged-in chip
   over a real logged-out site.

   The mini-cart drawer, .cart-badge counting and the Apprentice / Fellow tier picker —
   a localStorage cart under key `cas-cart`, priced by multiplying by 1.8. Tutor LMS
   native eCommerce owns the cart, and js/cas-header.js already renders the badge.
   Reviving this would give the site two carts that do not know about each other.
   (It also means nothing on the WordPress side emits [data-add-to-cart][data-tiered],
   which is confirmed: that attribute pair appears nowhere in inc/.)

   chalkTrail() and the theme-D chalk cursor — a custom `cursor: url(…)` for the whole
   themed subtree plus six pointer-following dust motes. It is a real prototype design
   feature and it was genuinely absent here (no `cursor: url` existed anywhere in the
   theme's CSS), but a site-wide custom cursor is a visible design decision, not an
   accessibility repair, and it would also apply under the admin bar and over Elementor
   overlays. It was left out on purpose pending Herman's call — and the call was made
   2026-08-25 (issue #31): both halves are now ported above, gated to theme D and fine
   pointers exactly as the prototype gated them.

   watchTheme() / syncTheme() / stamp() — kept the cart drawer and cookie bar on the
   same [data-theme] as .cas-root, because the prototype appended them to <body>,
   outside the themed wrapper. Here .cas-root IS <body>, and neither of those two
   elements is ported, so there is nothing left outside the themed subtree to stamp.

   curTheme() — js/cas-header.js already reads and writes the theme, on the body.
   ------------------------------------------------------------------------------ */
