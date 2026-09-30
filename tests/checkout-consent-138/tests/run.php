<?php
// Local test of the no-refund consent (issue #138) against Tutor 4.1.0 (free) with a manual payment method.
// It drives Tutor's own CheckoutController::pay_now() with a crafted POST, as the checkout form sends it.
use Tutor\Ecommerce\CheckoutController;
use Tutor\Models\OrderMetaModel;

ob_start(); // Keep headers_sent() false, as on a web request, so Tutor's redirect helper uses wp_safe_redirect().
$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;
function t( $name, $ok, $extra = '' ) {
	$ok ? $GLOBALS['pass']++ : $GLOBALS['fail']++;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . ( $extra ? '  [' . $extra . ']' : '' ) . "\n";
}
function order_count() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_orders" );
}

const WORDING = 'I want access to this course now. I understand that I lose my 14-day right to cancel once access starts, and that purchases are not refundable.';

$buyer  = wp_insert_user( array( 'user_login' => 'buyer', 'user_pass' => 'pw', 'user_email' => 'buyer@example.test', 'role' => 'subscriber' ) );
$course = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => 'Clouds', 'post_status' => 'publish' ) );
update_post_meta( $course, '_tutor_course_price_type', 'paid' );
update_post_meta( $course, 'tutor_course_price', 25 );
update_post_meta( $course, 'tutor_course_sale_price', '' );
wp_set_current_user( $buyer );
$_SERVER['REQUEST_METHOD'] = 'POST';

// Stop at the redirect Tutor ends a successful checkout with (it calls exit right after).
add_filter( 'wp_redirect', function ( $location ) {
	throw new Exception( 'redirect:' . $location );
} );

$post_base = function () use ( $course ) {
	return array(
		'_tutor_nonce' => wp_create_nonce( tutor()->nonce_action ), 'tutor_action' => 'tutor_pay_now', 'agree_to_terms' => '1',
		'object_ids' => (string) $course, 'order_type' => 'single_order', 'payment_method' => 'manual_test', 'payment_type' => 'manual',
		'billing_first_name' => 'A', 'billing_last_name' => 'B', 'billing_email' => 'buyer@example.test', 'billing_phone' => '123',
		'billing_zip_code' => 'AB1', 'billing_address' => '1 Road', 'billing_country' => 'GB', 'billing_state' => 'X', 'billing_city' => 'Y',
	);
};
// Returns [redirected_to or null, error list].
$checkout = function ( array $post, array $get = array() ) use ( $buyer ) {
	delete_transient( CheckoutController::PAY_NOW_ERROR_TRANSIENT_KEY . $buyer );
	$_POST = $post;
	$_REQUEST = $post;
	$_GET = $get;
	$redirect = null;
	try {
		( new CheckoutController( false ) )->pay_now();
	} catch ( Exception $e ) {
		$redirect = $e->getMessage();
	}
	$errors = get_transient( CheckoutController::PAY_NOW_ERROR_TRANSIENT_KEY . $buyer );
	return array( $redirect, is_array( $errors ) ? $errors : array() );
};

// 1. The form: one required checkbox, not pre-ticked, with the approved wording, above the button.
ob_start();
cas_checkout_render_refund_consent();
$html = ob_get_clean();
t( '1a box is a checkbox named cas_no_refund_consent, value 1', 1 === preg_match( '/<input[^>]*type="checkbox"[^>]*name="cas_no_refund_consent"[^>]*value="1"/', $html ) );
t( '1b box is required', 1 === preg_match( '/<input[^>]*\brequired\b/', $html ) );
t( '1c box is not pre-ticked', 0 === preg_match( '/\bchecked\b/', $html ) );
t( '1d wording is exactly the approved sentence', str_contains( html_entity_decode( wp_strip_all_tags( $html ) ), WORDING ) && WORDING === cas_checkout_refund_consent_text() );
$_POST = array( 'cas_no_refund_consent' => '1', 'tutor_action' => 'tutor_pay_now' );
ob_start();
cas_checkout_render_refund_consent();
t( '1e box is not ticked even after a posted tick (a refused try does not pre-tick)', 0 === preg_match( '/\bchecked\b/', ob_get_clean() ) );
$tpl = file_get_contents( '/theme-src/tutor/ecommerce/checkout.php' );
$at_box = strpos( $tpl, 'cas_checkout_render_refund_consent()' );
$at_btn = strpos( $tpl, 'id="tutor-checkout-pay-now-button"' );
$at_end = strpos( $tpl, '</form>' );
t( '1f the checkout template prints the box inside the form, above the place-order button', false !== $at_box && $at_box < $at_btn && $at_btn < $at_end );

