#!/usr/bin/env python3
"""cas_audit_log() return value (issue #150, W1-4). Runs the real mu-plugin in PHP CLI with WordPress stubbed.

true  : the whole line was written.
false : the log folder is missing, or it cannot be written (unwritable folder).
Also checks that the two Tutor Analytics handlers are on the admin-only guard list.
Run: python3 tests/test_audit_log_return_contract.py
"""
import os
import shutil
import stat
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MU = ROOT / "wp-content/mu-plugins"
SCRATCH = ROOT / ".claude" / "scratch"

STUBS = """
<?php
define( 'ABSPATH', $argv[1] );
function add_action( ...$a ) { return true; }
function add_filter( ...$a ) { return true; }
function wp_mail( ...$a ) { return true; }
function get_current_user_id() { return 7; }
require $argv[2];
$result = cas_audit_log( 'test_event', 'detail one' );
echo json_encode( array( 'result' => $result, 'is_bool' => is_bool( $result ) ) ), "\\n";
"""


def run(root: Path) -> dict:
    """Load the mu-plugin with ABSPATH three levels below `root`, so the log folder is root/cas-logs."""
    script = root / "probe.php"
    script.write_text(STUBS)
    abspath = str(root / "x" / "y" / "z") + "/"
    done = subprocess.run(["php", str(script), abspath, str(MU / "cas-audit-log.php")],
                          capture_output=True, text=True, timeout=60)
    if done.returncode:
        raise AssertionError("php failed: " + done.stderr[-400:])
    import json
    return json.loads(done.stdout.strip().splitlines()[-1])


class AuditLogReturnContract(unittest.TestCase):
    def setUp(self) -> None:
        SCRATCH.mkdir(parents=True, exist_ok=True)
        self.root = Path(tempfile.mkdtemp(prefix="auditret-", dir=SCRATCH))
        (self.root / "x" / "y" / "z").mkdir(parents=True)
        self.addCleanup(self._cleanup)

    def _cleanup(self) -> None:
        for p in self.root.rglob("*"):
            try:
                p.chmod(p.stat().st_mode | stat.S_IWUSR)
            except OSError:
                pass
        shutil.rmtree(self.root, ignore_errors=True)

    def test_true_when_the_line_is_written(self) -> None:
        (self.root / "cas-logs").mkdir()
        reply = run(self.root)
        self.assertTrue(reply["is_bool"])
        self.assertIs(reply["result"], True)
        line = (self.root / "cas-logs" / "audit.log").read_text()
        self.assertIn("test_event", line)
        self.assertIn("| uid=7 |", line, "the line format is unchanged")

    def test_false_when_the_log_folder_is_missing(self) -> None:
        reply = run(self.root)
        self.assertIs(reply["result"], False)
        self.assertTrue(reply["is_bool"])

    @unittest.skipIf(hasattr(os, "geteuid") and os.geteuid() == 0, "root can write an unwritable folder")
    def test_false_when_the_log_folder_cannot_be_written(self) -> None:
        folder = self.root / "cas-logs"
        folder.mkdir()
        folder.chmod(stat.S_IRUSR | stat.S_IXUSR)
        reply = run(self.root)
        self.assertIs(reply["result"], False)
        self.assertTrue(reply["is_bool"])

    def test_the_two_analytics_handlers_are_on_the_admin_only_guard(self) -> None:
        guard = (MU / "cas-ajax-guard.php").read_text(encoding="utf-8")
        start = guard.index("const CAS_AJAX_GUARD_ADMIN_ONLY")
        block = guard[start:guard.index(");", start)]
        self.assertIn("'view_progress'", block)
        self.assertIn("'export_analytics'", block)


if __name__ == "__main__":
    unittest.main(verbosity=2)
