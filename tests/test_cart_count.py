import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
MODULE = ROOT / "wp-content/themes/hello-elementor-child/inc/cas-cart-count.php"
HARNESS = ROOT / "tests/fixtures/cart_count_harness.php"


class CartCountTests(unittest.TestCase):
    def request(self, **changes):
        data = {"uid": 7, "counts": {"0": 3, "7": 2, "8": 5}}
        data.update(changes)
        result = subprocess.run(["php", str(HARNESS), str(MODULE)], input=json.dumps(data),
                                text=True, capture_output=True, check=True, timeout=10)
        self.assertEqual(result.stderr, "")
        result = json.loads(result.stdout)
        self.assertTrue(result["private_headers_requested"])
        self.assertFalse(any("write" in call.lower() for call in result["calls"]))
        return result

    def test_current_user_and_supported_guest_only(self):
        for uid, count in ((7, 2), (8, 5), (0, 3)):
            with self.subTest(uid=uid):
                result = self.request(uid=uid, guest_enabled=True)
                self.assertEqual(result["response"], {"status": 200, "success": True, "data": {"known": True, "count": count}})
                self.assertEqual(result["calls"], ["factory", f"read:{uid}"])

    def test_verified_empty_is_zero_but_unsupported_guest_is_unknown(self):
        self.assertEqual(self.request(counts={})["response"]["data"], {"known": True, "count": 0})
        unknown = self.request(uid=0)["response"]
        self.assertEqual(unknown, {"status": 503, "success": False, "data": {"known": False}})

    def test_post_only_and_no_caller_selected_identity(self):
        for method in ("GET", "HEAD", "PUT", "DELETE"):
            result = self.request(method=method)
            self.assertEqual(result["response"]["status"], 405)
            self.assertEqual(result["calls"], [])
        for key in ("user_id", "course_id", "cart_id"):
            result = self.request(post={"action": "cas_cart_count", "nonce": "valid", key: "8"})
            self.assertEqual(result["response"]["status"], 400)
            self.assertEqual(result["calls"], [])

    def test_drawer_uses_native_items_prices_and_escapes_titles(self):
        result = self.request(
            post={"action": "cas_cart_count", "nonce": "valid", "view": "drawer"},
            prices={"1": {"regular_price": 20, "sale_price": 12.5},
                    "2": {"regular_price": 30, "sale_price": 0}},
            titles={"1": '<img src=x onerror="alert(1)"> & study'},
        )
        data = result["response"]["data"]
        self.assertTrue(data["known"])
        self.assertEqual(data["count"], 2)
        self.assertEqual(data["html"].count('class="mc-line"'), 2)
        self.assertIn('<s>$20.00</s> <b class="now">$12.50</b>', data["html"])
        self.assertIn('Subtotal<b>$42.50</b>', data["html"])
        self.assertIn('&lt;img src=x onerror=&quot;alert(1)&quot;&gt; &amp; study', data["html"])
        self.assertNotIn('<img src=x', data["html"])
        self.assertIn('data-course-id="1"', data["html"])
        self.assertNotIn('mc-qty', data["html"])
        self.assertEqual(result["calls"], ["factory", "read:7"])

    def test_drawer_empty_and_unavailable_are_distinct(self):
        post = {"action": "cas_cart_count", "nonce": "valid", "view": "drawer"}
        empty = self.request(counts={}, post=post)["response"]
        self.assertEqual(empty["data"]["count"], 0)
        self.assertIn('Your basket is empty.', empty["data"]["html"])
        self.assertNotIn('Subtotal', empty["data"]["html"])
        for fields in ({"post_status": "private"}, {"post_type": "post"}, {"post_password": "secret"}):
            with self.subTest(fields=fields):
                self.assertEqual(self.request(post=post, post_fields=fields)["response"],
                                 {"status": 503, "success": False, "data": {"known": False}})
        for mode in ("missing_course", "price_db_error", "throw", "db_error"):
            self.assertEqual(self.request(post=post, mode=mode)["response"]["status"], 503)
        for price in ({}, {"regular_price": -1}, {"regular_price": "NaN"},
                      {"regular_price": 10, "sale_price": -5}, {"regular_price": "1e999"}):
            self.assertEqual(self.request(post=post, prices={"1": price})["response"]["status"], 503)

    def test_drawer_view_is_allowlisted_and_requires_the_same_actor_nonce(self):
        for value in (["drawer"], "other"):
            result = self.request(post={"action": "cas_cart_count", "nonce": "valid", "view": value})
            self.assertEqual(result["response"]["status"], 400)
            self.assertEqual(result["calls"], [])
        invalid = self.request(post={"action": "cas_cart_count", "nonce": "valid", "view": "drawer"}, nonce_uid=8)
        self.assertEqual(invalid["response"]["status"], 403)
        self.assertEqual(invalid["calls"], [])

    def test_nonce_missing_bad_array_and_wrong_identity(self):
        for nonce in (None, "bad", ["valid"]):
            result = self.request(post={"action": "cas_cart_count", "nonce": nonce})
            self.assertEqual(result["response"]["status"], 403)
            self.assertEqual(result["calls"], [])
        self.assertEqual(self.request(nonce_uid=8)["response"]["status"], 403)

    def test_helper_failures_and_invalid_native_results_are_unknown(self):
        for mode in ("throw", "bad_result", "bad_item", "bad_object", "db_error"):
            with self.subTest(mode=mode):
                response = self.request(mode=mode)["response"]
                self.assertEqual(response, {"status": 503, "success": False, "data": {"known": False}})
        self.assertEqual(self.request(native=False)["response"]["status"], 503)
        self.assertEqual(self.request(missing_factory=True)["response"]["status"], 503)

    def test_preexisting_db_error_is_not_this_reads_error_and_is_preserved(self):
        result = self.request(previous_error="old error")
        self.assertEqual(result["response"]["data"], {"known": True, "count": 2})
        self.assertEqual(result["last_error"], "old error")
        result = self.request(mode="same_db_error", previous_error="old error")
        self.assertEqual(result["response"]["data"], {"known": False})
        self.assertEqual(result["last_error"], "old error")
        result = self.request(mode="db_error", previous_error="old error")
        self.assertEqual(result["last_error"], "synthetic query error")

    def test_both_identity_hooks_are_registered(self):
        self.assertEqual(self.request()["hooks"], ["wp_ajax_cas_cart_count", "wp_ajax_nopriv_cas_cart_count"])

    def test_private_headers_wiring_and_no_mutation_calls_in_source(self):
        source = MODULE.read_text()
        self.assertIn("Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0", source)
        self.assertIn("Vary: Cookie", source)
        self.assertIn("Allow: POST", source)
        for forbidden in ("->add(", "->remove(", "clear_cart(", "add_course_to_cart(", "update_user_meta(",
                          "wp_set_current_user(", "setcookie(", "wp_set_auth_cookie(", "tutor_ecommerce_get_cart_count("):
            self.assertNotIn(forbidden, source)
        theme = MODULE.parents[1]
        functions = (theme / "functions.php").read_text()
        self.assertIn("'/inc/cas-cart-count.php'", functions)
        self.assertIn("'casCartCount'", functions)
        header = (theme / "inc/cas-header.php").read_text()
        self.assertIn('class="cart-badge" aria-hidden="true" hidden style="display:none;"></span>', header)
        self.assertNotIn("cas-cart", (theme / "js/cas-header.js").read_text())


if __name__ == "__main__":
    unittest.main()
