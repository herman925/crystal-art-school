#!/usr/bin/env python3
"""Private course files, follow-up: delete cleanup, folder and file modes, audit lines.

Runs the real module and the real audit mu-plugin under tests/fixtures/private_files_harness.php,
with WordPress functions stubbed. Each test works in its own folder under .claude/scratch and
needs no local WordPress site, no docker and no network. Set CAS_PRIVATE_MODULE to run the same
checks against another copy of cas-private-files.php (used for the RED run against origin/main).

Run: python3 tests/test_private_files_followup.py
"""
import json
import os
import shutil
import stat
import subprocess
import tempfile
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MODULE = Path(os.environ.get("CAS_PRIVATE_MODULE", ROOT / "wp-content/themes/hello-elementor-child/inc/cas-private-files.php"))
AUDIT = ROOT / "wp-content/mu-plugins/cas-audit-log.php"
HARNESS = Path(__file__).resolve().parent / "fixtures/private_files_harness.php"
SCRATCH = ROOT / ".claude" / "scratch"


def mode(path: Path) -> str:
    return oct(stat.S_IMODE(path.stat().st_mode))[2:]


class Site:
    """A fake WordPress tree: uploads, the private root and a log folder, all under one temp dir."""

    def __init__(self) -> None:
        SCRATCH.mkdir(parents=True, exist_ok=True)
        self.tmp = Path(tempfile.mkdtemp(prefix="privfiles-", dir=SCRATCH))
        self.abspath = self.tmp / "x" / "y" / "z"  # dirname(ABSPATH, 3) is the temp root, so logs go to tmp/cas-logs
        self.abspath.mkdir(parents=True)
        self.private = self.tmp / "cas-private"
        self.private.mkdir()
        (self.tmp / "cas-logs").mkdir()
        self.uploads = self.tmp / "uploads"
        self.uploads.mkdir()
        self.meta: dict = {}
        self.attached: dict = {}

    def close(self) -> None:
        shutil.rmtree(self.tmp, ignore_errors=True)

    def attachment(self, aid: int, names: list, private: bool = True) -> Path:
        """Create attachment `aid` whose original is names[0] and whose sizes are names[1:]."""
        folder = (self.private if private else self.uploads) / str(aid)
        folder.mkdir(parents=True, exist_ok=True)
        sizes = {}
        for n, name in enumerate(names):
            (folder / name).write_bytes(b"file %d %s" % (aid, name.encode()))
            if n:
                sizes[f"size{n}"] = {"file": name, "mime-type": "image/png"}
        self.attached[aid] = str(folder / names[0])
        self.meta[aid] = {"_cas_private": 1 if private else "", "_wp_attachment_metadata": {"sizes": sizes}}
        return folder

    def run(self, scenario: dict) -> dict:
        scenario = dict(scenario, abspath=str(self.abspath) + "/", private_dir=str(self.private),
                        module=str(MODULE), audit_module=str(AUDIT), meta=self.meta, attached=self.attached,
                        result=str(self.tmp / "result.json"))
        path = self.tmp / "scenario.json"
        path.write_text(json.dumps(scenario))
        done = subprocess.run(["php", str(HARNESS), str(path)], capture_output=True, text=True, timeout=60)
        if done.returncode or "Fatal" in done.stdout + done.stderr:
            raise AssertionError("harness failed: " + (done.stdout + done.stderr)[-800:])
        return json.loads((self.tmp / "result.json").read_text())

    def audit_lines(self) -> list:
        log = self.tmp / "cas-logs" / "audit.log"
        return [line for line in log.read_text().splitlines() if "private_file" in line] if log.exists() else []


