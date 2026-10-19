#!/usr/bin/env python3
"""Deterministic static contract for the #53 Tutor login override."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
LOGIN = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "tutor" / "login-form.php"
ACCOUNT = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "inc" / "cas-account.php"


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def assert_plain_password_fallback(source: str) -> None:
    match = re.search(
        r'<input\s+id="cas-login-password".*?/>',
        source,
        re.DOTALL,
    )
    if not match:
        raise AssertionError("login password input is missing")

    input_markup = match.group(0)
    require(input_markup, 'type="password"', "native password input")
    require(input_markup, 'name="pwd"', "Tutor password field name")
    require(input_markup, 'autocomplete="current-password"', "password autocomplete")

    for directive in ("x-data", "x-show", "x-if", "x-model", ":type", "@click"):
        if directive in source:
            raise AssertionError(f"login override must not depend on Alpine directive: {directive}")


def assert_vanilla_password_toggle(source: str) -> None:
    for token in (
        'data-cas-password-toggle',
        'aria-controls="cas-login-password"',
        'type="button"',
        "addEventListener( 'input'",
        "addEventListener( 'click'",
        "field.type =",
        "aria-pressed",
    ):
        require(source, token, "vanilla password toggle")


def assert_login_error_contract(login: str, account: str) -> None:
    require(login, 'class="cas-login-errors"', "login error wrapper")
    require(login, "'class', 'cas-login-error'", "login error item")
    require(login, 'role="alert"', "login error announcement")
    require(account, ".cas-login-errors", "login error layout styles")
    require(account, ".cas-login-error", "login error item styles")
    require(account, "width: 100%", "login error width")
    require(account, "box-sizing: border-box", "login error box sizing")
    require(account, "overflow-wrap: anywhere", "login error wrapping")


def assert_public_recovery_route(account: str) -> None:
    helper = re.search(
        r"function cas_account_is_retrieve_password\(\)\s*\{(.*?)\n\}",
        account,
        re.DOTALL,
    )
    if not helper:
        raise AssertionError("public recovery route helper is missing")
    require(helper.group(1), "'dashboard' === cas_account_current_key()", "recovery route scope")
    require(
        helper.group(1),
        "'retrieve-password' === (string) get_query_var( 'tutor_dashboard_page' )",
        "recovery route scope",
    )

    start = account.find("add_action(\n\t'template_redirect',")
    end = account.find("\n);", start)
    if start < 0 or end < 0:
        raise AssertionError("guest dashboard redirect is missing")

    body = account[start:end]
    guard = re.search(r"if\s*\((.*?)\)\s*\{\s*return;\s*\}", body, re.DOTALL)
    if not guard:
        raise AssertionError("guest dashboard early-return guard is missing")
    require(guard.group(1), "cas_account_is_retrieve_password()", "guest dashboard guard")
    if body.index("cas_account_is_retrieve_password()") > body.index("$dashboard ="):
        raise AssertionError("recovery route exemption must run before the dashboard redirect")
    require(body, "is_page( $dashboard )", "ordinary guest dashboard protection")
    require(body, "wp_safe_redirect( $login )", "ordinary guest dashboard protection")
    require(account, "tutor_before_retrieve_password_form", "Tutor recovery form hooks")
    require(account, "tutor_before_reset_password_form", "Tutor reset form hooks")


def test_public_recovery_route_exempts_only_tutor_retrieve_password_endpoint() -> None:
    assert_public_recovery_route(ACCOUNT.read_text(encoding="utf-8"))


def assert_password_recovery_outcome_is_generic(account: str) -> None:
    for token in (
        "function cas_account_password_recovery_input_valid()",
        "! is_scalar( $_POST['user_login'] )",
        "cas_account_password_recovery_request_active( false )",
        "add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_preflight', PHP_INT_MAX )",
        "add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_start', 1 )",
        "add_action( 'tutor_action_tutor_retrieve_password', 'cas_account_password_recovery_finish', 20 )",
        "add_action( 'wp_mail_failed', 'cas_account_password_recovery_log_mail_failure', 10, 1 )",
        "If an account matches those details, a reset email will be sent.",
        "tutor_flash_get( 'danger' )",
        "tutor_flash_get( 'success' )",
        "function cas_account_reset_password_feedback()",
        "tutor_alert( null, 'any', true, true )",
        "add_action( 'tutor_before_reset_password_form', 'cas_account_reset_password_feedback', 1 )",
    ):
        require(account, token, "password recovery privacy contract")

    logger = re.search(
        r"function cas_account_password_recovery_log_mail_failure\(.*?\n\}",
        account,
        re.DOTALL,
    )
    if not logger:
        raise AssertionError("password recovery mail-failure logger is missing")
    require(logger.group(0), "cas_audit_log", "private mail failure log")
    require(logger.group(0), "error_log", "server fallback mail failure log")
    for token in ("get_error_message()", "user_login", "user_email"):
        if token in logger.group(0):
            raise AssertionError(f"private mail failure logger must not expose {token}")


def test_recovery_request_hides_account_existence_and_preserves_validation() -> None:
    assert_password_recovery_outcome_is_generic(ACCOUNT.read_text(encoding="utf-8"))


def main() -> int:
    login = LOGIN.read_text(encoding="utf-8")
    account = ACCOUNT.read_text(encoding="utf-8")
    assert_plain_password_fallback(login)
    assert_vanilla_password_toggle(login)
    assert_login_error_contract(login, account)
    assert_public_recovery_route(account)
    assert_password_recovery_outcome_is_generic(account)
    print("login form contract passed: native password fallback, vanilla toggle, styled errors, public recovery and uniform reset response")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError) as exc:
        print(f"login form contract failed: {exc}")
        raise SystemExit(1)
