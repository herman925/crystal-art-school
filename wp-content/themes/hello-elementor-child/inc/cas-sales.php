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
	$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	$from   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
	$to     = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';

	return array(
		'status'   => in_array( $status, CAS_SALES_LIST_STATUSES, true ) ? $status : '',
		'course'   => isset( $_GET['course'] ) ? absint( $_GET['course'] ) : 0,
		'order_id' => isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0,
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
	$ids    = array_filter( array_map( 'absint', explode( ',', (string) $row->course_ids ) ) );
	$titles = array_map( 'get_the_title', $ids );
	$user   = get_userdata( (int) $row->user_id );

	return array(
		'course'  => $titles ? implode( '; ', $titles ) : '',
		'student' => $user ? $user->user_login : '',
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
	foreach ( array( 'Order', 'Student', 'Course', 'Date', 'Order status', 'Payment status', 'Amount', 'Change' ) as $h ) {
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

function cas_sales_transition_allowed( $from, $to ) {
	return isset( CAS_SALES_STATUS_MAP[ $from ] ) && in_array( $to, CAS_SALES_STATUS_MAP[ $from ], true );
}

/**
 * Step two: change the order status (compare-and-set on the status read from the database) and bring the
 * student's enrolment into line, using Tutor's own enrolment functions.
 *
 * @return bool
 */
function cas_sales_apply_status( $order_id, $to, $accepted_unpaid = false ) {
	global $wpdb;

	// Fail closed: no change is made unless the audit line can be written.
	if ( ! function_exists( 'cas_audit_log' ) ) {
		return false;
	}

	$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order_id ) );

	if ( ! $order || ! cas_sales_transition_allowed( $order->order_status, $to ) ) {
		return false;
	}

	// Completing without a recorded payment gives access for free, so it needs the extra tick.
	if ( 'paid' !== $order->payment_status && 'completed' === $to && ! $accepted_unpaid ) {
		return false;
	}

	$order_from = $order->order_status;
	$changed    = $wpdb->update(
		cas_sales_orders_table(),
		array(
			'order_status' => $to,
			'updated_at_gmt' => current_time( 'mysql', true ),
			'updated_by'   => get_current_user_id(),
		),
		array(
			'id'           => $order->id,
			'order_status' => $order->order_status,
		)
	);

	if ( 1 !== $changed ) {
		return false;
	}

	foreach ( array_filter( array_map( 'absint', explode( ',', cas_sales_course_ids( $order->id ) ) ) ) as $course_id ) {
		cas_sales_sync_enrolment( (int) $order->id, (int) $order->user_id, $course_id, $to );
	}

	cas_audit_log(
		'order_status_changed',
		sprintf( 'order=%d from=%s to=%s payment=%s', $order->id, $order_from, $to, $order->payment_status )
	);

	return true;
}

/**
 * Bring the student's enrolment on this course into line with the order's new status, using Tutor's functions.
 * Completing never creates a second enrolment row: an existing row is reused and tied to this order.
 * Cancelling only touches an enrolment that belongs to this order.
 */
function cas_sales_sync_enrolment( $order_id, $user_id, $course_id, $to ) {
	if ( ! class_exists( '\Tutor\Models\EnrollmentModel' ) ) {
		return;
	}

	$model = '\Tutor\Models\EnrollmentModel';

	if ( 'completed' === $to ) {
		if ( $model::is_enrolled( $course_id, $user_id, true ) ) {
			return; // already has access
		}

		$row = $model::is_enrolled( $course_id, $user_id, false );
		if ( $row ) {
			// do_enroll() would return this row without changing it, so reuse it here.
			$enrolment_id = (int) $row->ID;
			update_post_meta( $enrolment_id, $model::ENROLLMENT_ORDER_ID_META, $order_id );
		} else {
			$enrolment_id = (int) $model::do_enroll( $course_id, $order_id, $user_id, true );
		}

		if ( $enrolment_id ) {
			$model::update_enrollments( 'completed', array( $enrolment_id ) );
		}
		return;
	}

	$current = $model::is_enrolled( $course_id, $user_id, false );
	if ( $current && (int) $current->order_id === (int) $order_id ) {
		$model::update_enrollments( 'cancel', array( (int) $current->ID ) );
	}
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

	$post_action = isset( $_POST['cas_sales_action'] ) ? sanitize_key( wp_unslash( $_POST['cas_sales_action'] ) ) : '';
	$order_id    = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$to          = isset( $_POST['to'] ) ? sanitize_key( wp_unslash( $_POST['to'] ) ) : '';

	if ( ! in_array( $post_action, CAS_SALES_ACTIONS, true ) || ! $order_id ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	// The nonce is checked before anything about the order is read, so a bad request learns nothing.
	if ( 'confirm_status' === $post_action ) {
		check_admin_referer( 'cas_sales_confirm' );
	} else {
		check_admin_referer( 'cas_sales_apply_' . $order_id );
	}

	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT order_status, payment_status FROM ' . cas_sales_orders_table() . ' WHERE id = %d', $order_id ) );

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

	$GLOBALS['cas_sales_state']['notice'] = array(
		'ok'       => cas_sales_apply_status( $order_id, $to, $accepted_unpaid ),
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
		if ( $state['notice']['ok'] ) {
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

/**
 * CSV of the filtered list. Checked before any output, on admin_init.
 */
function cas_sales_maybe_export() {
	if ( ! isset( $_GET['page'], $_GET['cas_sales_export'] ) || CAS_SALES_PAGE !== $_GET['page'] ) {
		return;
	}

	cas_sales_guard();
	check_admin_referer( 'cas_sales_export' );

	$rows = cas_sales_list( cas_sales_filters() );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="sales-' . gmdate( 'Ymd-His' ) . '.csv"' );

	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'order_id', 'date', 'student', 'course', 'order_status', 'payment_status', 'amount' ) );
	foreach ( $rows as $row ) {
		$d = cas_sales_row_details( $row );
		fputcsv( $out, array( $row->id, substr( (string) $row->created_at_gmt, 0, 10 ), $d['student'], $d['course'], $row->order_status, $row->payment_status, number_format( (float) $row->total_price, 2, '.', '' ) ) );
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
