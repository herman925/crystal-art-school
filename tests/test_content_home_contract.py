"""Home nine-field source contracts only; native execution belongs to Review."""

import re
import unittest
from pathlib import Path

INC = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-home.php").read_text()


class HomeProseContract(unittest.TestCase):
    def test_exact_nine_existing_keys_and_native_sanitizers(self):
        fields = MODULE.split("function cas_content_home_fields()", 1)[1].split("function cas_content_home_clean", 1)[0]
        expected = [
            "cas_home_free_videos_text", "cas_home_gallery_sub", "cas_home_shop_lede",
            "cas_home_overview_lede_1", "cas_home_overview_lede_2",
            "cas_home_overview_point_1_t", "cas_home_overview_point_1_b",
            "cas_home_overview_point_2_t", "cas_home_overview_point_2_b",
        ]
        self.assertEqual(re.findall(r"'(cas_home_[a-z0-9_]+)' => array", fields), expected)
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for key in expected:
            self.assertIn("'" + key + "'", allowlist)
        self.assertEqual(fields.count("'sanitize' => 'wp_kses_post'"), 1)
        self.assertEqual(fields.count("'sanitize' => 'sanitize_text_field'"), 1)
        self.assertEqual(fields.count("'sanitize' => 'sanitize_textarea_field'"), 7)
        self.assertIn("CAS_HOME_GALLERY_SUB_DEFAULT", fields)
        self.assertIn("cas_overview_principle_default( 2, 'text' )", fields)

    def test_plain_native_controls_and_exact_input_shape(self):
        self.assertNotIn("wp_editor(", MODULE)
        self.assertIn("esc_textarea( $value )", MODULE)
        self.assertIn("esc_attr( $value )", MODULE)
        self.assertIn("array_key_exists( $key, $values )", MODULE)
        self.assertIn("count( $input ) !== 9", MODULE)
        self.assertIn("array_diff_key( $input, $fields )", MODULE)
        self.assertIn("array_diff_key( $fields, $input )", MODULE)
        self.assertIn("call_user_func( $field['sanitize'], wp_unslash(", MODULE)
        self.assertIn("current_user_can( CAS_CONTENT_CAP )", MODULE)
        for forbidden in ("$wpdb", "set_theme_mod(", "update_option(", "add_action("):
            self.assertNotIn(forbidden, MODULE)

    def test_closed_shared_route_and_preserved_image_editors(self):
        shared = (INC / "cas-content-prose.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        loader = (INC / "cas-content-editor.php").read_text()
        self.assertIn("cas_content_prose_page( 'home' )", MODULE)
        self.assertIn("'nonce' => 'cas_content_home_save'", shared)
        self.assertIn("'fields' => 'cas_content_home_fields'", shared)
        self.assertIn("without deleting the saved body text", shared)
        self.assertEqual(loader.count("'cas-content-home'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-home"), 4)
        self.assertEqual(hub.count("admin.php?page=cas-content-home-images"), 3)
        self.assertIn("'fields' => 'cas_content_home_images_fields'", shared)


if __name__ == "__main__":
    unittest.main()
