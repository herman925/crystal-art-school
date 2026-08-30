#!/usr/bin/env python3
"""Regression contracts for the remaining homepage parity fixes in #48."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
RELEASE = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "inc" / "cas-home-releases.php"
COURSES = ROOT / "wp-content" / "themes" / "hello-elementor-child" / "inc" / "cas-home-courses.php"


def read(path: Path) -> str:
    if not path.is_file():
        raise AssertionError(f"missing contract file: {path.relative_to(ROOT)}")
    return path.read_text(encoding="utf-8")


def assert_release_slides_prioritize_only_the_visible_cover() -> None:
    release = read(RELEASE)
    courses = read(COURSES)
    match = re.search(
        r"\$cover\s*=\s*\(\s*\$cover_id\s*&&\s*wp_attachment_is_image\(\s*\$cover_id\s*\)\s*\)"
        r"\s*\?\s*cas_home_course_img\(\s*\$cover_id\s*,\s*\$sizes\s*,\s*(?P<mode>[^)]*)\)",
        release,
        re.DOTALL,
    )
    if not match:
        raise AssertionError("release cover must call cas_home_course_img with an indexed priority")

    mode = re.sub(r"\s+", " ", match.group("mode")).strip()
    if mode != "0 === $index ? 'high' : 'low'":
        raise AssertionError(
            "release cover priority must be high for index 0 and low for every later slide"
        )

    expected_signature = "function cas_home_course_img( $att_id, $sizes, $priority = 'lazy' )"
    if expected_signature not in courses:
        raise AssertionError("course image helper must expose lazy, high, and low priority modes")

    helper_start = courses.index(expected_signature)
    helper = courses[helper_start : courses.index("\n}\n", helper_start) + 3]
    for token in (
        "'low' === $priority",
        "'high' === $priority",
        'fetchpriority=\"low\"',
        'fetchpriority=\"high\"',
        'loading=\"lazy\"',
    ):
        if token not in helper:
            raise AssertionError(f"course image helper is missing the {token} output state")


def main() -> int:
    assert_release_slides_prioritize_only_the_visible_cover()
    print("home parity contracts passed: release priority")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError) as exc:
        print(f"home parity contract failed: {exc}")
        raise SystemExit(1)
