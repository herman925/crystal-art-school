/**
 * Homepage "New release" carousel behaviour.
 *
 * The slides and dots are rendered server-side by inc/cas-home-releases.php; this
 * file is the browser half the server cannot do — showing one slide, hiding the
 * others, the 6s autoplay and its pause control. Ported from the prototype's inline
 * builder (index.html:749-816) on 2026-08-25, with its behaviour kept: the pause
 * button's state rides on aria-pressed; pointer-over, focus-inside and a hidden tab
 * all stop the autoplay (WCAG 2.2.2); hidden slides get `inert` + aria-hidden so a
 * faded slide's links leave the tab order; and under reduced motion nothing
 * auto-advances and the pause button is removed entirely.
 */
( function () {
	'use strict';

	var carousel = document.getElementById( 'rel-carousel' );

	if ( ! carousel ) {
		return;
	}

	var slides = Array.prototype.slice.call( carousel.querySelectorAll( '.rel-slide' ) );
	var dots = Array.prototype.slice.call( ( document.getElementById( 'rel-dots' ) || carousel ).querySelectorAll( 'button[data-release-id]' ) );

	if ( ! slides.length ) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/* #07 / #23: a slide that is faded out keeps its links and buttons in the tab
	   order and its headings in the accessibility tree unless it is told otherwise.
	   `inert` does both; aria-hidden keeps older engines honest. */
	function setSlideHidden( el, hidden ) {
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

	var current = 0;

	function announce( message ) {
		if ( window.CasAnnounce ) {
			window.CasAnnounce( message );
		}
	}

	function show( i, spoken ) {
		current = ( i + slides.length ) % slides.length;

		slides.forEach( function ( slide, k ) {
			slide.classList.toggle( 'is-active', k === current );
			setSlideHidden( slide, k !== current );
		} );

		dots.forEach( function ( dot, k ) {
			dot.classList.toggle( 'active', k === current );

			if ( k === current ) {
				dot.setAttribute( 'aria-current', 'true' );
			} else {
				dot.removeAttribute( 'aria-current' );
			}
		} );

		if ( spoken ) {
			var titleEl = slides[ current ].querySelector( '.rel-title' );
			var title = titleEl ? ( titleEl.getAttribute( 'title' ) || titleEl.textContent || '' ).trim() : '';

			announce( 'Release ' + ( current + 1 ) + ' of ' + slides.length + '. ' + title );
		}
	}

	show( 0, false );

	/* #21: WCAG 2.2.2 — anything that moves for more than five seconds beside other
	   content needs a user-operable stop: the explicit pause button, plus the
	   implicit stops (pointer over it, focus inside it, tab hidden). Under reduced
	   motion nothing auto-advances, so the button is removed rather than left as a
	   control over something that never moves. */
	var pauseBtn = carousel.querySelector( '[data-rel-pause]' );
	var timer = null;
	var paused = false;
	var hovered = false;
	var focused = false;

	function shouldRun() {
		return ! reduce && ! paused && ! hovered && ! focused && ! document.hidden;
	}

	function sync() {
		if ( timer ) {
			clearInterval( timer );
			timer = null;
		}

		if ( shouldRun() ) {
			timer = setInterval( function () {
				show( current + 1, false );
			}, 6000 );
		}
	}

	if ( pauseBtn ) {
		if ( reduce ) {
			pauseBtn.parentNode.removeChild( pauseBtn );
		} else {
			pauseBtn.addEventListener( 'click', function () {
				paused = ! paused;
				pauseBtn.setAttribute( 'aria-pressed', paused ? 'true' : 'false' );
				pauseBtn.setAttribute( 'aria-label', ( paused ? 'Play the ' : 'Pause the ' ) + 'new releases carousel' );
				announce( paused ? 'New releases carousel paused.' : 'New releases carousel playing.' );
				sync();
			} );
		}
	}

	carousel.addEventListener( 'mouseenter', function () { hovered = true; sync(); } );
	carousel.addEventListener( 'mouseleave', function () { hovered = false; sync(); } );
	carousel.addEventListener( 'focusin', function () { focused = true; sync(); } );
	carousel.addEventListener( 'focusout', function () {
		setTimeout( function () {
			focused = carousel.contains( document.activeElement );
			sync();
		}, 0 );
	} );

	document.addEventListener( 'visibilitychange', sync );

	dots.forEach( function ( dot, k ) {
		dot.addEventListener( 'click', function () {
			show( k, true );
			sync();
		} );
	} );

	sync();
} )();
