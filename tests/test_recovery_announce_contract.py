#!/usr/bin/env python3
"""Contract for #151: Tutor's recovery alerts are announced and focused after submit (child theme, no Tutor file edits)."""
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
SCRIPT = THEME / "js/cas-recovery-announce.js"
ACCOUNT = THEME / "inc/cas-account.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    if not SCRIPT.exists():
        raise AssertionError("the recovery announce script is missing")
    script = SCRIPT.read_text(encoding="utf-8")
    require(script, "setAttribute( 'role', 'alert' )", "alerts must get role=alert")
    require(script, "setAttribute( 'tabindex', '-1' )", "alerts must be focusable")
    require(script, ".focus()", "focus must move to the alert after submit")
    require(script, "MutationObserver", "alerts added after load must also be handled")
    require(script, ".tutor-alert", "the script must target Tutor's alert element")

    account = ACCOUNT.read_text(encoding="utf-8")
    require(
        account,
        "add_action( 'wp_enqueue_scripts', 'cas_account_recovery_announce_script' );",
        "the script must be enqueued from the account page code",
    )
    require(
        account,
        "cas_account_is_retrieve_password()",
        "the script must load only on the retrieve and reset page",
    )


if __name__ == "__main__":
    main()
    print("ok: recovery alerts are announced and focused")
