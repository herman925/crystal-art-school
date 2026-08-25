<?php
/**
 * Homepage Free Videos promo card — section 3.3 of the direction document.
 *
 * It is column 1 of the three-column "New release" row on the homepage, so this
 * shortcode renders ONE grid cell (`.span-4`) and nothing around it. The row's
 * `.pg-section` / `.pg-wrap` / `.release-head` / `.grid-12.release-trio` wrapper is
 * shared with the New Release carousel and the About Crystal column and is not this
 * file's to emit — see the note handed to the integrator.
 *
 * 3.3 fixes three things and this file honours all three: the title is `FREE VIDEOS`
 * (never THREE), it is set in Font A, and at rest the card shows only that title. The
 * blurb is revealed on hover on desktop and shown unconditionally on touch. All of that
 * behaviour is already in the shipped bundle — `.free-video` / `.fv-veil` / `.fv-play`
 * in css/industrial-1.css lines 786-801 — so this module adds no CSS of its own. The
 * hover dimming 3.3 asks to keep is the `.free-video:hover .fv-veil` gradient there.
 *
 * The heading string is the prototype's `Free videos`, not a shouted `FREE VIDEOS`.
 * Font A is Arizonia, a connected script; industrial-3.css:1200 sets
 * `text-transform: none` on `.font-a` deliberately, and 2.2 of the direction records
 * that the face ships one weight. The document's `FREE VIDEOS` settles FREE against
 * THREE, not letter case, and the prototype is the design spec. Flagged in the report.
 *
 * Click behaviour is unchanged from the prototype, as 3.3 requires: the whole card is
 * one link to the Free Videos page. Nothing plays on the homepage and nothing opens in
 * a modal.
 *
 * Editability, per Herman's Rule (2026-08-23): the photograph and the blurb are hers;
 * the heading, the play mark, the link and its destination are code. Both open fields
 * are in Customize > Pages > Home - Free videos card.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The blurb is quoted verbatim from 3.3 of the direction document, which specifies it as
 * 指定文字 — the client's own approved wording. It is registered as the Customizer
 * default so the panel opens showing the copy that is actually live, rather than an
 * empty box that reads as broken.
 */
function cas_home_free_videos_text_default() {
	return 'Watch, Learn & Create - Explore quick painting tutorials designed to inspire your artistic journey today.';
}

/**
 * Attachment 234, `home-free-videos.jpg` — the prototype's own `img/A2.jpeg` uploaded to
 * the media library on 2026-08-23. It is in the library rather than in the theme's img/
 * folder so that the default and any replacement she uploads travel the same path: both
 * are attachments, so `wp_get_attachment_image()` can build a srcset for either. A plain
 * URL would ship the 806px master to a phone.
 */
function cas_home_free_videos_photo_default() {
	$url = wp_get_attachment_image_url( 234, 'full' );

	return $url ? $url : '';
}

/**
 * Falls back to the shipped photograph when the stored value is empty.
 *
 * A Customizer "Remove image" stores `''`, and `get_theme_mod()` returns that in
 * preference to the registered default — the card would then paint `src=""`, which the
 * browser requests as the page itself. Same guard as `cas_brand_image()` and
 * `cas_tier_value()`. The text field deliberately has no such guard: body copy is hers,
 * and a sentence she may rewrite is one she may also delete.
 */
function cas_home_free_videos_photo() {
	$default = cas_home_free_videos_photo_default();
	$set     = get_theme_mod( 'cas_home_free_videos_photo', $default );

	return $set ? $set : $default;
}

function cas_home_free_videos_text() {
	return get_theme_mod( 'cas_home_free_videos_text', cas_home_free_videos_text_default() );
}

/**
 * The photograph, as an <img>.
 *
 * The slot is square — `.release-trio .free-video { aspect-ratio: 1/1 }` at
 * industrial-1.css:769 — and the image is `object-fit: cover` inside it, so an upload of
 * any shape crops to the slot instead of changing the row's height. That is what makes
 * opening this field safe.
 *
 * `loading` is set to false on purpose. The image is `height: 100%` inside an
 * absolutely-positioned box, and `wp_img_tag_add_auto_sizes()` prepends `sizes="auto,"`
 * to lazy images after the theme has emitted them; on the About certificates that made
 * every scan resolve at full width inside a small mount and paint outside its own
 * overflow box. The prototype carries no loading attribute here either.
 *
 * No alt is passed: when the photograph is an attachment the media library's own
 * Alternative Text is used, so a replacement brings its own description instead of
 * inheriting one written for a picture that is no longer there. The link already carries
 * `aria-label="Free videos"`, so an empty alt on the fallback branch is correct rather
 * than a gap.
 */
