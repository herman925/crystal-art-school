<?php

defined( 'ABSPATH' ) || exit;

function cas_cart_count_response() {
	nocache_headers();
	header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
	header( 'Vary: Cookie', false );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		header( 'Allow: POST' );
		wp_send_json_error( array( 'known' => false ), 405 );
	}

	$nonce = $_POST['nonce'] ?? null;
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'cas_cart_count' ) ) {
		wp_send_json_error( array( 'known' => false ), 403 );
	}
	if ( array_diff( array_keys( $_POST ), array( 'action', 'nonce' ) ) ) {
		wp_send_json_error( array( 'known' => false ), 400 );
	}

	global $wpdb;
	$previous_error = $wpdb->last_error;
	$wpdb->last_error = '';
	$count = null;
	try {
		if ( ! function_exists( 'tutor_utils' ) || ! function_exists( 'tutor_get_cart_object' ) || ! tutor_utils()->is_monetize_by_tutor() ) {
			throw new RuntimeException( 'Native cart unavailable.' );
		}
		if ( ! is_user_logged_in() && ( ! function_exists( 'tutor_is_guest_checkout_enabled' ) || ! tutor_is_guest_checkout_enabled()
			|| ! class_exists( '\TutorPro\Ecommerce\GuestCheckout\GuestCart' ) ) ) {
			throw new RuntimeException( 'Guest cart unavailable.' );
		}

		$cart = tutor_get_cart_object();
		if ( ! $cart instanceof \Tutor\Ecommerce\Cart\NativeCart ) {
			throw new RuntimeException( 'Unsupported cart.' );
		}
		// The higher-level count helper catches read failures and reports an empty cart.
		$items = $cart->get_cart_items();
		if ( ! is_array( $items ) || '' !== $wpdb->last_error ) {
			throw new RuntimeException( 'Cart read failed.' );
		}
		foreach ( $items as $item ) {
			if ( ! is_object( $item ) || ! isset( $item->id ) || ( ! is_int( $item->id ) && ! is_string( $item->id ) )
				|| ! ctype_digit( (string) $item->id ) || (int) $item->id < 1 ) {
				throw new RuntimeException( 'Invalid cart result.' );
			}
		}
		$count = count( $items );
	} catch ( Throwable $error ) {
		$count = null;
	} finally {
		if ( '' === $wpdb->last_error ) {
			$wpdb->last_error = $previous_error;
		}
	}

	if ( null === $count ) {
		wp_send_json_error( array( 'known' => false ), 503 );
	}
	wp_send_json_success( array( 'known' => true, 'count' => $count ) );
}

add_action( 'wp_ajax_cas_cart_count', 'cas_cart_count_response' );
add_action( 'wp_ajax_nopriv_cas_cart_count', 'cas_cart_count_response' );
