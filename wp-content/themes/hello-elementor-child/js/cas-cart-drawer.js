/* Prototype basket presentation; Tutor owns the cart, prices and removal. */
( function () {
	'use strict';
	if ( ! window.casCartDrawer || ! window.casCartCount || ! window.CasA11y ) { return; }
	var config = window.casCartDrawer;
	var backdrop = document.createElement( 'div' );
	backdrop.className = 'mc-backdrop';
	var drawer = document.createElement( 'div' );
	drawer.className = 'mc-drawer';
	drawer.setAttribute( 'role', 'dialog' );
	drawer.setAttribute( 'aria-modal', 'true' );
	drawer.setAttribute( 'aria-label', 'Shopping cart' );
	drawer.id = 'cas-basket-drawer';
	drawer.innerHTML = '<div class="mc-inner"><div class="mc-head"><div class="mc-title"><span class="ast" aria-hidden="true">✦</span> Your basket</div>'
		+ '<button class="mc-close" type="button" aria-label="Close cart">✕</button></div>'
		+ '<p class="mc-notice" role="status"></p><div class="mc-list"></div><div class="mc-footwrap"></div></div>';
	document.body.append( backdrop, drawer );
	var list = drawer.querySelector( '.mc-list' );
	var foot = drawer.querySelector( '.mc-footwrap' );
	var notice = drawer.querySelector( '.mc-notice' );
	var closeButton = drawer.querySelector( '.mc-close' );
	var opener = null;
	var overflow = '';
	var sequence = 0;
	var removing = false;
	var open = false;
	window.CasA11y.setHidden( drawer, true );
	window.CasA11y.trapFocus( drawer, function () { return open; } );

	function anchor( text, href ) {
		var link = document.createElement( 'a' );
		link.className = 'mc-btn primary';
		link.textContent = text;
		link.href = href;
		return link;
	}
	function unavailable() {
		list.replaceChildren(); foot.replaceChildren();
		notice.textContent = 'Your basket could not be loaded. Please try again or open your basket.';
		var retry = document.createElement( 'button' );
		retry.type = 'button'; retry.className = 'mc-btn ghost'; retry.textContent = 'Try again';
		retry.addEventListener( 'click', refresh );
		foot.append( retry, anchor( 'Open basket →', config.cartUrl ) );
	}
	async function post( data ) {
		var response = await fetch( window.casCartCount.ajaxUrl, {
			method: 'POST', credentials: 'same-origin', cache: 'no-store', redirect: 'error', body: data
		} );
		if ( ! response.ok ) { throw new Error( 'Basket request failed' ); }
		return response.json();
	}
	async function refresh() {
		var request = ++sequence;
		if ( ! open ) { return; }
		list.replaceChildren(); foot.replaceChildren(); notice.textContent = 'Loading your basket…';
		list.setAttribute( 'aria-busy', 'true' );
		try {
			var data = new URLSearchParams( { action: 'cas_cart_count', nonce: window.casCartCount.nonce, view: 'drawer' } );
			var response = await post( data );
			if ( request !== sequence || ! open ) { return; }
			var cart = response && response.data;
			if ( response.success !== true || ! cart || cart.known !== true || ! Number.isSafeInteger( cart.count ) || cart.count < 0 || typeof cart.html !== 'string' ) {
				throw new Error( 'Basket result unavailable' );
			}
			list.innerHTML = cart.html;
			if ( list.querySelectorAll( '.mc-line' ).length !== cart.count ) { throw new Error( 'Incomplete basket result' ); }
			var summary = list.querySelector( '.mc-foot' );
			if ( summary ) { foot.appendChild( summary ); }
			notice.textContent = '';
			if ( ! drawer.contains( document.activeElement ) ) { closeButton.focus(); }
			window.CasA11y.announce( cart.count ? 'Basket updated. ' + cart.count + ( cart.count === 1 ? ' item.' : ' items.' ) : 'Your basket is empty.' );
		} catch ( error ) {
			if ( request === sequence && open ) { unavailable(); }
		} finally {
			if ( request === sequence ) { list.removeAttribute( 'aria-busy' ); }
		}
	}
	function fitDrawer() {
		if ( ! open ) { return; }
		var toolbar = document.getElementById( 'wpadminbar' );
		var top = toolbar ? Math.max( 0, toolbar.getBoundingClientRect().bottom ) : 0;
		drawer.style.setProperty( '--cas-drawer-top', Math.min( top, window.innerHeight ) + 'px' );
	}
	window.addEventListener( 'resize', fitDrawer );
	if ( window.visualViewport ) { window.visualViewport.addEventListener( 'resize', fitDrawer ); }

	function close() {
		if ( ! open ) { return; }
		open = false; ++sequence;
		drawer.classList.remove( 'open' ); backdrop.classList.remove( 'open' );
		window.CasA11y.setHidden( drawer, true );
		document.body.style.overflow = overflow;
		if ( opener && opener.isConnected ) { opener.focus(); }
	}
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.cart-btn' );
		if ( ! button || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) { return; }
		event.preventDefault();
		if ( open ) { return; }
		opener = button; open = true;
		fitDrawer(); drawer.scrollTop = 0;
		overflow = document.body.style.overflow; document.body.style.overflow = 'hidden';
		drawer.classList.add( 'open' ); backdrop.classList.add( 'open' );
		window.CasA11y.setHidden( drawer, false ); closeButton.focus();
		if ( config.requiresLogin ) {
			list.replaceChildren(); foot.replaceChildren();
			notice.textContent = 'Log in to view your basket.';
			foot.appendChild( anchor( 'Log in →', config.loginUrl ) );
		} else { refresh(); }
	} );
	closeButton.addEventListener( 'click', close );
	backdrop.addEventListener( 'click', close );
	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' && open ) { event.preventDefault(); close(); }
	} );
	drawer.addEventListener( 'click', async function ( event ) {
		if ( event.target.closest( '[data-keep]' ) ) { close(); return; }
		var button = event.target.closest( '.mc-rm[data-course-id]' );
		if ( ! button || removing ) { return; }
		removing = true; button.disabled = true;
		var summary = foot.querySelector( 'a' );
		if ( summary ) { summary.inert = true; }
		notice.textContent = 'Removing course…';
		try {
			var response = await post( new URLSearchParams( {
				action: config.removeAction, _tutor_nonce: config.removeNonce, course_id: button.dataset.courseId
			} ) );
			if ( response.status_code !== 200 ) { throw new Error( 'Course removal failed' ); }
			document.dispatchEvent( new CustomEvent( 'tutorRemoveCartEvent', { detail: { cart_count: response.data && response.data.cart_count } } ) );
			// Tutor's cart page owns its own renderer and must not retain the removed row.
			if ( document.querySelector( '.tutor-cart-course-item' ) ) { window.location.reload(); }
		} catch ( error ) {
			if ( open ) { notice.textContent = 'The course could not be removed. Please try again.'; }
		} finally {
			removing = false; button.disabled = false;
			if ( summary ) { summary.inert = false; }
		}
	} );
	document.addEventListener( 'tutorRemoveCartEvent', function () { if ( open ) { refresh(); } } );
	document.addEventListener( 'tutorAddToCartEvent', function () { if ( open ) { refresh(); } } );
	window.addEventListener( 'focus', function () { if ( open && ! config.requiresLogin && ! removing ) { refresh(); } } );
	window.addEventListener( 'pagehide', close );
} )();
