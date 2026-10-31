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
			var nextTheme = 'D' === currentTheme() ? 'A' : 'D';
			applyTheme( nextTheme );
			if ( window.CasAnnounce ) { window.CasAnnounce( nextTheme === 'D' ? 'Dark mode on.' : 'Light mode on.' ); }

			return;
		}

		var burger = ev.target.closest( '.nav-burger' );

		if ( burger ) {
			var open = 'true' === burger.getAttribute( 'aria-expanded' );
			burger.setAttribute( 'aria-expanded', open ? 'false' : 'true' );
			document.querySelector( '.nav-sheet' ).classList.toggle( 'open', ! open );
		}
	} );

	// Desktop disclosure: hover, explicit caret, Escape, outside click and keyboard focus.
	document.querySelectorAll( '.nav-drop' ).forEach( function ( drop ) {
		var caret = drop.querySelector( '.nav-caret-btn' );
		if ( ! caret ) { return; }
		function setOpen( open ) {
			open = open && window.matchMedia( '(min-width: 861px)' ).matches;
			drop.classList.toggle( 'open', open );
			caret.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
		var hoverCloseTimer = null;
		drop.addEventListener( 'mouseenter', function () { clearTimeout( hoverCloseTimer ); setOpen( true ); } );
		drop.addEventListener( 'mouseleave', function () { hoverCloseTimer = setTimeout( function () { if ( ! drop.contains( document.activeElement ) ) { setOpen( false ); } }, 320 ); } );
		caret.addEventListener( 'click', function () { setOpen( caret.getAttribute( 'aria-expanded' ) !== 'true' ); } );
		drop.addEventListener( 'focusout', function ( event ) { if ( ! drop.contains( event.relatedTarget ) ) { setOpen( false ); } } );
		drop.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' ) { setOpen( false ); caret.focus(); return; }
			if ( event.key === 'ArrowDown' || event.key === 'ArrowUp' ) {
				var items = Array.prototype.slice.call( drop.querySelectorAll( '.mega-cat' ) );
				if ( ! items.length ) { return; }
				event.preventDefault(); setOpen( true );
				var index = items.indexOf( event.target );
				items[ index < 0 ? ( event.key === 'ArrowDown' ? 0 : items.length - 1 ) : ( index + ( event.key === 'ArrowDown' ? 1 : items.length - 1 ) ) % items.length ].focus();
			}
		} );
		document.addEventListener( 'click', function ( event ) { if ( ! drop.contains( event.target ) ) { setOpen( false ); } } );
		window.addEventListener( 'resize', function () { if ( ! window.matchMedia( '(min-width: 861px)' ).matches ) { setOpen( false ); } } );
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
