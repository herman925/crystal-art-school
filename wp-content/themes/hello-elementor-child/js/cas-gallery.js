/* Crystal's Art School — Gallery rail and quick view.
   The rail reads only the controls and data attributes emitted by
   inc/cas-gallery.php. It never invents media, prices, or artwork copy. */
(function () {
	'use strict';

	var grid = document.getElementById( 'store-grid' );

	if ( grid ) {
		var cells      = Array.prototype.slice.call( grid.querySelectorAll( '.art-card' ) );
		var countEl    = document.getElementById( 'store-count' );
		var emptyEl    = document.getElementById( 'store-empty' );
		var activeWrap = document.getElementById( 'active-tags' );
		var slider     = document.getElementById( 'artwork-price-slider' );
		var priceUI    = document.getElementById( 'artwork-price-ui' );
		var priceVal   = document.getElementById( 'artwork-price-val' );
		var clear      = document.getElementById( 'artwork-filter-clear' );
		var sortRail   = document.getElementById( 'artwork-sort' );
		var mediumRail = document.querySelector( '.fgroup[data-group="medium"]' );
		var typeRail   = document.querySelector( '.fgroup[data-group="type"]' );
		var initialOrder = cells.slice();
		var state      = { medium: [], type: [], maxPrice: Infinity, sort: 'newest' };

		function esc( value ) {
			return String( value == null ? '' : value )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		}

		function formatPrice( value ) {
			return String( value ).replace( /\.0+$/, '' );
		}

		function cellPrice( cell ) {
			var raw = cell.getAttribute( 'data-price' );
			var value = parseFloat( raw );

			return raw && !isNaN( value ) ? value : null;
		}

		function matches( cell ) {
			var price;

			if ( state.medium.length && !state.medium.some( function ( value ) { return ( cell.getAttribute( 'data-medium' ) || '' ).split( /,\s*/ ).indexOf( value ) >= 0; } ) ) {
				return false;
			}
			if ( state.type.length && !state.type.some( function ( value ) { return ( cell.getAttribute( 'data-type' ) || '' ).split( /,\s*/ ).indexOf( value ) >= 0; } ) ) {
				return false;
			}

			/* An unpriced piece remains visible at "Any price" but cannot claim to
			   satisfy a finite maximum. */
			if ( state.maxPrice !== Infinity ) {
				price = cellPrice( cell );
				if ( null === price || price > state.maxPrice ) {
					return false;
				}
			}

			return true;
		}

		function updateFilterButtons( rail, group ) {
			if ( ! rail ) {
				return;
			}

			Array.prototype.forEach.call( rail.querySelectorAll( '.fopt' ), function ( button ) {
				var active = state[ group ].indexOf( button.getAttribute( 'data-val' ) ) >= 0;
				button.classList.toggle( 'active', active );
				button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
		}

		function priceLabel() {
			if ( ! slider || ! priceVal ) {
				return '';
			}

			var value  = parseFloat( slider.value );
			var top    = parseFloat( slider.max );
			var symbol = priceVal.getAttribute( 'data-symbol' ) || '';

			return value >= top ? 'Up to ' + symbol + formatPrice( top ) + '+' : 'Up to ' + symbol + formatPrice( value );
		}

		function syncPrice() {
			if ( ! slider || ! priceUI || ! priceVal ) {
				return;
			}

			var min   = parseFloat( slider.min );
			var max   = parseFloat( slider.max );
			var value = parseFloat( slider.value );
			var span  = max - min || 1;
			var label = priceLabel();

			priceVal.textContent = label;
			slider.setAttribute( 'aria-valuetext', label );
			priceUI.style.setProperty( '--pct', ( ( value - min ) / span * 100 ) + '%' );
			state.maxPrice = value >= max ? Infinity : value;
		}

		function activeTags() {
			if ( ! activeWrap ) {
				return;
			}

			var html = [ 'medium', 'type' ].map( function ( group ) {
				return state[ group ].map( function ( value ) {
					return '<button class="active-tag" type="button" data-filter="' + group + '" data-val="'
						+ esc( value ) + '" aria-label="Remove ' + group + ' filter: ' + esc( value ) + '">'
						+ esc( value ) + '</button>';
				} ).join( '' );
			} ).join( '' );

			if ( state.maxPrice !== Infinity ) {
				html += '<button class="active-tag" type="button" data-filter="price" aria-label="Remove price filter">'
					+ esc( 'Up to ' + ( priceVal ? priceVal.getAttribute( 'data-symbol' ) || '' : '' ) + formatPrice( state.maxPrice ) )
					+ '</button>';
			}

			activeWrap.innerHTML = html;
		}

		function apply() {
			var shown = 0;

			cells.forEach( function ( cell ) {
				var visible = matches( cell );
				cell.classList.toggle( 'hidden', !visible );
				if ( visible ) {
					shown++;
				}
			} );

			if ( countEl ) {
				countEl.textContent = shown + ( shown === 1 ? ' piece' : ' pieces' );
			}
			if ( emptyEl ) {
				emptyEl.style.display = shown ? 'none' : 'block';
			}

			activeTags();
		}

		function sortCells() {
			var order = initialOrder.slice();

			order.sort( function ( first, second ) {
				var a;
				var b;

				if ( state.sort === 'newest' || state.sort === 'oldest' ) {
					a = parseInt( first.getAttribute( 'data-date' ), 10 ) || 0;
					b = parseInt( second.getAttribute( 'data-date' ), 10 ) || 0;
					return state.sort === 'newest' ? b - a : a - b;
				}

				a = cellPrice( first );
				b = cellPrice( second );
				if ( null === a && null === b ) { return 0; }
				if ( null === a ) { return 1; }
				if ( null === b ) { return -1; }
				return state.sort === 'price-asc' ? a - b : b - a;
			} );

			order.forEach( function ( cell ) {
				grid.appendChild( cell );
			} );
		}

		function setSortActive() {
			if ( ! sortRail ) {
				return;
			}

			Array.prototype.forEach.call( sortRail.querySelectorAll( '.fopt' ), function ( button ) {
				var active = button.getAttribute( 'data-val' ) === state.sort;
				button.classList.toggle( 'active', active );
				button.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
			} );
		}

		function wireFilter( rail, group ) {
			if ( ! rail ) {
				return;
			}
			Array.prototype.forEach.call( rail.querySelectorAll( '.fopt' ), function ( button ) {
				button.addEventListener( 'click', function () {
					var value = button.getAttribute( 'data-val' );
					var index = state[ group ].indexOf( value );
					if ( index >= 0 ) {
						state[ group ].splice( index, 1 );
					} else {
						state[ group ].push( value );
					}
					updateFilterButtons( rail, group );
					apply();
				} );
			} );
		}
		wireFilter( mediumRail, 'medium' );
		wireFilter( typeRail, 'type' );

		if ( slider && priceUI && priceVal ) {
			slider.addEventListener( 'input', syncPrice );
			slider.addEventListener( 'change', function () {
				syncPrice();
				apply();
			} );
			syncPrice();
		}

		if ( sortRail ) {
			Array.prototype.forEach.call( sortRail.querySelectorAll( '.fopt' ), function ( button ) {
				button.addEventListener( 'click', function () {
					state.sort = button.getAttribute( 'data-val' );
					setSortActive();
					sortCells();
				} );
			} );
		}

		if ( activeWrap ) {
			activeWrap.addEventListener( 'click', function ( event ) {
				var button = event.target.closest ? event.target.closest( '.active-tag' ) : null;
				if ( !button ) {
					return;
				}

				var group = button.getAttribute( 'data-filter' );
				var focusTarget = clear;
				if ( group === 'medium' || group === 'type' ) {
					var value = button.getAttribute( 'data-val' );
					var index = state[ group ].indexOf( value );
					var rail = group === 'medium' ? mediumRail : typeRail;
					if ( index >= 0 ) {
						state[ group ].splice( index, 1 );
					}
					updateFilterButtons( rail, group );
					if ( rail ) {
						Array.prototype.forEach.call( rail.querySelectorAll( '.fopt' ), function ( option ) {
							if ( option.getAttribute( 'data-val' ) === value ) { focusTarget = option; }
						} );
					}
				} else if ( slider ) {
					slider.value = slider.max;
					syncPrice();
					focusTarget = slider;
				}

				apply();
				if ( focusTarget ) { focusTarget.focus(); }
			} );
		}

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				state.medium = [];
				state.type = [];
				state.sort = 'newest';
				if ( slider ) {
					slider.value = slider.max;
					syncPrice();
				}
				updateFilterButtons( mediumRail, 'medium' );
				updateFilterButtons( typeRail, 'type' );
				setSortActive();
				sortCells();
				apply();
				if ( window.CasAnnounce ) {
					window.CasAnnounce( 'Filters cleared.' );
				}
			} );
		}

		setSortActive();
		updateFilterButtons( mediumRail, 'medium' );
		updateFilterButtons( typeRail, 'type' );
		sortCells();
		apply();
	}

	/* ------------------------------------------------------------------
	   Quick-view dialog
	   ------------------------------------------------------------------ */
	var modal = document.getElementById( 'art-modal' );
	if ( ! modal ) {
		return;
	}

	var mImg   = document.getElementById( 'am-img' );
	var mFrame = modal.querySelector( '.am-frame' );
	var mBox   = modal.querySelector( '.am-frame .am-img' );
	var mTitle = document.getElementById( 'am-title' );
	var mSub   = document.getElementById( 'am-sub' );
	var mSpec  = document.getElementById( 'am-spec' );
	var mTags  = document.getElementById( 'am-tags' );
	var mDesc  = document.getElementById( 'am-desc' );
	var mPrice = document.getElementById( 'am-price' );
	var mBuy   = document.getElementById( 'am-buy' );
	var opener = null;

	var FOCUS_SEL = 'a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])';

	function focusables() {
		return Array.prototype.filter.call(
			modal.querySelectorAll( FOCUS_SEL ),
			function ( element ) { return element.getClientRects().length > 0; }
		);
	}

	function open( card, button ) {
		var img = card.querySelector( '.art-img img' );
		if ( img ) {
			/* srcset and sizes are set before src, or the browser picks a candidate
			   from a srcset it has not been given yet. The dialog frame is smaller
			   than the card, so it asks for a smaller rung of the same ladder. */
			if ( img.srcset ) {
				mImg.srcset = img.srcset;
				mImg.sizes = '237px';
			} else {
				mImg.removeAttribute( 'srcset' );
				mImg.removeAttribute( 'sizes' );
			}
			mImg.src = img.currentSrc || img.src;
			mImg.alt = img.alt || '';
			mImg.style.display = '';
		}

		var frame = card.querySelector( '.art-frame' );
		var box   = card.querySelector( '.art-img' );
		if ( box ) {
			mBox.style.aspectRatio = box.style.getPropertyValue( '--r' ) || '';
		}
		mFrame.classList.toggle( 'land', !!( frame && frame.classList.contains( 'land' ) ) );

		var title = card.querySelector( '.art-title' );
		mTitle.textContent = title ? title.textContent : '';
		var sub = card.querySelector( '.art-sub' );
		var size = card.querySelector( '.art-size' );
		if ( mSub ) {
			mSub.textContent = sub ? sub.textContent : '';
			mSub.hidden = ! sub;
		}
		if ( mSpec ) {
			var sizeValue = mSpec.querySelector( 'dd' );
			if ( sizeValue ) { sizeValue.textContent = size ? size.textContent : ''; }
			mSpec.hidden = ! size || ! sizeValue;
		}

		/* Every value below is copied from markup this file printed and escaped,
		   so innerHTML here carries nothing the server did not already approve. */
		var tags = card.querySelector( '.art-tagrow' );
		mTags.innerHTML = tags ? tags.innerHTML : '';

		var desc = card.querySelector( '.cas-art-desc' );
		mDesc.innerHTML = desc ? desc.innerHTML : '';

		var price = card.querySelector( '.art-price' );
		mPrice.textContent = price ? price.textContent : '';
		mPrice.style.display = price ? '' : 'none';

		/* The dialog offers a Buy button only when the card did. A piece with
		   no listing of its own, or one already sold, has nowhere correct to
		   send the visitor. */
		var buy = card.querySelector( '.art-btn.etsy' );
		if ( buy ) {
			mBuy.href = buy.getAttribute( 'href' );
			mBuy.setAttribute( 'aria-label', buy.getAttribute( 'aria-label' ) || '' );
			mBuy.style.display = '';
		} else {
			mBuy.style.display = 'none';
			mBuy.removeAttribute( 'href' );
		}

		modal.classList.add( 'open' );
		modal.setAttribute( 'aria-hidden', 'false' );
		opener = button || document.activeElement;
		var closeButton = modal.querySelector( '.am-close' );
		if ( closeButton ) {
			closeButton.focus();
		}
	}

	function close() {
		if ( ! modal.classList.contains( 'open' ) ) {
			return;
		}
		modal.classList.remove( 'open' );
		modal.setAttribute( 'aria-hidden', 'true' );
		if ( opener && opener.focus ) {
			try { opener.focus(); } catch ( e ) {}
		}
		opener = null;
	}

	/* Delegated, so cards rendered by a second [cas_gallery] on the page are
	   wired without this script running twice. */
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-cas-detail]' ) : null;
		if ( ! button ) {
			return;
		}
		var card = button.closest( '.art-card' );
		if ( card ) {
			open( card, button );
		}
	} );

	Array.prototype.forEach.call( modal.querySelectorAll( '[data-close]' ), function ( element ) {
		element.addEventListener( 'click', close );
	} );

	/* The dialog claims aria-modal="true", so Tab has to stay inside it. */
	modal.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Tab' || ! modal.classList.contains( 'open' ) ) {
			return;
		}
		var focus = focusables();
		if ( ! focus.length ) {
			event.preventDefault();
			return;
		}
		var first = focus[0];
		var last  = focus[focus.length - 1];
		var active = document.activeElement;
		if ( event.shiftKey && ( active === first || ! modal.contains( active ) ) ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && ( active === last || ! modal.contains( active ) ) ) {
			event.preventDefault();
			first.focus();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) {
			close();
		}
	} );
}());
