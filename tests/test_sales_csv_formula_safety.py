#!/usr/bin/env python3
"""Pure PHP proof for CSV formula handling; no WordPress or database needed."""
import shutil
import subprocess
import unittest
from pathlib import Path

SALES = Path(__file__).parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-sales.php"


@unittest.skipUnless(shutil.which("php"), "PHP CLI is required for the pure-function check")
class SalesCsvFormulaSafety(unittest.TestCase):
    def test_formula_markers_after_whitespace_controls_and_bom_are_text(self) -> None:
        php = shutil.which("php")
        script = rf'''
        define('ABSPATH', '/');
        function add_action() {{}}
        require {str(SALES)!r};
        $cases = array(
            array('=1+1', "'=1+1"),
            array("  =1+1", "'  =1+1"),
            array("\x09@SUM(1,2)", "'\x09@SUM(1,2)"),
            array("\x01+CMD()", "'\x01+CMD()"),
            array("\xEF\xBB\xBF-HYPERLINK", "'\xEF\xBB\xBF-HYPERLINK"),
            array("\xFF=opaque", "'\xFF=opaque"),
        );
        foreach ($cases as $case) {{
            if (cas_sales_csv_text($case[0]) !== $case[1]) {{
                fwrite(STDERR, 'CSV text mismatch');
                exit(1);
            }}
        }}
        foreach (array('Crystal', 'clouds beginner', '58.00', '2026-10-23') as $plain) {{
            if (cas_sales_csv_text($plain) !== $plain) {{
                fwrite(STDERR, 'ordinary text changed');
                exit(1);
            }}
        }}
        '''
        result = subprocess.run([php, "-r", script], capture_output=True, text=True, check=False)
        self.assertEqual(result.returncode, 0, result.stderr or result.stdout)


if __name__ == "__main__":
    unittest.main()
