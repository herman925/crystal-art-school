<?php
/**
 * The cas_content role: Crystal's limited editor (issue #150, Route A, piece W1-1).
 *
 * manage_options is never granted to this role, and nothing here adds it to any role.
 * Theme CPTs and the Elementor library use the core 'post' capability type, so the role's
 * primitive caps (edit_posts and friends) cover every post type. The real limit is object
 * level: map_meta_cap refuses any post type that is not on the allowlist, and core already
 * refuses records the user did not author, because the role has no edit_others_* cap.
 */

const CAS_CONTENT_ROLE         = 'cas_content';
const CAS_CONTENT_CAP          = 'manage_cas_content';
const CAS_CONTENT_HUB_SLUG     = 'cas-content-hub';
const CAS_CONTENT_ROLE_VERSION = 1;

/** Post types the role may edit, own records only. Tutor courses and lessons join in W1-3. */
const CAS_CONTENT_POST_TYPES = array(
	'post',
	'attachment',
	'cas_artwork',
	'cas_showcase',
	'cas_free_video',
	'cas_about_photo',
	'cas_faq',
	'cas_testimonial',
	'cas_overview_qa',
);

/** Admin screens the role may open. Everything else is sent to the hub. */
const CAS_CONTENT_SCREENS = array(
	'index.php',
	'admin.php',
	'upload.php',
	'media.php',
	'profile.php',
	'edit.php',
	'post.php',
	'post-new.php',
	'async-upload.php',
	'admin-ajax.php',
	'admin-post.php',
);

/** Capabilities the role is granted. Nothing here is an admin power; see the contract test. */
function cas_content_role_caps() {
	return array(
		'read'                   => true,
		'upload_files'           => true,
		CAS_CONTENT_CAP          => true,
		'edit_posts'             => true,
		'edit_published_posts'   => true,
		'publish_posts'          => true,
		'delete_posts'           => true,
		'delete_published_posts' => true,
	);
}

