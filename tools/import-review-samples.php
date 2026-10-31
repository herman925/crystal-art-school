<?php
/** Explicit one-time import for the open webpage-review requests of 2026-11-01.
 * Run: wp eval-file /path/import-review-samples.php /path/review-assets --user=cas_dev
 * Existing real course content is untouched. Course 269 stays private; wall samples say Demo.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! current_user_can( 'manage_options' ) || empty( $args[0] ) ) {
	throw new RuntimeException( 'Run explicitly through WP-CLI as an administrator with the sample assets directory.' );
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$assets = realpath( $args[0] );
if ( ! $assets || ! is_file( $assets . '/demo-notes.pdf' ) ) { throw new RuntimeException( 'Sample assets missing.' ); }
function cas_review_check( $value ) {
	if ( is_wp_error( $value ) || ! $value ) { throw new RuntimeException( is_wp_error( $value ) ? $value->get_error_message() : 'Import failed.' ); }
	return $value;
}
function cas_review_find( $type, $key ) {
	$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'numberposts' => 1, 'meta_key' => '_cas_review_sample', 'meta_value' => $key, 'fields' => 'ids' ) );
	return $posts ? (int) $posts[0] : 0;
}
function cas_review_image( $file, $key, $title, $parent = 0 ) {
	$id = cas_review_find( 'attachment', $key );
	if ( $id ) { return $id; }
	if ( ! is_file( $file ) ) { throw new RuntimeException( 'Missing sample file: ' . basename( $file ) ); }
	$temp = wp_tempnam( basename( $file ) );
	cas_review_check( copy( $file, $temp ) );
	$id = cas_review_check( media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $temp ), $parent, $title ) );
	update_post_meta( $id, '_cas_review_sample', $key );
	return $id;
}
function cas_review_post( $type, $key, $title, $parent = 0, $order = 0, $status = 'publish', $existing = 0 ) {
	$id = cas_review_find( $type, $key ) ?: $existing;
	$id = cas_review_check( wp_insert_post( array( 'ID' => $id, 'post_type' => $type, 'post_title' => $title, 'post_status' => $status, 'post_author' => get_current_user_id(), 'post_parent' => $parent, 'menu_order' => $order ), true ) );
	update_post_meta( $id, '_cas_review_sample', $key );
	return $id;
}
$receipt = array( 'terms' => array(), 'about_photos' => array(), 'showcase' => array(), 'course' => 269, 'lessons' => array(), 'media' => array() );
// Preserve every old scalar value; native terms become the editable source of truth.
foreach ( cas_content_term_config() as $taxonomy => $config ) {
	$count = 0;
	foreach ( get_posts( array( 'post_type' => $config[0], 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) ) as $id ) {
		if ( metadata_exists( 'post', $id, '_cas_terms_' . $taxonomy ) ) { continue; }
		$names = cas_content_term_parts( (string) get_post_meta( $id, $config[1], true ) );
		cas_review_check( wp_set_object_terms( $id, $names, $taxonomy ) ?: array( 'empty' ) );
		++$count;
	}
	$receipt['terms'][ $taxonomy ] = $count;
}
// Turn all currently shipped About carousel photographs into editable records.
foreach ( cas_about_photo_sets() as $set_key => $set ) {
	$existing = get_posts( array( 'post_type' => CAS_ABOUT_PHOTO_POST_TYPE, 'post_status' => 'publish', 'numberposts' => 1, 'meta_key' => CAS_ABOUT_PHOTO_META_SET, 'meta_value' => $set_key ) );
	if ( $existing && ! get_post_meta( $existing[0]->ID, '_cas_review_sample', true ) ) { throw new RuntimeException( 'An About set already has owner content; review it before importing.' ); }
	foreach ( $set['files'] as $index => $file ) {
		$key = 'about-' . $file;
		$photo = cas_review_post( CAS_ABOUT_PHOTO_POST_TYPE, $key, $set['alt'] . ' ' . ( $index + 1 ), 0, $index );
		$attachment = cas_review_image( get_stylesheet_directory() . '/img/about/' . $file, $key . '-image', $set['alt'] . ' ' . ( $index + 1 ), $photo );
		cas_review_check( set_post_thumbnail( $photo, $attachment ) ?: has_post_thumbnail( $photo ) );
		update_post_meta( $photo, CAS_ABOUT_PHOTO_META_SET, $set_key );
		$receipt['about_photos'][] = $photo;
	}
}
// Use the prototype photographs and titles, with a registered Demo Student credit.
$student = get_user_by( 'login', 'cas_demo_student_20261031' );
if ( ! $student || ! in_array( 'subscriber', $student->roles, true ) ) { throw new RuntimeException( 'Expected existing demo student missing.' ); }
$pieces = array(
	array( 'Low tide, late light', 'Watercolour' ), array( 'The allotment shed', 'Watercolour' ),
	array( 'Tram stop, morning', 'Ink and wash' ), array( 'Two pears and a jug', 'Watercolour' ),
	array( 'Rooftops after rain', 'Gouache' ), array( 'The long field', 'Watercolour' ),
	array( 'Harbour wall', 'Watercolour' ), array( 'Winter hedgerow', 'Ink and wash' ),
	array( 'Kitchen window, six', 'Gouache' ), array( 'Estuary, low cloud', 'Watercolour' ),
);
$placeholder = get_post( 323 );
if ( ! $placeholder || 'cas_showcase' !== $placeholder->post_type || ( ! str_starts_with( $placeholder->post_title, 'Demo' ) ) ) { throw new RuntimeException( 'Showcase placeholder changed.' ); }
foreach ( $pieces as $index => $piece ) {
	$key = 'showcase-' . ( $index + 1 );
	$id = cas_review_post( 'cas_showcase', $key, 'Demo — ' . $piece[0], 0, $index, 'publish', 0 === $index ? 323 : 0 );
	$attachment = cas_review_image( $assets . '/showcase/sw' . sprintf( '%02d', $index + 1 ) . '.jpg', $key . '-image', 'Demo sample — ' . $piece[0], $id );
	cas_review_check( set_post_thumbnail( $id, $attachment ) ?: has_post_thumbnail( $id ) );
	update_post_meta( $id, CAS_SHOWCASE_META_USER, (int) $student->ID );
	cas_review_check( wp_set_object_terms( $id, cas_content_term_parts( $piece[1] ), 'cas_showcase_medium' ) );
	$receipt['showcase'][] = $id;
}
$course = get_post( 269 );
if ( ! $course || 'courses' !== $course->post_type || 'private' !== $course->post_status || 2 !== (int) $course->post_author ) { throw new RuntimeException( 'Private test course identity changed.' ); }
cas_review_check( wp_update_post( array( 'ID' => 269, 'post_title' => 'Demo — Watercolour Practice Studio', 'post_content' => '<p>Private sample course for reviewing the course and lesson editors. All notes, reference files and video slates are demonstration material.</p><h3>What you will practise</h3><ul><li>Preparing tools and making washes</li><li>Building a small colour study</li></ul>' ), true ) );
update_post_meta( 269, '_cas_review_sample', 'course' );
$notes = cas_review_image( $assets . '/demo-notes.pdf', 'course-notes', 'Demo — Practice notes (PDF)', 269 );
$reference = cas_review_image( $assets . '/demo-reference.jpg', 'course-reference', 'Demo — Reference photograph', 269 );
$video = cas_review_image( $assets . '/demo-video.mp4', 'course-video', 'Demo — Five-second lesson video', 269 );
$receipt['media'] = array( $notes, $reference, $video );
cas_review_check( update_post_meta( 269, '_tutor_attachments', array( $notes ) ) ?: get_post_meta( 269, '_tutor_attachments', true ) === array( $notes ) );
$topics = array(
	array( 'Demo — Getting Started', array( 'Demo — Tools and Materials', 'Demo — Light and Dark Washes' ) ),
	array( 'Demo — Painting a Small Study', array( 'Demo — Working from a Reference', 'Demo — Finishing and Reviewing' ) ),
);
foreach ( $topics as $ti => $topic ) {
	$topic_id = cas_review_post( 'topics', 'topic-' . $ti, $topic[0], 269, $ti, 'publish', 0 === $ti ? 270 : 0 );
	foreach ( $topic[1] as $li => $title ) {
		$id = cas_review_post( 'lesson', 'lesson-' . $ti . '-' . $li, $title, $topic_id, $li, 'publish', 0 === $ti && 0 === $li ? 271 : 0 );
		cas_review_check( wp_update_post( array( 'ID' => $id, 'post_content' => '<p>Demo lesson: explore the video, reference photograph and downloadable notes in this editor.</p><h3>Practice</h3><p>Make a small light-to-dark wash, compare the edges, then add one more layer after it dries.</p>' ), true ) );
		update_post_meta( $id, '_tutor_course_id_for_lesson', '269' );
		update_post_meta( $id, '_video', array( 'source' => 'html5', 'source_video_id' => $video, 'runtime' => array( 'hours' => '0', 'minutes' => '0', 'seconds' => '5' ) ) );
		update_post_meta( $id, '_tutor_attachments', array( $notes, $reference ) );
		cas_review_check( cas_private_is( $notes ) && cas_private_is( $reference ) );
		$receipt['lessons'][] = $id;
	}
}
WP_CLI::line( wp_json_encode( $receipt ) );
