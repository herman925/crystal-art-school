<?php
/**
 * Homepage "About Crystal" card — section 3.4 of the direction document.
 *
 * The third column of the New release row: her photograph in the twin-arch mask,
 * linking to the About page, with the ABOUT CRYSTAL label and the four social buttons
 * underneath.
 *
 * 3.4 is one of the chapters that states its own grants rather than leaving them to the
 * default-closed rule, and it lists exactly five: the About image, and the Instagram,
 * Facebook, YouTube and Email links. It then fixes the heading in the same breath —
 * 「`ABOUT CRYSTAL` 標題不開放後台修改」 — so the label is rendered from code.
 *
 * The four links are not registered here. 3.4 also says the homepage About block, the
 * Follow along strip and the footer use one set of four channels, so this reads
 * cas_social_links() like the other two. Crystal edits them once, under
 * Customizer → Site-wide → Contact & social links, and all three move together.
 *
 * There is no body copy in this card. The prototype's column is photograph, label and
 * social buttons only, and 3.4 opens no text field, so none is invented here.
 *
 * The photograph is a fixed-ratio slot filled with object-fit: cover — .arch-duo is
 * `aspect-ratio: 1/1` on this row and `.arch-duo > img` is `height: 100%;
 * object-fit: cover` — so an upload of any shape crops to the slot instead of
 * displacing the column.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The photograph shipped with the theme: img/A3.jpeg, the file Crystal supplied in
 * assets/00 - Home/ and the one the prototype's homepage renders in this slot.
 *
 * Registered as the control's default as well as used at render time, so the panel
 * opens showing a thumbnail of what is live rather than an empty control.
 */
function cas_home_about_default_image() {
	return get_stylesheet_directory_uri() . '/img/A3.jpeg';
}

/**
 * Removing an image in the Customizer stores an empty string, and get_theme_mod()
 * returns that in preference to the registered default — which would emit
 * <img src="">. Fall back the way cas_brand_image() does instead.
 */
function cas_home_about_image_url() {
	$set = get_theme_mod( 'cas_home_about_image' );

	return $set ? $set : cas_home_about_default_image();
}

/**
 * The <img> for the slot.
 *
 * While the shipped file is still in place the responsive ladder is read off disk —
 * whichever of the -192/-384/-768 derivatives are actually there — so a phone is not
 * sent the 1152px master for a 358px slot. Once Crystal uploads her own, the attachment
 * is recovered from the stored URL so WordPress builds the srcset; a plain <img> at a
 * plain URL would ship the full-size original.
 *
 * object-position is only applied to the shipped file. 50% 100% is calibrated to that
 * one 1152x2048 portrait, and holding a bottom anchor over an image chosen by someone
 * else would crop it in a way nobody asked for.
 */
function cas_home_about_img() {
	$alt   = 'Crystal — founder portrait in the twin-arch mask';
	$sizes = '(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px';
	$url   = cas_home_about_image_url();

	if ( cas_home_about_default_image() === $url ) {
		$dir = get_stylesheet_directory() . '/img/';
		$uri = get_stylesheet_directory_uri() . '/img/';

		if ( ! is_readable( $dir . 'A3.jpeg' ) ) {
			return '';
		}

		$set = array();

		foreach ( array( 192, 384, 768 ) as $w ) {
			if ( is_readable( $dir . 'A3-' . $w . '.jpeg' ) ) {
				$set[] = esc_url( $uri . 'A3-' . $w . '.jpeg' ) . ' ' . $w . 'w';
			}
		}

		$size = getimagesize( $dir . 'A3.jpeg' );
		$out  = '<img src="' . esc_url( $uri . 'A3.jpeg' ) . '" alt="' . esc_attr( $alt ) . '"';

		if ( $size ) {
			$out .= ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"';

			if ( $set ) {
				$set[] = esc_url( $uri . 'A3.jpeg' ) . ' ' . (int) $size[0] . 'w';
				$out  .= ' srcset="' . esc_attr( implode( ', ', $set ) ) . '" sizes="' . esc_attr( $sizes ) . '"';
			}
		}

		return $out . ' style="object-position:50% 100%;" decoding="async">';
	}

	$attachment = attachment_url_to_postid( $url );

	if ( $attachment ) {
		return wp_get_attachment_image(
			$attachment,
			'large',
			false,
			array(
				'alt'      => $alt,
				'sizes'    => $sizes,
				'decoding' => 'async',
			)
		);
	}

	return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" decoding="async">';
}

/**
 * Priority 10 with an explicit panel, the way cas-blog-templates.php and
 * cas-free-videos.php register theirs. cas-customizer-map.php builds the Pages panel at
 * priority 100 and would overwrite a section it did not create, so anything added into
 * one of ITS sections has to wait until 110 — this is a section of its own, so 10 is
 * correct and puts it in the panel before the map runs.
 *
 * Priority 12 sits it directly under Home (10) and above Home — testimonials (15).
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_about',
			array(
				'title'       => 'Home — About Crystal',
				'panel'       => 'cas_pages',
				'priority'    => 12,
				'description' => 'The About Crystal card on the homepage. The heading and the link to the About page are part of the design and are not edited here. The four social buttons under it are the site-wide ones, edited under <strong>Site-wide &rarr; Contact &amp; social links</strong>.',
			)
		);

		$wp_customize->add_setting(
			'cas_home_about_image',
			array(
				'default'           => cas_home_about_default_image(),
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'cas_home_about_image',
				array(
					'label'       => 'About photograph',
					'description' => 'Recommended 840 x 840px (square). Any size works — the image is cropped to a square slot, so keep the subject near the centre.',
					'section'     => 'cas_page_home_about',
					'settings'    => 'cas_home_about_image',
				)
			)
		);
	}
);

/**
 * Column 3 of the New release row. This returns the column only, not the row and not
 * the section: columns 1 and 2 of the same .grid-12.release-trio are the Free videos
 * card and the New release carousel.
 */
function cas_render_home_about() {
	$img = cas_home_about_img();

	if ( ! $img ) {
		return '';
	}

	/* WordPress rewrites `sizes="…"` into `sizes="auto, …"` on every lazy-loaded image
	   in filtered content, and Chrome then refuses to resolve a percentage height on an
	   img carrying `sizes=auto`. `.arch-duo > img` is `height: 100%`, which is exactly
	   the shape that made every About certificate render as an empty frame. The `sizes`
	   stated above is the value the prototype emits for this slot, so `auto` has no
	   measurement to add. Same switch, same reason, as cas_render_about(). */
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	ob_start();
	?>
	<div class="span-4 about-col reveal-up" style="--i:2">
		<a class="arch-link" href="<?php echo esc_url( cas_page_url( 'about' ) ); ?>" aria-label="Meet Crystal &mdash; about me">
			<div class="arch-duo">
				<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</a>
		<div class="arch-cap">
			<div class="eyebrow-lbl">About Crystal</div>
			<div class="social-row"><?php echo cas_social_links( 'social-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_about', 'cas_render_home_about' );
