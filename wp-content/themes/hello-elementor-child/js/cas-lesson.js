/*
 * Lesson page — the one behaviour Tutor does not provide: "Download all" on the course
 * materials card. The prototype fires each per-item link in turn, which keeps one source
 * of truth for the file list; there is no server-built bundle to point at.
 *
 * Everything else on the page is a plain link or Tutor's own form post, so nothing else needs
 * script. window.CasAnnounce comes from cas-site-extras.js and is guarded, so a page that
 * loads without it still works, silently.
 */
( function () {
	'use strict';

	var all = document.getElementById( 'res-all-btn' );
	var list = document.getElementById( 'res-list' );

	if ( ! all || ! list ) {
		return;
	}

	var say = function ( msg ) {
		if ( window.CasAnnounce ) {
			window.CasAnnounce( msg );
		}
	};

	all.addEventListener( 'click', function () {
		var items = Array.prototype.slice.call( list.querySelectorAll( 'a.res-item' ) );
		var label = all.innerHTML;

		items.forEach( function ( a, i ) {
			setTimeout( function () {
				a.click();
			}, i * 250 );
		} );

		all.innerHTML = 'Downloading ' + items.length + ( 1 === items.length ? ' file…' : ' files…' );
		all.disabled = true;
		say( 'Downloading ' + items.length + ( 1 === items.length ? ' file.' : ' files.' ) );

		setTimeout( function () {
			all.innerHTML = label;
			all.disabled = false;
			say( 'Downloads started.' );
		}, items.length * 250 + 600 );
	} );
}() );
