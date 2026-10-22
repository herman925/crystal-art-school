<?php
/**
 * W2-1 Sales: one admin screen under the content hub.
 *
 * Order lookup, a two-step status change, CSV export and read-only sales totals.
 * Status changes are allowed only from pending: to completed (grants access, as Tutor does) or to cancelled.
 * Refunds are impossible here: there is no refund status, no button, and no call into Tutor's refund paths.
 * Payment status is never written. Every screen and handler checks manage_cas_content and a nonce.
 */

defined( 'ABSPATH' ) || exit;

const CAS_SALES_CAP  = 'manage_cas_content';
const CAS_SALES_PAGE = 'cas-sales';

/** The only status changes this screen can make. Keys are the current status; values are the allowed targets. */
const CAS_SALES_STATUS_MAP = array(
	'pending' => array( 'completed', 'cancelled' ),
);

/** The only POST actions this screen accepts. */
const CAS_SALES_ACTIONS = array( 'confirm_status', 'apply_status' );

/** Statuses the list filter accepts. */
const CAS_SALES_LIST_STATUSES = array( 'pending', 'completed', 'cancelled', 'incomplete' );

function cas_sales_guard() {
	if ( ! current_user_can( CAS_SALES_CAP ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'hello-elementor-child' ), '', array( 'response' => 403 ) );
	}
}

function cas_sales_orders_table() {
	global $wpdb;

	return $wpdb->prefix . 'tutor_orders';
}

function cas_sales_items_table() {
	global $wpdb;

	return $wpdb->prefix . 'tutor_order_items';
}

/**
 * Filters from the query string. Each value is checked against an allowlist or a pattern; anything else is ignored.
 *
 * @return array
 */
function cas_sales_filters() {
	$status_raw = $_GET['status'] ?? '';
	$from_raw = $_GET['from'] ?? '';
	$to_raw = $_GET['to'] ?? '';
	$course_raw = $_GET['course'] ?? '';
	$order_raw = $_GET['order_id'] ?? '';
	$status = is_string( $status_raw ) ? sanitize_key( wp_unslash( $status_raw ) ) : '';
	$from = is_string( $from_raw ) ? sanitize_text_field( wp_unslash( $from_raw ) ) : '';
	$to = is_string( $to_raw ) ? sanitize_text_field( wp_unslash( $to_raw ) ) : '';

	return array(
		'status'   => in_array( $status, CAS_SALES_LIST_STATUSES, true ) ? $status : '',
		'course'   => is_scalar( $course_raw ) && ctype_digit( (string) $course_raw ) ? absint( $course_raw ) : 0,
		'order_id' => is_scalar( $order_raw ) && ctype_digit( (string) $order_raw ) ? absint( $order_raw ) : 0,
		'from'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from ) ? $from : '',
		'to'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to ) ? $to : '',
	);
}

/**
 * The order list. Scoped explicitly to the filters. Tutor's own list keys on the literal 'administrator' role,
 * so it is not used.
 *
 * @param array $f Filters from cas_sales_filters().
 *
 * @return array
 */
function cas_sales_list( $f ) {
	global $wpdb;

	$orders = cas_sales_orders_table();
	$items  = cas_sales_items_table();
	$where  = array( '1=1' );
	$args   = array();

	if ( $f['status'] ) {
		$where[] = 'o.order_status = %s';
		$args[]  = $f['status'];
	}
	if ( $f['order_id'] ) {
		$where[] = 'o.id = %d';
		$args[]  = $f['order_id'];
	}
	if ( $f['from'] ) {
		$where[] = 'o.created_at_gmt >= %s';
		$args[]  = $f['from'] . ' 00:00:00';
	}
	if ( $f['to'] ) {
		$where[] = 'o.created_at_gmt <= %s';
		$args[]  = $f['to'] . ' 23:59:59';
	}
	if ( $f['course'] ) {
		$where[] = 'EXISTS (SELECT 1 FROM ' . $items . ' fi WHERE fi.order_id = o.id AND fi.item_id = %d)';
		$args[]  = $f['course'];
	}

	$sql = "SELECT o.id, o.user_id, o.order_status, o.payment_status, o.total_price, o.created_at_gmt,
			GROUP_CONCAT(i.item_id ORDER BY i.id SEPARATOR ',') AS course_ids
		FROM {$orders} o
		LEFT JOIN {$items} i ON i.order_id = o.id
		WHERE " . implode( ' AND ', $where ) . '
		GROUP BY o.id
		ORDER BY o.id DESC
		LIMIT 200';

	return $args ? $wpdb->get_results( $wpdb->prepare( $sql, $args ) ) : $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * The course titles and the student for one order row, for display.
 *
 * @param object $row Order row from cas_sales_list().
 *
 * @return array
 */
function cas_sales_row_details( $row ) {
	global $wpdb;
	$ids    = array_filter( array_map( 'absint', explode( ',', (string) $row->course_ids ) ) );
	$titles = array_map( 'get_the_title', $ids );
	$login  = $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->users} WHERE ID = %d", (int) $row->user_id ) );

	return array(
		'course'  => $titles ? implode( '; ', $titles ) : '',
		'student' => (string) $login,
	);
}

