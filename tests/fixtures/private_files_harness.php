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
function get_post_meta( $id, $key = '', $single = false ) { return $GLOBALS['cas_meta'][ $id ][ $key ] ?? ''; }
function get_attached_file( $id ) { return $GLOBALS['cas_files'][ $id ] ?? ''; }
function update_attached_file( $id, $file ) { $GLOBALS['cas_files'][ $id ] = $file; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['cas_meta'][ $id ][ $key ] = $value; return true; }
function get_post_type( $id ) { return isset( $GLOBALS['cas_files'][ $id ] ) ? 'attachment' : ''; }
function get_post_mime_type( $id ) { return 'application/pdf'; }
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
