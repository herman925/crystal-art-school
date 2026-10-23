#!/usr/bin/env python3
"""Review-only access-state fixture; pure PHP stubs, no external data or mail."""
import shutil
import subprocess
import unittest
from pathlib import Path

ENROL = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-enrol.php"


@unittest.skipUnless(shutil.which("php"), "PHP CLI is required")
class ManualAccessState(unittest.TestCase):
    def test_grant_removal_and_audit_failure_compensation(self) -> None:
        script = r'''
namespace Tutor\Models {
    class EnrollmentModel {
        const POST_TYPE = 'tutor_enrolled';
        const ENROLLMENT_ORDER_ID_META = '_order';
        const ENROLLMENT_PRODUCT_ID_META = '_product';
        public static function get_enrolled_data($user, $course, $status) { return $GLOBALS['rows']; }
        public static function update_enrollments($status, $ids, $hooks): bool {
            if ($hooks) { throw new \Exception('notification hooks enabled'); }
            foreach ($GLOBALS['rows'] as $row) {
                if (in_array($row->ID, $ids, true)) { $row->post_status = $status; }
            }
            return true;
        }
        public static function do_enroll($course, $order, $user, $hooks) {
            if ($order !== 0 || $hooks) { throw new \Exception('order/mail side effect'); }
            $GLOBALS['rows'][] = (object)['ID' => 21, 'post_author' => $user, 'post_parent' => $course, 'post_status' => 'pending'];
            return 21;
        }
    }
}
namespace {
    define('ABSPATH', '/');
    function add_action() {}
    function get_post_status($id) {
        foreach ($GLOBALS['rows'] as $row) { if ($row->ID === $id) { return $row->post_status; } }
        return false;
    }
    function get_post($id) {
        foreach ($GLOBALS['rows'] as $row) { if ($row->ID === $id) { return $row; } }
        return null;
    }
    function get_post_meta($id, $key, $single) { return $GLOBALS['meta'][$id][$key] ?? ''; }
    function metadata_exists($type, $id, $key) { return isset($GLOBALS['meta'][$id][$key]); }
    function wp_json_encode($value) { return json_encode($value); }
    function clean_post_cache($id) {}
    $wpdb = new class {
        public $posts = 'synthetic_posts';
        public $postmeta = 'synthetic_postmeta';
        public $verified = null;
        public function prepare($sql, $args) { return [$sql, $args]; }
        public function query($prepared) {
            [$sql, $args] = $prepared;
            $this->verified = null;
            foreach (['p.post_author = %d', 'p.post_parent = %d', 'p.post_status = %s', 'om.meta_value', 'pm.meta_value', 'om.meta_id IS NOT NULL'] as $required) {
                if (strpos($sql, $required) === false) { throw new \Exception('undo omitted conditional identity/state'); }
            }
            if (!empty($GLOBALS['race'])) {
                $GLOBALS['meta'][$args[3]]['_order'] = '99';
            }
            $row = get_post($args[3]);
            if (!$row || $row->post_author !== $args[5] || $row->post_parent !== $args[6] || $row->post_status !== $args[7]) { return 0; }
            if (get_post_meta($row->ID, '_order', true) !== $args[8] || get_post_meta($row->ID, '_product', true) !== $args[9]
                || (int)metadata_exists('post', $row->ID, '_order') !== $args[10] || (int)metadata_exists('post', $row->ID, '_product') !== $args[11]) { return 0; }
            $row->post_status = $args[2];
            $this->verified = $row->ID;
            return 1;
        }
        public function get_var($prepared) { return $this->verified; }
    };
    function cas_audit_log($event, $detail) {
        $GLOBALS['events'][] = $event;
        return $event !== $GLOBALS['fail_event'];
    }
    require ENROL_PATH;
    function check($ok, $message) { if (!$ok) { throw new \Exception($message); } }
    function apply_access($operation) {
        return cas_enrol_apply(7, 34, $operation, cas_enrol_signature(cas_enrol_records(34, 7)));
    }
    $GLOBALS['rows'] = [];
    $GLOBALS['events'] = [];
    $GLOBALS['fail_event'] = 'manual_enrolment_attempt';
    check(apply_access('enrol') === 'audit', 'failed attempt audit accepted');
    check($GLOBALS['rows'] === [], 'failed attempt audit mutated access');
    $GLOBALS['fail_event'] = '';
    check(apply_access('enrol') === true, 'fresh grant failed');
    check(get_post_status(21) === 'completed', 'fresh grant did not complete');
    check(apply_access('enrol') === 'already', 'repeat grant duplicated access');
    check(count($GLOBALS['rows']) === 1, 'repeat grant created another row');
    $GLOBALS['fail_event'] = 'manual_enrolment_changed';
    check(apply_access('unenrol') === 'audit', 'final audit failure reported success');
    check(get_post_status(21) === 'completed', 'failed removal did not restore prior access');
    $GLOBALS['fail_event'] = '';
    check(apply_access('unenrol') === true, 'removal failed');
    check(get_post_status(21) === 'cancel', 'removal did not cancel active row');
    check(apply_access('unenrol') === 'already', 'repeat removal was not a no-op');
    check(cas_enrol_apply(7, 34, 'enrol', str_repeat('0', 64)) === false, 'stale confirmation accepted');
    $GLOBALS['rows'][] = (object)['ID' => 22, 'post_author' => 7, 'post_parent' => 34, 'post_status' => 'pending'];
    $GLOBALS['rows'][] = (object)['ID' => 23, 'post_author' => 7, 'post_parent' => 34, 'post_status' => 'pending'];
    check(apply_access('enrol') === true, 'pending-row reuse failed');
    $active = array_filter($GLOBALS['rows'], fn($row) => $row->post_status === 'completed');
    $pending = array_filter($GLOBALS['rows'], fn($row) => $row->post_status === 'pending');
    check(count($active) === 1 && count($pending) === 0, 'grant left duplicate active/pending rows');
    $row = array_values($active)[0];
    $signature = cas_enrol_signature(cas_enrol_records(34, 7));
    $changes = [cas_enrol_snapshot($row, 'cancel')];
    $row->post_status = 'cancel';
    $GLOBALS['race'] = true;
    check(cas_enrol_restore_changes($changes) === false, 'raced undo falsely reported restored');
    check($row->post_status === 'cancel' && $GLOBALS['meta'][$row->ID]['_order'] === '99', 'undo clobbered newer association');
    $row->post_status = 'completed';
    check(cas_enrol_signature(cas_enrol_records(34, 7)) !== $signature, 'confirmation ignored changed order association');
}
'''.replace("ENROL_PATH", repr(str(ENROL)))
        result = subprocess.run([shutil.which("php"), "-r", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr or result.stdout)


if __name__ == "__main__":
    unittest.main()
