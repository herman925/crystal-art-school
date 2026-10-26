<?php

defined( 'ABSPATH' ) || exit;

// PREPARATION ONLY: controls and sanitiser; no routes, writes or save integration.
function cas_content_blog_fields() {
	return array(
		'cas_blog_intro' => array(
			'label'   => 'Introduction',
			'default' => cas_blog_intro_default(),
		),
	);
}

function cas_content_blog_clean( $input ) {
	$fields = cas_content_blog_fields();
	if ( ! is_array( $input ) || count( $input ) !== 1 || array_diff_key( $input, $fields ) || ! isset( $input['cas_blog_intro'] ) || ! is_string( $input['cas_blog_intro'] ) ) {
		return new WP_Error( 'cas_blog_input', 'Submit only the Blog introduction as text.' );
	}
	return array( 'cas_blog_intro' => wp_kses_post( wp_unslash( $input['cas_blog_intro'] ) ) );
}

function cas_content_blog_controls( $values ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit the Blog introduction.', '', array( 'response' => 403 ) );
	}
	foreach ( cas_content_blog_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The Blog introduction needs an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2>';
		echo '<p>Leave this box empty to show no introduction. Article content, the heading and the page layout are unchanged.</p>';
		wp_editor( wp_kses_post( $value ), $key, array(
			'textarea_name' => 'cas_blog_fields[' . $key . ']',
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
