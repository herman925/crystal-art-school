<?php
/** Standalone regression check: no WordPress bootstrap, accounts or database. */
define( 'ABSPATH', __DIR__ );
function add_action( $hook, $callback, ...$args ) { $GLOBALS['actions'][ $hook ][] = $callback; }
function add_filter( ...$args ) {}
function get_userdata( $id ) { return in_array( $id, array( 11, 12, 13 ), true ) ? (object) array( 'ID' => $id ) : false; }
function get_user_meta( $id, $key, $single ) { return $GLOBALS['choices'][ $id ] ?? ''; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['owners'][ $id ] ?? 0; }
function get_option( $key, $default ) { return $GLOBALS['default'] ?? $default; }
define( 'CAS_SHOWCASE_META_USER', '_cas_showcase_user_id' );
require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-privacy.php';
function check( $actual, $expected, $message ) {
	if ( $actual !== $expected ) { throw new RuntimeException( $message ); }
}
$GLOBALS['choices'] = array( 11 => '0', 12 => '1' );
$GLOBALS['owners'] = array( 101 => 11, 102 => 12, 103 => 13, 104 => 99 );
foreach ( array( '0', '1' ) as $default ) {
	$GLOBALS['default'] = $default;
	check( cas_showcase_sharing_enabled( 11 ), false, 'Explicit OFF must survive a default change.' );
	check( cas_showcase_sharing_enabled( 12 ), true, 'Explicit ON must survive a default change.' );
	check( cas_showcase_sharing_enabled( 13 ), '1' === $default, 'Unanswered student uses current default.' );
	check( cas_showcase_piece_visible( 101 ), false, 'Opted-out student work must be excluded.' );
	check( cas_showcase_piece_visible( 102 ), true, 'Linked opted-in work remains eligible.' );
	check( cas_showcase_piece_visible( 104 ), false, 'Deleted or unknown student cannot bypass preferences.' );
	check( cas_showcase_piece_visible( 105 ), false, 'Unlinked work cannot bypass preferences.' );
}
unset( $GLOBALS['default'] );
check( cas_showcase_sharing_enabled( 13 ), true, 'Initial site default is ON.' );
check( $GLOBALS['choices'], array( 11 => '0', 12 => '1' ), 'Reading defaults must never backfill or fabricate consent.' );
echo "PASS: Showcase default, explicit choice, ownership and non-mutation checks\n";

// A failed write must never redirect to a misleading “saved” confirmation.
function get_current_user_id() { return 11; }
function wp_unslash( $value ) { return $value; }
function sanitize_text_field( $value ) { return $value; }
function wp_verify_nonce( ...$args ) { return true; }
function update_user_meta( ...$args ) { return false; }
function wp_die( $message, $title, $args ) { throw new RuntimeException( 'HTTP ' . $args['response'] ); }
$_POST = array( 'cas_showcase_preference_nonce' => 'synthetic-nonce', 'cas_showcase_sharing' => '1' );
try {
	$GLOBALS['actions']['admin_post_cas_save_showcase_preference'][0]();
	throw new RuntimeException( 'Failed storage must not claim success.' );
} catch ( RuntimeException $error ) {
	check( $error->getMessage(), 'HTTP 500', 'Failed storage must report a retryable error.' );
}
echo "PASS: failed preference storage cannot claim successful withdrawal or consent\n";
