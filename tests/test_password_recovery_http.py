#!/usr/bin/env python3
"""Read-only loopback smoke tests for Tutor's public password-recovery routes.

Set CAS_RECOVERY_TEST_BASE_URL to a local WordPress URL, for example
http://localhost:8089. This test only sends GET requests and never sends email.
"""

from __future__ import annotations

import os
import unittest
from urllib.parse import urljoin, urlsplit
from urllib.request import HTTPRedirectHandler, ProxyHandler, Request, build_opener


class TestPasswordRecoveryHttp(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        base_url = os.environ.get("CAS_RECOVERY_TEST_BASE_URL", "").strip()
        if not base_url:
            raise unittest.SkipTest("set CAS_RECOVERY_TEST_BASE_URL to a local WordPress URL")

        parsed = urlsplit(base_url)
        if parsed.scheme != "http" or parsed.hostname not in {"localhost", "127.0.0.1", "::1"}:
            raise AssertionError("refusing non-loopback recovery test target")
        if parsed.username or parsed.password or parsed.query or parsed.fragment:
            raise AssertionError("base URL must not include credentials, query or fragment")

        cls.base_url = base_url.rstrip("/")
        cls.base_path = parsed.path.rstrip("/")

    def get(self, path: str) -> tuple[str, str]:
        url = urljoin(self.base_url + "/", path.lstrip("/"))
        request = Request(url, headers={"User-Agent": "CAS local recovery route test"})

        class LoopbackRedirectHandler(HTTPRedirectHandler):
            def redirect_request(self, req, fp, code, msg, headers, newurl):
                target = urlsplit(newurl)
                if target.scheme != "http" or target.hostname not in {"localhost", "127.0.0.1", "::1"}:
                    raise AssertionError("refusing redirect outside loopback")
                return super().redirect_request(req, fp, code, msg, headers, newurl)

        with build_opener(ProxyHandler({}), LoopbackRedirectHandler()).open(request, timeout=10) as response:
            return urlsplit(response.url).path, response.read().decode("utf-8", "replace")

    def recovery_path(self, path: str) -> bool:
        return path.rstrip("/") == self.base_path + "/dashboard/retrieve-password"

    def test_recovery_get_works_with_or_without_trailing_slash(self) -> None:
        for slash in ("", "/"):
            with self.subTest(slash=slash):
                final_path, body = self.get(f"/dashboard/retrieve-password{slash}")
                self.assertTrue(self.recovery_path(final_path), final_path)
                self.assertIn('name="user_login"', body)
                self.assertIn('name="tutor_action" value="tutor_retrieve_password"', body)

    def test_reset_token_shape_selects_tutor_reset_form(self) -> None:
        path = "/dashboard/retrieve-password/?reset_key=invalid-get-only&user_id=2147483647"
        final_path, body = self.get(path)
        self.assertTrue(self.recovery_path(final_path), final_path)
        for field in ('name="reset_key"', 'name="user_id"', 'name="password"', 'name="confirm_password"'):
            self.assertIn(field, body)
        # This is a GET-only template-selection check, not proof that a reset token is valid.

    def test_ordinary_guest_dashboard_still_redirects_to_login(self) -> None:
        final_path, _ = self.get("/dashboard/")
        self.assertEqual(final_path.rstrip("/"), self.base_path + "/login")


if __name__ == "__main__":
    unittest.main()
