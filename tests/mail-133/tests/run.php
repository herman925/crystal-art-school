<?php
// Local test of inc/cas-mail.php against Tutor 4.1.0 (free) with the manual gateway. Mail goes to Mailpit.
use Tutor\Models\OrderModel;

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;
$_SERVER['REQUEST_METHOD'] = 'POST';
function t( $name, $ok, $extra = '' ) {
	global $pass, $fail;
	$ok ? $pass++ : $fail++;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . ( $extra ? '  [' . $extra . ']' : '' ) . "\n";
}
function inbox() {
	$r = json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://mailpit:8025/api/v1/messages?limit=200' ) ), true );
	return $r['messages'] ?? array();
}
function clear_inbox() {
	wp_remote_request( 'http://mailpit:8025/api/v1/messages', array( 'method' => 'DELETE' ) );
}
function to_of( $m ) {
	return array_map( fn( $x ) => $x['Address'], $m['To'] );
}
function msg( $id ) {
	return json_decode( wp_remote_retrieve_body( wp_remote_get( 'http://mailpit:8025/api/v1/message/' . $id ) ), true );
}
function subjects() {
	return array_map( fn( $m ) => $m['Subject'], inbox() );
}

update_option( 'tutor_option', array_merge( (array) get_option( 'tutor_option' ), array( 'monetize_by' => 'tutor', 'currency_code' => 'GBP', 'currency_position' => 'left' ) ) );
delete_option( 'cas_mail_day' );
delete_option( 'cas_mail_queue' );
clear_inbox();

$mk = function ( $login, $role = 'subscriber' ) {
	return wp_insert_user( array( 'user_login' => $login, 'user_pass' => 'pw', 'user_email' => $login . '@example.test', 'role' => $role, 'display_name' => $login ) );
};
$buyer = $mk( 'buyer' );
$on    = $mk( 'optin' );
$off   = $mk( 'optout' );
$none  = $mk( 'noflag' );
$adm   = $mk( 'adminfan', 'administrator' );
update_user_meta( $on, 'cas_notify_new_course', '1' );
update_user_meta( $on, 'cas_notify_promos', '1' );
update_user_meta( $off, 'cas_notify_new_course', '0' );
update_user_meta( $adm, 'cas_notify_new_course', '1' );
update_user_meta( $adm, 'cas_notify_promos', '1' );

$course = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => 'Clouds & Skies', 'post_status' => 'draft' ) );
update_post_meta( $course, '_tutor_course_price_type', 'paid' );

$model     = new OrderModel();
$new_order = function ( $uid, $total = 25.00 ) use ( $model, $course ) {
	return $model->create_order(
		array(
			'user_id'        => $uid,
			'order_type'     => 'single_order',
			'order_status'   => 'pending',
			'payment_status' => 'unpaid',
			'subtotal_price' => $total,
			'pre_tax_price'  => $total,
			'total_price'    => $total,
			'net_payment'    => $total,
			'payment_method' => 'manual',
			'created_at_gmt' => current_time( 'mysql', true ),
			'created_by'     => $uid,
			'updated_at_gmt' => current_time( 'mysql', true ),
			'updated_by'     => $uid,
			'items'          => array( array( 'item_id' => $course, 'regular_price' => $total, 'sale_price' => '', 'discount_price' => '' ) ),
		)
	);
};

