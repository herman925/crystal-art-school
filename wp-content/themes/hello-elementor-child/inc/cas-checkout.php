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
