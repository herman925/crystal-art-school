<?php
/** Crystal's editable About certificates; uses the existing certificate rail and highlight. */
defined( 'ABSPATH' ) || exit;

const CAS_CERTIFICATE_TYPE = 'cas_certificate';
const CAS_CERTIFICATE_PIN = 'cas_certificate_pinned';
const CAS_CERTIFICATE_READY = 'cas_certificates_ready';

add_action( 'init', static function () {
	register_post_type( CAS_CERTIFICATE_TYPE, array(
		'labels' => array( 'name' => 'Certificates', 'singular_name' => 'Certificate', 'add_new' => 'Add Certificate', 'add_new_item' => 'Add Certificate', 'edit_item' => 'Edit Certificate', 'all_items' => 'Certificates' ),
		'public' => false, 'publicly_queryable' => false, 'exclude_from_search' => true,
		'show_ui' => true, 'show_in_rest' => false, 'show_in_nav_menus' => false,
		'rewrite' => false, 'has_archive' => false, 'menu_icon' => 'dashicons-awards', 'menu_position' => 25,
		'supports' => array( 'title', 'thumbnail', 'page-attributes', 'author' ),
	) );
} );

add_filter( 'enter_title_here', static function ( $title, $post ) {
	return CAS_CERTIFICATE_TYPE === $post->post_type ? 'Honour or exhibition wording' : $title;
}, 10, 2 );

add_action( 'add_meta_boxes_' . CAS_CERTIFICATE_TYPE, static function () {
	add_meta_box( 'cas-certificate-details', 'Certificate Details', 'cas_certificate_details', CAS_CERTIFICATE_TYPE, 'normal', 'high' );
} );

function cas_certificate_details( $post ) {
	wp_nonce_field( 'cas_certificate_save', 'cas_certificate_nonce' );
	echo '<p><label for="cas-cert-year"><strong>Year</strong></label><br><input id="cas-cert-year" name="cas_certificate_year" type="text" maxlength="20" value="' . esc_attr( get_post_meta( $post->ID, '_cas_certificate_year', true ) ) . '"></p>';
	echo '<p><label for="cas-cert-caption"><strong>Scan Caption</strong></label><br><textarea id="cas-cert-caption" name="cas_certificate_caption" rows="4" class="large-text">' . esc_textarea( get_post_meta( $post->ID, '_cas_certificate_caption', true ) ) . '</textarea></p>';
	echo '<p>Use the title for the wording in the honours table, and Scan Caption for the text beneath the scan. A blank caption uses the title. Add or replace the public scan through <strong>Featured image</strong>. Use <strong>Order</strong> for the list position, lowest first.</p>';
	echo '<p>Upload only the version you want visitors to see. Cover personal details or signatures before uploading when needed. The existing masked scan stays masked unless you choose a replacement.</p>';
	echo '<p>After saving, open <a href="' . esc_url( admin_url( 'edit.php?post_type=' . CAS_CERTIFICATE_TYPE ) ) . '">Certificates</a> and use <strong>Pin to top</strong> to choose the large highlighted certificate. Only one is pinned at a time. A published entry without a scan keeps its table row but cannot be pinned.</p>';
}

add_action( 'save_post_' . CAS_CERTIFICATE_TYPE, static function ( $id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || ! current_user_can( 'edit_post', $id ) ) { return; }
	$nonce = $_POST['cas_certificate_nonce'] ?? null;
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'cas_certificate_save' ) ) { return; }
	foreach ( array( 'year' => 'sanitize_text_field', 'caption' => 'sanitize_textarea_field' ) as $field => $sanitize ) {
		$value = $_POST[ 'cas_certificate_' . $field ] ?? null;
		if ( is_string( $value ) ) { update_post_meta( $id, '_cas_certificate_' . $field, $sanitize( wp_unslash( $value ) ) ); }
	}
} );

/** A scan is public artwork, never a paid course attachment. Core's thumbnail guard also enforces this on save. */
function cas_certificate_image( $id ) {
	$attachment = (int) get_post_thumbnail_id( $id );
	return $attachment && wp_attachment_is_image( $attachment ) && ! cas_private_is( $attachment ) ? $attachment : 0;
}

add_filter( 'manage_' . CAS_CERTIFICATE_TYPE . '_posts_columns', static function ( $columns ) {
	$columns['certificate_year'] = 'Year';
	$columns['certificate_order'] = 'Order';
	$columns['certificate_pin'] = 'Highlight';
	return $columns;
} );

add_action( 'manage_' . CAS_CERTIFICATE_TYPE . '_posts_custom_column', static function ( $column, $id ) {
	if ( 'certificate_year' === $column ) { echo esc_html( get_post_meta( $id, '_cas_certificate_year', true ) ); }
	if ( 'certificate_order' === $column ) { echo (int) get_post( $id )->menu_order; }
	if ( 'certificate_pin' !== $column || ! current_user_can( CAS_CONTENT_CAP ) || ! current_user_can( 'edit_post', $id ) ) { return; }
	$pinned = (int) get_option( CAS_CERTIFICATE_PIN ) === (int) $id;
	if ( ! $pinned && ( 'publish' !== get_post_status( $id ) || ! cas_certificate_image( $id ) ) ) {
		echo 'Publish with a scan to pin';
		return;
	}
	// Core wraps the list table in posts-filter: associate an external form instead of nesting forms.
	$GLOBALS['cas_certificate_pin_forms'][ $id ] = $pinned;
	echo '<button type="submit" class="button" form="cas-certificate-pin-' . (int) $id . '" aria-label="' . esc_attr( ( $pinned ? 'Unpin: ' : 'Pin to top: ' ) . get_the_title( $id ) ) . '">' . ( $pinned ? 'Unpin' : 'Pin to top' ) . '</button>';
	if ( $pinned ) { echo '<p><strong>' . ( 'publish' === get_post_status( $id ) && cas_certificate_image( $id ) ? 'Pinned to top' : 'Pinned choice; not currently shown' ) . '</strong></p>'; }
}, 10, 2 );

