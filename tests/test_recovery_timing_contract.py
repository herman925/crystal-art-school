#!/usr/bin/env python3
"""Contract for #157: a recovery request is answered no earlier than a fixed floor, for known and unknown accounts alike."""
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-account.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    source = SOURCE.read_text(encoding="utf-8")

    require(
        source,
        "const CAS_RECOVERY_RESPONSE_FLOOR_US = 1500000;",
        "the response floor must be a named constant (1.5 s, microseconds)",
    )
    require(
        source,
        "add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_clock_start', 0 );",
        "the request clock must start before any account lookup (priority 0)",
    )
    require(
        source,
        "function cas_account_password_recovery_pad() {",
        "the padding function is missing",
    )
    require(
        source,
        "usleep( CAS_RECOVERY_RESPONSE_FLOOR_US - $elapsed_us );",
        "padding must sleep only the remaining time to the floor",
    )
    finish = source.split("function cas_account_password_recovery_finish() {", 1)[1].split("\n}\n", 1)[0]
    require(
        finish,
        "cas_account_password_recovery_pad();",
        "the finish handler must pad before the response is sent",
    )


if __name__ == "__main__":
    main()
    print("ok: recovery response is held to the timing floor")
