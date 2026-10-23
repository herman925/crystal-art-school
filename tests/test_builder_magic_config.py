#!/usr/bin/env python3
"""PREPARED for actual Review. Development performs syntax checks only; no fixture execution.
Pure boundary check, not WordPress/native-runtime or whole compatibility acceptance.
"""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
MODULE = ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-builder.php"
HARNESS = ROOT / "tests/fixtures/builder-magic-config-harness.php"


class MagicConfigPath(unittest.TestCase):
    def test_source_reads_the_magic_property_directly_once(self):
        body = MODULE.read_text().split("function cas_builder_sort_compatible", 1)[1].split("/** Only a fully checked", 1)[0]
        self.assertEqual(body.count("$plugin->path"), 1)
        self.assertIn("$path = $plugin->path;", body)
        self.assertIn("! is_string( $path ) || '' === $path", body)
        self.assertNotIn("empty( $plugin->path )", body)
        self.assertNotIn("isset( $plugin->path )", body)
        self.assertNotIn("$plugin->path ??", body)

    def test_get_without_isset_passes_only_the_path_boundary_and_invalid_values_fail(self):
        for mode in ("valid", "valid_no_slash", "empty", "null", "integer", "boolean", "array", "object"):
            with self.subTest(mode=mode):
                run = subprocess.run(["php", str(HARNESS), str(MODULE), mode], capture_output=True, text=True, timeout=30)
                self.assertEqual(run.returncode, 0, run.stderr)
                state = json.loads(run.stdout)
                self.assertEqual([state["has_get"], state["has_isset"], state["isset"], state["empty"]], [True, False, False, True])
                self.assertEqual(state["reads"], 1)
                self.assertFalse(state["result"], "the intentionally bad HEADER must still be refused by downstream guards")
                valid = mode in ("valid", "valid_no_slash")
                self.assertEqual(state["header_calls"], int(valid))
                self.assertEqual(state["header_file"], "/unit/tutor/tutor.php" if valid else None)


if __name__ == "__main__":
    unittest.main(verbosity=2)
