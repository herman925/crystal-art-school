"""Seven fixed-image source contracts; native upload/crop/privacy execution belongs to Review."""

import hashlib
import unittest
from pathlib import Path

INC = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-images.php").read_text()
MEDIA = (INC / "cas-content-media.php").read_text()


class FixedImagesContract(unittest.TestCase):
    def test_exact_seven_native_url_fields_and_default_factories(self):
        keys = ("cas_home_about_image", "cas_home_free_videos_photo", "cas_home_tools_photo", "cas_about_portrait", "cas_logo", "cas_logo2", "cas_signature")
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for key in keys:
            self.assertIn("'" + key + "'", MODULE)
            self.assertIn("'" + key + "'", allowlist)
        for factory in ("cas_home_about_default_image()", "cas_home_free_videos_photo_default()", "cas_about_fields()['cas_about_portrait']", "cas_brand_assets()", "get_stylesheet_directory_uri()"):
            self.assertIn(factory, MODULE)
        self.assertIn("array( 'cas_logo', 'cas_logo2', 'cas_signature' )", MODULE)
        self.assertNotRegex(MODULE, r"https?://")
        self.assertIn("esc_url_raw( wp_unslash(", MODULE)
        self.assertIn("! is_string( $input[ $key ] )", MODULE)

    def test_registered_hints_and_clear_storage_are_not_conflated(self):
        for hint in ("840 x 840px", "750 x 750px", "400 x 400px"):
            self.assertIn(hint, MODULE)
        self.assertIn("$native['desc']", MODULE)
        self.assertIn("$native[ $key ]['desc']", MODULE)
        self.assertIn("'cas_home_tools_photo' => array( 'label'", MODULE)
        self.assertIn("'default' => '', 'restore' => false", MODULE)
        self.assertIn("stores an empty URL", MODULE)
        self.assertIn("plain dark TOOLS card", MODULE)
        self.assertIn("$clean[ $key ] = $value;", MODULE)
        self.assertIn("data-cas-fallback=", MEDIA)
        self.assertIn("$preview = '' === $value ? $fallback : $value;", MEDIA)
        self.assertIn("esc_attr( $value )", MEDIA)
        for forbidden in ("set_theme_mod(", "delete_theme_mod(", "update_option(", "wp_delete_attachment(", "unlink(", "add_cap("):
            self.assertNotIn(forbidden, MODULE + MEDIA)

    def test_accepted_validator_and_public_query_move_verbatim(self):
        start = MEDIA.index("function cas_content_hero_local_upload")
        stop = MEDIA.index("function cas_content_image_slot")
        self.assertEqual(hashlib.sha256(MEDIA[start:stop].encode()).hexdigest(), "22cd569548235f6b4743b20ad763d7ad20d41e02bbdd914560f02d328fb36b2c")
        start = MEDIA.index("function cas_content_hero_media_query")
        stop = MEDIA.index("add_filter( 'ajax_query_attachments_args'")
        self.assertEqual(hashlib.sha256(MEDIA[start:stop].encode()).hexdigest(), "46e453f26cea4a038d46b7ea4e5e42a03647612a98ba59c2e257a77c293b4cd6")
        self.assertIn("! cas_content_hero_image_allowed( $value )", MODULE)
        self.assertIn("cas_content_hero_image_allowed( $field['default'] )", MEDIA)

    def test_three_fixed_page_routes_and_native_media_scope(self):
        shared = (INC / "cas-content-prose.php").read_text()
        loader = (INC / "cas-content-editor.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        for group in ("home-images", "about-image", "brand-images"):
            self.assertIn("cas_content_prose_page( '" + group + "' )", MODULE)
            self.assertIn("'" + group + "' => array(", shared)
            self.assertIn("cas-content-" + group, MEDIA)
        self.assertEqual(loader.count("'cas-content-media'"), 1)
        self.assertEqual(loader.count("'cas-content-images'"), 1)
        self.assertLess(loader.index("'cas-content-media'"), loader.index("'cas-content-hero'"))
        self.assertEqual(hub.count("'admin.php?page=cas-content-home-page'"), 1)
        self.assertEqual(hub.count("'admin.php?page=cas-content-about-page'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-site-details"), 2)
        self.assertIn("current_user_can( CAS_CONTENT_CAP )", MODULE)
        self.assertIn("current_user_can( 'upload_files' )", MODULE)

    def test_action_names_contain_visible_text_and_field_context(self):
        script = (INC.parent / "js/cas-content-hero.js").read_text()
        for text in ("$choose_text = $value ? 'Replace image' : 'Choose image';", "$clear_text = ! empty( $field['restore'] ) ? 'Use shipped default' : 'Clear slot';", "$choose_text . ' for ' . $field['label']", "$clear_text . ' for ' . $field['label']", "esc_html( $choose_text )", "esc_html( $clear_text )", "data-cas-label="):
            self.assertIn(text, MEDIA)
        self.assertIn(".text( 'Replace image' ).attr( 'aria-label', 'Replace image for ' + slot.data( 'casLabel' ) )", script)
        self.assertIn(".text( 'Choose image' ).attr( 'aria-label', 'Choose image for ' + slot.data( 'casLabel' ) )", script)
        self.assertNotIn("esc_attr( 'Clear ' . $field['label'] )", MEDIA)


if __name__ == "__main__":
    unittest.main()
