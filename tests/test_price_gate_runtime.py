#!/usr/bin/env python3
"""Course price gate, runtime proof on the local site (issue #150, Route A, W1-3).

The real screen and admin-post handlers are driven over HTTP as the synthetic cas_content user.
Needs CAS_RUNTIME_TESTS=1, this branch's theme mounted, and cas-audit-log.php (its return value is
used by the gate) copied into the local mu-plugins. Synthetic courses and users are removed afterwards.
"""
import base64
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
FAULT_SRC = pathlib.Path(__file__).resolve().parent / "fixtures/cas-w1-price-fault.php"
GATE_TEXT = (pathlib.Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-price-gate.php").read_text(encoding="utf-8")
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
        subprocess.run(["docker", "cp", str(FAULT_SRC), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-w1-price-fault.php"], check=True)
        subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chmod", "0644", "/var/www/html/wp-content/mu-plugins/cas-w1-price-fault.php"], check=True)
        docker("mkdir", "-p", "/cas-logs")
        docker("rm", "-f", "/cas-logs/audit.log")
        subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chown", "www-data:www-data", "/cas-logs"], check=True)
        cls.claims = []
        fx = php("""
global $wpdb;
$uid = username_exists('w1cascontent') ?: wp_create_user('w1cascontent', wp_generate_password(40), 'w1cascontent@example.test');
$pw = wp_generate_password(32, false); wp_set_password($pw, $uid);
(new WP_User($uid))->set_role('cas_content');
$own = wp_insert_post(array('post_type'=>'courses','post_status'=>'draft','post_title'=>'W1 own course','post_author'=>$uid));
update_post_meta($own, '_tutor_course_price_type', 'paid');
update_post_meta($own, 'tutor_course_price', '50'); update_post_meta($own, 'tutor_course_sale_price', '');
$adm = wp_insert_post(array('post_type'=>'courses','post_status'=>'draft','post_title'=>'W1 admin course','post_author'=>1));
update_post_meta($adm, '_tutor_course_price_type', 'paid');
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
        tokens = base64.b64encode(json.dumps(cls.claims).encode()).decode()
        php(f"""
require_once ABSPATH . 'wp-admin/includes/user.php';
global $wpdb;
foreach ((array)json_decode(base64_decode('{tokens}'), true) as $token) {{
  delete_option('cas_price_claim_' . hash('sha256', (string)$token)); delete_transient('cas_price_' . $token);
}}
foreach (array({int(cls.fx['own'])}, {int(cls.fx['adm'])}) as $lock_course) {{ delete_option('cas_price_course_lock_' . $lock_course); }}
$order_id = (int)($wpdb->get_var("SELECT id FROM {{$wpdb->tutor_orders}} WHERE user_id=(SELECT ID FROM {{$wpdb->users}} WHERE user_login='w1cascontent') ORDER BY id DESC LIMIT 1"));
if ($order_id) {{ $wpdb->delete($wpdb->tutor_order_items, array('order_id'=>$order_id)); $wpdb->delete($wpdb->tutor_orders, array('id'=>$order_id)); }}
foreach ($wpdb->get_col("SELECT ID FROM {{$wpdb->posts}} WHERE post_title LIKE 'W1 %'") as $id) {{ wp_delete_post((int)$id, true); }}
$uid = username_exists('w1cascontent'); if ($uid) {{ wp_delete_user($uid); }}
remove_role('cas_content'); delete_option('cas_content_role_version');
echo 'cleaned';
""")
        subprocess.run(["docker", "exec", CONTAINER, "rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-audit-log.php"], check=False)
        docker("rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-w1-price-fault.php")
        docker("rm", "-f", "/cas-logs/audit.log")
        subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chmod", "0755", "/cas-logs"], check=False)
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
        if token:
            self.__class__.claims.append(token)
        return status, loc, token

    def confirm(self, token, typed, faults=None):
        nonce = self.nonce_on(f"wp-admin/admin.php?page=cas-course-prices&token={token}") if token else self.nonce_on("wp-admin/admin.php?page=cas-course-prices")
        fields = {"action": "cas_price_confirm", "_wpnonce": nonce, "_wp_http_referer": "/", "token": token}
        fields.update(typed)
        if faults:
            fields["w1_price_faults"] = json.dumps(faults)
        return self.post(fields)

    def price(self, course):
        return php(f"echo get_post_meta({course}, 'tutor_course_price', true), '|', get_post_meta({course}, 'tutor_course_sale_price', true);").strip()

    def price_pair(self, course):
        pair = json.loads(php(f"echo json_encode(cas_price_current({course}));"))
        return {k:pair[k] for k in ('regular','sale')}

    def set_price(self, course, value):
        php(f"update_post_meta({course}, 'tutor_course_price', '{value}');")

    def set_pair(self, course, regular, sale):
        php(f"update_post_meta({course}, 'tutor_course_price', '{regular}'); update_post_meta({course}, 'tutor_course_sale_price', '{sale}');")

    def order_snapshot(self):
        return json.loads(php(f"""
global $wpdb;
$items = $wpdb->prefix . 'tutor_order_items'; $orders = $wpdb->prefix . 'tutor_orders';
$query = $wpdb->prepare("SELECT o.total_price, i.regular_price, i.sale_price FROM $items i JOIN $orders o ON o.id=i.order_id WHERE o.id=%d", {int(self.fx['order'])});
$row = $wpdb->get_row($query, ARRAY_A);
echo json_encode($row);
"""))

    def audit_event_count(self, event, course):
        log = docker("cat", "/cas-logs/audit.log").stdout
        marker = f"| {event} | course={course} "
        return sum(marker in line for line in log.splitlines())

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
        self.assertEqual(php(f"echo get_post_meta({course}, '_tutor_course_price_type', true);").strip(), "paid")

    def test_1_admin_course_is_refused_at_request(self):
        self.login()
        status, _, _ = self.request_change(self.fx["adm"], "70")
        self.assertEqual(status, 403)
        self.assertEqual(self.price(self.fx["adm"]), "80|")

    def test_2_request_then_confirm_saves_and_logs(self):
        self.login()
        self.set_pair(self.fx["own"], "50", "")
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
        self.set_pair(self.fx["own"], "60", "")
        _, _, token = self.request_change(self.fx["own"], "65")
        _, loc = self.confirm(token, {"old_regular": "60", "old_sale": "", "new_regular": "66", "new_sale": ""})
        self.assertIn("cas_price_notice=mismatch", loc)
        self.assertEqual(self.price(self.fx["own"]), "60|")

    def test_4_stale_old_value_is_refused(self):
        self.login()
        self.set_pair(self.fx["own"], "60", "")
        _, _, token = self.request_change(self.fx["own"], "65")
        self.set_price(self.fx["own"], "70")  # someone else changed it meanwhile
        _, loc = self.confirm(token, {"old_regular": "60", "old_sale": "", "new_regular": "65", "new_sale": ""})
        self.assertIn("cas_price_notice=stale", loc)
        self.assertEqual(self.price(self.fx["own"]), "70|")

    def test_5_token_is_single_use(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "60", "")
        before_order = self.order_snapshot()
        before_audit = self.audit_event_count("course_price", course)
        _, _, token = self.request_change(course, "61")
        typed = {"old_regular": "60", "old_sale": "", "new_regular": "61", "new_sale": ""}
        status, loc = self.confirm(token, typed)
        self.assertIn(status, (302, 403), f"first confirmation response was unexpected: {status} {loc}")
        self.assertEqual(self.price(course), "61.00|")
        self.assertEqual(self.audit_event_count("course_price", course), before_audit + 1)
        self.assertEqual(self.order_snapshot(), before_order)
        status, loc = self.confirm(token, typed)
        self.assertIn(status, (302, 403), f"replay must be refused: {status} {loc}")
        self.assertEqual(self.price(course), "61.00|", "replay must not apply another price")
        self.assertEqual(self.audit_event_count("course_price", course), before_audit + 1, "replay must not write another verified audit line")
        self.assertEqual(self.order_snapshot(), before_order)

    def test_6_no_token_is_refused(self):
        self.login()
        self.set_pair(self.fx["own"], "61", "")
        status, loc = self.confirm("", {"old_regular": "61", "old_sale": "", "new_regular": "62", "new_sale": ""})
        self.assertIn(status, (302, 403), f"missing token was not refused: {status} {loc}")
        self.assertEqual(self.price(self.fx["own"]), "61|")

    def test_7_same_token_claim_and_per_course_lock_are_atomic(self):
        before_price = self.price(self.fx["own"])
        before_order = self.order_snapshot()
        before_audit = self.audit_event_count("course_price", self.fx["own"])
        out = json.loads(php(f"""
$token = 'same-token-proof'; $expires = time() + CAS_PRICE_TOKEN_TTL;
$claim_one = cas_price_claim_token($token, $expires);
$key = 'cas_price_claim_' . hash('sha256', $token); $claim_expiry = (int)get_option($key);
$notoptions = wp_cache_get('notoptions', 'options');
$notoptions = is_array($notoptions) ? $notoptions : array(); $notoptions[$key] = true;
wp_cache_delete($key, 'options'); wp_cache_set('notoptions', $notoptions, 'options'); // Simulate a competing worker's stale negative cache.
$claim_two = cas_price_claim_token($token, $expires);
delete_option($key);
$lock_one = cas_price_acquire_course_lock({self.fx['own']}, 'attempt-one');
$lock_two = cas_price_acquire_course_lock({self.fx['own']}, 'attempt-two');
$course_lock_key = 'cas_price_course_lock_' . {self.fx['own']};
$expired_value = (time() - 1) . '|' . hash('sha256', 'attempt-one');
update_option($course_lock_key, $expired_value, false);
$successor_lock = cas_price_acquire_course_lock({self.fx['own']}, 'attempt-two');
$old_release = cas_price_release_course_lock({self.fx['own']}, $lock_one);
$successor_survives = get_option($course_lock_key) === $successor_lock;
$release_successor = cas_price_release_course_lock({self.fx['own']}, $successor_lock);
$lock_after_release = cas_price_acquire_course_lock({self.fx['own']}, 'attempt-three');
$release_three = $lock_after_release ? cas_price_release_course_lock({self.fx['own']}, $lock_after_release) : false;
echo json_encode(array('claims'=>array($claim_one,$claim_two),'claim_expiry'=>$claim_expiry,'expected_expiry'=>$expires,
 'first_lock'=>(bool)$lock_one,'contender_lock'=>(bool)$lock_two,'old_release'=>(bool)$old_release,
 'successor_lock'=>(bool)$successor_lock,'successor_survives'=>(bool)$successor_survives,
 'release_successor'=>(bool)$release_successor,'next_lock'=>(bool)$lock_after_release,'next_release'=>(bool)$release_three));
"""))
        self.assertEqual(out["claims"], [True, False])
        self.assertEqual(out["claim_expiry"], out["expected_expiry"], "the atomic claim lasts through the token's remaining lifetime")
        self.assertEqual({k:v for k,v in out.items() if k not in ("claims","claim_expiry","expected_expiry")},
                         {"first_lock": True, "contender_lock": False, "old_release": False,
                          "successor_lock": True, "successor_survives": True, "release_successor": True,
                          "next_lock": True, "next_release": True})
        self.assertEqual(self.price(self.fx["own"]), before_price, "claim/lock checks do not write price metadata")
        self.assertEqual(self.order_snapshot(), before_order)
        self.assertEqual(self.audit_event_count("course_price", self.fx["own"]), before_audit, "competing claims create no audit event")

    def test_8_distinct_tokens_for_one_course_second_confirmation_is_stale(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "50", "10")
        order_before = self.order_snapshot()
        _, _, first = self.request_change(course, "55", "20")
        _, _, second = self.request_change(course, "60", "25") # both tokens snapshot 50/10
        self.confirm(first, {"old_regular":"50","old_sale":"10","new_regular":"55","new_sale":"20"})
        _, loc = self.confirm(second, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"25"})
        self.assertIn("cas_price_notice=stale", loc)
        self.assertEqual(self.price(course), "55.00|20.00")
        self.assertEqual(self.order_snapshot(), order_before)

    def test_9_write_failures_restore_both_price_fields_and_leave_orders(self):
        self.login()
        course = self.fx["own"]
        order_before = self.order_snapshot()
        scenarios = [
            ({"tutor_course_price":["60.00"]}, "regular write fails before sale update"),
            ({"tutor_course_sale_price":["20.00"]}, "sale write fails after regular changed"),
        ]
        for faults, label in scenarios:
            with self.subTest(label=label):
                self.set_pair(course, "50", "10")
                before_failures = self.audit_event_count("course_price_write_failed", course)
                before_verified = self.audit_event_count("course_price", course)
                _, _, token = self.request_change(course, "60", "20")
                _, loc = self.confirm(token, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"20"}, faults)
                self.assertIn("cas_price_notice=write_failed", loc)
                self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"10.00"}, "the previous pair must be restored and read back")
                self.assertEqual(self.order_snapshot(), order_before)
                self.assertEqual(self.audit_event_count("course_price_write_failed", course), before_failures + 1)
                self.assertEqual(self.audit_event_count("course_price", course), before_verified, "failed pair must not be logged as verified")
                # The privileged meta-write window must be closed on every failure path.
                php(f"wp_set_current_user({self.fx['uid']}); update_post_meta({course}, 'tutor_course_price', '99'); echo 'attempted';")
                self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"10.00"})

    def test_10_failed_rollback_is_explicitly_operator_attention(self):
        self.login()
        course = self.fx["own"]
        order_before = self.order_snapshot()
        before_verified = self.audit_event_count("course_price", course)
        self.set_pair(course, "50", "10")
        _, _, token = self.request_change(course, "60", "20")
        faults = {"tutor_course_price":["60.00"], "tutor_course_sale_price":["10.00"]}
        _, loc = self.confirm(token, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"20"}, faults)
        self.assertIn("cas_price_notice=rollback_failed", loc)
        self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"20.00"}, "sale rollback injection leaves a detectable partial state")
        self.assertEqual(self.order_snapshot(), order_before)
        self.assertEqual(self.audit_event_count("course_price", course), before_verified, "unverified pair must never get the success event")
        log = docker("cat", "/cas-logs/audit.log").stdout
        self.assertIn(f"course={course} rollback=failed", log)
        # This is a local synthetic course only; the UI must explicitly tell the operator to intervene.
        self.assertIn("Stop editing and contact Herman immediately.", GATE_TEXT)

    def test_11_fails_closed_with_real_audit_logger_when_log_folder_is_unwritable(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "61", "10")
        order_before = self.order_snapshot()
        docker("rm", "-f", "/cas-logs/audit.log")
        subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chmod", "0555", "/cas-logs"], check=True)
        try:
            logger = json.loads(php("echo json_encode(array('uid'=>posix_geteuid(),'loaded'=>function_exists('cas_audit_log'),'written'=>cas_audit_log('w1_probe','unwritable')));"))
            self.assertEqual(logger, {"uid":33,"loaded":True,"written":False}, "merged logger must really fail under the web process user")
            _, _, token = self.request_change(course, "62", "12")
            _, loc = self.confirm(token, {"old_regular":"61","old_sale":"10","new_regular":"62","new_sale":"12"})
            self.assertIn("cas_price_notice=audit", loc)
            self.assertEqual(self.price_pair(course), {"regular":"61.00","sale":"10.00"})
            self.assertEqual(self.order_snapshot(), order_before)
        finally:
            subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "chmod", "0755", "/cas-logs"], check=False)

    def test_13_http_same_token_loser_writes_no_audit_or_price(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "50", "10")
        _, _, token = self.request_change(course, "60", "20")
        claimed = json.loads(php(f"$d=get_transient('cas_price_{token}'); echo json_encode(cas_price_claim_token('{token}', $d['expires']));"))
        self.assertTrue(claimed)
        before_audit = self.audit_event_count("course_price_attempt", course)
        before_order = self.order_snapshot()
        _, loc = self.confirm(token, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"20"})
        self.assertIn("cas_price_notice=expired", loc)
        self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"10.00"})
        self.assertEqual(self.audit_event_count("course_price_attempt", course), before_audit)
        self.assertEqual(self.order_snapshot(), before_order)

    def test_14_http_course_lock_loser_cannot_write_even_with_a_distinct_token(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "50", "10")
        _, _, loser = self.request_change(course, "60", "20")
        _, _, winner = self.request_change(course, "55", "15")
        lock = json.loads(php(f"echo json_encode(cas_price_acquire_course_lock({course}, 'held-proof'));"))
        self.assertIsInstance(lock, str)
        before_audit = self.audit_event_count("course_price_attempt", course)
        before_order = self.order_snapshot()
        try:
            _, loc = self.confirm(loser, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"20"})
            self.assertIn("cas_price_notice=busy", loc)
            self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"10.00"})
            self.assertEqual(self.audit_event_count("course_price_attempt", course), before_audit)
        finally:
            encoded = base64.b64encode(lock.encode()).decode()
            released = json.loads(php(f"echo json_encode(cas_price_release_course_lock({course}, base64_decode('{encoded}')));"))
            self.assertTrue(released)
        _, loc = self.confirm(winner, {"old_regular":"50","old_sale":"10","new_regular":"55","new_sale":"15"})
        self.assertIn("cas_price_notice=saved", loc)
        self.assertEqual(self.price_pair(course), {"regular":"55.00","sale":"15.00"})
        self.assertEqual(self.order_snapshot(), before_order)

    def test_15_missing_audit_function_cannot_mutate_either_price_or_orders(self):
        self.login()
        course = self.fx["own"]
        self.set_pair(course, "50", "10")
        order_before = self.order_snapshot()
        docker("rm", "-f", "/var/www/html/wp-content/mu-plugins/cas-audit-log.php")
        try:
            self.assertEqual(php("echo json_encode(function_exists('cas_audit_log'));"), "false")
            _, _, token = self.request_change(course, "60", "20")
            _, loc = self.confirm(token, {"old_regular":"50","old_sale":"10","new_regular":"60","new_sale":"20"})
            self.assertIn("cas_price_notice=audit", loc)
            self.assertEqual(self.price_pair(course), {"regular":"50.00","sale":"10.00"})
            self.assertEqual(self.order_snapshot(), order_before)
        finally:
            subprocess.run(["docker", "cp", str(AUDIT_SRC), f"{CONTAINER}:/var/www/html/wp-content/mu-plugins/cas-audit-log.php"], check=True)

    def test_12_administrators_are_unaffected(self):
        php(f"wp_set_current_user(1); update_post_meta({self.fx['adm']}, 'tutor_course_price', '85'); echo 'x';")
        self.assertEqual(self.price(self.fx["adm"]), "85|")
        self.set_price(self.fx["adm"], "80")


if __name__ == "__main__":
    unittest.main(verbosity=2)
