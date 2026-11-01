<?php
/** The approved restart clears completion for the current student and one enrolled course. */
defined( 'ABSPATH' ) || exit;

/**
 * @param int $course_id Selected course, never a caller-supplied student identity.
 * @return string|WP_Error First published lesson URL, or a refusal/failure.
 */
function cas_lesson_restart_course( $course_id ) {
	$user_id = get_current_user_id();
	if ( ! $user_id || ! class_exists( '\Tutor\Models\EnrollmentModel' )
		|| 'courses' !== get_post_type( $course_id )
		|| ! in_array( get_post_status( $course_id ), array( 'publish', 'private' ), true )
		|| ! \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $user_id ) ) {
		return new WP_Error( 'cas_restart_forbidden', 'You must be enrolled to restart this course.' );
	}

	global $wpdb;
	$ids = tutor_utils()->get_course_content_ids_by( tutor()->lesson_post_type, tutor()->course_post_type, $course_id );
	if ( $wpdb->last_error || ! is_array( $ids ) || ! $ids ) {
		return new WP_Error( 'cas_restart_unavailable', 'This course cannot be restarted right now.' );
	}
	$lesson_ids = array_map( 'intval', $ids );
	foreach ( $lesson_ids as $lesson_id ) {
		$topic_id = wp_get_post_parent_id( $lesson_id );
		if ( 'lesson' !== get_post_type( $lesson_id ) || 'topics' !== get_post_type( $topic_id )
			|| (int) $course_id !== wp_get_post_parent_id( $topic_id ) ) {
			return new WP_Error( 'cas_restart_unavailable', 'This course cannot be restarted right now.' );
		}
	}
	$outline = cas_lesson_outline( $course_id );
	$first   = $outline['items'][0] ?? null;
	if ( $wpdb->last_error || ! $first || ! in_array( $first['id'], $lesson_ids, true ) || ! $first['url'] ) {
		return new WP_Error( 'cas_restart_unavailable', 'This course has no available first lesson.' );
	}
	$comments = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT comment_ID FROM {$wpdb->comments} WHERE user_id = %d AND comment_post_ID = %d AND comment_type = %s AND comment_agent = %s",
			$user_id, $course_id, 'course_completed', 'TutorLMSPlugin'
		)
	);
	if ( $wpdb->last_error ) {
		return new WP_Error( 'cas_restart_unavailable', 'This course cannot be restarted right now.' );
	}

	// Tutor's bulk retake also erases quiz attempts and global reading info; retain those and access records.
	foreach ( $lesson_ids as $lesson_id ) {
		$key = '_tutor_completed_lesson_id_' . $lesson_id;
		if ( metadata_exists( 'user', $user_id, $key ) && ! delete_user_meta( $user_id, $key ) ) {
			return new WP_Error( 'cas_restart_unavailable', 'Completion could not be fully cleared. Please try again.' );
		}
	}
	foreach ( $comments as $comment_id ) {
		if ( ! wp_delete_comment( (int) $comment_id, true ) ) {
			return new WP_Error( 'cas_restart_unavailable', 'Completion could not be fully cleared. Please try again.' );
		}
	}
	// Tutor 4.1.3 keeps these values in a request-local cache; invalidate only this student's course.
	\Tutor\Cache\TutorCache::set( 'tutor_get_completed_lesson_count_by_' . $user_id . '_' . implode( '_', $ids ), false );
	\Tutor\Cache\TutorCache::set( "tutor_is_completed_course_{$course_id}_{$user_id}", false );
	return $first['url'];
}

function cas_lesson_handle_restart() {
	nocache_headers();
	$raw   = $_POST['course_id'] ?? null;
	$nonce = $_POST['_cas_restart_nonce'] ?? null;
	$id    = is_string( $raw ) ? filter_var( $raw, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) ) : false;
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! is_user_logged_in() || ! $id
		|| ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'cas_restart_course_' . $id ) ) {
		wp_die( 'Unable to restart this course.', '', array( 'response' => 403 ) );
	}
	$result = cas_lesson_restart_course( $id );
	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 'cas_restart_forbidden' === $result->get_error_code() ? 403 : 500 ) );
	}
	if ( ! wp_safe_redirect( $result, 303 ) ) {
		wp_die( 'Unable to open the first lesson.', '', array( 'response' => 500 ) );
	}
	exit;
}
add_action( 'admin_post_cas_restart_course', 'cas_lesson_handle_restart' );
add_action( 'admin_post_nopriv_cas_restart_course', 'cas_lesson_handle_restart' );
