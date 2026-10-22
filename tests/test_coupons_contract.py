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

    require(flat, "new \\Tutor\\Models\\CouponModel()", "all writes and reads must use Tutor's CouponModel")
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
        ("'coupon_saved', 'action=create code='", "$model->create_coupon( $data )"),
        ("'coupon_saved', \"coupon={$id} action=update\"", "$model->update_coupon( $id, $data )"),
        ("'coupon_deleted', \"coupon={$id}\"", "$model->delete_coupon( $id )"),
        ("'coupon_status_changed', \"coupon={$id} to={$to}\"", "$model->update_coupon( $id, array( 'coupon_status' => $to"),
    ):
        start = flat.index(event)
        end = flat.index(write, start)
        if start >= end:
            raise AssertionError(f"audit must precede write: {write}")

    require(flat, "$current && 'code' !== $current->coupon_type", "editing automatic coupons must be refused")
    require(flat, "if ( 'code' !== $coupon->coupon_type )", "row actions on automatic coupons must be refused")
    require(flat, "cas_coupons_used( $coupon->coupon_code ) > 0", "used coupons must not be deleted")
    require(flat, "if ( $current && $code !== $current->coupon_code && cas_coupons_used( $current->coupon_code ) > 0 )", "used coupon codes must not change")

    require(flat, "'/^[A-Z0-9_-]{3,40}$/'", "coupon code must be restricted to safe characters")
    require(flat, "'percentage' === $type && (float) $raw > 100", "percentage discounts cannot exceed 100")
    require(flat, "(int) $raw <= $max", "usage limits must fit Tutor's field widths")
    require(flat, "cas_coupons_limit( $in['total_usage_limit'] ?? '', 4294967295 )", "total-use cap must respect Tutor's UNSIGNED INT field")
    require(flat, "cas_coupons_limit( $in['per_user_usage_limit'] ?? '', 255 )", "per-user limit must respect Tutor's TINYINT field")
    require(flat, "'percentage' === $type && 100.0 === (float) $raw && empty( $in['accept_full_discount'] )", "100% coupons must require an extra confirmation")
    require(flat, "$parsed < $start_date", "expiry cannot be earlier than start")

    for forbidden in ("manage_options", "'administrator'", "refund", "Refund", "$wpdb->insert", "$wpdb->update", "$wpdb->delete"):
        if forbidden in code:
            raise AssertionError(f"forbidden token in coupon code: {forbidden}")

    print("coupons contract ok")


if __name__ == "__main__":
    main()
