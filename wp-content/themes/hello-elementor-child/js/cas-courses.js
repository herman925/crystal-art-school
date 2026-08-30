/* Add to cart on the catalogue cards.
   Posts to Tutor's own native cart endpoint rather than keeping a separate cart, so
   the count in the header and whatever checkout does stay consistent with Tutor. */
( function () {
	'use strict';

	if ( ! window.casCourses ) {
		return;
	}

	document.addEventListener( 'click', function ( ev ) {
		var btn = ev.target.closest( '[data-cas-add-to-cart]' );

		if ( ! btn || btn.disabled ) {
			return;
		}

		var id = btn.getAttribute( 'data-course-id' );
		var label = btn.innerHTML;

		btn.disabled = true;

		var body = new URLSearchParams();
		body.set( 'action', casCourses.action );
		body.set( 'course_id', id );
		body.set( '_tutor_nonce', casCourses.nonce );

		fetch( casCourses.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
		} )
			.then( function ( r ) {
				return r.text().then( function ( t ) {
					var parsed = null;

					try {
						parsed = JSON.parse( t );
					} catch ( e ) {}

					/* Tutor answers "already in the cart" with HTTP 400. That is not a
					   failure from the visitor's point of view — the course they wanted
					   is in the cart — so it must not read as one. */
					var already = parsed && /already in the cart/i.test( parsed.message || '' );

					return { ok: r.ok || already, already: already };
				} );
			} )
			.then( function ( res ) {
				if ( res && res.ok ) {
					btn.innerHTML = res.already ? 'In cart' : 'Added';
					/* The header badge reads Tutor's cart, so it only updates on the next
					   page load. Rather than fake a count, the button says what happened
					   and the visitor can go to the cart when ready. */
					btn.classList.add( 'is-added' );
				} else {
					btn.innerHTML = 'Try again';
					btn.disabled = false;
				}
			} )
			.catch( function () {
				btn.innerHTML = 'Try again';
				btn.disabled = false;
			} );

		/* Restore the original label if the visitor lingers, so a card that failed does
		   not sit there reading "Try again" forever. */
		window.setTimeout( function () {
			if ( ! btn.classList.contains( 'is-added' ) ) {
				btn.innerHTML = label;
				btn.disabled = false;
			}
		}, 4000 );
	} );
} )();

/* Filter and sort the catalogue.
   Every control is rendered by PHP from the courses actually on the page, so this file
   never decides what a chip should say — it reads the rail, matches the cards against
   it, and hides what does not match. A page with no rail leaves it doing nothing. */
