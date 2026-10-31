<?php

defined( 'ABSPATH' ) || exit;

function cas_content_contact_fields() {
	$defaults = cas_social_defaults();
	$fields = array();
	foreach ( array( 'cas_social_instagram' => 'instagram', 'cas_social_facebook' => 'facebook', 'cas_social_youtube' => 'youtube', 'cas_social_email' => 'email' ) as $key => $name ) {
		$fields[ $key ] = array(
			'label' => $defaults[ $name ]['label'],
			'default' => $defaults[ $name ]['default'],
			'type' => 'email' === $name ? 'email' : 'url',
			'sanitize' => 'email' === $name ? 'sanitize_email' : 'esc_url_raw',
		);
	}
	return $fields;
}

function cas_content_contact_clean( $input ) {
	$fields = cas_content_contact_fields();
	if ( ! is_array( $input ) || count( $input ) !== 4 || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_contact_input', 'Submit exactly the three social URLs and contact email.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'cas_contact_input', 'Each contact field must contain text.' );
		}
		$clean[ $key ] = call_user_func( $field['sanitize'], wp_unslash( $input[ $key ] ) );
	}
	return $clean;
}

function cas_content_contact_controls( $values, $input_name = 'cas_contact_fields' ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit site-wide contact values.', '', array( 'response' => 403 ) );
	}
	echo '<p>These are the existing shared values, not separate settings for each page. Changes also affect the contact and enquiry email destinations.</p>';
	foreach ( cas_content_contact_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The shared contact values need an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( cas_admin_title( $field['label'] ) ) . '</label></h2>';
		echo '<input type="' . esc_attr( $field['type'] ) . '" class="large-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $input_name . '[' . $key . ']' ) . '" value="' . esc_attr( $value ) . '">';
	}
}

function cas_content_contact_page() {
	cas_content_prose_page( 'contact' );
}
