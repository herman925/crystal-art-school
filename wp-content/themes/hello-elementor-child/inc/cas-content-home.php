<?php

defined( 'ABSPATH' ) || exit;

function cas_content_home_fields() {
	return array(
		'cas_home_free_videos_text' => array( 'label' => 'Free videos card — description', 'default' => cas_home_free_videos_text_default(), 'type' => 'textarea', 'sanitize' => 'wp_kses_post' ),
		'cas_home_gallery_sub' => array( 'label' => 'Art Gallery card — hover line', 'default' => CAS_HOME_GALLERY_SUB_DEFAULT, 'type' => 'text', 'sanitize' => 'sanitize_text_field' ),
		'cas_home_shop_lede' => array( 'label' => 'Shop all courses — introduction', 'default' => cas_home_courses_lede_default(), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_lede_1' => array( 'label' => 'Course overview — opening paragraph 1', 'default' => cas_overview_lede_1_default(), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_lede_2' => array( 'label' => 'Course overview — opening paragraph 2', 'default' => cas_overview_lede_2_default(), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_point_1_t' => array( 'label' => 'Course overview — point 1 title', 'default' => cas_overview_principle_default( 1, 'title' ), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_point_1_b' => array( 'label' => 'Course overview — point 1 text', 'default' => cas_overview_principle_default( 1, 'text' ), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_point_2_t' => array( 'label' => 'Course overview — point 2 title', 'default' => cas_overview_principle_default( 2, 'title' ), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
		'cas_home_overview_point_2_b' => array( 'label' => 'Course overview — point 2 text', 'default' => cas_overview_principle_default( 2, 'text' ), 'type' => 'textarea', 'sanitize' => 'sanitize_textarea_field' ),
	);
}

function cas_content_home_clean( $input ) {
	$fields = cas_content_home_fields();
	if ( ! is_array( $input ) || count( $input ) !== 9 || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_home_input', 'Submit exactly the nine Home text fields.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'cas_home_input', 'Each Home field must contain text.' );
		}
		$clean[ $key ] = call_user_func( $field['sanitize'], wp_unslash( $input[ $key ] ) );
	}
	return $clean;
}

function cas_content_home_controls( $values ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit Home text.', '', array( 'response' => 403 ) );
	}
	echo '<p>These fields display as plain text on the homepage; HTML does not add rich formatting. Images, hero slides and question lists are edited separately.</p>';
	foreach ( cas_content_home_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The Home text needs an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2>';
		$name = 'cas_home_fields[' . $key . ']';
		if ( 'text' === $field['type'] ) {
			echo '<input type="text" class="large-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		} else {
			echo '<textarea class="large-text" rows="4" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
		}
	}
}

function cas_content_home_page() {
	cas_content_prose_page( 'home' );
}
