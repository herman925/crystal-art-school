<?php
/**
 * Native Tutor order-placement success screen in the Crystal Art School shell.
 *
 * Tutor's PaymentHandler has already completed its native checkout flow before
 * loading this template. Keep the plugin's order id/status contract intact and
 * let the child theme provide presentation only.
 */

defined( 'ABSPATH' ) || exit;

tutor_utils()->tutor_custom_header();
?>
<main id="content" class="site-main cas-account-page cas-account-checkout-success">
	<?php echo cas_account_render_checkout_success( $order_id, $order_status ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</main>
<?php
tutor_utils()->tutor_custom_footer();
