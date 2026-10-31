/* Hero entrance and scroll parallax, ported from the prototype's index.html.
   Timings, easings and offsets are its values, not re-invented ones.

   This is enhancement only: the hero is fully visible and usable with this file
   absent or GSAP unavailable. Nothing here sets a from-state in CSS, so a failure
   leaves the composition rendered rather than blank. */
( function () {
	'use strict';

	var g = window.gsap;
	var hero = document.querySelector( '.hero' );

	if ( ! g || ! hero ) {
		return;
	}

	if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		return;
	}

	var slide = hero.querySelector( '.hero__slide.is-active' ) || hero;

	function q( sel ) {
		return slide.querySelector( sel );
	}

	/* The two photographs are revealed by a clip-path wipe running left to right, with
	   a 3px skew that eases out as they settle. The skew is what gives the move its
	   sense of momentum — a pure translate reads as mechanical. The text follows a
	   beat later so it appears to answer the image rather than land on top of it. */
	var tl = g.timeline( { defaults: { ease: 'power3.out' } } );

	/* fromTo, not from: `from` leaves GSAP holding skewX: 3 in its cache for the
	   element, so the next transform written to it — the scroll parallax below —
	   re-applies a 3 degree slant that never goes away. Stating the end values
	   explicitly resolves the skew to zero and leaves nothing cached. */
	tl.fromTo(
		q( '.hero__img--large' ),
		{ clipPath: 'inset(0 100% 0 0)', skewX: 3 },
		{ clipPath: 'inset(0 0% 0 0)', skewX: 0, duration: 1.1 },
		0.20
	);

	tl.fromTo(
		q( '.hero__img--small' ),
		{ clipPath: 'inset(0 100% 0 0)' },
		{ clipPath: 'inset(0 0% 0 0)', duration: 1.2 },
		0.50
	);

	[
		[ '.hero__title', 0.65 ],
		[ '.hero__subtitle', 0.78 ],
		[ '.hero__cta', 0.90 ],
	].forEach( function ( pair ) {
		tl.from( q( pair[ 0 ] ), {
			opacity: 0,
			y: 16,
			duration: 0.8,
			ease: 'power2.out',
		}, pair[ 1 ] );
	} );

	/* Scroll parallax: the layers drift at different rates so the composition has
	   depth as the page moves under it. Cheap plain-scroll listener rather than
	   ScrollTrigger — it only needs to run over the first screen and a bit, and
	   ScrollTrigger's machinery buys nothing for three gsap.set calls. */
	/* Every slide's images, not just the first. The active slide changes as the
	   carousel rotates, so targeting one slide would drift the parallax onto a hidden
	   element the moment it advanced. Inactive slides are invisible, so setting them
	   too costs nothing and keeps every slide correct whenever it becomes active. */
	var large = [].slice.call( hero.querySelectorAll( '.hero__img--large' ) );
	var small = [].slice.call( hero.querySelectorAll( '.hero__img--small' ) );
	var panel = hero.querySelector( '.hero__panel' );
	var ticking = false;

	function parallax() {
		var y = window.scrollY;

		if ( y < window.innerHeight * 1.2 ) {
			if ( large.length ) {
				g.set( large, { y: y * 0.12 } );
			}

			if ( small.length ) {
				g.set( small, { y: y * 0.22 } );
			}

			if ( panel ) {
				g.set( panel, { y: y * 0.05 } );
			}
		}

		ticking = false;
	}

	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( parallax );
		}
	}, { passive: true } );
} )();

/* Desktop Home parity. Mobile adaptation remains a separate pass. */
(function () {
  'use strict';
  var desktop = window.matchMedia('(min-width: 861px)');
  var gallery = document.querySelector('.shop-layout .gallery-tile');
  var card = document.querySelector('.shop-rail .course-card');
  function sizeGallery() {
    if (!gallery || !card) return;
    gallery.classList.toggle('cas-gallery-sized', desktop.matches);
    if (desktop.matches) gallery.style.height = Math.round(card.offsetHeight * .66) + 'px';
    else gallery.style.removeProperty('height');
  }
  sizeGallery();
  window.addEventListener('resize', sizeGallery);
  if (gallery && card && window.ResizeObserver) new ResizeObserver(sizeGallery).observe(card);
  if (document.fonts) document.fonts.ready.then(sizeGallery);

  var g = window.gsap;
  if (!g || !desktop.matches || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  // The reference removes these generic rises before its own desktop Home choreography.
  document.querySelectorAll('#content .reveal-up').forEach(function (el) { el.classList.remove('reveal-up'); });
  var scenes = [];
  function scene(trigger, elements, play) {
    if (!trigger || !elements.length) return;
    g.set(elements, {opacity: 0});
    scenes.push({trigger: trigger, play: play, done: false});
  }
  document.querySelectorAll('#content .section-title').forEach(function (title) {
    var left = title.classList.contains('is-left');
    scene(title, [title], function () {
      g.fromTo(title, {opacity: 0, y: -26, rotateZ: left ? -5 : 4, scale: .9, transformOrigin: left ? 'left top' : 'center top'},
        {opacity: 1, y: 0, rotateZ: 0, scale: 1, duration: .9, ease: 'elastic.out(1, .65)'});
    });
  });
  var quotes = Array.prototype.slice.call(document.querySelectorAll('.testi-card'));
  scene(document.querySelector('.testi-grid'), quotes, function () {
    g.fromTo(quotes, {opacity: 0, y: 56, scale: .92}, {opacity: 1, y: 0, scale: 1, duration: .7, ease: 'back.out(1.4)', stagger: .12});
  });
  var firstPass = true;
  function drive() {
    var vh = window.innerHeight || document.documentElement.clientHeight;
    var staged = 0;
    scenes.forEach(function (item) {
      if (item.done) return;
      var rect = item.trigger.getBoundingClientRect();
      if (rect.top < vh * .86 && rect.bottom > 30) {
        item.done = true;
        if (firstPass) g.delayedCall(staged++ * .45, item.play);
        else item.play();
      }
    });
    firstPass = false;
  }
  setTimeout(drive, 260);
  window.addEventListener('scroll', drive, {passive: true});
  window.addEventListener('resize', drive);
  document.addEventListener('focusin', drive);
})();
