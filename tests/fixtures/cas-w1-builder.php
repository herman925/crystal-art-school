<?php
/** Review-owned LOCAL fixture: wp eval-file /tmp/cas-w1-builder.php setup|cleanup. Never production. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'CAS_RUNTIME_TESTS' )
	|| ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true )
	|| 18089 !== (int) wp_parse_url( home_url(), PHP_URL_PORT ) ) {
	throw new RuntimeException( 'Only the explicitly enabled cas-local site on port 18089 is allowed.' );
}
$mode = $args[0] ?? 'setup';
$key = 'cas_w1_builder_fixture';
$ledger = get_option( $key );
if ( 'cleanup' === $mode ) {
	if ( ! is_array( $ledger ) ) {
		throw new RuntimeException( 'No fixture ledger exists; nothing was deleted.' );
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_reverse( $ledger['posts'] ) as $id ) {
		if ( 'w1-builder' !== get_post_meta( $id, '_cas_fixture_owner', true ) ) {
			throw new RuntimeException( 'Fixture ownership marker changed; stop cleanup.' );
		}
		if ( 'attachment' === get_post_type( $id ) ) {
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
		delete_option( 'cas_price_course_lock_' . $id );
	}
	foreach ( $ledger['users'] as $login => $id ) {
		$user = get_userdata( $id );
		if ( ! $user || $user->user_login !== $login || 0 !== strpos( $login, 'w1builder' ) ) {
			throw new RuntimeException( 'Synthetic user identity changed; stop cleanup.' );
		}
		wp_delete_user( $id );
	}
	if ( ! empty( $ledger['upload_dir'] ) && is_dir( $ledger['upload_dir'] ) && ! rmdir( $ledger['upload_dir'] ) ) {
		throw new RuntimeException( 'Fixture upload directory is not empty; inspect it rather than deleting recursively.' );
	}
	delete_option( $key );
	echo wp_json_encode( array( 'cleanup' => 'fixture records removed; Review must also remove native-created drafts/tokens it records' ) );
	return;
}
if ( 'setup' !== $mode || $ledger || ! function_exists( 'cas_builder_is_editor' ) || ! function_exists( 'tutor_time' ) ) {
	throw new RuntimeException( 'Wrong mode, existing fixture ledger, or missing builder/Tutor dependency.' );
}
foreach ( array( 'w1builderowner', 'w1builderother', 'w1buildersub' ) as $login ) {
	if ( username_exists( $login ) ) {
		throw new RuntimeException( 'Synthetic username already exists; do not overwrite it.' );
	}
}
$ledger = array( 'users' => array(), 'posts' => array() );
add_option( $key, $ledger, '', false );
$output = array( 'users' => array(), 'courses' => array(), 'media' => array() );
foreach ( array( 'w1builderowner' => 'cas_content', 'w1builderother' => 'cas_content', 'w1buildersub' => 'subscriber' ) as $login => $role ) {
	$pw = wp_generate_password( 32, false );
	$id = wp_create_user( $login, $pw, $login . '@example.test' );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$ledger['users'][ $login ] = $id;
	update_option( $key, $ledger, false );
	( new WP_User( $id ) )->set_role( $role );
	if ( 'cas_content' === $role ) {
		// Same REAL metadata values as Utils::approve_instructor(); do not call it (it adds a WP role).
		update_user_meta( $id, '_is_tutor_instructor', tutor_time() );
		update_user_meta( $id, '_tutor_instructor_status', 'approved' );
	}
	$output['users'][ $login ] = array( 'id' => $id, 'password' => $pw, 'role' => $role );
}
$owner = $ledger['users']['w1builderowner'];
$other = $ledger['users']['w1builderother'];
foreach ( array( 'own_paid' => array( $owner, 'paid' ), 'other_paid' => array( $other, 'paid' ), 'own_free' => array( $owner, 'free' ) ) as $name => $spec ) {
	$id = wp_insert_post( array( 'post_type' => 'courses', 'post_status' => 'draft', 'post_title' => 'W1 builder ' . $name, 'post_author' => $spec[0] ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-builder', true );
	$ledger['posts'][] = $id;
	update_option( $key, $ledger, false );
	update_post_meta( $id, '_tutor_course_price_type', $spec[1] );
	update_post_meta( $id, 'tutor_course_price', 'paid' === $spec[1] ? '50' : '0' );
	update_post_meta( $id, 'tutor_course_sale_price', '0' );
	// Course::save_course_meta() adds the real author link on save_post_courses; no invented linkage.
	$links = array_map( 'intval', get_user_meta( $spec[0], '_tutor_instructor_course_id', false ) );
	if ( ! in_array( $id, $links, true ) ) { throw new RuntimeException( 'Tutor did not create its native own-course linkage.' ); }
	$output['courses'][ $name ] = array( 'id' => $id, 'author' => $spec[0], 'type' => $spec[1], 'native_link' => true );
}
$course = $output['courses']['own_paid']['id'];
$parent = $course;
foreach ( array( 'topics', 'lesson' ) as $type ) {
	$id = wp_insert_post( array( 'post_type' => $type, 'post_status' => 'publish', 'post_parent' => $parent, 'post_title' => 'W1 builder ' . $type, 'post_author' => $owner ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-builder', true );
	$ledger['posts'][] = $id;
	update_option( $key, $ledger, false );
	$output[ $type ] = $id;
	$parent = $id;
}
$uploads = wp_upload_dir();
$dir = $uploads['basedir'] . '/w1-builder-' . $course;
$ledger['upload_dir'] = $dir;
update_option( $key, $ledger, false );
require_once ABSPATH . 'wp-admin/includes/image.php';
if ( ! wp_mkdir_p( $dir ) ) { throw new RuntimeException( 'Cannot create fixture upload folder.' ); }
foreach ( array( 'notes.pdf', 'reference.png' ) as $name ) {
	$path = $dir . '/' . $name;
	$png = 'reference.png' === $name;
	$bytes = $png ? base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQ0AAAAASUVORK5CYII=' ) : "%PDF-1.4\n% W1 builder synthetic notes\n%%EOF\n";
	if ( false === file_put_contents( $path, $bytes ) ) { throw new RuntimeException( 'Cannot write fixture attachment.' ); }
	$id = wp_insert_attachment( array( 'post_title' => 'W1 builder ' . $name, 'post_mime_type' => $png ? 'image/png' : 'application/pdf', 'post_status' => 'inherit', 'post_author' => $owner ), $path, 0, true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-builder', true );
	$ledger['posts'][] = $id;
	update_option( $key, $ledger, false );
	if ( $png ) {
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );
	}
	$output['media'][ $name ] = array( 'id' => $id, 'author' => $owner, 'route' => home_url( '/?cas_private_file=' . $id ) );
}
update_post_meta( $course, '_tutor_attachments', array( $output['media']['notes.pdf']['id'] ) );
update_post_meta( $output['lesson'], '_tutor_attachments', array( $output['media']['reference.png']['id'] ) );
foreach ( $output['media'] as &$media ) {
	$media['private'] = (bool) get_post_meta( $media['id'], '_cas_private', true );
	$media['current_url'] = wp_get_attachment_url( $media['id'] );
}
unset( $media );
$output['enrolled'] = class_exists( '\\Tutor\\Models\\EnrollmentModel' ) ? (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $course, $owner ) : null;
$output['private_author_access'] = 'KNOWN BLOCKER: current cas-private-files permits admin or enrolled, not author';
$output['tutor_nonce_field'] = tutor()->nonce;
$output['source_hashes'] = array();
foreach ( array( 'cas-content-builder', 'cas-price-gate', 'cas-private-files', 'cas-content-role' ) as $module ) {
	$output['source_hashes'][ $module ] = hash_file( 'sha256', get_stylesheet_directory() . '/inc/' . $module . '.php' );
}
$output['stored_tutor_option_sha256'] = hash( 'sha256', (string) $GLOBALS['wpdb']->get_var( "SELECT option_value FROM {$GLOBALS['wpdb']->options} WHERE option_name='tutor_option'" ) );
echo wp_json_encode( $output, JSON_PRETTY_PRINT );