class FollowupTests(unittest.TestCase):
    def setUp(self) -> None:
        self.site = Site()
        self.addCleanup(self.site.close)

    # Delete cleanup: the delete_attachment hook removes a private attachment's own folder.

    def test_delete_hook_is_registered(self) -> None:
        self.site.attachment(5, ["a.pdf"])
        self.assertTrue(self.site.run({"action": "delete", "id": 5, "user": {}})["delete_hooked"])

    def test_delete_removes_the_private_folder_with_its_sizes(self) -> None:
        folder = self.site.attachment(7, ["photo.png", "photo-150.png", "photo-300.png"])
        self.site.run({"action": "delete", "id": 7, "user": {}})
        self.assertFalse(folder.exists())

    def test_delete_leaves_other_private_attachments_and_the_root(self) -> None:
        keep = self.site.attachment(8, ["keep.pdf"])
        gone = self.site.attachment(9, ["gone.pdf", "gone-150.png"])
        self.site.run({"action": "delete", "id": 9, "user": {}})
        self.assertFalse(gone.exists())
        self.assertTrue((keep / "keep.pdf").is_file())
        self.assertTrue(self.site.private.is_dir())

    def test_delete_ignores_a_non_private_attachment(self) -> None:
        folder = self.site.attachment(10, ["plain.txt"], private=False)
        self.site.run({"action": "delete", "id": 10, "user": {}})
        self.assertTrue((folder / "plain.txt").is_file())

    def test_delete_removes_a_symlink_but_not_its_target(self) -> None:
        folder = self.site.attachment(11, ["doc.pdf"])
        outside = self.site.tmp / "outside.txt"
        outside.write_text("not private")
        os.symlink(outside, folder / "link.txt")
        self.site.run({"action": "delete", "id": 11, "user": {}})
        self.assertFalse(folder.exists())
        self.assertTrue(outside.is_file())

    def test_delete_leaves_a_subfolder_in_place(self) -> None:
        folder = self.site.attachment(12, ["doc.pdf"])
        (folder / "sub").mkdir()
        self.site.run({"action": "delete", "id": 12, "user": {}})
        self.assertTrue((folder / "sub").is_dir())

    # Modes: folders 0700, files moved out of uploads 0600.

    def test_private_folders_are_created_0700(self) -> None:
        original = self.site.uploads / "scan.pdf"
        original.write_bytes(b"pdf")
        self.site.attached[13] = str(original)
        self.site.meta[13] = {"_cas_private": "", "_wp_attachment_metadata": {}}
        self.site.run({"action": "move", "id": 13, "user": {}})
        folder = self.site.private / "13"
        self.assertTrue(folder.is_dir(), "move did not create the attachment folder")
        self.assertEqual(mode(folder), "700")
        self.assertEqual(mode(self.site.private), "700")

    def test_moved_files_are_0600(self) -> None:
        original = self.site.uploads / "scan.pdf"
        original.write_bytes(b"pdf")
        self.site.attached[14] = str(original)
        self.site.meta[14] = {"_cas_private": "", "_wp_attachment_metadata": {}}
        self.site.run({"action": "move", "id": 14, "user": {}})
        moved = self.site.private / "14" / "scan.pdf"
        self.assertTrue(moved.is_file(), "original was not moved into the private folder")
        self.assertEqual(mode(moved), "600")

    # Audit lines: one per serve, denial or miss, with the id and the outcome only.

    def test_audit_line_for_a_denied_request(self) -> None:
        self.site.attachment(20, ["lesson-sheet.pdf"])
        self.assertEqual(self.site.run({"action": "redirect", "get": {"cas_private_file": "20"}, "user": {}})["status"], 403)
        self.assertIn("attachment=20 outcome=denied", self.site.audit_lines()[-1])

    def test_audit_line_for_a_miss(self) -> None:
        self.assertEqual(self.site.run({"action": "redirect", "get": {"cas_private_file": "999999"}, "user": {}})["status"], 404)
        self.assertIn("attachment=999999 outcome=not_found", self.site.audit_lines()[-1])

    def test_audit_line_for_a_served_request_has_no_filename_or_email(self) -> None:
        self.site.attachment(21, ["lesson-sheet.pdf"])
        user = {"logged_in": True, "admin": True, "id": 3}
        self.assertEqual(self.site.run({"action": "redirect", "get": {"cas_private_file": "21"}, "user": user})["status"], 200)
        line = self.site.audit_lines()[-1]
        self.assertIn("attachment=21 outcome=served", line)
        self.assertNotIn("lesson-sheet", line)
        self.assertNotIn("@", line)


if __name__ == "__main__":
    unittest.main(verbosity=2)
