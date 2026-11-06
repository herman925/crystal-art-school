import pathlib
import subprocess
import unittest
import json
import socket
import tempfile
import time
import urllib.request

ROOT = pathlib.Path(__file__).resolve().parents[1]


class CourseMediumTests(unittest.TestCase):
    def test_complete_course_renderer(self):
        result = subprocess.run(
            ['php', '-d', 'zend.assertions=1', '-d', 'assert.exception=1',
             str(ROOT / 'tests/fixtures/course-render-harness.php')],
            capture_output=True, text=True, timeout=15,
        )
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_shared_medium_and_purchase_qualification(self):
        for count in ('single', 'multiple'):
            with self.subTest(media=count):
                result = subprocess.run(
                    ['php', '-d', 'zend.assertions=1', '-d', 'assert.exception=1',
                     str(ROOT / 'tests/fixtures/course-medium-harness.php'), count],
                    capture_output=True, text=True, timeout=15,
                )
                self.assertEqual(result.returncode, 0, result.stdout + result.stderr)

    def test_verified_hook_records_only_bound_payment_mode(self):
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        with tempfile.TemporaryFile() as log:
            server = subprocess.Popen(
                ['php', '-S', f'127.0.0.1:{port}',
                 str(ROOT / 'tests/fixtures/popularity-hook-harness.php')],
                stdout=log, stderr=log,
            )
            try:
                for _ in range(50):
                    try:
                        with socket.create_connection(('127.0.0.1', port), timeout=.1):
                            break
                    except OSError:
                        time.sleep(.05)
                event = {'object': 'event', 'type': 'payment_intent.succeeded',
                         'livemode': True, 'data': {'object': {
                             'object': 'payment_intent', 'id': 'pi_10001',
                             'livemode': True, 'status': 'succeeded',
                             'amount_received': 2000, 'metadata': {'order_id': '10001'},
                         }}}

                def send(payload, query=''):
                    request = urllib.request.Request(
                        f'http://127.0.0.1:{port}/{query}',
                        data=json.dumps(payload).encode(),
                        headers={'Content-Type': 'application/json'},
                    )
                    with urllib.request.urlopen(request, timeout=5) as response:
                        return json.load(response)

                result = send(event)
                self.assertEqual(result['evidence']['mode'], 'live')
                self.assertEqual(result['key'], '_cas_popularity_payment_evidence')
                self.assertIsNone(send(event, '?foreign=1')['evidence'])
                event['data']['object']['metadata']['order_id'] = '999'
                self.assertIsNone(send(event)['evidence'])
                event['data']['object']['metadata']['order_id'] = '10001'
                event['livemode'] = event['data']['object']['livemode'] = False
                self.assertEqual(send(event)['evidence']['mode'], 'test')
                event['data']['object']['livemode'] = True
                self.assertIsNone(send(event)['evidence'])
            finally:
                server.terminate()
                server.wait(timeout=5)


if __name__ == '__main__':
    unittest.main()
