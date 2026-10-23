<?php
/** W2-3: manually add or remove a student's access to one published Tutor course. */

defined( 'ABSPATH' ) || exit;

const CAS_ENROL_CAP     = 'manage_cas_content';
const CAS_ENROL_PAGE    = 'cas-enrol';
const CAS_ENROL_ACTIONS = array( 'confirm', 'apply' );

function cas_enrol_guard() {
	if ( ! current_user_can( CAS_ENROL_CAP ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'hello-elementor-child' ), '', array( 'response' => 403 ) );
	}
}

function cas_enrol_check_nonce( $action ) {
	if ( ! isset( $_POST['_wpnonce'] ) || ! is_string( $_POST['_wpnonce'] ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	check_admin_referer( $action );
}

function cas_enrol_form_text( $key ) {
	$value = $_POST[ $key ] ?? '';
	return is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : '';
}

function cas_enrol_form_id( $key ) {
	$value = $_POST[ $key ] ?? '';
	return is_scalar( $value ) && ctype_digit( (string) $value ) ? absint( $value ) : 0;
}

function cas_enrol_model() {
	return '\\Tutor\\Models\\EnrollmentModel';
}

/** Read only the user row and raw role marker; do not hydrate arbitrary target usermeta. */
function cas_enrol_student_by_id( $student_id ) {
	global $wpdb;
	$user = $wpdb->get_row( $wpdb->prepare( "SELECT ID, user_login, user_email, display_name FROM {$wpdb->users} WHERE ID = %d", absint( $student_id ) ) );
	if ( ! $user ) {
		return false;
	}

	$cap_key = $wpdb->prefix . 'capabilities';
	$caps = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 1", $user->ID, $cap_key ) );
	$is_subscriber = is_string( $caps ) && preg_match( '/s:10:"subscriber";b:1;/', $caps );
	$has_privileged_role = is_string( $caps ) && preg_match( '/s:\\d+:"(?:administrator|editor|author|shop_manager|tutor_instructor|cas_content)";b:1;/', $caps );
	$has_privileged_cap = is_string( $caps ) && preg_match( '/s:\\d+:"(?:manage_options|edit_users)";b:1;/', $caps );
	$student_flag = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 1", $user->ID, '_is_tutor_student' ) );
	if ( null !== $student_flag && ( ! is_string( $student_flag ) || ( '' !== $student_flag && ! ctype_digit( $student_flag ) ) ) ) {
		return false; // Do not pass a suspicious Tutor marker to update_user_meta() inside do_enroll().
	}
	$is_tutor_student = is_string( $student_flag ) && ctype_digit( $student_flag ) && (int) $student_flag > 0;

	if ( ( ! $is_subscriber && ! $is_tutor_student ) || $has_privileged_role || $has_privileged_cap ) {
		return false;
	}
	return $user;
}

/** Resolve a login/email without loading a WP_User object for an arbitrary target account. */
function cas_enrol_student( $identity ) {
	if ( ! is_string( $identity ) ) {
		return false;
	}
	$identity = trim( sanitize_text_field( wp_unslash( $identity ) ) );
	if ( '' === $identity ) {
		return false;
	}

	global $wpdb;
	if ( is_email( $identity ) ) {
		$student_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_email = %s LIMIT 1", $identity ) );
	} else {
		$student_id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_login = %s LIMIT 1", $identity ) );
	}
	return $student_id ? cas_enrol_student_by_id( $student_id ) : false;
}

/** A positive, published Tutor course only. */
function cas_enrol_course( $course_id ) {
	$course = get_post( absint( $course_id ) );
	return $course && 'courses' === $course->post_type && 'publish' === $course->post_status ? $course : false;
}

/** Return all Tutor enrolment rows for this student and course, including pending and cancelled records. */
function cas_enrol_records( $course_id, $student_id ) {
	$model = cas_enrol_model();
	$rows  = $model::get_enrolled_data( (int) $student_id, 0, '' );
	return array_values( array_filter( $rows, function ( $row ) use ( $course_id ) {
		return (int) $row->post_parent === (int) $course_id;
	} ) );
}

/** A stable snapshot prevents applying a confirmation after enrollment state has changed. */
function cas_enrol_signature( array $rows ) {
	$states = array_map( function ( $row ) { return wp_json_encode( cas_enrol_snapshot( $row, '' ) ); }, $rows );
	sort( $states, SORT_STRING );
	return hash( 'sha256', implode( '|', $states ) );
}

/** Tutor's updater has no expected-state predicate; undo must not overwrite a newer access decision. */
function cas_enrol_snapshot( $row, $written_status, $created = false ) {
	$model = cas_enrol_model();
	return array(
		'id' => (int) $row->ID,
		'status' => (string) $row->post_status,
		'created' => $created,
		'user_id' => (int) $row->post_author,
		'course_id' => (int) $row->post_parent,
		'written_status' => $written_status,
		'order_id' => (string) get_post_meta( $row->ID, $model::ENROLLMENT_ORDER_ID_META, true ),
		'product_id' => (string) get_post_meta( $row->ID, $model::ENROLLMENT_PRODUCT_ID_META, true ),
		'order_exists' => metadata_exists( 'post', $row->ID, $model::ENROLLMENT_ORDER_ID_META ),
		'product_exists' => metadata_exists( 'post', $row->ID, $model::ENROLLMENT_PRODUCT_ID_META ),
	);
}

function cas_enrol_restore_changes( array $changes ) {
	global $wpdb;
	$model = cas_enrol_model();
	$ok = true;
	foreach ( array_reverse( $changes ) as $change ) {
		$id = (int) $change['id'];
		$restore = $change['created'] ? 'cancel' : $change['status'];
		$from = " FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} om ON om.post_id = p.ID AND om.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s";
		$where = ' WHERE p.ID = %d AND p.post_type = %s AND p.post_author = %d AND p.post_parent = %d AND p.post_status = %s
			AND COALESCE(om.meta_value, \'\') = %s AND COALESCE(pm.meta_value, \'\') = %s
			AND (om.meta_id IS NOT NULL) = %d AND (pm.meta_id IS NOT NULL) = %d';
		$keys = array( $model::ENROLLMENT_ORDER_ID_META, $model::ENROLLMENT_PRODUCT_ID_META );
		$identity = array( $id, $model::POST_TYPE, $change['user_id'], $change['course_id'] );
		$association = array( $change['order_id'], $change['product_id'], (int) $change['order_exists'], (int) $change['product_exists'] );
		$sql = "UPDATE {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} om ON om.post_id = p.ID AND om.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			SET p.post_status = %s" . $where;
		$args = array_merge( $keys, array( $restore ), $identity, array( $change['written_status'] ), $association );
		$changed = $wpdb->query( $wpdb->prepare( $sql, $args ) );
		clean_post_cache( $id );
		$verified = $wpdb->get_var( $wpdb->prepare( 'SELECT p.ID' . $from . $where, array_merge( $keys, $identity, array( $restore ), $association ) ) );
		$ok = false !== $changed && 1 === $changed && (int) $verified === $id && $ok;
	}
	return $ok;
}

/** Audit before mutation; record success only after Tutor's state is verified. */
function cas_enrol_apply( $student_id, $course_id, $operation, $expected_signature ) {
	if ( ! function_exists( 'cas_audit_log' ) ) {
		return 'audit';
	}

	$model = cas_enrol_model();
	$rows  = cas_enrol_records( $course_id, $student_id );
	if ( ! hash_equals( cas_enrol_signature( $rows ), (string) $expected_signature ) ) {
		return false;
	}

	$completed = array_values( array_filter( $rows, function ( $row ) { return 'completed' === $row->post_status; } ) );
	$pending   = array_values( array_filter( $rows, function ( $row ) { return 'pending' === $row->post_status; } ) );
	$cancelled = array_values( array_filter( $rows, function ( $row ) { return 'cancel' === $row->post_status; } ) );
	if ( 'enrol' === $operation && $completed ) {
		return 'already';
	}
	if ( 'unenrol' === $operation && ! $completed && ! $pending ) {
		return 'already';
	}

	$from = $rows ? implode( ',', array_unique( array_map( function ( $row ) { return (string) $row->post_status; }, $rows ) ) ) : 'none';
	$enrolment_ids = implode( ',', array_map( function ( $row ) { return (int) $row->ID; }, $rows ) ) ?: 'none';
	$to = 'enrol' === $operation ? 'completed' : 'cancel';
	$detail = sprintf( 'student_id=%d course_id=%d action=%s enrolments=%s from=%s to=%s', $student_id, $course_id, $operation, $enrolment_ids, $from, $to );
	if ( true !== cas_audit_log( 'manual_enrolment_attempt', $detail ) ) {
		return 'audit';
	}

	$changes = array();
	try {
		if ( 'enrol' === $operation ) {
			if ( $pending || $cancelled ) {
				$reuse = $pending ? array_shift( $pending ) : array_shift( $cancelled );
				foreach ( $pending as $row ) {
					$changes[] = cas_enrol_snapshot( $row, 'cancel' );
				}
				if ( $pending ) {
					$model::update_enrollments( 'cancel', array_map( function ( $row ) { return (int) $row->ID; }, $pending ), false );
				}
				$changes[] = cas_enrol_snapshot( $reuse, 'completed' );
				$model::update_enrollments( 'completed', array( (int) $reuse->ID ), false );
			} else {
				$id = (int) $model::do_enroll( (int) $course_id, 0, (int) $student_id, false );
				if ( ! $id ) {
					cas_audit_log( 'manual_enrolment_failed', sprintf( 'student_id=%d course_id=%d action=enrol stage=do_enroll', $student_id, $course_id ) );
					return false;
				}
				$created_status = (string) get_post_status( $id );
				$changes[] = cas_enrol_snapshot( get_post( $id ), 'completed', true );
				if ( 'completed' !== $created_status ) {
					$model::update_enrollments( 'completed', array( $id ), false );
				}
			}
		} else {
			$active = array_merge( $completed, $pending );
			$ids = array_map( function ( $row ) { return (int) $row->ID; }, $active );
			foreach ( $active as $row ) {
				$changes[] = cas_enrol_snapshot( $row, 'cancel' );
			}
			$model::update_enrollments( 'cancel', $ids, false );
		}
	} catch ( \Throwable $error ) {
		$restored = cas_enrol_restore_changes( $changes );
		cas_audit_log( $restored ? 'manual_enrolment_failed' : 'manual_enrolment_rollback_failed', sprintf( 'student_id=%d course_id=%d action=%s', $student_id, $course_id, $operation ) );
		return $restored ? false : 'rollback';
	}

	$check_rows = cas_enrol_records( $course_id, $student_id );
	$active = array_filter( $check_rows, function ( $row ) { return in_array( $row->post_status, array( 'completed', 'pending' ), true ); } );
	$valid = 'enrol' === $operation
		? 1 === count( array_filter( $check_rows, function ( $row ) { return 'completed' === $row->post_status; } ) ) && 0 === count( array_filter( $check_rows, function ( $row ) { return 'pending' === $row->post_status; } ) )
		: 0 === count( $active );
	if ( ! $valid ) {
		$restored = cas_enrol_restore_changes( $changes );
		cas_audit_log( $restored ? 'manual_enrolment_failed' : 'manual_enrolment_rollback_failed', sprintf( 'student_id=%d course_id=%d action=%s result=state_mismatch', $student_id, $course_id, $operation ) );
		return $restored ? false : 'rollback';
	}
	if ( true !== cas_audit_log( 'manual_enrolment_changed', $detail ) ) {
		$restored = cas_enrol_restore_changes( $changes );
		cas_audit_log( $restored ? 'manual_enrolment_failed' : 'manual_enrolment_rollback_failed', sprintf( 'student_id=%d course_id=%d action=%s result=final_audit', $student_id, $course_id, $operation ) );
		return $restored ? 'audit' : 'rollback';
	}
	return true;
}

function cas_enrol_courses() {
	return get_posts( array(
		'post_type'      => 'courses',
		'post_status'    => 'publish',
		'posts_per_page' => 500,
		'orderby'        => 'title',
		'order'          => 'ASC',
	) );
}

/** Run both POST stages on page load so refusals retain their HTTP status. */
function cas_enrol_on_load() {
	cas_enrol_guard();
	$GLOBALS['cas_enrol_state'] = array( 'confirm' => null, 'notice' => null, 'error' => null, 'form' => null );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	$action = sanitize_key( cas_enrol_form_text( 'cas_enrol_action' ) );
	if ( ! in_array( $action, CAS_ENROL_ACTIONS, true ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	if ( 'confirm' === $action ) {
		cas_enrol_check_nonce( 'cas_enrol_confirm' );
		$operation = sanitize_key( cas_enrol_form_text( 'operation' ) );
		$course_id = cas_enrol_form_id( 'course_id' );
		$student   = cas_enrol_student( cas_enrol_form_text( 'student' ) );
		$course    = cas_enrol_course( $course_id );

		if ( ! in_array( $operation, array( 'enrol', 'unenrol' ), true ) || ! $student || ! $course ) {
			$GLOBALS['cas_enrol_state']['error'] = 'Choose a real student and a published course.';
			$GLOBALS['cas_enrol_state']['form'] = array(
				'student'   => cas_enrol_form_text( 'student' ),
				'course_id' => cas_enrol_form_id( 'course_id' ),
				'operation' => sanitize_key( cas_enrol_form_text( 'operation' ) ),
			);
			return;
		}

		$rows = cas_enrol_records( $course_id, $student->ID );
		$status = $rows ? implode( ', ', array_unique( array_map( function ( $row ) { return (string) $row->post_status; }, $rows ) ) ) : 'none';
		$completed = array_filter( $rows, function ( $row ) { return 'completed' === $row->post_status; } );
		$pending = array_filter( $rows, function ( $row ) { return 'pending' === $row->post_status; } );
		if ( 'enrol' === $operation && $completed ) {
			$GLOBALS['cas_enrol_state']['notice'] = array( 'text' => 'The student already has access to this course.' );
			return;
		}
		if ( 'unenrol' === $operation && ! $completed && ! $pending ) {
			$GLOBALS['cas_enrol_state']['notice'] = array( 'text' => 'The student has no active access to this course.' );
			return;
		}
		$GLOBALS['cas_enrol_state']['confirm'] = array(
			'student_id'          => (int) $student->ID,
			'student'             => $student,
			'course_id'           => (int) $course->ID,
			'course'              => $course,
			'operation'           => $operation,
			'enrolment_signature' => cas_enrol_signature( $rows ),
			'status'              => $status,
		);
		return;
	}

	$student_id = cas_enrol_form_id( 'student_id' );
	$course_id  = cas_enrol_form_id( 'course_id' );
	$operation  = sanitize_key( cas_enrol_form_text( 'operation' ) );
	if ( ! $student_id || ! $course_id || ! in_array( $operation, array( 'enrol', 'unenrol' ), true ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	cas_enrol_check_nonce( 'cas_enrol_apply_' . $student_id . '_' . $course_id . '_' . $operation );

	$student = cas_enrol_student_by_id( $student_id );
	$course  = cas_enrol_course( $course_id );
	if ( ! $student || ! $course ) {
		wp_die( esc_html__( 'That student or course is not available.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	$expected_signature = cas_enrol_form_text( 'expected_signature' );
	$result = cas_enrol_apply( $student_id, $course_id, $operation, $expected_signature );
	if ( 'audit' === $result ) {
		wp_die( esc_html__( 'Not saved: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
	}
	if ( 'rollback' === $result ) {
		wp_die( esc_html__( 'Course access could not be restored. Stop and tell Herman immediately.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
	}
	if ( 'already' === $result ) {
		$GLOBALS['cas_enrol_state']['notice'] = array( 'text' => 'enrol' === $operation ? 'The student already has access to this course.' : 'The student has no active access to this course.' );
		return;
	}
	if ( ! $result ) {
		wp_die( esc_html__( 'Access was not changed. Refresh the screen and try again, or ask Herman.', 'hello-elementor-child' ), '', array( 'response' => 409 ) );
	}

	$GLOBALS['cas_enrol_state']['notice'] = array(
		'text' => 'enrol' === $operation ? 'Course access added.' : 'Course access removed.',
	);
}

function cas_enrol_render_form( $values = array() ) {
	$student = isset( $values['student'] ) ? (string) $values['student'] : '';
	$course  = isset( $values['course_id'] ) ? absint( $values['course_id'] ) : 0;
	$op      = isset( $values['operation'] ) ? sanitize_key( $values['operation'] ) : 'enrol';

	echo '<h2>Change course access</h2>';
	echo '<form method="post">';
	wp_nonce_field( 'cas_enrol_confirm' );
	echo '<input type="hidden" name="cas_enrol_action" value="confirm" />';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th><label for="cas-enrol-student">Student login or email</label></th><td><input id="cas-enrol-student" name="student" type="text" value="' . esc_attr( $student ) . '" required /></td></tr>';
	echo '<tr><th><label for="cas-enrol-course">Course</label></th><td><select id="cas-enrol-course" name="course_id" required><option value="">Choose a course</option>';
	foreach ( cas_enrol_courses() as $item ) {
		echo '<option value="' . esc_attr( (string) $item->ID ) . '"' . selected( $course, $item->ID, false ) . '>' . esc_html( $item->post_title ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th><label for="cas-enrol-operation">Action</label></th><td><select id="cas-enrol-operation" name="operation">';
	echo '<option value="enrol"' . selected( $op, 'enrol', false ) . '>Add access</option>';
	echo '<option value="unenrol"' . selected( $op, 'unenrol', false ) . '>Remove access</option>';
	echo '</select></td></tr></tbody></table>';
	submit_button( 'Review change', 'primary', 'submit', false );
	echo '</form>';
}

function cas_enrol_render_confirm( $data ) {
	$operation = $data['operation'];
	$remove    = 'unenrol' === $operation;

	echo '<h2>Confirm course access change</h2>';
	echo '<p>Student: ' . esc_html( $data['student']->display_name ) . ' (' . esc_html( $data['student']->user_email ) . ')<br />';
	echo 'Course: ' . esc_html( $data['course']->post_title ) . '<br />Current access: ' . esc_html( $data['status'] ) . '</p>';
	if ( $remove ) {
		echo '<p><strong>This removes the student\'s access to this course. It does not refund, cancel, or change any order or payment.</strong></p>';
	} else {
		echo '<p><strong>This gives the student access to this course. It does not create or change any order or payment.</strong></p>';
	}
	echo '<p>If this is not the right student or course, go back without confirming. If a change is wrong, ask Herman; do not try to fix it by changing an order.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'cas_enrol_apply_' . $data['student_id'] . '_' . $data['course_id'] . '_' . $operation );
	echo '<input type="hidden" name="cas_enrol_action" value="apply" />';
	echo '<input type="hidden" name="student_id" value="' . esc_attr( (string) $data['student_id'] ) . '" />';
	echo '<input type="hidden" name="course_id" value="' . esc_attr( (string) $data['course_id'] ) . '" />';
	echo '<input type="hidden" name="operation" value="' . esc_attr( $operation ) . '" />';
	echo '<input type="hidden" name="expected_signature" value="' . esc_attr( $data['enrolment_signature'] ) . '" />';
	submit_button( $remove ? 'Confirm remove access' : 'Confirm give access', 'primary', 'submit', false );
	echo '</form>';
}

function cas_enrol_render() {
	cas_enrol_guard();
	$state = $GLOBALS['cas_enrol_state'] ?? array( 'confirm' => null, 'notice' => null, 'error' => null, 'form' => null );

	echo '<div class="wrap"><h1>Course access</h1>';
	echo '<p>Use this for a one-off access correction. Orders and payments are separate and are never changed here.</p>';
	if ( $state['notice'] ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $state['notice']['text'] ) . '</p></div>';
	}
	if ( $state['error'] ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $state['error'] ) . '</p></div>';
	}
	if ( $state['confirm'] ) {
		cas_enrol_render_confirm( $state['confirm'] );
	} else {
		cas_enrol_render_form( $state['form'] ?? array() );
	}
	echo '</div>';
}

function cas_enrol_menu() {
	$hook = add_submenu_page(
		'cas-content-hub',
		__( 'Course access', 'hello-elementor-child' ),
		__( 'Course access', 'hello-elementor-child' ),
		CAS_ENROL_CAP,
		CAS_ENROL_PAGE,
		'cas_enrol_render'
	);
	add_action( 'load-' . $hook, 'cas_enrol_on_load' );
}
add_action( 'admin_menu', 'cas_enrol_menu', 20 );
