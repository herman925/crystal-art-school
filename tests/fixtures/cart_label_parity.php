<?php
// Pure label callbacks only; no WordPress bootstrap, accounts or cart writes.
if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Standalone fixture only' ); }
define( 'ABSPATH', __DIR__ );
$filters = array();
function add_filter( $hook, $callback, ...$args ) { $GLOBALS['filters'][ $hook ][] = $callback; }
function add_action( ...$args ) {}
function cas_account_current_key() { return $GLOBALS['key']; }
function wp_doing_ajax() { return $GLOBALS['ajax']; }
function wp_unslash( $value ) { return $value; }
function sanitize_key( $value ) { return is_scalar( $value ) ? preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ) : ''; }
require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-checkout.php';
$cases = 0;
foreach ( array(
	array( 'cart', false, '', true ),
	array( '', true, 'tutor_delete_course_from_cart', true ),
	array( '', true, 'other_action', false ),
	array( '', true, array( 'tutor_delete_course_from_cart' ), false ),
	array( 'checkout', false, '', false ),
) as $case ) {
	[ $key, $ajax, $action, $is_cart ] = $case;
	$_POST = array( 'action' => $action );
	foreach ( array( 'Summary:' => 'Order summary', 'Proceed to checkout' => 'Continue to details →', 'Grand total' => 'Total' ) as $text => $expected ) {
		foreach ( array( 'tutor', 'other-domain' ) as $domain ) {
			$value = 'ORIGINAL';
			foreach ( $filters['gettext'] as $filter ) { $value = $filter( $value, $text, $domain ); }
			if ( $value !== ( $is_cart && 'tutor' === $domain ? $expected : 'ORIGINAL' ) ) { throw new RuntimeException( 'Cart label escaped its request/domain scope' ); }
			++$cases;
		}
	}
}
echo json_encode( array( 'synthetic_label_cases' => $cases ) ) . "\n";
