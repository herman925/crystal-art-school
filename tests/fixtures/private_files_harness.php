<?php
/*
 * Runs one scenario of the private-files module with WordPress stubbed out, so the delete,
 * permission and audit behaviour can be tested without the shared local WordPress site.
 * Driven by tests/test_private_files_followup.py. The scenario file is JSON; the result is
 * written to the path in scenario['result'] at shutdown.
 */
$s = json_decode( file_get_contents( $argv[1] ), true );

define( 'ABSPATH', $s['abspath'] );
define( 'CAS_PRIVATE_DIR', $s['private_dir'] );

$GLOBALS['cas_meta']   = $s['meta'];
$GLOBALS['cas_files']  = $s['attached'];
$GLOBALS['cas_user']   = $s['user'];
$GLOBALS['cas_hooks']  = array();
$GLOBALS['cas_status'] = 200;

function add_action( $hook, $cb, $priority = 10, $args = 1 ) { $GLOBALS['cas_hooks'][ $hook ][] = $cb; }
function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {}
function current_filter() { return $GLOBALS['cas_test_filter'] ?? ''; }
function wp_die( $message, $title = '', $args = array() ) { status_header( $args['response'] ?? 500 ); exit; }
function get_post_meta( $id, $key = '', $single = false ) { return $GLOBALS['cas_meta'][ $id ][ $key ] ?? ''; }
function get_attached_file( $id ) { return $GLOBALS['cas_files'][ $id ] ?? ''; }
function update_attached_file( $id, $file ) { $GLOBALS['cas_files'][ $id ] = $file; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['cas_meta'][ $id ][ $key ] = $value; return true; }
function get_post_type( $id ) { return isset( $GLOBALS['cas_files'][ $id ] ) ? 'attachment' : ''; }
function get_post_mime_type( $id ) { return 'application/pdf'; }
function wp_ext2type( $ext ) { return array( 'pdf' => 'document', 'jpg' => 'image', 'png' => 'image' )[ $ext ] ?? false; }
function wp_get_attachment_metadata( $id ) { return $GLOBALS['cas_meta'][ $id ]['_wp_attachment_metadata'] ?? array(); }
function wp_delete_file( $file ) { if ( is_file( $file ) || is_link( $file ) ) { unlink( $file ); } }
function get_current_user_id() { return (int) ( $GLOBALS['cas_user']['id'] ?? 0 ); }
function is_user_logged_in() { return ! empty( $GLOBALS['cas_user']['logged_in'] ); }
function current_user_can( $cap ) { return ! empty( $GLOBALS['cas_user']['admin'] ); }
function status_header( $code ) { $GLOBALS['cas_status'] = $code; }
function nocache_headers() {}
function home_url( $path = '' ) { return 'http://example.test' . $path; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_unslash( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $key ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $key ) ); }
function untrailingslashit( $value ) { return rtrim( $value, '/\\' ); }
function wp_mkdir_p( $dir ) { return is_dir( $dir ) || mkdir( $dir, 0755, true ); }

function wp_attachment_is_image( $id ) { return in_array( strtolower( pathinfo( get_attached_file( $id ), PATHINFO_EXTENSION ) ), array( 'png', 'jpg', 'jpeg' ), true ); }
function wp_get_attachment_url( $id ) { return home_url( '/uploads/' . basename( get_attached_file( $id ) ) ); }
function get_theme_mods() { return $GLOBALS['cas_s']['mods'] ?? array(); }
class CasPrivateFixtureDB {
	public $posts = 'posts'; public $postmeta = 'postmeta';
	function prepare( $sql, ...$args ) { return $sql; }
	function get_var( $sql ) { return ! empty( $GLOBALS['cas_s']['public_meta'] ) ? 1 : null; }
	function get_col( $sql ) { return $GLOBALS['cas_s']['contents'] ?? array(); }
}
$GLOBALS['cas_s'] = $s;
$GLOBALS['wpdb'] = new CasPrivateFixtureDB();

$GLOBALS['cas_result'] = array( 'status' => null, 'delete_hooked' => false );
register_shutdown_function(
	function () {
		$s = json_decode( file_get_contents( $GLOBALS['cas_scenario'] ), true );
		$GLOBALS['cas_result']['status']        = $GLOBALS['cas_status'];
		$GLOBALS['cas_result']['delete_hooked'] = ! empty( $GLOBALS['cas_hooks']['delete_attachment'] );
		file_put_contents( $s['result'], json_encode( $GLOBALS['cas_result'] ) );
	}
);
$GLOBALS['cas_scenario'] = $argv[1];

require $s['module'];
require $s['audit_module'];

switch ( $s['action'] ) {
	case 'public_use':
		$GLOBALS['cas_result']['public_use'] = cas_private_public_image_use( $s['id'] );
		break;
	case 'guard':
		$GLOBALS['cas_test_filter'] = $s['filter'];
		$GLOBALS['cas_result']['guard'] = cas_private_guard_metadata( null, $s['post_id'], $s['key'], $s['value'] );
		break;
	case 'display':
		$GLOBALS['cas_result']['display'] = cas_private_tutor_attachment_data( $s['display'] );
		break;
	case 'delete':
		if ( ! empty( $GLOBALS['cas_hooks']['delete_attachment'] ) ) {
			foreach ( $GLOBALS['cas_hooks']['delete_attachment'] as $cb ) {
				$cb( $s['id'], null );
			}
		}
		break;
	case 'move':
		cas_private_move_attachment( $s['id'] );
		break;
	case 'redirect':
		$_GET = $s['get'];
		foreach ( $GLOBALS['cas_hooks']['template_redirect'] as $cb ) {
			$cb();
		}
		break;
}
