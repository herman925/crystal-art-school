<?php
/**
 * Keep Tutor's membership-on notice out of wp-admin and block its one-click Enable URL.
 *
 * Tutor 4.1.3 registers TUTOR\User::hide_notices() on admin_init at priority 10. Its
 * ?tutor-hide-notice=registration&tutor-registration=enable branch updates users_can_register
 * without checking a nonce. The notice is removed at priority 1; the enable URL is denied at
 * priority 0, before Tutor runs. Settings > General remains the intentional registration switch.
 */

defined( 'ABSPATH' ) || exit;

/** Deny Tutor's one-click registration-enable URL before TUTOR\User::hide_notices runs. */
function cas_signup_lock_deny_tutor_enable_link() {
	if ( ! is_admin() ) {
		return;
	}
	if ( 'registration' !== ( isset( $_GET['tutor-hide-notice'] ) ? sanitize_key( wp_unslash( $_GET['tutor-hide-notice'] ) ) : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( 'enable' !== ( isset( $_GET['tutor-registration'] ) ? sanitize_key( wp_unslash( $_GET['tutor-registration'] ) ) : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	status_header( 403 );
	nocache_headers();
	wp_die(
		esc_html__( 'Registration can only be changed in Settings > General.', 'hello-elementor-child' ),
		esc_html__( 'Registration setting protected', 'hello-elementor-child' ),
		array( 'response' => 403 )
	);
}
add_action( 'admin_init', 'cas_signup_lock_deny_tutor_enable_link', 0 );

/** Remove Tutor's notice callback by class and method, without hiding unrelated admin notices. */
function cas_signup_lock_remove_tutor_notice() {
	global $wp_filter;
	$hook = isset( $wp_filter['admin_notices'] ) ? $wp_filter['admin_notices'] : null;
	if ( ! $hook instanceof WP_Hook ) {
		return;
	}
	foreach ( $hook->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$function = isset( $callback['function'] ) ? $callback['function'] : null;
			if ( is_array( $function ) && is_object( $function[0] ) && is_a( $function[0], 'TUTOR\\User' ) && 'show_registration_disabled' === $function[1] ) {
				remove_action( 'admin_notices', $function, $priority );
			}
		}
	}
}
add_action( 'admin_init', 'cas_signup_lock_remove_tutor_notice', 1 );
