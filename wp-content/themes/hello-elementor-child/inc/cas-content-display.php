<?php
defined( 'ABSPATH' ) || exit;

function cas_content_display_fields() {
	return array( 'cas_home_testimonial_limit' => array( 'label' => 'Student Comments Shown', 'default' => '3' ) );
}
function cas_content_display_clean( $input ) {
	if ( ! is_array( $input ) || array_keys( $input ) !== array( 'cas_home_testimonial_limit' ) || ! is_string( $input['cas_home_testimonial_limit'] ) || ! preg_match( '/^(?:[1-9]|1[0-2])$/D', $input['cas_home_testimonial_limit'] ) ) {
		return new WP_Error( 'home-display_input', 'Choose a student comment limit from 1 to 12.' );
	}
	return $input;
}
function cas_content_display_controls( $values, $input_name ) {
	$value = $values['cas_home_testimonial_limit'] ?? '3';
	echo '<p><label for="cas_home_testimonial_limit">Student Comments Shown</label></p><input type="number" id="cas_home_testimonial_limit" name="' . esc_attr( $input_name ) . '[cas_home_testimonial_limit]" min="1" max="12" step="1" required value="' . esc_attr( (string) $value ) . '"><p>From 1 to 12, in the Order set in Student Comments. Default: 3. Additional published comments are retained but hidden from Home.</p>';
}
