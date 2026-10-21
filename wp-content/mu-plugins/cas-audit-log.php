<?php
/**
 * Audit log and admin-account alerts (incident 2026-09-21).
 *
 * Appends one line per security-relevant event to ~/cas-logs/audit.log (outside the web root).
 * Emails CAS_AUDIT_ALERT_TO when any user gains the administrator role, and once a day when the
 * administrator count is not CAS_AUDIT_EXPECTED_ADMINS.
 * Logs no passwords, cookies, nonces or request bodies.
 */
defined( 'ABSPATH' ) || exit;

const CAS_AUDIT_ALERT_TO        = 'herchan925@gmail.com';
const CAS_AUDIT_EXPECTED_ADMINS = 2; // cas_owner, cas_dev

/* Every field of a log line goes through here: valid UTF-8, no control or line-separator characters
   (\p{Cc}, U+2028, U+2029, U+0085), and '\' and '|' escaped so a value cannot forge a column.
   Not idempotent: escaping an already-escaped value doubles its backslashes. Call it once per raw field. */
function cas_audit_field( $value ) {
	$text = (string) $value;
	if ( ! preg_match( '//u', $text ) ) {
		$text = function_exists( 'mb_scrub' ) ? mb_scrub( $text, 'UTF-8' ) : (string) iconv( 'UTF-8', 'UTF-8//IGNORE', $text );
	}
	$text = preg_replace( '/[\p{Cc}\x{2028}\x{2029}\x{0085}]+/u', ' ', $text );
	if ( null === $text ) {
		return '';
	}
	return str_replace( array( '\\', '|' ), array( '\\\\', '\\|' ), $text );
}

function cas_audit_log( $event, $detail = '' ) {
	$dir = dirname( ABSPATH, 3 ) . '/cas-logs'; // public_html -> crystalau.com -> domains -> home
	$dir = is_dir( $dir ) ? $dir : dirname( ABSPATH, 4 ) . '/cas-logs';
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$ip = cas_audit_field( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '-' );
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip .= ' xff=' . cas_audit_field( trim( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] ) );
	}
	$uid  = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;
	$line = gmdate( 'Y-m-d H:i:s' ) . ' UTC | ' . $ip . ' | uid=' . $uid . ' | ' . cas_audit_field( $event ) . ' | ' . cas_audit_field( $detail ) . "\n";
	@file_put_contents( $dir . '/audit.log', $line, FILE_APPEND | LOCK_EX );
}

/* Read user rows with raw SQL only: never call get_user_meta/WP_User on an untrusted user here. */
function cas_audit_login_of( $user_id ) {
	global $wpdb;
	return (string) $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->users} WHERE ID = %d", $user_id ) );
}

function cas_audit_alert( $subject, $body ) {
	cas_audit_log( 'alert', $subject );
	wp_mail( CAS_AUDIT_ALERT_TO, '[crystalau.com] ' . $subject, $body . "\n\nTime: " . gmdate( 'Y-m-d H:i:s' ) . ' UTC' );
}

