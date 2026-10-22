#!/usr/bin/env python3
"""Course price gate, runtime proof on the local site (issue #150, Route A, W1-3).

The real screen and admin-post handlers are driven over HTTP as the synthetic cas_content user.
Needs CAS_RUNTIME_TESTS=1, this branch's theme mounted, and cas-audit-log.php (its return value is
used by the gate) copied into the local mu-plugins. Synthetic courses and users are removed afterwards.
"""
import json
import os
import pathlib
import re
import subprocess
import sys
import unittest
import urllib.error
import urllib.parse
import urllib.request

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
from test_content_role_runtime import BASE, CONTAINER, get, opener, php  # noqa: E402

AUDIT_SRC = pathlib.Path(os.environ.get("CAS_AUDIT_SRC", "")) if os.environ.get("CAS_AUDIT_SRC") else None
ADMIN_POST = BASE + "/wp-admin/admin-post.php"


def docker(*args):
    return subprocess.run(["docker", "exec", CONTAINER, *args], capture_output=True, text=True, timeout=60)


class PriceGateRuntime(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if os.environ.get("CAS_RUNTIME_TESTS") != "1":
            raise unittest.SkipTest("set CAS_RUNTIME_TESTS=1 (changes the local site)")
        if not AUDIT_SRC or not AUDIT_SRC.is_file():
            raise unittest.SkipTest("set CAS_AUDIT_SRC to a cas-audit-log.php that returns a bool")
        subprocess.run(["docker", "cp", str(AUDIT_SRC), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-audit-log.php"], check=True)
        docker("mkdir", "-p", "/cas-logs")
        subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chown", "www-data:www-data", "/cas-logs"], check=True)
        fx = php("""
global $wpdb;
$uid = username_exists('w1cascontent') ?: wp_create_user('w1cascontent', wp_generate_password(40), 'w1cascontent@example.test');
$pw = wp_generate_password(32, false); wp_set_password($pw, $uid);
(new WP_User($uid))->set_role('cas_content');
$own = wp_insert_post(array('post_type'=>'courses','post_status'=>'draft','post_title'=>'W1 own course','post_author'=>$uid));
update_post_meta($own, 'tutor_course_price', '50'); update_post_meta($own, 'tutor_course_sale_price', '');
$adm = wp_insert_post(array('post_type'=>'courses','post_status'=>'draft','post_title'=>'W1 admin course','post_author'=>1));
update_post_meta($adm, 'tutor_course_price', '80'); update_post_meta($adm, 'tutor_course_sale_price', '');
$wpdb->insert($wpdb->tutor_orders, array('user_id'=>$uid,'order_type'=>'course','order_status'=>'completed','payment_status'=>'paid','subtotal_price'=>'50.00','pre_tax_price'=>'50.00','total_price'=>'50.00','net_payment'=>'50.00','created_at_gmt'=>gmdate('Y-m-d H:i:s'),'created_by'=>$uid,'updated_by'=>$uid));
$order = (int)$wpdb->insert_id;
$wpdb->insert($wpdb->tutor_order_items, array('order_id'=>$order,'item_id'=>$own,'regular_price'=>'50.00','sale_price'=>null,'discount_price'=>null,'coupon_code'=>null));
echo json_encode(array('uid'=>$uid,'pw'=>$pw,'own'=>$own,'adm'=>$adm,'order'=>$order));
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
$order_id = (int)($wpdb->get_var("SELECT id FROM {$wpdb->tutor_orders} WHERE user_id=(SELECT ID FROM {$wpdb->users} WHERE user_login='w1cascontent') ORDER BY id DESC LIMIT 1"));
if ($order_id) { $wpdb->delete($wpdb->tutor_order_items, array('order_id'=>$order_id)); $wpdb->delete($wpdb->tutor_orders, array('id'=>$order_id)); }
foreach ($wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE 'W1 %'") as $id) { wp_delete_post((int)$id, true); }
$uid = username_exists('w1cascontent'); if ($uid) { wp_delete_user($uid); }
remove_role('cas_content'); delete_option('cas_content_role_version');
echo 'cleaned';
""")
        docker("rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-audit-log.php")
        docker("rm", "-f", "/cas-logs/audit.log")
        docker("rmdir", "/cas-logs")

    def login(self):
        get(self.op, "wp-login.php")
        body = urllib.parse.urlencode({"log": "w1cascontent", "pwd": self.fx["pw"], "wp-submit": "Log In",
                                       "redirect_to": BASE + "/wp-admin/", "testcookie": "1"}).encode()
        try:
            self.op.open(urllib.request.Request(BASE + "/wp-login.php", data=body), timeout=30)
        except urllib.error.HTTPError:
            pass

    def nonce_on(self, path):
        _, body, _ = get(self.op, path)
        found = re.search(r'name="_wpnonce" value="([a-f0-9]+)"', body.decode("utf-8", "replace"))
        self.assertIsNotNone(found, f"no nonce on {path}")
        return found.group(1)

    def post(self, fields):
        try:
            with self.op.open(urllib.request.Request(ADMIN_POST, data=urllib.parse.urlencode(fields).encode()), timeout=30) as r:
                return r.status, r.headers.get("Location", "")
        except urllib.error.HTTPError as e:
            return e.code, e.headers.get("Location", "")

    def request_change(self, course, regular, sale=""):
        nonce = self.nonce_on("wp-admin/admin.php?page=cas-course-prices")
        status, loc = self.post({"action": "cas_price_request", "_wpnonce": nonce, "_wp_http_referer": "/",
                                 "course_id": course, "regular_price": regular, "sale_price": sale})
        token = urllib.parse.parse_qs(urllib.parse.urlparse(loc).query).get("token", [""])[0]
        return status, loc, token

    def confirm(self, token, typed):
        nonce = self.nonce_on(f"wp-admin/admin.php?page=cas-course-prices&token={token}") if token else self.nonce_on("wp-admin/admin.php?page=cas-course-prices")
        fields = {"action": "cas_price_confirm", "_wpnonce": nonce, "_wp_http_referer": "/", "token": token}
        fields.update(typed)
        return self.post(fields)

    def price(self, course):
        return php(f"echo get_post_meta({course}, 'tutor_course_price', true), '|', get_post_meta({course}, 'tutor_course_sale_price', true);").strip()

    def set_price(self, course, value):
        php(f"update_post_meta({course}, 'tutor_course_price', '{value}');")

    def order_snapshot(self):
        return json.loads(php(f"""
global $wpdb;
$items = $wpdb->prefix . 'tutor_order_items'; $orders = $wpdb->prefix . 'tutor_orders';
$query = $wpdb->prepare("SELECT o.total_price, i.regular_price, i.sale_price FROM $items i JOIN $orders o ON o.id=i.order_id WHERE o.id=%d", {int(self.fx['order'])});
$row = $wpdb->get_row($query, ARRAY_A);
echo json_encode($row);
"""))

    def test_0_role_cannot_write_any_price_meta_directly(self):
        course = self.fx["own"]
        php(f"""
wp_set_current_user({self.fx['uid']});
update_post_meta({course}, 'tutor_course_price', '1');
update_post_meta({course}, 'tutor_course_sale_price', '0.50');
update_post_meta({course}, '_tutor_course_price_type', 'free');
add_post_meta({course}, 'tutor_course_price', '2', true);
delete_post_meta({course}, 'tutor_course_sale_price');
// Exercise Tutor's actual save_post handler pair (Ecommerce::save_price and Course::save_course_meta).
$_POST = array('course_price'=>'3','course_sale_price'=>'1','tutor_course_price_type'=>'free');
do_action('save_post_courses', {course}, get_post({course}));
$_POST = array();
echo 'attempted';
""")
        self.assertEqual(self.price(course), "50|")
        self.assertEqual(php(f"echo get_post_meta({course}, '_tutor_course_price_type', true);").strip(), "")

    def test_1_admin_course_is_refused_at_request(self):
        self.login()
        status, _, _ = self.request_change(self.fx["adm"], "70")
        self.assertEqual(status, 403)
        self.assertEqual(self.price(self.fx["adm"]), "80|")

    def test_2_request_then_confirm_saves_and_logs(self):
        self.login()
        self.set_price(self.fx["own"], "50")
        status, loc, token = self.request_change(self.fx["own"], "60")
        self.assertTrue(token, f"request must return a token, got {status} {loc}")
        order_before = self.order_snapshot()
        self.assertEqual(order_before, {"total_price": "50.00", "regular_price": "50.00", "sale_price": None}, "synthetic historical order must exist")
        status, loc = self.confirm(token, {"old_regular": "50", "old_sale": "", "new_regular": "60", "new_sale": ""})
        self.assertIn("cas_price_notice=saved", loc)
        self.assertEqual(self.price(self.fx["own"]), "60.00|")
        self.assertEqual(self.order_snapshot(), order_before, "existing order total and order-item price are historical and unchanged")
        log = docker("cat", "/cas-logs/audit.log").stdout
        self.assertIn(f"course={self.fx['own']} regular=50.00->60.00", log)

    def test_3_wrong_typed_value_is_refused(self):
        self.login()
        self.set_price(self.fx["own"], "60")
        _, _, token = self.request_change(self.fx["own"], "65")
        _, loc = self.confirm(token, {"old_regular": "60", "old_sale": "", "new_regular": "66", "new_sale": ""})
        self.assertIn("cas_price_notice=mismatch", loc)
        self.assertEqual(self.price(self.fx["own"]), "60|")

    def test_4_stale_old_value_is_refused(self):
        self.login()
        self.set_price(self.fx["own"], "60")
        _, _, token = self.request_change(self.fx["own"], "65")
        self.set_price(self.fx["own"], "70")  # someone else changed it meanwhile
        _, loc = self.confirm(token, {"old_regular": "60", "old_sale": "", "new_regular": "65", "new_sale": ""})
        self.assertIn("cas_price_notice=stale", loc)
        self.assertEqual(self.price(self.fx["own"]), "70|")

    def test_5_token_is_single_use(self):
        self.login()
        self.set_price(self.fx["own"], "60")
        _, _, token = self.request_change(self.fx["own"], "61")
        typed = {"old_regular": "60", "old_sale": "", "new_regular": "61", "new_sale": ""}
        status, loc = self.confirm(token, typed)
        self.assertIn(status, (302, 403), f"replayed token was not refused: {status} {loc}")
        self.assertEqual(self.price(self.fx["own"]), "61.00|", "replay must not apply another price")

    def test_6_no_token_is_refused(self):
        self.login()
        status, loc = self.confirm("", {"old_regular": "61", "old_sale": "", "new_regular": "62", "new_sale": ""})
        self.assertIn(status, (302, 403), f"missing token was not refused: {status} {loc}")
        self.assertEqual(self.price(self.fx["own"]), "61.00|")

    def test_7_fails_closed_without_the_audit_line(self):
        self.login()
        self.set_price(self.fx["own"], "61")
        docker("rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-audit-log.php")
        try:
            _, _, token = self.request_change(self.fx["own"], "62")
            _, loc = self.confirm(token, {"old_regular": "61", "old_sale": "", "new_regular": "62", "new_sale": ""})
            self.assertIn("cas_price_notice=audit", loc)
            self.assertEqual(self.price(self.fx["own"]), "61|")
        finally:
            subprocess.run(["docker", "cp", str(AUDIT_SRC), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-audit-log.php"], check=True)

    def test_8_administrators_are_unaffected(self):
        php(f"wp_set_current_user(1); update_post_meta({self.fx['adm']}, 'tutor_course_price', '85'); echo 'x';")
        self.assertEqual(self.price(self.fx["adm"]), "85|")
        self.set_price(self.fx["adm"], "80")


if __name__ == "__main__":
    unittest.main(verbosity=2)
