<?php
/** Review-only setup/reset/cleanup for native sorting; never run by Development or on production. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'CAS_RUNTIME_TESTS' ) || home_url() !== getenv( 'CAS_SORT_FIXTURE_HOME' )
	|| ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true )
	|| ! current_user_can( 'manage_options' ) ) {
	throw new RuntimeException( 'Requires an acknowledged localhost synthetic site and its existing administrator.' );
}
$key = 'cas_w1_sort_fixture';
$mode = $args[0] ?? '';
$ledger = get_option( $key );
global $wpdb;
if ( in_array( $mode, array( 'reset', 'cleanup' ), true ) ) {
	if ( ! is_array( $ledger ) ) { throw new RuntimeException( 'Missing fixture ledger; nothing changed.' ); }
	foreach ( $ledger['posts'] as $id ) {
		if ( 'w1-sort' !== get_post_meta( $id, '_cas_fixture_owner', true ) ) { throw new RuntimeException( 'Fixture post identity drift; stop.' ); }
	}
	if ( 'reset' === $mode ) {
		foreach ( $ledger['rows'] as $row ) {
			$id = (int) $row['ID'];
			unset( $row['ID'] );
			if ( false === $wpdb->update( $wpdb->posts, $row, array( 'ID' => $id ) ) ) { throw new RuntimeException( 'Synthetic reset failed.' ); }
			clean_post_cache( $id );
		}
		echo wp_json_encode( array( 'reset' => true ) );
		return;
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_reverse( $ledger['posts'] ) as $id ) { wp_delete_post( $id, true ); }
	foreach ( $ledger['users'] as $login => $id ) {
		$user = get_userdata( $id );
		if ( ! $user || $user->user_login !== $login ) { throw new RuntimeException( 'Fixture user identity drift; stop.' ); }
		wp_delete_user( $id );
	}
	delete_option( $key );
	echo wp_json_encode( array( 'cleanup' => true ) );
	return;
}
if ( 'setup' !== $mode || $ledger || ! function_exists( 'cas_builder_validate_content_order' ) ) {
	throw new RuntimeException( 'Wrong mode, existing ledger or missing sorting fix.' );
}
foreach ( array( 'w1sortowner', 'w1sortother' ) as $login ) {
	if ( username_exists( $login ) ) { throw new RuntimeException( 'Synthetic username exists; never overwrite it.' ); }
}
$ledger = array( 'users' => array(), 'posts' => array() );
add_option( $key, $ledger, '', false );
$out = array( 'admin' => get_current_user_id(), 'home' => home_url(), 'users' => array(), 'courses' => array(), 'topics' => array(), 'lessons' => array() );
foreach ( array( 'w1sortowner', 'w1sortother' ) as $login ) {
	$pw = wp_generate_password( 32, false );
	$id = wp_create_user( $login, $pw, $login . '@example.test' );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$ledger['users'][ $login ] = $id;
	update_option( $key, $ledger, false );
	( new WP_User( $id ) )->set_role( 'cas_content' );
	// Real internal Tutor metadata, no WordPress instructor/admin role.
	update_user_meta( $id, '_is_tutor_instructor', tutor_time() );
	update_user_meta( $id, '_tutor_instructor_status', 'approved' );
	$out['users'][ $login ] = array( 'id' => $id, 'password' => $pw );
}
function w1_sort_post( $type, $title, $author, $parent = 0 ) {
	global $ledger, $key;
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'courses' === $type ? 'draft' : 'publish', 'post_title' => 'W1 sort ' . $title, 'post_author' => $author, 'post_parent' => $parent ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-sort', true );
	$ledger['posts'][] = (int) $id;
	update_option( $key, $ledger, false );
	return (int) $id;
}
$owner = $ledger['users']['w1sortowner'];
$other = $ledger['users']['w1sortother'];
foreach ( array( 'own' => $owner, 'foreign' => $other, 'empty' => $owner ) as $name => $author ) {
	$id = w1_sort_post( 'courses', $name, $author );
	update_post_meta( $id, '_tutor_course_price_type', 'paid' );
	update_post_meta( $id, 'tutor_course_price', 'own' === $name ? '40' : '50' );
	update_post_meta( $id, 'tutor_course_sale_price', 'own' === $name ? '31' : '0' );
	if ( ! in_array( $id, array_map( 'intval', get_user_meta( $author, '_tutor_instructor_course_id', false ) ), true ) ) { throw new RuntimeException( 'Native author linkage missing.' ); }
	$out['courses'][ $name ] = $id;
}
$published = wp_update_post( array( 'ID' => $out['courses']['own'], 'post_status' => 'publish' ), true );
if ( is_wp_error( $published ) ) { throw new RuntimeException( $published->get_error_message() ); }
foreach ( array( 'a', 'b', 'empty' ) as $name ) { $out['topics'][ $name ] = w1_sort_post( 'topics', $name, $owner, $out['courses']['own'] ); }
$out['topics']['foreign'] = w1_sort_post( 'topics', 'foreign', $other, $out['courses']['foreign'] );
foreach ( array( 'a1' => 'a', 'a2' => 'a', 'b1' => 'b', 'foreign' => 'foreign' ) as $name => $topic ) {
	$out['lessons'][ $name ] = w1_sort_post( 'lesson', $name, 'foreign' === $name ? $other : $owner, $out['topics'][ $topic ] );
}
$ids = implode( ',', array_map( 'intval', $ledger['posts'] ) );
$ledger['rows'] = $wpdb->get_results( "SELECT ID,post_type,post_parent,menu_order,post_status,post_author,post_title,post_content FROM {$wpdb->posts} WHERE ID IN ($ids) ORDER BY ID", ARRAY_A );
update_option( $key, $ledger, false );
$out['rows'] = $ledger['rows'];
$out['post_ids'] = $ledger['posts'];
$out['nonce_field'] = tutor()->nonce;
$out['module_sha256'] = hash_file( 'sha256', get_stylesheet_directory() . '/inc/cas-content-builder.php' );
echo wp_json_encode( $out, JSON_PRETTY_PRINT );
