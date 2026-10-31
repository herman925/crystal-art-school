"""Bounded, synthetic download-policy regression; no WordPress data or network required."""
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]

class Revocation(unittest.TestCase):
    def test_current_references_are_required(self):
        result = subprocess.run(['php', str(ROOT / 'tests/fixtures/private_revocation.php'),
                                 str(ROOT / 'wp-content/themes/hello-elementor-child/inc/cas-private-files.php')],
                                capture_output=True, text=True, timeout=10)
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn('16 revocation cases passed', result.stdout)

if __name__ == '__main__':
    unittest.main()
