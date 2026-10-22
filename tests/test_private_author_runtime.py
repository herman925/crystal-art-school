#!/usr/bin/env python3
"""PREPARED for actual Review execution on its one isolated synthetic stack; not run by Development.

Requires CAS_RUNTIME_TESTS=1, CAS_REVIEW_CONTAINER (no default/old-stack selection), and
CAS_PRIVATE_FIXTURE_JSON containing the protected output of cas-private-author-fixture.php.
No setup, stack start, image pull, production import or broad cleanup is performed here.
"""
import base64
import copy
import hashlib
import http.cookiejar
import json
import os
import pathlib
import subprocess
import unittest
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class PrivateAuthorRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("Review must explicitly enable local runtime tests")
        cls.container = os.environ["CAS_REVIEW_CONTAINER"]
        cls.fx = json.loads(pathlib.Path(os.environ["CAS_PRIVATE_FIXTURE_JSON"]).read_text())
        cls.base = cls.fx["files"]["notes"]["url"].split("/?")[0]
        if urllib.parse.urlparse(cls.base).hostname not in ("127.0.0.1", "localhost"):
            raise RuntimeError("Only the isolated localhost fixture is allowed")
        cls.admin = cls.fx["admin"]
        cls.owner = cls.fx["users"]["w1privateowner"]["id"]
        cls.other = cls.fx["users"]["w1privateother"]["id"]
        current = cls.php(cls.admin, "return hash_file('sha256', get_stylesheet_directory().'/inc/cas-private-files.php');")
        if current != cls.fx["module_sha256"]:
            raise RuntimeError("Loaded module differs from the captured fixture revision")

    @classmethod
    def php(cls, uid, body):
        script = """<?php
require '/var/www/html/wp-load.php';
wp_set_current_user(%d);
add_filter('wp_die_handler', function () { return function ($message, $title, $args) {
  throw new RuntimeException((string)($args['response'] ?? 0).'|'.$message);
}; });
try { $run = function () { %s }; echo wp_json_encode($run()); }
catch (RuntimeException $error) { echo wp_json_encode(array('denied'=>$error->getMessage())); }
""" % (int(uid), body)
        result = subprocess.run(["docker", "exec", "-i", "-u", "www-data", cls.container, "php"],
                                input=script, capture_output=True, text=True, timeout=60)
        if result.returncode:
            raise AssertionError("Synthetic PHP probe failed: " + result.stderr)
        return json.loads(result.stdout)

    def download(self, name, file_name):
        op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect)
        if name:
            user = self.fx["users"][name]
            op.open(self.base + "/wp-login.php", timeout=30).read()
            fields = urllib.parse.urlencode({"log": name, "pwd": user["password"], "testcookie": "1", "redirect_to": self.base + "/wp-admin/"}).encode()
            try:
                op.open(urllib.request.Request(self.base + "/wp-login.php", data=fields), timeout=30).read()
            except urllib.error.HTTPError as error:
                self.assertEqual(error.code, 302)
        try:
            with op.open(self.fx["files"][file_name]["url"], timeout=30) as result:
                return result.status, result.read()
        except urllib.error.HTTPError as error:
            return error.code, error.read()

    def test_01_current_author_can_read_course_and_lesson_reference_without_enrolment_or_instructor_meta(self):
        self.assertTrue(self.fx["owner_has_no_instructor_meta"])
        for name in ("notes", "reference"):
            status, body = self.download("w1privateowner", name)
            self.assertEqual(status, 200)
            self.assertEqual(hashlib.sha256(body).hexdigest(), self.fx["files"][name]["sha256"])

    def test_02_guest_student_editor_instructor_and_other_author_denied(self):
        for actor in (None, "w1privateoutsider", "w1privateeditor", "w1privateinstructor", "w1privateother"):
            for name in ("notes", "reference"):
                with self.subTest(actor=actor, file=name):
                    status, body = self.download(actor, name)
                    self.assertEqual(status, 403)
                    self.assertEqual(body, b"")
        self.assertEqual(self.download("w1privatestudent", "notes")[0], 200)
        self.assertEqual(self.download("w1privateowner", "owned_foreign")[0], 403, "attachment author is not course authority")
        self.assertEqual(self.download("w1privateother", "foreign")[0], 200)

    def test_03_guessed_private_assignment_and_by_mid_write_fail_before_mutation(self):
        course = self.fx["courses"]["own"]
        foreign = self.fx["files"]["foreign"]["id"]
        before = self.php(self.admin, f"return array(get_post_meta({course},'_tutor_attachments',true),get_post_meta({course},CAS_PRIVATE_META_REFS,true),get_post_meta({foreign},CAS_PRIVATE_META_COURSES,true));")
        for operation in (f"update_post_meta({course},'_tutor_attachments',array({foreign}));",
                          f"global $wpdb; $mid=$wpdb->get_var(\"SELECT meta_id FROM {{$wpdb->postmeta}} WHERE post_id={course} AND meta_key='_tutor_attachments'\"); update_metadata_by_mid('post',$mid,array({foreign}));"):
            denied = self.php(self.owner, operation + " return false;")
            self.assertIn("403|", denied["denied"])
            after = self.php(self.admin, f"return array(get_post_meta({course},'_tutor_attachments',true),get_post_meta({course},CAS_PRIVATE_META_REFS,true),get_post_meta({foreign},CAS_PRIVATE_META_COURSES,true));")
            self.assertEqual(after, before)
        self.assertEqual(self.download("w1privateowner", "foreign")[0], 403)

    def test_04_stale_mapping_and_attachment_owner_do_not_grant_authority(self):
        self.assertEqual(self.download("w1privateowner", "detached")[0], 403)
        # Preserve the pre-existing enrolled mapping behaviour, deliberately unchanged by this patch.
        self.assertEqual(self.download("w1privatestudent", "detached")[0], 200)

    def test_05_detach_revokes_author_access_and_admin_restore_is_explicit(self):
        lesson = self.fx["lesson"]
        image = self.fx["files"]["reference"]["id"]
        try:
            self.php(self.owner, f"update_post_meta({lesson},'_tutor_attachments',array()); return true;")
            self.assertEqual(self.download("w1privateowner", "reference")[0], 403)
        finally:
            self.php(self.admin, f"update_post_meta({lesson},'_tutor_attachments',array({image})); return true;")

    def test_06_takeover_reparent_and_security_binding_tampering_denied(self):
        other_course = self.fx["courses"]["other"]
        course = self.fx["courses"]["own"]
        lesson = self.fx["lesson"]
        foreign_topic = self.fx["foreign_topic"]
        attempts = [f"wp_update_post(array('ID'=>{other_course},'post_author'=>{self.owner}));",
                    f"wp_update_post(array('ID'=>{lesson},'post_parent'=>{foreign_topic}));",
                    f"update_post_meta({course},CAS_PRIVATE_META_REFS,array());",
                    f"delete_post_meta({course},CAS_PRIVATE_META_REFS);",
                    f"update_post_meta({self.fx['files']['owned_foreign']['id']},CAS_PRIVATE_META_FLAG,0);",
                    f"update_post_meta({self.fx['files']['fresh_upload']['id']},'_wp_attached_file',get_attached_file({self.fx['files']['foreign']['id']}));",
                    f"update_post_meta({self.fx['files']['fresh_upload']['id']},'_wp_attachment_metadata',array('sizes'=>array('bad'=>array('file'=>'../../foreign.pdf'))));"]
        for operation in attempts:
            self.assertIn("403|", self.php(self.owner, operation + " return false;")["denied"])
        self.assertEqual(self.php(self.admin, f"return (int)get_post({lesson})->post_parent;"), self.fx["topic"])

    def test_07_manifest_requires_reviewed_file_graph_list_preimages_and_supports_noop_and_rollback(self):
        encoded = base64.b64encode(json.dumps(self.fx["legacy_manifest"]).encode()).decode()
        call = f"$m=json_decode(base64_decode('{encoded}'),true); "
        self.assertEqual(self.download("w1privateowner", "legacy")[0], 403, "current but untrusted reference is not authority")
        self.assertFalse(self.php(self.owner, call + "return cas_private_apply_reference_manifest($m);"))
        altered = copy.deepcopy(self.fx["legacy_manifest"])
        altered["chain"][0]["post_author"] = "999999"
        bad = base64.b64encode(json.dumps(altered).encode()).decode()
        self.assertFalse(self.php(self.admin, f"$m=json_decode(base64_decode('{bad}'),true); return cas_private_apply_reference_manifest($m);"))
        # A real file change after inventory must refuse stamping, then restore only these synthetic bytes.
        file_id = self.fx["files"]["legacy"]["id"]
        self.assertTrue(self.php(self.admin, call + f"$path=get_attached_file({file_id}); $bytes=file_get_contents($path); try {{ file_put_contents($path,'stale-manifest'); return false===cas_private_apply_reference_manifest($m); }} finally {{ file_put_contents($path,$bytes); }}"))
        try:
            self.assertTrue(self.php(self.admin, call + "return cas_private_apply_reference_manifest($m);"))
            source = self.fx["courses"]["legacy"]
            first = self.php(self.admin, f"return cas_private_reference_snapshot({source})['binding'];")
            self.assertTrue(self.php(self.admin, call + "return cas_private_apply_reference_manifest($m);"))
            self.assertEqual(self.php(self.admin, f"return cas_private_reference_snapshot({source})['binding'];"), first)
            self.assertEqual(self.download("w1privateowner", "legacy")[0], 200)
        finally:
            self.assertTrue(self.php(self.admin, call + "return cas_private_apply_reference_manifest($m,true);"))
        self.assertEqual(self.download("w1privateowner", "legacy")[0], 403)

    def test_08_fresh_public_upload_can_be_assigned_without_using_attachment_owner_as_download_authority(self):
        course = self.fx["courses"]["own"]
        fresh = self.fx["files"]["fresh_upload"]["id"]
        notes = self.fx["files"]["notes"]["id"]
        try:
            self.assertTrue(self.php(self.owner, f"return update_post_meta({course},'_tutor_attachments',array({notes},{fresh}));"))
            self.assertEqual(self.download("w1privateowner", "fresh_upload")[0], 200)
        finally:
            self.php(self.admin, f"update_post_meta({course},'_tutor_attachments',array({notes})); return true;")
    def test_09_internal_security_window_closes_on_exception(self):
        self.assertTrue(self.php(self.owner, "try { cas_private_internal_write(function () { throw new RuntimeException('injected'); }); } catch (RuntimeException $error) {} return false===cas_private_internal_write();"))
    def test_10_missing_custom_cap_or_unapproved_role_cannot_assign_or_read(self):
        notes = self.fx["files"]["notes"]["id"]
        course = self.fx["courses"]["own"]
        strip_cap = "add_filter('user_has_cap', function ($caps) { $caps['manage_cas_content']=false; return $caps; }); "
        self.assertFalse(self.php(self.owner, strip_cap + f"return cas_private_can_download({notes});"))
        self.assertIn("403|", self.php(self.owner, strip_cap + f"update_post_meta({course},'_tutor_attachments',array()); return false;")["denied"])
        for actor in ("w1privateeditor", "w1privateinstructor", "w1privateoutsider"):
            self.assertIn("403|", self.php(self.fx["users"][actor]["id"], f"update_post_meta({course},'_tutor_attachments',array({notes})); return false;")["denied"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
