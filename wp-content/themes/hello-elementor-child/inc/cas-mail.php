<?php
/**
 * The site's own emails: receipt, enrolment, refund, and the opt-in new-course and promotion mails.
 *
 * WHY THEME CODE. Tutor Pro's Email and Notifications addons stay off (issue #119): the Email addon
 * emails every administrator by default, adds a public `?tutor_cron=1` queue trigger and replaces
 * the password-reset mail. Everything here goes through wp_mail(), so it uses the existing SMTP
 * hook in mu-plugins/cas-audit-log.php (the hello@ mailbox, 100 sends a day).
 *
 * WHO GETS WHAT.
 *   - Receipt, enrolment and refund notices go to the buyer or student only, never to an
 *     administrator, and they are sent at once. They are never held back by the daily cap.
 *   - New-course and promotion mails go only to users whose usermeta flag is '1'
 *     (cas_notify_new_course, cas_notify_promos). The flags are written by the dashboard
 *     switches (issue #125); a missing flag means off. This file only reads them.
 *   - No mail is sent to a user who can manage options.
 *
 * THE DAILY CAP. Bulk mails go into a queue and a WP-cron job sends a few every 15 minutes. They may
 * use only what is left of the cap after CAS_MAIL_BULK_RESERVE sends are held back for receipts and
 * refunds, so bulk mail cannot starve a receipt. WP-cron runs on page visits, so a quiet site sends
 * bulk mail later, never earlier.
 *
 * WHAT IS NOT HERE. No wording that is not data from the order or the course: no refund policy, no
 * delivery times, no promises. The words are in cas_mail_copy() so the owner can approve or change
 * them in one place. There is no one-click unsubscribe link; every bulk mail points to the
 * dashboard settings instead.
 *
 * Each mail is sent once: cas_mail_once() takes a lock in wp_options before sending.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CAS_MAIL_DAILY_CAP' ) ) {
	define( 'CAS_MAIL_DAILY_CAP', 100 ); // The mailbox limit on the trial plan.
}
if ( ! defined( 'CAS_MAIL_BULK_RESERVE' ) ) {
	define( 'CAS_MAIL_BULK_RESERVE', 40 ); // Sends held back each day for receipts, enrolments and refunds.
}
if ( ! defined( 'CAS_MAIL_BULK_BATCH' ) ) {
	define( 'CAS_MAIL_BULK_BATCH', 10 ); // Most bulk mails one cron run sends.
}

/* -------------------------------------------------------------------------
 * The words
 * ---------------------------------------------------------------------- */

/**
 * Every fixed sentence the mails use. Placeholders are sprintf() markers.
 *
 * @return array<string,string>
 */
function cas_mail_copy() {
	return array(
		'receipt_subject'      => 'Your receipt from %s',
		'receipt_heading'      => 'Thank you for your order',
		'receipt_intro'        => 'Here is your receipt for order #%s.',
		'receipt_cta'          => 'Go to your dashboard',
		'receipt_reason'       => 'You are receiving this email because you placed an order at %s.',

		'enrolled_subject'     => 'You are enrolled in %s',
		'enrolled_heading'     => 'You are enrolled',
		'enrolled_intro'       => 'You are now enrolled in %s.',
		'enrolled_cta'         => 'Go to the course',
		'enrolled_reason'      => 'You are receiving this email because you were enrolled in a course at %s.',

		'refund_subject'       => 'Your refund for order #%s',
		'refund_heading'       => 'Your refund',
		'refund_intro'         => 'We have refunded %1$s for order #%2$s.',
		'refund_total'         => 'Refunded so far on this order: %s.',
		'refund_cancelled'     => 'The enrolment for this order has been cancelled.',
		'refund_cta'           => 'Go to your dashboard',
		'refund_reason'        => 'You are receiving this email because a refund was made on your order at %s.',

		'new_course_subject'   => 'New course: %s',
		'new_course_heading'   => 'A new course has been published',
		'new_course_intro'     => '%s is now on the site.',
		'new_course_cta'       => 'View the course',

		'optin_reason'         => 'You are receiving this email because you chose to get %2$s from %1$s.',
		'optin_kind_new_course' => 'new-course emails',
		'optin_kind_promos'    => 'promotion emails',
		'settings_link'        => 'Turn it off in your dashboard settings',
	);
}

