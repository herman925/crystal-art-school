/* Course photographs — click a thumbnail, the large picture changes.
   Direction document 7.1 struck auto-rotation out, so there is no timer here and
   nothing moves unless someone asks it to.

   The buttons are rendered active-and-labelled by PHP, so the page is correct
   before this file arrives; all it adds is the switching. */
( function () {
	'use strict';

	var strip = document.getElementById( 'co-thumbs' );
	var cover = document.getElementById( 'co-cover' );
	var syllabus = document.getElementById( 'co-syllabus' );
	if ( syllabus ) {
		syllabus.addEventListener( 'click', function ( ev ) {
			var button = ev.target.closest( '.syl-phase-btn' );
			if ( ! button ) { return; }
			var body = document.getElementById( button.getAttribute( 'aria-controls' ) );
			if ( ! body ) { return; }
			var expanded = button.getAttribute( 'aria-expanded' ) === 'true';
			button.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
			body.hidden = expanded;
		} );
	}

	if ( ! strip || ! cover ) {
		return;
	}

	strip.addEventListener( 'click', function ( ev ) {
		var btn = ev.target.closest( '.co-thumb' );

		if ( ! btn || 'true' === btn.getAttribute( 'aria-pressed' ) ) {
			return;
		}

		var srcset = btn.getAttribute( 'data-srcset' );

		/* srcset before src, so the browser makes one choice and one request rather
		   than fetching the fallback and then replacing it. */
		if ( srcset ) {
			cover.setAttribute( 'srcset', srcset );
		} else {
			cover.removeAttribute( 'srcset' );
		}

		cover.setAttribute( 'src', btn.getAttribute( 'data-full' ) );
		cover.setAttribute( 'alt', btn.getAttribute( 'data-alt' ) || '' );

		/* WordPress prints the intrinsic width and height of whichever picture was
		   rendered first. Left in place they describe the wrong file after a swap,
		   which is what a browser reads to reserve space before an image loads. The
		   slot's own aspect-ratio holds the space, so they are dropped. */
		cover.removeAttribute( 'width' );
		cover.removeAttribute( 'height' );

		Array.prototype.forEach.call( strip.querySelectorAll( '.co-thumb' ), function ( other ) {
			var on = other === btn;

			other.classList.toggle( 'is-active', on );
			other.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	} );
} )();
