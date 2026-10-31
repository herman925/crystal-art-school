<?php

defined( 'ABSPATH' ) || exit;

function cas_content_hero_fields() {
	$native = cas_hero_fields();
	$fields = array();
	foreach ( array( 1, 2, 3 ) as $slide ) {
		foreach ( array( 'title', 'subtitle', 'large', 'small' ) as $part ) {
			$fields[ 'cas_hero_' . $slide . '_' . $part ] = array(
				'label' => 'Slide ' . $slide . ' — ' . $native[ $part ]['label'],
				'default' => cas_hero_default( $slide, $part ),
				'type' => $native[ $part ]['type'],
				'description' => $native[ $part ]['desc'],
			);
		}
	}
	return $fields;
}

function cas_content_hero_clean( $input ) {
	$fields = cas_content_hero_fields();
	if ( ! is_array( $input ) || count( $input ) !== 12 || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_hero_input', 'Submit exactly the twelve fields for the existing three slides.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'cas_hero_input', 'Each carousel field must contain text or an image URL.' );
		}
		$value = wp_unslash( $input[ $key ] );
		$value = 'image' === $field['type'] ? esc_url_raw( $value ) : wp_kses_post( $value );
		if ( 'image' === $field['type'] && ! cas_content_hero_image_allowed( $value ) ) {
			return new WP_Error( 'cas_hero_input', 'Use a known public original image from Media or clear the slot. Private, non-image or unresolved local-upload URLs cannot be used in the public carousel.' );
		}
		$clean[ $key ] = $value;
	}
	return $clean;
}

function cas_content_hero_controls( $values, $input_name = 'cas_hero_fields' ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit the homepage carousel.', '', array( 'response' => 403 ) );
	}
	$can_media = current_user_can( 'upload_files' );
	echo '<p>The three slides, image slots, View courses button and layout are fixed. Titles keep line breaks; descriptions display as plain text. Images crop to their existing slots.</p>';
	foreach ( cas_content_hero_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The carousel fields need an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2>';
		if ( $field['description'] ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
		$name = $input_name . '[' . $key . ']';
		if ( 'image' !== $field['type'] ) {
			echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			continue;
		}
		cas_content_image_slot( $key, $name, $value, $field, $can_media );
	}
	if ( ! $can_media ) {
		echo '<p>Your current media permission does not allow choosing or uploading images. Existing text and clearing slots remain available.</p>';
	}
}

function cas_content_hero_page() {
	cas_content_prose_page( 'hero' );
}
