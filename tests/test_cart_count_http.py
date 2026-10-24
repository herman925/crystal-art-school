"""Prepared read-only HTTP cases; independent Review must attest the exact staged source,
local target and current identities before enabling. No setup, login, cookies written by
this runner, fault injection, DB access or cleanup. Native no-write fingerprints/browser
and cache-host evidence remain separate Review gates; flags are not that evidence.
"""
import hashlib
import json
import os
from pathlib import Path
import stat
import unittest
import urllib.error
import urllib.parse
import urllib.request

MODULE = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-cart-count.php"


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class CartCountHTTP(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("REVIEW_LOCAL") != "1":
            raise unittest.SkipTest("Independent Review must enable an explicitly attested local target")
        path = Path(os.environ["CAS_CART_COUNT_REVIEW_INPUT"])
        info = path.lstat()
        parent = path.parent.stat()
        if not stat.S_ISREG(info.st_mode) or info.st_uid != os.getuid() or stat.S_IMODE(info.st_mode) != 0o600:
            raise RuntimeError("Input must be an owned non-symlink mode-0600 file")
        if parent.st_uid != os.getuid() or stat.S_IMODE(parent.st_mode) != 0o700 or info.st_size > 16384:
            raise RuntimeError("Input must be bounded and in an owned private directory")
        data = json.loads(path.read_text())
        if data["source_sha256"] != hashlib.sha256(MODULE.read_bytes()).hexdigest():
            raise RuntimeError("Local module differs from the independently selected source")
        origin = urllib.parse.urlsplit(data["origin"])
        if (origin.scheme != "http" or origin.hostname not in ("localhost", "127.0.0.1") or not origin.port
                or origin.username or origin.password or origin.query or origin.fragment or origin.path not in ("", "/")):
            raise RuntimeError("Explicit local HTTP origin and port required; no default stack")
        cls.url = data["origin"].rstrip("/") + "/wp-admin/admin-ajax.php"
        cls.contexts = data["contexts"]
        if set(cls.contexts) != {"first", "second", "guest"}:
            raise RuntimeError("Require separately attested first/second logged-in identities and guest cookie context")
        cls.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), NoRedirect)

    def request(self, context, method="POST", nonce=None, extra=None):
        fields = {"action": "cas_cart_count", "nonce": context["nonce"] if nonce is None else nonce}
        fields.update(extra or {})
        encoded = urllib.parse.urlencode(fields).encode()
        url = self.url if method == "POST" else self.url + "?" + encoded.decode()
        request = urllib.request.Request(url, data=encoded if method == "POST" else None, method=method,
                                         headers={"Cookie": context["cookie"], "Cache-Control": "no-store"})
        try:
            response = self.opener.open(request, timeout=5)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            body = response.read(65537)
            self.assertLessEqual(len(body), 65536)
            cache = response.headers.get("Cache-Control", "").lower()
            self.assertIn("private", cache)
            self.assertIn("no-store", cache)
            self.assertIn("cookie", response.headers.get("Vary", "").lower())
            return response.status, json.loads(body) if body else None

    def test_current_identity_initial_and_reload(self):
        for name in ("first", "second", "guest"):
            context = self.contexts[name]
            for _ in range(2):
                status, payload = self.request(context)
                self.assertEqual(status, context["expected_status"])
                if status == 200:
                    self.assertIs(payload["success"], True)
                    self.assertIs(payload["data"]["known"], True)
                    self.assertIs(type(payload["data"]["count"]), int)
                    self.assertEqual(payload["data"]["count"], context["expected_count"])
                else:
                    self.assertEqual(payload, {"success": False, "data": {"known": False}})

    def test_methods_nonces_and_caller_identity_are_refused_privately(self):
        context = self.contexts["first"]
        for method in ("GET", "HEAD"):
            status, payload = self.request(context, method=method)
            self.assertEqual(status, 405)
            if payload is not None:
                self.assertEqual(payload["data"], {"known": False})
        for nonce in ("invalid", self.contexts["second"]["nonce"]):
            status, payload = self.request(context, nonce=nonce)
            self.assertEqual(status, 403)
            self.assertEqual(payload["data"], {"known": False})
        status, payload = self.request(context, extra={"user_id": "999999"})
        self.assertEqual(status, 400)
        self.assertEqual(payload["data"], {"known": False})


if __name__ == "__main__":
    unittest.main()
