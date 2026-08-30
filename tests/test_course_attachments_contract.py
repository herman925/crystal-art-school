#!/usr/bin/env python3
"""Contract for the Tutor course-attachment shelf."""
from pathlib import Path

SOURCE = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-course-single.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    source = SOURCE.read_text(encoding="utf-8")

    require(
        source,
        "function cas_render_course_attachments( $course_id )",
        "course attachment renderer is missing",
    )
    require(
        source,
        "method_exists( $utils, 'get_attachments' )",
        "renderer must guard the Tutor attachment API",
    )
    require(
        source,
        "$attachments = $utils->get_attachments( $course_id );",
        "renderer must resolve attachments for the requested course",
    )
    require(
        source,
        "tutor_load_template( 'global.attachments' )",
        "renderer must reuse Tutor's official attachment template",
    )
    require(
        source,
        "$attachments_markup = cas_render_course_attachments( $id );",
        "course renderer must pass the current course ID to the attachment shelf",
    )
    require(
        source,
        "if ( $materials || $attachments_markup ) :",
        "materials section must render for attachments even without text materials",
    )
    require(
        source,
        "echo $attachments_markup;",
        "course output must include the attachment shelf markup",
    )

    # This slice must render existing Tutor records only; it must not seed content.
    forbidden = (
        "wp_insert_post",
        "media_handle_upload",
        "update_post_meta( $course_id",
        "_cas_course_attachment",
    )
    for needle in forbidden:
        if needle in source:
            raise AssertionError(f"attachment renderer must not seed content: {needle}")

    print("course attachment contract passed")


if __name__ == "__main__":
    main()
