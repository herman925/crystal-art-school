#!/usr/bin/env python3
"""Deterministic static contract for #140 (admin toolbar) and #141 (checkout footer account column)."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "hello-elementor-child"
ACCOUNT = (THEME / "inc" / "cas-account.php").read_text(encoding="utf-8")
FOOTER = (THEME / "inc" / "cas-footer.php").read_text(encoding="utf-8")


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def assert_admin_bar_for_managers_only() -> None:
    match = re.search(
        r"add_filter\(\s*'show_admin_bar',\s*function \( \$show \) \{(.*?)\},\s*(\d+)\s*\);",
        ACCOUNT,
        re.DOTALL,
    )
    if not match:
        raise AssertionError("show_admin_bar filter is missing from cas-account.php")
    body = match.group(1)
    require(body, "current_user_can( 'manage_options' )", "show_admin_bar filter")
    require(body, "? $show : false", "show_admin_bar filter (administrators keep their own setting)")
    if int(match.group(2)) < 11:
        raise AssertionError("show_admin_bar filter must run after the default priority")


def assert_footer_matches_login_state() -> None:
    require(FOOTER, "if ( $is_checkout && ! is_user_logged_in() )", "checkout footer account column")
    require(FOOTER, "get_option( 'users_can_register' ) ? cas_account_signup_url() : ''", "sign up link (closed registration)")
    # The account column function must not print the signed-out links unconditionally anywhere else.
    if FOOTER.count("cas_render_footer_account_column()") != 2:  # definition + one call
        raise AssertionError("cas_render_footer_account_column must have exactly one caller")


def main() -> None:
    assert_admin_bar_for_managers_only()
    assert_footer_matches_login_state()
    print("admin bar and footer contract: OK")


if __name__ == "__main__":
    main()