// 2. No box: no order, a clear error, Tutor never gets as far as billing or an order.
$before = order_count();
list( $redirect, $errors ) = $checkout( $post_base() );
t( '2a no box: no order is created', order_count() === $before && null === $redirect, (string) $redirect );
t( '2b no box: the error is in the checkout error list', in_array( 'Please tick the box above to say you want access now and that purchases are not refundable.', $errors, true ), json_encode( $errors ) );
t( '2c no box: billing is not saved', null === $GLOBALS['wpdb']->get_var( "SELECT user_id FROM {$GLOBALS['wpdb']->prefix}tutor_customers WHERE user_id = {$buyer}" ) );
foreach ( array( '0', '', 'on', 'yes', 'true' ) as $v ) {
	$before = order_count();
	list( $redirect, $errors ) = $checkout( array_merge( $post_base(), array( 'cas_no_refund_consent' => $v ) ) );
	t( '2d value "' . $v . '" is refused', order_count() === $before && null === $redirect && $errors );
}
$before = order_count();
list( $redirect, $errors ) = $checkout( array_merge( $post_base(), array( 'cas_no_refund_consent' => array( '1' ) ) ) );
t( '2e an array value is refused', order_count() === $before && null === $redirect );
$before = order_count();
$only_consent = array_merge( $post_base(), array( 'cas_no_refund_consent' => '1' ) );
unset( $only_consent['agree_to_terms'] );
list( $redirect, $errors ) = $checkout( $only_consent );
t( '2f the site terms box is still required too (the consent box does not replace it)', order_count() === $before && null === $redirect && in_array( 'Please accept the checkout terms to continue.', $errors, true ), json_encode( $errors ) );

// 3. Box ticked: the order is created and carries the time (UTC) and the exact wording.
$t0 = time();
list( $redirect, $errors ) = $checkout( array_merge( $post_base(), array( 'cas_no_refund_consent' => '1' ) ) );
$oid = (int) $GLOBALS['wpdb']->get_var( "SELECT MAX(id) FROM {$GLOBALS['wpdb']->prefix}tutor_orders" );
t( '3a box ticked: one order is created and Tutor redirects to the success page', $oid > 0 && order_count() === $before + 1 && str_contains( (string) $redirect, 'tutor_order_placement=success' ), (string) $redirect );
$at   = OrderMetaModel::get_meta( $oid, '_cas_refund_consent_at' );
$at   = is_object( $at ) ? maybe_unserialize( $at->meta_value ) : $at;
$text = OrderMetaModel::get_meta( $oid, '_cas_refund_consent_text' );
$text = is_object( $text ) ? maybe_unserialize( $text->meta_value ) : $text;
t( '3b consent time is recorded in UTC (Y-m-d H:i:s) and is now', 1 === preg_match( '/^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$/', (string) $at ) && abs( strtotime( $at . ' UTC' ) - $t0 ) < 120, (string) $at );
t( '3c the exact wording is recorded', WORDING === $text, (string) $text );
t( '3d the order has exactly the two consent meta rows', 2 === (int) $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->prefix}tutor_ordermeta WHERE order_id = %d AND meta_key LIKE %s", $oid, '_cas_refund_consent%' ) ) );

