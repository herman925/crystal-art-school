#!/usr/bin/env python3
"""Local HTTP proof for Tutor's one-click registration Enable lock. Never contacts production.

Copies only the lock mu-plugin into cas-local and creates a synthetic administrator. Sends GET,
HEAD, and POST requests to Tutor's exact query trigger and reads users_can_register after each.
Then it submits the real Settings > General form over HTTP with its nonce to enable and disable
registration. Cleanup leaves the local option at 0 and removes the synthetic user and plugin.
No browser is used.
"""
import http.cookiejar
import html.parser
import json
import subprocess
import unittest
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = "http://127.0.0.1:18089"
CONTAINER = "cas-local-wordpress-1"
PLUGIN = ROOT / "wp-content/mu-plugins/cas-signup-lock.php"
LOGIN = "w1signupadmin"

SETUP = r'''<?php
define('WP_ADMIN', true);
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/screen.php';
update_option('users_can_register', 0);
$uid = username_exists('w1signupadmin');
if ( ! $uid ) { $uid = wp_create_user('w1signupadmin', wp_generate_password(40), 'w1signupadmin@example.test'); }
$pw = wp_generate_password(32, false); wp_set_password($pw, $uid); (new WP_User($uid))->set_role('administrator');
wp_set_current_user($uid);
$notice = null;
foreach ( $GLOBALS['wp_filter']['admin_notices']->callbacks as $callbacks ) foreach ( $callbacks as $cb ) {
  $f = $cb['function'] ?? null;
  if ( is_array($f) && is_object($f[0]) && is_a($f[0], 'TUTOR\\User') && 'show_registration_disabled' === $f[1] ) { $notice = $f; }
}
$before = is_array($notice); cas_signup_lock_remove_tutor_notice();
$after = $notice && has_action('admin_notices', $notice) !== false;
ob_start(); do_action('admin_notices'); $markup = ob_get_clean();
echo json_encode(array('id'=>(int)$uid,'pw'=>$pw,'manage'=>current_user_can('manage_options'),
 'notice_before'=>$before,'notice_after'=>$after,'notice_markup_absent'=>false===strpos($markup,'tutor-user-registration-notice-wrapper'))), "\n";
'''

CLEANUP = r'''<?php
require '/var/www/html/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/user.php';
$uid = username_exists('w1signupadmin'); if ($uid) { wp_delete_user($uid); }
update_option('users_can_register', 0);
echo json_encode(array('user_left'=>(bool)username_exists('w1signupadmin'),'users_can_register'=>(int)get_option('users_can_register'))), "\n";
'''

def php(script):
    done = subprocess.run(["docker", "exec", "-i", "-u", "www-data", CONTAINER, "php"], input=script,
                          capture_output=True, text=True, timeout=90)
    if done.returncode:
        raise AssertionError("local PHP failed: " + done.stderr[-700:])
    return json.loads(done.stdout.strip().splitlines()[-1])

def option_value():
    return int(php("<?php require '/var/www/html/wp-load.php'; echo json_encode((int)get_option('users_can_register'));"))

class OptionsFormParser(html.parser.HTMLParser):
    """Serialize the actual General settings form like a browser does."""
    def __init__(self):
        super().__init__(); self.in_form = False; self.found_form = False; self.fields = []; self.select = None; self.textarea = None
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "form" and "options.php" in a.get("action", ""):
            self.in_form = True; self.found_form = True; return
        if not self.in_form: return
        if tag == "input":
            typ, name = a.get("type", "text").lower(), a.get("name")
            if not name or "disabled" in a or typ in ("submit", "button", "image", "reset"): return
            if typ in ("checkbox", "radio") and "checked" not in a: return
            self.fields.append((name, a.get("value", "")))
        elif tag == "select" and a.get("name"):
            self.select = {"name": a["name"], "options": []}
        elif tag == "option" and self.select is not None:
            self.select["options"].append((a.get("value", ""), "selected" in a))
        elif tag == "textarea" and a.get("name"):
            self.textarea = {"name": a["name"], "text": ""}
    def handle_data(self, data):
        if self.textarea is not None: self.textarea["text"] += data
    def handle_endtag(self, tag):
        if self.select is not None and tag == "select":
            opts = self.select["options"]
            if opts: self.fields.append((self.select["name"], next((v for v, sel in opts if sel), opts[0][0])))
            self.select = None
        if self.textarea is not None and tag == "textarea":
            self.fields.append((self.textarea["name"], self.textarea["text"])); self.textarea = None
        if tag == "form" and self.in_form: self.in_form = False

class SignupLockHTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        subprocess.run(["docker", "cp", str(PLUGIN), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-signup-lock.php"], check=True)
        try:
            cls.fx = php(SETUP)
        except Exception:
            subprocess.run(["docker", "exec", CONTAINER, "rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-signup-lock.php"], check=False)
            raise
        cls.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        with cls.op.open(BASE + "/wp-login.php", timeout=30): pass
        data = urllib.parse.urlencode({"log": LOGIN, "pwd": cls.fx["pw"], "wp-submit": "Log In", "redirect_to": BASE + "/wp-admin/", "testcookie": "1"}).encode()
        try:
            cls.op.open(urllib.request.Request(BASE + "/wp-login.php", data=data), timeout=30)
        except urllib.error.HTTPError as e:
            if e.code not in (302, 303): raise
    @classmethod
    def tearDownClass(cls):
        try:
            result = php(CLEANUP)
            if result["user_left"] or result["users_can_register"] != 0: raise AssertionError(f"cleanup failed: {result}")
        finally:
            subprocess.run(["docker", "exec", CONTAINER, "rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-signup-lock.php"], check=False)
    def request(self, method, path, body=b""):
        req = urllib.request.Request(BASE + path, data=body if method == "POST" else None, method=method)
        try:
            with self.op.open(req, timeout=30) as r: return r.status
        except urllib.error.HTTPError as e: return e.code
    def test_enable_query_denied_for_get_head_and_post(self):
        target = "/wp-admin/?tutor-hide-notice=registration&tutor-registration=enable"
        for method in ("GET", "HEAD", "POST"):
            with self.subTest(method=method):
                php("<?php require '/var/www/html/wp-load.php'; update_option('users_can_register', 0); echo json_encode(true);")
                self.assertEqual(self.request(method, target, b"marker=1"), 403)
                self.assertEqual(option_value(), 0, f"{method} changed registration")
    def test_real_settings_general_form_can_enable_and_disable(self):
        with self.op.open(BASE + "/wp-admin/options-general.php", timeout=30) as r:
            page = r.read().decode("utf-8", "replace"); self.assertEqual(r.status, 200)
        form = OptionsFormParser(); form.feed(page)
        self.assertTrue(form.found_form)
        self.assertTrue(any(k == "_wpnonce" for k, _ in form.fields))
        self.assertTrue(any(k == "option_page" and v == "general" for k, v in form.fields))
        def submit(enable):
            fields = [(k,v) for k,v in form.fields if k != "users_can_register"]
            if enable: fields.append(("users_can_register", "1"))
            req = urllib.request.Request(BASE + "/wp-admin/options.php", data=urllib.parse.urlencode(fields).encode(), method="POST")
            try:
                with self.op.open(req, timeout=30) as r: return r.status
            except urllib.error.HTTPError as e: return e.code
        self.assertIn(submit(True), (200, 302, 303)); self.assertEqual(option_value(), 1, "real Settings General form must enable")
        self.assertIn(submit(False), (200, 302, 303)); self.assertEqual(option_value(), 0, "real Settings General unchecked form must disable")
    def test_exact_notice_callback_removed(self):
        self.assertTrue(self.fx["manage"]); self.assertTrue(self.fx["notice_before"])
        self.assertFalse(self.fx["notice_after"]); self.assertTrue(self.fx["notice_markup_absent"])

if __name__ == "__main__": unittest.main(verbosity=2)