function cas_sales_render_filters( $f ) {
	echo '<form method="get" class="cas-sales-filters">';
	echo '<input type="hidden" name="page" value="' . esc_attr( CAS_SALES_PAGE ) . '" />';
	echo '<label>Order number <input type="number" name="order_id" value="' . esc_attr( $f['order_id'] ? $f['order_id'] : '' ) . '" /></label> ';
	echo '<label>Status <select name="status"><option value="">Any</option>';
	foreach ( CAS_SALES_LIST_STATUSES as $s ) {
		echo '<option value="' . esc_attr( $s ) . '"' . selected( $f['status'], $s, false ) . '>' . esc_html( ucfirst( $s ) ) . '</option>';
	}
	echo '</select></label> ';
	echo '<label>From <input type="date" name="from" value="' . esc_attr( $f['from'] ) . '" /></label> ';
	echo '<label>To <input type="date" name="to" value="' . esc_attr( $f['to'] ) . '" /></label> ';
	echo '<label>Course ID <input type="number" name="course" value="' . esc_attr( $f['course'] ? $f['course'] : '' ) . '" /></label> ';
	submit_button( 'Filter', 'secondary', '', false );
	echo '</form>';

	$export = wp_nonce_url(
		add_query_arg(
			array_merge(
				array( 'page' => CAS_SALES_PAGE, 'cas_sales_export' => 1 ),
				array_filter( array(
					'status'   => $f['status'],
					'order_id' => $f['order_id'] ?: '',
					'from'     => $f['from'],
					'to'       => $f['to'],
					'course'   => $f['course'] ?: '',
				) )
			),
			admin_url( 'admin.php' )
		),
		'cas_sales_export'
	);
	echo '<p><a class="button" href="' . esc_url( $export ) . '">Download CSV of this list</a></p>';
}

