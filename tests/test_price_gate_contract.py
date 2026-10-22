#!/usr/bin/env python3
"""Static contract for the course price gate (issue #150, Route A, W1-3). No WordPress needed."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
GATE = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-price-gate.php").read_text(encoding="utf-8")
ROLE = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-role.php").read_text(encoding="utf-8")
LOADER = (ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-editor.php").read_text(encoding="utf-8")


class PriceGateContract(unittest.TestCase):
    def test_every_metadata_write_route_is_refused_for_the_role(self):
        for hook in ["update_post_metadata", "add_post_metadata", "delete_post_metadata"]:
            self.assertIn(f"add_filter( '{hook}', 'cas_price_block_role_write', 10, 3 )", GATE)

    def test_the_block_covers_the_three_price_keys_and_only_courses(self):
        for key in ["tutor_course_price", "tutor_course_sale_price", "_tutor_course_price_type"]:
            self.assertIn(f"'{key}'", GATE)
        self.assertIn("'courses' !== get_post_type", GATE)

    def test_gate_window_resets_and_pair_is_verified(self):
        self.assertIn("function cas_price_write_pair", GATE)
        self.assertIn("finally", GATE)
        self.assertIn("cas_price_gate_open( false )", GATE)
        self.assertIn("return cas_price_current( $course_id ) === $pair", GATE)

    def test_unique_token_and_course_locks_are_atomic_and_owner_scoped(self):
        self.assertIn("INSERT IGNORE INTO {$wpdb->options}", GATE)
        self.assertIn("return 1 === $inserted", GATE)
        self.assertNotIn("return add_option(", GATE)
        self.assertIn("CAS_PRICE_COURSE_LOCK_TTL = 600", GATE)
        self.assertIn("function cas_price_acquire_course_lock", GATE)
        self.assertIn("$wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $existing )", GATE)
        self.assertIn("function cas_price_release_course_lock", GATE)
        self.assertIn("$wpdb->delete( $wpdb->options, array( 'option_name' => $key, 'option_value' => $value )", GATE)
        prune = GATE[GATE.index("function cas_price_prune_claims()") : GATE.index("function cas_price_claim_token(")]
        self.assertIn("'option_value' => $row['option_value']", prune, "expired prune must CAS-delete the exact observed value")
        self.assertNotIn("delete_option( $key )", prune, "pruning must not delete a successor's replacement lock")

    def test_confirm_claims_then_locks_before_stale_read_and_releases_in_finally(self):
        body = GATE[GATE.index("function cas_price_confirm()") : GATE.index("add_action( 'admin_post_cas_price_confirm'")]
        order = ["cas_price_claim_token( $token", "cas_price_acquire_course_lock( $course_id",
                 "cas_price_current( $course_id ) !== $data['old']"]
        positions = [body.index(part) for part in order]
        self.assertEqual(positions, sorted(positions))
        self.assertIn("finally", body)
        self.assertIn("cas_price_release_course_lock( $course_id, $lock )", body)

    def test_failed_pair_is_verified_rolled_back_and_never_called_saved(self):
        self.assertIn("function cas_price_restore_pair", GATE)
        self.assertIn("$current[ $field ] === $attempted[ $field ]", GATE)
        self.assertIn("cas_price_restore_pair( $course_id, $data['old'], $data['new'] )", GATE)
        self.assertIn("'rollback_failed'", GATE)
        self.assertIn("Stop editing and contact Herman immediately.", GATE)

    def test_audit_distinguishes_attempt_from_verified_change_and_failure(self):
        self.assertIn("cas_audit_log( 'course_price_attempt', $line )", GATE)
        self.assertIn("cas_audit_log( 'course_price', $line )", GATE)
        self.assertIn("cas_audit_log( 'course_price_write_failed'", GATE)

    def test_confirm_order_includes_audit_write_and_readback(self):
        body = GATE[GATE.index("function cas_price_confirm()") : GATE.index("add_action( 'admin_post_cas_price_confirm'")]
        order = ["cas_audit_log( 'course_price_attempt', $line )", "cas_price_write_pair( $course_id, $data['new'] )",
                 "cas_audit_log( 'course_price', $line )", "cas_price_current( $course_id ) === $data['new']"]
        positions = [body.index(part) for part in order]
        self.assertEqual(positions, sorted(positions))

    def test_token_is_short_lived_and_bound_to_the_user(self):
        self.assertIn("CAS_PRICE_TOKEN_TTL = 600", GATE)
        self.assertIn("(int) $data['user'] !== get_current_user_id()", GATE)
        self.assertIn("cas_price_claim_token( $token, (int) $data['expires'] )", GATE)

    def test_no_admin_power_and_exact_price_page(self):
        self.assertNotIn("'manage_options'", GATE)
        caps = ROLE.split("function cas_content_role_caps")[1].split("}")[0]
        self.assertNotIn("manage_options", caps)
        self.assertIn("'cas-course-prices'", ROLE)
        self.assertIn("'cas-course-prices'", GATE)
        self.assertIn("'cas-price-gate'", LOADER)

    def test_tutor_edit_caps_are_own_record_only(self):
        caps = ROLE.split("function cas_content_role_caps")[1].split("}")[0]
        for cap in ["edit_tutor_courses", "edit_tutor_lessons", "publish_tutor_lessons"]:
            self.assertIn(f"'{cap}'", caps)
        self.assertNotIn("publish_tutor_courses", caps)
        self.assertNotIn("edit_others_tutor_courses", caps)


if __name__ == "__main__":
    unittest.main(verbosity=2)
