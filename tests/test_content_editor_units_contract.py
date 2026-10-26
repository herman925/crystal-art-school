"""Prepared source-only controls contracts; no save route or runtime proof."""

import re
import unittest
from pathlib import Path

INC = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc"


class ContentEditorUnitsContract(unittest.TestCase):
    def test_exact_existing_blog_key_and_default(self):
        source = (INC / "cas-content-blog.php").read_text()
        fields = source.split("function cas_content_blog_fields()", 1)[1].split(
            "function cas_content_blog_clean", 1
        )[0]
        self.assertEqual(re.findall(r"'(cas_blog_[a-z_]+)'", fields), ["cas_blog_intro"])
        self.assertIn("cas_blog_intro_default()", fields)
        self.assertIn("count( $input ) !== 1", source)
        self.assertIn("Leave this box empty to show no introduction", source)

    def test_exact_existing_free_video_keys_and_defaults(self):
        source = (INC / "cas-content-free-videos.php").read_text()
        fields = source.split("function cas_content_free_videos_fields()", 1)[1].split(
            "function cas_content_free_videos_clean", 1
        )[0]
        self.assertEqual(
            re.findall(r"'(cas_fv_[a-z_]+)'", fields),
            ["cas_fv_lead", "cas_fv_intro_demos", "cas_fv_intro_quick", "cas_fv_intro_shorts"],
        )
        self.assertEqual(fields.count("cas_fv_intro_default("), 4)
        self.assertIn("count( $input ) !== 4", source)
        self.assertIn("array_diff_key( $fields, $input )", source)
        self.assertIn("Leave a box empty to hide that paragraph", source)

    def test_preparation_preserves_native_controls_without_activation(self):
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for filename in ("cas-content-blog.php", "cas-content-free-videos.php"):
            source = (INC / filename).read_text()
            self.assertIn("PREPARATION ONLY", source)
            self.assertIn("current_user_can( CAS_CONTENT_CAP )", source)
            self.assertIn("wp_kses_post( wp_unslash(", source)
            self.assertIn("array_key_exists( $key, $values )", source)
            self.assertIn("wp_editor( wp_kses_post( $value )", source)
            self.assertIn("'wpautop'       => false", source)
            for forbidden in ("set_theme_mod(", "update_option(", "$wpdb", "add_action(", "add_filter(", "add_submenu_page(", "submit_button("):
                self.assertNotIn(forbidden, source)
            selector = source.split("_fields()", 1)[1].split("_clean(", 1)[0]
            for key in re.findall(r"'(cas_(?:blog_intro|fv_lead|fv_intro_[a-z]+))'", selector):
                self.assertIn("'" + key + "'", allowlist)


if __name__ == "__main__":
    unittest.main()
