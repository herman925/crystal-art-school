<?php
/** Purchase counts and minimal verified payment evidence; never infer mode from today's settings. */
defined( 'ABSPATH' ) || exit;

function cas_popularity_payload_mode( $row ) {
	$raw = (string) ( $row['payment_payloads'] ?? '' );
	if ( strlen( $raw ) > 262144 ) { return 'unknown'; }
	$payload = json_decode( $raw, true, 32 );
	if ( ! is_array( $payload ) ) { $payload = json_decode( stripslashes( $raw ), true, 32 ); }
	if ( ! is_array( $payload ) || empty( $row['transaction_id'] ) ) { return 'unknown'; }
	$id = (string) $row['order_id'];
	$transaction = (string) $row['transaction_id'];
	if ( 'stripe' === $row['payment_method'] ) {
		if ( 'payment_intent' !== ( $payload['object'] ?? '' ) || 'succeeded' !== ( $payload['status'] ?? '' ) || (float) ( $payload['amount_received'] ?? 0 ) <= 0 || ( $payload['id'] ?? '' ) !== $transaction || (string) ( $payload['metadata']['order_id'] ?? '' ) !== $id || ! is_bool( $payload['livemode'] ?? null ) ) { return 'unknown'; }
		return $payload['livemode'] ? 'live' : 'test';
	}
	if ( 'paypal' === $row['payment_method'] ) {
		$resource = $payload['resource'] ?? array();
		if ( 'PAYMENT.CAPTURE.COMPLETED' !== ( $payload['event_type'] ?? '' ) || 'COMPLETED' !== ( $resource['status'] ?? '' ) || (float) ( $resource['amount']['value'] ?? 0 ) <= 0 || ( $resource['id'] ?? '' ) !== $transaction || (string) ( $resource['custom_id'] ?? '' ) !== $id ) { return 'unknown'; }
		$modes = array();
		foreach ( (array) ( $resource['links'] ?? array() ) as $link ) {
			if ( 'self' !== ( $link['rel'] ?? '' ) ) { continue; }
			$url = parse_url( (string) ( $link['href'] ?? '' ) );
			if ( ! $url || 'https' !== ( $url['scheme'] ?? '' ) || ( $url['path'] ?? '' ) !== '/v2/payments/captures/' . $transaction ) { return 'unknown'; }
			$host = $url['host'] ?? '';
			if ( in_array( $host, array( 'api.paypal.com', 'api-m.paypal.com' ), true ) ) { $modes[] = 'live'; }
			elseif ( in_array( $host, array( 'api.sandbox.paypal.com', 'api-m.sandbox.paypal.com' ), true ) ) { $modes[] = 'test'; }
			else { return 'unknown'; }
		}
		$modes = array_values( array_unique( $modes ) );
		return 1 === count( $modes ) ? $modes[0] : 'unknown';
	}
	return 'unknown';
}

function cas_popularity_payment_mode( $row ) {
	$payload_mode = cas_popularity_payload_mode( $row );
	$evidence = json_decode( (string) ( $row['mode_evidence'] ?? '' ), true );
	$evidence_mode = 'unknown';
	if ( is_array( $evidence ) && 1 === ( $evidence['version'] ?? null ) && 'tutor_payment_hook' === ( $evidence['verified_by'] ?? '' ) && (string) ( $evidence['order_id'] ?? '' ) === (string) $row['order_id'] && ( $evidence['transaction_id'] ?? '' ) === $row['transaction_id'] && ( $evidence['payment_method'] ?? '' ) === $row['payment_method'] && in_array( $evidence['mode'] ?? '', array( 'live', 'test' ), true ) ) { $evidence_mode = $evidence['mode']; }
	if ( 'unknown' !== $payload_mode && 'unknown' !== $evidence_mode && $payload_mode !== $evidence_mode ) { return 'unknown'; }
	return 'unknown' !== $payload_mode ? $payload_mode : $evidence_mode;
}

