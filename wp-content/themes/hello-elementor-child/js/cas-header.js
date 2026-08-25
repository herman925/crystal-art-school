/* Header behaviour: theme toggle, cart badge, burger.
   The prototype re-rendered the whole header on a 50ms poll to survive its runtime
   overwriting the DOM. WordPress renders it once, so this binds once. */
( function () {
	'use strict';

	var body = document.body;

	function currentTheme() {
		return body.getAttribute( 'data-theme' ) === 'A' ? 'A' : 'D';
	}

	function applyTheme( theme ) {
		body.setAttribute( 'data-theme', theme );

		var toggle = document.querySelector( '.mode-toggle' );

		if ( toggle ) {
			/* aria-pressed, not just aria-label: a toggle has to report which way it is
			   currently set, not only what pressing it would do. */
			toggle.setAttribute( 'aria-pressed', 'D' === theme ? 'true' : 'false' );
		}

		try {
			localStorage.setItem( 'cas-theme', theme );
		} catch ( e ) {}
	}

	document.addEventListener( 'click', function ( ev ) {
		var toggle = ev.target.closest( '.mode-toggle' );

		if ( toggle ) {
			applyTheme( 'D' === currentTheme() ? 'A' : 'D' );

			return;
		}

		var burger = ev.target.closest( '.nav-burger' );

		if ( burger ) {
			var open = 'true' === burger.getAttribute( 'aria-expanded' );
			burger.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			document.querySelector( '.nav-sheet' ).classList.toggle( 'open', ! open );
		}
	} );

	/* The count belongs in the button's accessible name. The badge itself stays
	   aria-hidden, or the count is announced twice. */
	( function renderCart() {
		var n = 0;

		try {
			n = JSON.parse( localStorage.getItem( 'cas-cart' ) || '[]' ).reduce( function ( a, i ) {
				return a + ( i.qty || 1 );
			}, 0 );
		} catch ( e ) {}

		var badge = document.querySelector( '.cart-badge' );

		if ( ! badge ) {
			return;
		}

		badge.textContent = n;
		badge.style.display = n > 0 ? 'grid' : 'none';

		var btn = badge.closest( '.cart-btn' );

		if ( btn ) {
			btn.setAttribute( 'aria-label', n > 0 ? 'Shopping cart, ' + n + ' items' : 'Shopping cart' );
		}
	} )();
} )();
