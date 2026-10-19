#!/usr/bin/env python3
"""Static contract for the #151 recovery-form accessibility repair (theme side only)."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
ACCOUNT = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "inc" / "cas-account.php"


def require(haystack: str, needle: str, context: str) -> None:
    if needle not in haystack:
        raise AssertionError(f"{context} is missing: {needle}")


def function_body(source: str, name: str) -> str:
    match = re.search(r"function " + name + r"\(.*?\n\}", source, re.DOTALL)
    if not match:
        raise AssertionError(f"function {name} is missing")
    return match.group(0)


def test_labels_are_associated_with_their_fields() -> None:
    source = ACCOUNT.read_text(encoding="utf-8")
    body = function_body(source, "cas_account_recovery_accessible")
    require(body, "'//label[not(@for)]'", "label association query")
    require(body, "tutor-form-group", "label association scope")
    require(body, "setAttribute( 'for'", "label association write")


def test_error_container_is_announced_and_described() -> None:
    source = ACCOUNT.read_text(encoding="utf-8")
    body = function_body(source, "cas_account_recovery_accessible")
    require(body, "tutor-mb-8", "Tutor alert wrapper match")
    require(body, "'cas-recovery-errors'", "stable error container id")
    require(body, "aria-describedby", "field description of the error container")
    alert = function_body(source, "cas_account_recovery_alert")
    require(alert, "tabindex=\"-1\"", "focusable error summary")
    require(alert, "role=\"", "error summary live role")


def test_success_message_keeps_status_semantics() -> None:
    source = ACCOUNT.read_text(encoding="utf-8")
    require(source, "cas_account_recovery_status_active( true )", "generic success marked as status")
    require(function_body(source, "cas_account_recovery_accessible"), "'status'", "status role for success")


def test_focus_moves_only_to_error_summary() -> None:
    source = ACCOUNT.read_text(encoding="utf-8")
    after = function_body(source, "cas_account_retrieve_password_after")
    require(after, "cas_account_recovery_accessible(", "accessible pass applied to captured form")
    require(after, "error.focus()", "focus after error")
    require(after, "\"alert\" === error.getAttribute( \"role\" )", "focus gated on alert role")


def test_privacy_and_token_guards_are_unchanged() -> None:
    source = ACCOUNT.read_text(encoding="utf-8")
    for token in (
        "tutor_alert( null, 'any', true, true )",
        "If an account matches those details, a reset email will be sent.",
        "cas_account_password_recovery_request_active( false )",
        "'lostpassword_post'",
    ):
        require(source, token, "privacy and token guard")
    if "is_email" in function_body(source, "cas_account_password_recovery_input_valid"):
        raise AssertionError("recovery input helper must not require email syntax")
    if re.search(r"login_form_(rp|resetpass)", source):
        raise AssertionError("core rp and resetpass handlers must not be hooked")


def main() -> int:
    test_labels_are_associated_with_their_fields()
    test_error_container_is_announced_and_described()
    test_success_message_keeps_status_semantics()
    test_focus_moves_only_to_error_summary()
    test_privacy_and_token_guards_are_unchanged()
    print("recovery form a11y contract passed: label association, error summary, status semantics, focus after error")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError) as exc:
        print(f"recovery form a11y contract failed: {exc}")
        raise SystemExit(1)
