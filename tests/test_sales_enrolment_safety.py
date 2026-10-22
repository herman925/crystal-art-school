#!/usr/bin/env python3
"""Review fixture: order corrections must preserve access from another order."""
import shutil
import subprocess
import unittest
from pathlib import Path

SALES = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-sales.php"


@unittest.skipUnless(shutil.which("php"), "PHP CLI is required")
class SalesEnrolmentSafety(unittest.TestCase):
    def test_other_order_access_is_not_overwritten_or_cancelled(self) -> None:
        script = r'''
namespace Tutor\Models {
    class EnrollmentModel {
        const ENROLLMENT_ORDER_ID_META = '_order';
        const POST_TYPE = 'tutor_enrolled';
        const ENROLLMENT_PRODUCT_ID_META = '_product';
        public static function get_enrolled_data($user, $course, $status) { return $GLOBALS['rows']; }
        public static function update_enrollments($status, $ids, $hooks = true): bool {
            if ($hooks) { throw new \Exception('notification hooks enabled'); }
            foreach ($GLOBALS['rows'] as $row) {
                if (in_array($row->ID, $ids, true)) { $row->post_status = $status; }
            }
            $GLOBALS['writes'][] = [$status, $ids];
            return true;
        }
        public static function do_enroll() { throw new \Exception('existing access was not found'); }
    }
}
namespace {
    define('ABSPATH', '/');
    function add_action() {}
    function get_post_status($id) {
        foreach ($GLOBALS['rows'] as $row) { if ($row->ID === $id) { return $row->post_status; } }
        return false;
    }
    function get_post_meta($id, $key, $single) { return $GLOBALS['meta'][$id][$key] ?? ''; }
    function metadata_exists($type, $id, $key) { return isset($GLOBALS['meta'][$id][$key]); }
    function update_post_meta() { throw new \Exception('existing order association was overwritten'); }
    $wpdb = new class {
        public $posts = 'synthetic_posts';
        public $postmeta = 'synthetic_postmeta';
        public function prepare($sql, $args) { return [$sql, $args]; }
        public function query($prepared) {
            [$sql, $args] = $prepared;
            foreach (['p.post_author = %d', 'p.post_parent = %d', 'p.post_status = %s', 'COALESCE(om.meta_value', 'COALESCE(pm.meta_value'] as $required) {
                if (strpos($sql, $required) === false) { throw new \Exception('conditional undo omitted identity/state'); }
            }
            $GLOBALS['meta'][11]['_order'] = 103;
            if ((string)$GLOBALS['meta'][11]['_order'] !== (string)$args[count($args) - 4]) { return 0; }
            throw new \Exception('conditional undo unexpectedly matched newer association');
        }
    };
    require SALES_PATH;
    function check($condition, $message) { if (!$condition) { throw new \Exception($message); } }
    $GLOBALS['rows'] = [
        (object)['ID' => 11, 'post_parent' => 34, 'post_status' => 'pending'],
        (object)['ID' => 12, 'post_parent' => 34, 'post_status' => 'completed'],
    ];
    $GLOBALS['meta'] = [11 => ['_order' => 101], 12 => ['_order' => 99]];
    $GLOBALS['writes'] = [];
    check(cas_sales_sync_enrolment(101, 7, 34, 'completed') === ['changed' => false], 'prior access hidden by pending row');
    check($GLOBALS['writes'] === [], 'completion altered prior access');
    check($GLOBALS['meta'][12]['_order'] === 99, 'prior purchase association changed');
    $snapshot = cas_sales_sync_enrolment(101, 7, 34, 'cancelled');
    check($snapshot['id'] === 11, 'cancellation selected another order');
    check(get_post_status(11) === 'cancel', 'matching pending enrolment not cancelled');
    check(get_post_status(12) === 'completed', 'other purchase access revoked');
    check(cas_sales_sync_enrolment(102, 7, 34, 'cancelled') === ['changed' => false], 'unrelated cancellation changed access');
    check(count($GLOBALS['writes']) === 1, 'unrelated cancellation wrote enrolments');
    $order = (object)['id' => 101, 'user_id' => 7];
    check(cas_sales_access_verified($order, [34], 'completed'), 'prior access did not satisfy final completion check');
    check(cas_sales_access_verified($order, [34], 'cancelled'), 'other order access blocked valid cancellation');
    check(cas_sales_restore_enrolment($snapshot) === false, 'compensation overwrote newer order association');
    check($GLOBALS['meta'][11]['_order'] === 103, 'conditional undo clobbered concurrent association');
    check(count($GLOBALS['writes']) === 1, 'foreign association changed before compensation refusal');
    $GLOBALS['meta'][11]['_order'] = 101;
    $GLOBALS['rows'][0]->post_status = 'pending';
    check(!cas_sales_access_verified($order, [34], 'cancelled'), 'final check missed matching pending enrolment');
}
'''.replace("SALES_PATH", repr(str(SALES)))
        result = subprocess.run([shutil.which("php"), "-r", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr or result.stdout)


if __name__ == "__main__":
    unittest.main()
