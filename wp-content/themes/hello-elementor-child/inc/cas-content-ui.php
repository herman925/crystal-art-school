<?php
/** The site's existing materials and type, scoped to the content workspace. */
defined( 'ABSPATH' ) || exit;

function cas_content_ui_screen() {
	$page = $_GET['page'] ?? null;
	return is_string( $page ) && in_array( $page, array(
		'cas-content-hub', 'cas-content-home-page', 'cas-content-about-page', 'cas-content-site-details',
		'cas-content-about', 'cas-content-blog', 'cas-content-free-videos', 'cas-content-home',
		'cas-content-contact', 'cas-content-hero', 'cas-content-home-images', 'cas-content-about-image', 'cas-content-brand-images',
	), true ) && current_user_can( CAS_CONTENT_CAP );
}

function cas_content_ui_fonts() {
	return 'https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600&family=Arizonia&family=Bodoni+Moda:wght@500;600;700&family=Oswald:wght@500;600&display=swap';
}

function cas_content_ui_stylesheet( $name ) {
	return get_stylesheet_directory_uri() . '/css/' . $name . '.css?ver=' . filemtime( get_stylesheet_directory() . '/css/' . $name . '.css' );
}

add_action( 'admin_enqueue_scripts', function () {
	if ( ! cas_content_ui_screen() ) { return; }
	if ( ( $_GET['page'] ?? '' ) !== 'cas-content-hub' ) {
		wp_enqueue_style( 'cas-content-editor-nav', cas_content_ui_stylesheet( 'cas-content-editor-nav' ), array(), null );
		return;
	}
	wp_enqueue_style( 'cas-content-fonts', cas_content_ui_fonts(), array(), null );
	wp_enqueue_style( 'cas-content-materials', cas_content_ui_stylesheet( 'cas-content-materials' ), array(), null );
	wp_enqueue_style( 'cas-content-ui', cas_content_ui_stylesheet( 'cas-content-ui' ), array( 'cas-content-materials', 'cas-content-fonts' ), null );
} );

add_filter( 'admin_body_class', function ( $classes ) {
	return cas_content_ui_screen() && ( $_GET['page'] ?? '' ) === 'cas-content-hub' ? $classes . ' cas-content-screen' : $classes;
} );

/** Inner editors intentionally keep the native WordPress appearance. */
function cas_content_ui_tinymce( $settings, $editor_id ) { return $settings; }
