<?php
/**
 * The site's own transactional emails: receipt, enrolment confirmation and refund notice.
 *
 * WHY THEME CODE. Tutor Pro's Email and Notifications addons stay off (issue #119): the Email addon
 * emails every administrator by default, adds a public `?tutor_cron=1` queue trigger and replaces
 * the password-reset mail. Everything here goes through wp_mail(), so it uses the existing SMTP
 * hook in mu-plugins/cas-audit-log.php (the hello@ mailbox, 100 sends a day).
 *
 * WHO GETS WHAT. Each mail goes to the buyer or student only, at once, and never to a user who can
 * manage options. There is no bulk, marketing or opt-in mail: the site sends transactional mail only
 * (owner decision 2026-09-30).
 *
 * WHAT IS NOT HERE. No wording that is not data from the order or the course: no refund policy, no
 * delivery times, no promises. The words are in cas_mail_copy() so the owner can approve or change
 * them in one place.
 *
 * Each mail is sent once: cas_mail_once() takes a lock in wp_options before sending.
 */

defined( 'ABSPATH' ) || exit;

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
	);
}

/* -------------------------------------------------------------------------
 * Guards and the audit log
 * ---------------------------------------------------------------------- */

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
 * Build the HTML and plain-text bodies from one description.
 *
 * $mail keys: heading (string), lines (string[] paragraphs), rows (array of [label, value]),
 * cta_label and cta_url (optional), reason (string, the footer sentence).
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
