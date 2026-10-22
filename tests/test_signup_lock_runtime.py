#!/usr/bin/env python3
"""Runtime proof of Tutor's registration notice lock on cas-local; production is never contacted.

The local DB's users_can_register is explicitly left at 0 after the tests. The test copies only the
new mu-plugin into the local container and removes it afterwards. It does not fetch authenticated
admin links. Run: python3 tests/test_signup_lock_runtime.py
"""
import json
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONTAINER = "cas-local-wordpress-1"
PLUGIN = ROOT / "wp-content/mu-plugins/cas-signup-lock.php"
PROBE = r'''<?php
define('WP_ADMIN', true);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
update_option('users_can_register', 0);
$admins = get_users(array('role'=>'administrator','fields'=>'ID','number'=>1));
$admin = $admins ? (int)$admins[0] : 0;
wp_set_current_user($admin);
$notice_callback = null;
foreach ( $GLOBALS['wp_filter']['admin_notices']->callbacks as $callbacks ) {
	foreach ( $callbacks as $callback ) {
		$f = $callback['function'] ?? null;
		if ( is_array( $f ) && is_object( $f[0] ) && is_a( $f[0], 'TUTOR\\User' ) && 'show_registration_disabled' === $f[1] ) { $notice_callback = $f; }
	}
}
$notice_hooked = is_array( $notice_callback );
cas_signup_lock_remove_tutor_notice();
$notice_hooked_after = $notice_callback && has_action('admin_notices', $notice_callback) !== false;
ob_start(); do_action('admin_notices'); $notice_html = ob_get_clean();

// This goes through admin_init, where the mu-plugin guard runs at 0 before Tutor at 10.
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = array('tutor-hide-notice'=>'registration', 'tutor-registration'=>'enable', '_wpnonce'=>'invalid');
add_filter('wp_die_handler', function () { return function($message, $title='', $args=array()) { throw new RuntimeException(json_encode(array('title'=>$title,'args'=>$args))); }; });
$blocked = false; $details = array();
try { do_action('admin_init'); } catch (RuntimeException $e) { $blocked = true; $details = json_decode($e->getMessage(), true) ?: array(); }
$after_get = (int)get_option('users_can_register');

// Settings > General's native option write path remains untouched for an administrator.
$admin_cap = current_user_can('manage_options');
update_option('users_can_register', 1); $settings_can_enable = 1 === (int)get_option('users_can_register');
update_option('users_can_register', 0); $settings_can_disable = 0 === (int)get_option('users_can_register');
$final = (int)get_option('users_can_register');
echo json_encode(array('admin_cap'=>$admin_cap,'notice_hooked_before'=>$notice_hooked,'notice_hooked_after'=>$notice_hooked_after,
 'notice_absent'=>false===strpos($notice_html,'tutor-user-registration-notice-wrapper'),
 'enable_get_blocked'=>$blocked,'blocked_response'=>$details['args']['response'] ?? null,'registration_after_get'=>$after_get,
 'settings_general_enable_path'=>$settings_can_enable,'settings_general_disable_path'=>$settings_can_disable,'registration_final'=>$final)),"\n";
'''


def run_probe():
    done = subprocess.run(["docker", "exec", "-i", "-u", "www-data", CONTAINER, "php"],
                          input=PROBE, capture_output=True, text=True, timeout=120)
    if done.returncode:
        raise AssertionError("local PHP proof failed: " + done.stderr[-700:])
    return json.loads(done.stdout.strip().splitlines()[-1])


class SignupLockRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        subprocess.run(["docker", "cp", str(PLUGIN), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-signup-lock.php"], check=True)
        cls.result = run_probe()

    @classmethod
    def tearDownClass(cls):
        subprocess.run(["docker", "exec", CONTAINER, "rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-signup-lock.php"], check=False)
        # Guard against a test bug leaving this local-only option enabled.
        restore = subprocess.run(["docker", "exec", "-u", "www-data", CONTAINER, "php", "-r",
                                  "require '/var/www/html/wp-load.php'; update_option('users_can_register', 0);"],
                                 capture_output=True, text=True, timeout=30)
        if restore.returncode:
            raise RuntimeError("could not leave cas-local registration disabled")

    def test_admin_user_and_exact_tutor_notice_callback_found(self):
        self.assertTrue(self.result["admin_cap"])
        self.assertTrue(self.result["notice_hooked_before"])

    def test_notice_callback_removed_and_markup_absent(self):
        self.assertFalse(self.result["notice_hooked_after"])
        self.assertTrue(self.result["notice_absent"])

    def test_crafted_enable_get_is_blocked_before_tutor_and_option_stays_off(self):
        self.assertTrue(self.result["enable_get_blocked"])
        self.assertEqual(self.result["blocked_response"], 403)
        self.assertEqual(self.result["registration_after_get"], 0)

    def test_settings_general_option_path_still_works_and_ends_off(self):
        self.assertTrue(self.result["settings_general_enable_path"])
        self.assertTrue(self.result["settings_general_disable_path"])
        self.assertEqual(self.result["registration_final"], 0)


if __name__ == "__main__":
    unittest.main(verbosity=2)
