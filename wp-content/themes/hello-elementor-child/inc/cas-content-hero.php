<?php

defined( 'ABSPATH' ) || exit;

function cas_content_hero_fields() {
	$native = cas_hero_fields();
	$fields = array();
	foreach ( array( 1, 2, 3 ) as $slide ) {
		foreach ( array( 'title', 'subtitle', 'large', 'small' ) as $part ) {
			$fields[ 'cas_hero_' . $slide . '_' . $part ] = array(
				'label' => 'Slide ' . $slide . ' — ' . $native[ $part ]['label'],
				'default' => cas_hero_default( $slide, $part ),
				'type' => $native[ $part ]['type'],
				'description' => $native[ $part ]['desc'],
			);
		}
	}
	return $fields;
}

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

function cas_content_hero_clean( $input ) {
	$fields = cas_content_hero_fields();
	if ( ! is_array( $input ) || count( $input ) !== 12 || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_hero_input', 'Submit exactly the twelve fields for the existing three slides.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'cas_hero_input', 'Each carousel field must contain text or an image URL.' );
		}
		$value = wp_unslash( $input[ $key ] );
		$value = 'image' === $field['type'] ? esc_url_raw( $value ) : wp_kses_post( $value );
		if ( 'image' === $field['type'] && ! cas_content_hero_image_allowed( $value ) ) {
			return new WP_Error( 'cas_hero_input', 'Use a known public original image from Media or clear the slot. Private, non-image or unresolved local-upload URLs cannot be used in the public carousel.' );
		}
		$clean[ $key ] = $value;
	}
	return $clean;
}

function cas_content_hero_controls( $values ) {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit the homepage carousel.', '', array( 'response' => 403 ) );
	}
	$can_media = current_user_can( 'upload_files' );
	echo '<p>The three slides, image slots, View courses button and layout are fixed. Titles keep line breaks; descriptions display as plain text. Images crop to their existing slots.</p>';
	foreach ( cas_content_hero_fields() as $key => $field ) {
		$value = array_key_exists( $key, $values ) ? $values[ $key ] : $field['default'];
		if ( ! is_string( $value ) ) {
			wp_die( 'The carousel fields need an administrator check.', '', array( 'response' => 409 ) );
		}
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></h2>';
		if ( $field['description'] ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
		$name = 'cas_hero_fields[' . $key . ']';
		if ( 'image' !== $field['type'] ) {
			echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
			continue;
		}
		$allowed = cas_content_hero_image_allowed( $value );
		echo '<div class="cas-hero-image-slot"><input type="hidden" id="' . esc_attr( $key ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"><div class="cas-hero-image-preview">';
		if ( $value && $allowed ) {
			echo '<img src="' . esc_url( $value ) . '" alt="" style="max-width:240px;height:auto;display:block">';
		}
		echo '</div><button type="button" class="button cas-hero-image-choose" aria-label="' . esc_attr( 'Choose image for ' . $field['label'] ) . '"' . ( $can_media ? '' : ' disabled' ) . '>' . esc_html( $value ? 'Replace image' : 'Choose image' ) . '</button> ';
		echo '<button type="button" class="button-link cas-hero-image-clear" aria-label="' . esc_attr( 'Clear ' . $field['label'] ) . '">Clear slot</button><p class="description cas-hero-media-error" role="alert"' . ( $allowed ? ' hidden' : '' ) . '>Choose a public image, not a private course file.</p></div>';
	}
	if ( ! $can_media ) {
		echo '<p>Your current media permission does not allow choosing or uploading images. Existing text and clearing slots remain available.</p>';
	}
}

function cas_content_hero_enqueue() {
	$page = $_GET['page'] ?? null;
	if ( ! is_string( $page ) || 'cas-content-hero' !== $page || ! current_user_can( CAS_CONTENT_CAP ) ) {
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

function cas_content_hero_page() {
	cas_content_prose_page( 'hero' );
}
