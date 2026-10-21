#!/usr/bin/env python3
"""Contract for #150 option 1: one hub card per public page; links only; runtime capability; every Customizer link is a real section."""
import re
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
HUB = THEME / "inc/cas-content-hub.php"
FUNCTIONS = THEME / "functions.php"

CARDS = ("Home", "Courses", "Gallery", "Showcase", "Blog", "About", "Free videos", "FAQ", "Every page")
# Customizer section ids the hub deep-links to. Each must be registered by the theme (checked below).
SECTIONS = (
    "cas_hero", "cas_page_home_free_videos", "cas_page_home_about", "cas_page_home_courses",
    "cas_page_home_course_overview", "cas_page_home_gallery", "cas_page_home_blog", "cas_page_courses",
    "cas_page_blog", "cas_page_about", "cas_page_free_videos", "cas_brand", "cas_contact",
)


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    if not HUB.exists():
        raise AssertionError("the content hub file is missing")
    raw = HUB.read_text(encoding="utf-8")
    hub = " ".join(raw.split())  # whitespace-insensitive: the file aligns its constants

    require(hub, "const CAS_CONTENT_HUB_CAP = 'manage_cas_content';", "the hub must use the manage_cas_content capability")
    require(hub, "add_filter( 'user_has_cap', 'cas_content_hub_user_has_cap' );", "the capability must come from a user_has_cap filter")
    require(hub, "! empty( $allcaps['manage_options'] )", "the filter must grant the capability only to users with manage_options")
    require(hub, "$allcaps[ CAS_CONTENT_HUB_CAP ] = true;", "the filter must set the hub capability at runtime")
    require(hub, "add_menu_page(", "the hub must be an admin menu page")
    require(hub, "'cas-content-hub'", "the hub must have its own admin page slug")
    for name in CARDS:
        require(hub, f"'name' => '{name}'", f"the hub is missing the page card: {name}")
    require(hub, "'customize.php?autofocus[section]='", "Customizer links must deep-link with autofocus[section]")
    require(hub, "'&url='", "Customizer links must pass the page URL so the preview opens on that page")
    require(hub, "currency, checkout and payments: ask Herman.", "the Courses card must route currency and payments to Herman")
    if "tutor_settings" in raw:
        raise AssertionError("the hub must not link into Tutor settings (currency and checkout are payment-critical)")
    require(hub, "'Posts: title, text, pictures, topic and date'", "the posts line must list only what she edits")
    if "developer" in hub.lower():
        raise AssertionError("the developer note is internal and must not appear on the hub")
    require(hub, "cas_content_hub_list_item(", "list screens must be linked through the capability-checked list helper")
    require(hub, "current_user_can( $line['cap'] )", "each line must be capability-checked before it is shown")

    for forbidden, why in (
        ("add_cap(", "the capability must not be written into any role"),
        ("remove_cap(", "the hub must not remove capabilities"),
        ("add_role(", "the hub must not add roles"),
        ("update_option(", "the hub must not write options"),
        ("get_role(", "the hub must not touch role records"),
        ("'subscriber'", "the hub must not grant anything to subscribers"),
        ("wp_insert_post", "the hub must not save content"),
        ("<form", "the hub must not contain forms"),
    ):
        if forbidden in raw:
            raise AssertionError(why)

    # Every Customizer section the hub links to must be registered by the theme: either passed to add_section()
    # as a literal, or listed as a pointer section in the Customizer map (its id is the key of the pointer array).
    registered = " ".join(" ".join(p.read_text(encoding="utf-8").split()) for p in (THEME / "inc").glob("*.php"))
    for section in SECTIONS:
        literal = re.escape(section)
        if not re.search(r"add_section\(\s*'" + literal + r"'", registered) and not re.search(r"'" + literal + r"'\s*=>\s*\[", registered):
            raise AssertionError(f"the hub links to a Customizer section the theme never registers: {section}")

    require(
        FUNCTIONS.read_text(encoding="utf-8"),
        "require_once get_stylesheet_directory() . '/inc/cas-content-hub.php';",
        "functions.php must load the hub",
    )


if __name__ == "__main__":
    main()
    print("ok: content hub is one card per public page, links only, runtime capability")
