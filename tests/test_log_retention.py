import datetime as dt
import importlib.util
import io
import json
import locale
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import tracemalloc
import unittest
from unittest.mock import patch

HELPER = Path(__file__).resolve().parents[1] / "tools/log-retention.py"
spec = importlib.util.spec_from_file_location("retention_candidate", HELPER)
retention = importlib.util.module_from_spec(spec)
spec.loader.exec_module(retention)
NOW = dt.datetime(2026, 10, 24, 12)


class ParserTests(unittest.TestCase):
    def setUp(self):
        locale.setlocale(locale.LC_TIME, "C")

    def filtered(self, payload, kind, days, chunk=65536):
        output = io.BytesIO()
        with patch.object(retention, "CHUNK", chunk):
            counts = retention.filter_bytes(io.BytesIO(payload), output, len(payload), kind,
                                            NOW - dt.timedelta(days=days), retention.Budget(len(payload), len(payload) + 2097152, 10))
        self.assertEqual(counts["read"], len(payload))
        self.assertEqual(counts["kept"] + counts["dropped"], len(payload))
        return output.getvalue()

    def test_php_cutoff_optional_utc_trace_and_invalid_date(self):
        cutoff = NOW - dt.timedelta(days=180)
        old = (cutoff - dt.timedelta(seconds=1)).strftime("[%d-%b-%Y %H:%M:%S UTC]").encode()
        equal = cutoff.strftime("[%d-%b-%Y %H:%M:%S]").encode()
        payload = b"leading\xff\r\n" + old + b" expired\rtrace\r\n" + equal + b" equal\ntrace\x80\r[99-Jan-2026 00:00:00 UTC] unknown\r\nkeep\r"
        expected = b"leading\xff\r\n" + equal + b" equal\ntrace\x80\r[99-Jan-2026 00:00:00 UTC] unknown\r\nkeep\r"
        for chunk in (1, 2, 7, 103, 65536):
            self.assertEqual(self.filtered(payload, "php", 180, chunk), expected)

    def test_audit_cutoff_pipes_and_continuations(self):
        cutoff = NOW - dt.timedelta(days=90)
        old = (cutoff - dt.timedelta(seconds=1)).strftime("%Y-%m-%d %H:%M:%S UTC |").encode()
        equal = cutoff.strftime("%Y-%m-%d %H:%M:%S UTC |").encode()
        newer = (cutoff + dt.timedelta(seconds=1)).strftime("%Y-%m-%d %H:%M:%S UTC |").encode()
        payload = old + b" old\ntrace | escaped \\|\n" + equal + b" at cutoff\r\n" + newer + b" newer\r2026-99-99 12:00:00 UTC | invalid\ntrace"
        expected = payload[payload.index(equal):]
        for chunk in (1, 25, 104, 65536):
            self.assertEqual(self.filtered(payload, "audit", 90, chunk), expected)

    def test_unicode_digits_follow_original_text_header_parser(self):
        prefix = "[２７-Apr-２０２６ 12:00:00 UTC]".encode()
        decision, category = retention.keep_header(prefix, "php", NOW, False)
        try:
            expected = dt.datetime.strptime("２７-Apr-２０２６ 12:00:00", "%d-%b-%Y %H:%M:%S") >= NOW
        except ValueError:
            expected = True
        self.assertEqual(decision, expected)
        self.assertIn(category, ("dated", "invalid_date"))

    def test_huge_newline_free_line_has_bounded_reader_and_memory(self):
        class Generated:
            remaining = 32 * 1024 * 1024

            def read(self, size):
                if not 0 < size <= 65536:
                    raise AssertionError("unbounded read")
                take = min(size, self.remaining)
                self.remaining -= take
                return b"x" * take

        class Sink:
            def write(self, data):
                return len(data)

        size = Generated.remaining
        tracemalloc.start()
        try:
            counts = retention.filter_bytes(Generated(), Sink(), size, "php", NOW, retention.Budget(size, size + 2097152, 30))
            self.assertLess(tracemalloc.get_traced_memory()[1], 2 * 1024 * 1024)
            self.assertEqual(counts["kept"], size)
        finally:
            tracemalloc.stop()

    def test_short_write_and_short_read_are_failures(self):
        class Short:
            def write(self, data):
                return len(data) - 1

        with self.assertRaises(retention.RetentionError):
            retention.filter_bytes(io.BytesIO(b"x"), Short(), 1, "php", NOW, retention.Budget(1, 2097152, 10))
        with self.assertRaises(retention.RetentionError):
            retention.filter_bytes(io.BytesIO(b"x"), io.BytesIO(), 2, "php", NOW, retention.Budget(2, 2097152, 10))

    def test_post_prune_trigger_boundaries(self):
        for size in (retention.TRIGGER - 1, retention.TRIGGER, retention.TRIGGER + 1):
            kept = self.filtered(b"x" * size, "php", 180)
            self.assertEqual(len(kept) >= retention.TRIGGER, size >= retention.TRIGGER)
        expired = b"[01-Jan-2000 00:00:00 UTC] " + b"x" * retention.TRIGGER
        self.assertEqual(self.filtered(expired, "php", 180), b"")


