#!/usr/bin/env python3
"""Static contract for the Tutor signup-notice lock. No WordPress required."""
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
LOCK = (ROOT / "wp-content/mu-plugins/cas-signup-lock.php").read_text(encoding="utf-8")


class TutorSignupLockContract(unittest.TestCase):
    def test_mu_plugin_has_direct_access_guard(self):
        self.assertIn("defined( 'ABSPATH' ) || exit;", LOCK)

    def test_notice_callback_is_removed_by_exact_class_method(self):
        self.assertIn("add_action( 'admin_init', 'cas_signup_lock_remove_tutor_notice', 1 )", LOCK)
        self.assertIn("remove_action( 'admin_notices', $function, $priority )", LOCK)
        self.assertIn("'TUTOR\\\\User'", LOCK)
        self.assertIn("'show_registration_disabled'", LOCK)
        self.assertIn("remove_action( 'admin_notices', $function, $priority )", LOCK)

    def test_enable_get_is_denied_before_tutors_default_priority_handler(self):
        self.assertIn("add_action( 'admin_init', 'cas_signup_lock_deny_tutor_enable_link', 0 )", LOCK)
        self.assertIn("'GET' !== strtoupper", LOCK)
        self.assertIn("tutor-hide-notice", LOCK)
        self.assertIn("tutor-registration", LOCK)
        self.assertIn("status_header( 403 )", LOCK)
        self.assertIn("array( 'response' => 403 )", LOCK)

    def test_no_option_write_or_settings_general_interception(self):
        self.assertNotIn("update_option", LOCK)
        self.assertNotIn("pre_update_option_users_can_register", LOCK)
        self.assertNotIn("admin_init', 'cas_signup_lock_deny_tutor_enable_link', 10", LOCK)


if __name__ == "__main__":
    unittest.main(verbosity=2)
