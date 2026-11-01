<?php
/** Integration assertions for the fresh, synthetic WordPress fixture only. */
$root = getenv( 'CAS_CERT_TEST_ROOT' );
if ( ! is_string( $root ) || ! str_starts_with( $root, '/scratch/' ) ) { throw new RuntimeException( 'Use the isolated scratch fixture' ); }
require $root . '/wp-load.php';
if ( 'local' !== wp_get_environment_type() || ! in_array( parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) { throw new RuntimeException( 'Local synthetic site required' ); }
function cert_check( $ok, $message ) { if ( ! $ok ) { throw new RuntimeException( $message ); } }
function cert_markup() { ob_start(); cas_about_certificates_render(); return ob_get_clean(); }
$owner = get_user_by( 'login', 'floweditor' ); $other = get_user_by( 'login', 'flowother' );
cert_check( $owner && $other, 'Synthetic editors missing' );
$prior_pin = get_option( CAS_CERTIFICATE_PIN );
$published = get_posts( array( 'post_type' => CAS_CERTIFICATE_TYPE, 'post_status' => 'publish', 'numberposts' => -1 ) );
$image = 0;
foreach ( $published as $post ) { if ( cas_certificate_image( $post->ID ) ) { $image = cas_certificate_image( $post->ID ); break; } }
cert_check( $image > 0, 'Imported public scan missing' );
wp_set_current_user( 1 );
$mine = wp_insert_post( array( 'post_type' => CAS_CERTIFICATE_TYPE, 'post_status' => 'publish', 'post_title' => 'Synthetic policy certificate', 'post_author' => $owner->ID, 'menu_order' => -10 ) );
$foreign = wp_insert_post( array( 'post_type' => CAS_CERTIFICATE_TYPE, 'post_status' => 'publish', 'post_title' => 'Synthetic other-author certificate', 'post_author' => $other->ID ) );
set_post_thumbnail( $mine, $image );
try {
	wp_set_current_user( $owner->ID );
	cert_check( current_user_can( 'edit_post', $mine ), 'Own certificate edit denied' );
	cert_check( ! current_user_can( 'edit_post', $foreign ), 'Foreign certificate edit allowed' );
	cert_check( ! current_user_can( 'manage_options' ), 'Content role acquired administration' );
	$_POST = array( 'cas_certificate_nonce' => wp_create_nonce( 'cas_certificate_save' ), 'cas_certificate_year' => '<b>2028</b>', 'cas_certificate_caption' => '<b>Public caption</b>' );
	do_action( 'save_post_' . CAS_CERTIFICATE_TYPE, $mine );
	cert_check( '2028' === get_post_meta( $mine, '_cas_certificate_year', true ), 'Year save/sanitization failed' );
	cert_check( 'Public caption' === get_post_meta( $mine, '_cas_certificate_caption', true ), 'Caption save/sanitization failed' );
	do_action( 'save_post_' . CAS_CERTIFICATE_TYPE, $foreign );
	cert_check( '' === get_post_meta( $foreign, '_cas_certificate_year', true ), 'Foreign metadata changed' );
	$_POST['cas_certificate_nonce'] = 'invalid'; $_POST['cas_certificate_year'] = '2030';
	do_action( 'save_post_' . CAS_CERTIFICATE_TYPE, $mine );
	cert_check( '2028' === get_post_meta( $mine, '_cas_certificate_year', true ), 'Missing nonce allowed metadata change' );
	$_POST = array();
	cert_check( cas_private_public_image_use( $image ), 'Certificate scan missing from public-image protection' );
	update_option( CAS_CERTIFICATE_PIN, $mine, false );
	$html = cert_markup();
	cert_check( 1 === substr_count( $html, ' data-highlight' ), 'Single pinned highlight violated' );
	cert_check( str_contains( substr( $html, strpos( $html, '<figure' ), 180 ), 'data-certificate-id="' . $mine . '"' ), 'Pin did not lead the public order' );
	update_post_meta( $mine, '_cas_certificate_caption', '' );
	cert_check( str_contains( cert_markup(), '<figcaption>Synthetic policy certificate</figcaption>' ), 'Blank caption lost its accessible title' );
	wp_update_post( array( 'ID' => $mine, 'post_status' => 'draft' ) );
	$html = cert_markup();
	cert_check( ! str_contains( $html, 'data-certificate-id="' . $mine . '"' ) && ! str_contains( $html, ' data-highlight' ), 'Draft certificate remained public/pinned' );
	wp_set_current_user( 1 );
	foreach ( $published as $post ) { wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) ); }
	wp_update_post( array( 'ID' => $foreign, 'post_status' => 'draft' ) );
	$html = cas_render_about();
	cert_check( str_contains( $html, 'No certificates are published yet.' ), 'Empty state missing' );
	cert_check( ! str_contains( $html, 'data-honour=' ), 'Empty list resurrected hardcoded certificates' );
	$kind = get_post_type_object( CAS_CERTIFICATE_TYPE );
	cert_check( ! $kind->publicly_queryable && ! $kind->show_in_rest, 'Certificate acquired an unrelated public endpoint' );
	echo "Certificate role, nonce, sanitization, public-image protection, pin, withdrawal and empty-state assertions passed\n";
} finally {
	$_POST = array(); wp_set_current_user( 1 );
	foreach ( $published as $post ) { wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'publish' ) ); }
	wp_delete_post( $mine, true ); wp_delete_post( $foreign, true ); update_option( CAS_CERTIFICATE_PIN, $prior_pin, false );
}
