#!/usr/bin/env python3
"""PREPARED for actual Review, not executed by Development.
Requires CAS_RUNTIME_TESTS=1, CAS_REVIEW_CONTAINER and CAS_PREVIEW_FIXTURE_JSON.
No stack launch/mount/build/pull, production action or fixture creation occurs in this runner.
"""
import http.cookiejar
import json
import os
from pathlib import Path
import subprocess
import unittest
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class AuthorPreviewRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("actual Review must explicitly enable synthetic runtime tests")
        cls.container = os.environ["CAS_REVIEW_CONTAINER"]
        cls.fx = json.loads(Path(os.environ["CAS_PREVIEW_FIXTURE_JSON"]).read_text())
        cls.base = cls.fx["home"]
        if urllib.parse.urlparse(cls.base).hostname not in ("localhost", "127.0.0.1"):
            raise RuntimeError("Refusing a non-local fixture")
        cls.admin = cls.fx["admin"]
        cls.owner = cls.fx["users"]["w1previewowner"]["id"]
        cls.other = cls.fx["users"]["w1previewother"]["id"]
        cls.sessions = {}
        actual = cls.php(cls.admin, "return array('cas-content-role'=>hash_file('sha256',get_stylesheet_directory().'/inc/cas-content-role.php'),'cas-lesson'=>hash_file('sha256',get_stylesheet_directory().'/inc/cas-lesson.php'),'single-lesson'=>hash_file('sha256',get_stylesheet_directory().'/single-lesson.php'));")
        if any(actual[k] != cls.fx["source_hashes"][k] for k in actual):
            raise RuntimeError("Loaded source files differ from fixture capture")

    @classmethod
    def php(cls, actor, body):
        code = "<?php\nrequire '/var/www/html/wp-load.php';\nwp_set_current_user(%d);\n$probe=function(){ %s }; echo wp_json_encode($probe());" % (int(actor), body)
        result = subprocess.run(["docker", "exec", "-i", "-u", "www-data", cls.container, "php"], input=code, text=True, capture_output=True, timeout=60)
        if result.returncode:
            raise AssertionError("Synthetic PHP probe failed: " + result.stderr)
        return json.loads(result.stdout)

    def session(self, actor):
        if actor not in self.sessions:
            op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect)
            if actor:
                op.open(self.base + "/wp-login.php", timeout=30).read()
                fields = {"log": actor, "pwd": self.fx["users"][actor]["password"], "testcookie": "1", "redirect_to": self.base + "/wp-admin/"}
                try:
                    op.open(urllib.request.Request(self.base + "/wp-login.php", data=urllib.parse.urlencode(fields).encode()), timeout=30).read()
                except urllib.error.HTTPError as error:
                    self.assertEqual(error.code, 302)
            self.sessions[actor] = op
        return self.sessions[actor]

    def get(self, actor, url):
        self.assertEqual(urllib.parse.urlparse(url).netloc, urllib.parse.urlparse(self.base).netloc)
        try:
            with self.session(actor).open(url, timeout=30) as result:
                return result.status, result.read().decode("utf-8", "replace"), result.headers
        except urllib.error.HTTPError as error:
            return error.code, error.read().decode("utf-8", "replace"), error.headers

    def immutable_state(self):
        ids = ",".join(str(int(u["id"])) for u in self.fx["users"].values())
        posts = ",".join(str(int(i)) for i in self.fx["post_ids"])
        return self.php(self.admin, f"""
global $wpdb;
$enrol=$wpdb->get_results("SELECT ID,post_parent,post_author,post_status FROM {{$wpdb->posts}} WHERE post_type='tutor_enrolled' AND post_author IN ({ids}) ORDER BY ID",ARRAY_A);
$flags=$wpdb->get_results("SELECT post_id,meta_key,meta_value FROM {{$wpdb->postmeta}} WHERE post_id IN ({posts}) AND meta_key IN ('_is_preview','_tutor_is_public_course','_tutor_course_price_type','tutor_course_price','tutor_course_sale_price') ORDER BY post_id,meta_key",ARRAY_A);
$table=$wpdb->prefix.'tutor_orders';
$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
$orders=$exists ? $wpdb->get_results("SELECT id,user_id,total_price,order_status FROM $table WHERE user_id IN ({ids}) ORDER BY id",ARRAY_A) : null;
$completed=$wpdb->get_results("SELECT comment_ID,comment_post_ID,user_id,comment_type FROM {{$wpdb->comments}} WHERE user_id IN ({ids}) AND comment_type='course_completed' ORDER BY comment_ID",ARRAY_A);
$lesson_progress=$wpdb->get_results($wpdb->prepare("SELECT user_id,meta_key,meta_value FROM {{$wpdb->usermeta}} WHERE user_id IN ({ids}) AND meta_key LIKE %s ORDER BY user_id,meta_key",$wpdb->esc_like('_tutor_completed_').'%'),ARRAY_A);
return array('enrol'=>$enrol,'flags'=>$flags,'orders'=>$orders,'completed'=>$completed,'lesson_progress'=>$lesson_progress);
""")

    def test_target_user_object_edit_caps_never_use_ambient_user_or_global_primitive(self):
        own = self.fx["lessons"]["protected"]["id"]
        draft = self.fx["lessons"]["draft"]["id"]
        foreign = self.fx["lessons"]["foreign"]["id"]
        broken = self.fx["lessons"]["broken"]["id"]
        for ambient in (0, self.admin, self.owner, self.other):
            result = self.php(ambient, f"return array(user_can({self.owner},'edit_post',{own}),user_can({self.owner},'edit_tutor_lesson',{own}),user_can({self.owner},'edit_post',{draft}),user_can({self.owner},'edit_post',{foreign}),user_can({self.other},'edit_post',{own}),user_can({self.other},'edit_post',{foreign}),user_can({self.owner},'edit_tutor_lesson'),user_can({self.owner},'edit_tutor_lesson',0),user_can({self.owner},'edit_tutor_lesson',{self.fx['closed_page']}),user_can({self.owner},'edit_post',{broken}),get_role('cas_content')->has_cap('edit_tutor_lesson')); ")
            self.assertEqual(result, [True, True, True, False, False, True, False, False, False, False, False])

    def test_author_exception_does_not_globally_grant_native_tutor_access(self):
        lesson = self.fx["lessons"]["protected"]["id"]
        course = self.fx["courses"]["own"]
        result = self.php(self.owner, f"return array(cas_lesson_author_course({lesson}),\\Tutor\\Models\\CourseModel::has_course_content_access(array('current_post_type'=>'lesson','current_post_id'=>{lesson},'course_id'=>{course},'is_public'=>false,'is_enrolled'=>false))); ")
        self.assertEqual(result, [course, False])

    def test_missing_custom_or_real_course_edit_cap_fails_for_checked_target(self):
        lesson = self.fx["lessons"]["protected"]["id"]
        for cap in ("manage_cas_content", "edit_published_posts"):
            result = self.php(self.admin, f"add_filter('user_has_cap',function($all,$caps,$args,$user){{ if((int)$user->ID==={self.owner}){{$all['{cap}']=false;}} return $all; }},100,4); return array(cas_content_author_lesson_course({lesson},{self.owner}),user_can({self.owner},'edit_post',{lesson}));")
            self.assertEqual(result, [0, False])

    def test_non_enrolled_owner_gets_published_nonpreview_and_real_draft_preview_privately(self):
        self.assertFalse(self.fx["owner_enrolled"])
        self.assertFalse(self.fx["owner_instructor_meta"])
        before = self.immutable_state()
        for name in ("protected", "draft"):
            lesson = self.fx["lessons"][name]
            url = lesson["preview_url"] if name == "draft" else lesson["url"]
            status, body, headers = self.get("w1previewowner", url)
            self.assertEqual(status, 200)
            self.assertIn(lesson["marker"], body)
            self.assertIn("class=\"lesson-cols\"", body)
            if name == "draft":
                self.assertIn("W1 author lesson draft", body, "draft must be the current outline item, not the first published lesson")
            cache = headers.get("Cache-Control", "").lower()
            self.assertIn("private", cache)
            self.assertIn("no-store", cache)
            self.assertIn("cookie", ",".join(headers.get_all("Vary") or []).lower())
        self.assertEqual(self.immutable_state(), before, "viewing must not alter enrolment, orders or public/free/preview/price flags")

    def test_guest_other_author_student_without_enrolment_editor_and_metadata_only_instructor_get_no_new_stage(self):
        for actor in (None, "w1previewother", "w1previewoutsider", "w1previeweditor", "w1previewinstructor"):
            status, body, _ = self.get(actor, self.fx["lessons"]["protected"]["url"])
            self.assertIn(status, (200, 302, 403, 404))
            self.assertNotIn(self.fx["lessons"]["protected"]["marker"], body)
        for actor in (None, "w1previewother", "w1previewoutsider"):
            _, body, _ = self.get(actor, self.fx["lessons"]["draft"]["preview_url"])
            self.assertNotIn(self.fx["lessons"]["draft"]["marker"], body)
            self.assertNotIn("W1 author lesson draft", body)
            self.assertNotIn("W1_AUTHOR_CONTENT_LESSON_DRAFT", body)
        foreign = self.fx["lessons"]["foreign"]
        forged = foreign["query_url"] + "&course_id=" + str(self.fx["courses"]["own"])
        _, body, _ = self.get("w1previewowner", forged)
        self.assertNotIn(foreign["marker"], body, "request course ID / lesson author must not supply authority")

    def test_enrolled_and_guest_public_preview_paths_are_preserved_without_guest_files(self):
        before = self.immutable_state()
        status, body, _ = self.get("w1previewstudent", self.fx["lessons"]["protected"]["url"])
        self.assertEqual(status, 200)
        self.assertIn(self.fx["lessons"]["protected"]["marker"], body)
        status, body, _ = self.get(None, self.fx["lessons"]["public_preview"]["url"])
        self.assertEqual(status, 200)
        self.assertIn(self.fx["lessons"]["public_preview"]["marker"], body)
        self.assertNotIn(self.fx["files"]["notes"]["title"], body)
        self.assertNotIn(self.fx["files"]["reference"]["title"], body)
        self.assertEqual(self.get(None, self.fx["files"]["notes"]["url"])[0], 403)
        self.assertEqual(self.immutable_state(), before)

    def test_materials_require_separate_private_provenance_handler(self):
        if not self.fx["private_dependency"]:
            _, body, _ = self.get("w1previewowner", self.fx["lessons"]["protected"]["url"])
            self.assertNotIn(self.fx["files"]["notes"]["title"], body)
            self.assertNotIn(self.fx["files"]["reference"]["title"], body)
            self.skipTest("#182 absent: fail-closed hiding checked; combined author materials/direct-file acceptance still REQUIRED")
        _, body, _ = self.get("w1previewowner", self.fx["lessons"]["protected"]["url"])
        self.assertIn(self.fx["files"]["notes"]["title"], body)
        self.assertIn('id="ref-card"', body)
        self.assertEqual(self.get("w1previewowner", self.fx["files"]["notes"]["url"])[0], 200)
        self.assertEqual(self.get("w1previewowner", self.fx["files"]["reference"]["url"])[0], 200)
        self.assertEqual(self.get("w1previewowner", self.fx["files"]["foreign"]["url"])[0], 403)

    def test_trashed_lesson_topic_or_course_breaks_author_permission(self):
        lesson = self.fx["lessons"]["protected"]["id"]
        for node in (lesson, self.fx["topics"]["own"], self.fx["courses"]["own"]):
            old = self.php(self.admin, f"return get_post_status({node});")
            try:
                self.php(self.admin, f"wp_update_post(array('ID'=>{node},'post_status'=>'trash')); return true;")
                self.assertEqual(self.php(self.admin, f"return array(cas_content_author_lesson_course({lesson},{self.owner}),user_can({self.owner},'edit_post',{lesson}));"), [0, False])
            finally:
                self.php(self.admin, f"wp_update_post(array('ID'=>{node},'post_status'=>'{old}')); return true;")


if __name__ == "__main__":
    unittest.main(verbosity=2)
