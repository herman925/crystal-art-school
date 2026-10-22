#!/usr/bin/env python3
"""Review-only coupon acknowledgement fixture; no WordPress/database/shared stack."""
import shutil
import subprocess
import unittest
from pathlib import Path

COUPONS = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-coupons.php"


@unittest.skipUnless(shutil.which("php"), "PHP CLI is required")
class CouponAcknowledgement(unittest.TestCase):
    def test_only_exact_string_one_acknowledges_full_discount(self) -> None:
        script = r'''
namespace Tutor\Models {
    class CouponModel {
        public function get_table_name() { return 'synthetic_coupons'; }
        public function get_coupon_by_id() { throw new \Exception('unsafe formatted read'); }
        public function get_coupon_by_code() { throw new \Exception('unsafe formatted read'); }
    }
}
namespace {
    define('ABSPATH', '/');
    function add_action() {}
    function sanitize_text_field($value) { return trim($value); }
    function sanitize_key($value) { return $value; }
    function wp_unslash($value) { return $value; }
    function absint($value) { return abs((int)$value); }
    if (!function_exists('mb_strlen')) { function mb_strlen($value) { return strlen($value); } }
    $wpdb = new class {
        public function prepare($query, ...$values) { return [$query, $values]; }
        public function get_row($query) {
            if (!is_array($query) || strpos($query[0], 'coupon_code = %s') === false) {
                throw new \Exception('unparameterised coupon read');
            }
            return null;
        }
    };
    require COUPONS_PATH;
    $input = ['coupon_title' => 'Synthetic', 'coupon_code' => 'REVIEW100',
        'discount_type' => 'percentage', 'discount_amount' => '100',
        'start_date' => '2026-10-23', 'expire_date' => '',
        'total_usage_limit' => '', 'per_user_usage_limit' => ''];
    foreach ([null, '', '0', 'false', 'yes', 'on', ' 1', '1 ', 1, true, ['1']] as $ack) {
        $result = cas_coupons_validate($input + ['accept_full_discount' => $ack], 0);
        if (!isset($result['error'])) { throw new \Exception('malformed acknowledgement accepted'); }
    }
    $result = cas_coupons_validate($input + ['accept_full_discount' => '1'], 0);
    if (!isset($result['data']) || $result['data']['discount_amount'] !== '100.00') {
        throw new \Exception('exact acknowledgement refused');
    }
    $input['discount_amount'] = '99';
    if (!isset(cas_coupons_validate($input, 0)['data'])) {
        throw new \Exception('ordinary discount unnecessarily requires acknowledgement');
    }
    $intended = $result['data'] + ['coupon_status' => 'active', 'created_by' => 7];
    $stored = (object)$intended;
    $stored->created_by = '7';
    if (!cas_coupons_record_matches($stored, $intended)) { throw new \Exception('normalised stored row refused'); }
    foreach ($intended as $key => $value) {
        $wrong = clone $stored;
        $wrong->$key = $value === null ? '' : 'mismatch';
        if (cas_coupons_record_matches($wrong, $intended)) {
            throw new \Exception('readback mismatch accepted: ' . $key);
        }
    }
    $missing = clone $stored;
    unset($missing->applies_to);
    if (cas_coupons_record_matches($missing, $intended) || cas_coupons_record_matches(null, $intended)) {
        throw new \Exception('missing persisted row/field accepted');
    }
}
'''.replace("COUPONS_PATH", repr(str(COUPONS)))
        result = subprocess.run([shutil.which("php"), "-r", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr or result.stdout)


if __name__ == "__main__":
    unittest.main()