function cas_audit_admin_granted( $id, $role ) {
	if ( 'administrator' !== $role ) {
		return;
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '-';
	cas_audit_alert(
		'New administrator: ' . cas_audit_login_of( $id ),
		'User id ' . (int) $id . ' (' . cas_audit_login_of( $id ) . ') was given the administrator role.' .
		"\nActing user id: " . get_current_user_id() . "\nRequest IP: " . $ip .
		"\n\nIf you did not do this, treat the site as compromised."
	);
}

add_action( 'user_register', function ( $id ) { cas_audit_log( 'user_register', 'id=' . $id . ' login=' . cas_audit_login_of( $id ) ); } );
add_action( 'set_user_role', function ( $id, $role, $old ) {
	cas_audit_log( 'set_user_role', 'id=' . $id . ' role=' . $role . ' old=' . implode( ',', (array) $old ) );
	cas_audit_admin_granted( $id, $role );
}, 10, 3 );
add_action( 'add_user_role', function ( $id, $role ) {
	cas_audit_log( 'add_user_role', 'id=' . $id . ' role=' . $role );
	cas_audit_admin_granted( $id, $role );
}, 10, 2 );
add_action( 'remove_user_role', function ( $id, $role ) { cas_audit_log( 'remove_user_role', 'id=' . $id . ' role=' . $role ); }, 10, 2 );
add_action( 'delete_user', function ( $id ) { cas_audit_log( 'delete_user', 'id=' . $id . ' login=' . cas_audit_login_of( $id ) ); } );
add_action( 'wp_login', function ( $login, $user ) { cas_audit_log( 'wp_login', 'login=' . $login . ' id=' . (int) $user->ID ); }, 10, 2 );
add_action( 'wp_login_failed', function ( $login ) { cas_audit_log( 'wp_login_failed', 'login=' . substr( (string) $login, 0, 60 ) ); } );
add_action( 'after_password_reset', function ( $user ) { cas_audit_log( 'password_reset', 'id=' . (int) $user->ID ); } );
add_action( 'wp_create_application_password', function ( $uid ) { cas_audit_log( 'app_password_created', 'id=' . (int) $uid ); } );
add_action( 'activated_plugin', function ( $p ) { cas_audit_log( 'plugin_activated', $p ); } );
add_action( 'deactivated_plugin', function ( $p ) { cas_audit_log( 'plugin_deactivated', $p ); } );
add_action( 'deleted_plugin', function ( $p ) { cas_audit_log( 'plugin_deleted', $p ); } );
add_action( 'switch_theme', function ( $name ) { cas_audit_log( 'theme_switched', $name ); } );
add_action( 'upgrader_process_complete', function ( $up, $opts ) { cas_audit_log( 'upgrader', ( $opts['type'] ?? '?' ) . ':' . ( $opts['action'] ?? '?' ) ); }, 10, 2 );
foreach ( array( 'users_can_register', 'default_role', 'admin_email', 'active_plugins', 'siteurl', 'home' ) as $opt ) {
	add_action( 'update_option_' . $opt, function ( $old, $new ) use ( $opt ) {
		cas_audit_log( 'option_changed', $opt . ( is_scalar( $new ) ? '=' . $new : '' ) );
	}, 10, 2 );
}

/* Every admin-ajax action name (name only), except heartbeat. */
add_action( 'admin_init', function () {
	if ( wp_doing_ajax() && ! empty( $_REQUEST['action'] ) && 'heartbeat' !== $_REQUEST['action'] ) {
		cas_audit_log( 'ajax', substr( sanitize_key( $_REQUEST['action'] ), 0, 80 ) );
	}
}, 1 );

/* Send all site mail through the Hostinger mailbox. Credentials live in wp-config.php, never in git. */
if ( defined( 'CAS_SMTP_USER' ) && defined( 'CAS_SMTP_PASS' ) ) {
	add_action( 'phpmailer_init', function ( $mailer ) {
		$mailer->isSMTP();
		$mailer->Host       = 'smtp.hostinger.com';
		$mailer->Port       = 465;
		$mailer->SMTPSecure = 'ssl';
		$mailer->SMTPAuth   = true;
		$mailer->Username   = CAS_SMTP_USER;
		$mailer->Password   = CAS_SMTP_PASS;
	} );
	// Hostinger rejects a From that differs from the authenticated mailbox.
	add_filter( 'wp_mail_from', function () { return CAS_SMTP_USER; }, PHP_INT_MAX );
	add_filter( 'wp_mail_from_name', function () { return 'Crystal Art School'; }, PHP_INT_MAX );
}

/* Daily admin count. Raw SQL, so it also sees an administrator written straight into the DB. */
function cas_audit_admin_logins() {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare(
		"SELECT u.user_login FROM {$wpdb->users} u JOIN {$wpdb->usermeta} m ON m.user_id = u.ID WHERE m.meta_key = %s AND m.meta_value LIKE %s ORDER BY u.ID",
		$wpdb->prefix . 'capabilities',
		'%"administrator"%'
	) );
}

add_action( 'cas_audit_daily_admin_check', function () {
	$logins = cas_audit_admin_logins();
	cas_audit_log( 'admin_count', count( $logins ) . ' ' . implode( ',', $logins ) );
	if ( count( $logins ) !== CAS_AUDIT_EXPECTED_ADMINS ) {
		cas_audit_alert(
			'Administrator count is ' . count( $logins ) . ', expected ' . CAS_AUDIT_EXPECTED_ADMINS,
			"Administrator accounts now:\n" . implode( "\n", $logins )
		);
	}
} );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'cas_audit_daily_admin_check' ) ) {
		wp_schedule_event( time() + 300, 'daily', 'cas_audit_daily_admin_check' );
	}
} );
