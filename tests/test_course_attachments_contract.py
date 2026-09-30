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
    require(
        source,
        "<?php if ( $materials ) : ?>\n\t\t\t\t\t<ul class=\"learn-list\">\n\t\t\t\t\t\t<?php foreach ( $materials as $m ) : ?>",
        "text-material list must stay empty when only attachments exist",
    )
    require(
        source,
        "\t\t\t\t\t\t<?php endforeach; ?>\n\t\t\t\t\t</ul>\n\t\t\t\t<?php endif; ?>\n\t\t\t\t<?php if ( $attachments_markup ) : ?>\n\t\t\t\t\t<div class=\"cas-course-attachments\">",
        "text-material list must close before the attachment shelf, which prints only when it has markup",
    )
    require(
        source,
        "if ( ! is_array( $attachments ) || ! $attachments ) {\n\t\treturn '';\n\t}\n\n\tob_start();\n\ttutor_load_template( 'global.attachments' );",
        "a course with no attachments must return no markup before Tutor's template (and its empty state) can run (#142)",
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
