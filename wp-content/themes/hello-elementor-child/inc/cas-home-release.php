<?php
/**
 * The New release row — the shared wrapper §3.3 and §3.4 sit inside.
 *
 * The prototype (index.html:203-249) puts three columns of one `.grid-12.release-trio`
 * inside a single `.pg-section`: the Free videos promo card (`--i:0`), the §5 New
 * release carousel (`--i:1`) and the About Crystal column (`--i:2`). Each of those
 * cards is built by its own module and each returns the bare column, because a column
 * cannot supply the row it shares with two others. This file is that row and nothing
 * else.
 *
 * WHAT IS MISSING, AND WHY THE HEADING IS NOT PRINTED. Column 2 — the §5 New release
 * carousel — has not been built. The prototype's heading for this row is not a heading
 * for the row: `industrial-1.css:766` pins `.release-head .section-title` to
 * `grid-column: 5 / span 4`, directly above that middle column, so "New release" names
 * the carousel and nothing else. Printing it now would put a heading over an empty
 * grid cell, which is the one thing the build rules forbid. The `.release-head` block
 * belongs with §5 and should be added at the same time as the carousel.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the row, or nothing at all when neither column has anything to show.
 *
 * @return string
 */
function cas_render_home_release_row() {
	/* Column 2 ([cas_home_release] for the §5 carousel) goes between these two when it
	   is built. Both modules return '' rather than an empty shell when they have no
	   picture, so an empty row is a real possibility and is rendered as nothing. */
	$columns  = do_shortcode( '[cas_home_free_videos]' );
	$columns .= do_shortcode( '[cas_home_about]' );

	if ( '' === trim( $columns ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="pg-section">
		<div class="pg-wrap">
			<div class="grid-12 release-trio">
				<?php echo $columns; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- both columns are built and escaped by their own modules. ?>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_release_row', 'cas_render_home_release_row' );
