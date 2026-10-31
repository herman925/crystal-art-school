<?php

defined( 'ABSPATH' ) || exit;

function cas_content_free_videos_fields() {
	return array(
		'cas_fv_lead'         => array( 'label' => 'Opening line', 'default' => cas_fv_intro_default( 'lead' ) ),
		'cas_fv_intro_demos'  => array( 'label' => 'Step-by-step demos — introduction', 'default' => cas_fv_intro_default( 'demos' ) ),
		'cas_fv_intro_quick'  => array( 'label' => 'Quick inspiration — introduction', 'default' => cas_fv_intro_default( 'quick' ) ),
		'cas_fv_intro_shorts' => array( 'label' => 'Shorts — introduction', 'default' => cas_fv_intro_default( 'shorts' ) ),
	);
}

function cas_content_free_videos_clean( $input ) {
	$fields = cas_content_free_videos_fields();
	if ( ! is_array( $input ) || count( $input ) !== 4 || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_free_videos_input', 'Submit exactly the opening line and three Free Videos introductions.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'cas_free_videos_input', 'Each Free Videos introduction must contain text.' );
		}
		$clean[ $key ] = wp_kses_post( wp_unslash( $input[ $key ] ) );
	}
	return $clean;
}

function cas_content_free_videos_controls( $values, $input_name = 'cas_fv_fields' ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit Free Videos introductions.', '', array( 'response' => 403 ) );
	}
	echo '<p>Leave a box empty to hide that paragraph. Video records, section headings and layout are unchanged.</p>';
	foreach ( cas_content_free_videos_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The Free Videos introductions need an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2>';
		wp_editor( wp_kses_post( $value ), $key, array(
			'textarea_name' => $input_name . '[' . $key . ']',
			'textarea_rows' => 4,
			'media_buttons' => false,
			'quicktags'     => false,
			'wpautop'       => false,
			'tinymce'       => array(
				'forced_root_block' => false,
				'toolbar1'          => 'bold italic link unlink undo redo',
				'toolbar2'          => '',
			),
		) );
	}
}

function cas_content_free_videos_page() {
	cas_content_prose_page( 'free-videos' );
}
