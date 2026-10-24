#!/usr/bin/env python3
"""Contract for #148: enrolled/admin files, plus narrowly verified author references (2026-10-23)."""
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-lesson.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    source = SOURCE.read_text(encoding="utf-8")

    require(
        source,
        "$may_files = $enrolled || current_user_can( 'manage_options' ) || $author;",
        "materials need enrolment/admin or the explicit current-course-author decision",
    )
    require(
        source,
        "function_exists( 'cas_private_author_can_download' ) && cas_private_author_can_download( (int) $file->id )",
        "author stage access must not replace the separate per-file provenance/download decision",
    )
    require(
        source,
        "$files   = $may_files ? array_merge( $course_files, $attach['files'] ) : array();",
        "the materials block must be built only when $may_files is true",
    )
    require(
        source,
        "$film    = cas_lesson_film( $lesson_id );",
        "the preview lesson's own video must stay outside the materials gate",
    )
    require(
        source,
        "<?php if ( $files ) : ?>",
        "the materials block must still render only when $files is non-empty",
    )
    require(
        source,
        "<?php if ( $may_files && $attach['photos'] ) : ?>",
        "the reference photos block must be gated by $may_files (CEO decision 2026-10-21)",
    )


if __name__ == "__main__":
    main()
    print("ok: lesson materials retain student/admin gates and require separate author file authority")
