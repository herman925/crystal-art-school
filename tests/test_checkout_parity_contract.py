#!/usr/bin/env python3
"""Deterministic static contract for the #123 checkout step fork."""

from __future__ import annotations

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "hello-elementor-child"
FORK = (THEME / "tutor" / "ecommerce" / "checkout.php").read_text(encoding="utf-8")
MODULE = (THEME / "inc" / "cas-checkout.php").read_text(encoding="utf-8")
ACCOUNT = (THEME / "inc" / "cas-account.php").read_text(encoding="utf-8")
FUNCTIONS = (THEME / "functions.php").read_text(encoding="utf-8")
CSS = (THEME / "css" / "cas-checkout.css").read_text(encoding="utf-8")
FIELDS = (THEME / "tutor" / "ecommerce" / "cas-checkout-billing-fields.php").read_text(encoding="utf-8")


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def forbid(haystack: str, needle: str, context: str) -> None:
    if needle in haystack:
        raise AssertionError(f"{context} must not contain: {needle}")


def assert_tutor_hooks_kept() -> None:
    for token in (
        'id="tutor-checkout-form"',
        'name="tutor_action" value="tutor_pay_now"',
        "tutor_nonce_field()",
        "tutor-checkout-details",
        'id="tutor-checkout-pay-now-button"',
        'name="payment_method"',
        'name="payment_type"',
        "checkout-billing-form-fields.php",
        "tutor_checkout_enable_pay_now_btn",
        "LegalConsent::render_consent_field",
    ):
        require(FORK, token, "checkout fork (Tutor contract)")


def assert_layout_matches_prototype() -> None:
    for token in (
        'class="checkout-grid"',
        'class="co-panel"',
        '<aside class="summary cas-co-summary">',
        'class="btn primary big full marine"',
        'class="btn ghost full cas-account-back-link"',
    ):
        require(FORK, token, "checkout fork (prototype layout)")


def assert_actions_outside_refreshed_element() -> None:
    """Tutor replaces the innerHTML of [tutor-checkout-details] on coupon/country change."""
    start = FORK.index("<div tutor-checkout-details>")
    end = FORK.index("</div>", FORK.index("$cas_details_html", start))
    refreshed = FORK[start:end]
    for token in ("tutor-checkout-pay-now-button", "agree_to_terms", "render_consent_field", "cas-account-back-link"):
        forbid(refreshed, token, "the AJAX-refreshed summary element")
    if FORK.index("tutor-checkout-pay-now-button") < end:
        raise AssertionError("pay button must come after the refreshed summary element")


def assert_one_consent() -> None:
    require(FORK, "cas_account_render_checkout_terms()", "checkout fork (fallback consent)")
    forbid(ACCOUNT, "add_action( 'tutor_before_checkout_order_details', 'cas_account_render_checkout_terms'", "cas-account.php (second consent hook)")
    require(ACCOUNT, "cas_account_checkout_has_native_consent()", "cas-account.php (native consent check)")


def assert_words_and_button() -> None:
    require(MODULE, "'Order Details' => 'Order summary'", "gettext map")
    require(MODULE, "'Grand Total'   => 'Total'", "gettext map")
    require(MODULE, "'tutor_checkout_pay_now_btn_text'", "button text filter")
    require(MODULE, "Place order", "button text")
    require(MODULE, "tutor_get_checkout_html", "AJAX refresh coverage")
    require(FUNCTIONS, "/inc/cas-checkout.php", "functions.php require")


def assert_css_scoped() -> None:
    require(CSS, ".tutor-checkout-page", "cas-checkout.css scope")
    require(CSS, ".cas-co-summary", "cas-checkout.css summary")


def assert_billing_trimmed() -> None:
    """State and Phone are not asked, but Tutor's customers table has no default for them."""
    require(FORK, "cas-checkout-billing-fields.php", "checkout fork (trimmed billing fields)")
    forbid(FORK, "templates/ecommerce/checkout-billing-form-fields.php", "checkout fork")
    for name in ("billing_first_name", "billing_last_name", "billing_email", "billing_country", "billing_city", "billing_zip_code", "billing_address"):
        require(FIELDS, f'name="{name}"', "trimmed billing fields (asked)")
    for name in ("billing_state", "billing_phone"):
        require(FIELDS, f'<input type="hidden" name="{name}" value="">', "trimmed billing fields (empty hidden)")
        forbid(FIELDS, f'<select name="{name}"', "trimmed billing fields (visible)")
        forbid(FIELDS, f'type="text" name="{name}"', "trimmed billing fields (visible)")


def main() -> None:
    assert_tutor_hooks_kept()
    assert_layout_matches_prototype()
    assert_actions_outside_refreshed_element()
    assert_one_consent()
    assert_words_and_button()
    assert_css_scoped()
    assert_billing_trimmed()
    print("checkout parity contract: OK")


if __name__ == "__main__":
    main()