function cas_sales_render_list( $f ) {
	$rows = cas_sales_list( $f );

	if ( ! $rows ) {
		echo '<p>No orders match these filters.</p>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>';
	foreach ( array( 'Order', 'Student login', 'Course', 'Date', 'Order status', 'Payment status', 'Amount', 'Change' ) as $h ) {
		echo '<th>' . esc_html( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$d = cas_sales_row_details( $row );
		echo '<tr>';
		echo '<td>' . esc_html( (string) $row->id ) . '</td>';
		echo '<td>' . esc_html( $d['student'] ) . '</td>';
		echo '<td>' . esc_html( $d['course'] ) . '</td>';
		echo '<td>' . esc_html( substr( (string) $row->created_at_gmt, 0, 10 ) ) . '</td>';
		echo '<td>' . esc_html( (string) $row->order_status ) . '</td>';
		echo '<td>' . esc_html( (string) $row->payment_status ) . '</td>';
		echo '<td>' . esc_html( number_format( (float) $row->total_price, 2 ) ) . '</td>';
		echo '<td>';
		if ( isset( CAS_SALES_STATUS_MAP[ $row->order_status ] ) ) {
			foreach ( CAS_SALES_STATUS_MAP[ $row->order_status ] as $to ) {
				echo '<form method="post" style="display:inline">';
				wp_nonce_field( 'cas_sales_confirm' );
				echo '<input type="hidden" name="cas_sales_action" value="confirm_status" />';
				echo '<input type="hidden" name="order_id" value="' . esc_attr( (string) $row->id ) . '" />';
				echo '<input type="hidden" name="to" value="' . esc_attr( $to ) . '" />';
				echo '<button class="button" type="submit">' . esc_html( 'Mark ' . $to ) . '</button>';
				echo '</form> ';
			}
		}
		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * Step one of a status change: show what will happen and what it grants or removes. Changes nothing.
 */
function cas_sales_render_confirm( $order_id, $to ) {
	global $wpdb;

	$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order_id ) );

	if ( ! $order || ! cas_sales_transition_allowed( $order->order_status, $to ) ) {
		wp_die( esc_html__( 'This order cannot be changed to that status.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	if ( 'completed' === $to && false === cas_sales_completed_courses( $order ) ) {
		wp_die( esc_html__( 'Not saved: this order does not contain an accessible individual course and student. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	$d = cas_sales_row_details( (object) array( 'user_id' => $order->user_id, 'course_ids' => cas_sales_course_ids( $order_id ) ) );

	echo '<h2>Confirm: order ' . esc_html( (string) $order->id ) . ' to ' . esc_html( $to ) . '</h2>';
	echo '<p>Student: ' . esc_html( $d['student'] ) . '<br />Course: ' . esc_html( $d['course'] ) . '<br />';
	echo 'Order status now: ' . esc_html( (string) $order->order_status ) . '<br />';
	echo 'Payment status: ' . esc_html( (string) $order->payment_status ) . '</p>';

	if ( 'completed' === $to ) {
		echo '<p>Completing the order gives the student access to the course.</p>';
	} else {
		echo '<p>Cancelling the order does not give the student access.</p>';
	}

	echo '<form method="post">';
	wp_nonce_field( 'cas_sales_apply_' . $order->id );
	echo '<input type="hidden" name="cas_sales_action" value="apply_status" />';
	echo '<input type="hidden" name="order_id" value="' . esc_attr( (string) $order->id ) . '" />';
	echo '<input type="hidden" name="to" value="' . esc_attr( $to ) . '" />';
	echo '<input type="hidden" name="expected_payment" value="' . esc_attr( $order->payment_status ) . '" />';
	echo '<input type="hidden" name="confirmation_token" value="' . esc_attr( wp_hash( $order->id . '|' . $to . '|' . $order->payment_status ) ) . '" />';

	if ( 'paid' !== $order->payment_status && 'completed' === $to ) {
		echo '<p><strong>No payment is recorded for this order. Completing it gives the student access for free.</strong></p>';
		echo '<label><input type="checkbox" name="accept_unpaid" value="1" /> I understand, and want to give access without payment.</label><br />';
	}

	submit_button( 'Confirm change', 'primary', 'submit', false );
	echo '</form>';
}

function cas_sales_course_ids( $order_id ) {
	global $wpdb;

	return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GROUP_CONCAT(item_id ORDER BY id SEPARATOR ",") FROM ' . cas_sales_items_table() . ' WHERE order_id = %d', $order_id ) );
}

function cas_sales_order_course_ids( $order_id ) {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT item_id FROM ' . cas_sales_items_table() . ' WHERE order_id = %d ORDER BY id', $order_id ) );
	return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
}

/** Completing is only supported for valid student orders containing accessible courses. */
function cas_sales_completed_courses( $order ) {
	global $wpdb;
	$order_model = '\\Tutor\\Models\\OrderModel';
	$course_model = '\\Tutor\\Models\\CourseModel';
	$student_exists = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID = %d", (int) $order->user_id ) );
	if ( ! class_exists( $order_model ) || ! class_exists( $course_model ) || $order_model::TYPE_SINGLE_ORDER !== $order->order_type || ! $student_exists ) {
		return false;
	}
	$ids = cas_sales_order_course_ids( $order->id );
	if ( ! $ids ) {
		return false;
	}
	foreach ( $ids as $id ) {
		if ( 'courses' !== get_post_type( $id ) || ! $course_model::is_course_accessible( $id ) || is_wp_error( apply_filters( 'tutor_allow_course_enrollment', true, $id ) ) ) {
			return false;
		}
	}
	return $ids;
}

function cas_sales_transition_allowed( $from, $to ) {
	return isset( CAS_SALES_STATUS_MAP[ $from ] ) && in_array( $to, CAS_SALES_STATUS_MAP[ $from ], true );
}

/** The Tutor marker is numeric; reject malformed raw values before EnrollmentModel updates it. */
function cas_sales_student_marker_safe( $user_id ) {
	global $wpdb;
	$marker = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s LIMIT 1", (int) $user_id, '_is_tutor_student' ) );
	return null === $marker || ( is_string( $marker ) && ( '' === $marker || ctype_digit( $marker ) ) );
}

/** Restore a meta field exactly as it was before a manual enrollment change. */
function cas_sales_restore_enrolment_meta( $enrolment_id, $key, $exists, $value ) {
	if ( $exists ) {
		update_post_meta( $enrolment_id, $key, $value );
	} else {
		delete_post_meta( $enrolment_id, $key );
	}
	return (bool) metadata_exists( 'post', $enrolment_id, $key ) === (bool) $exists
		&& ( ! $exists || (string) get_post_meta( $enrolment_id, $key, true ) === (string) $value );
}

/** Compensate one enrollment row without firing Tutor emails/hooks. */
function cas_sales_restore_enrolment( $snapshot ) {
	if ( empty( $snapshot['changed'] ) ) {
		return true;
	}

	$model = '\\Tutor\\Models\\EnrollmentModel';
	$id    = (int) $snapshot['id'];
	if ( $snapshot['written_status'] !== get_post_status( $id )
		|| (string) get_post_meta( $id, $model::ENROLLMENT_ORDER_ID_META, true ) !== (string) $snapshot['written_order_id']
		|| (string) get_post_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, true ) !== (string) $snapshot['written_product_id'] ) {
		return false;
	}
	if ( $snapshot['created'] ) {
		$model::update_enrollments( 'cancel', array( $id ), false );
		cas_sales_restore_enrolment_meta( $id, $model::ENROLLMENT_ORDER_ID_META, false, '' );
		cas_sales_restore_enrolment_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, false, '' );
		return 'cancel' === get_post_status( $id );
	}

	$model::update_enrollments( $snapshot['status'], array( $id ), false );
	$order_ok = cas_sales_restore_enrolment_meta( $id, $model::ENROLLMENT_ORDER_ID_META, $snapshot['order_exists'], $snapshot['order_id'] );
	$product_ok = cas_sales_restore_enrolment_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, $snapshot['product_exists'], $snapshot['product_id'] );
	return $snapshot['status'] === get_post_status( $id ) && $order_ok && $product_ok;
}

/** Change one course enrollment through Tutor; never send Tutor mail for an order that may roll back. */
function cas_sales_sync_enrolment( $order_id, $user_id, $course_id, $to ) {
	$model = '\\Tutor\\Models\\EnrollmentModel';
	if ( ! class_exists( $model ) ) {
		return false;
	}

	$current = false;
	foreach ( $model::get_enrolled_data( $user_id, 0, '' ) as $row ) {
		if ( (int) $row->post_parent !== (int) $course_id ) {
			continue;
		}
		if ( 'completed' === $to && 'completed' === $row->post_status ) {
			return array( 'changed' => false );
		}
		$row->order_id = (int) get_post_meta( $row->ID, $model::ENROLLMENT_ORDER_ID_META, true );
		if ( 'cancelled' === $to ) {
			if ( (int) $row->order_id === (int) $order_id && 'cancel' !== $row->post_status ) {
				$current = $row;
				break;
			}
		} elseif ( ! $current ) {
			$current = $row;
		}
	}
	$status = $current ? (string) get_post_status( $current->ID ) : 'none';
	if ( 'cancelled' === $to && ( ! $current || (int) $current->order_id !== (int) $order_id || 'cancel' === $status ) ) {
		return array( 'changed' => false );
	}
	if ( $current && ! in_array( $status, array( 'pending', 'cancel', 'completed' ), true ) ) {
		return false;
	}

	$created = ! $current;
	$id      = $current ? (int) $current->ID : 0;
	if ( $created ) {
		if ( ! cas_sales_student_marker_safe( $user_id ) ) {
			return false;
		}
		$id = (int) $model::do_enroll( $course_id, 0, $user_id, false );
		if ( ! $id ) {
			return false;
		}
		$status = (string) get_post_status( $id );
	}

	$snapshot = array(
		'changed'        => true,
		'created'        => $created,
		'id'             => $id,
		'status'         => $status,
		'order_exists'   => metadata_exists( 'post', $id, $model::ENROLLMENT_ORDER_ID_META ),
		'order_id'       => get_post_meta( $id, $model::ENROLLMENT_ORDER_ID_META, true ),
		'product_exists' => metadata_exists( 'post', $id, $model::ENROLLMENT_PRODUCT_ID_META ),
		'product_id'     => get_post_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, true ),
		'written_status' => 'completed' === $to ? 'completed' : 'cancel',
		'written_order_id' => 'completed' === $to ? (int) $order_id : get_post_meta( $id, $model::ENROLLMENT_ORDER_ID_META, true ),
		'written_product_id' => 'completed' === $to ? (int) tutor_utils()->get_course_product_id( $course_id ) : get_post_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, true ),
	);

	$target = 'completed' === $to ? 'completed' : 'cancel';
	if ( $status !== $target ) {
		$model::update_enrollments( $target, array( $id ), false );
	}
	if ( 'completed' === $to ) {
		update_post_meta( $id, $model::ENROLLMENT_ORDER_ID_META, (int) $order_id );
		update_post_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, (int) tutor_utils()->get_course_product_id( $course_id ) );
	}

	$verified = $target === get_post_status( $id );
	if ( 'completed' === $to ) {
		$verified = $verified
			&& (int) get_post_meta( $id, $model::ENROLLMENT_ORDER_ID_META, true ) === (int) $order_id
			&& (int) get_post_meta( $id, $model::ENROLLMENT_PRODUCT_ID_META, true ) === (int) tutor_utils()->get_course_product_id( $course_id );
	}
	if ( ! $verified ) {
		$snapshot['failed'] = true;
	}
	return $snapshot;
}

/** Roll back the order row only if it still has the status and payment snapshot this request wrote. */
function cas_sales_restore_order_status( $order, $from, $to ) {
	global $wpdb;

	$restored = $wpdb->update(
		cas_sales_orders_table(),
		array( 'order_status' => $from, 'updated_at_gmt' => current_time( 'mysql', true ), 'updated_by' => get_current_user_id() ),
		array( 'id' => $order->id, 'order_status' => $to, 'payment_status' => $order->payment_status )
	);
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT order_status, payment_status FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order->id ) );
	return 1 === $restored && $row && $from === $row->order_status && $order->payment_status === $row->payment_status;
}

