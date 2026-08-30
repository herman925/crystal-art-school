#!/usr/bin/env python3
"""Source/render contract for the authorized source-only slice of #65."""

from __future__ import annotations

import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content" / "themes" / "hello-elementor-child"
HOME = THEME / "inc" / "cas-home.php"
COURSES = THEME / "inc" / "cas-courses.php"
ABOUT = THEME / "inc" / "cas-about.php"
SHOWCASE = THEME / "inc" / "cas-showcase.php"
FOOTER = THEME / "inc" / "cas-footer.php"
PAGES = THEME / "inc" / "cas-pages.php"
ACCOUNT = THEME / "inc" / "cas-account.php"
LOGIN = THEME / "tutor" / "login-form.php"
PAYMENT_SUCCESS = THEME / "tutor" / "ecommerce" / "order-placement-success.php"


def read(path: Path) -> str:
    if not path.is_file():
        raise AssertionError(f"missing contract file: {path.relative_to(ROOT)}")
    return path.read_text(encoding="utf-8")


def section(source: str, start: str, end: str) -> str:
    try:
        begin = source.index(start)
        finish = source.index(end, begin)
    except ValueError as exc:
        raise AssertionError(f"missing source section: {start}") from exc
    return source[begin:finish]


def require(source: str, token: str, context: str) -> None:
    if token not in source:
        raise AssertionError(f"{context} is missing: {token}")


def forbid(source: str, token: str, context: str) -> None:
    if token in source:
        raise AssertionError(f"{context} still contains: {token}")


def test_home_community_shortcode_is_absent() -> None:
    source = read(HOME)
    for token in (
        "cas_render_community_band",
        "cas_community_band",
        "Fellow tier",
        "private Discord",
        "See the tiers",
    ):
        forbid(source, token, "homepage source")

    for token in (
        "function cas_render_testimonials",
        "add_shortcode( 'cas_testimonials', 'cas_render_testimonials' );",
        "function cas_render_social_strip",
        "add_shortcode( 'cas_social_strip', 'cas_render_social_strip' );",
    ):
        require(source, token, "unrelated homepage surface")


def test_courses_membership_block_and_customizer_are_absent() -> None:
    source = read(COURSES)
    for token in (
        "cas_tiers",
        "cas_tier_default",
        "cas_tier_value",
        "cas_render_membership_tiers",
        "cas_page_courses_tiers",
        "cas_tier_",
        "Three Ways to Study",
        "#membership",
        "Per-course + Discord",
    ):
        forbid(source, token, "Courses source")

    for token in (
        "function cas_render_courses( $atts )",
        "function cas_render_courses_intro( $intro )",
        "cas_render_courses_intro( $intro )",
        "cas_course_card( get_the_ID(), $i )",
        "function cas_first_preview_lesson_url()",
        "cas_render_free_trial()",
        "add_shortcode( 'cas_courses', 'cas_render_courses' );",
        "'tutor_add_course_to_cart'",
        "'tutor_guest_add_course_to_cart'",
    ):
        require(source, token, "unrelated course path")


def test_about_discord_channel_is_absent_without_removing_other_channels() -> None:
    source = read(ABOUT)
    channels = section(source, '<ul class="contact-channels">', "</ul>")

    for token in ("Discord", "Fellows channel", "<strong>Discord</strong>"):
        forbid(source, token, "About source")
    for token in (
        "<strong>Email</strong>",
        "<strong>Instagram</strong>",
        "<strong>Facebook</strong>",
        "<strong>Originals</strong>",
        "cas_social_icon( 'email' )",
        "cas_social_icon( 'instagram' )",
        "cas_social_icon( 'facebook' )",
    ):
        require(channels, token, "preserved About channel")


def test_showcase_membership_cta_and_destination_helper_are_absent() -> None:
    source = read(SHOWCASE)
    for token in (
        "Fellow",
        "Discord",
        "cas_showcase_membership_url",
        "#membership",
        "Join as a Fellow",
        "Fellows share",
    ):
        forbid(source, token, "showcase source")

    for token in (
        "function cas_render_showcase()",
        "cas_showcase_pieces()",
        'id="sw-grid"',
        "cas_showcase_lightbox();",
        "add_shortcode( 'cas_showcase', 'cas_render_showcase' );",
    ):
        require(source, token, "preserved showcase path")


def test_footer_membership_link_is_absent_without_touching_account_variant() -> None:
    source = read(FOOTER)
    for token in ('>Membership</a>', "#membership"):
        forbid(source, token, "footer source")
    for token in (
        ">All courses</a>",
        ">Free videos</a>",
        "function cas_render_footer_account_column",
        "'checkout' === $variant",
        "cas_account_is_checkout_surface()",
        "Terms &amp; privacy",
    ):
        require(source, token, "preserved footer path")


def test_faq_intro_default_is_empty_but_stored_value_and_answers_remain_live() -> None:
    source = read(PAGES)
    default = re.search(
        r"function cas_faq_intro_default\(\)\s*\{(?P<body>.*?)\n\}",
        source,
        re.DOTALL,
    )
    if not default:
        raise AssertionError("FAQ intro default function is missing")
    if not re.search(r"return\s+'';", default.group("body")):
        raise AssertionError("FAQ intro developer default must be empty, not replacement copy")
    forbid(default.group("body"), "Discord", "FAQ intro developer default")

    intro = section(source, "function cas_faq_intro()", "/*\n * cas-customizer-map.php")
    require(intro, "get_theme_mod( 'cas_faq_intro', cas_faq_intro_default() )", "stored FAQ intro")

    renderer = section(source, "function cas_render_faq()", "add_shortcode( 'cas_faq'")
    for token in (
        "cas_faq_items()",
        "wp_kses_post( $item['q'] )",
        "wp_kses_post( $item['a'] )",
        "wp_kses_post( $intro )",
    ):
        require(renderer, token, "preserved FAQ path")


def test_authentication_and_native_payment_seams_remain_intact() -> None:
    account = read(ACCOUNT)
    login = read(LOGIN)
    payment_success = read(PAYMENT_SUCCESS)

    for token in (
        "function cas_account_login_url",
        "function cas_account_signup_url",
        "function cas_account_cart_url",
        "function cas_account_render_checkout_success",
        "tutor_can_purchase_course",
        "add_shortcode( 'cas_checkout', 'cas_render_checkout' );",
    ):
        require(account, token, "preserved account/payment path")

    for token in (
        "tutor_nonce_field();",
        'name="tutor_action" value="tutor_user_login"',
        'name="redirect_to"',
        "do_action( 'tutor_after_login_form' );",
    ):
        require(login, token, "preserved authentication path")

    for token in (
        "cas_account_render_checkout_success( $order_id, $order_status )",
        "$order_id",
        "$order_status",
    ):
        require(payment_success, token, "preserved native payment success path")


def main() -> int:
    test_home_community_shortcode_is_absent()
    test_courses_membership_block_and_customizer_are_absent()
    test_about_discord_channel_is_absent_without_removing_other_channels()
    test_showcase_membership_cta_and_destination_helper_are_absent()
    test_footer_membership_link_is_absent_without_touching_account_variant()
    test_faq_intro_default_is_empty_but_stored_value_and_answers_remain_live()
    test_authentication_and_native_payment_seams_remain_intact()
    print("membership/Discord removal contract passed")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (AssertionError, OSError) as exc:
        print(f"membership/Discord removal contract failed: {exc}")
        raise SystemExit(1)
