/* #151: Tutor's recovery alerts have no role, so a screen reader does not announce them after submit.
   Give each alert role="alert", make it focusable and move focus to it. Tutor's own files are not changed. */
( function () {
	'use strict';

	function announce( alert ) {
		if ( alert.getAttribute( 'data-cas-announced' ) ) {
			return;
		}
		alert.setAttribute( 'data-cas-announced', '1' );
		alert.setAttribute( 'role', 'alert' );
		alert.setAttribute( 'tabindex', '-1' );
		alert.focus();
	}

	function scan( root ) {
		var alerts = root.querySelectorAll ? root.querySelectorAll( '.tutor-alert' ) : [];
		Array.prototype.forEach.call( alerts, announce );
	}

	scan( document );

	if ( 'MutationObserver' in window ) {
		new MutationObserver( function ( mutations ) {
			mutations.forEach( function ( mutation ) {
				Array.prototype.forEach.call( mutation.addedNodes, function ( node ) {
					if ( node.nodeType !== 1 ) {
						return;
					}
					if ( node.matches && node.matches( '.tutor-alert' ) ) {
						announce( node );
					} else {
						scan( node );
					}
				} );
			} );
		} ).observe( document.body, { childList: true, subtree: true } );
	}
} )();
