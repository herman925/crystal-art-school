<?php
/** Desktop empty-basket parity. Populated baskets retain Tutor's unmodified renderer. */
defined( 'ABSPATH' ) || exit;

$cas_native_cart = tutor()->path . 'templates/ecommerce/cart.php';
ob_start();
require $cas_native_cart;
$cas_native_markup = ob_get_clean();
// Tutor has already resolved its authoritative list/count while rendering once.
if ( ! isset( $course_list, $total_count ) || ! is_array( $course_list ) || count( $course_list ) || (int) $total_count !== 0 ) {
	echo $cas_native_markup; // Native trusted template output.
	return;
}
$cas_zero = cas_course_price( 0 );
$cas_courses_url = cas_page_url( 'courses' );
?>
<div class="cas-basket-empty-desktop">
	<section data-stage="1" tabindex="-1">
		<div class="checkout-grid">
			<div><div class="cart-empty">
				<p style="font-family:var(--font-marker);font-weight:600;font-size:22px;color:var(--ink);margin:0 0 8px;">Your basket is empty</p>
				<p style="margin:0 0 18px;">Browse the courses and add one.</p>
				<a class="btn primary" href="<?php echo esc_url( $cas_courses_url ); ?>">See the courses &#8594;</a>
			</div></div>
			<aside class="summary" aria-label="Order summary">
				<h3>Order summary</h3>
				<div class="sum-row"><span>Subtotal</span><span><?php echo esc_html( $cas_zero ); ?></span></div>
				<div class="sum-total"><span class="l">Total</span><span class="v"><?php echo esc_html( $cas_zero ); ?></span></div>
				<button class="btn primary big full marine" type="button" disabled aria-disabled="true" style="margin-top:18px;opacity:.5;">Continue to details &#8594;</button>
				<a class="btn ghost full" href="<?php echo esc_url( $cas_courses_url ); ?>" style="margin-top:10px;">Keep browsing</a>
			</aside>
		</div>
	</section>
</div>
<div class="cas-basket-empty-legacy"><?php echo $cas_native_markup; // Native trusted template output. ?></div>
