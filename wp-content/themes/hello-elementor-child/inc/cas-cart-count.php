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
	$view = $_POST['view'] ?? '';
	if ( ! is_string( $view ) || ! in_array( $view, array( '', 'drawer' ), true )
		|| array_diff( array_keys( $_POST ), array( 'action', 'nonce', 'view' ) ) ) {
		wp_send_json_error( array( 'known' => false ), 400 );
	}

	global $wpdb;
	$previous_error = $wpdb->last_error;
	$wpdb->last_error = '';
	$count = null;
	$html = null;
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
		if ( 'drawer' === $view ) {
			$html = cas_cart_drawer_html( $items );
			if ( '' !== $wpdb->last_error ) { throw new RuntimeException( 'Cart details read failed.' ); }
		}
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
	$data = array( 'known' => true, 'count' => $count );
	if ( null !== $html ) { $data['html'] = $html; }
	wp_send_json_success( $data );
}

/** Render only the current native cart; all mutation remains in Tutor's handlers. */
function cas_cart_drawer_html( $items ) {
	if ( ! $items ) {
		return '<div class="mc-empty"><span class="ast" aria-hidden="true">✦</span><p>Your basket is empty.</p><a class="mc-btn primary" href="' . esc_url( cas_page_url( 'gallery' ) ) . '">Browse the gallery →</a></div>';
	}
	$lines = '';
	$subtotal = 0;
	foreach ( $items as $item ) {
		$id = (int) $item->id;
		$post = get_post( $id );
		if ( ! $post || 'courses' !== $post->post_type || 'publish' !== $post->post_status || $post->post_password ) {
			throw new RuntimeException( 'Cart course unavailable.' );
		}
		$price = tutor_utils()->get_raw_course_price( $id );
		$regular = $price->regular_price ?? null;
		$sale = $price->sale_price ?? 0;
		if ( ! is_numeric( $regular ) || ! is_numeric( $sale )
			|| ! is_finite( (float) $regular ) || ! is_finite( (float) $sale ) || $regular < 0 || $sale < 0 ) {
			throw new RuntimeException( 'Cart price unavailable.' );
		}
		$amount = $sale ? (float) $sale : (float) $regular;
		$subtotal += $amount;
		if ( ! is_finite( $subtotal ) ) { throw new RuntimeException( 'Cart subtotal unavailable.' ); }
		$image = get_tutor_course_thumbnail_src( '', $id );
		$lines .= '<div class="mc-line"><div class="mc-thumb">'
			. ( $image ? '<img src="' . esc_url( $image ) . '" alt="">' : '' ) . '</div>'
			. '<div><div class="mc-name">' . esc_html( get_the_title( $id ) ) . '</div><div class="mc-kind">Course</div></div>'
			. '<div class="mc-right"><div class="mc-price">'
			. ( $regular && $sale && (float) $sale !== (float) $regular ? '<s>' . esc_html( cas_course_price( $regular ) ) . '</s> <b class="now">' . esc_html( cas_course_price( $amount ) ) . '</b>' : esc_html( cas_course_price( $amount ) ) )
			. '</div><button class="mc-rm" type="button" data-course-id="' . $id . '" aria-label="' . esc_attr( 'Remove ' . get_the_title( $id ) ) . '">Remove</button></div></div>';
	}
	return $lines . '<div class="mc-foot"><div class="mc-sub">Subtotal<b>' . esc_html( cas_course_price( $subtotal ) ) . '</b></div>'
		. '<div class="mc-acts"><button class="mc-btn ghost" type="button" data-keep>Keep shopping</button>'
		. '<a class="mc-btn primary" href="' . esc_url( cas_page_url( 'checkout' ) ) . '">Go to checkout →</a></div></div>';
}

add_action( 'wp_ajax_cas_cart_count', 'cas_cart_count_response' );
add_action( 'wp_ajax_nopriv_cas_cart_count', 'cas_cart_count_response' );
