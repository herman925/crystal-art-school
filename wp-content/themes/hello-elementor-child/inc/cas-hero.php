<?php
/**
 * Homepage hero — three slides, each with a title, a description, one large image and
 * one small image, per section 3.2 of the direction document.
 *
 * Those four fields per slide are the ones the document opens for editing. The CTA is
 * not: 3.2 fixes both its label and its destination, so it is rendered from code and
 * always points at the Courses page.
 *
 * Both image slots are fixed-ratio boxes filled with object-fit: cover, so an upload of
 * any aspect ratio crops to the slot instead of displacing the composition. That is
 * what 3.2 asks for, and it is why the layout does not need re-tuning per image. The
 * document also requires the recommended sizes to be stated in the admin; they are in
 * each control's description below.
 */

defined( 'ABSPATH' ) || exit;

const CAS_HERO_SLIDES = 3;

/**
 * Slot ratios are measured from the prototype: the large image occupies 41.7% x 79% of
 * a 1862:780 stage and the small one 24% x 50%, which at the shared 1440px wrapper's
 * 1392px content width comes to about 580x442 and 334x280. The recommendations below are those doubled, so
 * they stay sharp on high-density displays without asking for needlessly large files.
 */
function cas_hero_fields() {
	return [
		'title'    => [
			'label' => 'Title',
			'type'  => 'textarea',
			'desc'  => 'Line breaks are kept, so you can choose where the line wraps.',
		],
		'subtitle' => [
			'label' => 'Description',
			'type'  => 'textarea',
			'desc'  => '',
		],
		'large'    => [
			'label' => 'Large image',
			'type'  => 'image',
			'desc'  => 'Recommended 974 x 774px (landscape, roughly 5:4). Any size works, because the image is cropped to fit the slot. Keep the subject near the centre.',
		],
		'small'    => [
			'label' => 'Small image',
			'type'  => 'image',
			'desc'  => 'Recommended 560 x 490px (close to square). Sits in front of the large image, towards its lower left. Also cropped to fit.',
		],
	];
}

function cas_hero_defaults() {
	return [
		1 => [
			'title'    => "Unlock your\ncreative potential.",
			'subtitle' => 'Step-by-step video lessons designed for all skill levels: watercolour, oil painting and sketch art from passionate creators.',
			'large'    => '/img/hero-1-large.jpg',
			'small'    => '/img/hero-1-small.jpg',
		],
		2 => [
			'title'    => "Glorious\nwatercolour.",
			'subtitle' => 'Beginner to advanced, step by step, no rush.',
			'large'    => '/img/hero-2-large.jpg',
			'small'    => '/img/hero-2-small.jpg',
		],
		3 => [
			'title'    => "Built\nwith care.",
			'subtitle' => 'Real lessons from a working artist, not a content factory.',
			'large'    => '/img/hero-3-large.jpg',
			'small'    => '/img/hero-3-small.jpg',
		],
	];
}

/**
 * The shipped value for a field, image paths resolved to full URLs.
 *
 * This is registered as each control's Customizer default, not just used at render
 * time. Registering it means the panel opens showing the text that is actually live
 * and a thumbnail of the current image, so Crystal can see what she is replacing.
 * With an empty default the controls render blank, which reads as broken.
 */
function cas_hero_default( $slide, $field ) {
	$defaults = cas_hero_defaults();
	$value    = isset( $defaults[ $slide ][ $field ] ) ? $defaults[ $slide ][ $field ] : '';

	if ( $value && in_array( $field, [ 'large', 'small' ], true ) ) {
		return get_stylesheet_directory_uri() . $value;
	}

	return $value;
}

function cas_hero_value( $slide, $field ) {
	return get_theme_mod( "cas_hero_{$slide}_{$field}", cas_hero_default( $slide, $field ) );
}

/**
 * Render one hero image with the responsive ladder shipped beside static theme assets.
 *
 * Customizer image controls normally return a WordPress attachment URL, so those use
 * WordPress's own generated srcset. The shipped defaults live in the child theme and
 * use the checked-in derivatives; only files that exist are offered, so a changed
 * default cannot create a row of 404s. Width and height reserve the crop box before
 * either image paints.
 */
