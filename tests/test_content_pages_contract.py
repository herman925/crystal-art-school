#!/usr/bin/env python3
"""Contract for the exact cas_content admin subpage allowlist (Route A integration)."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ROLE = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-role.php").read_text(encoding="utf-8")


class ContentPagesContract(unittest.TestCase):
    def test_only_hub_sales_coupons_and_enrol_are_default_allowed(self):
        start = ROLE.index("function cas_content_allowed_admin_page")
        end = ROLE.index("\n}", start)
        block = ROLE[start:end]
        self.assertIn("array( CAS_CONTENT_HUB_SLUG, 'cas-sales', 'cas-coupons', 'cas-enrol' )", block)
        self.assertIn("in_array( sanitize_key( $page ), $pages, true )", block)
        self.assertNotIn("strpos", block)
        self.assertNotIn("preg_match", block)

    def test_screen_guard_uses_exact_allowlist_and_redirects_elsewhere(self):
        self.assertIn("$page_ok    = ! $admin_page || cas_content_allowed_admin_page( $admin_page );", ROLE)
        self.assertIn("admin_url( 'admin.php?page=' . CAS_CONTENT_HUB_SLUG )", ROLE)


if __name__ == "__main__":
    unittest.main(verbosity=2)
