<?php
/**
 * Checkout step: the prototype's wording and its stylesheet.
 *
 * The markup is tutor/ecommerce/checkout.php (a fork of Tutor's own, see its header) and
 * the layout rules are css/cas-checkout.css. This file supplies the two things that are
 * neither: the stylesheet's enqueue, and the words Tutor prints in the parts of the page it
 * renders itself — the order summary, which Tutor refreshes over AJAX when a coupon or a
 * country changes — so the heading and the total read as Checkout.dc.html writes them.
 *
 * Nothing here touches Tutor's form, nonce, fields or order handling.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the request is the checkout step, or Tutor's AJAX refresh of its order summary.
 *
 * The refresh is a separate request with no page in it, so the page test alone would let
 * the summary change its heading the first time a coupon is applied.
 *
 * @return bool
 */
function cas_checkout_is_step() {
	if ( wp_doing_ajax() ) {
		return isset( $_POST['action'] ) && 'tutor_get_checkout_html' === sanitize_key( wp_unslash( $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Tutor verifies its nonce before this template renders.
	}

	return 'checkout' === cas_account_current_key();
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( 'checkout' !== cas_account_current_key() ) {
			return;
		}

		$file = get_stylesheet_directory() . '/css/cas-checkout.css';

		wp_enqueue_style( 'cas-checkout', get_stylesheet_directory_uri() . '/css/cas-checkout.css', array( 'cas-industrial-3' ), (string) filemtime( $file ) );
	},
	29
);

/**
 * The prototype's words for what Tutor prints itself. Only these three strings, only on
 * the checkout step, and only from Tutor's own text domain.
 */
add_filter(
	'gettext',
	function ( $translation, $text, $domain ) {
		if ( 'tutor' !== $domain || ! cas_checkout_is_step() ) {
			return $translation;
		}

		$words = array(
			'Order Details' => 'Order summary',
			'Grand Total'   => 'Total',
		);

		return $words[ $text ] ?? $translation;
	},
	10,
	3
);

/**
 * The prototype's button says "Place order" at every price. Tutor says "Pay Now", or
 * "Place Order" for a free basket; the arrow is drawn in the template.
 */
add_filter(
	'tutor_checkout_pay_now_btn_text',
	function () {
		return __( 'Place order', 'hello-elementor-child' );
	}
);

/* -------------------------------------------------------------------------
 * The no-refund consent (issue #138)
 * ---------------------------------------------------------------------- */

/**
 * The consent sentence, word for word as the owner approved it on 2026-09-30.
 *
 * A buyer of digital content keeps a 14-day right to cancel unless they ask for access at once and
 * accept that the right ends. The site sells on that basis and offers no refunds. This is the one
 * place the wording lives: the page prints it, and the order stores this exact string. It is not
 * legal advice, and the owner is advised to confirm the wording with an adviser.
 *
 * @return string
 */
function cas_checkout_refund_consent_text() {
	return 'I want access to this course now. I understand that I lose my 14-day right to cancel once access starts, and that purchases are not refundable.';
}

/**
 * Whether the checkout POST carries the consent box ticked.
 *
 * @return bool
 */
function cas_checkout_refund_consent_posted() {
	if ( ! isset( $_POST['cas_no_refund_consent'] ) || ! is_scalar( $_POST['cas_no_refund_consent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Tutor verifies its nonce before its order handler runs.
		return false;
	}

	return '1' === (string) wp_unslash( $_POST['cas_no_refund_consent'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Refuse the checkout when the box is not ticked, before Tutor saves billing data or creates an order.
 *
 * Tutor's `pay_now()` (CheckoutController.php) asks `tutor_can_purchase_course` for every item in a
 * single-course order, after it has verified its nonce and before it writes the billing record or
 * calls the order controller. An error here is put in Tutor's pay-now error list and printed in the
 * checkout page, next to the button. The filter runs on other requests too (for example a course
 * page), so it acts only on the pay-now submission. It refuses every item, so it cannot be let through by
 * a later one.
 *
 * @param bool|WP_Error $can_buy Existing Tutor purchase result.
 * @return bool|WP_Error
 */
function cas_checkout_require_refund_consent( $can_buy ) {
	if ( ! cas_account_checkout_submission() || cas_checkout_refund_consent_posted() || is_wp_error( $can_buy ) ) {
		return $can_buy;
	}

	return new WP_Error(
		'cas_refund_consent_required',
		__( 'Please tick the box above to say you want access now and that purchases are not refundable.', 'hello-elementor-child' )
	);
}
add_filter( 'tutor_can_purchase_course', 'cas_checkout_require_refund_consent', 20 );

/**
 * Write the consent on the order: the time (UTC) and the exact wording shown.
 *
 * `tutor_order_placed` (new order) and `tutor_order_updated` (an unpaid order being paid again) both
 * fire inside the pay-now request with the order id, after the consent check above has passed.
 * This is the evidence if a buyer disputes the charge. The order is already created, so a failure
 * here is logged and never stops it.
 *
 * @param array|object|int $order Order data with an id.
 */
function cas_checkout_store_refund_consent( $order ) {
	if ( ! cas_account_checkout_submission() || ! cas_checkout_refund_consent_posted() ) {
		return;
	}

	$order_id = is_array( $order ) && isset( $order['id'] ) ? absint( $order['id'] ) : ( is_object( $order ) && isset( $order->id ) ? absint( $order->id ) : absint( $order ) );
	if ( ! $order_id || ! class_exists( '\Tutor\Models\OrderMetaModel' ) ) {
		return;
	}

	try {
		\Tutor\Models\OrderMetaModel::update_meta( $order_id, '_cas_refund_consent_at', current_time( 'mysql', true ) );
		\Tutor\Models\OrderMetaModel::update_meta( $order_id, '_cas_refund_consent_text', cas_checkout_refund_consent_text() );
	} catch ( \Throwable $exception ) {
		if ( function_exists( 'cas_audit_log' ) ) {
			cas_audit_log( 'refund_consent_not_stored', 'order=' . $order_id );
		}
	}
}
add_action( 'tutor_order_placed', 'cas_checkout_store_refund_consent' );
add_action( 'tutor_order_updated', 'cas_checkout_store_refund_consent' );

/**
 * Print the consent box, above the place-order button. Never ticked, not even after a refused try.
 *
 * Called from tutor/ecommerce/checkout.php inside the order-summary card, outside the element Tutor
 * refreshes when a coupon or country changes.
 */
function cas_checkout_render_refund_consent() {
	?>
	<div class="cas-refund-consent">
		<label for="cas-refund-consent">
			<input type="checkbox" id="cas-refund-consent" name="cas_no_refund_consent" value="1" required>
			<span><?php echo esc_html( cas_checkout_refund_consent_text() ); ?></span>
		</label>
	</div>
	<?php
}

/**
 * The second way to start a payment: paying an order that was left unpaid.
 *
 * Tutor's `pay_incomplete_order()` (CheckoutController.php, the "pay" button in the order history) sends
 * the buyer to the payment gateway for an existing order and never asks `tutor_can_purchase_course`,
 * so the checks above do not see it. An order made through this checkout already carries the consent
 * (written when it was placed) and may go on. An order with no consent on it, for example one made
 * before this existed or by hand, is refused, and the buyer is sent back to start the purchase again
 * from the course page. This runs before Tutor's own handler (priority 10).
 */
function cas_checkout_guard_incomplete_order() {
	$order_id = isset( $_POST['order_id'] ) && is_scalar( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- this only refuses; Tutor verifies its nonce before it pays anything.

	if ( $order_id && class_exists( '\Tutor\Models\OrderMetaModel' ) && \Tutor\Models\OrderMetaModel::get_meta( $order_id, '_cas_refund_consent_at' ) ) {
		return;
	}

	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( 'action_denied', 'pay_incomplete_order without consent order=' . $order_id );
	}

	tutor_utils()->redirect_to(
		tutor_utils()->tutor_dashboard_url( 'purchase_history' ),
		__( 'This order cannot be paid from here. Please start your purchase again from the course page.', 'hello-elementor-child' ),
		'error'
	);
	exit;
}
add_action( 'tutor_action_tutor_pay_incomplete_order', 'cas_checkout_guard_incomplete_order', 1 );