/* -------------------------------------------------------------------------
 * Flags, guards, counters
 * ---------------------------------------------------------------------- */

/**
 * Whether a user switched a notification on. Off unless the flag is exactly '1'.
 *
 * @param int    $user_id User id.
 * @param string $flag    One of cas_notify_new_course, cas_notify_reminders, cas_notify_promos.
 * @return bool
 */
function cas_notify_enabled( $user_id, $flag ) {
	$flags = array( 'cas_notify_new_course', 'cas_notify_reminders', 'cas_notify_promos' );

	return in_array( $flag, $flags, true ) && '1' === (string) get_user_meta( (int) $user_id, $flag, true );
}

/**
 * The address to write to, or '' when this user must not be emailed at all.
 *
 * @param int $user_id User id.
 * @return string
 */
function cas_mail_address( $user_id ) {
	$user = get_userdata( (int) $user_id );

	if ( ! $user || user_can( $user, 'manage_options' ) || ! is_email( $user->user_email ) ) {
		return '';
	}

	return $user->user_email;
}

/**
 * Take a once-only lock. add_option() fails when the row exists, so two requests cannot both win.
 *
 * @param string $key Lock name.
 * @return bool True when this caller may send.
 */
function cas_mail_once( $key ) {
	return add_option( 'cas_mail_sent_' . sanitize_key( $key ), gmdate( 'Y-m-d H:i:s' ), '', 'no' );
}

/**
 * Sends made today (UTC). The counter resets when the date changes.
 *
 * @return int
 */
function cas_mail_sent_today() {
	$day = get_option( 'cas_mail_day' );

	return ( is_array( $day ) && gmdate( 'Y-m-d' ) === $day['date'] ) ? (int) $day['count'] : 0;
}

/**
 * Count one send. Receipts count too, so bulk mail sees what is really left.
 */
function cas_mail_count_send() {
	update_option(
		'cas_mail_day',
		array(
			'date'  => gmdate( 'Y-m-d' ),
			'count' => cas_mail_sent_today() + 1,
		),
		false
	);
}

/**
 * Write to the audit log when it exists. Event names and ids only, never an address.
 *
 * @param string $event  Event name.
 * @param string $detail Short detail.
 */
function cas_mail_log( $event, $detail = '' ) {
	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( $event, $detail );
	}
}

/* -------------------------------------------------------------------------
 * The template
 * ---------------------------------------------------------------------- */

/**
 * The dashboard settings address, where the three switches live.
 *
 * @return string
 */
function cas_mail_settings_url() {
	if ( function_exists( 'tutor_utils' ) ) {
		return tutor_utils()->tutor_dashboard_url( 'account/settings' );
	}

	return home_url( '/dashboard/' );
}

/**
 * Build the HTML and plain-text bodies from one description.
 *
 * $mail keys: heading (string), lines (string[] paragraphs), rows (array of [label, value]),
 * cta_label and cta_url (optional), reason (string, the footer sentence), settings (bool, add the
 * dashboard settings link to the footer).
 *
 * Colours are the palette in DESIGN.md §3. Email clients ignore stylesheets and web fonts, so the
 * styles are inline and the type is a safe serif and sans stack.
 *
 * @param array $mail Description.
 * @return array{0:string,1:string} HTML, plain text.
 */
