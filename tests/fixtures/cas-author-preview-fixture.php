<?php
/** Review-only author preview fixture; WP loaded, existing local admin, explicit setup|cleanup. */
if ( ! defined( 'ABSPATH' ) || '1' !== getenv( 'CAS_RUNTIME_TESTS' ) || home_url() !== getenv( 'CAS_PREVIEW_FIXTURE_HOME' )
	|| ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) || ! current_user_can( 'manage_options' ) ) {
	throw new RuntimeException( 'Requires the explicitly acknowledged LOCAL Review site and its administrator.' );
}
$key = 'cas_w1_author_preview_fixture';
$ledger = get_option( $key );
if ( 'cleanup' === ( $args[0] ?? '' ) ) {
	if ( ! is_array( $ledger ) ) { throw new RuntimeException( 'No fixture ledger; nothing removed.' ); }
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( array_reverse( $ledger['posts'] ) as $id ) {
		if ( 'w1-author-preview' !== get_post_meta( $id, '_cas_fixture_owner', true ) ) { throw new RuntimeException( 'Fixture identity drift; stop cleanup.' ); }
		if ( 'attachment' === get_post_type( $id ) ) { wp_delete_attachment( $id, true ); }
		else { wp_delete_post( $id, true ); }
	}
	foreach ( $ledger['users'] as $login => $id ) {
		$user = get_userdata( $id );
		if ( ! $user || $user->user_login !== $login ) { throw new RuntimeException( 'Synthetic account identity drift.' ); }
		wp_delete_user( $id );
	}
	if ( ! empty( $ledger['dir'] ) && is_dir( $ledger['dir'] ) && ! rmdir( $ledger['dir'] ) ) { throw new RuntimeException( 'Owned upload directory is not empty; inspect it.' ); }
	delete_option( $key );
	echo wp_json_encode( array( 'cleanup' => true ) );
	return;
}
if ( 'setup' !== ( $args[0] ?? '' ) || $ledger || ! function_exists( 'cas_content_author_lesson_course' )
	|| ! get_role( 'cas_content' ) || ! get_role( 'cas_content' )->has_cap( 'edit_tutor_courses' ) ) { throw new RuntimeException( 'Wrong mode, existing ledger or missing preview/role dependency.' ); }
