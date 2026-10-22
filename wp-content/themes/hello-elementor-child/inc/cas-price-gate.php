<?php
/**
 * Course price gate for the cas_content role (issue #150, Route A, piece W1-3).
 *
 * The role may edit its own courses, but never their prices. Every write to the price meta is refused
 * for the role at the meta layer, so every save route is covered: the course form, quick edit, bulk
 * edit, the REST API when the request is the role's, and any direct update_post_meta call.
 *
 * The only route to a new price is a two-step confirmation on the Course prices screen:
 *   1. request: the role gives the new regular and sale prices. The server reads the current prices,
 *      stores a one-time token (10 minutes, that user only) and shows old and new side by side.
 *   2. confirm: the role types the old and new values again. The server checks the token, the typed
 *      values, that the current price is still the old one (no stale change), and the capability,
 *      logs the attempt, writes and reads back both prices, then logs the verified outcome. A failed
 *      write restores and verifies the prior pair; an unverified rollback requires operator attention.
 * Native administrator/Tutor writes use the same course lock and receive a retry on contention.
 * Historical orders are never touched.
 */

const CAS_PRICE_META_KEYS = array( 'tutor_course_price', 'tutor_course_sale_price', '_tutor_course_price_type' );
const CAS_PRICE_TOKEN_TTL = 600;
const CAS_PRICE_COURSE_LOCK_TTL = 600;
const CAS_PRICE_PAGE_SLUG = 'cas-course-prices';
const CAS_PRICE_PENDING_META = '_cas_pending_initial_price';

/** True only while the confirm step writes a price. Set and cleared in that one function. */
function cas_price_gate_open( $set = null ) {
	static $open = false;
	if ( null !== $set ) {
		$open = (bool) $set;
	}
	return $open;
}

/** Refuse a price-meta write made by the role. Returning true reports success and stores nothing. */
function cas_price_block_role_write( $check, $object_id, $meta_key ) {
	if ( ( ! in_array( $meta_key, CAS_PRICE_META_KEYS, true ) && CAS_PRICE_PENDING_META !== $meta_key ) || cas_price_gate_open() ) {
		return $check;
	}
	if ( ! cas_content_is_user( get_current_user_id() ) || 'courses' !== get_post_type( (int) $object_id ) ) {
		return $check;
	}
	return true;
}
add_filter( 'update_post_metadata', 'cas_price_block_role_write', 10, 3 );
add_filter( 'add_post_metadata', 'cas_price_block_role_write', 10, 3 );
add_filter( 'delete_post_metadata', 'cas_price_block_role_write', 10, 3 );

/** Current prices, read on the server. Sale is '' when there is no sale. */
function cas_price_current( $course_id ) {
	wp_cache_delete( (int) $course_id, 'post_meta' ); // Verify database state, not a snapshot cached before acquiring the lock.
	$normalise = static function ( $raw ) {
		$clean = cas_price_clean( $raw );
		return null === $clean ? (string) $raw : $clean;
	};
	$sale = $normalise( get_post_meta( $course_id, 'tutor_course_sale_price', true ) );
	return array(
		'type'    => (string) get_post_meta( $course_id, '_tutor_course_price_type', true ),
		'regular' => $normalise( get_post_meta( $course_id, 'tutor_course_price', true ) ),
		'sale'    => ( '' !== $sale && is_numeric( $sale ) && 0.0 === (float) $sale ) ? '' : $sale,
	);
}

/** A price as the server stores it: '' (none) or a number with two decimals. Null when invalid. */
function cas_price_clean( $raw ) {
	if ( ! is_scalar( $raw ) && null !== $raw ) {
		return null;
	}
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}
	if ( ! preg_match( '/^\d{1,6}(\.\d{1,2})?$/', $raw ) ) {
		return null;
	}
	return number_format( (float) $raw, 2, '.', '' );
}

function cas_price_user_may_edit( $course_id ) {
	return current_user_can( 'manage_cas_content' ) && 'courses' === get_post_type( $course_id ) && current_user_can( 'edit_post', $course_id );
}