/** Inspect only events Tutor has already signature-verified, bound to this native order. */
function cas_popularity_webhook_mode( $row, $raw ) {
	if ( strlen( $raw ) > 262144 ) { return 'unknown'; }
	$payload = json_decode( $raw, true, 32 );
	if ( ! is_array( $payload ) ) { return 'unknown'; }
	if ( 'paypal' === $row['payment_method'] ) { $row['payment_payloads'] = $raw; return cas_popularity_payload_mode( $row ); }
	if ( 'stripe' !== $row['payment_method'] || 'event' !== ( $payload['object'] ?? '' ) || ! is_bool( $payload['livemode'] ?? null ) || ! in_array( $payload['type'] ?? '', array( 'charge.succeeded', 'payment_intent.succeeded' ), true ) ) { return 'unknown'; }
	$object = $payload['data']['object'] ?? array();
	if ( ! is_bool( $object['livemode'] ?? null ) || $object['livemode'] !== $payload['livemode'] || (string) ( $object['metadata']['order_id'] ?? '' ) !== (string) $row['order_id'] ) { return 'unknown'; }
	$transaction = $row['transaction_id'];
	$intent = 'payment_intent' === ( $object['object'] ?? '' ) && ( $object['id'] ?? '' ) === $transaction && 'succeeded' === ( $object['status'] ?? '' ) && (float) ( $object['amount_received'] ?? 0 ) > 0;
	$charge = 'charge' === ( $object['object'] ?? '' ) && ( $object['payment_intent'] ?? '' ) === $transaction && true === ( $object['paid'] ?? null ) && true === ( $object['captured'] ?? null ) && (float) ( $object['amount'] ?? 0 ) > 0;
	return $intent || $charge ? ( $payload['livemode'] ? 'live' : 'test' ) : 'unknown';
}

