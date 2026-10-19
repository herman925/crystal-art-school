from __future__ import annotations

import hashlib
import importlib.util
import sys
import tempfile
import unittest
from pathlib import Path

RUNNER_PATH = Path(__file__).parent / "paid-materials-security" / "run.py"
SPEC = importlib.util.spec_from_file_location("paid_materials_runner", RUNNER_PATH)
if SPEC is None or SPEC.loader is None:
    raise RuntimeError("cannot load paid-materials safety runner")
runner = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = runner
SPEC.loader.exec_module(runner)


class PaidMaterialsSafetyTests(unittest.TestCase):
    def test_accepts_only_plain_loopback_http(self) -> None:
        for value in (
            "http://localhost:18089",
            "http://127.0.0.1:18089",
            "http://[::1]:18089",
        ):
            with self.subTest(value=value):
                self.assertEqual(runner.validate_loopback_base(value).scheme, "http")

    def test_refuses_external_production_and_non_http_targets(self) -> None:
        for value in (
            "https://crystalau.com",
            "https://staging.crystalau.com",
            "http://192.168.1.25:18089",
            "https://127.0.0.1:18089",
            "file:///etc/passwd",
            "http://user:password@127.0.0.1:18089",
            "http://127.0.0.1:18089/?redirect=https://crystalau.com",
            "http://127.0.0.1:18089/#fragment",
        ):
            with self.subTest(value=value), self.assertRaises(runner.SafetyError):
                runner.validate_loopback_base(value)

    def test_redirect_must_keep_exact_loopback_origin(self) -> None:
        origin = runner.validate_loopback_base("http://127.0.0.1:18089")
        self.assertEqual(
            runner.validate_same_origin("http://127.0.0.1:18089/login/", origin),
            "http://127.0.0.1:18089/login/",
        )
        for value in (
            "http://localhost:18089/login/",
            "http://127.0.0.1:8090/",
            "https://127.0.0.1:18089/",
            "http://crystalau.com/",
        ):
            with self.subTest(value=value), self.assertRaises(runner.SafetyError):
                runner.validate_same_origin(value, origin)

    def test_manifest_requires_synthetic_identity_and_safe_database(self) -> None:
        origin = runner.validate_loopback_base("http://127.0.0.1:18089")
        base = self.synthetic_manifest()
        runner.validate_manifest(base, origin)

        for index, edit in enumerate((
            lambda data: data.update(synthetic=False),
            lambda data: data["database"].update(name="production_wp"),
            lambda data: data["database"].update(host="mysql.crystalau.com"),
            lambda data: data.update(home="https://crystalau.com/"),
            lambda data: data["users"]["editor"].update(login="real-user"),
            lambda data: data["files"]["paid_lesson"].update(url="https://crystalau.com/private.pdf"),
        )):
            data = self.synthetic_manifest()
            edit(data)
            with self.subTest(case=index), self.assertRaises(runner.SafetyError):
                runner.validate_manifest(data, origin)

    def test_manifest_file_must_stay_outside_local_repo(self) -> None:
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            repo = root / "repo"
            outside = root / "private"
            repo.mkdir()
            outside.mkdir()
            manifest = outside / "fixture.json"
            manifest.write_text("{}", encoding="utf-8")

            self.assertEqual(runner.validate_manifest_path(manifest, repo), manifest.resolve())
            inside = repo / "fixture.json"
            inside.write_text("{}", encoding="utf-8")
            with self.assertRaises(runner.SafetyError):
                runner.validate_manifest_path(inside, repo)

    def test_remote_manifest_stays_outside_runtime_roots(self) -> None:
        origin = runner.validate_loopback_base("http://127.0.0.1:18089")
        for remote_path in ("/var/www/html/fixture.json", "/source/fixture.json"):
            data = self.synthetic_manifest()
            data["manifest_path"] = remote_path
            with self.subTest(remote_path=remote_path), self.assertRaises(runner.SafetyError):
                runner.validate_manifest(data, origin)

    def test_runtime_path_guards_support_posix_and_windows(self) -> None:
        self.assertTrue(runner.path_string_is_absolute("/var/www/html"))
        self.assertTrue(runner.path_string_is_absolute(r"C:\\wordpress\\html"))
        self.assertTrue(runner.path_string_is_within("/tmp/fixture.json", "/tmp"))
        self.assertTrue(runner.path_string_is_within(r"C:\\temp\\fixture.json", r"C:\\temp"))
        self.assertFalse(runner.path_string_is_within("/tmp/fixture.json", "/var/www/html"))

    def test_file_check_uses_body_hash_not_status_alone(self) -> None:
        fixture = b"%PDF-1.4\nSYNTHETIC-MARKER\n%%EOF\n"
        expected = hashlib.sha256(fixture).hexdigest()
        login_page = b"<html><form><input name='log'></form></html>"
        self.assertTrue(runner.body_matches_fixture(fixture, expected))
        self.assertFalse(runner.body_matches_fixture(login_page, expected))

    def test_clients_do_not_share_cookie_jars(self) -> None:
        origin = runner.validate_loopback_base("http://127.0.0.1:18089")
        first = runner.LoopbackClient(origin)
        second = runner.LoopbackClient(origin)
        self.assertIsNot(first.cookie_jar, second.cookie_jar)

    @staticmethod
    def synthetic_manifest() -> dict:
        run_id = "cas-paid-materials-a1b2c3d4e5f6"
        files = {}
        for key in runner.FILE_ROLES:
            files[key] = {
                "name": f"{run_id}-{key}.pdf",
                "url": f"http://127.0.0.1:18089/wp-content/uploads/{run_id}-{key}.pdf",
                "sha256": "a" * 64,
            }
        users = {
            role: {
                "login": f"caspaid_{run_id}_{role}",
                "password": "synthetic-test-password-value-long-enough",
                "email": f"caspaid-{role}@example.test",
                "role": runner.WP_ROLES[role],
            }
            for role in runner.USER_ROLES
        }
        users["editor"]["authorization"] = {field: False for field in runner.EDITOR_AUTH_FIELDS}

        return {
            "schema": 1,
            "synthetic": True,
            "run_id": run_id,
            "environment": "staging",
            "home": "http://127.0.0.1:18089/",
            "siteurl": "http://127.0.0.1:18089/",
            "web_root": "/var/www/html",
            "repo_root": "/source",
            "manifest_path": f"/tmp/{run_id}.json",
            "database": {"host": "db", "name": "wp_test"},
            "users": users,
            "pages": {
                "course": f"http://127.0.0.1:18089/courses/{run_id}-course/",
                "paid_lesson": f"http://127.0.0.1:18089/lessons/{run_id}-paid-lesson/",
                "preview_lesson": f"http://127.0.0.1:18089/lessons/{run_id}-preview-lesson/",
            },
            "files": files,
            "markers": {
                "course": f"CAS_PAID_MATERIALS_COURSE_{run_id}",
                "paid_lesson": f"CAS_PAID_MATERIALS_PAID_LESSON_{run_id}",
                "preview_lesson": f"CAS_PAID_MATERIALS_PREVIEW_LESSON_{run_id}",
            },
        }


if __name__ == "__main__":
    unittest.main()
