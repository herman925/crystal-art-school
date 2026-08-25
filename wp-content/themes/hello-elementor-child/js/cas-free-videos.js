/* Free videos page.
   The cards are rendered by inc/cas-free-videos.php, not here — the prototype
   built every tile from a generated JS array, which meant the page was empty
   without script and invisible to search. This file only does the three things
   that need a browser: the rails, the featured stage's dots, and the player.

   Everything the player needs travels on the card itself (data-embed, data-host,
   data-vertical, href), so there is no second copy of the film list to drift out
   of step with the markup. */
(function () {
	'use strict';

	var modal = document.getElementById('fv-modal');
	if (!modal) { return; }

	/* .reveal-one is opacity:1 in industrial-1.css and .in-view only adds the
	   entrance animation, so this is decoration rather than a visibility fix. */
	Array.prototype.forEach.call(
		document.querySelectorAll('.reveal-one, .section-title'),
		function (el) { el.classList.add('in-view'); }
	);

	/* ---------------------------------------------------------------
	   Player
	   --------------------------------------------------------------- */

	var stage = document.getElementById('fvm-stage');
	var poster = document.getElementById('fvm-poster');
	var frame = document.getElementById('fvm-frame');
	var noteEl = document.getElementById('fvm-note');
	var titleEl = document.getElementById('fvm-title');
	var descEl = document.getElementById('fvm-desc');
	var closeBtn = modal.querySelector('.fvm-close');
	var opener = null;

	function say(msg) { if (window.CasAnnounce) { window.CasAnnounce(msg); } }
	function isOpen() { return modal.classList.contains('open'); }

	/* getClientRects() is the test rather than the selector alone: the iframe is
	   in the markup for every film and display:none until one is loaded, so a trap
	   that included it would land focus nowhere. */
	function focusables() {
		return Array.prototype.filter.call(
			modal.querySelectorAll('a[href],button:not([disabled]),iframe,[tabindex]:not([tabindex="-1"])'),
			function (el) { return el.getClientRects().length > 0; }
		);
	}

	function openV(card) {
		var img = card.querySelector('.fv-thumb img');
		/* Assigning '' would set src to the page URL and request the document
		   itself, leaving a broken image behind the player. */
		if (img && (img.currentSrc || img.src)) {
			poster.src = img.currentSrc || img.src;
			poster.style.display = '';
		} else {
			poster.removeAttribute('src');
			poster.style.display = 'none';
		}

		var heading = card.querySelector('h3');
		var title = heading ? heading.textContent : '';
		var blurb = card.querySelector('p');
		poster.alt = title;
		titleEl.textContent = title;
		descEl.textContent = blurb ? blurb.textContent : '';

		/* Only an <a> card reaches here, and the server gives an <a> an embed URL
		   or nothing. The guard stays because the markup and the meta can drift. */
		var embed = card.getAttribute('data-embed') || '';
		stage.classList.toggle('has-video', !!embed);
		/* 1.4.12: one player, two container ratios. A vertical film keeps the stage
		   height and is pillarboxed rather than letterboxed, so the modal does not
		   jump size between a landscape and a portrait video. The same test and the
		   same CSS for both hosts — there is deliberately no second vertical
		   implementation for YouTube. */
		stage.classList.toggle('is-vertical', card.getAttribute('data-vertical') === '1');

		var want = embed || 'about:blank';
		if (frame.getAttribute('src') !== want) { frame.setAttribute('src', want); }
		/* The frame's accessible name was the constant "Video player" for every film
		   on the page. An embedded player is a tab stop, and its name is the only
		   thing a screen reader has to tell one film from another. */
		frame.setAttribute('title', title ? title + ' — video player' : 'Video player');

		/* The note names the host, because "trouble playing" has a different cause
		   and a different fix on each: YouTube tends to be blocked outright on a
		   school or office network, while a film hosted here fails on the referrer
		   check that stops the stream being shared. */
		var away = card.getAttribute('href');
		var yt = card.getAttribute('data-host') === 'youtube';
		if (embed) {
			noteEl.textContent = yt ? 'Plays from YouTube' : 'Plays from crystalau.com';
			if (away) {
				noteEl.appendChild(document.createTextNode(' · '));
				var a = document.createElement('a');
				a.href = away;
				a.target = '_blank';
				a.rel = 'noopener';
				a.textContent = 'Trouble playing? ' + (yt ? 'Open on YouTube' : 'Open in a new tab') + ' ↗';
				noteEl.appendChild(a);
			}
		} else {
			noteEl.textContent = 'This film has not been uploaded yet.';
		}

		opener = document.activeElement;
		modal.classList.add('open');
		modal.setAttribute('aria-hidden', 'false');
		modal.removeAttribute('inert');
		document.body.style.overflow = 'hidden';
		closeBtn.focus();
		say(title);
	}

	function closeV() {
		if (!isOpen()) { return; }
		stage.classList.remove('has-video');
		frame.setAttribute('src', 'about:blank');
		modal.classList.remove('open');
		modal.setAttribute('aria-hidden', 'true');
		modal.setAttribute('inert', '');
		document.body.style.overflow = '';
		if (opener && document.contains(opener) && opener.focus) { opener.focus(); }
		opener = null;
	}

	modal.setAttribute('inert', '');

	/* The dialog claims aria-modal="true", so Tab has to stay inside it. */
	modal.addEventListener('keydown', function (e) {
		if (e.key !== 'Tab' || !isOpen()) { return; }
		var f = focusables();
		if (!f.length) { e.preventDefault(); return; }
		var first = f[0], last = f[f.length - 1], a = document.activeElement;
		if (e.shiftKey && (a === first || !modal.contains(a))) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && a === last) { e.preventDefault(); first.focus(); }
	});
	document.addEventListener('focusin', function (e) {
		if (!isOpen() || modal.contains(e.target)) { return; }
		closeBtn.focus();
	});
	Array.prototype.forEach.call(modal.querySelectorAll('[data-close]'), function (el) {
		el.addEventListener('click', closeV);
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { closeV(); }
	});

	/* Delegated, so the featured stage's cards and the rails' cards are wired by
	   one listener and a second [cas_free_videos] on a page needs nothing extra.
	   Only an <a> card opens the player: a tile with no film has nowhere to go and
	   is not a control at all. */
	document.addEventListener('click', function (e) {
		var card = e.target.closest ? e.target.closest('a.fv-card') : null;
		if (!card) { return; }
		e.preventDefault();
		openV(card);
	});

	/* ---------------------------------------------------------------
	   Rails
	   --------------------------------------------------------------- */

	Array.prototype.forEach.call(document.querySelectorAll('.fv-rail'), function (rail) {
		var grid = rail.querySelector('.fv-grid');
		if (!grid) { return; }

		function mk(dir, glyph) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'fv-arrow ' + dir;
			b.setAttribute('aria-label', dir === 'prev' ? 'Previous videos' : 'More videos');
			b.textContent = glyph;
			rail.appendChild(b);
			return b;
		}
		var prev = mk('prev', '‹'), next = mk('next', '›');

		/* Centre the arrows on the PICTURE, not on the card: the title and blurb
		   below it are a different height in every section. */
		function centreArrows() {
			var thumb = grid.querySelector('.fv-thumb');
			if (!thumb) { return; }
			var t = thumb.getBoundingClientRect(), r = rail.getBoundingClientRect();
			rail.style.setProperty('--fv-arrow-top', (t.top - r.top + t.height / 2) + 'px');
		}
		function step() {
			var card = grid.querySelector('.fv-card');
			var gap = parseFloat(getComputedStyle(grid).columnGap) || 24;
			return card ? card.getBoundingClientRect().width + gap : grid.clientWidth;
		}
		function sync() {
			var over = grid.scrollWidth - grid.clientWidth;
			rail.classList.toggle('has-more', over > 4);
			prev.disabled = grid.scrollLeft <= 2;
			next.disabled = grid.scrollLeft >= over - 2;
		}
		/* scrollBy asked for 'smooth' unconditionally and .fv-grid declared
		   scroll-behavior: smooth on top of it, so a reduced-motion reader still got
		   an animated rail. Both are conditional now. */
		function glide() {
			return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
		}

		prev.addEventListener('click', function () { grid.scrollBy({ left: -step(), behavior: glide() }); });
		next.addEventListener('click', function () { grid.scrollBy({ left: step(), behavior: glide() }); });
		grid.addEventListener('scroll', sync);
		window.addEventListener('resize', function () { sync(); centreArrows(); });
		sync();
		centreArrows();
		/* The thumbnails are lazy, so the first measurement can precede layout. */
		if (document.fonts && document.fonts.ready) { document.fonts.ready.then(centreArrows); }
		Array.prototype.forEach.call(grid.querySelectorAll('img'), function (im) {
			if (!im.complete) { im.addEventListener('load', centreArrows, { once: true }); }
		});
	});

	/* ---------------------------------------------------------------
	   Featured stage
	   --------------------------------------------------------------- */

	/* 15: the featured row carries the step-by-step demos, latest first, with dots
	   (client feedback 2026-08-16, tickets D005 and D006 — see the note at the top
	   of inc/cas-free-videos.php). The cards are already in the page and all but
	   the first carry `hidden`; the dots only change which one that is, so with no
	   script the newest demo still shows and there are no dead controls under it. */
	var feat = document.getElementById('fv-feat');
	var dots = document.getElementById('fv-feat-dots');
	if (feat && dots) {
		var cards = feat.querySelectorAll('.fv-card');
		if (cards.length > 1) {
			var current = 0;
			var showCard = function (i) {
				current = i;
				Array.prototype.forEach.call(cards, function (c, n) { c.hidden = (n !== i); });
				Array.prototype.forEach.call(dots.children, function (b, n) {
					b.classList.toggle('active', n === i);
				});
			};
			Array.prototype.forEach.call(cards, function (card, i) {
				var b = document.createElement('button');
				b.type = 'button';
				b.setAttribute('aria-label', 'Show demo ' + (i + 1));
				b.addEventListener('click', function () {
					showCard(i);
					/* The stage swaps silently otherwise — a class change announces
					   nothing, and the dots are the only visual cue. */
					var h = cards[current].querySelector('h3');
					say('Featured demo ' + (i + 1) + ' of ' + cards.length + ': ' + (h ? h.textContent : '') + '.');
				});
				dots.appendChild(b);
			});
			showCard(0);
		}
	}
}());
