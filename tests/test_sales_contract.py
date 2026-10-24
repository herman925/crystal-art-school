#!/usr/bin/env python3
"""Contract for W2-1 Sales: one admin screen under the hub; capability and nonce on every path; status changes only pending->completed and pending->cancelled; no refund, no payment write, no manage_options; audit line with ids and statuses only."""
import re
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
SALES = THEME / "inc/cas-sales.php"
FUNCTIONS = THEME / "functions.php"
WARNING = "No payment is recorded for this order. Completing it gives the student access for free."


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def code_only(raw: str) -> str:
    """The file with comments removed, so prose in docblocks cannot satisfy or trip a code check."""
    no_blocks = re.sub(r"/\*.*?\*/", "", raw, flags=re.S)
    return "\n".join(line for line in no_blocks.splitlines() if not line.strip().startswith("//"))


def main() -> None:
    if not SALES.exists():
        raise AssertionError("inc/cas-sales.php is missing")
    raw = SALES.read_text(encoding="utf-8")
    code = code_only(raw)
    flat = " ".join(code.split())

    # Hub placement and capability
    require(flat, "'cas-content-hub'", "the sales screen must sit under the content hub menu")
    require(flat, "const CAS_SALES_CAP = 'manage_cas_content';", "the sales screen must use manage_cas_content")
    require(flat, "if ( ! current_user_can( CAS_SALES_CAP ) )", "every screen must check the capability")
    require(flat, "cas_sales_guard();", "the render and export paths must call the capability guard")

    # Nonces on every state-changing or data-returning path
    require(flat, "check_admin_referer( 'cas_sales_confirm' )", "the confirm step must check its nonce")
    require(flat, "check_admin_referer( 'cas_sales_apply_' . $order_id )", "the apply step must check a per-order nonce")
    require(flat, "check_admin_referer( 'cas_sales_export' )", "the CSV export must check its nonce")

    # Action allowlist: exact names, and a rejection for anything else
    require(flat, "const CAS_SALES_ACTIONS = array( 'confirm_status', 'apply_status' );", "the POST action allowlist must be exactly confirm_status and apply_status")
    require(flat, "if ( ! in_array( $post_action, CAS_SALES_ACTIONS, true ) || ! $order_id )", "unknown actions must be rejected before any work")

    # Status map: the only changes allowed
    require(flat, "const CAS_SALES_STATUS_MAP = array( 'pending' => array( 'completed', 'cancelled' ), );", "the status map must allow only pending to completed or cancelled")
    require(flat, "function cas_sales_completed_courses( $order )", "completion must preflight the order's item set")
    require(flat, "false === cas_sales_completed_courses( $order )", "completion must refuse unsupported or inaccessible items before writing")
    require(flat, "$order_model::TYPE_SINGLE_ORDER !== $order->order_type", "only individual course orders may be completed")
    require(flat, "cas_sales_order_course_ids( $order->id )", "order items must be read explicitly before access is granted")
    require(flat, "function cas_sales_student_marker_safe( $user_id )", "before Tutor touches the student marker, read its raw safe value")
    require(flat, "SELECT user_login FROM {$wpdb->users} WHERE ID = %d", "exported student login must use a raw user-table lookup")
    require(flat, "SELECT ID FROM {$wpdb->users} WHERE ID = %d", "order student existence must be checked without WP_User hydration")
    for unsafe in ("get_userdata(", "get_user_meta("):
        if unsafe in code:
            raise AssertionError(f"Sales must not hydrate potentially suspect student usermeta: {unsafe}")

    # Hard refusals: no refund, no manage_options, no Tutor's role literal, no payment write
    for banned in ("manage_options", "'administrator'", "refund", "Refund"):
        if banned in code:
            raise AssertionError(f"forbidden token in code: {banned}")

    # wp_die's second argument is the title, so the HTTP status must be passed in the args array
    if re.search(r"wp_die\(.*\), (40[03]) \);", flat):
        raise AssertionError("wp_die passes the status as the title; use array( 'response' => N )")

    # Status change is a compare-and-set on the status read from the database
    require(flat, "'order_status' => $order->order_status", "the status update must compare against the status it read")

    # Enrolment goes through Tutor's own functions
    require(flat, r"\\Tutor\\Models\\EnrollmentModel", "enrolment must go through Tutor's EnrollmentModel")
    require(flat, "$target = 'completed' === $to ? 'completed' : 'cancel';", "Tutor enrollment status mapping must be explicit")
    require(flat, "$model::update_enrollments( $target, array( $id ), false )", "Tutor updates must suppress hooks that could send mail before success")
    require(flat, "$model::do_enroll( $course_id, 0, $user_id, false )", "new access must use Tutor without notification hooks")
    require(flat, "$model::get_enrolled_data( $user_id, 0, '' )", "all enrolment rows must be checked so a pending row cannot hide earlier purchased access")
    require(flat, "cas_sales_restore_enrolment( $previous )", "failed enrollment verification must restore only owned snapshots")
    require(flat, "cas_sales_access_verified( $order, $course_ids, $to )", "final success requires an independent access readback")
    require(flat, "p.post_author = %d AND p.post_parent = %d AND p.post_status = %s", "compensation SQL must match exact user/course/status in the write")
    require(flat, "COALESCE(om.meta_value, \\'\\') = %s AND COALESCE(pm.meta_value, \\'\\') = %s", "compensation SQL must compare order/product associations atomically")
    require(flat, "false === $changed || $changed < 1", "conditional undo must reject a write which did not match")
    require(flat, "(string) $snapshot['written_order_id']", "compensation must compare the exact order association before restoring")
    require(flat, "cas_sales_restore_order_status( $order, $order_from, $to )", "failed enrollment or audit verification must compensate the order status")
    require(flat, "if ( 'cancelled' === $to && ( ! $current || (int) $current->order_id !== (int) $order_id", "cancellation must not remove access tied to another order")
    require(flat, "if ( 'completed' === $to && 'completed' === $row->post_status ) { return array( 'changed' => false ); }", "existing access must not be modified on repeated completion")

    require(flat, "clean_post_cache( $id ); $verified = $target === get_post_status( $id );", "native direct SQL must invalidate the post cache before status verification")
    require(flat, "catch ( \\Throwable $error )", "vendor write exceptions must enter controlled failure handling")
    require(flat, "$uncertain = $before !== cas_sales_enrolment_fingerprint( $after )", "a new-enrolment exception must compare fresh persisted state before claiming no change")
    require(flat, "$order_restored && empty( $snapshot['uncertain'] )", "uncertain insertion must never be reported as verified compensation")
    require(flat, "if ( 'write' === $result ) { wp_die(", "verified write exceptions must show a clear not-saved response, not an unhandled fatal")
    require(flat, "in_array( $to, CAS_SALES_STATUS_MAP['pending'], true ) && $row->order_status === $to", "already-done applies only to permitted completed/cancelled replay targets")

    # POST validation runs before the admin page prints, so refusals keep their HTTP status
    require(flat, "add_action( 'load-' . $hook, 'cas_sales_on_load' );", "POST handling must run on the page load hook")

    require(flat, "if ( true !== cas_audit_log( 'order_status_change_attempt', $line ) ) { return 'audit'; }", "no money change may proceed unless the attempt audit fully writes")
    apply_at = flat.index("function cas_sales_apply_status(")
    preflight_at = flat.index("cas_sales_completed_courses( $order )", apply_at)
    attempt_at = flat.index("cas_audit_log( 'order_status_change_attempt', $line )", apply_at)
    write_at = flat.index("$wpdb->update( cas_sales_orders_table()", apply_at)
    sync_at = flat.index("cas_sales_sync_enrolment( (int) $order->id", apply_at)
    verified_at = flat.index("cas_audit_log( 'order_status_changed', $line )", apply_at)
    if not preflight_at < attempt_at < write_at < sync_at < verified_at:
        raise AssertionError("preflight and attempt audit must precede writes; verified-change event must follow order and access verification")
    update = flat[write_at:]
    update_data_start = update.index('array(') + len('array(')
    where_marker = update.index('), array(', update_data_start)
    update_data = update[update_data_start:where_marker]
    where_start = where_marker + len('), array(')
    where_end = update.index(') )', where_start)
    update_where = update[where_start:where_end]
    if "'payment_status'" in update_data:
        raise AssertionError("payment_status must not be written")
    require(update_where, "'payment_status' => $order->payment_status", "the order update must compare the payment status it checked")
    require(flat, "'order=%d from=%s to=%s payment=%s', $order->id, $order_from, $to, $order->payment_status", "audit lines must carry id, from, to and payment status only")
    require(flat, "if ( 'preflight' === $result ) { wp_die(", "unsupported orders must be rejected before the attempt audit or mutation")
    require(flat, "if ( 'rollback' === $result ) { wp_die(", "an unverified rollback must stop the request with a critical error")

    # Unpaid completion: warning and extra tick
    require(flat, WARNING, "the unpaid warning text must match Head's wording exactly")
    require(flat, "name=\"accept_unpaid\"", "unpaid completion must show an extra tick")
    require(flat, "name=\"confirmation_token\"", "confirmation must carry a server-authenticated payment snapshot")
    require(flat, "hash_equals( wp_hash( $order_id . '|' . $to . '|' . $expected_payment ), $confirmation_token )", "apply must authenticate the expected snapshot, not trust hidden inputs")
    require(flat, "$expected_payment !== $order->payment_status", "the write helper must reject a payment changed since confirmation")
    require(flat, "'paid' !== $row->payment_status && 'completed' === $to && ! $accepted_unpaid", "unpaid completion must be refused without the tick")
    require(flat, "'paid' !== $order->payment_status && 'completed' === $to && ! $accepted_unpaid", "the apply step must also refuse unpaid completion without the tick")
    require(flat, "if ( ! $row || ! cas_sales_transition_allowed( $row->order_status, $to ) )", "the POST handler must refuse disallowed transitions with 400 before any change")

    # CSV export reads only
    require(flat, "fputcsv( $out, array( 'order_id', 'date', 'student_login', 'course', 'order_status', 'payment_status', 'amount' ) )", "the CSV must expose the actual exported columns")
    require(flat, "function cas_sales_csv_text( $value )", "CSV text fields need spreadsheet-formula neutralization")
    require(flat, "[\\\\p{Cc}\\\\p{Cf}\\\\p{Z}]*[=+\\\\-@]", "CSV export must neutralize leading control, format, whitespace and formula characters")
    require(flat, "cas_sales_csv_text( $d['student'] )", "student login must be exported as safe text")
    require(flat, "cas_sales_csv_text( $d['course'] )", "untrusted course title must be exported as safe text")
    export_row = flat[flat.index("fputcsv( $out, array(", flat.index("function cas_sales_maybe_export")):]
    if "cas_sales_csv_text( number_format" in export_row or "cas_sales_csv_text( $row->id" in export_row:
        raise AssertionError("numeric amount and order id must remain numeric/plain")

    # Scoped order list, not Tutor's list
    require(flat, "cas_sales_orders_table()", "orders must be read through the explicit table helper")

    print("sales contract ok")


if __name__ == "__main__":
    main()