/** Prune expired token and course-lock options whenever a token is atomically claimed. */
function cas_price_prune_claims() {
	global $wpdb;
	foreach ( array( 'cas_price_claim_', 'cas_price_course_lock_' ) as $prefix ) {
		$like = $wpdb->esc_like( $prefix ) . '%';
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s AND CAST(option_value AS UNSIGNED) < %d", $like, time() ), ARRAY_A );
		foreach ( (array) $rows as $row ) {
			// Compare-and-delete the value read above, so a concurrent replacement stays locked.
			if ( 1 === $wpdb->delete( $wpdb->options, array( 'option_name' => $row['option_name'], 'option_value' => $row['option_value'] ), array( '%s', '%s' ) ) ) {
				wp_cache_delete( $row['option_name'], 'options' );
			}
		}
	}
}

/** Insert-only option claim: add_option() upserts on a race, so use the unique DB key directly. */
function cas_price_insert_claim( $key, $value ) {
	global $wpdb;
	$inserted = $wpdb->query( $wpdb->prepare(
		"INSERT IGNORE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, 'off')",
		$key,
		(string) $value
	) );
	wp_cache_delete( $key, 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	return 1 === $inserted;
}

/** Keep a consumed token claimed through its original expiry, independently of the course lock. */
function cas_price_claim_token( $token, $expires ) {
	if ( time() > (int) $expires ) {
		return false;
	}
	cas_price_prune_claims();
	$key = 'cas_price_claim_' . hash( 'sha256', (string) $token );
	return cas_price_insert_claim( $key, (string) (int) $expires );
}

/** Atomic per-course serialization. The DB unique option_name is the lock; value ties release to its owner. */
function cas_price_acquire_course_lock( $course_id, $owner ) {
	global $wpdb;
	$key   = 'cas_price_course_lock_' . (int) $course_id;
	$value = ( time() + CAS_PRICE_COURSE_LOCK_TTL ) . '|' . hash( 'sha256', (string) $owner );
	if ( cas_price_insert_claim( $key, $value ) ) {
		return $value;
	}
	$existing = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
	$parts    = explode( '|', (string) $existing, 2 );
	if ( count( $parts ) !== 2 || (int) $parts[0] >= time() ) {
		return false;
	}
	// Compare-and-delete the expired value: two contenders cannot both replace a newer lock.
	$deleted = $wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $existing ), array( '%s', '%s' ) );
	if ( 1 !== $deleted ) {
		return false;
	}
	wp_cache_delete( $key, 'options' );
	return cas_price_insert_claim( $key, $value ) ? $value : false;
}

/** Release only the exact lock value this request acquired. */
function cas_price_release_course_lock( $course_id, $value ) {
	global $wpdb;
	$key     = 'cas_price_course_lock_' . (int) $course_id;
	$deleted = $wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $value ), array( '%s', '%s' ) );
	wp_cache_delete( $key, 'options' );
	return 1 === $deleted;
}

/** Native save_post and direct metadata writers hold the same course lock through their entire request. */
function cas_price_native_write_lock( $course_id ) {
	static $held = array();
	$course_id = (int) $course_id;
	if ( isset( $held[ $course_id ] ) ) {
		return;
	}
	$owner = wp_generate_uuid4();
	$value = cas_price_acquire_course_lock( $course_id, $owner );
	if ( false === $value ) {
		wp_die( 'Another price change is being processed. Retry the course save in a moment.', '', array( 'response' => 409 ) );
	}
	$held[ $course_id ] = $value;
	register_shutdown_function( static function () use ( $course_id, $value ) {
		cas_price_release_course_lock( $course_id, $value );
	} );
}
/** Reject a contended native save before the course row (not just its price meta) changes. */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'courses' === $data['post_type'] && ! empty( $postarr['ID'] ) && ! cas_content_is_user( get_current_user_id() ) ) {
		cas_price_native_write_lock( (int) $postarr['ID'] );
	}
	return $data;
}, 0, 2 );
add_action( 'save_post_courses', function ( $id ) {
	if ( ! cas_content_is_user( get_current_user_id() ) ) {
		cas_price_native_write_lock( $id );
	}
}, 0 );

