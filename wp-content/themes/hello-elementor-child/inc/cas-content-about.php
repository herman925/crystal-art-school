<?php

defined( 'ABSPATH' ) || exit;

function cas_content_about_fields() {
	$schema = cas_about_fields();
	$fields = array();
	foreach ( array( 'cas_about_intro', 'cas_about_quote', 'cas_about_journey_body', 'cas_about_community_body', 'cas_about_instructor_body' ) as $key ) {
		$fields[ $key ] = $schema[ $key ];
	}
	return $fields;
}

function cas_content_about_clean( $input ) {
	$fields = cas_content_about_fields();
	if ( ! is_array( $input ) || count( $input ) !== count( $fields ) || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'about_input', 'Submit exactly the five About text fields. Nothing has been saved.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'about_input', 'Each About text field must contain text. Nothing has been saved.' );
		}
		$value = wp_kses_post( wp_unslash( $input[ $key ] ) );
		if ( 'inline' === $field['type'] ) {
			preg_match_all( '/<\/?([a-z][a-z0-9]*)\b[^>]*>/i', $value, $tags );
			$inline = array( 'a', 'abbr', 'acronym', 'b', 'br', 'cite', 'code', 'del', 'em', 'i', 'ins', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var' );
			if ( array_diff( array_map( 'strtolower', $tags[1] ), $inline ) ) {
				return new WP_Error( 'about_input', 'Opening paragraph and quotation accept inline formatting only. Remove paragraph wrappers, headings, lists or images from these two fields. Nothing has been saved.' );
			}
		}
		$clean[ $key ] = $value;
	}
	return $clean;
}

function cas_content_about_controls( $values, $input_name = 'cas_about_fields' ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit About text.', '', array( 'response' => 403 ) );
	}
	foreach ( cas_content_about_fields() as $key => $field ) {
		$value = $values[ $key ] ?? $field['default'];
		$inline = 'inline' === $field['type'];
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( html_entity_decode( $field['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) . '</label></h2>';
		echo '<p>' . esc_html( $inline ? 'One paragraph, with emphasis or links. Headings and lists belong in the essays below.' : 'Use paragraphs and emphasis. Cross-headings use the Cross-heading format; the section heading above this essay stays fixed.' ) . '</p>';
		wp_editor( $value, $key, array(
			'textarea_name' => $input_name . '[' . $key . ']',
			'textarea_rows' => $inline ? 3 : 10,
			'media_buttons' => false,
			'quicktags'     => false,
			'wpautop'       => ! $inline,
			'tinymce'       => array(
				'forced_root_block' => $inline ? false : 'p',
				'toolbar1'          => $inline ? 'bold italic link unlink undo redo' : 'styleselect bold italic bullist numlist link unlink undo redo',
				'toolbar2'          => '',
				'style_formats'     => array( array( 'title' => 'Paragraph', 'block' => 'p' ), array( 'title' => 'Cross-heading', 'block' => 'h3', 'classes' => 'bio-sub' ) ),
			),
		) );
	}
}

function cas_content_about_page() {
	cas_content_prose_page( 'about' );
}