// 4. An unpaid order being paid again (order_id in the URL): refused without the box, updated with it.
$order = ( new Tutor\Models\OrderModel() )->get_order_by_id( $oid );
( new Tutor\Models\OrderModel() )->update_order( $oid, array( 'order_status' => 'incomplete', 'payment_status' => 'unpaid' ) );
$before = order_count();
list( $redirect, $errors ) = $checkout( $post_base(), array( 'order_id' => $oid ) );
t( '4a retry of an unpaid order without the box is refused', null === $redirect && $errors && order_count() === $before );
OrderMetaModel::update_meta( $oid, '_cas_refund_consent_at', '2000-01-01 00:00:00' );
list( $redirect, $errors ) = $checkout( array_merge( $post_base(), array( 'cas_no_refund_consent' => '1' ) ), array( 'order_id' => $oid ) );
$at2 = OrderMetaModel::get_meta( $oid, '_cas_refund_consent_at' );
$at2 = is_object( $at2 ) ? maybe_unserialize( $at2->meta_value ) : $at2;
t( '4b retry with the box: the consent time is refreshed on the same order', order_count() === $before && '2000-01-01 00:00:00' !== $at2, (string) $at2 . ' ' . (string) $redirect );

// 5. Other requests are untouched.
$_POST = array();
t( '5a the purchase filter passes on a request that is not the pay-now submission', true === apply_filters( 'tutor_can_purchase_course', true, $course ) );
$_POST = array( 'tutor_action' => 'tutor_complete_lesson' );
t( '5b a different tutor_action passes', true === apply_filters( 'tutor_can_purchase_course', true, $course ) );
$_POST = array( 'tutor_action' => 'tutor_pay_now', 'agree_to_terms' => '1' );
$e = apply_filters( 'tutor_can_purchase_course', true, $course );
t( '5c the pay-now submission without the box gets a WP_Error', is_wp_error( $e ) && 'cas_refund_consent_required' === $e->get_error_code() );
$e0 = new WP_Error( 'other', 'Other' );
t( '5d an existing error from another check is kept', $e0 === apply_filters( 'tutor_can_purchase_course', $e0, $course ) );
$_POST = array( 'tutor_action' => 'tutor_pay_now', 'agree_to_terms' => '1', 'cas_no_refund_consent' => '1' );
t( '5e with the box the filter lets the purchase through', true === apply_filters( 'tutor_can_purchase_course', true, $course ) );

// 6. The second way to pay, an existing unpaid order: refused unless the order already carries the consent.
$run_incomplete = function ( $order_id ) {
	$_POST = array( 'tutor_action' => 'tutor_pay_incomplete_order', 'order_id' => $order_id );
	try {
		cas_checkout_guard_incomplete_order();
		return 'allowed';
	} catch ( Exception $e ) {
		return 'denied:' . $e->getMessage();
	}
};
$o_plain = ( new Tutor\Models\OrderModel() )->create_order( array(
	'user_id' => $buyer, 'order_type' => 'single_order', 'order_status' => 'incomplete', 'payment_status' => 'unpaid', 'subtotal_price' => 25, 'pre_tax_price' => 25, 'total_price' => 25, 'net_payment' => 25,
	'payment_method' => 'manual_test', 'created_at_gmt' => current_time( 'mysql', true ), 'created_by' => $buyer, 'updated_at_gmt' => current_time( 'mysql', true ), 'updated_by' => $buyer,
	'items' => array( array( 'item_id' => $course, 'regular_price' => 25, 'sale_price' => '', 'discount_price' => '' ) ),
) );
t( '6a an unpaid order with no consent on it is refused at the pay-incomplete entry point', str_starts_with( $run_incomplete( $o_plain ), 'redirect:' ) || str_starts_with( $run_incomplete( $o_plain ), 'denied:' ), $run_incomplete( $o_plain ) );
t( '6b an order that carries the consent may go on', 'allowed' === $run_incomplete( $oid ) );
t( '6c no order id is refused', 'allowed' !== $run_incomplete( 0 ) );

echo "\npass=" . $GLOBALS['pass'] . ' fail=' . $GLOBALS['fail'] . "\n";
echo "audit log:\n" . ( file_exists( '/tmp/audit.log' ) ? file_get_contents( '/tmp/audit.log' ) : '(none)' );
