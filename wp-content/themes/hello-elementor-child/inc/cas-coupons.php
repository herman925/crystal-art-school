<?php
/**
 * W2-2 Coupons: list, create, edit, activate/deactivate and delete. Tutor's CouponModel handles mutations and usage counts;
 * coupon-row reads use its trusted table name without hydrating creator WP_User objects.
 * Coupons apply to all courses only (no per-course or bundle rules here). Used coupons cannot be deleted and
 * used codes cannot be changed. A 100% coupon makes the course free, so it needs an exact confirmation tick.
 */

defined( 'ABSPATH' ) || exit;

const CAS_COUPONS_CAP     = 'manage_cas_content';
const CAS_COUPONS_PAGE    = 'cas-coupons';
const CAS_COUPONS_ACTIONS = array( 'save', 'activate', 'deactivate', 'delete' );

function cas_coupons_guard() {
	if ( ! current_user_can( CAS_COUPONS_CAP ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'hello-elementor-child' ), '', array( 'response' => 403 ) );
	}
}

function cas_coupons_model() {
	return new \Tutor\Models\CouponModel();
}

/** Read rows without Tutor's formatter, which hydrates creator WP_User objects and capabilities. */
function cas_coupons_record_by_id( $id ) {
	global $wpdb;
	$table = cas_coupons_model()->get_table_name();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ) );
}

function cas_coupons_record_by_code( $code ) {
	global $wpdb;
	$table = cas_coupons_model()->get_table_name();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE coupon_code = %s", $code ) );
}

function cas_coupons_records() {
	global $wpdb;
	$table = cas_coupons_model()->get_table_name();
	$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT 200" );
	foreach ( (array) $rows as $row ) {
		$row->usage_count = cas_coupons_used( $row->coupon_code );
	}
	return (array) $rows;
}

/** How many times a coupon has been used at checkout. */
function cas_coupons_used( $coupon_code ) {
	return (int) cas_coupons_model()->get_coupon_usage_count( $coupon_code );
}

/**
 * Validate the coupon form. Returns the data to write, or an error message.
 *
 * @param array $in Raw POST values.
 * @param int   $id Coupon id (0 for a new coupon).
 *
 * @return array{data?: array, error?: string}
 */
function cas_coupons_validate( array $in, $id ) {
	$title = sanitize_text_field( wp_unslash( $in['coupon_title'] ?? '' ) );
	$code  = strtoupper( trim( sanitize_text_field( wp_unslash( $in['coupon_code'] ?? '' ) ) ) );
	$type  = sanitize_key( $in['discount_type'] ?? '' );
	$raw   = trim( sanitize_text_field( wp_unslash( $in['discount_amount'] ?? '' ) ) );
	$start = sanitize_text_field( wp_unslash( $in['start_date'] ?? '' ) );
	$end   = sanitize_text_field( wp_unslash( $in['expire_date'] ?? '' ) );

	if ( '' === $title || mb_strlen( $title ) > 120 ) {
		return array( 'error' => 'Give the coupon a title of up to 120 characters.' );
	}
	if ( ! preg_match( '/^[A-Z0-9_-]{3,40}$/', $code ) ) {
		return array( 'error' => 'The code must be 3 to 40 letters, numbers, dashes or underscores.' );
	}
	$existing = cas_coupons_record_by_code( $code );
	if ( $existing && (int) $existing->id !== (int) $id ) {
		return array( 'error' => 'That code is already in use.' );
	}
	$current = $id ? cas_coupons_record_by_id( $id ) : null;
	if ( $current && $code !== $current->coupon_code && cas_coupons_used( $current->coupon_code ) > 0 ) {
		return array( 'error' => 'This coupon has been used, so its code cannot change.' );
	}
	if ( ! in_array( $type, array( 'percentage', 'flat' ), true ) ) {
		return array( 'error' => 'Choose a percentage or a fixed amount.' );
	}
	if ( ! preg_match( '/^\d+(\.\d{1,2})?$/', $raw ) || (float) $raw <= 0 ) {
		return array( 'error' => 'The discount must be a number above zero, with at most two decimals.' );
	}
	if ( 'percentage' === $type && (float) $raw > 100 ) {
		return array( 'error' => 'A percentage discount cannot be more than 100.' );
	}

	$start_date = DateTime::createFromFormat( '!Y-m-d', $start );
	if ( ! $start_date || $start_date->format( 'Y-m-d' ) !== $start ) {
		return array( 'error' => 'Enter the start date as YYYY-MM-DD.' );
	}
	$expire_date = null;
	if ( '' !== $end ) {
		$parsed = DateTime::createFromFormat( '!Y-m-d', $end );
		if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $end || $parsed < $start_date ) {
			return array( 'error' => 'The expiry date must be a valid date on or after the start date.' );
		}
		$expire_date = $end . ' 23:59:59';
	}

	$total   = cas_coupons_limit( $in['total_usage_limit'] ?? '', 4294967295 );
	$per_one = cas_coupons_limit( $in['per_user_usage_limit'] ?? '', 255 );
	if ( false === $total || false === $per_one ) {
		return array( 'error' => 'Usage limits must be blank (no limit) or a whole number of at least 1.' );
	}

	$full_discount_ack = isset( $in['accept_full_discount'] ) && is_string( $in['accept_full_discount'] ) && '1' === $in['accept_full_discount'];
	if ( 'percentage' === $type && 100.0 === (float) $raw && ! $full_discount_ack ) {
		return array( 'error' => 'No payment is due at 100%. This coupon makes the course free. Tick the box to confirm.' );
	}

	return array(
		'data' => array(
			'coupon_title'               => $title,
			'coupon_code'                => $code,
			'discount_type'              => $type,
			'discount_amount'            => number_format( (float) $raw, 2, '.', '' ),
			'start_date_gmt'             => $start . ' 00:00:00',
			'expire_date_gmt'            => $expire_date,
			'total_usage_limit'          => $total,
			'per_user_usage_limit'       => $per_one,
			'coupon_type'                => 'code',
			'applies_to'                 => 'all_courses',
			'purchase_requirement'       => 'no_minimum',
			'purchase_requirement_value' => null,
		),
	);
}

