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
	wp_enqueue_style( 'cas-content-fonts', cas_content_ui_fonts(), array(), null );
	wp_enqueue_style( 'cas-content-materials', cas_content_ui_stylesheet( 'cas-content-materials' ), array(), null );
	wp_enqueue_style( 'cas-content-ui', cas_content_ui_stylesheet( 'cas-content-ui' ), array( 'cas-content-materials', 'cas-content-fonts' ), null );
} );

add_filter( 'admin_body_class', function ( $classes ) {
	return cas_content_ui_screen() ? $classes . ' cas-content-screen' : $classes;
} );

function cas_content_ui_tinymce( $settings, $editor_id ) {
	if ( ! cas_content_ui_screen() || ! in_array( $editor_id, array(
		'cas_about_intro', 'cas_about_quote', 'cas_about_journey_body', 'cas_about_community_body', 'cas_about_instructor_body',
		'cas_blog_intro', 'cas_fv_lead', 'cas_fv_intro_demos', 'cas_fv_intro_quick', 'cas_fv_intro_shorts',
	), true ) ) { return $settings; }
	$settings['body_class'] = trim( ( $settings['body_class'] ?? '' ) . ' cas-writing-surface' );
	$settings['content_css'] = implode( ',', array_filter( array(
		$settings['content_css'] ?? '', cas_content_ui_fonts(),
		cas_content_ui_stylesheet( 'cas-content-materials' ), cas_content_ui_stylesheet( 'cas-content-writing' ),
	) ) );
	return $settings;
}
add_filter( 'tiny_mce_before_init', 'cas_content_ui_tinymce', 20, 2 );
