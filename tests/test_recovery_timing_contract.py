#!/usr/bin/env python3
"""Contract for #157: the reset mail is deferred until after the recovery answer, and the handler sends it, never drops it."""
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-account.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def forbid(text: str, needle: str, message: str) -> None:
    if needle in text:
        raise AssertionError(message)


def main() -> None:
    source = SOURCE.read_text(encoding="utf-8")

    require(
        source,
        "add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_defer_start', 0 );",
        "the deferral must arm at priority 0, inside the Tutor reset request, before any account lookup",
    )
    require(
        source,
        "add_filter( 'pre_wp_mail', 'cas_account_password_recovery_defer_mail', 10, 2 );",
        "wp_mail calls during the reset request must be captured by pre_wp_mail",
    )
    require(
        source,
        "add_action( 'shutdown', 'cas_account_password_recovery_send_deferred', 0 );",
        "the deferred mail must be sent at shutdown",
    )
    send = source.split("function cas_account_password_recovery_send_deferred() {", 1)[1].split("\n}\n", 1)[0]
    require(send, "litespeed_finish_request()", "send step must use LiteSpeed's finish call when it exists")
    require(send, "fastcgi_finish_request()", "send step must fall back to fastcgi_finish_request()")
    require(send, "wp_mail( $atts['to'], $atts['subject'], $atts['message']", "captured mail must be sent unchanged")
    require(send, "cas_audit_log( 'password_reset_mail_failed', 'deferred' );", "a failed deferred send must be logged without address or token")
    forbid(source, "CAS_RECOVERY_RESPONSE_FLOOR_US", "the floor constant must be removed")
    forbid(source, "usleep(", "the sleep floor must be removed")


if __name__ == "__main__":
    main()
    print("ok: reset mail is deferred to shutdown and sent unchanged")
