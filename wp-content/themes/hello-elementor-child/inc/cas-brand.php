<?php
/**
 * Brand assets Crystal can replace herself.
 *
 * The logo and the footer signature were theme files, so swapping either needed a
 * developer. Both are assets she owns and is actively replacing — she has asked for a
 * thinner logo, and the current signature is an opaque white-ground export awaiting a
 * transparent one — so routing every swap through a deploy was the wrong shape.
 *
 * They are images, not layout, so exposing them does not cut against the
 * default-closed CMS rule in the direction document (§1.4.14), which locks structure,
 * colour, type, icons and button destinations.
 *
 * Each falls back to the file currently shipped in the theme, so the site renders
 * identically until something is uploaded.
 */

defined( 'ABSPATH' ) || exit;

function cas_brand_assets() {
	return [
		'cas_logo'      => [
			'label'    => 'Header logo',
			'fallback' => '/img/lau-logo.png',
			'desc'     => 'Replaces the Lau. mark in the site header. A transparent PNG or SVG works best — the theme recolours it per light/dark mode, so the colour it is drawn in does not matter.',
		],
		'cas_logo2'     => [
			'label'    => 'Hero signature mark',
			'fallback' => '/img/logo2.png',
			'desc'     => 'The signature mark centred above the homepage hero. Transparent PNG or SVG.',
		],
		'cas_signature' => [
			'label'    => 'Footer signature',
			'fallback' => '/img/signature.png',
			'desc'     => 'Crystal\'s signature in the footer. Needs a transparent background; the current file has a white ground and shows as a pale block on the dark footer.',
		],
	];
}

function cas_brand_image( $key ) {
	$assets = cas_brand_assets();

	if ( ! isset( $assets[ $key ] ) ) {
		return '';
	}

	$set = get_theme_mod( $key );

	return $set ? $set : get_stylesheet_directory_uri() . $assets[ $key ]['fallback'];
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_brand',
			[
				'title'       => 'Brand assets',
				'description' => 'Upload replacements for the logo and signature. Leave empty to keep the current image.',
				'priority'    => 25,
			]
		);

		foreach ( cas_brand_assets() as $key => $asset ) {
			$wp_customize->add_setting(
				$key,
				[
					'default'           => get_stylesheet_directory_uri() . $asset['fallback'],
					'sanitize_callback' => 'esc_url_raw',
					'transport'         => 'refresh',
				]
			);

			$wp_customize->add_control(
				new WP_Customize_Image_Control(
					$wp_customize,
					$key,
					[
						'label'       => $asset['label'],
						'description' => $asset['desc'],
						'section'     => 'cas_brand',
						'settings'    => $key,
					]
				)
			);
		}
	}
);
