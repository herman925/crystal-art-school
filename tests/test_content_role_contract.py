#!/usr/bin/env python3
"""Static contract for the cas_content role (issue #150, Route A, W1-1). No WordPress needed.

Checks that the role never gets an administrator power, that the allowlist excludes the Elementor
library and pages, that the loader and its single functions.php line are in place, and that the
closed screens are listed. Run: python3 tests/test_content_role_contract.py
"""
import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / "wp-content/themes/hello-elementor-child"
ROLE = (THEME / "inc/cas-content-role.php").read_text(encoding="utf-8")
LOADER = (THEME / "inc/cas-content-editor.php").read_text(encoding="utf-8")
FUNCTIONS = (THEME / "functions.php").read_text(encoding="utf-8")

ADMIN_POWERS = ["manage_options", "edit_theme_options", "activate_plugins", "install_plugins", "edit_plugins",
                "edit_themes", "list_users", "create_users", "edit_users", "promote_users", "delete_users",
                "import", "export", "unfiltered_html", "manage_tutor", "edit_others_posts",
                "edit_others_tutor_courses", "edit_others_tutor_lessons"]


def block(name: str) -> str:
    """The text of a PHP array constant or function, from its name to its closing line."""
    start = ROLE.index(name)
    end = ROLE.find("\n);", start)
    if end < 0:
        end = ROLE.find("\n}", start)
    return ROLE[start:end]


class ContentRoleContract(unittest.TestCase):
    def test_role_grants_no_administrator_power(self):
        caps = block("function cas_content_role_caps")
        for power in ADMIN_POWERS:
            self.assertNotIn(f"'{power}'", caps, f"the role must not be granted {power}")

    def test_manage_options_is_never_added_anywhere_in_the_module(self):
        self.assertIsNone(re.search(r"add_cap\(\s*'manage_options'|'manage_options'\s*=>\s*true", ROLE))

    def test_allowlist_excludes_elementor_library_and_pages(self):
        allow = block("const CAS_CONTENT_POST_TYPES")
        self.assertNotIn("elementor_library", allow)
        self.assertNotIn("'page'", allow)
        self.assertNotIn("'courses'", allow, "Tutor courses join the allowlist in W1-3, behind the price gate")

    def test_allowlist_names_the_approved_content_types(self):
        allow = block("const CAS_CONTENT_POST_TYPES")
        for slug in ["post", "attachment", "cas_artwork", "cas_showcase", "cas_free_video", "cas_about_photo",
                     "cas_faq", "cas_testimonial", "cas_overview_qa"]:
            self.assertIn(f"'{slug}'", allow)

    def test_closed_screens_are_not_in_the_screen_list(self):
        screens = block("const CAS_CONTENT_SCREENS")
        for closed in ["plugins.php", "users.php", "options.php", "options-general.php", "themes.php",
                       "tools.php", "customize.php", "nav-menus.php", "export.php", "import.php"]:
            self.assertNotIn(f"'{closed}'", screens)

    def test_loader_is_wired_by_exactly_one_functions_php_line(self):
        self.assertEqual(FUNCTIONS.count("inc/cas-content-editor.php"), 1)

    def test_loader_skips_missing_modules_and_lists_the_later_pieces(self):
        for module in ["cas-content-role", "cas-customizer-allowlist", "cas-price-gate", "cas-sales",
                       "cas-coupons", "cas-enrol"]:
            self.assertIn(f"'{module}'", LOADER)
        self.assertIn("is_readable", LOADER)

    def test_login_redirect_and_menu_hiding_are_registered(self):
        self.assertIn("add_filter( 'login_redirect', 'cas_content_login_redirect'", ROLE)
        self.assertIn("add_action( 'admin_menu', 'cas_content_admin_menu', 999 )", ROLE)
        self.assertIn("add_action( 'admin_init', 'cas_content_screen_guard', 1 )", ROLE)


if __name__ == "__main__":
    unittest.main(verbosity=2)
