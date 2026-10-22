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

    def test_the_gate_is_open_only_inside_the_confirm_write(self):
        self.assertEqual(GATE.count("cas_price_gate_open( true )"), 1)
        self.assertEqual(GATE.count("cas_price_gate_open( false )"), 1)
        self.assertLess(GATE.index("cas_price_gate_open( true )"), GATE.index("update_post_meta( $course_id, 'tutor_course_price'"))

    def test_the_confirm_checks_run_in_order_and_fail_closed(self):
        order = ["check_admin_referer( 'cas_price_confirm' )", "get_transient( 'cas_price_' . $token )",
                 "delete_transient( 'cas_price_' . $token )", "cas_price_user_may_edit( $course_id )",
                 "$typed !== $expected", "cas_price_current( $course_id ) !== $data['old']",
                 "cas_audit_log( 'course_price', $line )", "update_post_meta( $course_id, 'tutor_course_price'"]
        body = GATE[GATE.index("function cas_price_confirm()"):]
        positions = [body.index(part) for part in order]
        self.assertEqual(positions, sorted(positions), "checks must run before the write, in this order")
        self.assertIn("! function_exists( 'cas_audit_log' ) || ! cas_audit_log(", GATE)

    def test_the_token_is_short_lived_single_use_and_user_bound(self):
        self.assertIn("CAS_PRICE_TOKEN_TTL = 600", GATE)
        self.assertIn("(int) $data['user'] !== get_current_user_id()", GATE)
        self.assertIn("delete_transient( 'cas_price_' . $token )", GATE)

    def test_the_gate_grants_no_admin_power_and_adds_no_manage_options(self):
        self.assertNotIn("'manage_options'", GATE)
        self.assertNotIn("manage_options", ROLE.split("function cas_content_role_caps")[1].split("}")[0])

    def test_the_screen_is_allowed_through_the_guard_and_loaded(self):
        self.assertIn("'cas-course-prices'", ROLE)
        self.assertIn("'cas-course-prices'", GATE)
        self.assertIn("'cas-price-gate'", LOADER)

    def test_the_role_can_edit_courses_and_lessons_but_not_publish_courses(self):
        caps = ROLE.split("function cas_content_role_caps")[1].split("}")[0]
        self.assertIn("'edit_tutor_courses'", caps)
        self.assertIn("'edit_tutor_lessons'", caps)
        self.assertNotIn("publish_tutor_courses", caps)
        self.assertNotIn("edit_others_tutor_courses", caps)


if __name__ == "__main__":
    unittest.main(verbosity=2)
