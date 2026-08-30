#!/usr/bin/env python3
"""Regression contracts for the remaining Courses parity gaps in #51."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "hello-elementor-child"
COURSES = THEME / "inc" / "cas-courses.php"
ARCHIVE = THEME / "archive-courses.php"
SCRIPT = THEME / "js" / "cas-courses.js"

EXPECTED_COVER_SIZES = (
    "(max-width: 480px) calc(100vw - 48px), "
    "(max-width: 880px) calc((100vw - 68px) / 2), "
    "(max-width: 1100px) calc((100vw - 348px) / 2), "
    "calc((min(100vw, 1440px) - 368px) / 3)"
)


def read(path: Path) -> str:
    if not path.is_file():
        raise AssertionError(f"missing contract file: {path.relative_to(ROOT)}")
    return path.read_text(encoding="utf-8")


def between(source: str, start: str, end: str) -> str:
    try:
        begin = source.index(start)
        finish = source.index(end, begin)
    except ValueError as exc:
        raise AssertionError(f"missing contract section: {start}") from exc
    return source[begin:finish]


def test_course_card_uses_native_responsive_images() -> None:
    source = read(COURSES)
    card = between(source, "function cas_course_card", "/* ---------------------------------------------------------------------------")
    sizes = between(source, "function cas_course_cover_sizes", "function cas_course_card")

    if EXPECTED_COVER_SIZES not in sizes:
        raise AssertionError("course cover sizes must mirror the catalogue grid breakpoints")
    for token in (
        "get_post_thumbnail_id( $course_id )",
        "wp_get_attachment_image(",
        "'sizes'    => $sizes",
        "'decoding' => 'async'",
        "'alt'      =>",
    ):
        if token not in card:
            raise AssertionError(f"course card is missing native image contract: {token}")

    if "get_the_post_thumbnail_url( $course_id, 'large' )" in card:
        raise AssertionError("course cards must not fall back to a bare thumbnail URL")


def test_sort_changes_are_announced_through_the_shared_guard() -> None:
    source = read(SCRIPT)
    sort = between(source, "var sortRail", "\n\tapply();")

    expected = {
        "'featured': 'Sorted by featured order.'",
        "'price-asc': 'Sorted by price, lowest first.'",
        "'price-desc': 'Sorted by price, highest first.'",
    }
    for token in expected:
        if token not in sort:
            raise AssertionError(f"sort announcement is missing: {token}")

    if "typeof window.CasAnnounce === 'function'" not in sort:
        raise AssertionError("sort announcements must guard the shared CasAnnounce function")
    if "window.CasAnnounce( SORT_SAID[ val ] )" not in sort:
        raise AssertionError("sort handler must announce the selected truthful string")


def test_archive_main_accepts_skip_link_focus() -> None:
    source = read(ARCHIVE)
    if '<main id="content" class="site-main cas-archive" tabindex="-1">' not in source:
        raise AssertionError("Courses archive main must be focusable by the shared skip link")


def test_intro_uses_only_the_editor_marker_for_read_more() -> None:
    source = read(COURSES)
    helper = between(source, "function cas_render_courses_intro", "function cas_render_free_trial")

    for token in (
        "explode( '<!--more-->'",
        'data-cas-courses-intro-toggle',
        'aria-expanded="false"',
        'aria-controls="cas-courses-intro-more"',
        'id="cas-courses-intro-more"',
        'hidden',
        '>Read More<',
    ):
        if token not in helper:
            raise AssertionError(f"intro fold is missing the marker-based contract: {token}")

    if "wp_trim_words" in helper or "substr(" in helper:
        raise AssertionError("intro fold must not truncate by a fixed length")
    if "cas_render_courses_intro( $intro )" not in source:
        raise AssertionError("Courses renderer must use the marker-aware intro helper")


def main() -> int:
    test_course_card_uses_native_responsive_images()
    test_sort_changes_are_announced_through_the_shared_guard()
    test_archive_main_accepts_skip_link_focus()
    test_intro_uses_only_the_editor_marker_for_read_more()
    print("Courses parity contracts passed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
