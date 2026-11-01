<?php
// Standalone rendering cases only; never bootstrap WordPress or a provider here.
namespace Tutor\Ecommerce {
	class CartController { public function get_cart_items() { return $GLOBALS['cart_case']; } }
}
namespace {
	if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { throw new \RuntimeException( 'Standalone synthetic fixture only' ); }
	define( 'ABSPATH', __DIR__ );
	$root = sys_get_temp_dir() . '/cas-cart-parity-' . bin2hex( random_bytes( 6 ) ) . '/';
	mkdir( $root . 'templates/ecommerce', 0700, true );
	file_put_contents( $root . 'templates/ecommerce/cart.php', '<?php $course_list = $GLOBALS["cart_case"]["courses"]["results"] ?? null; $total_count = $GLOBALS["cart_case"]["courses"]["total_count"] ?? null; $GLOBALS["native_cart_renders"]++; echo $GLOBALS["native_markup"];' );
	function tutor() { return (object) array( 'path' => $GLOBALS['root'] ); }
	function cas_course_price( $amount ) { if ( $amount !== 0 ) { throw new \RuntimeException( 'Only the confirmed empty total may be rendered here' ); } return '£0'; }
	function cas_page_url( $slug ) { return '/courses/'; }
	function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
	function esc_url( $value ) { return htmlspecialchars( $value, ENT_QUOTES ); }
	$source = dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/tutor/ecommerce/cart.php';
	$cases = 0;
	try {
		foreach ( array(
			array( array( 'courses' => array( 'results' => array(), 'total_count' => 0 ) ), true ),
			array( array( 'courses' => array( 'results' => array( (object) array( 'ID' => 12 ) ), 'total_count' => 1 ) ), false ),
			array( array( 'courses' => array( 'results' => array(), 'total_count' => 1 ) ), false ),
			array( array(), false ),
		) as $case ) {
			$cart_case = $case[0]; $native_cart_renders = 0; $native_markup = 'NATIVE_CART_RENDERER'; if ( ! empty( $cart_case['courses']['results'] ) ) { $native_markup .= '<a data-cy="tutor-native-checkout-button" class="native-checkout" href="/checkout/">Native checkout</a>'; } ob_start(); include $source; $html = ob_get_clean();
			if ( $case[1] ) {
				if ( ! str_contains( $html, 'class="checkout-grid"' ) || ! str_contains( $html, 'Order summary' ) || ! str_contains( $html, 'disabled aria-disabled="true"' ) || ! str_contains( $html, 'NATIVE_CART_RENDERER' ) || str_contains( $html, 'no payment is taken' ) ) { throw new \RuntimeException( 'Empty basket structure or native mobile fallback missing' ); }
			} elseif ( ! empty( $cart_case['courses']['results'] ) ) {
				if ( ! str_contains( $html, $native_markup ) || substr_count( $html, 'Keep browsing' ) !== 1 || ! str_contains( $html, 'href="/courses/"' ) ) { throw new \RuntimeException( 'Native checkout action or prototype navigation link missing' ); }
			} elseif ( trim( $html ) !== 'NATIVE_CART_RENDERER' ) { throw new \RuntimeException( 'Unknown basket did not delegate untouched to Tutor' ); }
			if ( $native_cart_renders !== 1 ) { throw new \RuntimeException( 'Native cart must render exactly once' ); }
			++$cases;
		}
		echo json_encode( array( 'synthetic_cart_cases' => $cases, 'native_checkout_acceptance' => false ) ) . "\n";
	} finally {
		unlink( $root . 'templates/ecommerce/cart.php' ); rmdir( $root . 'templates/ecommerce' ); rmdir( $root . 'templates' ); rmdir( $root );
	}
}
