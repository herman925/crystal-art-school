<?php

defined( 'ABSPATH' ) || exit;

function cas_content_home_images_fields() {
	return array(
		'cas_home_about_image' => array( 'label' => 'About Crystal photograph', 'default' => cas_home_about_default_image(), 'restore' => true, 'description' => 'Recommended 840 x 840px (square). Any size works — the image is cropped to a square slot, so keep the subject near the centre.' ),
		'cas_home_free_videos_photo' => array( 'label' => 'Free videos card photograph', 'default' => cas_home_free_videos_photo_default(), 'restore' => true, 'description' => 'Recommended 750 x 750px (square). The photograph crops to fill the card; a darker or quieter photograph keeps the overlaid title readable.' ),
		'cas_home_tools_photo' => array( 'label' => 'TOOLS card background photograph', 'default' => '', 'restore' => false, 'description' => 'Optional. Recommended about 400 x 400px. The photograph crops to its square. With no photograph the card stays a plain dark square.' ),
	);
}

function cas_content_about_image_fields() {
	$native = cas_about_fields()['cas_about_portrait'];
	return array( 'cas_about_portrait' => array( 'label' => $native['label'], 'default' => get_stylesheet_directory_uri() . $native['default'], 'description' => $native['desc'], 'restore' => true ) );
}

function cas_content_brand_images_fields() {
	$native = cas_brand_assets();
	$fields = array();
	foreach ( array( 'cas_logo', 'cas_logo2', 'cas_signature' ) as $key ) {
		$fields[ $key ] = array( 'label' => $native[ $key ]['label'], 'default' => get_stylesheet_directory_uri() . $native[ $key ]['fallback'], 'description' => $native[ $key ]['desc'], 'restore' => true );
	}
	return $fields;
}

function cas_content_images_clean( $input, $fields, $error ) {
	if ( ! is_array( $input ) || count( $input ) !== count( $fields ) || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( $error, 'Submit exactly the image URL fields on this form.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( $error, 'Each image field must contain a URL string, not an attachment ID.' );
		}
		$value = esc_url_raw( wp_unslash( $input[ $key ] ) );
		if ( ! cas_content_hero_image_allowed( $value ) ) {
			return new WP_Error( $error, 'Use a known public original image from Media or clear the field. Private, non-image or unresolved local-upload URLs are not allowed.' );
		}
		$clean[ $key ] = $value;
	}
	return $clean;
}

function cas_content_home_images_clean( $input ) {
	return cas_content_images_clean( $input, cas_content_home_images_fields(), 'cas_home_images_input' );
}
function cas_content_about_image_clean( $input ) {
	return cas_content_images_clean( $input, cas_content_about_image_fields(), 'cas_about_image_input' );
}
function cas_content_brand_images_clean( $input ) {
	return cas_content_images_clean( $input, cas_content_brand_images_fields(), 'cas_brand_images_input' );
}

function cas_content_images_controls( $values, $fields, $input_name ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit these images.', '', array( 'response' => 403 ) );
	}
	$can_media = current_user_can( 'upload_files' );
	foreach ( $fields as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The image URLs need an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2><p class="description">' . esc_html( $field['description'] ) . '</p>';
		echo '<p>' . esc_html( $field['restore'] ? 'Clearing this field stores an empty URL and uses the existing shipped default; it does not remove the image from the public layout.' : 'Clearing this optional field leaves the plain dark TOOLS card, without a photograph.' ) . '</p>';
		cas_content_image_slot( $key, $input_name . '[' . $key . ']', $value, $field, $can_media );
	}
	if ( ! $can_media ) {
		echo '<p>Your current media permission does not allow choosing or uploading images. Existing content permissions still govern clearing these fields.</p>';
	}
}

function cas_content_home_images_controls( $values ) {
	cas_content_images_controls( $values, cas_content_home_images_fields(), 'cas_home_image_fields' );
}
function cas_content_about_image_controls( $values ) {
	cas_content_images_controls( $values, cas_content_about_image_fields(), 'cas_about_image_fields' );
}
function cas_content_brand_images_controls( $values ) {
	cas_content_images_controls( $values, cas_content_brand_images_fields(), 'cas_brand_image_fields' );
}

function cas_content_home_images_page() { cas_content_prose_page( 'home-images' ); }
function cas_content_about_image_page() { cas_content_prose_page( 'about-image' ); }
function cas_content_brand_images_page() { cas_content_prose_page( 'brand-images' ); }
