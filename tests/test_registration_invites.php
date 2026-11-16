<?php
/** Single-use invitation state and alternate-entrypoint checks, without WordPress/network. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
const ARRAY_A = 'ARRAY_A';
$options = array( 'cas_invite_registration_enabled' => '1' );
$rows = array(); $transaction_before = null; $scope = 'register'; $admin = false; $signed_in = false; $native = true; $checks = 0;
function add_filter( ...$args ) {}
function add_action( ...$args ) {}
function get_option( $key, $default = false ) { return $GLOBALS['options'][$key] ?? $default; }
function is_admin() { return $GLOBALS['admin']; }
function is_user_logged_in() { return $GLOBALS['signed_in']; }
function doing_action( $name ) { return 'template_redirect' === $name && $GLOBALS['native']; }
function tutor() { return (object) array( 'nonce' => '_tutor_nonce', 'nonce_action' => 'tutor_action' ); }
function wp_verify_nonce( $value, $action ) { return 'valid-nonce' === $value && 'tutor_action' === $action; }
function cas_account_current_key() { return $GLOBALS['scope']; }
function wp_unslash( $x ) { return $x; }
function wp_salt( $x ) { return 'synthetic-salt'; }
function wp_json_encode( $x ) { return json_encode( $x ); }
function wp_cache_delete( ...$args ) {}
function get_userdata( $id ) { return (object) array( 'ID' => $id, 'roles' => array( 'subscriber' ) ); }
function clean_user_cache( $id ) {}
function wp_die( $message, ...$args ) { throw new RuntimeException( $message ); }
class WP_Error { public $errors = array(); function add( $key, $message ) { $this->errors[$key] = $message; } }
class InviteDB {
	public $options = 'wp_options';
	function prepare( $query, ...$args ) { return array( $query, $args ); }
	function get_var( $query ) { return $GLOBALS['rows'][$query[1][0]] ?? null; }
	function query( $query ) {
		if ( 'ROLLBACK' === $query ) { if ( null !== $GLOBALS['transaction_before'] ) { $GLOBALS['rows'] = $GLOBALS['transaction_before']; $GLOBALS['transaction_before'] = null; } return 0; }
		list( $after, $key, $before ) = $query[1];
		if ( ( $GLOBALS['rows'][$key] ?? null ) !== $before ) { return 0; }
		$GLOBALS['rows'][$key] = $after; return 1;
	}
}
$wpdb = new InviteDB();
require __DIR__ . '/../wp-content/themes/hello-elementor-child/inc/cas-registration-invites.php';
function need( $condition, $message ) { $GLOBALS['checks']++; if ( ! $condition ) { throw new RuntimeException( $message ); } }
function fixture( $state = 'ready', $expires = null ) {
	$code = 'CAS-' . implode( '-', str_split( strtoupper( bin2hex( random_bytes( 12 ) ) ), 6 ) );
	$key = cas_invite_option_key( $code );
	$GLOBALS['rows'][$key] = wp_json_encode( array( 'state' => $state, 'expires' => $expires ?? time() + 60 ) );
	return array( $code, $key );
}
function post_code( $code ) { $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST = array( 'tutor_action' => 'tutor_register_student', '_tutor_nonce' => 'valid-nonce', 'cas_invite_code' => $code ); cas_invite_context( array() ); }
function denied( $code ) { post_code( $code ); return ! empty( cas_invite_registration_errors( new WP_Error() )->errors ); }
need( cas_account_registration_available(), 'Invite mode exposes signup link with global registration closed.' );
$_SERVER['REQUEST_METHOD'] = 'GET'; $_POST = array();
need( 1 === cas_invites_tutor_registration_option( false ), 'Tutor form is available.' );
$scope = 'login'; need( false === cas_invites_tutor_registration_option( false ), 'Core/public option remains closed.' );
$_SERVER['REQUEST_METHOD'] = 'POST'; $_POST['tutor_action'] = 'tutor_register_instructor';
need( false === cas_invites_tutor_registration_option( false ), 'Instructor registration not enabled.' );
need( denied( '' ), 'Missing code denied.' );
need( denied( array( 'bad' ) ), 'Array input denied.' );
need( denied( str_repeat( 'A', 100 ) ), 'Oversized input denied.' );
need( denied( 'CAS-AAAAAA-BBBBBB-CCCCCC-DDDDDD' ), 'Unknown code denied.' );
foreach ( array( 'used', 'revoked', 'reserved' ) as $state ) { list( $code ) = fixture( $state ); need( denied( $code ), "$state code denied." ); }
list( $code ) = fixture( 'ready', time() - 1 ); need( denied( $code ), 'Expired code denied.' );
list( $code, $key ) = fixture(); post_code( $code ); $_POST['_tutor_nonce'] = 'wrong';
need( ! empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Invalid nonce denied.' );
need( 'ready' === cas_invite_read( $key )['data']['state'], 'Invalid nonce never reserves a code.' );
post_code( $code ); $native = false; need( ! empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Core or alternate registration cannot use invite.' ); $native = true;
post_code( $code ); $admin = true; need( ! empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Admin page is not student registration.' ); $admin = false;
post_code( $code ); $signed_in = true; need( ! empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Signed-in creation is not invite registration.' ); $signed_in = false;
post_code( strtolower( $code ) );
need( empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Valid normalized code accepted.' );
$context = cas_invite_context(); need( 'reserved' === cas_invite_read( $key )['data']['state'], 'Code atomically reserved.' );
need( 'subscriber' === cas_invite_student_role( false ), 'Invited user always starts as subscriber.' );
post_code( $code ); need( ! empty( cas_invite_registration_errors( new WP_Error() )->errors ), 'Concurrent second reservation denied.' );
cas_invite_context( $context ); cas_invite_release_failed_registration();
need( 'ready' === cas_invite_read( $key )['data']['state'], 'Validation failure releases reservation.' );
post_code( $code ); cas_invite_registration_errors( new WP_Error() ); $transaction_before = $rows;
cas_invite_user_created( 123 );
need( 'used' === cas_invite_read( $key )['data']['state'], 'Native user creation consumes code.' );
need( 123 === cas_invite_read( $key )['data']['user'], 'Invite bound to actual new user ID.' );
cas_invite_release_failed_registration();
need( 'ready' === cas_invite_read( $key )['data']['state'], 'Native verification rollback restores usable code.' );
post_code( $code ); cas_invite_registration_errors( new WP_Error() ); $transaction_before = $rows;
cas_invite_user_created( 124 ); $transaction_before = null; cas_invite_registration_finished(); cas_invite_release_failed_registration();
need( 'used' === cas_invite_read( $key )['data']['state'], 'Successful commit stays used at shutdown.' );
need( denied( $code ), 'Successful code cannot be replayed.' );
list( $code, $key ) = fixture(); post_code( $code ); cas_invite_registration_errors( new WP_Error() );
$reserved = cas_invite_read( $key ); $data = $reserved['data']; $data['state'] = 'revoked';
cas_invite_replace( $key, $reserved['raw'], wp_json_encode( $data ) ); cas_invite_release_failed_registration();
need( 'revoked' === cas_invite_read( $key )['data']['state'], 'Failed registration never undoes administrator revocation.' );
need( false === cas_invite_replace( $key, $reserved['raw'], '{}' ), 'Stale writers cannot replace a successor row.' );
$options['cas_invite_registration_enabled'] = '0'; cas_invite_context( array() );
need( false === cas_account_registration_available(), 'Disabled mode restores global closed state.' );
need( false === cas_invites_tutor_registration_option( false ), 'Disabled mode does not enable Tutor.' );
need( false === cas_invite_student_role( false ), 'Manual/admin account role unchanged.' );
echo "Registration invitations: $checks checks passed.\n";