function cas_hero_img( $url, $sizes, $attrs = [] ) {
	if ( ! $url ) {
		return '';
	}

	$theme_dir = trailingslashit( get_stylesheet_directory() ) . 'img/';
	$theme_uri = trailingslashit( get_stylesheet_directory_uri() ) . 'img/';
	$path      = preg_replace( '/[?#].*$/', '', $url );
	$rel       = '';

	if ( 0 === strpos( $path, $theme_uri ) ) {
		$rel = ltrim( substr( $path, strlen( $theme_uri ) ), '/' );
	}

	if ( $rel && false === strpos( $rel, '..' ) && is_readable( $theme_dir . $rel ) ) {
		$size = getimagesize( $theme_dir . $rel );

		if ( ! $size ) {
			return '';
		}

		$ext         = pathinfo( $rel, PATHINFO_EXTENSION );
		$stem        = substr( $rel, 0, - ( strlen( $ext ) + 1 ) );
		$derivatives = [];

		foreach ( glob( $theme_dir . $stem . '-*.' . $ext ) ?: [] as $candidate ) {
			$name = basename( $candidate );

			if ( ! preg_match( '/-(\d+)\.' . preg_quote( $ext, '/' ) . '$/i', $name ) ) {
				continue;
			}

			$candidate_size = getimagesize( $candidate );

			if ( ! $candidate_size || $candidate_size[0] >= $size[0] ) {
				continue;
			}

			$candidate_rel = substr( $candidate, strlen( $theme_dir ) );
			$derivatives[ (int) $candidate_size[0] ] = esc_url( $theme_uri . $candidate_rel ) . ' ' . (int) $candidate_size[0] . 'w';
		}

		$render_attrs = [
			'alt'    => '',
			'width'  => (int) $size[0],
			'height' => (int) $size[1],
		];

		if ( $derivatives ) {
			ksort( $derivatives, SORT_NUMERIC );
			$derivatives[]       = esc_url( $theme_uri . $rel ) . ' ' . (int) $size[0] . 'w';
			$render_attrs['srcset'] = implode( ', ', $derivatives );
			$render_attrs['sizes']  = $sizes;
		}

		foreach ( $attrs as $name => $value ) {
			if ( 'alt' !== $name ) {
				$render_attrs[ $name ] = $value;
			}
		}

		$out = '<img src="' . esc_url( $theme_uri . $rel ) . '"';

		foreach ( $render_attrs as $name => $value ) {
			$out .= ' ' . sanitize_key( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return $out . '>';
	}

	$attachment = attachment_url_to_postid( $url );

	if ( $attachment ) {
		$attrs['alt']   = '';
		$attrs['sizes'] = $sizes;

		return wp_get_attachment_image( $attachment, 'full', false, $attrs );
	}

	$out = '<img src="' . esc_url( $url ) . '" alt=""';

	foreach ( $attrs as $name => $value ) {
		$out .= ' ' . sanitize_key( $name ) . '="' . esc_attr( $value ) . '"';
	}

	return $out . '>';
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_hero',
			[
				'title'       => 'Homepage hero',
				'description' => 'The three rotating slides at the top of the homepage. The View courses button is fixed and always links to the Courses page.',
				'priority'    => 26,
			]
		);

		foreach ( range( 1, CAS_HERO_SLIDES ) as $slide ) {
			foreach ( cas_hero_fields() as $field => $spec ) {
				$key = "cas_hero_{$slide}_{$field}";

				$wp_customize->add_setting(
					$key,
					[
						'default'           => cas_hero_default( $slide, $field ),
						'sanitize_callback' => 'image' === $spec['type'] ? 'esc_url_raw' : 'wp_kses_post',
						'transport'         => 'refresh',
					]
				);

				$args = [
					'label'       => sprintf( 'Slide %d - %s', $slide, $spec['label'] ),
					'description' => $spec['desc'],
					'section'     => 'cas_hero',
					'settings'    => $key,
				];

				if ( 'image' === $spec['type'] ) {
					$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, $args ) );
				} else {
					$args['type'] = 'textarea';
					$wp_customize->add_control( $key, $args );
				}
			}
		}
	}
);

