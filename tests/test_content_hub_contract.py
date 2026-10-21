#!/usr/bin/env python3
"""Contract for #150 slice 1: one links-only content hub, granted to Administrator only, with no new editor or role."""
from pathlib import Path

THEME = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child"
HUB = THEME / "inc/cas-content-hub.php"
FUNCTIONS = THEME / "functions.php"


def require(text: str, needle: str, message: str) -> None:
    if needle not in text:
        raise AssertionError(message)


def main() -> None:
    if not HUB.exists():
        raise AssertionError("the content hub file is missing")
    hub = " ".join(HUB.read_text(encoding="utf-8").split())  # whitespace-insensitive: the file aligns its constants
    require(hub, "const CAS_CONTENT_HUB_CAP = 'manage_cas_content';", "the hub must use the manage_cas_content capability")
    require(hub, "'cas-content-hub'", "the hub must have its own admin page slug")
    require(hub, "add_menu_page(", "the hub must be an admin menu page")
    for title in ("Website pages", "Courses & lessons", "Artwork & Showcase", "Posts & resources", "Site details"):
        require(hub, title, f"the hub is missing the task group: {title}")
    require(hub, "add_filter( 'user_has_cap', 'cas_content_hub_user_has_cap' );", "the capability must come from a user_has_cap filter")
    require(hub, "! empty( $allcaps['manage_options'] )", "the filter must grant the capability only to users with manage_options")
    require(hub, "$allcaps[ CAS_CONTENT_HUB_CAP ] = true;", "the filter must set the hub capability at runtime")
    for forbidden, why in (
        ("add_cap(", "the capability must not be written into any role"),
        ("remove_cap(", "slice 1 must not remove capabilities"),
        ("add_role(", "slice 1 must not add roles"),
        ("update_option(", "the hub must not write options (no DB change, no flag)"),
        ("get_role(", "the hub must not touch role records"),
        ("'subscriber'", "the hub must not grant anything to subscribers"),
    ):
        if forbidden in hub:
            raise AssertionError(why)
    require(
        FUNCTIONS.read_text(encoding="utf-8"),
        "require_once get_stylesheet_directory() . '/inc/cas-content-hub.php';",
        "functions.php must load the hub",
    )


if __name__ == "__main__":
    main()
    print("ok: content hub is links-only and administrator-only")
