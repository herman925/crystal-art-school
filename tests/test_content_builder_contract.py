#!/usr/bin/env python3
"""Source checks prepared for Review; do not substitute these for native Tutor runtime acceptance."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BUILDER = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-builder.php").read_text()
GATE = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-price-gate.php").read_text()
FIXTURE = (ROOT / "tests/fixtures/cas-w1-builder.php").read_text()


class ContentBuilderContract(unittest.TestCase):
    def test_exact_native_menu_and_own_author_capability(self):
        self.assertIn("$pages[] = 'create-course'", BUILDER)
        self.assertIn("$menu['group_one']['course_builder']['callback']", BUILDER)
        self.assertIn("(int) $post->post_author === get_current_user_id()", BUILDER)
        self.assertIn("current_user_can( 'edit_post', $post->ID )", BUILDER)
        self.assertIn("new \\TUTOR\\Course( false )", BUILDER)
        self.assertIn("$course->load_course_builder_view()", BUILDER)
        self.assertIn("function_exists( 'cas_price_assert_publishable' )", BUILDER)

    def test_nonce_backed_post_with_dispatcher_action_and_no_author_transfer(self):
        self.assertIn("'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )", BUILDER)
        self.assertIn("$action !== ( $_POST['action'] ?? '' )", BUILDER)
        self.assertIn("$_REQUEST['action']", BUILDER)
        self.assertIn("wp_verify_nonce", BUILDER)
        self.assertIn("array( 'post_author', 'post_author_override' )", BUILDER)
        self.assertIn("'tutor_create_course', 'tutor_save_withdraw_account'", BUILDER)

    def test_curriculum_uses_current_parent_chain_before_native_handlers(self):
        self.assertIn("function cas_builder_course_for_item", BUILDER)
        self.assertIn("$post->post_type !== $type", BUILDER)
        self.assertIn("cas_builder_course_for_item( $post->post_parent, 'topics' )", BUILDER)
        self.assertIn("cas_builder_course_for_item( $lesson, 'lesson' ) !== $course", BUILDER)
        for action in ("tutor_save_topic", "tutor_delete_topic", "tutor_save_lesson", "tutor_delete_lesson", "tutor_lesson_details", "tutor_update_course_content_order"):
            self.assertIn(action, BUILDER)
        self.assertIn("cas_builder_owns_course( $course )", BUILDER)

    def test_no_role_grants_or_automatic_instructor_provisioning(self):
        for forbidden in ("add_role(", "add_cap(", "set_role(", "update_user_meta(", "approve_instructor("):
            self.assertNotIn(forbidden, BUILDER)
        self.assertIn("update_user_meta( $id, '_is_tutor_instructor', tutor_time() )", FIXTURE)
        self.assertIn("update_user_meta( $id, '_tutor_instructor_status', 'approved' )", FIXTURE)
        self.assertNotIn("add_user_meta(", FIXTURE, "the fixture must rely on Tutor's native author link")

    def test_publish_option_overlay_cannot_be_stored(self):
        self.assertIn("'option_tutor_option'", BUILDER)
        self.assertIn("'default_option_tutor_option'", BUILDER)
        self.assertIn("'pre_update_option_tutor_option'", BUILDER)
        self.assertIn("unset( $value['instructor_can_publish_course'] )", BUILDER)
        self.assertIn("SELECT option_value FROM {$wpdb->options}", BUILDER)
        self.assertNotIn("update_option(", BUILDER)

    def test_initial_paid_state_requires_fresh_native_draft(self):
        for check in ("cas_builder_fresh_course( $id )", "cas_builder_owns_course( $id )", "cas_builder_nonce_valid()",
                      "'tutor_create_new_draft_course' !==", "metadata_exists( 'post', $id, CAS_PRICE_PENDING_META )"):
            self.assertIn(check, GATE)
        self.assertIn("'tutor_draft_course_created'", GATE)
        self.assertIn("'paid'", GATE)
        self.assertIn("'Set first price'", GATE)
        self.assertIn("'rest_pre_insert_courses'", GATE)
        self.assertIn("'wp_insert_post_data'", GATE)

    def test_payout_settings_and_public_applications_remain_closed(self):
        for action in ("tutor_save_withdraw_account", "tutor_make_an_withdraw", "tutor_order_refund", "tutor_option_save",
                       "tutor_import_settings", "tutor_add_instructor", "instructor_approval_action", "tutor_apply_instructor"):
            self.assertIn(action, BUILDER)
        self.assertIn("Paid courses cannot be made publicly accessible here.", BUILDER)


if __name__ == "__main__":
    unittest.main(verbosity=2)