function cas_mail_render( array $mail ) {
	$site     = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$home     = home_url( '/' );
	$lines    = isset( $mail['lines'] ) ? (array) $mail['lines'] : array();
	$rows     = isset( $mail['rows'] ) ? (array) $mail['rows'] : array();
	$cta_url  = isset( $mail['cta_url'] ) ? (string) $mail['cta_url'] : '';
	$cta_text = isset( $mail['cta_label'] ) ? (string) $mail['cta_label'] : '';
	$settings = ! empty( $mail['settings'] );

	$serif = "Georgia,'Times New Roman',serif";
	$sans  = "Arial,Helvetica,sans-serif";

	// The site name in text, not the logo image: the logo is white artwork made for the dark header and
	// would vanish on the oak band.
	$head = '<a href="' . esc_url( $home ) . '" style="color:#2b2823;font:600 22px ' . $serif . ';text-decoration:none;">' . esc_html( $site ) . '</a>';

	$html  = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $mail['heading'] ) . '</title></head>';
	$html .= '<body style="margin:0;padding:0;background:#ede1d0;">';
	$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#ede1d0;"><tr><td align="center" style="padding:24px 12px;">';
	$html .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#f3ede1;border:1px solid #cdc7bd;">';
	$html .= '<tr><td style="background:#cab389;padding:18px 28px;">' . $head . '</td></tr>';
	$html .= '<tr><td style="padding:28px 28px 8px;color:#474138;font:400 16px/1.55 ' . $sans . ';">';
	$html .= '<h1 style="margin:0 0 16px;color:#2b2823;font:600 26px/1.25 ' . $serif . ';">' . esc_html( $mail['heading'] ) . '</h1>';
	foreach ( $lines as $line ) {
		$html .= '<p style="margin:0 0 14px;">' . esc_html( $line ) . '</p>';
	}
	if ( $rows ) {
		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 18px;border-top:1px solid #cdc7bd;">';
		foreach ( $rows as $row ) {
			$html .= '<tr><td style="padding:9px 12px 9px 0;border-bottom:1px solid #cdc7bd;color:#474138;font:400 15px/1.4 ' . $sans . ';">' . esc_html( $row[0] ) . '</td>';
			$html .= '<td align="right" style="padding:9px 0;border-bottom:1px solid #cdc7bd;color:#2b2823;font:600 15px/1.4 ' . $sans . ';">' . esc_html( $row[1] ) . '</td></tr>';
		}
		$html .= '</table>';
	}
	if ( $cta_url && $cta_text ) {
		$html .= '<p style="margin:8px 0 22px;"><a href="' . esc_url( $cta_url ) . '" style="display:inline-block;background:#9a6436;color:#ffffff;padding:12px 24px;font:600 15px ' . $sans . ';text-decoration:none;">' . esc_html( $cta_text ) . '</a></p>';
	}
	$html .= '</td></tr>';
	$html .= '<tr><td style="padding:16px 28px 26px;border-top:1px solid #cdc7bd;color:#474138;font:400 13px/1.5 ' . $sans . ';">';
	$html .= esc_html( $mail['reason'] );
	if ( $settings ) {
		$html .= ' <a href="' . esc_url( cas_mail_settings_url() ) . '" style="color:#4d6625;">' . esc_html( cas_mail_copy()['settings_link'] ) . '</a>';
	}
	$html .= '<br><a href="' . esc_url( $home ) . '" style="color:#4d6625;">' . esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $home ) ) ) . '</a>';
	$html .= '</td></tr></table></td></tr></table></body></html>';

	$text = $mail['heading'] . "\n\n";
	foreach ( $lines as $line ) {
		$text .= $line . "\n\n";
	}
	foreach ( $rows as $row ) {
		$text .= $row[0] . ': ' . $row[1] . "\n";
	}
	if ( $rows ) {
		$text .= "\n";
	}
	if ( $cta_url && $cta_text ) {
		$text .= $cta_text . ': ' . $cta_url . "\n\n";
	}
	$text .= "--\n" . $mail['reason'];
	if ( $settings ) {
		$text .= ' ' . cas_mail_copy()['settings_link'] . ': ' . cas_mail_settings_url();
	}
	$text .= "\n" . $home . "\n";

	return array( $html, $text );
}

