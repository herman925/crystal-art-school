#!/usr/bin/env python3
"""docs-site/deploy.sh: host key pinning, argument handling and dry-run (no network, no SSH).

Run: python3 tests/test_docs_deploy.py
The real publish path is not exercised here: it needs the server and the SSH key.
Set DEPLOY_DRY_RUN_TEST=1 to also run the full dry-run build (npm ci, about two minutes).
"""
import os
import shutil
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
DEPLOY = ROOT / "docs-site" / "deploy.sh"
SCRATCH = ROOT / ".claude" / "scratch"
EXPECTED = "SHA256:QM5FECXwy/Tk32qCDy82opddDNuZYWWfi31zV0hSABY"


def run_bash(script: str, *args: str) -> subprocess.CompletedProcess:
    """Source deploy.sh (its main() does not run when sourced), then run `script`."""
    return subprocess.run(["bash", "-c", f'source "$1"; shift; {script}', "_", str(DEPLOY), *args],
                          capture_output=True, text=True, timeout=120)


class DeployScriptTests(unittest.TestCase):
    def setUp(self) -> None:
        SCRATCH.mkdir(parents=True, exist_ok=True)
        self.tmp = Path(tempfile.mkdtemp(prefix="deploytest-", dir=SCRATCH))
        self.addCleanup(shutil.rmtree, self.tmp, True)
        key = self.tmp / "host_key"
        subprocess.run(["ssh-keygen", "-q", "-t", "ed25519", "-N", "", "-f", str(key)], check=True)
        pub = (self.tmp / "host_key.pub").read_text().split()[:2]
        self.known = self.tmp / "known_hosts"
        self.known.write_text(f"[127.0.0.1]:65002 {pub[0]} {pub[1]}\n")
        self.fingerprint = subprocess.run(["ssh-keygen", "-lf", str(self.known)],
                                          capture_output=True, text=True, check=True).stdout.split()[1]

    def test_matching_fingerprint_passes(self) -> None:
        self.assertEqual(run_bash('check_host_fingerprint "$1" "$2"', str(self.known), self.fingerprint).returncode, 0)

    def test_mismatched_fingerprint_aborts_with_a_message(self) -> None:
        done = run_bash('check_host_fingerprint "$1" "$2"', str(self.known), EXPECTED)
        self.assertNotEqual(done.returncode, 0)
        self.assertIn("Nothing was sent", done.stderr)

    def test_empty_known_hosts_aborts(self) -> None:
        empty = self.tmp / "empty"
        empty.write_text("")
        self.assertNotEqual(run_bash('check_host_fingerprint "$1" "$2"', str(empty), EXPECTED).returncode, 0)

    def test_failed_scan_aborts_before_any_connection_is_used(self) -> None:
        # Points the scan at a closed local port, so nothing reaches the real server.
        done = run_bash('REMOTE=u@127.0.0.1; PORT=1; scan_host_key "$1"', str(self.tmp / "scanned"))
        self.assertNotEqual(done.returncode, 0)
        self.assertIn("no host key", done.stderr)

    def test_unknown_argument_is_refused_before_any_network_call(self) -> None:
        done = subprocess.run(["bash", str(DEPLOY), "--publish-now"], capture_output=True, text=True, timeout=60)
        self.assertEqual(done.returncode, 2)
        self.assertIn("usage", done.stderr)

    def test_script_never_disables_host_key_checking(self) -> None:
        text = DEPLOY.read_text()
        self.assertNotIn("StrictHostKeyChecking=no", text)
        self.assertIn("StrictHostKeyChecking=yes", text)
        self.assertNotIn("cygpath", text)
        self.assertNotIn("/c/Users", text)

    @unittest.skipUnless(os.environ.get("DEPLOY_DRY_RUN_TEST"), "set DEPLOY_DRY_RUN_TEST=1 to run the full dry-run build")
    def test_dry_run_builds_and_lists_uploads(self) -> None:
        env = dict(os.environ, TMPDIR=str(self.tmp))
        done = subprocess.run(["bash", str(DEPLOY), "--dry-run"], capture_output=True, text=True, env=env, timeout=900)
        self.assertEqual(done.returncode, 0, done.stderr[-800:])
        self.assertIn("dry run: nothing sent", done.stdout)
        self.assertIn("index.html", done.stdout)
        self.assertIn("users.php", done.stdout)  # not uploaded from the repo copy: see deploy.sh
        setup_files = {"host_key", "host_key.pub", "known_hosts", "node-compile-cache"}  # node writes its cache to TMPDIR
        leftovers = [x.name for x in self.tmp.iterdir() if x.name not in setup_files]
        self.assertEqual(leftovers, [], "build directory was not removed")


if __name__ == "__main__":
    unittest.main(verbosity=2)
