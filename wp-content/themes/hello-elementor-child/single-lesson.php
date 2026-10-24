<?php
/**
 * Single lesson. Tutor's own learning-area template is replaced because its layout is not
 * the agreed design; the swap happens in inc/cas-lesson.php via template_include, and only
 * for a lesson the visitor is allowed to watch.
 *
 * The two actions below are the ones Tutor's own template fires before it prints anything.
 * Tutor Pro's content-drip and similar add-ons listen for them, so they run here too.
 */

defined( 'ABSPATH' ) || exit;

$cas_lesson_id     = get_queried_object_id();
$cas_lesson_course = cas_lesson_course_id( $cas_lesson_id );

do_action( 'tutor/course/single/content/before/all', $cas_lesson_course, $cas_lesson_id );

/* Stock behaviour, kept: a student who has finished every item is marked as completed the
   moment they open a lesson page, and Tutor's review prompt is queued for their next visit
   to a Tutor screen. */
$cas_user_id = get_current_user_id();
$cas_author_preview = cas_lesson_author_course( $cas_lesson_id ) && ! \Tutor\Models\EnrollmentModel::is_enrolled( $cas_lesson_course, $cas_user_id );

if ( ! $cas_author_preview && $cas_user_id && \Tutor\Models\CourseModel::can_autocomplete_course( $cas_lesson_course, $cas_user_id ) ) {
	if ( \Tutor\Models\CourseModel::mark_course_as_completed( $cas_lesson_course, $cas_user_id ) ) {
		\TUTOR\Course::set_review_popup_data( $cas_user_id, $cas_lesson_course );
	}
}

get_header();

echo cas_render_lesson(); // phpcs:ignore WordPress.Security.EscapeOutput

get_footer();