/** Pro pre-save/direct regular, sale and TYPE routes reach this before their first money-field mutation. */
function cas_price_coordinate_native_meta( $check, $id, $key ) {
	if ( in_array( $key, CAS_PRICE_META_KEYS, true ) && 'courses' === get_post_type( $id )
		&& ! cas_price_gate_open() && ! cas_content_is_user( get_current_user_id() ) ) {
		cas_price_native_write_lock( $id );
	}
	return $check;
}
add_filter( 'add_post_metadata', 'cas_price_coordinate_native_meta', 1, 3 );
add_filter( 'update_post_metadata', 'cas_price_coordinate_native_meta', 1, 3 );
add_filter( 'delete_post_metadata', 'cas_price_coordinate_native_meta', 1, 3 );

/** Initialisation is allowed only for a fresh, native-created own draft tracked by the builder adapter. */
add_action( 'tutor_draft_course_created', function ( $id ) {
	if ( ! function_exists( 'cas_builder_fresh_course' ) || ! cas_builder_fresh_course( $id )
		|| ! cas_builder_owns_course( $id ) || ! cas_builder_nonce_valid()
		|| 'tutor_create_new_draft_course' !== ( $_POST['action'] ?? '' )
		|| ! in_array( get_post_status( $id ), array( 'draft', 'auto-draft' ), true )
		|| metadata_exists( 'post', $id, CAS_PRICE_PENDING_META )
		|| metadata_exists( 'post', $id, 'tutor_course_price' ) || metadata_exists( 'post', $id, 'tutor_course_sale_price' ) ) {
		return;
	}
	cas_price_native_write_lock( $id );
	cas_price_gate_open( true );
	try {
		update_post_meta( $id, '_tutor_course_price_type', 'paid' );
		add_post_meta( $id, CAS_PRICE_PENDING_META, wp_generate_uuid4(), true );
	} finally {
		cas_price_gate_open( false );
	}
	if ( 'paid' !== get_post_meta( $id, '_tutor_course_price_type', true ) || ! get_post_meta( $id, CAS_PRICE_PENDING_META, true ) ) {
		wp_die( 'The initial course price could not be prepared. Contact Herman.', '', array( 'response' => 503 ) );
	}
} );

/** Reject publication BEFORE a row becomes public, never silently downgrade a success response to draft. */
function cas_price_assert_publishable( $id ) {
	if ( ! $id || 'courses' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id )
		|| ( cas_content_is_user( get_current_user_id() ) && (int) get_post_field( 'post_author', $id ) !== get_current_user_id() ) ) {
		return new WP_Error( 'cas_course_forbidden', 'You may only publish your own courses.', array( 'status' => 403 ) );
	}
	cas_price_native_write_lock( $id );
	$price = cas_price_current( $id );
	if ( get_post_meta( $id, CAS_PRICE_PENDING_META, true ) || 'paid' !== $price['type']
		|| null === cas_price_clean( $price['regular'] ) || '' === $price['regular'] || (float) $price['regular'] <= 0
		|| null === cas_price_clean( $price['sale'] )
		|| ( '' !== $price['sale'] && ( (float) $price['sale'] <= 0 || (float) $price['sale'] >= (float) $price['regular'] ) ) ) {
		return new WP_Error( 'cas_initial_price_required', 'Use Course prices → Set first price and confirm a positive paid-course price before publishing.', array( 'status' => 409 ) );
	}
	return true;
}
add_filter( 'tutor_is_error_before_course_update', function ( $error, $params ) {
	if ( cas_content_is_user( get_current_user_id() ) && in_array( $params['post_status'] ?? '', array( 'publish', 'future' ), true ) ) {
		$check = cas_price_assert_publishable( absint( $params['course_id'] ?? $params['ID'] ?? 0 ) );
		return is_wp_error( $check ) ? $check : $error;
	}
	return $error;
}, 10, 2 );
add_filter( 'rest_pre_insert_courses', function ( $prepared, $request ) {
	if ( cas_content_is_user( get_current_user_id() ) && in_array( $prepared->post_status ?? '', array( 'publish', 'future' ), true ) ) {
		$check = cas_price_assert_publishable( absint( $request['id'] ?? 0 ) );
		return is_wp_error( $check ) ? $check : $prepared;
	}
	return $prepared;
}, 10, 2 );
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'courses' === $data['post_type'] && cas_content_is_user( get_current_user_id() )
		&& in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
		$check = cas_price_assert_publishable( absint( $postarr['ID'] ?? 0 ) );
		if ( is_wp_error( $check ) ) {
			wp_die( esc_html( $check->get_error_message() ), '', array( 'response' => (int) ( $check->get_error_data()['status'] ?? 409 ) ) );
		}
	}
	return $data;
}, 100, 2 );

