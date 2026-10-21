#!/usr/bin/env python3
"""cas_content Customizer allowlist, runtime proof on the local site (issue #150, Route A, W1-2).

Uses the real admin-ajax customize_save path with the Customizer's own nonce and changeset:
an allowlisted setting is published live, a forbidden one is rejected server-side, and the
core sections are not offered to the role. Runs only with CAS_RUNTIME_TESTS=1, because the local
stack must have this branch's theme and the mu-plugin mounted, and the test changes local data.

    CAS_RUNTIME_TESTS=1 python3 tests/test_customizer_allowlist_runtime.py
"""
import json
import pathlib
import re
import sys
import unittest
import urllib.error
import urllib.parse
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from test_content_role_runtime import BASE, get, opener, php  # noqa: E402

LOGIN = "w1cascontent"


class CustomizerAllowlistRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        import os
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("set CAS_RUNTIME_TESTS=1 (changes the local site)")
        cls.fx = json.loads(php("""
$uid = username_exists('w1cascontent') ?: wp_create_user('w1cascontent', wp_generate_password(40), 'w1cascontent@example.test');
$pw = wp_generate_password(32, false); wp_set_password($pw, $uid);
(new WP_User($uid))->set_role('cas_content');
echo json_encode(array('uid' => $uid, 'pw' => $pw));
"""))
        cls.op = opener()

    @classmethod
    def tearDownClass(cls):
        php("""
require_once ABSPATH . 'wp-admin/includes/user.php';
$uid = username_exists('w1cascontent'); if ($uid) { wp_delete_user($uid); }
remove_theme_mod('cas_social_email');
echo 'cleaned';
""")

    def login(self):
        get(self.op, "wp-login.php")
        body = urllib.parse.urlencode({"log": LOGIN, "pwd": self.fx["pw"], "wp-submit": "Log In",
                                       "redirect_to": BASE + "/wp-admin/", "testcookie": "1"}).encode()
        try:
            self.op.open(urllib.request.Request(BASE + "/wp-login.php", data=body), timeout=30)
        except urllib.error.HTTPError:
            pass

    def customizer_state(self):
        status, body, _ = get(self.op, "wp-admin/customize.php")
        self.assertEqual(status, 200, "the Customizer must open for the role")
        html = body.decode("utf-8", "replace")
        start = html.index("_wpCustomizeSettings = ") + len("_wpCustomizeSettings = ")
        return json.loads(html[start:html.index(";\n", start)])

    def save(self, state, customized, publish=True):
        form = {
            "action": "customize_save", "wp_customize": "on", "nonce": state["nonce"]["save"],
            "customize_theme": "hello-elementor-child", "customized": json.dumps(customized),
        }
        if state.get("changeset", {}).get("uuid"):
            form["customize_changeset_uuid"] = state["changeset"]["uuid"]
        if publish:
            form["customize_changeset_status"] = "publish"
        try:
            with self.op.open(urllib.request.Request(BASE + "/wp-admin/admin-ajax.php",
                                                     data=urllib.parse.urlencode(form).encode()), timeout=60) as r:
                return json.loads(r.read().decode("utf-8", "replace"))
        except urllib.error.HTTPError as e:
            return {"http": e.code, "body": e.read().decode("utf-8", "replace")[:200]}

    def test_allowed_setting_is_published_live(self):
        self.login()
        state = self.customizer_state()
        reply = self.save(state, {"cas_social_email": "w1-live@example.test"})
        self.assertTrue(reply.get("success"), f"save must succeed: {reply}")
        self.assertTrue(reply["data"]["setting_validities"]["cas_social_email"] is True, reply)
        self.assertEqual(php("echo get_theme_mod('cas_social_email');").strip(), "w1-live@example.test",
                         "the allowed setting must be live (stored as a theme mod), not only in a draft")

    def test_forbidden_setting_is_rejected_server_side(self):
        self.login()
        before = php("echo get_option('blogname');")
        state = self.customizer_state()
        reply = self.save(state, {"blogname": "W1 hacked"})
        validity = (reply.get("data") or {}).get("setting_validities", {}).get("blogname", {})
        self.assertIn("unauthorized", validity, f"blogname must be refused by capability: {reply}")
        self.assertEqual(php("echo get_option('blogname');"), before, "blogname must not change")

    def test_core_sections_are_not_offered_to_the_role(self):
        self.login()
        state = self.customizer_state()
        html_sections = set(state.get("sections", {}).keys()) if isinstance(state.get("sections"), dict) else set()
        for hidden in ["title_tagline", "nav_menus", "widgets", "static_front_page", "custom_css"]:
            self.assertNotIn(hidden, html_sections, f"{hidden} must not be offered to the role")
        self.assertIn("cas_hero", html_sections, "an allowed section must be offered")


if __name__ == "__main__":
    unittest.main(verbosity=2)
