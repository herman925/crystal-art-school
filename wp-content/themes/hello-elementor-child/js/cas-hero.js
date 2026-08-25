/* Homepage hero carousel.
   The crossfade itself is CSS — .hero__slide has opacity 0 with a .6s transition and
   .is-active takes it to 1. This only moves the class, updates the counter, and drives
   autoplay. */
( function () {
	'use strict';

	var hero = document.querySelector( '.hero' );

	if ( ! hero ) {
		return;
	}

	var slides = [].slice.call( hero.querySelectorAll( '.hero__slide' ) );

	if ( slides.length < 2 ) {
		return;
	}

	var counter = hero.querySelector( '.hero__counter-current' );
	var pauseBtn = hero.querySelector( '[data-hero-pause]' );
	var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var index = 0;
	var timer = null;
	var paused = false;

	/* Inactive slides are taken out of the tab order and hidden from screen readers.
	   Without this, all three headings and all three "View courses" links are exposed
	   at once and a keyboard user tabs into invisible controls. */
	function setHidden( slide, hidden ) {
		if ( hidden ) {
			slide.setAttribute( 'inert', '' );
			slide.setAttribute( 'aria-hidden', 'true' );
		} else {
			slide.removeAttribute( 'inert' );
			slide.removeAttribute( 'aria-hidden' );
		}
	}

	function show( next ) {
		index = ( next + slides.length ) % slides.length;

		slides.forEach( function ( slide, k ) {
			slide.classList.toggle( 'is-active', k === index );
			setHidden( slide, k !== index );
		} );

		if ( counter ) {
			counter.textContent = ( '0' + ( index + 1 ) ).slice( -2 );
		}
	}

	function stop() {
		if ( timer ) {
			clearInterval( timer );
			timer = null;
		}
	}

	function start() {
		stop();

		if ( reduced || paused ) {
			return;
		}

		timer = setInterval( function () {
			show( index + 1 );
		}, 6000 );
	}

	hero.addEventListener( 'click', function ( ev ) {
		var arrow = ev.target.closest( '[data-hero]' );

		if ( arrow ) {
			show( index + ( 'next' === arrow.getAttribute( 'data-hero' ) ? 1 : -1 ) );
			start();

			return;
		}

		if ( ev.target.closest( '[data-hero-pause]' ) ) {
			paused = ! paused;
			pauseBtn.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
			paused ? stop() : start();
		}
	} );

	/* Autoplay yields to anyone actually looking at or using the carousel, and to a
	   backgrounded tab. */
	hero.addEventListener( 'mouseenter', stop );
	hero.addEventListener( 'mouseleave', start );
	hero.addEventListener( 'focusin', stop );
	hero.addEventListener( 'focusout', start );

	document.addEventListener( 'visibilitychange', function () {
		document.hidden ? stop() : start();
	} );

	/* Under reduced motion there is nothing to pause, so the control is removed rather
	   than left sitting there doing nothing. */
	if ( reduced && pauseBtn ) {
		pauseBtn.remove();
	}

	show( 0 );
	start();
} )();
