#!/usr/bin/env python3
"""Contract for W2-3: exact access screen, limited target lookup, Tutor-owned enrollment updates, audit-first and no order/payment effects."""
import re
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
ENROL = THEME / "inc/cas-enrol.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    if not ENROL.exists():
        raise AssertionError("inc/cas-enrol.php is missing")
    raw = ENROL.read_text(encoding="utf-8")
    code = re.sub(r"/\*.*?\*/", "", raw, flags=re.S)
    code = "\n".join(line for line in code.splitlines() if not line.lstrip().startswith("//"))
    flat = " ".join(code.split())

    require(flat, "CAS_ENROL_CAP = 'manage_cas_content'", "screen must use manage_cas_content")
    require(flat, "CAS_ENROL_PAGE = 'cas-enrol'", "screen slug must be exact")
    require(flat, "'cas-content-hub'", "screen must sit under the content hub")
    require(flat, "const CAS_ENROL_ACTIONS = array( 'confirm', 'apply' );", "POST actions must be exactly confirm and apply")
    require(flat, "current_user_can( CAS_ENROL_CAP )", "screen and handlers must check the limited capability")
    require(flat, "cas_enrol_check_nonce( 'cas_enrol_confirm' )", "review step must require its nonce")
    require(flat, "cas_enrol_check_nonce( 'cas_enrol_apply_' . $student_id . '_' . $course_id . '_' . $operation )", "apply nonce must bind student, course and operation")
    require(flat, "add_action( 'load-' . $hook, 'cas_enrol_on_load' );", "POST handling must occur before output")

    # Untrusted student input is resolved via prepared raw reads; no WP_User/meta hydration.
    require(flat, "SELECT ID, user_login, user_email, display_name FROM {$wpdb->users} WHERE ID = %d", "student detail reads must be parameterized and raw")
    require(flat, "SELECT ID FROM {$wpdb->users} WHERE user_email = %s LIMIT 1", "email lookup must be parameterized")
    require(flat, "SELECT ID FROM {$wpdb->users} WHERE user_login = %s LIMIT 1", "login lookup must be parameterized")
    for unsafe in ("get_user_by(", "get_userdata(", "get_user_meta(", "maybe_unserialize(", "user_can( $user"):
        if unsafe in code:
            raise AssertionError(f"target student lookup must not hydrate arbitrary usermeta: {unsafe}")
    require(flat, 'subscriber";b:1;', "only student accounts may be targeted")
    require(flat, "manage_options|edit_users", "staff capability metadata must be excluded without WP_User hydration")

    require(flat, "'post_status' => 'publish'", "only published courses may be selected")
    require(flat, "'courses' === $course->post_type", "only Tutor course posts may be targeted")
    require(flat, "cas_enrol_signature( $rows )", "confirmation must snapshot current enrollment rows")
    require(flat, "hash_equals( cas_enrol_signature( $rows ), (string) $expected_signature )", "apply must refuse changed enrollment state")

    require(flat, "return '\\\\Tutor\\\\Models\\\\EnrollmentModel';", "enrollment actions must use Tutor's EnrollmentModel")
    require(flat, "$model::get_enrolled_data( (int) $student_id, 0, '' )", "all course enrollment rows must be read before changing access")
    require(flat, "$model::do_enroll( (int) $course_id, 0, (int) $student_id, false )", "new access must use Tutor without notification hooks")
    require(flat, "$to = 'enrol' === $operation ? 'completed' : 'cancel';", "manual operation must use Tutor's exact enrolment statuses")
    require(flat, "$model::update_enrollments( 'completed', array( (int) $reuse->ID ), false )", "reused enrolments must suppress Tutor notification hooks")
    require(flat, "$model::update_enrollments( 'completed', array( $id ), false )", "new enrolments must suppress Tutor notification hooks")
    require(flat, "$model::update_enrollments( 'cancel', $ids, false )", "multiple active rows must all be cancelled on removal")
    require(flat, "'completed' === $row->post_status", "grant must verify completed enrolment rows after Tutor returns")
    require(flat, "0 === count( $active )", "removal must verify that no active/pending row remains")

    # Audit first, then Tutor writes. The audit detail is identifiers/status only.
    apply_at = flat.index("function cas_enrol_apply(")
    attempt_at = flat.index("cas_audit_log( 'manual_enrolment_attempt', $detail )", apply_at)
    audit_at = flat.index("cas_audit_log( 'manual_enrolment_changed', $detail )", apply_at)
    write_at = flat.index("$model::update_enrollments(", apply_at)
    verify_at = flat.index("$check_rows = cas_enrol_records(", apply_at)
    if not attempt_at < write_at < verify_at < audit_at:
        raise AssertionError("attempt audit must precede writes and success audit must follow access verification")
    require(flat, "true !== cas_audit_log( 'manual_enrolment_attempt', $detail )", "no access change may proceed unless the attempt audit is fully written")
    require(flat, "true !== cas_audit_log( 'manual_enrolment_changed', $detail )", "final audit failure must trigger compensation")
    require(flat, "cas_enrol_restore_changes( $changes )", "failed writes or verification must restore changed rows")
    require(flat, "wp_json_encode( cas_enrol_snapshot( $row, '' ) )", "confirmation signature must cover identity and order/product associations, not only status")
    require(flat, "p.post_author = %d AND p.post_parent = %d AND p.post_status = %s", "undo SQL must condition exact student/course/written status")
    require(flat, "COALESCE(om.meta_value, \\'\\') = %s AND COALESCE(pm.meta_value, \\'\\') = %s", "undo SQL must compare order/product associations inside the write")
    require(flat, "(om.meta_id IS NOT NULL) = %d AND (pm.meta_id IS NOT NULL) = %d", "undo must distinguish absent from empty metadata")
    require(flat, "false !== $changed && 1 === $changed && (int) $verified === $id", "undo must require one matched write plus fresh readback")
    require(flat, "if ( 'rollback' === $result ) { wp_die(", "failed compensation must not display success")
    require(flat, "student_id=%d course_id=%d action=%s enrolments=%s from=%s to=%s", "audit must contain no user name/email")

    for forbidden in ("wp_mail(", "manage_options' => true", "tutor-assignments", "tutor-enrollments", "payment_status", "tutor_orders"):
        if forbidden in code:
            raise AssertionError(f"enrollment screen must not add order/payment/Pro side effects: {forbidden}")

    print("enrol contract ok")


if __name__ == "__main__":
    main()