( function () {
	'use strict';

	var grid = document.getElementById( 'lesson-grid' );

	if ( ! grid ) {
		return;
	}

	var cells = Array.prototype.slice.call( grid.querySelectorAll( '.lesson-cell' ) );
	var groups = Array.prototype.slice.call( document.querySelectorAll( '.fgroup[data-group]' ) );
	var countEl = document.getElementById( 'lesson-count' );
	var noRes = document.getElementById( 'no-results' );
	var slider = document.getElementById( 'lesson-price-slider' );
	var priceUI = document.getElementById( 'lesson-price-ui' );
	var priceVal = document.getElementById( 'lesson-price-val' );

	var sel = {};
	var ceiling = Infinity;

	groups.forEach( function ( group ) {
		sel[ group.getAttribute( 'data-group' ) ] = 'all';
	} );

	/* data-media and data-theme each hold every term the course carries, joined by a
	   pipe: a course tagged "Sketching & Watercolour" is two media, and matching a
	   single-value attribute is what once let the Medium filter offer Watercolour only
	   while a card plainly said SKETCHING. */
	function carries( cell, attr, value ) {
		return ( cell.getAttribute( attr ) || '' ).split( '|' ).indexOf( value ) > -1;
	}

	function matches( cell ) {
		if ( sel.medium && sel.medium !== 'all' && ! carries( cell, 'data-media', sel.medium ) ) {
			return false;
		}

		if ( sel.theme && sel.theme !== 'all' && ! carries( cell, 'data-theme', sel.theme ) ) {
			return false;
		}

		if ( sel.difficulty && sel.difficulty !== 'all'
			&& cell.getAttribute( 'data-difficulty' ) !== sel.difficulty ) {
			return false;
		}

		if ( sel.duration && sel.duration !== 'all'
			&& cell.getAttribute( 'data-duration' ) !== sel.duration ) {
			return false;
		}

		if ( sel.offer === 'sale' && cell.getAttribute( 'data-sale' ) !== '1' ) {
			return false;
		}

		if ( parseFloat( cell.getAttribute( 'data-price' ) ) > ceiling ) {
			return false;
		}

		return true;
	}

	/* #lesson-count is the live region for every control on the rail: the number of
	   results is the one thing all of them change, so announcing it here means the chips,
	   the slider and Clear all do not each need a message of their own. */
	function apply() {
		var n = 0;

		cells.forEach( function ( cell ) {
			var ok = matches( cell );

			cell.classList.toggle( 'hidden', ! ok );

			if ( ok ) {
				n++;
			}
		} );

		if ( countEl ) {
			countEl.textContent = n + ( n === 1 ? ' course' : ' courses' );
		}

		if ( noRes ) {
			noRes.style.display = n ? 'none' : 'block';
		}
	}

	function press( scope, chosen ) {
		scope.querySelectorAll( '.fopt' ).forEach( function ( opt ) {
			var on = opt === chosen;

			opt.classList.toggle( 'active', on );
			opt.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	}

	groups.forEach( function ( group ) {
		var key = group.getAttribute( 'data-group' );

		group.querySelectorAll( '.fopt' ).forEach( function ( opt ) {
			opt.addEventListener( 'click', function () {
				press( group, opt );
				sel[ key ] = opt.getAttribute( 'data-val' );
				apply();
			} );
		} );
	} );

	/* Reads the input, then writes every dependent thing from it — the printed label, the
	   announced value, the fill width and the cut-off all come from one place, so they
	   cannot disagree the way they did when the fill painted 100% over a value of 60. */
	function syncPrice() {
		var top = parseFloat( slider.max );
		var v = parseFloat( slider.value );
		var span = top - parseFloat( slider.min ) || 1;
		/* The currency symbol is the store's setting, so PHP prints it onto the field
		   rather than this script carrying a copy that a currency change would strand. */
		var symbol = priceVal.getAttribute( 'data-symbol' ) || '';
		var label = v >= top ? 'Any price' : ( v === 0 ? 'Free only' : 'Up to ' + symbol + v );

		priceVal.textContent = label;
		slider.setAttribute( 'aria-valuetext', label );
		priceUI.style.setProperty( '--pct', ( ( ( v - parseFloat( slider.min ) ) / span ) * 100 ) + '%' );
		ceiling = v >= top ? Infinity : v;
	}

	if ( slider && priceUI && priceVal ) {
		slider.addEventListener( 'input', function () {
			syncPrice();
			apply();
		} );
		slider.addEventListener( 'change', function () {
			syncPrice();
			apply();
		} );

		syncPrice();
	}

	var clear = document.getElementById( 'fp-clear' );

	if ( clear ) {
		clear.addEventListener( 'click', function () {
			groups.forEach( function ( group ) {
				sel[ group.getAttribute( 'data-group' ) ] = 'all';
				press( group, group.querySelector( '.fopt' ) );
			} );

			if ( slider ) {
				slider.value = slider.max;
				syncPrice();
			}

			apply();
		} );
	}

	/* Featured is whatever order the server sent, which is Tutor's own catalogue order —
	   kept as a snapshot so returning to it does not need a reload. */
	var sortRail = document.getElementById( 'lesson-sort' );
	var featured = cells.slice();
	var SORT_SAID = {
		'featured': 'Sorted by featured order.',
		'price-asc': 'Sorted by price, lowest first.',
		'price-desc': 'Sorted by price, highest first.'
	};

	if ( sortRail ) {
		sortRail.querySelectorAll( '.fopt' ).forEach( function ( opt ) {
			opt.addEventListener( 'click', function () {
				var order = featured.slice();
				var val = opt.getAttribute( 'data-val' );

				press( sortRail, opt );

				if ( val === 'price-asc' || val === 'price-desc' ) {
					order.sort( function ( a, b ) {
						var d = parseFloat( a.getAttribute( 'data-price' ) ) - parseFloat( b.getAttribute( 'data-price' ) );

						return val === 'price-asc' ? d : -d;
					} );
				}

				order.forEach( function ( cell ) {
					grid.appendChild( cell );
				} );

				if ( typeof window.CasAnnounce === 'function' && SORT_SAID[ val ] ) {
					window.CasAnnounce( SORT_SAID[ val ] );
				}
			} );
		} );
	}

	apply();
} )();

/* Expand only the continuation Crystal marks in the editable introduction. */
( function () {
	'use strict';

	var toggle = document.querySelector( '[data-cas-courses-intro-toggle]' );

	if ( ! toggle ) {
		return;
	}

	var target_id = toggle.getAttribute( 'aria-controls' );
	var more      = target_id ? document.getElementById( target_id ) : null;

	if ( ! more ) {
		return;
	}

	toggle.addEventListener( 'click', function () {
		var expanded = 'true' === toggle.getAttribute( 'aria-expanded' );

		more.hidden = expanded;
		toggle.setAttribute( 'aria-expanded', expanded ? 'false' : 'true' );
		toggle.textContent = expanded ? 'Read More' : 'Read Less';
	} );
} )();
