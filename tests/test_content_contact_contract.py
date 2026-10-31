"""Shared-contact source contracts only; native behaviour belongs to Review."""

import re
import unittest
from pathlib import Path

INC = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-contact.php").read_text()


class SharedContactContract(unittest.TestCase):
    def test_exact_four_existing_keys_and_native_defaults(self):
        fields = MODULE.split("function cas_content_contact_fields()", 1)[1].split("function cas_content_contact_clean", 1)[0]
        self.assertEqual(
            re.findall(r"'(cas_social_[a-z]+)' => '([a-z]+)'", fields),
            [("cas_social_instagram", "instagram"), ("cas_social_facebook", "facebook"), ("cas_social_youtube", "youtube"), ("cas_social_email", "email")],
        )
        self.assertIn("$defaults = cas_social_defaults();", fields)
        self.assertIn("'sanitize' => 'email' === $name ? 'sanitize_email' : 'esc_url_raw'", fields)
        self.assertIn("'type' => 'email' === $name ? 'email' : 'url'", fields)
        self.assertNotRegex(MODULE, r"https?://")
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for key in ("instagram", "facebook", "youtube", "email"):
            self.assertIn("'cas_social_" + key + "'", allowlist)

    def test_exact_payload_and_native_input_controls(self):
        for text in ("count( $input ) !== 4", "array_diff_key( $input, $fields )", "array_diff_key( $fields, $input )", "! is_string( $input[ $key ] )", "call_user_func( $field['sanitize'], wp_unslash(", "array_key_exists( $key, $values )", "esc_attr( $value )", "current_user_can( CAS_CONTENT_CAP )"):
            self.assertIn(text, MODULE)
        for forbidden in ("wp_editor(", "set_theme_mod(", "update_option(", "$wpdb", "required=", "add_action("):
            self.assertNotIn(forbidden, MODULE)
        self.assertIn("existing shared values", MODULE)
        self.assertIn("contact and enquiry email destinations", MODULE)

    def test_site_wide_closed_route_without_default_or_renderer_change(self):
        shared = (INC / "cas-content-prose.php").read_text()
        loader = (INC / "cas-content-editor.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        self.assertIn("cas_content_prose_page( 'contact' )", MODULE)
        self.assertIn("'title' => 'Site-wide contact and social links'", shared)
        self.assertIn("'nonce' => 'cas_content_contact_save'", shared)
        self.assertIn("Empty fields stay empty; they do not restore defaults", shared)
        self.assertIn("existing renderer still prints its links", shared)
        self.assertEqual(loader.count("'cas-content-contact'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-site-details"), 2)
        self.assertIn("'name'  => 'Every page'", hub)
        self.assertNotIn("admin.php?page=cas-content-brand-images", hub)


if __name__ == "__main__":
    unittest.main()
