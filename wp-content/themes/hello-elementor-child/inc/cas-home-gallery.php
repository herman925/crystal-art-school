<?php
defined( 'ABSPATH' ) || exit;

/**
 * Home — ART GALLERY promo card. Direction document section 3.6.
 *
 * The chapter confirms four things and nothing else: the card shows a gallery
 * picture under the enlarged `ART GALLERY` title; hovering reveals
 * "Bring unique artwork into your space." over a dark gradient at the foot of
 * the picture; the title stays visible while it does; and the card keeps its
 * existing link to the Gallery page.
 *
 * Markup is index.html:262-270 verbatim, so industrial-1.css:890-901 styles it
 * and no new CSS is needed. One class is deliberately omitted — see the note on
 * .shop-gallery in cas_render_home_gallery().
 *
 * Editability, under Herman's Rule (2026-08-23):
 *   - The picture is Crystal's. It is the first piece in her own Gallery
 *     curation, read from the existing Artwork post type. No second image slot
 *     is registered: a promo card for the gallery that shows something other
 *     than the gallery is a parallel store to keep in step by hand.
 *   - The hover line is body copy, so it is a Customizer field.
 *   - The `ART GALLERY` title, the asterisk, the link and its destination are
 *     structure and stay in code.
 */

const CAS_HOME_GALLERY_SUB = 'cas_home_gallery_sub';

/* The line the chapter confirms, quoted exactly. It is the shipped default, not
   invented copy: see docs/20260804_Design_Direction.md section 3.6. */
const CAS_HOME_GALLERY_SUB_DEFAULT = 'Bring unique artwork into your space.';


/* ------------------------------------------------------------------
   Customizer
   ------------------------------------------------------------------ */

/**
 * Priority 10 with an explicit panel is how a NEW section joins the Pages panel
 * — cas-blog-templates.php and cas-free-videos.php both do this. The map at
 * priority 100 creates the panel itself and re-parents the older sections; a
 * section registered here is already parented, so it is left alone.
 *
 * Section priority 16 puts this card directly after "Home" (10) and
 * "Home — testimonials" (15), which is its reading order on the page.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_gallery',
			array(
				'title'       => 'Home — art gallery card',
				'panel'       => 'cas_pages',
				'priority'    => 16,
				'description' => 'The picture on this card is the first piece in Dashboard &rarr; Artwork, in the order set there. Change that order and the card follows. The card is hidden while no artwork is published.',
			)
		);

		$wp_customize->add_setting(
			CAS_HOME_GALLERY_SUB,
			array(
				'default'           => CAS_HOME_GALLERY_SUB_DEFAULT,
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			CAS_HOME_GALLERY_SUB,
			array(
				'label'       => 'Hover line',
				'description' => 'Shown across the foot of the picture when a visitor points at the card. Leave it empty to show none.',
				'section'     => 'cas_page_home_gallery',
				'type'        => 'text',
			)
		);
	},
	10
);


/* ------------------------------------------------------------------
   Front end
   ------------------------------------------------------------------ */

add_shortcode( 'cas_home_gallery', 'cas_render_home_gallery' );

/**
 * Returns '' when there is no artwork to show. A promo card for a gallery with
 * nothing in it advertises an empty room, and an empty frame reads as a broken
 * page rather than as a missing upload — the same call cas_artwork_card() makes
 * for one piece (cas-gallery.php:337-340).
 *
 * The prototype's second class, .shop-gallery, is NOT emitted. It exists only to
 * cancel the tile's 1/1 aspect ratio (industrial-2.css:373) so that inline JS at
 * index.html:884-893 can match the card's height to the first course card. That
 * JS is not ported and there are no published courses to measure, so the class
 * would leave the card with no defined height and let a swapped picture set the
 * composition — which Herman's Rule 5 forbids. Without it the tile keeps its
 * square slot and object-fit: cover, and any picture crops to it.
 */
function cas_render_home_gallery() {
	$thumb_id = cas_home_gallery_thumb_id();
	if ( ! $thumb_id ) {
		return '';
	}

	/* WordPress rewrites `sizes="300px"` into `sizes="auto, 300px"` on every
	   lazy-loaded image inside filtered content — wp_filter_content_tags() runs on
	   the_content at priority 12, after do_shortcode at 11 — and Chrome then
	   refuses to resolve a percentage height on an img carrying `sizes=auto`.
	   .gallery-tile img is `height: 100%` (industrial-1.css:891), so this card is
	   exactly the shape that emptied every certificate frame on About. The sizes
	   below are the ones index.html:263 states, so `auto` has no measurement to
	   add. Core's own documented switch, registered only on a request that renders
	   this card. cas-about.php:742 does the same for the same reason. */
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	$img = wp_get_attachment_image(
		$thumb_id,
		'large',
		false,
		array(
			/* index.html:263 verbatim. */
			'sizes'    => '(max-width: 600px) 358px, (max-width: 1000px) 720px, 300px',
			/* The anchor carries the accessible name, so a description here would
			   be a second name for the same link. */
			'alt'      => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);

	if ( '' === $img ) {
		return '';
	}

	$sub = (string) get_theme_mod( CAS_HOME_GALLERY_SUB, CAS_HOME_GALLERY_SUB_DEFAULT );

	$gallery = function_exists( 'cas_page_url' ) ? cas_page_url( 'gallery' ) : home_url( '/gallery/' );

	ob_start();
	?>
	<a class="gallery-tile" href="<?php echo esc_url( $gallery ); ?>" aria-label="Browse the art gallery">
		<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() escapes its own attributes. ?>
		<div class="gt-veil">
			<span class="gt-ast">&#10022;</span>
			<span class="gt-title">Art<br>Gallery</span>
			<?php if ( '' !== trim( $sub ) ) : ?>
				<span class="gt-sub"><?php echo esc_html( $sub ); ?></span>
			<?php endif; ?>
		</div>
	</a>
	<?php
	return ob_get_clean();
}

/**
 * The featured image of the first published piece in the client's own Gallery
 * curation. Home sorts by menu_order ascending, then publication date descending;
 * the Gallery page sorts independently by publication date, so its lead may differ.
 *
 * The meta query is what makes "first" mean "first piece that has a picture". An
 * artwork saved without a featured image is skipped here exactly as it is
 * skipped on the Gallery page, rather than emptying this card.
 */
function cas_home_gallery_thumb_id() {
	$query = new WP_Query(
		array(
			'post_type'      => 'cas_artwork',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one EXISTS test, one row.
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	if ( empty( $query->posts ) ) {
		return 0;
	}

	return (int) get_post_thumbnail_id( $query->posts[0] );
}