$roles = array( 'w1previewowner' => 'cas_content', 'w1previewother' => 'cas_content', 'w1previewstudent' => 'subscriber', 'w1previewoutsider' => 'subscriber', 'w1previewinstructor' => 'subscriber', 'w1previeweditor' => 'editor' );
foreach ( $roles as $login => $role ) {
	if ( username_exists( $login ) ) { throw new RuntimeException( 'Fixture login exists; never overwrite it.' ); }
}
$ledger = array( 'posts' => array(), 'users' => array() );
add_option( $key, $ledger, '', false );
$out = array( 'home' => home_url(), 'admin' => get_current_user_id(), 'users' => array(), 'courses' => array(), 'lessons' => array(), 'files' => array() );
foreach ( $roles as $login => $role ) {
	$pw = wp_generate_password( 32, false );
	$id = wp_create_user( $login, $pw, $login . '@example.test' );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$ledger['users'][ $login ] = $id;
	update_option( $key, $ledger, false );
	( new WP_User( $id ) )->set_role( $role );
	$out['users'][ $login ] = array( 'id' => $id, 'password' => $pw, 'role' => $role );
}
// Owner deliberately has NO instructor metadata; metadata-only negative is a different subscriber.
update_user_meta( $ledger['users']['w1previewinstructor'], '_is_tutor_instructor', tutor_time() );
update_user_meta( $ledger['users']['w1previewinstructor'], '_tutor_instructor_status', 'approved' );
function w1_preview_post( $type, $title, $author, $parent = 0, $status = 'publish' ) {
	global $key, $ledger;
	$id = wp_insert_post( array( 'post_type' => $type, 'post_title' => 'W1 author ' . $title, 'post_content' => 'W1_AUTHOR_CONTENT_' . strtoupper( str_replace( ' ', '_', $title ) ), 'post_status' => $status, 'post_author' => $author, 'post_parent' => $parent ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-author-preview', true );
	$ledger['posts'][] = (int) $id;
	update_option( $key, $ledger, false );
	return (int) $id;
}
$owner = $ledger['users']['w1previewowner'];
$other = $ledger['users']['w1previewother'];
foreach ( array( 'own' => $owner, 'draft' => $owner, 'foreign' => $other ) as $name => $author ) {
	$id = w1_preview_post( 'courses', 'course ' . $name, $author, 0, 'draft' );
	update_post_meta( $id, '_tutor_course_price_type', 'paid' );
	update_post_meta( $id, 'tutor_course_price', '50' );
	update_post_meta( $id, 'tutor_course_sale_price', '0' );
	if ( 'draft' !== $name ) { wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) ); }
	$out['courses'][ $name ] = $id;
	$out['topics'][ $name ] = w1_preview_post( 'topics', 'topic ' . $name, $author, $id, 'draft' === $name ? 'draft' : 'publish' );
}
foreach ( array( 'protected' => 'own', 'public_preview' => 'own', 'draft' => 'draft', 'foreign' => 'foreign', 'broken' => null ) as $name => $topic ) {
	// Foreign lesson is authored by owner: lesson authorship alone MUST NOT grant it.
	$id = w1_preview_post( 'lesson', 'lesson ' . $name, 'foreign' === $name || 'broken' === $name ? $owner : $out['admin'], $topic ? $out['topics'][ $topic ] : 0, 'draft' === $name ? 'draft' : 'publish' );
	update_post_meta( $id, '_is_preview', 'public_preview' === $name ? '1' : '0' );
	$marker = 'cas_author_fixture_stage=' . $name;
	update_post_meta( $id, '_video', array( 'source' => 'embedded', 'source_embedded' => '<iframe src="' . esc_url( home_url( '/wp-includes/images/blank.gif?' . $marker ) ) . '"></iframe>' ) );
	$out['lessons'][ $name ] = array( 'id' => $id, 'marker' => $marker, 'url' => get_permalink( $id ), 'query_url' => add_query_arg( array( 'post_type' => 'lesson', 'p' => $id ), home_url( '/' ) ), 'preview_url' => get_preview_post_link( $id ) );
}
$out['closed_page'] = w1_preview_post( 'page', 'closed page', $owner );
$uploads = wp_upload_dir();
$ledger['dir'] = $uploads['basedir'] . '/w1-author-preview-' . $out['courses']['own'];
update_option( $key, $ledger, false );
if ( ! wp_mkdir_p( $ledger['dir'] ) ) { throw new RuntimeException( 'Cannot create fixture upload directory.' ); }
foreach ( array( 'notes', 'reference', 'foreign' ) as $name ) {
	$png = 'reference' === $name;
	$path = $ledger['dir'] . '/' . $name . ( $png ? '.png' : '.pdf' );
	$bytes = $png ? base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQ0AAAAASUVORK5CYII=' ) : "%PDF-1.4\n% W1 author synthetic notes\n%%EOF\n";
	if ( false === file_put_contents( $path, $bytes ) ) { throw new RuntimeException( 'Cannot create fixture bytes.' ); }
	$id = wp_insert_attachment( array( 'post_title' => 'W1_AUTHOR_FILE_' . strtoupper( $name ), 'post_status' => 'inherit', 'post_mime_type' => $png ? 'image/png' : 'application/pdf', 'post_author' => $owner ), $path, 0, true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	add_post_meta( $id, '_cas_fixture_owner', 'w1-author-preview', true );
	$ledger['posts'][] = (int) $id;
	update_option( $key, $ledger, false );
	if ( $png ) { wp_update_attachment_metadata( $id, array( 'width' => 1, 'height' => 1, 'file' => _wp_relative_upload_path( $path ), 'sizes' => array() ) ); }
	$out['files'][ $name ] = array( 'id' => (int) $id, 'title' => 'W1_AUTHOR_FILE_' . strtoupper( $name ), 'url' => cas_private_url( $id ) );
}
update_post_meta( $out['courses']['own'], '_tutor_attachments', array( $out['files']['notes']['id'] ) );
foreach ( array( 'protected', 'public_preview' ) as $name ) { update_post_meta( $out['lessons'][ $name ]['id'], '_tutor_attachments', array( $out['files']['reference']['id'] ) ); }
update_post_meta( $out['courses']['foreign'], '_tutor_attachments', array( $out['files']['foreign']['id'] ) );
w1_preview_post( 'tutor_enrolled', 'student control', $ledger['users']['w1previewstudent'], $out['courses']['own'], 'completed' );
$out['private_dependency'] = function_exists( 'cas_private_author_can_download' );
$out['owner_enrolled'] = (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $out['courses']['own'], $owner );
$out['owner_instructor_meta'] = (bool) get_user_meta( $owner, '_is_tutor_instructor', true );
$out['post_ids'] = $ledger['posts'];
$out['source_hashes'] = array();
foreach ( array( 'cas-content-role', 'cas-lesson', 'cas-private-files' ) as $module ) { $out['source_hashes'][ $module ] = hash_file( 'sha256', get_stylesheet_directory() . '/inc/' . $module . '.php' ); }
$out['source_hashes']['single-lesson'] = hash_file( 'sha256', get_stylesheet_directory() . '/single-lesson.php' );
echo wp_json_encode( $out, JSON_PRETTY_PRINT );
