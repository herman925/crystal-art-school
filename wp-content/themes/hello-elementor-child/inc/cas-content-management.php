<?php
/** Shared navigation and native term management for the site's content lists. */
defined( 'ABSPATH' ) || exit;

function cas_content_term_config() {
	return array(
		'cas_artwork_medium' => array( 'cas_artwork', CAS_ARTWORK_META_MEDIUM, 'Artwork Media', 'Medium' ),
		'cas_artwork_type' => array( 'cas_artwork', CAS_ARTWORK_META_TYPE, 'Artwork Types', 'Type' ),
		'cas_showcase_medium' => array( 'cas_showcase', CAS_SHOWCASE_META_MEDIUM, 'Student Work Media', 'Medium' ),
	);
}

add_action( 'init', function () {
	foreach ( cas_content_term_config() as $taxonomy => $config ) {
		register_taxonomy( $taxonomy, $config[0], array(
			'labels' => array(
				'name' => $config[2], 'singular_name' => $config[3],
				'add_new_item' => 'Add ' . $config[3], 'edit_item' => 'Edit ' . $config[3],
				'separate_items_with_commas' => 'Separate values with commas',
				'choose_from_most_used' => 'Choose from the most used',
			),
			'public' => false, 'show_ui' => true, 'show_in_rest' => true,
			'hierarchical' => false, 'rewrite' => false,
		) );
	}
}, 20 );

function cas_content_term_parts( $value ) {
	return array_values( array_unique( array_filter( array_map( 'trim', explode( ',', $value ) ), static function ( $part ) { return '' !== $part; } ) ) );
}

/** Legacy values remain readable until the explicit import or the first native term save. */
function cas_content_term_text( $post_id, $taxonomy, $legacy_key ) {
	$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
	if ( ! is_wp_error( $terms ) && ( $terms || metadata_exists( 'post', $post_id, '_cas_terms_' . $taxonomy ) ) ) {
		return implode( ', ', $terms );
	}
	return (string) get_post_meta( $post_id, $legacy_key, true );
}

add_action( 'set_object_terms', function ( $object_id, $terms, $tt_ids, $taxonomy ) {
	if ( isset( cas_content_term_config()[ $taxonomy ] ) ) {
		// An empty selection must stay empty instead of restoring the legacy text.
		update_post_meta( $object_id, '_cas_terms_' . $taxonomy, '1' );
	}
}, 10, 4 );

function cas_content_management_screen() {
	$screen = get_current_screen();
	return $screen && current_user_can( CAS_CONTENT_CAP ) && (
		in_array( $screen->post_type, CAS_CONTENT_POST_TYPES, true ) ||
		in_array( $screen->taxonomy, array_merge( array_keys( cas_content_term_config() ), array( 'category', 'post_tag', 'course-category', 'course-tag' ) ), true )
	);
}

add_action( 'admin_notices', function () {
	if ( ! cas_content_management_screen() ) { return; }
	echo '<p class="cas-content-back"><a class="button" href="' . esc_url( admin_url( 'admin.php?page=cas-content-hub' ) ) . '">&larr; Content Hub</a></p>';
	$screen = get_current_screen();
	if ( 'edit' === $screen->base && 'cas_artwork' === $screen->post_type ) {
		echo '<p class="cas-artwork-sort">Publication Date: ';
		foreach ( array( 'DESC' => 'Newest First', 'ASC' => 'Oldest First' ) as $order => $label ) {
			$url = add_query_arg( array( 'orderby' => 'date', 'order' => $order, 'paged' => false ) );
			echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a> ';
		}
		echo '</p>';
	}
} );

add_action( 'admin_enqueue_scripts', function () {
	if ( ! cas_content_management_screen() ) { return; }
	wp_enqueue_style( 'cas-content-management', cas_content_ui_stylesheet( 'cas-content-management' ), array(), null );
	if ( 'post' === get_current_screen()->base && 'cas_artwork' === get_current_screen()->post_type ) {
		wp_enqueue_script( 'cas-content-management', get_stylesheet_directory_uri() . '/js/cas-content-management.js', array( 'wp-dom-ready', 'wp-data', 'wp-edit-post' ), filemtime( get_stylesheet_directory() . '/js/cas-content-management.js' ), true );
	}
} );
