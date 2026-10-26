"""Static About-form contracts only; native execution belongs to independent Review."""

import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
INC = ROOT / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-about.php").read_text()


class AboutFormContract(unittest.TestCase):
    def test_exact_five_existing_settings(self):
        selector = MODULE.split("function cas_content_about_fields()", 1)[1].split(
            "function cas_content_about_allowed_pages", 1
        )[0]
        keys = re.findall(r"'cas_about_[a-z_]+'", selector)
        self.assertEqual(
            keys,
            [
                "'cas_about_intro'",
                "'cas_about_quote'",
                "'cas_about_journey_body'",
                "'cas_about_community_body'",
                "'cas_about_instructor_body'",
            ],
        )
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for key in keys:
            self.assertIn(key, allowlist)
        self.assertNotIn("set_theme_mod(", MODULE)
        self.assertNotIn("update_option(", MODULE)
        self.assertNotIn("add_option(", MODULE)

    def test_existing_cap_post_and_nonce(self):
        save = MODULE.split("function cas_content_about_save(", 1)[1]
        self.assertIn("current_user_can( CAS_CONTENT_CAP )", save)
        self.assertIn("'POST' !==", save)
        self.assertIn("wp_verify_nonce(", save)
        self.assertIn("wp_nonce_field( 'cas_content_about_save' )", save)
        self.assertIn("array_diff_key( $input, $fields )", MODULE)
        self.assertIn("array_diff_key( $fields, $input )", MODULE)

    def test_actual_whole_option_conditional_sql(self):
        self.assertIn("$wpdb->query( $wpdb->prepare(", MODULE)
        self.assertIn("SET option_value = %s WHERE option_id = %d", MODULE)
        self.assertIn("BINARY option_name = BINARY %s", MODULE)
        self.assertIn("BINARY option_value = BINARY %s", MODULE)
        self.assertIn("BINARY autoload = BINARY %s", MODULE)
        self.assertNotIn("SET autoload", MODULE)
        self.assertIn("$mods = $row['mods'];", MODULE)
        self.assertIn("hash_equals( cas_content_about_snapshot", MODULE)
        self.assertIn("0 === $changed", MODULE)
        self.assertNotIn("while (", MODULE)

    def test_cache_readback_and_safe_decode(self):
        self.assertIn("'allowed_classes' => false", MODULE)
        self.assertIn("serialize( $mods ) !== $row['option_value']", MODULE)
        self.assertIn("array( $option, 'alloptions', 'notoptions' )", MODULE)
        self.assertIn("wp_cache_delete( $cache_key, 'options' )", MODULE)
        self.assertIn("$after['option_value'] !== $raw", MODULE)
        self.assertIn("$after['autoload'] !== $row['autoload']", MODULE)
        self.assertIn("serialize( get_option( $option ) ) !== $raw", MODULE)
        self.assertNotIn("wp_cache_flush", MODULE)

    def test_native_editor_inline_safety_and_default_warning(self):
        self.assertIn("wp_editor( $value, $key", MODULE)
        self.assertIn("wp_kses_post( wp_unslash(", MODULE)
        self.assertIn("'inline' === $field['type']", MODULE)
        self.assertIn("'forced_root_block' => $inline ? false : 'p'", MODULE)
        self.assertIn("'classes' => 'bio-sub'", MODULE)
        self.assertIn("Leaving a box empty restores its shipped wording", MODULE)
        self.assertIn("Cached public pages may take time to refresh", MODULE)
        self.assertIn("'disabled' => 'disabled'", MODULE)

    def test_minimal_entry_points(self):
        loader = (INC / "cas-content-editor.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        self.assertEqual(loader.count("'cas-content-about'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-about"), 1)
        self.assertIn("Portrait photograph', 'cas_page_about'", hub)
        self.assertIn("$pages[] = 'cas-content-about';", MODULE)


if __name__ == "__main__":
    unittest.main()
