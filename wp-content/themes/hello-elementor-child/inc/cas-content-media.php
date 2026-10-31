<?php

defined( 'ABSPATH' ) || exit;

function cas_content_hero_local_upload( $url ) {
	$parts = wp_parse_url( $url );
	$uploads = wp_get_upload_dir();
	$base = wp_parse_url( $uploads['baseurl'] );
	$home = wp_parse_url( home_url( '/' ) );
	if ( ! is_array( $parts ) || ! is_array( $base ) ) {
		return true;
	}
	$host = rtrim( strtolower( rawurldecode( $parts['host'] ?? '' ) ), '.' );
	$hosts = array( rtrim( strtolower( $base['host'] ?? '' ), '.' ), rtrim( strtolower( $home['host'] ?? '' ), '.' ) );
	if ( $host && ! in_array( $host, $hosts, true ) ) {
		return false;
	}
	$paths = array();
	foreach ( array( $parts['path'] ?? '', $base['path'] ?? '' ) as $path ) {
		$segments = array();
		foreach ( explode( '/', str_replace( '\\', '/', rawurldecode( $path ) ) ) as $part ) {
			if ( '..' === $part ) {
				array_pop( $segments );
			} elseif ( '' !== $part && '.' !== $part ) {
				$segments[] = $part;
			}
		}
		$paths[] = '/' . implode( '/', $segments );
	}
	return false !== strpos( $paths[0], '%' ) || $paths[0] === $paths[1] || 0 === strpos( $paths[0], rtrim( $paths[1], '/' ) . '/' );
}

function cas_content_hero_image_allowed( $url ) {
	if ( '' === $url ) {
		return true;
	}
	$query = wp_parse_url( $url, PHP_URL_QUERY );
	if ( is_string( $query ) ) {
		wp_parse_str( $query, $args );
		if ( array_key_exists( CAS_PRIVATE_QUERY_VAR, $args ) ) {
			return false;
		}
	}
	$lookup = preg_replace( '/[?#].*$/', '', $url );
	$attachment = attachment_url_to_postid( $lookup );
	if ( $attachment ) {
		return wp_attachment_is_image( $attachment ) && ! in_array( get_post_status( $attachment ), array( 'private', 'trash' ), true ) && ! cas_private_is( $attachment );
	}
	foreach ( array( 1, 2, 3 ) as $slide ) {
		foreach ( array( 'large', 'small' ) as $part ) {
			if ( $lookup === cas_hero_default( $slide, $part ) ) {
				return true;
			}
		}
	}
	// Unknown managed-upload URLs are not evidence of a public image or derivative ownership.
	return ! cas_content_hero_local_upload( $lookup );
}

function cas_content_image_slot( $key, $name, $value, $field, $can_media ) {
	$fallback = ! empty( $field['restore'] ) && cas_content_hero_image_allowed( $field['default'] ) ? $field['default'] : '';
	$preview = '' === $value ? $fallback : $value;
	$allowed = cas_content_hero_image_allowed( $preview );
	$choose_text = $value ? 'Replace image' : 'Choose image';
	$clear_text = ! empty( $field['restore'] ) ? 'Use shipped default' : 'Clear slot';
	echo '<div class="cas-hero-image-slot" data-cas-label="' . esc_attr( $field['label'] ) . '" data-cas-fallback="' . esc_attr( $fallback ) . '"><input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><div class="cas-hero-image-preview">';
	if ( $preview && $allowed ) {
		echo '<img src="' . esc_url( $preview ) . '" alt="" style="max-width:240px;height:auto;display:block">';
	}
	echo '</div><button type="button" class="button cas-hero-image-choose" aria-label="' . esc_attr( $choose_text . ' for ' . $field['label'] ) . '"' . ( $can_media ? '' : ' disabled' ) . '>' . esc_html( $choose_text ) . '</button> ';
	echo '<button type="button" class="button-link cas-hero-image-clear" aria-label="' . esc_attr( $clear_text . ' for ' . $field['label'] ) . '">' . esc_html( $clear_text ) . '</button><p class="description cas-hero-media-error" role="alert"' . ( $allowed ? ' hidden' : '' ) . '>Choose a known public original image from Media; private files and unresolved local uploads are not allowed.</p></div>';
}

function cas_content_hero_enqueue() {
	$page = $_GET['page'] ?? null;
	if ( ! is_string( $page ) || ! in_array( $page, array( 'cas-content-hero', 'cas-content-home-images', 'cas-content-about-image', 'cas-content-brand-images', 'cas-content-home-page', 'cas-content-about-page', 'cas-content-site-details' ), true ) || ! current_user_can( CAS_CONTENT_CAP ) ) {
		return;
	}
	$can_media = current_user_can( 'upload_files' );
	if ( $can_media ) {
		wp_enqueue_media();
	}
	wp_enqueue_script( 'cas-content-hero', get_stylesheet_directory_uri() . '/js/cas-content-hero.js', array( 'jquery' ), filemtime( get_stylesheet_directory() . '/js/cas-content-hero.js' ), true );
	wp_localize_script( 'cas-content-hero', 'casHeroMedia', array( 'allowed' => $can_media, 'nonce' => wp_create_nonce( 'cas_hero_media' ), 'privateQuery' => CAS_PRIVATE_QUERY_VAR ) );
}
add_action( 'admin_enqueue_scripts', 'cas_content_hero_enqueue' );

function cas_content_hero_media_query( $query ) {
	$raw = $_REQUEST['query'] ?? null;
	if ( ! is_array( $raw ) || ! array_key_exists( 'cas_hero_media', $raw ) ) {
		return $query;
	}
	$nonce = $raw['cas_hero_media'];
	if ( ! current_user_can( CAS_CONTENT_CAP ) || ! current_user_can( 'upload_files' ) || ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'cas_hero_media' ) || ( isset( $query['meta_query'] ) && ! is_array( $query['meta_query'] ) ) ) {
		$query['post__in'] = array( 0 );
		return $query;
	}
	$query['post_mime_type'] = 'image';
	$query['post_status'] = 'inherit';
	$public = array( 'key' => CAS_PRIVATE_META_FLAG, 'compare' => 'NOT EXISTS' );
	$query['meta_query'] = isset( $query['meta_query'] ) ? array( 'relation' => 'AND', $query['meta_query'], $public ) : array( $public );
	return $query;
}
add_filter( 'ajax_query_attachments_args', 'cas_content_hero_media_query', 20 );
