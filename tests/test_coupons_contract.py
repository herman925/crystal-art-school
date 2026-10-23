#!/usr/bin/env python3
"""Contract for W2-2: CouponModel CRUD, exact action list, capability/nonces, safe coupon limits, and audit-first writes."""
import re
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
COUPONS = THEME / "inc/cas-coupons.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    if not COUPONS.exists():
        raise AssertionError("inc/cas-coupons.php is missing")
    raw = COUPONS.read_text(encoding="utf-8")
    code = re.sub(r"/\*.*?\*/", "", raw, flags=re.S)
    code = "\n".join(line for line in code.splitlines() if not line.lstrip().startswith("//"))
    flat = " ".join(code.split())

    require(flat, "new \\Tutor\\Models\\CouponModel()", "coupon mutations and usage counts must use Tutor's CouponModel")
    require(flat, "cas_coupons_model()->get_table_name()", "safe raw coupon reads must use Tutor's trusted table name")
    require(flat, "SELECT * FROM {$table} WHERE id = %d", "coupon id read must be prepared")
    require(flat, "SELECT * FROM {$table} WHERE coupon_code = %s", "coupon code read must be prepared")
    require(flat, "SELECT * FROM {$table} ORDER BY id DESC LIMIT 200", "coupon list must preserve id-descending order and the 200-row bound")
    require(flat, "cas_coupons_used( $row->coupon_code )", "list usage counts must use Tutor's CouponModel")
    for unsafe in ("get_userdata(", "get_user_meta(", "maybe_unserialize("):
        if unsafe in code:
            raise AssertionError(f"coupon screens must not hydrate creator usermeta: {unsafe}")
    require(flat, "CAS_COUPONS_CAP = 'manage_cas_content'", "screen must use manage_cas_content")
    require(flat, "'cas-content-hub'", "coupon screen must be under the content hub")
    require(flat, "'cas-coupons'", "coupon slug must be cas-coupons")
    require(flat, "add_action( 'load-' . $hook, 'cas_coupons_on_load' );", "POST handler must run before screen output")
    require(flat, "current_user_can( CAS_COUPONS_CAP )", "screen and handler must check capability")
    require(flat, "const CAS_COUPONS_ACTIONS = array( 'save', 'activate', 'deactivate', 'delete' );", "POST actions must use the exact allowlist")
    require(flat, "! in_array( $action, CAS_COUPONS_ACTIONS, true )", "unknown actions must be refused")

    require(flat, "check_admin_referer( 'cas_coupons_save_' . $id )", "create/edit must check its nonce")
    require(flat, "check_admin_referer( 'cas_coupons_row_' . $action . '_' . $id )", "row actions must check their per-action nonce")

    require(flat, "true === cas_audit_log( $event, $detail )", "audit must fail closed unless the logger returns true")
    for write in ("$model->create_coupon( $data )", "$model->update_coupon( $id, $data )", "$model->delete_coupon( $id )", "$model->update_coupon( $id, array( 'coupon_status' => $to"):
        if write not in flat:
            raise AssertionError(f"expected CouponModel write is missing: {write}")
    for event, write in (
        ("'coupon_save_attempt', 'action=create code='", "$model->create_coupon( $data )"),
        ("'coupon_save_attempt', \"coupon={$id} action=update\"", "$model->update_coupon( $id, $data )"),
        ("'coupon_delete_attempt', \"coupon={$id}\"", "$model->delete_coupon( $id )"),
        ("'coupon_status_change_attempt', \"coupon={$id} to={$to}\"", "$model->update_coupon( $id, array( 'coupon_status' => $to"),
    ):
        start = flat.index(event)
        end = flat.index(write, start)
        if start >= end:
            raise AssertionError(f"audit must precede write: {write}")

    require(flat, "$stored = cas_coupons_record_by_id( $new )", "create must re-read its exact persisted row")
    require(flat, "(int) $stored->id !== (int) $new || ! cas_coupons_record_matches( $stored, $data )", "create success must match returned id and every intended field")
    require(flat, "foreach ( $intended as $key => $value )", "readback must cover all validated fields and creation metadata")
    require(flat, "property_exists( $stored, $key )", "missing persisted fields cannot verify successfully")
    require(flat, "Do not submit again. Refresh the list and tell Herman.", "uncertain create must not invite duplicate submission")
    create_at = flat.index("$stored = cas_coupons_record_by_id( $new )")
    success_at = flat.index("'Coupon created.'", create_at)
    if create_at >= success_at:
        raise AssertionError("create must be verified before success notice")

    require(flat, "$current && 'code' !== $current->coupon_type", "editing automatic coupons must be refused")
    require(flat, "if ( 'code' !== $coupon->coupon_type )", "row actions on automatic coupons must be refused")
    require(flat, "cas_coupons_used( $coupon->coupon_code ) > 0", "used coupons must not be deleted")
    require(flat, "if ( $current && $code !== $current->coupon_code && cas_coupons_used( $current->coupon_code ) > 0 )", "used coupon codes must not change")

    require(flat, "'/^[A-Z0-9_-]{3,40}$/'", "coupon code must be restricted to safe characters")
    require(flat, "'percentage' === $type && (float) $raw > 100", "percentage discounts cannot exceed 100")
    require(flat, "(int) $raw <= $max", "usage limits must fit Tutor's field widths")
    require(flat, "cas_coupons_limit( $in['total_usage_limit'] ?? '', 4294967295 )", "total-use cap must respect Tutor's UNSIGNED INT field")
    require(flat, "cas_coupons_limit( $in['per_user_usage_limit'] ?? '', 255 )", "per-user limit must respect Tutor's TINYINT field")
    require(flat, "is_string( $in['accept_full_discount'] ) && '1' === $in['accept_full_discount']", "100% coupons must require the exact confirmation tick value")
    require(flat, "$parsed < $start_date", "expiry cannot be earlier than start")

    for forbidden in ("manage_options", "'administrator'", "refund", "Refund", "$wpdb->insert", "$wpdb->update", "$wpdb->delete"):
        if forbidden in code:
            raise AssertionError(f"forbidden token in coupon code: {forbidden}")

    print("coupons contract ok")


if __name__ == "__main__":
    main()
