<?php
/**
 * Customizer access for the cas_content role (issue #150, Route A, piece W1-2).
 *
 * Must be a mu-plugin: core checks 'customize' at setup_theme, which runs before the theme's
 * functions.php, so a mapping in the theme is not yet registered at that point. This mapping
 * applies to the cas_content role only. Administrators keep core's mapping.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'map_meta_cap',
	function ( $caps, $cap, $user_id ) {
		if ( 'customize' !== $cap ) {
			return $caps;
		}
		$user = get_userdata( (int) $user_id );
		if ( ! $user || ! in_array( 'cas_content', (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true ) ) {
			return $caps;
		}
		$role = get_role( 'cas_content' );
		if ( ! $role || empty( $role->capabilities['manage_cas_content'] ) ) {
			return $caps;
		}
		return array( 'manage_cas_content' );
	},
	5,
	3
);