/** Preserve minimal mode evidence when Tutor stores a null Stripe payload on the order. */
function cas_course_record_payment_evidence( $result ) {
	if ( ! is_object( $result ) || 'paid' !== ( $result->payment_status ?? '' ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! did_action( 'rest_api_init' ) ) { return; }
	$raw_id = $result->id ?? '';
	if ( ! is_scalar( $raw_id ) || ! ctype_digit( (string) $raw_id ) || (int) $raw_id < 1 ) { return; }
	try {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT id AS order_id,payment_method,payment_status,transaction_id FROM {$wpdb->prefix}tutor_orders WHERE id=%d", (int) $raw_id ), ARRAY_A );
		if ( ! $row || 'paid' !== $row['payment_status'] || $row['payment_method'] !== ( $result->payment_method ?? '' ) || $row['transaction_id'] !== ( $result->transaction_id ?? '' ) ) { return; }
		$raw = file_get_contents( 'php://input', false, null, 0, 262145 );
		$mode = is_string( $raw ) ? cas_popularity_webhook_mode( $row, $raw ) : 'unknown';
		if ( 'unknown' === $mode ) { return; }
		$evidence = array( 'version' => 1, 'verified_by' => 'tutor_payment_hook', 'order_id' => (int) $raw_id, 'payment_method' => $row['payment_method'], 'transaction_id' => $row['transaction_id'], 'mode' => $mode );
		\Tutor\Models\OrderMetaModel::update_meta( (int) $raw_id, '_cas_popularity_payment_evidence', wp_json_encode( $evidence ) );
	} catch ( \Throwable $error ) {
		// A reporting failure must never interrupt payment processing; unknowns remain excluded.
	}
}
add_action( 'tutor_order_payment_updated', 'cas_course_record_payment_evidence', 30 );

/** One purchase per order/course, even if an imported order has duplicate item rows. */
function cas_popularity_counts( array $rows, array $course_media ) {
	$seen = array(); $courses = array(); $media = array(); $excluded = array();
	foreach ( $rows as $row ) {
		if ( 'completed' !== $row['order_status'] || 'paid' !== $row['payment_status'] || 'single_order' !== $row['order_type'] || (float) $row['total_price'] <= 0 || (float) $row['item_amount'] <= 0 || 0.0 !== (float) $row['refund_amount'] || ! empty( $row['is_test_account'] ) || (int) $row['order_id'] < 1 || (int) $row['course_id'] < 1 ) { continue; }
		$order = (int) $row['order_id']; $course = (int) $row['course_id'];
		if ( 'live' !== cas_popularity_payment_mode( $row ) ) { $excluded[ $order ] = true; continue; }
		$key = $order . ':' . $course;
		if ( isset( $seen[ $key ] ) ) { continue; }
		$seen[ $key ] = true;
		$courses[ $course ] = ( $courses[ $course ] ?? 0 ) + 1;
		foreach ( array_unique( $course_media[ $course ] ?? array() ) as $medium ) { $media[ $medium ] = ( $media[ $medium ] ?? 0 ) + 1; }
	}
	arsort( $courses ); arsort( $media );
	return array( 'courses' => $courses, 'media' => $media, 'excluded_orders' => count( $excluded ) );
}

function cas_course_popularity_render( $filters ) {
	if ( ! current_user_can( 'manage_cas_content' ) ) { return; }
	global $wpdb;
	$from = $filters['from'] ? $filters['from'] . ' 00:00:00' : gmdate( 'Y-m-d 00:00:00', strtotime( '-12 months' ) );
	$to = $filters['to'] ? $filters['to'] . ' 23:59:59' : gmdate( 'Y-m-d 23:59:59' );
	$where = 'o.created_at_gmt BETWEEN %s AND %s'; $args = array( $from, $to );
	if ( $filters['course'] ) { $where .= ' AND i.item_id=%d'; $args[] = $filters['course']; }
	if ( $filters['order_id'] ) { $where .= ' AND o.id=%d'; $args[] = $filters['order_id']; }
	$rows = $wpdb->get_results( $wpdb->prepare(
		"SELECT o.id AS order_id,o.order_type,o.order_status,o.payment_status,o.total_price,o.refund_amount,o.payment_method,o.transaction_id,o.payment_payloads,i.item_id AS course_id,
		(SELECT em.meta_value FROM {$wpdb->prefix}tutor_ordermeta em WHERE em.order_id=o.id AND em.meta_key='_cas_popularity_payment_evidence' ORDER BY em.id DESC LIMIT 1) AS mode_evidence,
		COALESCE(NULLIF(i.discount_price,''),NULLIF(i.sale_price,''),i.regular_price) AS item_amount
		FROM {$wpdb->prefix}tutor_orders o INNER JOIN {$wpdb->prefix}tutor_order_items i ON i.order_id=o.id
		INNER JOIN {$wpdb->posts} p ON p.ID=i.item_id AND p.post_type='courses'
		WHERE {$where} AND o.order_status='completed' AND o.payment_status='paid' AND o.total_price>0 AND COALESCE(o.refund_amount,0)=0 AND o.order_type='single_order'
		AND NOT EXISTS (SELECT 1 FROM {$wpdb->usermeta} um WHERE um.user_id=o.user_id AND um.meta_key='_cas_test_account' AND um.meta_value='1')
		AND NOT EXISTS (SELECT 1 FROM {$wpdb->prefix}tutor_ordermeta rm WHERE rm.order_id=o.id AND rm.meta_key IN ('refund','partial_refund'))", $args
	), ARRAY_A );
	if ( ! is_array( $rows ) ) { echo '<section class="cas-course-popularity"><h2>Course popularity — live paid purchases</h2><p>Popularity could not be loaded. Please try again or contact the site administrator.</p></section>'; return; }
	$course_media = array();
	foreach ( array_unique( array_column( (array) $rows, 'course_id' ) ) as $id ) {
		$terms = wp_get_post_terms( (int) $id, 'course-category', array( 'fields' => 'names' ) );
		$course_media[ $id ] = is_wp_error( $terms ) ? array() : $terms;
	}
	$data = cas_popularity_counts( (array) $rows, $course_media );
	echo '<section class="cas-course-popularity"><h2>Course popularity — live paid purchases</h2><p>Uses the selected dates, Course ID and Order number (last 12 months when dates are empty). Only completed, paid individual-course purchases with verified live Stripe/PayPal evidence count. Test accounts, test payments, free purchases, recorded refunds and unverified payments are excluded. Order status filters do not change this qualification.</p>';
	foreach ( array( 'courses' => 'By course', 'media' => 'By Medium' ) as $key => $heading ) {
		echo '<h3>' . esc_html( $heading ) . '</h3><table class="widefat striped"><thead><tr><th>' . ( 'courses' === $key ? 'Course' : 'Medium' ) . '</th><th>Paid purchases</th></tr></thead><tbody>';
		if ( ! $data[ $key ] ) { echo '<tr><td colspan="2">No verified live paid purchases in this range yet.</td></tr>'; }
		foreach ( $data[ $key ] as $item => $count ) { echo '<tr><td>' . esc_html( 'courses' === $key ? get_the_title( (int) $item ) : $item ) . '</td><td>' . esc_html( (string) $count ) . '</td></tr>'; }
		echo '</tbody></table>';
	}
	echo '<p>' . esc_html( (string) $data['excluded_orders'] ) . ' paid order(s) excluded because the payment was in test mode or its mode could not be verified.</p>';
	echo '<p>Each order/course counts once. A course assigned to several media counts once in each, using its current Medium assignments; Medium totals can overlap. These are purchase counts, not revenue, views or bank settlements. Older payments without verifiable mode evidence remain excluded.</p></section>';
}
