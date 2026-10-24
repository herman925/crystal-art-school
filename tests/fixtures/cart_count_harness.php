<?php
namespace Tutor\Ecommerce\Cart {
	class NativeCart {
		public function get_cart_items() {
			$GLOBALS['calls'][] = 'read:' . $GLOBALS['input']['uid'];
			$mode = $GLOBALS['input']['mode'] ?? '';
			if ( 'throw' === $mode ) { throw new \RuntimeException( 'synthetic read failure' ); }
			if ( 'db_error' === $mode ) { $GLOBALS['wpdb']->last_error = 'synthetic query error'; return array(); }
			if ( 'same_db_error' === $mode ) { $GLOBALS['wpdb']->last_error = $GLOBALS['input']['previous_error']; return array(); }
			if ( 'bad_result' === $mode ) { return false; }
			if ( 'bad_item' === $mode ) { return array( (object) array( 'id' => array( 7 ) ) ); }
			$count = $GLOBALS['input']['counts'][ (string) $GLOBALS['input']['uid'] ] ?? 0;
			return array_map( static function ( $id ) { return (object) array( 'id' => $id ); }, $count ? range( 1, $count ) : array() );
		}
		public function add( $id ) { $GLOBALS['calls'][] = 'forbidden-write'; throw new \LogicException( 'Cart write forbidden' ); }
		public function remove( $id ) { $GLOBALS['calls'][] = 'forbidden-write'; throw new \LogicException( 'Cart write forbidden' ); }
		public function clear_cart() { $GLOBALS['calls'][] = 'forbidden-write'; throw new \LogicException( 'Cart write forbidden' ); }
	}
}
namespace TutorPro\Ecommerce\GuestCheckout {
	class GuestCart {}
}
namespace {
	define( 'ABSPATH', __DIR__ );
	$input = json_decode( stream_get_contents( STDIN ), true, 512, JSON_THROW_ON_ERROR );
	$calls = array();
	$headers_requested = false;
	$wpdb = (object) array( 'last_error' => $input['previous_error'] ?? '' );
	$_SERVER['REQUEST_METHOD'] = $input['method'] ?? 'POST';
	$_POST = $input['post'] ?? array( 'action' => 'cas_cart_count', 'nonce' => 'valid' );
	$hooks = array();
	class Sent extends \RuntimeException {
		public $payload;
		public function __construct( $payload ) { $this->payload = $payload; }
	}
	function add_action( $hook, $callback ) { $GLOBALS['hooks'][ $hook ] = $callback; }
	function nocache_headers() { $GLOBALS['headers_requested'] = true; }
	function wp_unslash( $value ) { return $value; }
	function wp_verify_nonce( $nonce, $action ) {
		return 'valid' === $nonce && 'cas_cart_count' === $action
			&& ( $GLOBALS['input']['nonce_uid'] ?? $GLOBALS['input']['uid'] ) === $GLOBALS['input']['uid'];
	}
	function is_user_logged_in() { return $GLOBALS['input']['uid'] > 0; }
	function tutor_utils() {
		return new class {
			public function is_monetize_by_tutor() { return $GLOBALS['input']['native'] ?? true; }
		};
	}
	function tutor_is_guest_checkout_enabled() { return $GLOBALS['input']['guest_enabled'] ?? false; }
	if ( empty( $input['missing_factory'] ) ) {
		function tutor_get_cart_object() {
			$GLOBALS['calls'][] = 'factory';
			return 'bad_object' === ( $GLOBALS['input']['mode'] ?? '' ) ? new \stdClass() : new \Tutor\Ecommerce\Cart\NativeCart();
		}
	}
	function tutor_ecommerce_get_cart_count() { throw new \LogicException( 'Failure-swallowing helper forbidden' ); }
	function tutor_get_cart_items() { throw new \LogicException( 'Failure-swallowing wrapper forbidden' ); }
	function wp_send_json_error( $data, $status = 200 ) { throw new Sent( array( 'status' => $status, 'success' => false, 'data' => $data ) ); }
	function wp_send_json_success( $data, $status = 200 ) { throw new Sent( array( 'status' => $status, 'success' => true, 'data' => $data ) ); }
	require $argv[1];
	try {
		cas_cart_count_response();
		throw new \LogicException( 'Missing response' );
	} catch ( Sent $sent ) {
		echo json_encode( array( 'response' => $sent->payload, 'calls' => $calls,
			'private_headers_requested' => $headers_requested, 'last_error' => $wpdb->last_error, 'hooks' => array_keys( $hooks ) ), JSON_THROW_ON_ERROR );
	}
}
