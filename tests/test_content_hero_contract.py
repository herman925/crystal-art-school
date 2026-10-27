"""Hero source contracts only; native media/browser/security execution belongs to Review."""

import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
INC = ROOT / "wp-content/themes/hello-elementor-child/inc"
MODULE = (INC / "cas-content-hero.php").read_text()
SCRIPT = (ROOT / "wp-content/themes/hello-elementor-child/js/cas-content-hero.js").read_text()


class HeroEditorContract(unittest.TestCase):
    def test_only_existing_three_by_four_url_string_settings(self):
        self.assertIn("array( 1, 2, 3 ) as $slide", MODULE)
        self.assertIn("array( 'title', 'subtitle', 'large', 'small' ) as $part", MODULE)
        self.assertIn("$native = cas_hero_fields();", MODULE)
        self.assertIn("cas_hero_default( $slide, $part )", MODULE)
        self.assertIn("'description' => $native[ $part ]['desc']", MODULE)
        self.assertIn("count( $input ) !== 12", MODULE)
        self.assertIn("! is_string( $input[ $key ] )", MODULE)
        self.assertIn("? esc_url_raw( $value ) : wp_kses_post( $value )", MODULE)
        allowlist = (INC / "cas-customizer-allowlist.php").read_text()
        for slide in (1, 2, 3):
            for part in ("title", "subtitle", "large", "small"):
                self.assertIn(f"'cas_hero_{slide}_{part}'", allowlist)
        self.assertIn(".val( image.url )", SCRIPT)
        self.assertNotIn(".val( image.id )", SCRIPT)
        self.assertNotIn("absint(", MODULE)

    def test_scoped_public_native_library_and_private_url_denial(self):
        for text in ("wp_enqueue_media();", "current_user_can( 'upload_files' )", "'cas-content-hero' !== $page", "'cas_hero_media'", "wp_verify_nonce(", "'post__in'] = array( 0 )", "'post_mime_type'] = 'image'", "'post_status'] = 'inherit'", "CAS_PRIVATE_META_FLAG, 'compare' => 'NOT EXISTS'", "array( 'relation' => 'AND', $query['meta_query'], $public )", "array_key_exists( CAS_PRIVATE_QUERY_VAR, $args )", "wp_attachment_is_image( $attachment )", "! cas_private_is( $attachment )", "array( 'private', 'trash' )"):
            self.assertIn(text, MODULE)
        self.assertIn("library: { type: 'image', cas_hero_media: casHeroMedia.nonce }", SCRIPT)
        self.assertIn("image.type === 'image'", SCRIPT)
        self.assertIn("searchParams.has( casHeroMedia.privateQuery )", SCRIPT)
        self.assertIn("url.protocol === 'https:'", SCRIPT)

    def test_native_controls_do_not_delete_or_expand_layout(self):
        self.assertIn("<textarea", MODULE)
        self.assertIn("input type=\"hidden\"", MODULE)
        self.assertIn("$can_media ? '' : ' disabled'", MODULE)
        self.assertIn("Images crop to their existing slots", MODULE)
        for forbidden in ("wp_editor(", "update_post_meta(", "delete_post_meta(", "wp_delete_attachment(", "unlink(", "set_theme_mod(", "$wpdb"):
            self.assertNotIn(forbidden, MODULE)
        self.assertIn(".val( '' )", SCRIPT)
        self.assertNotIn(".html(", SCRIPT)
        self.assertIn("aria-label=", MODULE)

    def test_exact_shared_route_and_unchanged_other_image_editors(self):
        loader = (INC / "cas-content-editor.php").read_text()
        shared = (INC / "cas-content-prose.php").read_text()
        hub = (INC / "cas-content-hub.php").read_text()
        self.assertIn("cas_content_prose_page( 'hero' )", MODULE)
        self.assertIn("'nonce' => 'cas_content_hero_save'", shared)
        self.assertIn("Clearing an image omits it", shared)
        self.assertIn("first title leaves the main heading empty", shared)
        self.assertEqual(loader.count("'cas-content-hero'"), 1)
        self.assertEqual(hub.count("admin.php?page=cas-content-hero"), 1)
        self.assertIn("Free videos card: photograph', 'cas_page_home_free_videos'", hub)
        self.assertIn("Portrait photograph', 'cas_page_about'", hub)

    def test_unresolved_local_uploads_fail_closed_without_rewriting_storage(self):
        self.assertIn("$lookup = preg_replace( '/[?#].*$/', '', $url );", MODULE)
        self.assertIn("attachment_url_to_postid( $lookup )", MODULE)
        self.assertIn("wp_get_upload_dir()", MODULE)
        self.assertIn("home_url( '/' )", MODULE)
        self.assertIn("rawurldecode( $path )", MODULE)
        self.assertIn("$lookup === cas_hero_default( $slide, $part )", MODULE)
        self.assertIn("return ! cas_content_hero_local_upload( $lookup );", MODULE)
        self.assertIn("$clean[ $key ] = $value;", MODULE)
        self.assertNotIn("return ! $attachment ||", MODULE)


if __name__ == "__main__":
    unittest.main()
