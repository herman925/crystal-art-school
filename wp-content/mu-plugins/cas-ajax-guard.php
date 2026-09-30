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