function cas_render_hero() {
	$cta = cas_page_url( 'courses' );

	ob_start();
	?>
	<div class="pg-wrap">
		<div class="logo2 is-center"><img src="<?php echo esc_url( cas_brand_image( 'cas_logo2' ) ); ?>" alt="" aria-hidden="true"></div>
		<header class="hero hero--compact" id="hero" aria-roledescription="carousel" aria-label="Studio highlights">
			<div class="hero__panel" aria-hidden="true"></div>
			<svg class="hero__swirl" viewBox="0 0 600 1200" aria-hidden="true" preserveAspectRatio="xMidYMid slice">
				<path d="M 60 40 C 240 80, 380 200, 320 320 S 60 460, 100 580 C 160 720, 400 700, 420 820 S 180 980, 220 1080 C 260 1140, 460 1160, 540 1100"></path>
				<path d="M 80 120 C 200 180, 300 260, 240 360 S 120 500, 180 600 C 260 700, 360 760, 320 860 S 140 1000, 200 1120"></path>
			</svg>
			<?php foreach ( range( 1, CAS_HERO_SLIDES ) as $i ) : ?>
				<?php $first = ( 1 === $i ); ?>
				<article class="hero__slide<?php echo $first ? ' is-active' : ''; ?>" data-slide="<?php echo esc_attr( $i ); ?>">
					<div class="hero__text">
						<?php
						/*
						 * Only slide 1 carries the page's h1. The other two titles are captions,
						 * so the document outline does not change with whichever slide happens to
						 * be showing.
						 */
						$title = nl2br( esc_html( cas_hero_value( $i, 'title' ) ) );

						if ( $first ) {
							echo '<h1 class="hero__title font-a">' . $title . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
						} else {
							echo '<p class="hero__title font-a">' . $title . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
						<p class="hero__subtitle"><?php echo esc_html( cas_hero_value( $i, 'subtitle' ) ); ?></p>
						<a class="hero__cta" href="<?php echo esc_url( $cta ); ?>">View courses <span aria-hidden="true">&rarr;</span></a>
					</div>
					<div class="hero__media">
						<?php
						/*
						 * Slide 1's large image is the one actually seen on load, so it takes
						 * priority and the inactive slides are lazy-loaded rather than racing it.
						 * They should not compete with the first image for the page's initial
						 * request budget.
						 */
						?>
						<?php
						$large_attrs = [
							'fetchpriority' => $first ? 'high' : 'low',
							'loading'      => $first ? 'eager' : 'lazy',
							'decoding'     => 'async',
						];
						$small_attrs = [
							'fetchpriority' => 'low',
							'loading'      => $first ? 'eager' : 'lazy',
							'decoding'     => 'async',
						];
						?>
						<figure class="hero__img hero__img--large"><?php echo cas_hero_img( cas_hero_value( $i, 'large' ), '(max-width: 860px) calc(70vw - 22px), (max-width: 1440px) calc(41.7vw - 20px), 580px', $large_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput ?></figure>
						<figure class="hero__img hero__img--small"><?php echo cas_hero_img( cas_hero_value( $i, 'small' ), '(max-width: 860px) calc(38vw - 12px), (max-width: 1440px) calc(24vw - 12px), 334px', $small_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput ?></figure>
					</div>
				</article>
			<?php endforeach; ?>
			<nav class="hero__nav" aria-label="Hero slides">
				<button class="cas-pause" type="button" data-hero-pause aria-pressed="false" aria-label="Pause the studio highlights carousel">
					<svg class="ico-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"></rect><rect x="14" y="5" width="4" height="14" rx="1"></rect></svg>
					<svg class="ico-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.2v13.6L19 12z"></path></svg>
				</button>
				<button class="hero__arrow" type="button" data-hero="prev" aria-label="Previous slide"><svg viewBox="0 0 64 12" aria-hidden="true"><path d="M0 6h62 M6 1 L1 6 L6 11" fill="none" stroke="currentColor" stroke-width="1"></path></svg></button>
				<button class="hero__arrow" type="button" data-hero="next" aria-label="Next slide"><svg viewBox="0 0 64 12" aria-hidden="true"><path d="M0 6h62 M58 1 L63 6 L58 11" fill="none" stroke="currentColor" stroke-width="1"></path></svg></button>
				<div class="hero__counter"><span class="hero__counter-current">01</span> <span class="hero__counter-total">/ 0<?php echo esc_html( CAS_HERO_SLIDES ); ?></span></div>
			</nav>
		</header>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_hero', 'cas_render_hero' );
