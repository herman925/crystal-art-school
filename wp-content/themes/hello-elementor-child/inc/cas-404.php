<?php
/**
 * The themed 404.
 *
 * The last page on the site still wearing hello-elementor's stock template. The
 * archives module declines `is_404()` on purpose — an empty date archive keeps its
 * true 404 status instead of being answered with an apology at HTTP 200 — so this
 * surface is its own module, rendered through the theme's normal template path via
 * `404.php` (which is exactly the house pattern cas-archives.php:86-90 describes).
 *
 * Every string here is a heading, a button label or an empty state, so Herman's Rule
 * (2026-08-23) keeps them in code. Nothing is client-editable and nothing needs to
 * be.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

/**
 * The page body: the wood-grain banner (via the shared hero helper), one honest
 * sentence, and a way home. Returns the shortcode's output.
 *
 * @return string
 */
function cas_render_404() {
	ob_start();
	?>
	<?php
	if ( function_exists( 'cas_page_hero' ) ) {
		echo cas_page_hero( 'Lost', 'This page has wandered off.' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the theme's own helper escapes its own output.
	}
	?>
	<div class="pg-wrap">
		<div class="store-empty" role="status">
			<p style="margin:0 0 22px;">The address you followed does not match anything on the site &mdash; it may have been mistyped, or the page may have moved.</p>
			<a class="btn primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the homepage <span class="arr" aria-hidden="true">&#8594;</span></a>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_404', 'cas_render_404' );

/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * The 404 needs the site's own button, hero and empty-state styling. The archive
 * surface's stylesheet rides on the industrial bundle that carries all of them, so
 * the same handle is registered and enqueued here with the same dependency — the
 * `.store-empty` rule this page reuses lives in css/industrial-2.css.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_404() ) {
			return;
		}

		wp_register_style( 'cas-404', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-404' );
	},
	27
);
