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
 * Owner direction, 2026-10-31: choose the card's picture from published Artwork
 * in Content Hub → Home → Gallery Image. The original ordering remains the
 * fallback until a selection is saved. The title and destination stay fixed.
 */

const CAS_HOME_GALLERY_ARTWORK = 'cas_home_gallery_artwork';
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
				'description' => 'Choose the picture from published Artwork in Content Hub → Home → Gallery Image. Until a choice is saved, the existing gallery order supplies the picture.',
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

/** Only already-public gallery pictures can be featured on Home. */
function cas_home_gallery_image( $artwork_id ) {
	$post = get_post( $artwork_id );
	if ( ! $post || 'cas_artwork' !== $post->post_type || 'publish' !== $post->post_status || $post->post_password ) { return 0; }
	$thumb = (int) get_post_thumbnail_id( $artwork_id );
	if ( ! $thumb || ! wp_attachment_is_image( $thumb ) || ! in_array( get_post_status( $thumb ), array( 'inherit', 'publish' ), true ) || ( function_exists( 'cas_private_is' ) && cas_private_is( $thumb ) ) ) { return 0; }
	return $thumb;
}

function cas_home_gallery_artworks( $orderby = array( 'menu_order' => 'ASC', 'date' => 'DESC' ) ) {
	$query = new WP_Query( array(
		'post_type' => 'cas_artwork', 'post_status' => 'publish', 'post_password' => '',
		'posts_per_page' => -1, 'orderby' => $orderby, 'fields' => 'ids', 'no_found_rows' => true,
		'meta_query' => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ),
	) );
	return array_values( array_filter( $query->posts, 'cas_home_gallery_image' ) );
}

function cas_home_gallery_artwork_id() {
	$chosen = (int) get_theme_mod( CAS_HOME_GALLERY_ARTWORK, 0 );
	if ( $chosen && cas_home_gallery_image( $chosen ) ) { return $chosen; }
	// Preserve the previously shown artwork until a choice is saved, or if it is withdrawn.
	$artworks = cas_home_gallery_artworks();
	return $artworks ? (int) $artworks[0] : 0;
}

function cas_home_gallery_thumb_id() {
	$id = cas_home_gallery_artwork_id();
	return $id ? cas_home_gallery_image( $id ) : 0;
}