// 1. Paid order: receipt and enrolment, buyer only.
wp_update_post( array( 'ID' => $course, 'post_status' => 'publish' ) );
delete_option( 'cas_mail_queue' );
clear_inbox();
$oid = $new_order( $buyer );
$model->mark_as_paid( $oid );
$m    = inbox();
$subs = subjects();
t( '1a receipt sent', (bool) array_filter( $subs, fn( $s ) => str_starts_with( $s, 'Your receipt from' ) ), implode( ' | ', $subs ) );
t( '1b enrolment sent', (bool) array_filter( $subs, fn( $s ) => str_starts_with( $s, 'You are enrolled in' ) ) );
t( '1c exactly 2 mails', 2 === count( $m ), (string) count( $m ) );
$all_to = array();
foreach ( $m as $x ) {
	$all_to = array_merge( $all_to, to_of( $x ) );
}
t( '1d only the buyer is a recipient', array( 'buyer@example.test' ) === array_values( array_unique( $all_to ) ) );
$full = array();
foreach ( $m as $x ) {
	if ( str_starts_with( $x['Subject'], 'Your receipt' ) ) {
		$full = msg( $x['ID'] );
	}
}
t( '1e receipt has HTML and plain parts', ! empty( $full['HTML'] ) && ! empty( $full['Text'] ) );
t( '1f receipt shows item, order number, total', str_contains( $full['Text'], 'Clouds & Skies' ) && str_contains( $full['Text'], 'Order number: #' . $oid ) && str_contains( $full['Text'], 'Total paid: £25.00' ), $full['Text'] );
t( '1g HTML escapes the ampersand', str_contains( $full['HTML'], 'Clouds &amp; Skies' ) && ! str_contains( $full['HTML'], 'Clouds & Skies' ) );
t( '1h From is the site mailbox', 'hello@crystalau.test' === $full['From']['Address'] );
t( '1i no cc or bcc', empty( $full['Cc'] ) && empty( $full['Bcc'] ) );

// 2. Repeat events do not repeat mail.
clear_inbox();
do_action( 'tutor_order_payment_status_changed', $oid, 'unpaid', 'paid' );
$enrol = $GLOBALS['wpdb']->get_var( $GLOBALS['wpdb']->prepare( "SELECT ID FROM {$GLOBALS['wpdb']->posts} WHERE post_type='tutor_enrolled' AND post_author=%d AND post_parent=%d", $buyer, $course ) );
do_action( 'tutor_after_enrolled', $course, $buyer, $enrol );
t( '2 repeat events send nothing', 0 === count( inbox() ), (string) count( inbox() ) );

// 3. Refund through Tutor's own handler, partial then full.
wp_set_current_user( 1 );
$call_refund = function ( $amount ) use ( $oid ) {
	$_POST    = array( '_tutor_nonce' => wp_create_nonce( tutor()->nonce_action ), 'order_id' => $oid, 'amount' => $amount, 'reason' => 'internal note do not send', 'is_remove_enrolment' => '' );
	$_REQUEST = $_POST;
	add_filter( 'wp_die_ajax_handler', fn() => function () { throw new Exception( 'die' ); } );
	add_filter( 'wp_doing_ajax', '__return_true' );
	try {
		( new Tutor\Ecommerce\OrderController( false ) )->make_refund();
	} catch ( Throwable $e ) {
		// json_response ends the request with wp_die.
	}
	remove_all_filters( 'wp_die_ajax_handler' );
};
clear_inbox();
$call_refund( 5 );
$m = inbox();
t( '3a partial refund mail sent', 1 === count( $m ) && str_starts_with( $m[0]['Subject'], 'Your refund for order #' . $oid ), implode( '|', subjects() ) );
if ( $m ) {
	$r = msg( $m[0]['ID'] );
	t( '3b refund text has amount, no staff reason', str_contains( $r['Text'], 'refunded £5.00' ) && ! str_contains( $r['Text'], 'internal note' ), $r['Text'] );
	t( '3c refund goes to the buyer only', array( 'buyer@example.test' ) === to_of( $m[0] ) );
}
clear_inbox();
$call_refund( 20 );
$m = inbox();
t( '3d second refund sends its own notice', 1 === count( $m ), (string) count( $m ) );
if ( $m ) {
	$r = msg( $m[0]['ID'] );
	t( '3e shows refunded so far', str_contains( $r['Text'], 'Refunded so far on this order: £25.00' ), $r['Text'] );
}
wp_set_current_user( 0 );

// 4. Admin buyer gets nothing.
clear_inbox();
$o2 = $new_order( $adm );
$model->mark_as_paid( $o2 );
t( '4 no mail to an administrator buyer', 0 === count( inbox() ), implode( '|', subjects() ) );

// 5. Free order: no receipt.
clear_inbox();
$o3 = $new_order( $on, 0.00 );
$model->mark_as_paid( $o3 );
t( '5 free order has no receipt', ! array_filter( subjects(), fn( $s ) => str_starts_with( $s, 'Your receipt' ) ), implode( '|', subjects() ) );