/** True when the user's only role is cas_content (never an administrator). */
function cas_content_is_user( $user_id ) {
	$user = get_userdata( (int) $user_id );
	return $user && in_array( CAS_CONTENT_ROLE, (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true );
}

/** Create or refresh the role. Runs on init; the version option makes it a one-time write per change. */
function cas_content_role_install() {
	if ( (int) get_option( 'cas_content_role_version' ) === CAS_CONTENT_ROLE_VERSION && get_role( CAS_CONTENT_ROLE ) ) {
		return;
	}
	remove_role( CAS_CONTENT_ROLE );
	add_role( CAS_CONTENT_ROLE, __( 'Content editor', 'hello-elementor-child' ), cas_content_role_caps() );
	update_option( 'cas_content_role_version', CAS_CONTENT_ROLE_VERSION, false );
}
add_action( 'init', 'cas_content_role_install', 1 );

/**
 * Object-level gate for the role. Other users are not affected.
 * A post type outside the allowlist is denied for every object cap, which covers the
 * Elementor library and pages. Everything else keeps core's mapping (own records only).
 */
function cas_content_map_meta_cap( $caps, $cap, $user_id, $args ) {
	if ( ! cas_content_is_user( $user_id ) ) {
		return $caps;
	}
	$object_caps = array( 'edit_post', 'delete_post', 'read_post', 'publish_post' );
	if ( ! in_array( $cap, $object_caps, true ) ) {
		return $caps;
	}
	$post = isset( $args[0] ) ? get_post( (int) $args[0] ) : null;
	if ( ! $post || ! in_array( $post->post_type, CAS_CONTENT_POST_TYPES, true ) ) {
		return array( 'do_not_allow' );
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'cas_content_map_meta_cap', 10, 4 );

/**
 * Create is not an object cap, so map_meta_cap cannot stop it. This closes the create and edit
 * primitives for one request when that request targets a closed post type: a wp-admin screen
 * with a post_type off the allowlist, a REST route for such a type, or any Elementor AJAX or
 * admin-post action. Requests for allowed types are untouched.
 */
function cas_content_user_has_cap( $allcaps, $caps, $args, $user ) {
	$cap = isset( $args[0] ) ? $args[0] : '';
	if ( ! in_array( $cap, array( 'create_posts', 'edit_posts' ), true ) || ! cas_content_is_user( $user->ID ) ) {
		return $allcaps;
	}
	if ( cas_content_request_targets_closed_type() ) {
		$allcaps[ $cap ] = false;
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'cas_content_user_has_cap', 10, 4 );

/** True when this request aims at a post type that is not on the allowlist, or at Elementor. */
function cas_content_request_targets_closed_type() {
	$types = array();
	if ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$types[] = sanitize_key( wp_unslash( $_REQUEST['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( isset( $_REQUEST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( false !== strpos( $action, 'elementor' ) ) {
			return true;
		}
	}
	if ( preg_match( '#/wp-json/wp/v2/([a-z0-9_-]+)|[?&]rest_route=/wp/v2/([a-z0-9_-]+)#', isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', $m ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$segment = ! empty( $m[1] ) ? $m[1] : $m[2];
		foreach ( get_post_types( array(), 'objects' ) as $obj ) {
			if ( $segment === ( $obj->rest_base ? $obj->rest_base : $obj->name ) ) {
				$types[] = $obj->name;
			}
		}
	}
	foreach ( $types as $type ) {
		if ( ! in_array( $type, CAS_CONTENT_POST_TYPES, true ) ) {
			return true;
		}
	}
	return false;
}

/** The hub is where the role lands after login. */
function cas_content_login_redirect( $redirect_to, $requested, $user ) {
	if ( $user instanceof WP_User && cas_content_is_user( $user->ID ) ) {
		return admin_url( 'admin.php?page=' . CAS_CONTENT_HUB_SLUG );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'cas_content_login_redirect', 10, 3 );

/** Hide every admin menu the role does not use. Hiding is cosmetic; the screen guard below is the control. */
function cas_content_admin_menu() {
	if ( ! cas_content_is_user( get_current_user_id() ) ) {
		return;
	}
	global $menu;
	$keep = array( CAS_CONTENT_HUB_SLUG, 'upload.php', 'edit.php', 'profile.php' );
	foreach ( CAS_CONTENT_POST_TYPES as $type ) {
		if ( 'post' !== $type && 'attachment' !== $type ) {
			$keep[] = 'edit.php?post_type=' . $type;
		}
	}
	foreach ( (array) $menu as $item ) {
		if ( ! empty( $item[2] ) && ! in_array( $item[2], $keep, true ) ) {
			remove_menu_page( $item[2] );
		}
	}
}
add_action( 'admin_menu', 'cas_content_admin_menu', 999 );

/**
 * Screen guard: a direct request to a closed screen is sent to the hub. The role lacks the caps
 * these screens need anyway; this makes the refusal explicit and keeps the closed list visible.
 */
function cas_content_screen_guard() {
	if ( ! cas_content_is_user( get_current_user_id() ) ) {
		return;
	}
	global $pagenow;
	$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['post_type'] ) ) : '';  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $post_type && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$found = get_post( absint( $_GET['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_type = $found ? $found->post_type : '';
	}
	$screen_ok  = in_array( $pagenow, CAS_CONTENT_SCREENS, true );
	$type_ok    = ! $post_type || in_array( $post_type, CAS_CONTENT_POST_TYPES, true );
	$admin_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page_ok    = ! $admin_page || CAS_CONTENT_HUB_SLUG === $admin_page;
	if ( ! $screen_ok || ! $type_ok || ! $page_ok ) {
		wp_safe_redirect( admin_url( 'admin.php?page=' . CAS_CONTENT_HUB_SLUG ) );
		exit;
	}
}
add_action( 'admin_init', 'cas_content_screen_guard', 1 );
