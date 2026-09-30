<?php
/**
 * Deny risky Tutor LMS ajax actions to non-administrators (issue #101, map #99).
 *
 * Tutor 4.1.0 and Tutor Pro 4.1.0 check only a nonce on these actions. The nonce is printed to
 * every logged-in user, so a student account can call them. The first one is live today. The
 * others are dormant while their Pro addon or setting is off, and stay blocked if it is turned on.
 * Actions that instructors or students use on purpose are not listed here.
 * Each denial is written to the audit log (cas-audit-log.php): action name and user id only.
 */
defined( 'ABSPATH' ) || exit;

const CAS_AJAX_GUARD_ADMIN_ONLY = array(
	'delete_tutor_license',                 // tutor-pro/updater/update.php:167 - deletes the licence option. LIVE.
	'tutor_admin_student_list_bulk_action', // tutor-report Report.php:369 - deletes any student's enrolments.
	'tutor_remove_device_manually',         // DeviceManagement.php:726 - deletes any usermeta row by id.
	'tutor_pro_gift_enrollment',            // GiftEnrollment.php:51 - free enrolment in any course.
	'tutor_remove_assignment_attempt',      // Assignments.php:318 - deletes any comment row by id.
	'tutor_membership_plan_duplicate',      // MembershipController.php:254 - no nonce, no capability.
	'import_gradebook_sample_data',         // GradeBook.php:895 - inserts rows on every call.
	'tutor_pn_save_subscription',           // tutor-notifications Push_Notification.php:48 - no nonce, stores any push URL. Addon off.
	'tutor_save_notification_preference',   // NotificationPreference.php:59 - unbounded rows per user. Addon off.
);

/* Tutor form posts (not ajax) that the site does not use: the school takes no instructors.
   Tutor handles both on template_redirect at priority 10 (tutor/classes/Instructor.php:73-74).
   A student can post the first one today and Tutor then notifies or emails every administrator. */
const CAS_ACTION_GUARD_ADMIN_ONLY = array(
	'tutor_apply_instructor',    // Instructor::apply_instructor - any logged-in user becomes a pending instructor.
	'tutor_register_instructor', // Instructor::register_instructor - instructor sign-up form, needs users_can_register.
);

/* Priority 0 runs before the audit log hook (priority 1) and before any wp_ajax_* handler. */
add_action( 'admin_init', function () {
	if ( ! wp_doing_ajax() || empty( $_REQUEST['action'] ) || ! is_string( $_REQUEST['action'] ) ) {
		return;
	}
	$action = $_REQUEST['action'];
	if ( ! in_array( $action, CAS_AJAX_GUARD_ADMIN_ONLY, true ) || current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( 'ajax_denied', substr( sanitize_key( $action ), 0, 80 ) );
	}
	wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
}, 0 );

/* Priority 0 runs before Tutor's own template_redirect handlers (priority 10). */
add_action( 'template_redirect', function () {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' )
		|| empty( $_POST['tutor_action'] ) || ! is_string( $_POST['tutor_action'] ) ) {
		return;
	}
	// Same cleaning as Tutor's Input::post() (data_sanitizer), so padded or tagged values match too.
	$action = sanitize_text_field( wp_unslash( $_POST['tutor_action'] ) );
	if ( ! in_array( $action, CAS_ACTION_GUARD_ADMIN_ONLY, true ) || current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( 'action_denied', substr( sanitize_key( $action ), 0, 80 ) );
	}
	wp_die( 'Not allowed.', 'Not allowed', array( 'response' => 403 ) );
}, 0 );
