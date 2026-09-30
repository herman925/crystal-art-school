#!/usr/bin/env python3
"""Contract for the lesson page port (issue #122)."""
import re
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"


def read(relative: str) -> str:
    return (THEME / relative).read_text(encoding="utf-8")


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def forbid(text: str, needle: str, message: str) -> None:
    if needle in text:
        raise AssertionError(message)


def main() -> None:
    lesson = read("inc/cas-lesson.php")
    template = read("single-lesson.php")
    tour = read("tutor/shared/tour.php")
    account = read("inc/cas-account.php")
    functions = read("functions.php")

    # Routing: only an accessible lesson is claimed, everything else stays with Tutor.
    require(lesson, "is_singular( 'lesson' )", "router must only claim lesson posts")
    require(lesson, "CourseModel::has_course_content_access(", "access must be Tutor's own test")
    require(lesson, "isset( $_GET['subpage'] )", "course sub-pages must stay with Tutor")
    require(lesson, "if ( ! cas_lesson_is_ours() ) {\n\t\t\treturn $template;", "a refused visitor must fall through to Tutor")
    require(lesson, "},\n\t999\n);", "template_include must run after Tutor's own (priority 999)")
    require(functions, "/inc/cas-lesson.php", "functions.php must load the lesson module")

    # The template keeps the actions Tutor fires before it prints anything.
    require(template, "tutor/course/single/content/before/all", "template must fire Tutor's content-before action")
    require(template, "can_autocomplete_course", "template must keep Tutor's course auto-completion")

    # Completion is Tutor's own form post, with its nonce, and continues to the next lesson.
    require(lesson, "wp_nonce_field( tutor()->nonce_action, tutor()->nonce, false )", "completion form must carry Tutor's nonce")
    require(lesson, 'name="tutor_action" value="tutor_complete_lesson"', "completion must post Tutor's own action")
    require(lesson, "add_action( 'tutor_lesson_completed_after', 'cas_lesson_continue_after_complete', 99, 2 )", "continue must run after Tutor's handler")
    require(lesson, "cas_lesson_course_id( $next ) !== cas_lesson_course_id( (int) $lesson_id )", "continue must stay inside the course")

    # Video goes through the signed-address path and keeps Tutor's player for other sources.
    require(lesson, "cas_video_sign_url( $src )", "embed address must pass through cas_video_sign_url")
    require(lesson, "tutor_lesson_video( false )", "non-embed sources must keep Tutor's own player")

    # Tutor's dark learning-area shell must not paint over the page, only on this route.
    require(lesson, "foreach ( array( 'tutor-core', 'tutor-learning', 'tutor-google-fonts', 'tutor-kids' ) as $handle )", "Tutor's core and learning-area styles must be dropped on the lesson route")
    require(lesson, "cas_lesson_total_runtime( array_sum( array_column( $items, 'seconds' ) ) )", "running time must be the sum of the lesson runtimes")
    require(lesson, "<?php if ( $files ) : ?>\n\t\t\t\t<div class=\"curr-card materials-card\" id=\"materials-card\">", "the materials card must be hidden when there are no files")
    forbid(lesson, "still to be supplied", "owner-facing placeholder copy must not reach a student")

    # Nothing from the prototype's demo copy is invented.
    code = re.sub(r"/\*.*?\*/", "", lesson, flags=re.S)  # the docblocks explain what was left out
    for demo in ("Taught by", "Take it slowly", "Share your skies", "You painted the whole sky"):
        forbid(code, demo, "demo copy must not be shipped: " + demo)

    # Tour: empty override, plus the new-student flag.
    if re.search(r"\becho\b|\?>|<\w", tour.replace("<?php", "")):
        raise AssertionError("tutor/shared/tour.php must not output anything")
    require(account, "update_user_meta( $user_id, '_tutor_tour_completed', true )", "new students must be marked as having finished the tour")
    require(account, "add_action( 'tutor_new_user_registered', 'cas_account_skip_tutor_tour' )", "tour flag must be set on registration")

    print("lesson page contract passed: routed, gated by Tutor, signed video, tour off")


if __name__ == "__main__":
    main()