add_action( 'admin_footer-edit.php', static function () {
	foreach ( (array) ( $GLOBALS['cas_certificate_pin_forms'] ?? array() ) as $id => $pinned ) {
		echo '<form hidden id="cas-certificate-pin-' . (int) $id . '" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'cas_certificate_pin_' . $id );
		echo '<input type="hidden" name="action" value="cas_certificate_pin"><input type="hidden" name="certificate_id" value="' . (int) $id . '"><input type="hidden" name="operation" value="' . ( $pinned ? 'unpin' : 'pin' ) . '"></form>';
	}
} );

add_action( 'admin_post_cas_certificate_pin', static function () {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { wp_die( 'Use the Certificates list button.', '', array( 'response' => 405 ) ); }
	$value = $_POST['certificate_id'] ?? null;
	$id = is_scalar( $value ) ? absint( $value ) : 0;
	$operation = $_POST['operation'] ?? null;
	if ( ! current_user_can( CAS_CONTENT_CAP ) || CAS_CERTIFICATE_TYPE !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'You cannot change this certificate.', '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'cas_certificate_pin_' . $id );
	if ( ! in_array( $operation, array( 'pin', 'unpin' ), true ) ) { wp_die( 'Invalid action.', '', array( 'response' => 400 ) ); }
	if ( 'pin' === $operation ) {
		if ( 'publish' !== get_post_status( $id ) || ! cas_certificate_image( $id ) ) {
			wp_die( 'Publish the certificate with a public scan before pinning it.', '', array( 'response' => 400 ) );
		}
		// One option stores one ID: pinning another certificate replaces the old choice.
		update_option( CAS_CERTIFICATE_PIN, $id, false );
	} elseif ( (int) get_option( CAS_CERTIFICATE_PIN ) === $id ) {
		update_option( CAS_CERTIFICATE_PIN, 0, false );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=' . CAS_CERTIFICATE_TYPE ) );
	exit;
} );

add_action( 'pre_get_posts', static function ( $query ) {
	if ( is_admin() && $query->is_main_query() && CAS_CERTIFICATE_TYPE === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
	}
} );

/** The ready flag prevents clearing every entry from resurrecting the old hardcoded list. */
function cas_about_certificates_render() {
	$posts = get_posts( array( 'post_type' => CAS_CERTIFICATE_TYPE, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) ) );
	if ( ! $posts ) { echo '<p>No certificates are published yet.</p>'; return; }
	$pinned = (int) get_option( CAS_CERTIFICATE_PIN );
	foreach ( $posts as $key => $post ) {
		if ( $pinned === (int) $post->ID && cas_certificate_image( $post->ID ) ) {
			unset( $posts[ $key ] ); array_unshift( $posts, $post ); break;
		}
	}
	echo '<div class="cert-highlight" id="cert-highlight" hidden></div><div class="cert-split"><div class="cert-list" id="cert-list" role="group" aria-label="International honours and certificates"></div><div class="bc-row is-rail" id="cert-rail">';
	$has_demo = false;
	foreach ( $posts as $post ) {
		$image = cas_certificate_image( $post->ID );
		$year = get_post_meta( $post->ID, '_cas_certificate_year', true );
		$demo = 'Demo' === $year; $has_demo = $has_demo || $demo;
		$caption = get_post_meta( $post->ID, '_cas_certificate_caption', true );
		if ( '' === $caption ) { $caption = $post->post_title; }
		echo '<figure class="bc-card bc-card--cert' . ( $image ? '' : ' is-ph' ) . ( $demo ? ' is-demo' : '' ) . '" data-certificate-id="' . (int) $post->ID . '" data-year="' . esc_attr( $year ) . '" data-honour="' . esc_attr( $post->post_title ) . '"' . ( $image && $pinned === (int) $post->ID ? ' data-highlight' : '' ) . '><div class="cert-frame"><div class="cert-mat">';
		if ( $image ) {
			echo wp_get_attachment_image( $image, 'full', false, array( 'sizes' => '120px', 'loading' => 'lazy', 'decoding' => 'async' ) );
		} else {
			$shape = get_post_meta( $post->ID, '_cas_certificate_placeholder', true );
			$shape = in_array( $shape, array( 'landscape', 'portrait', 'square' ), true ) ? $shape : 'portrait';
			echo '<span class="cert-slot is-' . esc_attr( $shape ) . '">' . esc_html( $demo ? ucfirst( $shape ) . ' certificate' : 'Scan not supplied' ) . '</span>';
		}
		echo '</div></div><figcaption>' . nl2br( esc_html( $caption ) ) . '</figcaption></figure>';
	}
	echo '</div></div>';
	if ( $has_demo ) { echo '<p style="margin:14px 0 0;font-size:13px;line-height:1.6;color:var(--muted);max-width:70ch;">Rows marked Demo are layout samples, not awards. The exhibition poster is identified as a poster, not an award. The samples remain published for review.</p>'; }
}