/** Re-read access independently after all order items have been processed. */
function cas_sales_access_verified( $order, $course_ids, $to ) {
	$model = '\\Tutor\\Models\\EnrollmentModel';
	$rows = $model::get_enrolled_data( (int) $order->user_id, 0, '' );
	foreach ( $course_ids as $course_id ) {
		$has_access = false;
		foreach ( $rows as $row ) {
			if ( (int) $row->post_parent !== (int) $course_id ) {
				continue;
			}
			$has_access = $has_access || 'completed' === $row->post_status;
			if ( 'cancelled' === $to && 'cancel' !== $row->post_status && (int) get_post_meta( $row->ID, $model::ENROLLMENT_ORDER_ID_META, true ) === (int) $order->id ) {
				return false;
			}
		}
		if ( 'completed' === $to && ! $has_access ) {
			return false;
		}
	}
	return true;
}

/** Update the order and its access as one verified action. */
function cas_sales_apply_status( $order_id, $to, $expected_payment, $accepted_unpaid = false ) {
	global $wpdb;
	if ( ! function_exists( 'cas_audit_log' ) ) {
		return 'audit';
	}
	$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order_id ) );
	if ( ! $order || $expected_payment !== $order->payment_status || ! cas_sales_transition_allowed( $order->order_status, $to ) ) {
		return false;
	}
	if ( 'paid' !== $order->payment_status && 'completed' === $to && ! $accepted_unpaid ) {
		return false;
	}
	$course_ids = 'completed' === $to ? cas_sales_completed_courses( $order ) : cas_sales_order_course_ids( $order->id );
	if ( false === $course_ids ) {
		return 'preflight';
	}
	$enrolment_model = '\\Tutor\\Models\\EnrollmentModel';
	if ( ! class_exists( $enrolment_model ) || ( 'completed' === $to && ! cas_sales_student_marker_safe( $order->user_id ) ) ) {
		return 'preflight';
	}
	foreach ( $enrolment_model::get_enrolled_data( (int) $order->user_id, 0, '' ) as $enrolment ) {
		if ( in_array( (int) $enrolment->post_parent, $course_ids, true ) && ! in_array( $enrolment->post_status, array( 'pending', 'cancel', 'completed' ), true ) ) {
			return 'preflight';
		}
	}

	$order_from = $order->order_status;
	$line = sprintf( 'order=%d from=%s to=%s payment=%s', $order->id, $order_from, $to, $order->payment_status );
	if ( true !== cas_audit_log( 'order_status_change_attempt', $line ) ) {
		return 'audit';
	}

	$changed = $wpdb->update(
		cas_sales_orders_table(),
		array( 'order_status' => $to, 'updated_at_gmt' => current_time( 'mysql', true ), 'updated_by' => get_current_user_id() ),
		array( 'id' => $order->id, 'order_status' => $order->order_status, 'payment_status' => $order->payment_status )
	);
	if ( 1 !== $changed ) {
		cas_audit_log( 'order_status_change_failed', $line . ' reason=order_changed' );
		return false;
	}

	$snapshots = array();
	foreach ( $course_ids as $course_id ) {
		$snapshot = cas_sales_sync_enrolment( (int) $order->id, (int) $order->user_id, (int) $course_id, $to );
		if ( false === $snapshot || ! empty( $snapshot['failed'] ) ) {
			if ( is_array( $snapshot ) ) {
				$snapshots[] = $snapshot;
			}
			$order_restored = cas_sales_restore_order_status( $order, $order_from, $to );
			$enrolments_restored = $order_restored;
			if ( $order_restored ) {
				foreach ( array_reverse( $snapshots ) as $previous ) {
					$enrolments_restored = cas_sales_restore_enrolment( $previous ) && $enrolments_restored;
				}
			}
			$restored = $enrolments_restored && $order_restored;
			cas_audit_log( $restored ? 'order_status_change_failed' : 'order_status_rollback_failed', $line . ' reason=enrolment_sync rollback=' . ( $restored ? 'verified' : 'failed' ) );
			return $restored ? false : 'rollback';
		}
		$snapshots[] = $snapshot;
	}

	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT order_status, payment_status FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order->id ) );
	if ( ! $row || $to !== $row->order_status || $order->payment_status !== $row->payment_status || ! cas_sales_access_verified( $order, $course_ids, $to ) || true !== cas_audit_log( 'order_status_changed', $line ) ) {
		$order_restored = cas_sales_restore_order_status( $order, $order_from, $to );
		$enrolments_restored = $order_restored;
		if ( $order_restored ) {
			foreach ( array_reverse( $snapshots ) as $previous ) {
				$enrolments_restored = cas_sales_restore_enrolment( $previous ) && $enrolments_restored;
			}
		}
		$restored = $enrolments_restored && $order_restored;
		cas_audit_log( $restored ? 'order_status_change_failed' : 'order_status_rollback_failed', $line . ' reason=verify_or_audit rollback=' . ( $restored ? 'verified' : 'failed' ) );
		return $restored ? 'audit' : 'rollback';
	}
	return true;
}