/** Write both fields in the privileged window, always close that window, then verify both values. */
function cas_price_write_pair( $course_id, $pair ) {
	cas_price_gate_open( true );
	try {
		update_post_meta( $course_id, 'tutor_course_price', $pair['regular'] );
		update_post_meta( $course_id, 'tutor_course_sale_price', $pair['sale'] );
	} catch ( Throwable $error ) {
		return false;
	} finally {
		cas_price_gate_open( false );
	}
	return cas_price_current( $course_id ) === $pair;
}

/** Restore only fields still equal to our attempted values; never overwrite a concurrent third value. */
function cas_price_restore_pair( $course_id, $old, $attempted ) {
	$current = cas_price_current( $course_id );
	$restore = array();
	$safe    = true;
	foreach ( array( 'regular', 'sale' ) as $field ) {
		if ( $current[ $field ] === $old[ $field ] ) {
			continue;
		}
		if ( $current[ $field ] === $attempted[ $field ] ) {
			$restore[ $field ] = $old[ $field ];
		} else {
			$safe = false; // A different writer changed this field; do not clobber it.
		}
	}
	if ( $restore ) {
		cas_price_gate_open( true );
		try {
			if ( isset( $restore['regular'] ) ) {
				update_post_meta( $course_id, 'tutor_course_price', $restore['regular'] );
			}
			if ( isset( $restore['sale'] ) ) {
				update_post_meta( $course_id, 'tutor_course_sale_price', $restore['sale'] );
			}
		} catch ( Throwable $error ) {
			return false;
		} finally {
			cas_price_gate_open( false );
		}
	}
	return $safe && cas_price_current( $course_id ) === $old;
}

function cas_price_back( $args ) {
	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . CAS_PRICE_PAGE_SLUG ) ) );
	exit;
}