// 6. New course: queue reaches only opted-in non-admins; private sends nothing; no repeat.
clear_inbox();
delete_option( 'cas_mail_queue' );
delete_option( 'cas_mail_day' );
$c2 = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => 'Private test', 'post_status' => 'private' ) );
t( '6a private course queues nothing', ! get_option( 'cas_mail_queue' ) );
$c3 = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => 'Urban Sketching', 'post_status' => 'publish' ) );
$q  = get_option( 'cas_mail_queue' );
t( '6b publish queues one job with exactly the opted-in user', is_array( $q ) && 1 === count( $q ) && array( $on ) === $q[0]['users'], json_encode( $q ? $q[0]['users'] : null ) );
cas_mail_bulk_tick();
$m = inbox();
t( '6c one new-course mail to the opted-in student', 1 === count( $m ) && array( 'optin@example.test' ) === to_of( $m[0] ) && 'New course: Urban Sketching' === $m[0]['Subject'], implode( '|', subjects() ) );
if ( $m ) {
	$n = msg( $m[0]['ID'] );
	t( '6d footer explains why and links settings', str_contains( $n['Text'], 'you chose to get new-course emails' ) && str_contains( $n['Text'], 'Turn it off in your dashboard settings:' ), $n['Text'] );
}
t( '6e queue emptied', ! get_option( 'cas_mail_queue' ) );
wp_update_post( array( 'ID' => $c3, 'post_status' => 'draft' ) );
wp_update_post( array( 'ID' => $c3, 'post_status' => 'publish' ) );
t( '6f republish does not announce twice', ! get_option( 'cas_mail_queue' ) );

// 7. Opt out while queued: nothing sent.
clear_inbox();
$c4 = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => 'Oils', 'post_status' => 'publish' ) );
update_user_meta( $on, 'cas_notify_new_course', '0' );
cas_mail_bulk_tick();
t( '7 opt-out while queued sends nothing', 0 === count( inbox() ), implode( '|', subjects() ) );
update_user_meta( $on, 'cas_notify_new_course', '1' );

// 8. Promo reaches promo opt-ins only.
clear_inbox();
delete_option( 'cas_mail_queue' );
$n = cas_mail_queue_promo( 'Studio news', 'Studio news', array( 'A short note.' ), 'Read more', home_url( '/' ) );
t( '8a promo queued for exactly 1 user', 1 === $n, (string) $n );
cas_mail_bulk_tick();
$m = inbox();
t( '8b promo mail to the promo opt-in', 1 === count( $m ) && array( 'optin@example.test' ) === to_of( $m[0] ), implode( '|', subjects() ) );

// 9. Daily cap: bulk waits when only the reserve is left, receipts still go out.
clear_inbox();
delete_option( 'cas_mail_queue' );
update_option( 'cas_mail_day', array( 'date' => gmdate( 'Y-m-d' ), 'count' => CAS_MAIL_DAILY_CAP - CAS_MAIL_BULK_RESERVE ) );
cas_mail_queue_promo( 'Held', 'Held', array( 'x' ), '', '' );
cas_mail_bulk_tick();
t( '9a bulk sends nothing when only the reserve is left', 0 === count( inbox() ) && 1 === count( get_option( 'cas_mail_queue' ) ) );
$o4 = $new_order( $buyer, 10 );
$model->mark_as_paid( $o4 );
t( '9b receipt still goes out', (bool) array_filter( subjects(), fn( $s ) => str_starts_with( $s, 'Your receipt' ) ), implode( '|', subjects() ) );
update_option( 'cas_mail_day', array( 'date' => gmdate( 'Y-m-d', time() - 86400 ), 'count' => 99 ) );
t( '9c counter resets on a new day', 0 === cas_mail_sent_today() );
t( '9d flag helper: only the three names, only "1"', cas_notify_enabled( $on, 'cas_notify_promos' ) && ! cas_notify_enabled( $off, 'cas_notify_new_course' ) && ! cas_notify_enabled( $none, 'cas_notify_new_course' ) && ! cas_notify_enabled( $on, 'other_flag' ) );

echo "\npass=" . $GLOBALS['pass'] . ' fail=' . $GLOBALS['fail'] . "\n";
echo "audit log:\n" . ( file_exists( '/tmp/audit.log' ) ? file_get_contents( '/tmp/audit.log' ) : '(none)' );