function cas_home_free_videos_img() {
	$url = cas_home_free_videos_photo();

	if ( ! $url ) {
		return '';
	}

	$sizes      = '(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px';
	$attachment = attachment_url_to_postid( $url );

	if ( $attachment ) {
		return wp_get_attachment_image(
			$attachment,
			'large',
			false,
			array(
				'sizes'    => $sizes,
				'loading'  => false,
				'decoding' => 'async',
			)
		);
	}

	return '<img src="' . esc_url( $url ) . '" alt="" sizes="' . esc_attr( $sizes ) . '" decoding="async">';
}

/*
 * Priority 10, with `panel => cas_pages`, which is how inc/cas-blog-templates.php and
 * inc/cas-free-videos.php register their own page sections. The 110 treatment is only
 * needed for the pointer sections inc/cas-customizer-map.php creates at 100; this id is
 * not one of them, so the map never touches it.
 *
 * Section priority 11 puts the card directly under "Home" (the hero, priority 10) and
 * above "Home - testimonials" (15), which is the order the three blocks appear in on the
 * page. 11 was chosen partly because it is outside the 25/35/45/65/75 range the other
 * homepage modules built in this round were pointed at, so the panel does not end up
 * with two sections sharing one number the way two sections once shared 60.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_free_videos',
			array(
				'title'       => 'Home &mdash; Free videos card',
				'panel'       => 'cas_pages',
				'priority'    => 11,
				'description' => 'The square card on the homepage that links to the Free videos page. The words &ldquo;Free videos&rdquo;, the play mark and the link itself are fixed &mdash; only the photograph and the sentence below the title can be changed here.',
			)
		);

		$wp_customize->add_setting(
			'cas_home_free_videos_photo',
			array(
				'default'           => cas_home_free_videos_photo_default(),
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'cas_home_free_videos_photo',
				array(
					'label'       => 'Photograph',
					/* 1.4.8 asks for the recommended size to sit beside the field. The slot
					   is square and about 373px wide on a desktop, so this is that doubled
					   for high-density screens. */
					'description' => 'Recommended 750 x 750px (square). Any size works, because the photograph is cropped to fill the square card &mdash; keep the subject near the centre. The title and the sentence are printed on top, so a darker or quieter photograph reads better.',
					'section'     => 'cas_page_home_free_videos',
					'settings'    => 'cas_home_free_videos_photo',
				)
			)
		);

		$wp_customize->add_setting(
			'cas_home_free_videos_text',
			array(
				'default'           => cas_home_free_videos_text_default(),
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'cas_home_free_videos_text',
			array(
				'label'       => 'Description',
				'description' => 'One sentence. On a computer it appears when the visitor points at the card; on a phone it is always shown. Clearing it leaves the card showing only its title.',
				'type'        => 'textarea',
				'section'     => 'cas_page_home_free_videos',
				'settings'    => 'cas_home_free_videos_text',
			)
		);
	}
);

/**
 * Renders the grid cell only. `--i:0` is the first stagger step of the trio row and is
 * carried over from the prototype unchanged.
 */
function cas_render_home_free_videos() {
	$href = cas_page_url( 'free-videos' );
	$text = cas_home_free_videos_text();
	$img  = cas_home_free_videos_img();

	ob_start();
	?>
	<div class="span-4 reveal-up" style="--i:0">
		<a class="free-video" href="<?php echo esc_url( $href ); ?>" aria-label="Free videos">
			<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() escapes its own attributes; the fallback branch escapes its own. ?>
			<div class="fv-veil">
				<div class="fv-play" aria-hidden="true">&#9654;</div>
				<strong class="font-a">Free videos</strong>
				<?php if ( $text ) : ?>
					<span><?php echo esc_html( $text ); ?></span>
				<?php endif; ?>
			</div>
		</a>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_free_videos', 'cas_render_home_free_videos' );