/**
 * Read-only totals: completed orders by month, and by course, for the date range.
 */
function cas_sales_render_totals( $f ) {
	global $wpdb;

	$orders = cas_sales_orders_table();
	$items  = cas_sales_items_table();
	$from   = $f['from'] ? $f['from'] . ' 00:00:00' : gmdate( 'Y-m-d 00:00:00', strtotime( '-12 months' ) );
	$to     = $f['to'] ? $f['to'] . ' 23:59:59' : gmdate( 'Y-m-d 23:59:59' );

	$by_month = $wpdb->get_results( $wpdb->prepare(
		"SELECT DATE_FORMAT(created_at_gmt, '%%Y-%%m') AS period, COUNT(*) AS orders, SUM(total_price) AS total
		FROM {$orders} WHERE order_status = 'completed' AND created_at_gmt BETWEEN %s AND %s
		GROUP BY period ORDER BY period DESC",
		$from,
		$to
	) );

	$by_course = $wpdb->get_results( $wpdb->prepare(
		"SELECT i.item_id AS course_id, COUNT(DISTINCT o.id) AS orders,
			SUM(COALESCE(NULLIF(i.sale_price, ''), i.regular_price)) AS total
		FROM {$orders} o INNER JOIN {$items} i ON i.order_id = o.id
		WHERE o.order_status = 'completed' AND o.created_at_gmt BETWEEN %s AND %s
		GROUP BY i.item_id ORDER BY total DESC",
		$from,
		$to
	) );

	echo '<h2>Completed sales by month</h2>';
	if ( $by_month ) {
		echo '<table class="widefat striped"><thead><tr><th>Month</th><th>Orders</th><th>Total</th></tr></thead><tbody>';
		foreach ( $by_month as $r ) {
			echo '<tr><td>' . esc_html( $r->period ) . '</td><td>' . esc_html( (string) $r->orders ) . '</td><td>' . esc_html( number_format( (float) $r->total, 2 ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No completed orders in this range.</p>';
	}

	echo '<h2>Completed sales by course</h2>';
	if ( $by_course ) {
		echo '<table class="widefat striped"><thead><tr><th>Course</th><th>Orders</th><th>Total (item prices, before tax and coupons)</th></tr></thead><tbody>';
		foreach ( $by_course as $r ) {
			echo '<tr><td>' . esc_html( get_the_title( (int) $r->course_id ) ) . '</td><td>' . esc_html( (string) $r->orders ) . '</td><td>' . esc_html( number_format( (float) $r->total, 2 ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No completed orders in this range.</p>';
	}
}

/**
 * Runs before WordPress sends any HTML, so refusals below carry their real HTTP status.
 * Validates each POST step and records the result for the screen. Changes nothing on a refusal.
 */
function cas_sales_on_load() {
	cas_sales_guard();

	$GLOBALS['cas_sales_state'] = array( 'confirm' => null, 'notice' => null );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	$action_raw = $_POST['cas_sales_action'] ?? '';
	$post_action = is_string( $action_raw ) ? sanitize_key( wp_unslash( $action_raw ) ) : '';
	$order_raw = $_POST['order_id'] ?? '';
	$order_id = is_scalar( $order_raw ) && ctype_digit( (string) $order_raw ) ? absint( $order_raw ) : 0;
	$to_raw = $_POST['to'] ?? '';
	$to = is_string( $to_raw ) ? sanitize_key( wp_unslash( $to_raw ) ) : '';

	if ( ! in_array( $post_action, CAS_SALES_ACTIONS, true ) || ! $order_id ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	// The nonce is checked before anything about the order is read, so a bad request learns nothing.
	$nonce = $_POST['_wpnonce'] ?? '';
	if ( ! is_string( $nonce ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	if ( 'confirm_status' === $post_action ) {
		check_admin_referer( 'cas_sales_confirm' );
	} else {
		check_admin_referer( 'cas_sales_apply_' . $order_id );
		$expected_payment = $_POST['expected_payment'] ?? null;
		$confirmation_token = $_POST['confirmation_token'] ?? null;
		if ( ! is_string( $expected_payment ) || ! is_string( $confirmation_token ) || ! hash_equals( wp_hash( $order_id . '|' . $to . '|' . $expected_payment ), $confirmation_token ) ) {
			wp_die( esc_html__( 'That confirmation is not valid. Review the order again.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
		}
	}

	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT order_status, payment_status FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order_id ) );

	// A repeated submit (double click, resent form) finds the change already made: say so and write nothing.
	if ( $row && 'apply_status' === $post_action && $row->order_status === $to ) {
		$GLOBALS['cas_sales_state']['notice'] = array( 'already' => true, 'order_id' => $order_id, 'to' => $to );
		return;
	}

	if ( ! $row || ! cas_sales_transition_allowed( $row->order_status, $to ) ) {
		wp_die( esc_html__( 'That status change is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	if ( 'confirm_status' === $post_action ) {
		$GLOBALS['cas_sales_state']['confirm'] = array( 'order_id' => $order_id, 'to' => $to );
		return;
	}

	$accepted_unpaid = isset( $_POST['accept_unpaid'] ) && '1' === $_POST['accept_unpaid']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if ( 'paid' !== $row->payment_status && 'completed' === $to && ! $accepted_unpaid ) {
		wp_die( esc_html__( 'Tick the confirmation to give access without payment.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	if ( $expected_payment !== $row->payment_status ) {
		wp_die( esc_html__( 'Payment changed since confirmation. Review the order again.', 'hello-elementor-child' ), '', array( 'response' => 409 ) );
	}
	$result = cas_sales_apply_status( $order_id, $to, $expected_payment, $accepted_unpaid );
	if ( 'preflight' === $result ) {
		wp_die( esc_html__( 'Not saved: this order does not contain an accessible individual course and student. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	if ( 'audit' === $result ) {
		wp_die( esc_html__( 'Not saved: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
	}
	if ( 'rollback' === $result ) {
		wp_die( esc_html__( 'The order or course access could not be restored. Stop and tell Herman immediately.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
	}

	$GLOBALS['cas_sales_state']['notice'] = array(
		'ok'       => true === $result,
		'order_id' => $order_id,
		'to'       => $to,
	);
}

/**
 * Admin screen. Shows the confirm step or the result of a change, then the list and totals.
 */
function cas_sales_render() {
	cas_sales_guard();

	$state = isset( $GLOBALS['cas_sales_state'] ) ? $GLOBALS['cas_sales_state'] : array( 'confirm' => null, 'notice' => null );

	echo '<div class="wrap"><h1>Sales</h1>';

	if ( $state['confirm'] ) {
		cas_sales_render_confirm( $state['confirm']['order_id'], $state['confirm']['to'] );
		echo '</div>';
		return;
	}

	if ( $state['notice'] ) {
		if ( ! empty( $state['notice']['already'] ) ) {
			echo '<div class="notice notice-info"><p>Order ' . esc_html( (string) $state['notice']['order_id'] ) . ' is already ' . esc_html( $state['notice']['to'] ) . '. Nothing was changed.</p></div>';
		} elseif ( $state['notice']['ok'] ) {
			echo '<div class="notice notice-success"><p>Order ' . esc_html( (string) $state['notice']['order_id'] ) . ' is now ' . esc_html( $state['notice']['to'] ) . '.</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>The order was not changed. It may have changed since you opened it.</p></div>';
		}
	}

	$f = cas_sales_filters();
	cas_sales_render_filters( $f );
	cas_sales_render_list( $f );
	cas_sales_render_totals( $f );
	echo '</div>';
}

/** Protect untrusted text cells when an operator opens the CSV in spreadsheet software. */
function cas_sales_csv_text( $value ) {
	$value  = (string) $value;
	$prefix = preg_replace( '/\\A[\\p{Cc}\\p{Cf}\\p{Z}]*[=+\\-@]/u', "'", $value, 1 );
	return null === $prefix || $prefix !== $value ? "'" . $value : $value;
}

/**
 * CSV of the filtered list. Checked before any output, on admin_init.
 */
function cas_sales_maybe_export() {
	if ( ! isset( $_GET['page'], $_GET['cas_sales_export'] ) || ! is_string( $_GET['page'] ) || CAS_SALES_PAGE !== $_GET['page'] ) {
		return;
	}

	cas_sales_guard();
	$nonce = $_GET['_wpnonce'] ?? '';
	if ( ! is_string( $nonce ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	check_admin_referer( 'cas_sales_export' );

	$rows = cas_sales_list( cas_sales_filters() );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="sales-' . gmdate( 'Ymd-His' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'order_id', 'date', 'student_login', 'course', 'order_status', 'payment_status', 'amount' ) );
	foreach ( $rows as $row ) {
		$d = cas_sales_row_details( $row );
		fputcsv( $out, array( $row->id, substr( (string) $row->created_at_gmt, 0, 10 ), cas_sales_csv_text( $d['student'] ), cas_sales_csv_text( $d['course'] ), cas_sales_csv_text( $row->order_status ), cas_sales_csv_text( $row->payment_status ), number_format( (float) $row->total_price, 2, '.', '' ) ) );
	}
	fclose( $out );
	exit;
}

function cas_sales_menu() {
	$hook = add_submenu_page(
		'cas-content-hub',
		__( 'Sales', 'hello-elementor-child' ),
		__( 'Sales', 'hello-elementor-child' ),
		CAS_SALES_CAP,
		CAS_SALES_PAGE,
		'cas_sales_render'
	);
	add_action( 'load-' . $hook, 'cas_sales_on_load' );
}
add_action( 'admin_menu', 'cas_sales_menu', 20 ); // after the hub registers its parent menu
add_action( 'admin_init', 'cas_sales_maybe_export' );