/** Step 1: record the proposed change and show it for confirmation. */
function cas_price_request() {
	check_admin_referer( 'cas_price_request' );
	$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! $course_id || ! cas_price_user_may_edit( $course_id ) ) {
		wp_die( esc_html__( 'Not allowed.', 'hello-elementor-child' ), 403 );
	}
	$regular = cas_price_clean( wp_unslash( $_POST['regular_price'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$sale    = cas_price_clean( wp_unslash( $_POST['sale_price'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( null === $regular || '' === $regular || (float) $regular <= 0 || null === $sale || ( '' !== $sale && ( (float) $sale <= 0 || (float) $sale >= (float) $regular ) ) ) {
		cas_price_back( array( 'course_id' => $course_id, 'cas_price_notice' => 'invalid' ) );
	}
	$old = cas_price_current( $course_id );
	if ( 'paid' !== $old['type'] ) {
		cas_price_back( array( 'course_id' => $course_id, 'cas_price_notice' => 'paid_only' ) );
	}
	$token = wp_generate_password( 32, false );
	set_transient(
		'cas_price_' . $token,
		array(
			'user'    => get_current_user_id(),
			'course'  => $course_id,
			'old'     => $old,
			'new'     => array( 'type' => 'paid', 'regular' => $regular, 'sale' => $sale ),
			'expires' => time() + CAS_PRICE_TOKEN_TTL,
		),
		CAS_PRICE_TOKEN_TTL
	);
	cas_price_back( array( 'token' => $token ) );
}
add_action( 'admin_post_cas_price_request', 'cas_price_request' );

/** Block changed native role prices/type before Tutor's save callback; normal content edits still work. */
add_action( 'wp_ajax_tutor_update_course', function () {
	if ( ! cas_content_is_user( get_current_user_id() ) ) {
		return;
	}
	$id = isset( $_POST['course_id'] ) && is_scalar( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
	if ( ! cas_price_user_may_edit( $id ) || (int) get_post_field( 'post_author', $id ) !== get_current_user_id()
		|| ! function_exists( 'tutor' ) || ! isset( $_POST[ tutor()->nonce ] ) || ! is_string( $_POST[ tutor()->nonce ] )
		|| ! wp_verify_nonce( wp_unslash( $_POST[ tutor()->nonce ] ), tutor()->nonce_action ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	cas_price_native_write_lock( $id );
	$old = cas_price_current( $id );
	$type = isset( $_POST['pricing']['type'] ) ? $_POST['pricing']['type'] : ( $_POST['tutor_course_price_type'] ?? $old['type'] );
	$regular = isset( $_POST['course_price'] ) ? cas_price_clean( wp_unslash( $_POST['course_price'] ) ) : $old['regular'];
	$sale = isset( $_POST['course_sale_price'] ) ? cas_price_clean( wp_unslash( $_POST['course_sale_price'] ) ) : $old['sale'];
	if ( null !== $sale && is_numeric( $sale ) && 0.0 === (float) $sale ) { $sale = ''; }
	if ( get_post_meta( $id, CAS_PRICE_PENDING_META, true ) && '' === $old['regular'] && '0.00' === $regular ) { $regular = ''; }
	if ( $type !== $old['type'] || $regular !== $old['regular'] || $sale !== $old['sale'] ) {
		wp_send_json( array( 'message' => 'Change prices only through Course prices and its confirmation step.' ), 409 );
	}
}, -5 );

/** Step 2: atomically claim the token and course, then confirm, log, write, verify. */
function cas_price_confirm() {
	check_admin_referer( 'cas_price_confirm' );
	$token = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$data  = $token ? get_transient( 'cas_price_' . $token ) : false;
	if ( ! is_array( $data ) || (int) $data['user'] !== get_current_user_id() || time() > (int) $data['expires'] ) {
		cas_price_back( array( 'cas_price_notice' => 'expired' ) );
	}
	// Atomic one-use claim: a competing confirmation using the same token gets no claim.
	if ( ! cas_price_claim_token( $token, (int) $data['expires'] ) ) {
		cas_price_back( array( 'cas_price_notice' => 'expired' ) );
	}
	delete_transient( 'cas_price_' . $token );
	$course_id = (int) $data['course'];
	if ( ! cas_price_user_may_edit( $course_id ) ) {
		wp_die( esc_html__( 'Not allowed.', 'hello-elementor-child' ), 403 );
	}
	$owner = hash( 'sha256', $token . '|' . get_current_user_id() );
	$lock  = cas_price_acquire_course_lock( $course_id, $owner );
	if ( false === $lock ) {
		cas_price_back( array( 'course_id' => $course_id, 'cas_price_notice' => 'busy' ) );
	}
	$notice = 'write_failed';
	try {
		$typed = array(
			'old_regular' => cas_price_clean( wp_unslash( $_POST['old_regular'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'old_sale'    => cas_price_clean( wp_unslash( $_POST['old_sale'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'new_regular' => cas_price_clean( wp_unslash( $_POST['new_regular'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'new_sale'    => cas_price_clean( wp_unslash( $_POST['new_sale'] ?? '' ) ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		);
		$expected = array(
			'old_regular' => $data['old']['regular'],
			'old_sale'    => $data['old']['sale'],
			'new_regular' => $data['new']['regular'],
			'new_sale'    => $data['new']['sale'],
		);
		if ( $typed !== $expected ) {
			$notice = 'mismatch';
		} elseif ( 'paid' !== ( $data['old']['type'] ?? '' ) || 'paid' !== cas_price_current( $course_id )['type'] ) {
			$notice = 'paid_only';
		} elseif ( cas_price_current( $course_id ) !== $data['old'] ) { // Read/compare only under the per-course lock.
			$notice = 'stale';
		} else {
			$line = 'course=' . $course_id
				. ' regular=' . $data['old']['regular'] . '->' . $data['new']['regular']
				. ' sale=' . $data['old']['sale'] . '->' . $data['new']['sale'];
			if ( ! function_exists( 'cas_audit_log' ) || ! cas_audit_log( 'course_price_attempt', $line ) ) {
				$notice = 'audit';
			} elseif ( ! cas_price_write_pair( $course_id, $data['new'] ) ) {
				$restored = cas_price_restore_pair( $course_id, $data['old'], $data['new'] );
				if ( function_exists( 'cas_audit_log' ) ) {
					cas_audit_log( 'course_price_write_failed', 'course=' . $course_id . ' rollback=' . ( $restored ? 'verified' : 'failed' ) );
				}
				$notice = $restored ? 'write_failed' : 'rollback_failed';
			} elseif ( ! cas_audit_log( 'course_price', $line ) ) {
				$restored = cas_price_restore_pair( $course_id, $data['old'], $data['new'] );
				if ( function_exists( 'cas_audit_log' ) ) {
					cas_audit_log( 'course_price_log_failed', 'course=' . $course_id . ' rollback=' . ( $restored ? 'verified' : 'failed' ) );
				}
				$notice = $restored ? 'audit' : 'rollback_failed';
			} elseif ( cas_price_current( $course_id ) === $data['new'] ) { // Only confirmed after both fields read back as the new pair.
				$notice = 'saved';
			} else {
				$restored = cas_price_restore_pair( $course_id, $data['old'], $data['new'] );
				if ( function_exists( 'cas_audit_log' ) ) {
					cas_audit_log( 'course_price_verify_failed', 'course=' . $course_id . ' rollback=' . ( $restored ? 'verified' : 'failed' ) );
				}
				$notice = $restored ? 'write_failed' : 'rollback_failed';
			}
		}
		if ( 'saved' === $notice && get_post_meta( $course_id, CAS_PRICE_PENDING_META, true ) ) {
			cas_price_gate_open( true );
			try { delete_post_meta( $course_id, CAS_PRICE_PENDING_META ); }
			finally { cas_price_gate_open( false ); }
			wp_cache_delete( $course_id, 'post_meta' );
			if ( get_post_meta( $course_id, CAS_PRICE_PENDING_META, true ) ) {
				cas_audit_log( 'course_initial_price_pending', 'course=' . $course_id . ' price_verified=yes' );
				$notice = 'initial_pending';
			}
		}
	} finally {
		cas_price_release_course_lock( $course_id, $lock );
	}
	cas_price_back( array( 'course_id' => $course_id, 'cas_price_notice' => $notice ) );
}
add_action( 'admin_post_cas_price_confirm', 'cas_price_confirm' );

/** The Course prices screen: a request form per course, or the confirmation for a pending token. */
function cas_price_render() {
	if ( ! current_user_can( 'manage_cas_content' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'hello-elementor-child' ), 403 );
	}
	$notices = array(
		'invalid'  => __( 'Enter prices as numbers, for example 58 or 58.50. The sale price must be lower than the regular price, or left empty.', 'hello-elementor-child' ),
		'expired'  => __( 'That confirmation has expired or was already used. Start again.', 'hello-elementor-child' ),
		'paid_only' => __( 'Only paid courses can use this price screen. Free or unknown course types cannot be converted here.', 'hello-elementor-child' ),
		'mismatch' => __( 'The typed prices do not match the change. Nothing was saved.', 'hello-elementor-child' ),
		'stale'    => __( 'The price changed since you asked. Nothing was saved; start again.', 'hello-elementor-child' ),
		'audit'    => __( 'The change could not be recorded in the audit log, so nothing was saved.', 'hello-elementor-child' ),
		'busy'     => __( 'Another price change for this course is being processed. Start again in a moment.', 'hello-elementor-child' ),
		'write_failed' => __( 'The price fields did not both save. The previous price pair was restored and verified; nothing new was saved.', 'hello-elementor-child' ),
		'rollback_failed' => __( 'The price change and its rollback could not be verified. Stop editing and contact Herman immediately.', 'hello-elementor-child' ),
		'initial_pending' => __( 'The price is saved and verified, but publication is still blocked because the first-price marker could not be cleared. Contact Herman.', 'hello-elementor-child' ),
		'saved'    => __( 'The price is saved.', 'hello-elementor-child' ),
	);
	$notice = isset( $_GET['cas_price_notice'] ) ? sanitize_key( wp_unslash( $_GET['cas_price_notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<div class="wrap"><h1>' . esc_html__( 'Course prices', 'hello-elementor-child' ) . '</h1>';
	if ( isset( $notices[ $notice ] ) ) {
		echo '<div class="notice notice-info"><p>' . esc_html( $notices[ $notice ] ) . '</p></div>';
	}
	$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$data  = $token ? get_transient( 'cas_price_' . $token ) : false;
	if ( is_array( $data ) && (int) $data['user'] === get_current_user_id() ) {
		cas_price_render_confirm( $token, $data );
	} else {
		cas_price_render_list();
	}
	echo '</div>';
}

function cas_price_render_list() {
	echo '<p>' . esc_html__( 'Prices change only here. Enter the new regular price and, if there is a sale, the sale price. You will check the change on the next screen before anything is saved.', 'hello-elementor-child' ) . '</p>';
	foreach ( get_posts( array( 'post_type' => 'courses', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $course ) {
		if ( ! current_user_can( 'edit_post', $course->ID ) ) {
			continue;
		}
		$now = cas_price_current( $course->ID );
		if ( 'paid' !== $now['type'] ) {
			continue;
		}
		$first = (bool) get_post_meta( $course->ID, CAS_PRICE_PENDING_META, true );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:12px 0;padding:12px;border:1px solid #ccd0d4;background:#fff">';
		wp_nonce_field( 'cas_price_request' );
		echo '<input type="hidden" name="action" value="cas_price_request"><input type="hidden" name="course_id" value="' . esc_attr( $course->ID ) . '">';
		echo '<strong>' . esc_html( $course->post_title ) . '</strong> — ' . esc_html__( 'now', 'hello-elementor-child' ) . ': ' . esc_html( $now['regular'] !== '' ? $now['regular'] : '—' ) . ' / ' . esc_html( $now['sale'] !== '' ? $now['sale'] : '—' );
		echo ' <label>' . esc_html__( 'Regular', 'hello-elementor-child' ) . ' <input type="text" name="regular_price" value="' . esc_attr( $now['regular'] ) . '" size="8"></label>';
		echo ' <label>' . esc_html__( 'Sale', 'hello-elementor-child' ) . ' <input type="text" name="sale_price" value="' . esc_attr( $now['sale'] ) . '" size="8"></label>';
		echo ' <button class="button" type="submit">' . esc_html( $first ? __( 'Set first price', 'hello-elementor-child' ) : __( 'Review change', 'hello-elementor-child' ) ) . '</button></form>';
	}
}

function cas_price_render_confirm( $token, $data ) {
	$old = $data['old'];
	$new = $data['new'];
	echo '<h2>' . esc_html( get_the_title( $data['course'] ) ) . '</h2>';
	echo '<table class="widefat striped" style="max-width:520px"><thead><tr><th></th><th>' . esc_html__( 'Now', 'hello-elementor-child' ) . '</th><th>' . esc_html__( 'New', 'hello-elementor-child' ) . '</th></tr></thead><tbody>';
	echo '<tr><td>' . esc_html__( 'Regular', 'hello-elementor-child' ) . '</td><td>' . esc_html( $old['regular'] ) . '</td><td>' . esc_html( $new['regular'] ) . '</td></tr>';
	echo '<tr><td>' . esc_html__( 'Sale', 'hello-elementor-child' ) . '</td><td>' . esc_html( $old['sale'] ) . '</td><td>' . esc_html( $new['sale'] ) . '</td></tr></tbody></table>';
	echo '<p>' . esc_html__( 'To confirm, type the current and the new prices again. Leave the sale empty if there is none.', 'hello-elementor-child' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'cas_price_confirm' );
	echo '<input type="hidden" name="action" value="cas_price_confirm"><input type="hidden" name="token" value="' . esc_attr( $token ) . '">';
	echo '<p><label>' . esc_html__( 'Current regular', 'hello-elementor-child' ) . ' <input type="text" name="old_regular" size="8"></label> ';
	echo '<label>' . esc_html__( 'Current sale', 'hello-elementor-child' ) . ' <input type="text" name="old_sale" size="8"></label></p>';
	echo '<p><label>' . esc_html__( 'New regular', 'hello-elementor-child' ) . ' <input type="text" name="new_regular" size="8"></label> ';
	echo '<label>' . esc_html__( 'New sale', 'hello-elementor-child' ) . ' <input type="text" name="new_sale" size="8"></label></p>';
	echo '<p><button class="button button-primary" type="submit">' . esc_html__( 'Save the new prices', 'hello-elementor-child' ) . '</button></p></form>';
}

/** The screen sits under the hub, for the role and for administrators. */
function cas_price_menu() {
	add_submenu_page( 'cas-content-hub', __( 'Course prices', 'hello-elementor-child' ), __( 'Course prices', 'hello-elementor-child' ), 'manage_cas_content', CAS_PRICE_PAGE_SLUG, 'cas_price_render' );
}
add_action( 'admin_menu', 'cas_price_menu', 20 );

