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


def main() -> int:
    login = LOGIN.read_text(encoding="utf-8")
    account = ACCOUNT.read_text(encoding="utf-8")
    assert_plain_password_fallback(login)
    assert_vanilla_password_toggle(login)
    assert_login_error_contract(login, account)
    print("login form contract passed: native password fallback, vanilla toggle, styled errors")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError) as exc:
        print(f"login form contract failed: {exc}")
        raise SystemExit(1)
