#!/usr/bin/env python3
"""cas_content role, runtime proof on the local WordPress site (issue #150, Route A, W1-1).

Creates synthetic records and a synthetic cas_content user, sends crafted HTTP requests as that
user, records each allowed path as working and each closed path as denied, then removes all of it.

Runs only when CAS_RUNTIME_TESTS=1, because the local stack must have this branch's theme mounted
(CAS_CHILD_THEME_SRC) and the test changes local data. Run:
    CAS_RUNTIME_TESTS=1 python3 tests/test_content_role_runtime.py
"""
import http.cookiejar
import json
import os
import re
import subprocess
import unittest
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

BASE = os.environ.get("CAS_LOCAL_BASE", "http://127.0.0.1:18089")
CONTAINER = "cas-local-wordpress-1"
HUB = "admin.php?page=cas-content-hub"
LOGIN = "w1cascontent"


def php(script: str) -> str:
    done = subprocess.run(["docker", "exec", "-i", "-u", "www-data", CONTAINER, "php"],
                          input="<?php\nrequire '/var/www/html/wp-load.php';\n" + script,
                          capture_output=True, text=True, timeout=180)
    if done.returncode:
        raise RuntimeError("local php failed: " + done.stderr[-600:])
    return done.stdout


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


def opener():
    jar = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), NoRedirect)


def get(op, path):
    try:
        with op.open(BASE + "/" + path, timeout=30) as r:
            return r.status, r.read(), r.headers.get("Location", "")
    except urllib.error.HTTPError as e:
        return e.code, e.read(), e.headers.get("Location", "")


def verdict(status, location):
    """DENIED = refused (403) or sent to the hub. ALLOWED = the screen loaded."""
    if status == 403:
        return "DENIED"
    if status == 302 and HUB in location:
        return "DENIED"
    if status == 200:
        return "ALLOWED"
    return f"status {status}"


class ContentRoleRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("set CAS_RUNTIME_TESTS=1 (changes the local site)")
        fx = php("""
$uid = username_exists('w1cascontent') ?: wp_create_user('w1cascontent', wp_generate_password(40), 'w1cascontent@example.test');
$pw = wp_generate_password(32, false); wp_set_password($pw, $uid);
(new WP_User($uid))->set_role('cas_content');
$own = wp_insert_post(array('post_type'=>'post','post_status'=>'draft','post_title'=>'W1 own post','post_author'=>$uid));
$faq = wp_insert_post(array('post_type'=>'cas_faq','post_status'=>'draft','post_title'=>'W1 own faq','post_author'=>$uid));
$adm = wp_insert_post(array('post_type'=>'post','post_status'=>'draft','post_title'=>'W1 admin post','post_author'=>1));
$art = wp_insert_post(array('post_type'=>'cas_artwork','post_status'=>'draft','post_title'=>'W1 admin artwork','post_author'=>1));
$pg  = wp_insert_post(array('post_type'=>'page','post_status'=>'draft','post_title'=>'W1 page','post_author'=>1));
$tpl = wp_insert_post(array('post_type'=>'elementor_library','post_status'=>'draft','post_title'=>'W1 template','post_author'=>1));
echo json_encode(array('uid'=>$uid,'pw'=>$pw,'own'=>$own,'faq'=>$faq,'admin_post'=>$adm,'artwork'=>$art,'page'=>$pg,'template'=>$tpl));
""")
        cls.fx = json.loads(fx)
        cls.op = opener()

    @classmethod
    def tearDownClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            return
        php("""
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
foreach ($wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE 'W1 %'") as $id) { wp_delete_post((int)$id, true); }
$uid = username_exists('w1cascontent'); if ($uid) { wp_delete_user($uid); }
remove_role('cas_content'); delete_option('cas_content_role_version');
echo 'cleaned';
""")

    def login(self):
        get(self.op, "wp-login.php")  # sets the WordPress test cookie before the POST
        body = urllib.parse.urlencode({"log": LOGIN, "pwd": self.fx["pw"], "wp-submit": "Log In",
                                       "redirect_to": BASE + "/wp-admin/", "testcookie": "1"}).encode()
        try:
            with self.op.open(urllib.request.Request(BASE + "/wp-login.php", data=body), timeout=30) as r:
                location = r.headers.get("Location", "")
        except urllib.error.HTTPError as e:
            location = e.headers.get("Location", "")
        self.assertIn(HUB, location, "login must land on the hub")
        self.session = self.op

    def check(self, path, want):
        status, _, loc = get(self.session, path)
        got = verdict(status, loc)
        self.assertEqual(got, want, f"{path}: got {got} ({status} {loc[-60:]})")

    def test_0_login_lands_on_hub(self):
        self.login()

    def test_1_allowed_own_records_and_hub(self):
        self.login()
        f = self.fx
        self.check(f"wp-admin/post.php?post={f['own']}&action=edit", "ALLOWED")
        self.check(f"wp-admin/post.php?post={f['faq']}&action=edit", "ALLOWED")
        self.check("wp-admin/upload.php", "ALLOWED")
        self.check("wp-admin/edit.php", "ALLOWED")
        self.check("wp-admin/" + HUB, "ALLOWED")

    def test_2_denied_closed_screens(self):
        self.login()
        for path in ["wp-admin/plugins.php", "wp-admin/plugin-install.php", "wp-admin/users.php",
                     "wp-admin/user-new.php", "wp-admin/options-general.php", "wp-admin/options.php",
                     "wp-admin/themes.php", "wp-admin/theme-editor.php", "wp-admin/tools.php",
                     "wp-admin/export.php", "wp-admin/nav-menus.php",
                     "wp-admin/admin.php?page=tutor", "wp-admin/admin.php?page=tutor_settings"]:
            self.check(path, "DENIED")

    def test_3_denied_elementor_and_pages(self):
        self.login()
        f = self.fx
        self.check(f"wp-admin/post.php?post={f['template']}&action=edit", "DENIED")
        self.check(f"wp-admin/post.php?post={f['template']}&action=elementor", "DENIED")
        self.check("wp-admin/edit.php?post_type=elementor_library", "DENIED")
        self.check("wp-admin/post-new.php?post_type=elementor_library", "DENIED")
        self.check(f"wp-admin/post.php?post={f['page']}&action=edit", "DENIED")
        self.check("wp-admin/edit.php?post_type=page", "DENIED")

    def test_4_denied_admin_authored_records(self):
        self.login()
        f = self.fx
        self.check(f"wp-admin/post.php?post={f['admin_post']}&action=edit", "DENIED")
        self.check(f"wp-admin/post.php?post={f['artwork']}&action=edit", "DENIED")

    def test_5_crafted_requests_rejected(self):
        self.login()
        f = self.fx
        # REST write to the template, with a valid REST nonce for the synthetic user.
        nonce = self.rest_nonce()
        req = urllib.request.Request(BASE + f"/wp-json/wp/v2/elementor_library/{f['template']}",
                                     data=json.dumps({"title": "W1 hacked"}).encode(), method="POST",
                                     headers={"Content-Type": "application/json", "X-WP-Nonce": nonce})
        try:
            with self.session.open(req, timeout=30) as r:
                result = r.status
        except urllib.error.HTTPError as e:
            result = e.code
        self.assertIn(result, (401, 403), f"REST template write must be refused, got {result}")
        # Options write with a valid options nonce: the option must not change.
        before = php("echo get_option('blogname');")
        ononce = php(f"wp_set_current_user({f['uid']}); echo wp_create_nonce('update-options');").strip()
        body = urllib.parse.urlencode({"option_page": "general", "_wpnonce": ononce, "blogname": "W1 hacked"}).encode()
        try:
            with self.session.open(urllib.request.Request(BASE + "/wp-admin/options.php", data=body), timeout=30) as r:
                pass
        except urllib.error.HTTPError:
            pass
        self.assertEqual(php("echo get_option('blogname');"), before, "blogname must not change")

    def rest_nonce(self):
        """The REST nonce a logged-in admin page hands out. A CLI nonce is tied to no session and fails."""
        _, body, _ = get(self.session, "wp-admin/index.php")
        found = re.search(r'wpApiSettings = \{[^}]*"nonce":"([a-f0-9]+)"', body.decode("utf-8", "replace"))
        self.assertIsNotNone(found, "no REST nonce on the admin page")
        return found.group(1)

    def rest_post(self, route, payload):
        nonce = self.rest_nonce()
        req = urllib.request.Request(BASE + "/wp-json/wp/v2/" + route, data=json.dumps(payload).encode(), method="POST",
                                     headers={"Content-Type": "application/json", "X-WP-Nonce": nonce})
        try:
            with self.session.open(req, timeout=30) as r:
                return r.status
        except urllib.error.HTTPError as e:
            return e.code

    def test_6_create_is_denied_for_closed_types_and_allowed_for_posts(self):
        self.login()
        # Create through REST: a closed post type, a closed core type, and an allowed type.
        self.assertIn(self.rest_post("elementor_library", {"title": "W1 created template", "status": "draft"}), (401, 403))
        self.assertIn(self.rest_post("pages", {"title": "W1 created page", "status": "draft"}), (401, 403))
        self.assertEqual(self.rest_post("posts", {"title": "W1 created post", "status": "draft"}), 201)
        # Create through wp-admin screens.
        self.check("wp-admin/post-new.php?post_type=elementor_library", "DENIED")
        self.check("wp-admin/post-new.php?post_type=page", "DENIED")
        self.check("wp-admin/post-new.php", "ALLOWED")
        # Elementor AJAX is closed to the role.
        body = urllib.parse.urlencode({"action": "elementor_ajax", "_nonce": "x"}).encode()
        try:
            with self.session.open(urllib.request.Request(BASE + "/wp-admin/admin-ajax.php", data=body), timeout=30) as r:
                reply = r.read().decode("utf-8", "replace")
        except urllib.error.HTTPError as e:
            reply = e.read().decode("utf-8", "replace")
        self.assertNotIn('"success":true', reply.replace(" ", ""), "Elementor AJAX must not succeed for the role")


if __name__ == "__main__":
    unittest.main(verbosity=2)
