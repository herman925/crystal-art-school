<?php
/** One public course selection and Medium vocabulary for navigation and filters. */
defined( 'ABSPATH' ) || exit;

function cas_public_course_ids() {
	static $ids;
	if ( null === $ids ) {
		$ids = get_posts( array( 'post_type' => 'courses', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'menu_order date', 'order' => 'DESC', 'suppress_filters' => false ) );
	}
	return $ids;
}

function cas_course_medium_terms() {
	static $options;
	if ( null === $options ) {
		$options = array();
		$ids = cas_public_course_ids();
		$terms = $ids ? wp_get_object_terms( $ids, 'course-category', array( 'orderby' => 'name', 'order' => 'ASC' ) ) : array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) { $options[ $term->slug ] = $term->name; }
			natcasesort( $options );
		}
	}
	return $options;
}

/** Change labels only; native taxonomy IDs and saved tags remain intact. */
add_action( 'init', function () {
	$taxonomy = get_taxonomy( 'course-category' );
	if ( $taxonomy ) {
		foreach ( array( 'name' => 'Medium', 'singular_name' => 'Medium', 'menu_name' => 'Medium', 'add_new_item' => 'Add Medium', 'edit_item' => 'Edit Medium', 'search_items' => 'Search Medium', 'all_items' => 'All Media', 'parent_item' => 'Parent Medium', 'new_item_name' => 'New Medium' ) as $key => $label ) { $taxonomy->labels->$key = $label; }
	}
}, 100 );

add_filter( 'tutor_localize_data', function ( $data ) {
	if ( current_user_can( 'manage_cas_content' ) ) {
		if ( ! is_array( $data['visibility_control'] ?? null ) ) { $data['visibility_control'] = array(); }
		if ( ! is_array( $data['visibility_control']['course_builder'] ?? null ) ) { $data['visibility_control']['course_builder'] = array(); }
		foreach ( array( 'admin', 'instructor' ) as $role ) { $data['visibility_control']['course_builder'][ 'basics_tags_' . $role ] = 'off'; }
		if ( function_exists( 'cas_content_is_user' ) && cas_content_is_user( get_current_user_id() ) ) {
			foreach ( array( 'admin', 'instructor' ) as $role ) {
				foreach ( array( 'general', 'content_drip', 'enrollment' ) as $option ) { $data['visibility_control']['course_builder'][ 'basics_options_' . $option . '_' . $role ] = 'off'; }
			}
		}
	}
	return $data;
}, 1100 );

add_filter( 'tutor_admin_menu', function ( $menu ) {
	if ( current_user_can( 'manage_cas_content' ) ) {
		if ( isset( $menu['group_one']['categories'] ) ) { $menu['group_one']['categories']['page_title'] = 'Medium'; $menu['group_one']['categories']['menu_title'] = 'Medium'; }
		if ( function_exists( 'cas_content_is_user' ) && cas_content_is_user( get_current_user_id() ) ) { unset( $menu['group_one']['tags'] ); }
	}
	return $menu;
} );

/** Hidden Tags must survive native course saves, including omitted client fields. */
function cas_course_preserve_hidden_tags() {
	if ( ! current_user_can( 'manage_cas_content' ) || ! function_exists( 'cas_builder_nonce_valid' ) || ! cas_builder_nonce_valid() ) { return; }
	$raw = $_POST['course_id'] ?? '';
	$id = is_scalar( $raw ) && ctype_digit( (string) $raw ) ? (int) $raw : 0;
	$post = $id ? get_post( $id ) : null;
	if ( ! $post || 'courses' !== $post->post_type || ! current_user_can( 'edit_post', $id ) ) { return; }
	$tags = wp_get_post_terms( $id, 'course-tag', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $tags ) ) { wp_send_json( array( 'status_code' => 503, 'message' => 'Course labels could not be checked. Please try again.' ), 503 ); }
	if ( $tags ) {
		$_POST['course_tags'] = $tags;
		$_REQUEST['course_tags'] = $tags;
	} else {
		// Tutor rejects an explicitly empty array; omission preserves the empty selection.
		unset( $_POST['course_tags'], $_REQUEST['course_tags'] );
	}
}
add_action( 'wp_ajax_tutor_update_course', 'cas_course_preserve_hidden_tags', 3 );

add_action( 'tutor_after_course_builder_load', function () {
	if ( ! current_user_can( 'manage_cas_content' ) ) { return; }
	$labels = array( 'Basics' => 'Course', 'Curriculum' => 'Lessons', 'Additional' => 'Outcomes/Materials', 'Categories' => 'Medium', 'Category' => 'Medium', 'Category name' => 'Medium name', 'Category name is required' => 'Medium name is required', 'No categories found.' => 'No media found.' );
	wp_add_inline_script( 'tutor-course-builder', 'wp.hooks.addFilter("i18n.gettext","cas/course-labels",function(value,text,domain){var labels=' . wp_json_encode( $labels ) . ';return domain==="tutor"&&Object.prototype.hasOwnProperty.call(labels,text)?labels[text]:value;});', 'before' );
} );

/** Both catalogues use the same progressive, searchable Medium picker. */
add_action( 'wp_enqueue_scripts', function () {
	$post = get_post();
	$custom = $post && ( has_shortcode( (string) $post->post_content, 'cas_courses' ) || has_shortcode( (string) $post->post_content, 'cas_gallery' ) );
	if ( ! $custom && ! is_page( array( 'courses', 'gallery' ) ) && ! is_post_type_archive( 'courses' ) && ! is_tax( 'course-category' ) && count( cas_course_medium_terms() ) <= 6 ) { return; }
	$dir = get_stylesheet_directory();
	$deps = array( 'cas-site-extras' );
	if ( wp_script_is( 'cas-mobile-filters', 'enqueued' ) ) { $deps[] = 'cas-mobile-filters'; }
	wp_enqueue_script( 'cas-medium-picker', get_stylesheet_directory_uri() . '/js/cas-medium-picker.js', $deps, filemtime( $dir . '/js/cas-medium-picker.js' ), true );
	wp_enqueue_style( 'cas-medium-picker', get_stylesheet_directory_uri() . '/css/cas-medium-picker.css', array( 'cas-industrial-3' ), filemtime( $dir . '/css/cas-medium-picker.css' ) );
}, 35 );

require_once __DIR__ . '/cas-course-popularity.php';
