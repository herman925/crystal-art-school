"""Static About regression contracts; native execution belongs to independent Review."""

import re
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
INC = ROOT / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-about.php").read_text()
SHARED = (INC / "cas-content-prose.php").read_text()


class AboutFormContract(unittest.TestCase):
    def test_exact_five_existing_settings(self):
        selector = MODULE.split("function cas_content_about_fields()", 1)[1].split(
            "function cas_content_about_clean", 1
        )[0]
        keys = re.findall(r"'cas_about_[a-z_]+'", selector)
        self.assertEqual(keys, ["'cas_about_intro'", "'cas_about_quote'", "'cas_about_journey_body'", "'cas_about_community_body'", "'cas_about_instructor_body'"])
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for key in keys:
            self.assertIn(key, allowlist)
        for forbidden in ("set_theme_mod(", "update_option(", "add_option("):
            self.assertNotIn(forbidden, MODULE + SHARED)

    def test_existing_cap_post_and_nonce(self):
        save = SHARED.split("function cas_content_prose_save(", 1)[1]
        self.assertIn("current_user_can( CAS_CONTENT_CAP )", save)
        self.assertIn("'POST' !==", save)
        self.assertIn("wp_verify_nonce( wp_unslash( $nonce ), $config['nonce'] )", save)
        self.assertIn("'nonce' => 'cas_content_about_save'", SHARED)
        self.assertIn("wp_nonce_field( $config['nonce'] )", save)
        self.assertIn("array_diff_key( $input, $fields )", MODULE)
        self.assertIn("array_diff_key( $fields, $input )", MODULE)

    def test_actual_whole_option_conditional_sql(self):
        self.assertIn("$wpdb->query( $wpdb->prepare(", SHARED)
        self.assertIn("SET option_value = %s WHERE option_id = %d", SHARED)
        for name in ("option_name", "option_value", "autoload"):
            self.assertIn("BINARY " + name + " = BINARY %s", SHARED)
        self.assertNotIn("SET autoload", SHARED)
        self.assertIn("$mods = $row['mods'];", SHARED)
        self.assertIn("hash_equals( cas_content_prose_snapshot", SHARED)
        self.assertIn("0 === $changed", SHARED)
        self.assertNotIn("while (", SHARED)

    def test_cache_readback_and_safe_decode(self):
        for required in (
            "'allowed_classes' => false",
            "serialize( $mods ) !== $row['option_value']",
            "array( $option, 'alloptions', 'notoptions' )",
            "wp_cache_delete( $cache_key, 'options' )",
            "$after['option_value'] !== $raw",
            "$after['autoload'] !== $row['autoload']",
            "serialize( get_option( $option ) ) !== $raw",
        ):
            self.assertIn(required, SHARED)
        self.assertNotIn("wp_cache_flush", SHARED)

    def test_native_editor_inline_safety_and_default_warning(self):
        for required in ("wp_editor( $value, $key", "wp_kses_post( wp_unslash(", "'inline' === $field['type']", "'forced_root_block' => $inline ? false : 'p'", "'classes' => 'bio-sub'"):
            self.assertIn(required, MODULE)
        for required in ("Leaving a box empty restores its shipped wording", "Cached public pages may take time to refresh", "'disabled' => 'disabled'"):
            self.assertIn(required, SHARED)

    def test_minimal_entry_points(self):
        loader = (INC / "cas-content-editor.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        self.assertEqual(loader.count("'cas-content-about'"), 1)
        self.assertEqual(loader.count("'cas-content-prose'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-about"), 1)
        self.assertIn("Portrait photograph', 'cas_page_about'", hub)
        self.assertIn("cas_content_prose_page( 'about' )", MODULE)
        self.assertIn("array( 'cas-content-about', 'cas-content-blog', 'cas-content-free-videos', 'cas-content-home', 'cas-content-contact' )", SHARED)

    def test_only_five_closed_server_selected_groups(self):
        config = SHARED.split("function cas_content_prose_config(", 1)[1].split("function cas_content_prose_allowed_pages", 1)[0]
        self.assertEqual(re.findall(r"^\t\t'([^']+)' => array", config, re.MULTILINE), ["about", "blog", "free-videos", "home", "contact"])
        self.assertNotIn("$_POST['group']", SHARED)
        self.assertNotIn("apply_filters(", SHARED)
        self.assertIn("$config = cas_content_prose_config( $group );", SHARED)
        self.assertIn("get_current_user_id() . '|' . $group . '|' . $option", SHARED)


if __name__ == "__main__":
    unittest.main()
