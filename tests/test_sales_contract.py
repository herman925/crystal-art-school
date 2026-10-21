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

    # Hard refusals: no refund, no manage_options, no Tutor's role literal, no payment writes
    for banned in ("manage_options", "'administrator'", "refund", "Refund", "payment_status' =>", "'completed' => array"):
        if banned in code:
            raise AssertionError(f"forbidden token in code: {banned}")

    # wp_die's second argument is the title, so the HTTP status must be passed in the args array
    if re.search(r"wp_die\(.*\), (40[03]) \);", flat):
        raise AssertionError("wp_die passes the status as the title; use array( 'response' => N )")

    # Status change is a compare-and-set on the status read from the database
    require(flat, "'order_status' => $order->order_status", "the status update must compare against the status it read")

    # Enrolment goes through Tutor's own functions
    require(flat, "\\Tutor\\Models\\EnrollmentModel", "enrolment must go through Tutor's EnrollmentModel")
    require(flat, "$model::update_enrollments( 'completed'", "completing must update enrolment through Tutor")
    require(flat, "$model::update_enrollments( 'cancel'", "cancelling must cancel enrolment through Tutor")
    require(flat, "if ( $model::is_enrolled( $course_id, $user_id, true ) ) { return;", "completing must not add a row when access already exists")
    require(flat, "$enrolment_id = (int) $row->ID;", "completing must reuse an existing enrolment row, not call do_enroll over it")

    # POST validation runs before the admin page prints, so refusals keep their HTTP status
    require(flat, "add_action( 'load-' . $hook, 'cas_sales_on_load' );", "POST handling must run on the page load hook")

    # Fail closed on the audit line
    require(flat, "if ( ! function_exists( 'cas_audit_log' ) ) { return false; }", "no change may be made without the audit logger")
    require(flat, "'order=%d from=%s to=%s payment=%s', $order->id, $order_from, $to, $order->payment_status", "the audit line must carry id, from, to and payment status only")

    # Unpaid completion: warning and extra tick
    require(flat, WARNING, "the unpaid warning text must match Head's wording exactly")
    require(flat, "name=\"accept_unpaid\"", "unpaid completion must show an extra tick")
    require(flat, "'paid' !== $row->payment_status && 'completed' === $to && ! $accepted_unpaid", "unpaid completion must be refused without the tick")
    require(flat, "'paid' !== $order->payment_status && 'completed' === $to && ! $accepted_unpaid", "the apply step must also refuse unpaid completion without the tick")
    require(flat, "if ( ! $row || ! cas_sales_transition_allowed( $row->order_status, $to ) )", "the POST handler must refuse disallowed transitions with 400 before any change")

    # CSV export reads only
    require(flat, "fputcsv( $out, array( 'order_id', 'date', 'student', 'course', 'order_status', 'payment_status', 'amount' ) )", "the CSV must have the agreed columns")

    # Scoped order list, not Tutor's list
    require(flat, "cas_sales_orders_table()", "orders must be read through the explicit table helper")

    print("sales contract ok")


if __name__ == "__main__":
    main()
