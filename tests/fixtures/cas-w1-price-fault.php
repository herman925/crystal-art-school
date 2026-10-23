<?php
/* Local-only fault injection for test_price_gate_runtime.py. Never deploy. */
add_filter(
	'update_post_metadata',
	function ( $check, $object_id, $meta_key, $meta_value ) {
		if ( 'cas_price_confirm' !== ( $_POST['action'] ?? '' ) || empty( $_POST['w1_price_faults'] ) ) {
			return $check;
		}
		$faults = json_decode( wp_unslash( $_POST['w1_price_faults'] ), true );
		$values = isset( $faults[ $meta_key ] ) ? array_map( 'strval', (array) $faults[ $meta_key ] ) : array();
		return in_array( (string) $meta_value, $values, true ) ? true : $check;
	},
	9,
	4
);
