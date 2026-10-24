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

	( function () {
		var sequence = 0;

		function renderCart( count ) {
			document.querySelectorAll( '.cart-badge' ).forEach( function ( badge ) {
				var known = Number.isSafeInteger( count ) && count >= 0;
				badge.textContent = known ? count : '';
				badge.hidden = ! known || count === 0;
				badge.style.display = known && count > 0 ? 'grid' : 'none';
				var button = badge.closest( '.cart-btn' );
				if ( button ) {
					button.setAttribute( 'aria-label', known
						? 'Shopping cart, ' + count + ( count === 1 ? ' item' : ' items' )
						: 'Shopping cart, item count unavailable' );
				}
			} );
		}

		function refreshCart() {
			var request = ++sequence;
			renderCart( null );
			if ( ! window.casCartCount || ! document.querySelector( '.cart-badge' ) ) {
				return;
			}
			var data = new URLSearchParams();
			data.set( 'action', 'cas_cart_count' );
			data.set( 'nonce', window.casCartCount.nonce );
			fetch( window.casCartCount.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				cache: 'no-store',
				redirect: 'error',
				body: data,
			} ).then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Cart count unavailable' );
				}
				return response.json();
			} ).then( function ( response ) {
				if ( request === sequence && response.success === true && response.data && response.data.known === true ) {
					renderCart( response.data.count );
				}
			} ).catch( function () {
				if ( request === sequence ) {
					renderCart( null );
				}
			} );
		}

		document.addEventListener( 'tutorAddToCartEvent', refreshCart );
		document.addEventListener( 'tutorRemoveCartEvent', refreshCart );
		window.addEventListener( 'focus', refreshCart );
		window.addEventListener( 'pageshow', refreshCart );
		window.addEventListener( 'pagehide', function () {
			++sequence;
			renderCart( null );
		} );
		refreshCart();
	} )();
} )();
