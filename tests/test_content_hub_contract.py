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
    require(hub, "$role->add_cap( CAS_CONTENT_HUB_CAP );", "the capability must be granted to the administrator role")
    require(hub, "get_role( 'administrator' )", "the grant must target only the administrator role")
    if hub.count("add_cap(") != 1:
        raise AssertionError("the hub must grant exactly one capability to exactly one role")
    if "get_role( 'subscriber' )" in hub or "'editor'" in hub or "'author'" in hub:
        raise AssertionError("the hub must not grant anything to any other role")
    if "add_role(" in hub or "remove_cap(" in hub:
        raise AssertionError("slice 1 must not add roles or remove capabilities")
    require(
        FUNCTIONS.read_text(encoding="utf-8"),
        "require_once get_stylesheet_directory() . '/inc/cas-content-hub.php';",
        "functions.php must load the hub",
    )


if __name__ == "__main__":
    main()
    print("ok: content hub is links-only and administrator-only")
