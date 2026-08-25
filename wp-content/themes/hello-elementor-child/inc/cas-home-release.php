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
 * THE HEADING. `industrial-1.css:766` pins `.release-head .section-title` to
 * `grid-column: 5 / span 4`, directly above the middle column, so "New release" names
 * the carousel and nothing else. It is printed only when the carousel column has
 * content — a heading over an empty grid cell is the one thing the build rules
 * forbid.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the row, or nothing at all when no column has anything to show.
 *
 * @return string
 */
function cas_render_home_release_row() {
	$free   = do_shortcode( '[cas_home_free_videos]' );
	$release = do_shortcode( '[cas_home_release]' );
	$about  = do_shortcode( '[cas_home_about]' );

	$columns = $free . $release . $about;

	if ( '' === trim( $columns ) ) {
		return '';
	}

	ob_start();
	?>
	<section class="pg-section">
		<div class="pg-wrap">
			<?php if ( '' !== trim( $release ) ) : ?>
				<div class="release-head">
					<h2 class="section-title is-left">New release</h2>
				</div>
			<?php endif; ?>
			<div class="grid-12 release-trio">
				<?php echo $columns; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all three columns are built and escaped by their own modules. ?>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_release_row', 'cas_render_home_release_row' );
