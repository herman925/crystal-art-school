#!/usr/bin/env python3
"""PREPARED isolated negative guard cases; actual Review executes, Development syntax-checks only.
The PHP harness deliberately does NOT bootstrap WP/Tutor or override a live plugin version.
"""
import json
from pathlib import Path
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
MODULE = ROOT / "wp-content/themes/hello-elementor-child/inc/cas-content-builder.php"
HARNESS = ROOT / "tests/fixtures/builder-sort-compat-harness.php"


class BuilderSortCompatibilityUnit(unittest.TestCase):
    def test_actual_version_source_pins_and_prewrite_live_validator_are_explicit(self):
        source = MODULE.read_text()
        compatible = source.split("function cas_builder_sort_compatible", 1)[1].split("/** Only a fully checked", 1)[0]
        self.assertIn("'4.1.3' !== TUTOR_VERSION", compatible)
        self.assertIn("'4.1.3' !== ( $header['Version'] ?? '' )", compatible)
        self.assertIn("ff2420312a655fda5c853d4e8fa5e270ec8fed835f369117b2adba7544855467", compatible)
        self.assertIn("b5fbf74b3747b742f5f75abb3ce9043ca566670aead26fa5908087f4cacf4cf9", compatible)
        validate = source.split("function cas_builder_validate_content_order", 1)[1].split("/** The same vendor saver", 1)[0]
        self.assertLess(validate.index("cas_builder_sort_compatible"), validate.index("->get_course_contents"))
        self.assertIn("$validator->invoke( new \\TUTOR\\Course( false )", validate)
        early = source.split("/** Resolve curriculum ownership", 1)[0]
        self.assertIn("cas_builder_assert_course_sort( $id )", early)

    def test_unsupported_version_structure_or_context_stops_before_vendor_or_caller_write(self):
        for mode in ("unsupported_version", "unsupported_structure", "unsupported_context"):
            with self.subTest(mode=mode):
                result = subprocess.run(["php", str(HARNESS), str(MODULE), mode], text=True, capture_output=True, timeout=30)
                self.assertEqual(result.returncode, 0, result.stderr)
                state = json.loads(result.stdout)
                self.assertEqual(state["status"], 503)
                self.assertEqual(state["before"], state["after"])
                self.assertEqual(state["native_getter_calls"], 0, "never call an unsupported vendor getter first")
                if mode == "unsupported_version":
                    self.assertEqual(state["context_reads"], 0, "version mismatch must reject before even probing request context")
                self.assertEqual(state["scope"], [])


if __name__ == "__main__":
    unittest.main(verbosity=2)
