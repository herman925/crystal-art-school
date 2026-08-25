<?php
/**
 * The Blog editing constraints.
 *
 * Blog is the one place on this site where Crystal lays out the content herself. Every
 * other page is a fixed template with named editable fields, because the direction
 * document's CMS rule (§1.4.14) is default-closed: only the fields a chapter explicitly
 * lists may be modified. §12.2 makes the single exception — "Blog 可使用較自由的內容編輯
 * 方式" — and then closes it again by naming the exact blocks that freedom extends to.
 *
 * So this file is not a page template and renders nothing. It is the fence around the
 * exception: the whitelist below IS the list from §12.2, and nothing more.
 *
 * Two things deliberately left alone, so a later reader does not "finish the job":
 *
 * 1. Block Patterns are not registered and not unregistered. Herman declined to supply
 *    patterns (CLAUDE.md, 2026-07-26). The accepted consequence is that every construct
 *    Crystal uses must be reachable from the plain toolbar, which is why the whitelist
 *    stays small rather than why it should grow.
 * 2. theme.json colour and typography controls stay open. That was a deliberate choice
 *    in favour of her freedom, made knowing it permits visual drift between posts. Do
 *    not lock the palette here as a side effect of locking the block list.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The blocks a post body may contain, per §12.2.
 *
 * Grouped the way the direction document groups them — content blocks, then layout
 * blocks — so the two lists can be compared against the document without decoding
 * block names.
 *
 * The three names marked "child" are not in the document's table and are not an
 * extension of it. They are the inner blocks their parents are built from, and a parent
 * whose child is disallowed is inert in the editor: since WP 6.0 a List is a core/list
 * wrapping core/list-item, Columns has always been a core/columns wrapping core/column,
 * and since WP 5.9 a Gallery is a core/gallery wrapping core/image. Omitting them would
 * whitelist three blocks that cannot be typed into. core/image is already whitelisted in
 * its own right, so Gallery needs no separate entry.
 *
 * Verify this against the running WordPress version before adding blocks: core changes
 * which blocks are parent/child from time to time, and the failure is silent — the block
 * simply refuses to accept content.
 *
 * @return string[]
 */
function cas_blog_allowed_blocks() {
	return [
		// Content — §12.2「內容」.
		'core/paragraph',
		'core/heading',
		'core/image',
		'core/list',
		'core/list-item', // child of core/list.
		'core/quote',
		'core/separator',
		'core/gallery',

		// Layout — §12.2「版面」.
		'core/columns',
		'core/column', // child of core/columns.
		'core/group',
		'core/cover',
	];
}

/**
 * Restricts the block inserter for posts only.
 *
 * The whitelist is what enforces both prohibitions in §12.2, by omission rather than by
 * a separate rule:
 *
 * - Custom HTML (core/html) is absent. Pasted HTML cannot afterwards be edited visually
 *   by Crystal, and it cannot be restyled when the site's CSS changes — it is a frozen
 *   fragment inside an otherwise living post. core/freeform, the Classic block, is
 *   absent for the same reason: it is a second door into the same arbitrary markup.
 * - Table (core/table) is absent. It is a data table, not a layout grid. It does not
 *   reflow on a phone, so a three-column table overflows the page, and a screen reader
 *   announces its cells as data with row and column relationships that mean nothing. Any
 *   multi-column layout must use core/columns, which stacks on mobile by design.
 *
 * Scoping is the part worth being careful about. The filter fires for every block editor
 * screen — pages, the site editor, widgets, and each custom post type — and the context's
 * post is null on several of them. Restricting on a null post would silently strip the
 * inserter everywhere, so anything that is not a 'post' returns the incoming value
 * untouched, whether that value is `true` or another plugin's array.
 *
 * @param bool|string[]           $allowed Incoming allowance: true, or a list of names.
 * @param WP_Block_Editor_Context $context The screen being set up.
 * @return bool|string[]
 */
function cas_blog_allowed_block_types( $allowed, $context ) {
	if ( ! isset( $context->post ) || ! ( $context->post instanceof WP_Post ) ) {
		return $allowed;
	}

	if ( 'post' !== $context->post->post_type ) {
		return $allowed;
	}

	return cas_blog_allowed_blocks();
}

add_filter( 'allowed_block_types_all', 'cas_blog_allowed_block_types', 10, 2 );