/**
 * Send one mail: an HTML part and a plain-text part, through wp_mail().
 *
 * wp_mail() has no text alternative of its own, so it is set on the PHPMailer object for this one
 * call. The SMTP settings and the From address come from the phpmailer_init and wp_mail_from hooks
 * in mu-plugins/cas-audit-log.php.
 *
 * @param int    $user_id Recipient user id.
 * @param string $subject Subject line.
 * @param array  $mail    Description for cas_mail_render().
 * @param string $tag     Short label for the audit log, for example receipt:12.
 * @return bool
 */
function cas_mail_send( $user_id, $subject, array $mail, $tag ) {
	$to = cas_mail_address( $user_id );
	if ( '' === $to ) {
		return false;
	}

	list( $html, $text ) = cas_mail_render( $mail );

	$alt = static function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName -- PHPMailer's own property.
	};
	$fail_reason = '';
	$catch       = static function ( $error ) use ( &$fail_reason ) {
		$fail_reason = $error->get_error_message();
	};
	add_action( 'phpmailer_init', $alt, 20 );
	add_action( 'wp_mail_failed', $catch );

	$sent = wp_mail(
		$to,
		trim( preg_replace( '/[\r\n]+/', ' ', wp_strip_all_tags( $subject ) ) ),
		$html,
		array( 'Content-Type: text/html; charset=UTF-8' )
	);

	remove_action( 'phpmailer_init', $alt, 20 );
	remove_action( 'wp_mail_failed', $catch );

	if ( $sent ) {
		cas_mail_count_send();
		cas_mail_log( 'mail_sent', $tag );
	} else {
		cas_mail_log( 'mail_failed', $tag . ' ' . substr( $fail_reason, 0, 120 ) );
	}

	return $sent;
}

/* -------------------------------------------------------------------------
 * Receipt, enrolment and refund: sent at once, to the buyer only
 * ---------------------------------------------------------------------- */

/**
 * A price as plain text in the site's own format, or '' for a missing amount.
 *
 * @param mixed $amount Amount.
 * @return string
 */
