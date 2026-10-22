<?php
/** Review-only synthetic setup/cleanup; run with WP loaded and $args[0]=setup|cleanup. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'CAS_RUNTIME_TESTS' )
	|| ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true )
	|| home_url() !== getenv( 'CAS_PRIVATE_FIXTURE_HOME' ) || ! current_user_can( 'manage_options' ) ) {
	throw new RuntimeException( 'Requires an explicitly acknowledged localhost synthetic site and its administrator.' );
}
$key = 'cas_w1_private_author_fixture';
$ledger = get_option( $key );
if ( 'cleanup' === ( $args[0] ?? '' ) ) {
	if ( ! is_array( $ledger ) ) { throw new RuntimeException( 'No fixture ledger; nothing deleted.' ); }
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_reverse( $ledger['posts'] ) as $id ) {
		if ( 'w1-private-author' !== get_post_meta( $id, '_cas_fixture_owner', true ) ) { throw new RuntimeException( 'Fixture ownership drift; stop cleanup.' ); }
		if ( 'attachment' === get_post_type( $id ) ) { wp_delete_attachment( $id, true ); }
		else { wp_delete_post( $id, true ); }
	}
	foreach ( $ledger['users'] as $login => $id ) {
		$user = get_userdata( $id );
		if ( ! $user || $user->user_login !== $login ) { throw new RuntimeException( 'Synthetic user identity drift.' ); }
		wp_delete_user( $id );
	}
	if ( ! empty( $ledger['dir'] ) && is_dir( $ledger['dir'] ) && ! rmdir( $ledger['dir'] ) ) { throw new RuntimeException( 'Owned upload directory is not empty; inspect it.' ); }
	delete_option( $key );
	echo wp_json_encode( array( 'cleanup' => 'ledger-owned synthetic records removed' ) );
	return;
}
if ( 'setup' !== ( $args[0] ?? '' ) || $ledger || ! function_exists( 'cas_private_apply_reference_manifest' )
	|| ! get_role( 'cas_content' ) || ! get_role( 'cas_content' )->has_cap( 'edit_tutor_courses' ) ) {
	throw new RuntimeException( 'Wrong mode, existing ledger, or missing private-author / reviewed course-role dependency.' );
}
$roles = array( 'w1privateowner' => 'cas_content', 'w1privateother' => 'cas_content', 'w1privatestudent' => 'subscriber',
	'w1privateoutsider' => 'subscriber', 'w1privateeditor' => 'editor', 'w1privateinstructor' => 'subscriber' );
foreach ( $roles as $login => $role ) {
	if ( username_exists( $login ) ) { throw new RuntimeException( 'Fixture username exists; never overwrite it.' ); }
}
$ledger = array( 'posts' => array(), 'users' => array() );
add_option( $key, $ledger, '', false );
$out = array( 'admin' => get_current_user_id(), 'users' => array(), 'courses' => array(), 'files' => array() );
foreach ( $roles as $login => $role ) {
	$pw = wp_generate_password( 32, false );
	$id = wp_create_user( $login, $pw, $login . '@example.test' );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$ledger['users'][ $login ] = $id;
	update_option( $key, $ledger, false );
	( new WP_User( $id ) )->set_role( $role );
	$out['users'][ $login ] = array( 'id' => $id, 'password' => $pw, 'role' => $role );
}
// Prove that REAL instructor metadata alone never grants the new author-download path.
update_user_meta( $ledger['users']['w1privateinstructor'], '_is_tutor_instructor', tutor_time() );
update_user_meta( $ledger['users']['w1privateinstructor'], '_tutor_instructor_status', 'approved' );
function w1_private_record( $id ) {
	global $key, $ledger;
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-private-author', true );
	$ledger['posts'][] = (int) $id;
	update_option( $key, $ledger, false );
	return (int) $id;
}
$owner = $ledger['users']['w1privateowner'];
$other = $ledger['users']['w1privateother'];
foreach ( array( 'own' => $owner, 'other' => $other, 'legacy' => $owner ) as $name => $author ) {
	$id = w1_private_record( wp_insert_post( array( 'post_type' => 'courses', 'post_status' => 'draft', 'post_title' => 'W1 private ' . $name, 'post_author' => $author ), true ) );
	$out['courses'][ $name ] = $id;
}
$topic = w1_private_record( wp_insert_post( array( 'post_type' => 'topics', 'post_status' => 'publish', 'post_parent' => $out['courses']['own'], 'post_title' => 'W1 private topic', 'post_author' => $owner ), true ) );
$lesson = w1_private_record( wp_insert_post( array( 'post_type' => 'lesson', 'post_status' => 'publish', 'post_parent' => $topic, 'post_title' => 'W1 private lesson', 'post_author' => $owner ), true ) );
$foreign_topic = w1_private_record( wp_insert_post( array( 'post_type' => 'topics', 'post_status' => 'publish', 'post_parent' => $out['courses']['other'], 'post_title' => 'W1 private other topic', 'post_author' => $other ), true ) );
$out['topic'] = $topic;
$out['lesson'] = $lesson;
$out['foreign_topic'] = $foreign_topic;
$uploads = wp_upload_dir();
$ledger['dir'] = $uploads['basedir'] . '/w1-private-author-' . $out['courses']['own'];
update_option( $key, $ledger, false );
if ( ! wp_mkdir_p( $ledger['dir'] ) ) { throw new RuntimeException( 'Cannot create fixture upload directory.' ); }
foreach ( array( 'notes', 'reference', 'foreign', 'owned_foreign', 'detached', 'legacy', 'fresh_upload' ) as $name ) {
	$png = 'reference' === $name;
	$path = $ledger['dir'] . '/' . $name . ( $png ? '.png' : '.pdf' );
	$bytes = $png ? base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQ0AAAAASUVORK5CYII=' ) : "%PDF-1.4\n% synthetic W1 private " . $name . "\n%%EOF\n";
	if ( false === file_put_contents( $path, $bytes ) ) { throw new RuntimeException( 'Cannot write fixture bytes.' ); }
	$id = w1_private_record( wp_insert_attachment( array( 'post_title' => 'W1 private ' . $name, 'post_mime_type' => $png ? 'image/png' : 'application/pdf', 'post_status' => 'inherit', 'post_author' => 'foreign' === $name ? $other : $owner ), $path, 0, true ) );
	if ( $png ) { wp_update_attachment_metadata( $id, array( 'width' => 1, 'height' => 1, 'file' => _wp_relative_upload_path( $path ), 'sizes' => array() ) ); }
	$out['files'][ $name ] = array( 'id' => $id, 'url' => cas_private_url( $id ) );
}
update_post_meta( $out['courses']['own'], '_tutor_attachments', array( $out['files']['notes']['id'] ) );
update_post_meta( $lesson, '_tutor_attachments', array( $out['files']['reference']['id'] ) );
update_post_meta( $out['courses']['other'], '_tutor_attachments', array( $out['files']['foreign']['id'], $out['files']['owned_foreign']['id'] ) );
update_post_meta( $out['courses']['legacy'], '_tutor_attachments', array( $out['files']['legacy']['id'] ) );
cas_private_secure_attachment( $out['files']['detached']['id'], array( $out['courses']['own'] ) );
delete_post_meta( $out['courses']['legacy'], CAS_PRIVATE_META_REFS );
$out['legacy_manifest'] = cas_private_reference_snapshot( $out['courses']['legacy'] );
if ( ! $out['legacy_manifest'] ) { throw new RuntimeException( 'Fixture migration failed; check the synthetic private root is writable.' ); }
$enrolment = w1_private_record( wp_insert_post( array( 'post_type' => 'tutor_enrolled', 'post_status' => 'completed', 'post_parent' => $out['courses']['own'], 'post_author' => $ledger['users']['w1privatestudent'], 'post_title' => 'W1 private enrolment' ), true ) );
foreach ( $out['files'] as &$file ) {
	$file['private'] = cas_private_is( $file['id'] );
	$file['sha256'] = hash_file( 'sha256', get_attached_file( $file['id'] ) );
}
unset( $file );
$out['module_sha256'] = hash_file( 'sha256', get_stylesheet_directory() . '/inc/cas-private-files.php' );
$out['owner_has_no_instructor_meta'] = ! get_user_meta( $owner, '_is_tutor_instructor', true );
echo wp_json_encode( $out, JSON_PRETTY_PRINT );