class GenerationTests(unittest.TestCase):
    def setUp(self):
        if os.environ.get("REVIEW_LOCAL") != "1":
            self.skipTest("Actual Review must explicitly set REVIEW_LOCAL=1; only unique synthetic temporary files")
        self.temp = tempfile.TemporaryDirectory(prefix="review-local-retention-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name)
        self.root.chmod(0o700)

    def log(self, name, data, mode):
        path = self.root / name
        path.write_bytes(data)
        path.chmod(mode)
        return path

    def invoke(self, *options):
        return subprocess.run([sys.executable, "-I", str(HELPER), "--directory", str(self.root),
                               "--space-budget", "8388608", *options], capture_output=True, text=True,
                              cwd=self.root, env={"PATH": "/usr/bin:/bin", "LC_ALL": "C"}, timeout=10)

    def generations(self):
        return sorted(self.root.glob("cas-retention-*"))

    def test_preopened_and_lock_waiting_descriptors_preserve_acknowledged_markers(self):
        import fcntl
        path = self.log("php-errors.log", b"ack-before\n", 0o644)
        old = path.open("ab", buffering=0)
        self.addCleanup(old.close)
        fcntl.flock(old.fileno(), fcntl.LOCK_EX)
        before = os.fstat(old.fileno())
        completed = self.invoke()
        self.assertEqual(completed.returncode, 3, completed.stderr)
        raw = self.generations()[0] / "php.raw"
        self.assertEqual(raw.stat().st_ino, before.st_ino)
        self.assertNotEqual(path.stat().st_ino, before.st_ino)
        fcntl.flock(old.fileno(), fcntl.LOCK_UN)
        old.write(b"ack-old-fd\n")
        with path.open("ab", buffering=0) as new:
            new.write(b"ack-new-fd\n")
        published = raw.read_bytes() + path.read_bytes()
        for marker in (b"ack-before\n", b"ack-old-fd\n", b"ack-new-fd\n"):
            self.assertEqual(published.count(marker), 1)
        receipts = [json.loads(p.read_text()) for p in self.generations()[0].glob("receipt-*.json")]
        self.assertTrue(any(r.get("logs", [{}])[0].get("writer_closure") == "UNPROVEN" for r in receipts if r.get("logs")))
        self.assertEqual(path.stat().st_mode & 0o777, 0o644)

    def test_php_absent_does_not_skip_audit_and_repeated_capture_is_unique(self):
        audit = self.log("audit.log", b"audit-first\n", 0o600)
        self.assertEqual(self.invoke().returncode, 3)
        audit.write_bytes(b"audit-second\n")
        self.assertEqual(self.invoke().returncode, 3)
        generations = self.generations()
        self.assertEqual(len(generations), 2)
        self.assertEqual({(p / "audit.raw").read_bytes() for p in generations}, {b"audit-first\n", b"audit-second\n"})
        self.assertEqual(audit.stat().st_mode & 0o777, 0o600)
        self.assertEqual(audit.stat().st_uid, os.getuid())

    def test_over_budget_php_preserved_but_audit_processed(self):
        php = self.log("php-errors.log", b"x" * 256, 0o644)
        before = php.stat().st_ino
        self.log("audit.log", b"audit\n", 0o600)
        self.assertEqual(self.invoke("--max-bytes", "128").returncode, 3)
        self.assertEqual(php.stat().st_ino, before)
        self.assertEqual(php.read_bytes(), b"x" * 256)
        self.assertEqual((self.generations()[0] / "audit.raw").read_bytes(), b"audit\n")

    def test_unknown_legacy_archive_held_and_incident_archive_never_selected(self):
        incident = self.log("php-errors-incident-20261019-kept-lines.log.gz", b"private incident", 0o600)
        archive = self.log("php-errors.log.2000-01-01", b"unknown archive", 0o644)
        self.assertEqual(self.invoke().returncode, 3)
        self.assertEqual(incident.read_bytes(), b"private incident")
        self.assertEqual(archive.read_bytes(), b"unknown archive")
        self.assertFalse(self.generations())

    def test_symlink_input_refused_without_touching_target(self):
        target = self.log("unrelated", b"not a log", 0o600)
        (self.root / "audit.log").symlink_to(target)
        self.assertEqual(self.invoke().returncode, 3)
        self.assertEqual(target.read_bytes(), b"not a log")
        self.assertTrue((self.root / "audit.log").is_symlink())

    def test_maintenance_lock_refuses_second_run(self):
        import fcntl
        path = self.log("php-errors.log", b"unchanged", 0o644)
        with (self.root / ".cas-retention.lock").open("w+b") as lock:
            os.chmod(lock.name, 0o600)
            fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
            self.assertEqual(self.invoke().returncode, 3)
        self.assertEqual(path.read_bytes(), b"unchanged")
        self.assertFalse(self.generations())

    def test_growth_during_filter_remains_in_raw_not_a_false_clean_result(self):
        self.log("php-errors.log", b"before\n", 0o644)
        os.mkdir(self.root / "owned-generation", 0o700)
        root = os.open(self.root, os.O_RDONLY | os.O_DIRECTORY)
        generation = os.open(self.root / "owned-generation", os.O_RDONLY | os.O_DIRECTORY)
        original_filter = retention.filter_bytes

        def append_then_filter(*args):
            with (self.root / "owned-generation/php.raw").open("ab", buffering=0) as writer:
                writer.write(b"ack-during-filter\n")
            return original_filter(*args)

        try:
            with patch.object(retention, "filter_bytes", side_effect=append_then_filter):
                result = retention.capture(root, generation, "php", "php-errors.log", 180, 0o644,
                                           NOW, retention.Budget(1024, 8388608, 10))
        finally:
            os.close(generation)
            os.close(root)
        self.assertTrue(result["source_changed"])
        self.assertEqual(result["state"], "RETENTION_EXCEPTION")
        self.assertFalse(result["source_retired"])
        self.assertFalse(result["output_published"])
        self.assertEqual((self.root / "owned-generation/php.raw").read_bytes(), b"before\nack-during-filter\n")
        self.assertEqual((self.root / "owned-generation/php.candidate").read_bytes(), b"before\n")

    def test_deadline_and_space_refuse_before_capture(self):
        for space, seconds in ((1, 10), (8388608, -1)):
            with self.assertRaises(retention.RetentionError):
                retention.Budget(1024, space, seconds).admit(1)

    def test_failed_filter_keeps_raw_and_removes_only_owned_partial(self):
        self.log("php-errors.log", b"original\n", 0o644)
        os.mkdir(self.root / "owned-generation", 0o700)
        root = os.open(self.root, os.O_RDONLY | os.O_DIRECTORY)
        generation = os.open(self.root / "owned-generation", os.O_RDONLY | os.O_DIRECTORY)
        try:
            with patch.object(retention, "filter_bytes", side_effect=OSError("synthetic ENOSPC")):
                with self.assertRaises(OSError):
                    retention.capture(root, generation, "php", "php-errors.log", 180, 0o644,
                                      NOW, retention.Budget(1024, 8388608, 10))
        finally:
            os.close(generation)
            os.close(root)
        self.assertEqual((self.root / "owned-generation/php.raw").read_bytes(), b"original\n")
        self.assertFalse((self.root / "owned-generation/php.candidate").exists())
        self.assertEqual((self.root / "php-errors.log").read_bytes(), b"")


if __name__ == "__main__":
    unittest.main()