/**
 * A usage limit: blank means no limit (null), otherwise a whole number of at least 1. False when invalid.
 */
function cas_coupons_limit( $raw, $max = PHP_INT_MAX ) {
	$raw = trim( sanitize_text_field( wp_unslash( (string) $raw ) ) );
	if ( '' === $raw ) {
		return null;
	}
	return ctype_digit( $raw ) && (int) $raw >= 1 && (int) $raw <= $max ? (int) $raw : false;
}

/**
 * Record one change. The merged audit logger returns true only when its line is fully written; every
 * coupon write waits for that exact result.
 */
function cas_coupons_audit( $event, $detail ) {
	return function_exists( 'cas_audit_log' ) && true === cas_audit_log( $event, $detail );
}

/**
 * Runs before the admin page prints, so refusals keep their HTTP status. Validates and performs each POST step.
 */
function cas_coupons_on_load() {
	cas_coupons_guard();

	$GLOBALS['cas_coupons_state'] = array( 'notice' => null, 'errors' => null, 'edit' => null );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	$action = isset( $_POST['cas_coupons_action'] ) ? sanitize_key( wp_unslash( $_POST['cas_coupons_action'] ) ) : '';
	$id     = isset( $_POST['coupon_id'] ) ? absint( $_POST['coupon_id'] ) : 0;

	if ( ! in_array( $action, CAS_COUPONS_ACTIONS, true ) ) {
		wp_die( esc_html__( 'That request is not allowed.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	$model = cas_coupons_model();

	if ( 'save' === $action ) {
		check_admin_referer( 'cas_coupons_save_' . $id );
		$current = $id ? cas_coupons_record_by_id( $id ) : null;
		if ( $id && ! $current ) {
			wp_die( esc_html__( 'That coupon does not exist.', 'hello-elementor-child' ), '', array( 'response' => 404 ) );
		}
		if ( $current && 'code' !== $current->coupon_type ) {
			wp_die( esc_html__( 'Only code coupons can be changed here.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
		}
		$checked = cas_coupons_validate( $_POST, $id ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $checked['error'] ) ) {
			$GLOBALS['cas_coupons_state']['errors'] = $checked['error'];
			$GLOBALS['cas_coupons_state']['edit']   = array( 'id' => $id, 'post' => $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		$user = get_current_user_id();
		$now  = current_time( 'mysql', true );
		if ( $id ) {
			$data = $checked['data'] + array( 'updated_by' => $user, 'updated_at_gmt' => $now );
			if ( ! cas_coupons_audit( 'coupon_save_attempt', "coupon={$id} action=update" ) ) {
				wp_die( esc_html__( 'Not saved: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
			}
			$model->update_coupon( $id, $data );
			$stored = cas_coupons_record_by_id( $id );
			$matches = $stored
				&& $stored->coupon_code === $data['coupon_code']
				&& $stored->coupon_title === $data['coupon_title']
				&& $stored->discount_type === $data['discount_type']
				&& (float) $stored->discount_amount === (float) $data['discount_amount']
				&& $stored->start_date_gmt === $data['start_date_gmt']
				&& $stored->expire_date_gmt === $data['expire_date_gmt']
				&& (int) $stored->total_usage_limit === (int) $data['total_usage_limit']
				&& (int) $stored->per_user_usage_limit === (int) $data['per_user_usage_limit'];
			if ( ! $matches ) {
				$GLOBALS['cas_coupons_state']['errors'] = 'Not saved: the database did not confirm the change. Tell Herman.';
				return;
			}
			$GLOBALS['cas_coupons_state']['notice'] = array( 'text' => 'Coupon saved.' );
		} else {
			$data = $checked['data'] + array( 'coupon_status' => 'active', 'created_by' => $user, 'updated_by' => $user, 'created_at_gmt' => $now, 'updated_at_gmt' => $now );
			if ( ! cas_coupons_audit( 'coupon_save_attempt', 'action=create code=' . $data['coupon_code'] ) ) {
				wp_die( esc_html__( 'Not saved: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
			}
			try {
				$new = $model->create_coupon( $data );
			} catch ( \Throwable $e ) {
				$GLOBALS['cas_coupons_state']['errors'] = 'Not saved: the database refused the coupon. Tell Herman.';
				$GLOBALS['cas_coupons_state']['edit']   = array( 'id' => 0, 'post' => $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				return;
			}
			if ( ! $new ) {
				$GLOBALS['cas_coupons_state']['errors'] = 'Not saved: the database refused the coupon. Tell Herman.';
				return;
			}
			$GLOBALS['cas_coupons_state']['notice'] = array( 'text' => 'Coupon created.' );
		}
		return;
	}

	check_admin_referer( 'cas_coupons_row_' . $action . '_' . $id );
	$coupon = $id ? cas_coupons_record_by_id( $id ) : null;
	if ( ! $coupon ) {
		wp_die( esc_html__( 'That coupon does not exist.', 'hello-elementor-child' ), '', array( 'response' => 404 ) );
	}
	if ( 'code' !== $coupon->coupon_type ) {
		wp_die( esc_html__( 'Only code coupons can be changed here.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}

	if ( 'delete' === $action ) {
		if ( cas_coupons_used( $coupon->coupon_code ) > 0 ) {
			wp_die( esc_html__( 'This coupon has been used, so it cannot be deleted. Deactivate it instead.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
		}
		if ( ! cas_coupons_audit( 'coupon_delete_attempt', "coupon={$id}" ) ) {
			wp_die( esc_html__( 'Not deleted: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
		}
		$deleted = $model->delete_coupon( $id );
		if ( ! $deleted || cas_coupons_record_by_id( $id ) ) {
			$GLOBALS['cas_coupons_state']['errors'] = 'Not deleted: the database did not confirm the change. Tell Herman.';
			return;
		}
		$GLOBALS['cas_coupons_state']['notice'] = array( 'text' => 'Coupon deleted.' );
		return;
	}

	// activate or deactivate: only between active and inactive, never trash, expired or scheduled.
	$to = 'activate' === $action ? 'active' : 'inactive';
	$from_ok = 'activate' === $action ? 'inactive' : 'active';
	if ( $coupon->coupon_status !== $from_ok ) {
		wp_die( esc_html__( 'That change is not allowed for this coupon.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
	}
	if ( ! cas_coupons_audit( 'coupon_status_change_attempt', "coupon={$id} to={$to}" ) ) {
		wp_die( esc_html__( 'Not changed: the change could not be recorded. Tell Herman.', 'hello-elementor-child' ), '', array( 'response' => 500 ) );
	}
	$saved = $model->update_coupon( $id, array( 'coupon_status' => $to, 'updated_by' => get_current_user_id(), 'updated_at_gmt' => current_time( 'mysql', true ) ) );
	$stored = cas_coupons_record_by_id( $id );
	if ( ! $saved || ! $stored || $stored->coupon_status !== $to ) {
		$GLOBALS['cas_coupons_state']['errors'] = 'Not changed: the database did not confirm the change. Tell Herman.';
		return;
	}
	$GLOBALS['cas_coupons_state']['notice'] = array( 'text' => 'Coupon ' . ( 'active' === $to ? 'activated' : 'deactivated' ) . '.' );
}

function cas_coupons_render_list() {
	$model   = cas_coupons_model();
	$rows = array_values( array_filter( cas_coupons_records(), function ( $c ) { return 'trash' !== $c->coupon_status; } ) );

	if ( ! $rows ) {
		echo '<p>No coupons yet.</p>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>';
	foreach ( array( 'Code', 'Title', 'Discount', 'Status', 'Valid', 'Used', 'Change' ) as $h ) {
		echo '<th>' . esc_html( $h ) . '</th>';
	}
	echo '</tr></thead><tbody>';

	foreach ( $rows as $c ) {
		$used   = (int) $c->usage_count;
		$amount = 'percentage' === $c->discount_type ? rtrim( rtrim( number_format( (float) $c->discount_amount, 2 ), '0' ), '.' ) . '%' : '£' . number_format( (float) $c->discount_amount, 2 );
		$valid  = substr( (string) $c->start_date_gmt, 0, 10 ) . ' to ' . ( $c->expire_date_gmt ? substr( (string) $c->expire_date_gmt, 0, 10 ) : 'no end' );
		$limit  = $c->total_usage_limit ? ' of ' . (int) $c->total_usage_limit : '';

		echo '<tr>';
		echo '<td>' . esc_html( $c->coupon_code ) . '</td>';
		echo '<td>' . esc_html( $c->coupon_title ) . '</td>';
		echo '<td>' . esc_html( $amount ) . '</td>';
		echo '<td>' . esc_html( $c->coupon_status ) . '</td>';
		echo '<td>' . esc_html( $valid ) . '</td>';
		echo '<td>' . esc_html( $used . $limit ) . '</td>';
		echo '<td>';
		if ( 'code' === $c->coupon_type ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( array( 'page' => CAS_COUPONS_PAGE, 'edit' => $c->id ), admin_url( 'admin.php' ) ) ) . '">Edit</a> ';
		if ( in_array( $c->coupon_status, array( 'active', 'inactive' ), true ) ) {
			$next = 'active' === $c->coupon_status ? 'deactivate' : 'activate';
			echo '<form method="post" style="display:inline">';
			wp_nonce_field( 'cas_coupons_row_' . $next . '_' . $c->id );
			echo '<input type="hidden" name="cas_coupons_action" value="' . esc_attr( $next ) . '" />';
			echo '<input type="hidden" name="coupon_id" value="' . esc_attr( (string) $c->id ) . '" />';
			echo '<button class="button" type="submit">' . esc_html( ucfirst( $next ) ) . '</button></form> ';
		}
		if ( 0 === $used ) {
			echo '<form method="post" style="display:inline">';
			wp_nonce_field( 'cas_coupons_row_delete_' . $c->id );
			echo '<input type="hidden" name="cas_coupons_action" value="delete" />';
			echo '<input type="hidden" name="coupon_id" value="' . esc_attr( (string) $c->id ) . '" />';
			echo '<button class="button" type="submit">Delete</button></form>';
		}
		}
		echo '</td></tr>';
	}

	echo '</tbody></table>';
}

/**
 * The create/edit form. Shows the posted values back when validation failed.
 */
function cas_coupons_render_form( $id, $values ) {
	$v = function ( $key, $default = '' ) use ( $values ) { return isset( $values[ $key ] ) ? $values[ $key ] : $default; };

	echo '<h2>' . ( $id ? 'Edit coupon' : 'New coupon' ) . '</h2>';
	echo '<form method="post">';
	wp_nonce_field( 'cas_coupons_save_' . $id );
	echo '<input type="hidden" name="cas_coupons_action" value="save" />';
	echo '<input type="hidden" name="coupon_id" value="' . esc_attr( (string) $id ) . '" />';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th><label for="coupon_title">Title</label></th><td><input id="coupon_title" name="coupon_title" type="text" maxlength="120" value="' . esc_attr( $v( 'coupon_title' ) ) . '" required /></td></tr>';
	echo '<tr><th><label for="coupon_code">Code</label></th><td><input id="coupon_code" name="coupon_code" type="text" maxlength="40" value="' . esc_attr( $v( 'coupon_code' ) ) . '" required /> <span class="description">Letters, numbers, dashes or underscores. Cannot change once used.</span></td></tr>';
	echo '<tr><th><label for="discount_type">Discount type</label></th><td><select id="discount_type" name="discount_type">';
	foreach ( array( 'percentage' => 'Percentage', 'flat' => 'Fixed amount (£)' ) as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $v( 'discount_type', 'percentage' ), $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></td></tr>';
	echo '<tr><th><label for="discount_amount">Discount</label></th><td><input id="discount_amount" name="discount_amount" type="text" value="' . esc_attr( $v( 'discount_amount' ) ) . '" required /></td></tr>';
	echo '<tr><th><label for="start_date">Starts</label></th><td><input id="start_date" name="start_date" type="date" value="' . esc_attr( $v( 'start_date', gmdate( 'Y-m-d' ) ) ) . '" required /></td></tr>';
	echo '<tr><th><label for="expire_date">Expires</label></th><td><input id="expire_date" name="expire_date" type="date" value="' . esc_attr( $v( 'expire_date' ) ) . '" /> <span class="description">Blank means no end date.</span></td></tr>';
	echo '<tr><th><label for="total_usage_limit">Total uses</label></th><td><input id="total_usage_limit" name="total_usage_limit" type="number" min="1" value="' . esc_attr( $v( 'total_usage_limit' ) ) . '" /> <span class="description">Blank means no limit.</span></td></tr>';
	echo '<tr><th><label for="per_user_usage_limit">Uses per student</label></th><td><input id="per_user_usage_limit" name="per_user_usage_limit" type="number" min="1" value="' . esc_attr( $v( 'per_user_usage_limit' ) ) . '" /> <span class="description">Blank means no limit.</span></td></tr>';
	echo '<tr><th>Full discount</th><td><label><input name="accept_full_discount" type="checkbox" value="1" /> I understand a 100% coupon makes the course free for anyone who uses it.</label></td></tr>';
	echo '</tbody></table>';
	submit_button( 'Save coupon', 'primary', 'submit', false );
	echo ' <a href="' . esc_url( add_query_arg( 'page', CAS_COUPONS_PAGE, admin_url( 'admin.php' ) ) ) . '">Back to coupons</a>';
	echo '</form>';
}

function cas_coupons_render() {
	cas_coupons_guard();

	$state = $GLOBALS['cas_coupons_state'] ?? array( 'notice' => null, 'errors' => null, 'edit' => null );

	echo '<div class="wrap"><h1>Coupons</h1>';

	if ( $state['notice'] ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $state['notice']['text'] ) . '</p></div>';
	}
	if ( $state['errors'] ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $state['errors'] ) . '</p></div>';
	}

	if ( $state['edit'] ) {
		$posted = $state['edit']['post'];
		cas_coupons_render_form( (int) $state['edit']['id'], array_map( 'wp_unslash', $posted ) );
		echo '</div>';
		return;
	}

	$edit_id = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$new     = isset( $_GET['new'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $edit_id || $new ) {
		$values = array();
		if ( $edit_id ) {
			$coupon = cas_coupons_record_by_id( $edit_id );
			if ( ! $coupon ) {
				wp_die( esc_html__( 'That coupon does not exist.', 'hello-elementor-child' ), '', array( 'response' => 404 ) );
			}
			if ( 'code' !== $coupon->coupon_type ) {
				wp_die( esc_html__( 'Only code coupons can be changed here.', 'hello-elementor-child' ), '', array( 'response' => 400 ) );
			}
			$values = array(
				'coupon_code'          => $coupon->coupon_code,
				'discount_type'        => $coupon->discount_type,
				'discount_amount'      => $coupon->discount_amount,
				'start_date'           => substr( (string) $coupon->start_date_gmt, 0, 10 ),
				'expire_date'          => $coupon->expire_date_gmt ? substr( (string) $coupon->expire_date_gmt, 0, 10 ) : '',
				'total_usage_limit'    => $coupon->total_usage_limit ?: '',
				'per_user_usage_limit' => $coupon->per_user_usage_limit ?: '',
			);
		}
		cas_coupons_render_form( $edit_id, $values );
		echo '</div>';
		return;
	}

	echo '<p><a class="button button-primary" href="' . esc_url( add_query_arg( array( 'page' => CAS_COUPONS_PAGE, 'new' => 1 ), admin_url( 'admin.php' ) ) ) . '">Add coupon</a></p>';
	cas_coupons_render_list();
	echo '</div>';
}

function cas_coupons_menu() {
	$hook = add_submenu_page(
		'cas-content-hub',
		__( 'Coupons', 'hello-elementor-child' ),
		__( 'Coupons', 'hello-elementor-child' ),
		CAS_COUPONS_CAP,
		CAS_COUPONS_PAGE,
		'cas_coupons_render'
	);
	add_action( 'load-' . $hook, 'cas_coupons_on_load' );
}
add_action( 'admin_menu', 'cas_coupons_menu', 20 );