function cas_mail_price( $amount ) {
	return wp_strip_all_tags( html_entity_decode( tutor_get_formatted_price( (float) $amount ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * The order's line items as [title, price] rows.
 *
 * @param object $order Order from OrderModel::get_order_by_id().
 * @return array<int,array{0:string,1:string}>
 */
function cas_mail_order_rows( $order ) {
	$model = new \Tutor\Models\OrderModel();
	$rows  = array();

	foreach ( (array) $order->items as $item ) {
		$rows[] = array( html_entity_decode( (string) $item->title, ENT_QUOTES, 'UTF-8' ), cas_mail_price( $model->get_order_item_display_price( $item ) ) );
	}

	return $rows;
}

/**
 * Receipt, when an order becomes paid. Tutor fires this from the payment webhook (Stripe
 * charge.succeeded) and from an administrator marking an order paid, and can fire it twice for
 * one order, so the once-only lock decides.
 *
 * @param int    $order_id    Order id.
 * @param string $prev_status Previous payment status.
 * @param string $new_status  New payment status.
 */
function cas_mail_receipt( $order_id, $prev_status, $new_status ) {
	if ( \Tutor\Models\OrderModel::PAYMENT_PAID !== $new_status ) {
		return;
	}

	$order = ( new \Tutor\Models\OrderModel() )->get_order_by_id( $order_id );
	if ( ! $order || (float) $order->total_price <= 0 || '' === cas_mail_address( $order->user_id ) ) {
		return; // Free orders get the enrolment mail only.
	}
	if ( ! cas_mail_once( 'receipt_' . (int) $order_id ) ) {
		return;
	}

	$copy = cas_mail_copy();
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$rows = cas_mail_order_rows( $order );

	$rows[] = array( 'Order number', '#' . (int) $order->id );
	$rows[] = array( 'Date', (string) $order->created_at_readable );
	$rows[] = array( 'Payment method', (string) $order->payment_method_readable );
	$off    = (float) $order->discount_amount + (float) $order->coupon_amount;
	if ( $off > 0 ) {
		$rows[] = array( 'Discount', '-' . cas_mail_price( $off ) );
	}
	if ( (float) $order->tax_amount > 0 ) {
		$rows[] = array( 'Tax', cas_mail_price( $order->tax_amount ) );
	}
	$rows[] = array( 'Total paid', cas_mail_price( $order->total_price ) );

	cas_mail_send(
		$order->user_id,
		sprintf( $copy['receipt_subject'], $site ),
		array(
			'heading'   => $copy['receipt_heading'],
			'lines'     => array( sprintf( $copy['receipt_intro'], (int) $order->id ) ),
			'rows'      => $rows,
			'cta_label' => $copy['receipt_cta'],
			'cta_url'   => tutor_utils()->tutor_dashboard_url(),
			'reason'    => sprintf( $copy['receipt_reason'], $site ),
		),
		'receipt order=' . (int) $order_id
	);
}
add_action( 'tutor_order_payment_status_changed', 'cas_mail_receipt', 20, 3 );

/**
 * Enrolment confirmation, when a student is enrolled and the enrolment is completed.
 *
 * @param int $course_id Course id.
 * @param int $user_id   Student id.
 * @param int $enrol_id  Enrolment post id.
 */
function cas_mail_enrolled( $course_id, $user_id, $enrol_id ) {
	if ( 'courses' !== get_post_type( $course_id ) || '' === cas_mail_address( $user_id ) ) {
		return;
	}
	// One mail per order and course. Tutor can create a second enrolment record when it re-runs an
	// order, and that must not send a second mail. Enrolments with no order (free, or added by
	// hand) are keyed by the enrolment record.
	$order_id = (int) get_post_meta( $enrol_id, \Tutor\Models\EnrollmentModel::ENROLLMENT_ORDER_ID_META, true );
	$lock     = $order_id ? 'enrol_o' . $order_id . '_c' . (int) $course_id : 'enrol_' . (int) $enrol_id;
	if ( ! cas_mail_once( $lock ) ) {
		return;
	}

	$copy  = cas_mail_copy();
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$title = html_entity_decode( get_the_title( $course_id ), ENT_QUOTES, 'UTF-8' );

	cas_mail_send(
		$user_id,
		sprintf( $copy['enrolled_subject'], $title ),
		array(
			'heading'   => $copy['enrolled_heading'],
			'lines'     => array( sprintf( $copy['enrolled_intro'], $title ) ),
			'cta_label' => $copy['enrolled_cta'],
			'cta_url'   => get_permalink( $course_id ),
			'reason'    => sprintf( $copy['enrolled_reason'], $site ),
		),
		'enrolled course=' . (int) $course_id . ' enrol=' . (int) $enrol_id
	);
}
add_action( 'tutor_after_enrolled', 'cas_mail_enrolled', 20, 3 );

/**
 * Whether the enrolment linked to this order is cancelled. The order's own status is no proof: an
 * order can be refunded and cancelled while its enrolment stays completed. So this reads the
 * enrolment records that point at this order (post meta ENROLLMENT_ORDER_ID_META) for the courses
 * in the order, and answers yes only when one of them has the status `cancel`.
 *
 * @param object $order Order from OrderModel::get_order_by_id().
 * @return bool
 */
function cas_mail_order_enrolment_cancelled( $order ) {
	global $wpdb;

	$courses = array_filter( array_map( static function ( $item ) {
		return (int) $item->id;
	}, (array) $order->items ) );
	if ( ! $courses ) {
		return false;
	}

	$in = implode( ',', $courses ); // Integers only.

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s AND m.meta_value = %d
			WHERE p.post_type = %s AND p.post_status = %s AND p.post_parent IN ( {$in} ) LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $in is a list of integers.
			\Tutor\Models\EnrollmentModel::ENROLLMENT_ORDER_ID_META,
			(int) $order->id,
			\Tutor\Models\EnrollmentModel::POST_TYPE,
			\Tutor\Models\EnrollmentModel::STATUS_CANCEL
		)
	);
}

/**
 * Refund notice, after an administrator refunds an order in full or in part. Tutor has already
 * saved the new refund total when this fires. The lock key holds that total, so a second refund
 * on the same order is a new notice and a repeat of the same event is not.
 *
 * @param int    $order_id Order id.
 * @param float  $amount   Amount refunded by this action.
 * @param string $reason   The administrator's reason. It is not sent: it is written for staff.
 */
function cas_mail_refund( $order_id, $amount, $reason = '' ) {
	$order = ( new \Tutor\Models\OrderModel() )->get_order_by_id( $order_id );
	if ( ! $order || '' === cas_mail_address( $order->user_id ) ) {
		return;
	}
	if ( ! cas_mail_once( 'refund_' . (int) $order_id . '_' . (int) round( (float) $order->refund_amount * 100 ) ) ) {
		return;
	}

	$copy  = cas_mail_copy();
	$site  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$lines = array( sprintf( $copy['refund_intro'], cas_mail_price( $amount ), (int) $order->id ) );

	if ( (float) $order->refund_amount > (float) $amount ) {
		$lines[] = sprintf( $copy['refund_total'], cas_mail_price( $order->refund_amount ) );
	}
	if ( cas_mail_order_enrolment_cancelled( $order ) ) {
		$lines[] = $copy['refund_cancelled'];
	}

	cas_mail_send(
		$order->user_id,
		sprintf( $copy['refund_subject'], (int) $order->id ),
		array(
			'heading'   => $copy['refund_heading'],
			'lines'     => $lines,
			'cta_label' => $copy['refund_cta'],
			'cta_url'   => tutor_utils()->tutor_dashboard_url(),
			'reason'    => sprintf( $copy['refund_reason'], $site ),
		),
		'refund order=' . (int) $order_id
	);
}
add_action( 'tutor_after_order_refund', 'cas_mail_refund', 20, 3 );

/* -------------------------------------------------------------------------
 * Opt-in bulk mail: queued, capped, checked again at send time
 * ---------------------------------------------------------------------- */

/**
 * Queue a mail for every user whose flag is '1'.
 *
 * $campaign keys: type (new_course or promos), subject, heading, lines, cta_label, cta_url.
 * The flag is read again when each mail goes out, so someone who opts out while the queue is
 * waiting gets nothing more.
 *
 * @param string $flag     cas_notify_new_course or cas_notify_promos.
 * @param array  $campaign Campaign description.
 * @return int Number of recipients queued.
 */
function cas_mail_queue_campaign( $flag, array $campaign ) {
	if ( ! in_array( $flag, array( 'cas_notify_new_course', 'cas_notify_promos' ), true ) ) {
		return 0;
	}

	$ids = get_users(
		array(
			'meta_key'   => $flag, // phpcs:ignore WordPress.DB.SlowDBQuery -- one small query per campaign.
			'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'     => 'ID',
		)
	);
	$ids = array_values( array_filter( array_map( 'intval', $ids ), static function ( $id ) {
		return '' !== cas_mail_address( $id );
	} ) );
	if ( ! $ids ) {
		return 0;
	}

	$queue   = get_option( 'cas_mail_queue', array() );
	$queue   = is_array( $queue ) ? $queue : array();
	$queue[] = array_merge( $campaign, array( 'flag' => $flag, 'users' => $ids, 'pos' => 0 ) );
	update_option( 'cas_mail_queue', $queue, false );
	cas_mail_log( 'mail_queued', $flag . ' recipients=' . count( $ids ) );

	return count( $ids );
}

/**
 * Promotion mail. Nothing calls this on its own: it is the entry point for a promotion the owner
 * decides to send, and it reaches only users who switched promotions on.
 *
 * @param string $subject   Subject line.
 * @param string $heading   Heading.
 * @param string $body      Paragraphs, one per array item.
 * @param string $cta_label Button label, or ''.
 * @param string $cta_url   Button address, or ''.
 * @return int Number of recipients queued.
 */
function cas_mail_queue_promo( $subject, $heading, array $body, $cta_label = '', $cta_url = '' ) {
	return cas_mail_queue_campaign(
		'cas_notify_promos',
		array(
			'subject'   => $subject,
			'heading'   => $heading,
			'lines'     => $body,
			'cta_label' => $cta_label,
			'cta_url'   => $cta_url,
		)
	);
}

/**
 * New-course mail, queued the first time a course becomes public. Private and draft courses send
 * nothing, and a course that is unpublished and published again is not announced twice.
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       The post.
 */
function cas_mail_new_course( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || 'courses' !== $post->post_type || wp_installing() ) {
		return;
	}
	if ( get_post_meta( $post->ID, '_cas_new_course_mailed', true ) ) {
		return;
	}
	update_post_meta( $post->ID, '_cas_new_course_mailed', gmdate( 'Y-m-d H:i:s' ) );

	$copy  = cas_mail_copy();
	$title = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );

	cas_mail_queue_campaign(
		'cas_notify_new_course',
		array(
			'subject'   => sprintf( $copy['new_course_subject'], $title ),
			'heading'   => $copy['new_course_heading'],
			'lines'     => array( sprintf( $copy['new_course_intro'], $title ) ),
			'cta_label' => $copy['new_course_cta'],
			'cta_url'   => get_permalink( $post ),
		)
	);
}
add_action( 'transition_post_status', 'cas_mail_new_course', 10, 3 );

