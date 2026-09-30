#!/usr/bin/env python3
"""Deterministic static contract for the #125 student dashboard subpages."""

from __future__ import annotations

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "hello-elementor-child"
PAGES = (THEME / "inc" / "cas-dashboard-pages.php").read_text(encoding="utf-8")
ACCOUNT = (THEME / "inc" / "cas-account.php").read_text(encoding="utf-8")
FUNCTIONS = (THEME / "functions.php").read_text(encoding="utf-8")
SCRIPT = (THEME / "js" / "cas-dashboard.js").read_text(encoding="utf-8")


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def forbid(haystack: str, needle: str, context: str) -> None:
    if needle in haystack:
        raise AssertionError(f"{context} must not contain: {needle}")


def assert_routes() -> None:
    require(FUNCTIONS, "/inc/cas-dashboard-pages.php", "functions.php require")
    # Every Tutor subpage the studio replaces, and the two that are not launched.
    for token in (
        "'billing'  => 'panel-purchases'",
        "'settings' => 'panel-settings'",
        "'profile'  => 'panel-account'",
        "'reviews'  => 'blocked'",
        "'quiz-attempts' === $page",
        "'my-quiz-attempts' === $sub",
        "return 'panel-wishlist'",
        "return 'panel-courses'",
        "is_student_view()",
        "'template_redirect'",
        "tutor_enrolled_courses_page_tabs",
        "courses/my-quiz-attempts",
    ):
        require(PAGES, token, "route map")


def assert_panels() -> None:
    for token in (
        "cas_dashboard_purchases_html( $user_id )",
        "cas_dashboard_wishlist_html( $user_id )",
        "cas_dashboard_settings_html( $user_id )",
        'id="panel-wishlist"',
        "tutor_dashboard_invoice_button",
        "OrderModel::render_pay_button",
    ):
        require(ACCOUNT + PAGES, token, "panels")
    forbid(ACCOUNT, "dashboard.account.billing", "studio (Tutor billing card replaced by the table)")
    require(PAGES, '<table class="dash-table">', "purchases table")


def assert_tutor_components_started() -> None:
    """The profile and password forms need Tutor's settings component on the studio page."""
    require(ACCOUNT, 'x-data="tutorSettings()"', "profile and security hosts")
    require(ACCOUNT, 'form="cas-dashboard-profile-form"', "profile save button")
    require(PAGES, "dashboard-page", "Tutor component registration")
    require(PAGES, "window.addEventListener('load',restore)", "address restored after load")


def assert_settings_storage() -> None:
    for token in (
        "'cas_notify_new_course'",
        "'cas_notify_reminders'",
        "'cas_notify_promos'",
        "cas_notify_updated_at",
        "cas_main_focus",
        "wp_verify_nonce",
        "get_current_user_id()",
        "BillingModel",
        "billing_country",
    ):
        require(PAGES, token, "settings storage")
    forbid(PAGES, "$_POST['user_id']", "settings storage (never a user id from the request)")
    require(SCRIPT, "data-cas-switch", "switch script")


def main() -> None:
    assert_routes()
    assert_panels()
    assert_tutor_components_started()
    assert_settings_storage()
    print("dashboard pages contract: OK")


if __name__ == "__main__":
    main()
