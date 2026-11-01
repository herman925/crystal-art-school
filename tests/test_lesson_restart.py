import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]


class LessonRestartTests(unittest.TestCase):
    def request(self, **data):
        result = subprocess.run(['php', str(ROOT / 'tests/fixtures/lesson_restart.php')],
                                input=json.dumps(data), text=True, capture_output=True,
                                check=True, timeout=5)
        self.assertEqual(result.stderr, '')
        value = json.loads(result.stdout)
        self.assertTrue(value['headers'])
        return value

    def assert_unchanged(self, result):
        self.assertEqual(result['meta'], result['initial']['meta'])
        self.assertEqual(result['comments'], result['initial']['comments'])
        self.assertIsNone(result['redirect'])

    def test_clears_only_actor_course_completion_and_redirects_to_first(self):
        result = self.request(post={'course_id': '10', '_cas_restart_nonce': 'valid:7:cas_restart_course_10',
                                    'user_id': '8', 'redirect_to': 'https://invalid.example/'})
        self.assertEqual(result['response']['status'], 303)
        self.assertEqual(result['redirect'], {'url': '/lesson/101/', 'status': 303})
        self.assertEqual(result['meta'], {k: v for k, v in result['initial']['meta'].items()
                                          if k not in ['7:101', '7:102']})
        self.assertEqual(result['comments'], {k: v for k, v in result['initial']['comments'].items() if k != '1'})
        self.assertEqual(result['cache'], {'tutor_get_completed_lesson_count_by_7_101_102': False,
                                           'tutor_is_completed_course_10_7': False})

    def test_post_and_authenticated_actor_required(self):
        for data in [{'method': method} for method in ['GET', 'HEAD', 'PUT']] + [{'uid': 0}]:
            result = self.request(**data)
            self.assertEqual(result['response']['status'], 403)
            self.assert_unchanged(result)

    def test_nonce_is_bound_to_actor_and_course(self):
        for nonce in [None, [], 'bad', 'valid:8:cas_restart_course_10', 'valid:7:cas_restart_course_20']:
            result = self.request(post={'course_id': '10', '_cas_restart_nonce': nonce})
            self.assertEqual(result['response']['status'], 403)
            self.assert_unchanged(result)

    def test_invalid_course_inputs_refused_before_writes(self):
        for course in [None, [], '0', '-10', '10foo', '999999999999999999999999999']:
            result = self.request(post={'course_id': course, '_cas_restart_nonce': 'valid:7:cas_restart_course_10'})
            self.assertEqual(result['response']['status'], 403)
            self.assert_unchanged(result)

    def test_enrolment_and_published_course_required(self):
        for data in [{'unenrolled': True}, {'course_status': 'draft'}, {'course_status': 'trash'}]:
            result = self.request(**data)
            self.assertEqual(result['response']['status'], 403)
            self.assert_unchanged(result)

    def test_invalid_graph_missing_first_and_read_failure_do_not_write(self):
        for data in [{'ids': [101, 201]}, {'ids': []}, {'no_first': True}, {'db_error': True}]:
            result = self.request(**data)
            self.assertEqual(result['response']['status'], 500)
            self.assert_unchanged(result)

    def test_failed_write_does_not_report_success(self):
        result = self.request(write_error=True)
        self.assertEqual(result['response']['status'], 500)
        self.assert_unchanged(result)


if __name__ == '__main__':
    unittest.main()
