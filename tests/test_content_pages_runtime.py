#!/usr/bin/env python3
"""cas-local proof of the exact admin-page allowlist and capability boundary.

No HTTP requests to wp-admin are made. It loads the actual theme code in the local WP process,
then checks the same exact page helper the admin_init screen guard uses. This is deliberately
not a substitute for Worker 2's end-to-end loaded-screen proof; that screen is in their W2 branch.
"""
import json
import os
import subprocess
import unittest

CONTAINER = "cas-local-wordpress-1"
PHP = r'''<?php
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
cas_content_role_install();
$editor = username_exists('w1pageseditor'); if (!$editor) { $editor=wp_create_user('w1pageseditor',wp_generate_password(32,false),'w1pageseditor@example.test'); }
$reader = username_exists('w1pagesreader'); if (!$reader) { $reader=wp_create_user('w1pagesreader',wp_generate_password(32,false),'w1pagesreader@example.test'); }
(new WP_User($editor))->set_role('cas_content'); (new WP_User($reader))->set_role('subscriber');
$allowed = array(); foreach (array('cas-content-hub','cas-sales','cas-coupons','cas-enrol','cas-course-prices') as $page) { $allowed[$page]=cas_content_allowed_admin_page($page); }
$closed = array(); foreach (array('tutor','tutor_settings','elementor','random','cas-sales-extra') as $page) { $closed[$page]=cas_content_allowed_admin_page($page); }
$out=array('allowed'=>$allowed,'closed'=>$closed,'editor_manage'=>user_can($editor,'manage_cas_content'),'reader_manage'=>user_can($reader,'manage_cas_content'));
// Delete only synthetic records created by this test.
wp_delete_user($editor); wp_delete_user($reader);
echo json_encode($out), "\n";
'''


def probe():
    done = subprocess.run(["docker", "exec", "-i", "-u", "www-data", CONTAINER, "php"],
                         input=PHP, capture_output=True, text=True, timeout=90)
    if done.returncode:
        raise AssertionError("cas-local PHP failed: " + done.stderr[-500:])
    return json.loads(done.stdout.strip().splitlines()[-1])


@unittest.skipUnless(os.environ.get("CAS_RUNTIME_TESTS") == "1", "set CAS_RUNTIME_TESTS=1 for local DB proof")
class ContentPagesRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.out = probe()

    def test_editor_can_use_the_exact_hub_and_subpages(self):
        self.assertTrue(self.out["editor_manage"])
        self.assertEqual(self.out["allowed"], {"cas-content-hub": True, "cas-sales": True, "cas-coupons": True, "cas-enrol": True, "cas-course-prices": True})

    def test_subscriber_does_not_have_the_screen_capability(self):
        self.assertFalse(self.out["reader_manage"])

    def test_unrelated_and_lookalike_page_slugs_are_closed(self):
        self.assertEqual(self.out["closed"], {"tutor": False, "tutor_settings": False, "elementor": False, "random": False, "cas-sales-extra": False})


if __name__ == "__main__":
    unittest.main(verbosity=2)
