#!/usr/bin/env python3
"""Contract for signed video addresses: an address that is already signed is re-signed (#87)."""
import re
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-video-token.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    source = SOURCE.read_text(encoding="utf-8")
    body = re.search(r"function cas_video_sign_url\( \$url \) \{.*?\n\}\n", source, re.S)
    if not body:
        raise AssertionError("cas_video_sign_url() is missing")
    code = body.group(0)

    if "strpos( $url, 'token=' )" in code:
        raise AssertionError("an address that already carries token= must not be returned unchanged")
    require(
        code,
        "$url = remove_query_arg( array( 'token', 'expires' ), $url );",
        "an existing token and expires must be stripped before signing",
    )
    if code.index("remove_query_arg") > code.index("hash( 'sha256'"):
        raise AssertionError("the old signature must be stripped before the new one is made")
    require(code, "add_query_arg(", "the fresh signature must be added back")
    require(code, "'' === $key", "with no key configured the address must be returned as it was found")
    require(
        code,
        "preg_match( '~^https://(?:iframe|video)\\.mediadelivery\\.net/",
        "only addresses on the video host may be signed",
    )

    print("video re-sign contract passed: a pre-signed address is stripped and signed again")


if __name__ == "__main__":
    main()