/**
 * How many bulk mails may go out right now: what is left of the cap after the reserve.
 *
 * @return int
 */
function cas_mail_bulk_room() {
	return max( 0, min( CAS_MAIL_BULK_BATCH, CAS_MAIL_DAILY_CAP - CAS_MAIL_BULK_RESERVE - cas_mail_sent_today() ) );
}

/**
 * The cron job: send the next few queued mails.
 */
function cas_mail_bulk_tick() {
	$queue = get_option( 'cas_mail_queue', array() );
	if ( ! is_array( $queue ) || ! $queue ) {
		return;
	}
	$room = cas_mail_bulk_room();

	foreach ( $queue as $i => &$job ) {
		while ( $room > 0 && $job['pos'] < count( $job['users'] ) ) {
			$user_id = (int) $job['users'][ $job['pos'] ];
			++$job['pos'];

			if ( ! cas_notify_enabled( $user_id, $job['flag'] ) ) {
				continue; // Opted out while waiting: not sent and not counted.
			}
			$kind = 'cas_notify_promos' === $job['flag'] ? 'optin_kind_promos' : 'optin_kind_new_course';
			$copy = cas_mail_copy();
			$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

			cas_mail_send(
				$user_id,
				$job['subject'],
				array(
					'heading'   => $job['heading'],
					'lines'     => $job['lines'],
					'cta_label' => $job['cta_label'],
					'cta_url'   => $job['cta_url'],
					'reason'    => sprintf( $copy['optin_reason'], $site, $copy[ $kind ] ),
					'settings'  => true,
				),
				'bulk ' . $job['flag'] . ' user=' . $user_id
			);
			--$room;
		}
		if ( $job['pos'] >= count( $job['users'] ) ) {
			unset( $queue[ $i ] );
		}
	}
	unset( $job );

	update_option( 'cas_mail_queue', array_values( $queue ), false );
}
add_action( 'cas_mail_bulk_tick', 'cas_mail_bulk_tick' );

add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['cas_fifteen_minutes'] = array(
			'interval' => 900,
			'display'  => 'Every 15 minutes',
		);
		return $schedules;
	}
);

add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'cas_mail_bulk_tick' ) ) {
			wp_schedule_event( time() + 300, 'cas_fifteen_minutes', 'cas_mail_bulk_tick' );
		}
	}
);
