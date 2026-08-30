#!/usr/bin/env python3
"""Deterministic regression contract for the About certificate rows in #54."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CSS = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "css" / "industrial-3.css"


def read(path: Path) -> str:
    if not path.is_file():
        raise AssertionError(f"missing contract file: {path.relative_to(ROOT)}")
    return path.read_text(encoding="utf-8")


def certificate_section(source: str) -> str:
    start = source.index("/* ---- the certificate strip")
    end = source.index("/* ---- the certificate lightbox", start)
    return source[start:end]


def test_certificate_rows_explicitly_allow_wrapping() -> None:
    section = certificate_section(read(CSS))
    selector = re.search(
        r"(?ms)^\.cert-row,\s*\.cert-row\s+\.cert-name\s*\{(?P<body>[^{}]*)\}",
        section,
    )
    if not selector:
        raise AssertionError(
            "About certificate rows must explicitly override inherited button nowrap"
        )

    if not re.search(
        r"(?:^|;)\s*white-space\s*:\s*normal\s*(?:;|$)",
        selector.group("body"),
    ):
        raise AssertionError(
            "About certificate rows must set white-space: normal on the row and name"
        )


def main() -> int:
    test_certificate_rows_explicitly_allow_wrapping()
    print("About parity contracts passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
