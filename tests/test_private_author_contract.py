#!/usr/bin/env python3
"""Prepared source contracts, not runtime acceptance. Review owns execution and adversarial coverage."""
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[1]
SOURCE = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-private-files.php").read_text()


class PrivateAuthorContract(unittest.TestCase):
    def test_actual_role_cap_current_course_author_and_edit_permission(self):
        for text in ("cas_content_is_user( get_current_user_id() )", "current_user_can( 'manage_cas_content' )",
                     "(int) $course->post_author === get_current_user_id()", "current_user_can( 'edit_post', (int) $course->ID )"):
            self.assertIn(text, SOURCE)
        author = SOURCE.split("function cas_private_author_can_download")[1].split("/** Only this module")[0]
        self.assertNotIn("CAS_PRIVATE_META_COURSES", author)
        self.assertNotIn("instructor", author)
        self.assertNotIn("$_GET", author)
        self.assertIn("'courses','lesson'", author)
        self.assertIn("$binding === array( 'source'", author)

    def test_assignment_has_a_pre_mutation_guard_and_post_mutation_provenance(self):
        for hook in ("add_post_metadata", "update_post_metadata", "delete_post_metadata"):
            self.assertIn(f"add_filter( '{hook}', 'cas_private_guard_metadata', 0, 4 )", SOURCE)
        for hook in ("update_post_metadata_by_mid", "delete_post_metadata_by_mid"):
            self.assertIn(hook, SOURCE)
        self.assertIn("cas_private_is( $id ) ? ! cas_private_author_can_download( $id )", SOURCE)
        self.assertIn("cas_private_approved_assignment( $post_id, $ids )", SOURCE)
        self.assertIn("cas_private_approved_assignment( $post_id ) !== $ids", SOURCE)
        self.assertIn("'deleted_post_meta'", SOURCE)

    def test_security_metadata_and_storage_rewrites_are_guarded(self):
        for key in ("CAS_PRIVATE_META_REFS", "CAS_PRIVATE_META_COURSES", "CAS_PRIVATE_META_FLAG", "CAS_PRIVATE_META_ORIGIN", "_wp_attached_file", "_wp_attachment_metadata"):
            self.assertIn(key, SOURCE)
        self.assertIn("try { return $callback(); } finally { --$depth; }", SOURCE)
        self.assertIn("if ( $deleting && cas_private_deleting_post( $post_id ) )", SOURCE)
        self.assertIn("array( 'before_delete_post', 'delete_attachment' )", SOURCE)
        self.assertIn("0 !== strpos( $path, $root . DIRECTORY_SEPARATOR )", SOURCE)
        self.assertIn("basename( $file ) !== $file", SOURCE)

    def test_takeover_and_cross_course_reparent_are_checked_before_post_write(self):
        self.assertIn("add_filter( 'wp_insert_post_data'", SOURCE)
        self.assertIn("$id && ! cas_private_author_owns( $old )", SOURCE)
        self.assertIn("(int) $data['post_author'] !== get_current_user_id()", SOURCE)
        self.assertIn("(int) $old->ID !== (int) $course->ID", SOURCE)

    def test_admin_enrolled_path_keeps_existing_mapping(self):
        body = SOURCE.split("function cas_private_can_download")[1].split("/** The file to send")[0]
        self.assertIn("current_user_can( 'manage_options' )", body)
        self.assertIn("cas_private_author_can_download( $attachment_id )", body)
        self.assertIn("CAS_PRIVATE_META_COURSES", body)
        self.assertIn("EnrollmentModel::is_enrolled", body)

    def test_legacy_manifest_pins_graph_raw_list_file_identity_and_empty_marker_preimage(self):
        snapshot = SOURCE.split("function cas_private_reference_snapshot")[1].split("/** One reviewed legacy")[0]
        for text in ("post_parent,post_author,post_status", "meta_id,meta_value", "hash_file( 'sha256'", "'chain' => $chain", "'list' => $refs", "'files' => $files"):
            self.assertIn(text, snapshot)
        apply = SOURCE.split("function cas_private_apply_reference_manifest")[1].split("/** Stop guessed")[0]
        self.assertIn("empty( $expected['ids'] )", apply)
        self.assertIn("array() !== $expected['binding']", apply)
        self.assertIn("$current !== $expected", apply)
        self.assertIn("$after !== $expected", apply)
        self.assertIn("if ( $ours ) { return true; }", apply)
        self.assertIn("if ( ! $ours ) { return false; }", apply)
        self.assertIn("delete_post_meta( $current['source'], CAS_PRIVATE_META_REFS, $binding )", apply)


if __name__ == "__main__":
    unittest.main(verbosity=2)
