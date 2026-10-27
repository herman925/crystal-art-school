"""Hero source contracts only; native media/browser/security execution belongs to Review."""

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
INC = ROOT / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-hero.php").read_text()
MEDIA = (INC / "cas-content-media.php").read_text()
SCRIPT = (ROOT / "wp-content/themes/hello-elementor-child/js/cas-content-hero.js").read_text()


class HeroEditorContract(unittest.TestCase):
    def test_only_existing_three_by_four_url_string_settings(self):
        for text in ("array( 1, 2, 3 ) as $slide", "array( 'title', 'subtitle', 'large', 'small' ) as $part", "$native = cas_hero_fields();", "cas_hero_default( $slide, $part )", "'description' => $native[ $part ]['desc']", "count( $input ) !== 12", "! is_string( $input[ $key ] )", "? esc_url_raw( $value ) : wp_kses_post( $value )"):
            self.assertIn(text, MODULE)
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for slide in (1, 2, 3):
            for part in ("title", "subtitle", "large", "small"):
                self.assertIn(f"'cas_hero_{slide}_{part}'", allowlist)
        self.assertIn(".val( image.url )", SCRIPT)
        self.assertNotIn(".val( image.id )", SCRIPT)
        self.assertNotIn("absint(", MODULE + MEDIA)

    def test_scoped_public_native_library_and_private_url_denial(self):
        for text in ("wp_enqueue_media();", "current_user_can( 'upload_files' )", "array( 'cas-content-hero', 'cas-content-home-images', 'cas-content-about-image', 'cas-content-brand-images' )", "'cas_hero_media'", "wp_verify_nonce(", "'post__in'] = array( 0 )", "'post_mime_type'] = 'image'", "'post_status'] = 'inherit'", "CAS_PRIVATE_META_FLAG, 'compare' => 'NOT EXISTS'", "array( 'relation' => 'AND', $query['meta_query'], $public )", "array_key_exists( CAS_PRIVATE_QUERY_VAR, $args )", "wp_attachment_is_image( $attachment )", "! cas_private_is( $attachment )", "array( 'private', 'trash' )"):
            self.assertIn(text, MEDIA)
        for text in ("library: { type: 'image', cas_hero_media: casHeroMedia.nonce }", "image.type === 'image'", "searchParams.has( casHeroMedia.privateQuery )", "url.protocol === 'https:'"):
            self.assertIn(text, SCRIPT)

    def test_native_controls_do_not_delete_or_expand_layout(self):
        self.assertIn("<textarea", MODULE)
        self.assertIn("cas_content_image_slot(", MODULE)
        self.assertIn("input type=\"hidden\"", MEDIA)
        self.assertIn("$can_media ? '' : ' disabled'", MEDIA)
        self.assertIn("Images crop to their existing slots", MODULE)
        for forbidden in ("wp_editor(", "update_post_meta(", "delete_post_meta(", "wp_delete_attachment(", "unlink(", "set_theme_mod(", "$wpdb"):
            self.assertNotIn(forbidden, MODULE + MEDIA)
        self.assertIn(".val( '' )", SCRIPT)
        self.assertNotIn(".html(", SCRIPT)
        self.assertIn("aria-label=", MEDIA)

    def test_exact_shared_route_and_other_fixed_image_editors(self):
        loader = (INC / "cas-content-editor.php").read_text()
        shared = (INC / "cas-content-prose.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        self.assertIn("cas_content_prose_page( 'hero' )", MODULE)
        self.assertIn("'nonce' => 'cas_content_hero_save'", shared)
        self.assertIn("Clearing an image omits it", shared)
        self.assertIn("first title leaves the main heading empty", shared)
        self.assertEqual(loader.count("'cas-content-hero'"), 1)
        self.assertEqual(loader.count("'cas-content-media'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-hero"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-home-images"), 3)
        self.assertEqual(hub.count("admin.php?page=cas-content-about-image"), 1)

    def test_unresolved_local_uploads_fail_closed_without_rewriting_storage(self):
        for text in ("$lookup = preg_replace( '/[?#].*$/', '', $url );", "attachment_url_to_postid( $lookup )", "wp_get_upload_dir()", "home_url( '/' )", "rawurldecode( $path )", "$lookup === cas_hero_default( $slide, $part )", "return ! cas_content_hero_local_upload( $lookup );"):
            self.assertIn(text, MEDIA)
        self.assertIn("$clean[ $key ] = $value;", MODULE)
        self.assertNotIn("return ! $attachment ||", MODULE + MEDIA)


if __name__ == "__main__":
    unittest.main()
