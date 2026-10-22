#!/usr/bin/env python3
"""PREPARED ONLY. Actual Review runs this against its isolated synthetic stack, never Development.
Requires CAS_RUNTIME_TESTS=1, CAS_REVIEW_CONTAINER and CAS_SORT_FIXTURE_JSON from the focused fixture.
No stack launch/build/pull, environment replacement, production access or automatic fixture creation.
"""
import base64
import copy
import http.cookiejar
import json
import os
from pathlib import Path
import re
import subprocess
import unittest
import urllib.error
import urllib.parse
import urllib.request

FIXTURE = Path(__file__).resolve().parent / "fixtures/cas-builder-sort-fixture.php"


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class BuilderSortRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("actual Review must enable the isolated synthetic tests")
        cls.container = os.environ["CAS_REVIEW_CONTAINER"]
        cls.fx = json.loads(Path(os.environ["CAS_SORT_FIXTURE_JSON"]).read_text())
        cls.base = cls.fx["home"]
        if urllib.parse.urlparse(cls.base).hostname not in ("localhost", "127.0.0.1"):
            raise RuntimeError("Refusing a non-local fixture")
        cls.owner = cls.fx["users"]["w1sortowner"]["id"]
        cls.admin = cls.fx["admin"]
        actual = cls.php(cls.admin, "return hash_file('sha256',get_stylesheet_directory().'/inc/cas-content-builder.php');")
        if actual != cls.fx["module_sha256"]:
            raise RuntimeError("Loaded source differs from fixture source")
        cls.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect)
        cls.op.open(cls.base + "/wp-login.php", timeout=30).read()
        fields = {"log": "w1sortowner", "pwd": cls.fx["users"]["w1sortowner"]["password"], "testcookie": "1", "redirect_to": cls.base + "/wp-admin/"}
        try:
            cls.op.open(urllib.request.Request(cls.base + "/wp-login.php", data=urllib.parse.urlencode(fields).encode()), timeout=30).read()
        except urllib.error.HTTPError as error:
            if error.code != 302:
                raise
        hub = cls.op.open(cls.base + "/wp-admin/admin.php?page=cas-content-hub", timeout=30).read().decode()
        form = re.search(r'<form id="cas-builder-add-course".*?</form>', hub, re.S)
        nonce = re.search(r'name="' + re.escape(cls.fx["nonce_field"]) + r'" value="([^"]+)"', form.group(0) if form else "")
        if not nonce:
            raise RuntimeError("No real session-bound Tutor nonce in the hub form")
        cls.nonce = nonce.group(1)

    @classmethod
    def php(cls, uid, body):
        code = "<?php\nrequire '/var/www/html/wp-load.php';\nwp_set_current_user(%d);\n$probe=function(){ %s }; echo wp_json_encode($probe());" % (int(uid), body)
        result = subprocess.run(["docker", "exec", "-i", "-u", "www-data", cls.container, "php"], input=code, capture_output=True, text=True, timeout=60)
        if result.returncode:
            raise AssertionError("Synthetic PHP failed: " + result.stderr)
        return json.loads(result.stdout)

    def setUp(self):
        source = base64.b64encode(FIXTURE.read_bytes()).decode()
        base = base64.b64encode(self.base.encode()).decode()
        self.php(self.admin, f"putenv('CAS_RUNTIME_TESTS=1'); putenv('CAS_SORT_FIXTURE_HOME='.base64_decode('{base}')); $args=array('reset'); ob_start(); eval('?>'.base64_decode('{source}')); $out=ob_get_clean(); return json_decode($out,true);")

    def snapshot(self):
        ids = ",".join(str(int(i)) for i in self.fx["post_ids"])
        return self.php(self.admin, f"global $wpdb; return $wpdb->get_results(\"SELECT ID,post_type,post_parent,menu_order,post_status,post_author,post_title,post_content FROM {{$wpdb->posts}} WHERE ID IN ({ids}) ORDER BY ID\",ARRAY_A);")

    def matrix(self, *, indexed=False):
        rows = []
        ids = self.fx["lessons"]
        for name, lessons in (("a", [ids["a1"], ids["a2"]]), ("b", [ids["b1"]]), ("empty", [])):
            rows.append({"topic_id": str(self.fx["topics"][name]) if indexed else self.fx["topics"][name],
                         "lesson_ids": {str(i): str(v) for i, v in enumerate(lessons)} if indexed else lessons})
        return {str(i): row for i, row in enumerate(rows)} if indexed else rows

    def ajax(self, fields):
        try:
            with self.op.open(urllib.request.Request(self.base + "/wp-admin/admin-ajax.php", data=urllib.parse.urlencode(fields).encode()), timeout=30) as result:
                return result.status, json.loads(result.read())
        except urllib.error.HTTPError as error:
            return error.code, json.loads(error.read())

    def post(self, raw, *, course_save=None, extra=None):
        fields = {"action": "tutor_update_course_content_order", self.fx["nonce_field"]: self.nonce, "tutor_topics_lessons_sorting": raw}
        if course_save is not None:
            own = course_save == self.fx["courses"]["own"]
            fields.update({"action": "tutor_update_course", "course_id": course_save, "post_title": "W1 sort must not partially save", "post_content": "W1 sort body must not partially save", "post_status": "publish" if own else "draft",
                           "course_level": "beginner", "pricing[type]": "paid", "course_price": "40" if own else "50", "course_sale_price": "31" if own else "0", "enable_qna": "no", "is_public_course": "no"})
        if extra:
            fields.update(extra)
        return self.ajax(fields)

    def reject_without_mutation(self, payload, status=400, *, course_save=None, raw=False):
        before = self.snapshot()
        got, response = self.post(payload if raw else json.dumps(payload), course_save=course_save)
        self.assertEqual(got, status, response)
        self.assertFalse(response["success"])
        self.assertEqual(self.snapshot(), before, "all parent/order/status/author/title/body rows must be unchanged")

    def test_missing_and_substituted_or_unknown_keys(self):
        for row in ({"topic_id": self.fx["topics"]["a"]},
                    {"topic_id": self.fx["topics"]["a"], "contents": [{"id": self.fx["lessons"]["a1"], "type": "lesson"}]},
                    {"topic_id": self.fx["topics"]["a"], "lesson_ids": [], "extra": True},
                    {"lesson_ids": []}):
            matrix = self.matrix()
            matrix[0] = row
            with self.subTest(row=row):
                self.reject_without_mutation(matrix)

    def test_malformed_json_root_row_and_id_types(self):
        for raw in ('[', 'null', 'true', '"text"', '{"topic_id":1,"lesson_ids":[]}', '{"1":{}}'):
            self.reject_without_mutation(raw, raw=True)
        for value in (None, False, "1", {"1": self.fx["lessons"]["a1"]}):
            matrix = self.matrix()
            matrix[0]["lesson_ids"] = value
            self.reject_without_mutation(matrix)
        for value in (False, 0, -1, 1.5, "1e3", "01", " 1 ", [], {}):
            matrix = self.matrix()
            matrix[0]["topic_id"] = value
            self.reject_without_mutation(matrix)
            matrix = self.matrix()
            matrix[0]["lesson_ids"] = [value]
            self.reject_without_mutation(matrix)

    def test_duplicates_missing_topics_and_foreign_parent_chain(self):
        matrix = self.matrix()
        self.reject_without_mutation(matrix + [copy.deepcopy(matrix[0])])
        matrix[0]["lesson_ids"].append(matrix[0]["lesson_ids"][0])
        self.reject_without_mutation(matrix)
        matrix = self.matrix()
        matrix[1]["lesson_ids"].append(matrix[0]["lesson_ids"][0])
        self.reject_without_mutation(matrix)
        self.reject_without_mutation(self.matrix()[:1], 409)
        matrix = self.matrix()
        matrix[1]["topic_id"] = self.fx["topics"]["foreign"]
        self.reject_without_mutation(matrix, 409)
        matrix = self.matrix()
        matrix[0]["lesson_ids"][0] = self.fx["lessons"]["foreign"]
        self.reject_without_mutation(matrix, 409)

    def test_later_invalid_row_has_no_partial_mutation_on_either_entrypoint(self):
        self.reject_without_mutation([{"topic_id": self.fx["topics"]["a"]}], course_save=self.fx["courses"]["own"])
        matrix = self.matrix()
        matrix[0]["lesson_ids"].reverse()
        del matrix[1]["lesson_ids"]
        self.reject_without_mutation(matrix)
        self.reject_without_mutation(matrix, course_save=self.fx["courses"]["own"])

    def test_explicit_empty_is_a_conflict_if_it_would_detach_existing_items(self):
        for indexed in (False, True):
            matrix = self.matrix(indexed=indexed)
            matrix["0" if indexed else 0]["lesson_ids"] = {} if indexed else []
            self.reject_without_mutation(matrix, 409)

    def test_valid_array_and_native_indexed_object_preserve_all_content(self):
        for indexed in (False, True):
            before = {int(row["ID"]): row for row in self.snapshot()}
            matrix = self.matrix(indexed=indexed)
            row = matrix["0" if indexed else 0]
            row["lesson_ids"] = {"0": str(self.fx["lessons"]["a2"]), "1": str(self.fx["lessons"]["a1"])} if indexed else list(reversed(row["lesson_ids"]))
            status, response = self.post(json.dumps(matrix))
            self.assertEqual(status, 200, response)
            after = {int(row["ID"]): row for row in self.snapshot()}
            for uid, old in before.items():
                for field in ("post_parent", "post_status", "post_author"):
                    self.assertEqual(after[uid][field], old[field])
            self.assertEqual(int(after[self.fx["lessons"]["a2"]]["menu_order"]), 0)
            self.assertEqual(int(after[self.fx["lessons"]["a1"]]["menu_order"]), 1)

    def test_valid_published_course_save_can_sort_after_complete_preflight(self):
        matrix = self.matrix()
        matrix[0]["lesson_ids"].reverse()
        status, response = self.post(json.dumps(matrix), course_save=self.fx["courses"]["own"])
        self.assertEqual(status, 200, response)
        after = {int(row["ID"]): row for row in self.snapshot()}
        course = after[self.fx["courses"]["own"]]
        self.assertEqual(course["post_status"], "publish")
        self.assertEqual(int(course["post_author"]), self.owner)
        self.assertEqual(course["post_title"], "W1 sort must not partially save")
        self.assertEqual(int(after[self.fx["lessons"]["a1"]]["post_parent"]), self.fx["topics"]["a"])
        self.assertEqual(int(after[self.fx["lessons"]["a2"]]["menu_order"]), 0)

    def test_native_builder_lists_mixed_statuses_and_both_sort_entrypoints_preserve_them(self):
        status, response = self.ajax({"action": "tutor_course_contents", self.fx["nonce_field"]: self.nonce, "course_id": self.fx["courses"]["own"]})
        self.assertEqual(status, 200, response)
        native = {int(item["ID"]): item["post_status"] for row in response["data"] for item in row["contents"]}
        expected = {self.fx["lessons"][name]: self.fx["lesson_statuses"][name] for name in ("a1", "a2", "b1")}
        self.assertEqual(native, expected, "use the REAL current actor's native list, not a test status override")
        for action in (None, self.fx["courses"]["own"]):
            for indexed in (False, True):
                self.setUp()
                before = {int(row["ID"]): row for row in self.snapshot()}
                matrix = self.matrix(indexed=indexed)
                values = [self.fx["lessons"]["a2"], self.fx["lessons"]["a1"]]
                matrix["0" if indexed else 0]["lesson_ids"] = {str(i): str(v) for i, v in enumerate(values)} if indexed else values
                status, response = self.post(json.dumps(matrix), course_save=action)
                self.assertEqual(status, 200, response)
                after = {int(row["ID"]): row for row in self.snapshot()}
                for uid, old in before.items():
                    for field in ("post_parent", "post_status", "post_author"):
                        self.assertEqual(after[uid][field], old[field])
                self.assertEqual(after[self.fx["lessons"]["a2"]]["post_status"], "draft")

    def test_trash_and_unsupported_children_remain_distinct_conflicts_without_mutation(self):
        for node in (self.fx["lessons"]["trash"], self.fx["unsupported"]):
            target = self.fx["topics"]["a"]
            try:
                self.php(self.admin, f"global $wpdb; $wpdb->update($wpdb->posts,array('post_parent'=>{target}),array('ID'=>{node})); clean_post_cache({node}); return true;")
                for action in (None, self.fx["courses"]["own"]):
                    self.reject_without_mutation(self.matrix(), 409, course_save=action)
                    matrix = self.matrix()
                    matrix[0]["lesson_ids"].append(node)
                    self.reject_without_mutation(matrix, 409, course_save=action)
            finally:
                original = self.fx["topics"]["foreign"]
                self.php(self.admin, f"global $wpdb; $wpdb->update($wpdb->posts,array('post_parent'=>{original}),array('ID'=>{node})); clean_post_cache({node}); return true;")

    def test_no_draft_widening_for_unscoped_published_ids_query(self):
        parents = ",".join(str(self.fx["topics"][name]) for name in ("a", "b", "empty"))
        ids = self.php(self.owner, f"return get_posts(array('post_type'=>array('lesson','tutor_quiz'),'post_parent__in'=>array({parents}),'post_status'=>'publish','fields'=>'ids','posts_per_page'=>-1));")
        self.assertEqual([int(i) for i in ids], [self.fx["lessons"]["a1"]])

    def test_real_last_draft_move_allows_an_explicit_empty_source_topic_on_both_entrypoints(self):
        for action in (None, self.fx["courses"]["own"]):
            self.setUp()
            matrix = self.matrix(indexed=True)
            moved = self.fx["lessons"]["b1"]
            matrix["0"]["lesson_ids"]["2"] = str(moved)
            matrix["1"]["lesson_ids"] = {}
            before = {int(row["ID"]): row for row in self.snapshot()}
            self.assertEqual(before[moved]["post_status"], "draft")
            status, response = self.post(json.dumps(matrix), course_save=action, extra={"content_parent[parent_topic_id]": self.fx["topics"]["a"], "content_parent[content_id]": moved})
            self.assertEqual(status, 200, response)
            after = {int(row["ID"]): row for row in self.snapshot()}
            self.assertEqual(int(after[moved]["post_parent"]), self.fx["topics"]["a"])
            self.assertEqual(after[moved]["post_status"], "draft")
            for uid, old in before.items():
                for field in ("post_status", "post_author"):
                    self.assertEqual(after[uid][field], old[field])

    def test_empty_existing_course_and_new_insert_empty_context_are_safe(self):
        empty = self.fx["courses"]["empty"]
        for raw in ("[]", "{}"):
            self.assertEqual(self.php(self.owner, f"return cas_builder_validate_content_order('{raw}',0);"), 0)
            self.assertEqual(self.php(self.owner, f"return cas_builder_validate_content_order('{raw}',{empty});"), empty)
            status, response = self.post(raw, course_save=empty)
            self.assertEqual(status, 200, response)
        self.assertEqual(self.php(self.admin, f"global $wpdb; return (int)$wpdb->get_var(\"SELECT COUNT(*) FROM {{$wpdb->posts}} WHERE post_parent={empty} AND post_type='topics'\");"), 0)


if __name__ == "__main__":
    unittest.main(verbosity=2)
