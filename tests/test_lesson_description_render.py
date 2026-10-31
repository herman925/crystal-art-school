"""Render the actual description fragment with controlled data; no native WP/Tutor proof."""
import json
import subprocess
import unittest
from pathlib import Path

SOURCE = Path(__file__).resolve().parents[1] / "wp-content/themes/hello-elementor-child/inc/cas-lesson.php"
PHP = r'''
function esc_html($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function wp_kses_post($s) { return $s; } // Deliberately not native KSES proof.
function wpautop($s) { return '<p>' . $s . '</p>'; }
function get_post_field($field, $id) { return $id === 53 ? $GLOBALS['fixture_body'] : 'COURSE_BODY_MUST_NOT_LEAK'; }
function wp_get_attachment_image($id, $size, $icon, $attrs) {
    return '<img data-id="' . $id . '" class="' . esc_attr($attrs['class']) . '">';
}
$source = file_get_contents($argv[1]);
$assignment_start = strpos($source, '$lesson_body =');
$assignment_end = strpos($source, '$is_tall', $assignment_start);
$assignment = substr($source, $assignment_start, $assignment_end - $assignment_start);
$start = strpos($source, '<div class="unit-panel">');
$end = strpos($source, '<?php if ( $enrolled && $current ) : ?>', $start);
$fragment = substr($source, $start, $end - $start);
$results = array();
foreach (array('with-cover' => array('LESSON_BODY_MARKER', 81), 'no-cover' => array('LESSON_BODY_MARKER', 0), 'empty' => array('', 81)) as $name => [$body, $cover]) {
    $GLOBALS['fixture_body'] = $body;
    $lesson_id = 53; $course_id = 21; $title = 'Course title';
    $current = array('title' => 'Lesson title', 'thumb' => $cover, 'sub' => $body);
    $position = 0; $phased = false;
    eval($assignment);
    ob_start(); eval('?>' . $fragment); $results[$name] = ob_get_clean();
}
echo json_encode($results, JSON_THROW_ON_ERROR);
'''


class LessonDescriptionRender(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        result = subprocess.run(
            ["php", "-d", "display_errors=stderr", "-r", PHP, str(SOURCE)],
            capture_output=True, text=True, timeout=10, check=True,
        )
        if result.stderr:
            raise AssertionError(result.stderr)
        cls.rendered = json.loads(result.stdout)

    def test_each_description_uses_lesson_text_once(self):
        for case in ("with-cover", "no-cover"):
            self.assertEqual(self.rendered[case].count("LESSON_BODY_MARKER"), 1)
            self.assertNotIn("COURSE_BODY_MUST_NOT_LEAK", self.rendered[case])

    def test_cover_uses_the_lesson_and_is_omitted_when_absent(self):
        self.assertIn('data-id="81" class="cd-cover"', self.rendered["with-cover"])
        self.assertNotIn("<img", self.rendered["no-cover"])

    def test_empty_lesson_has_no_description_or_course_fallback(self):
        self.assertNotIn('id="course-desc"', self.rendered["empty"])
        self.assertNotIn("COURSE_BODY_MUST_NOT_LEAK", self.rendered["empty"])
        self.assertNotIn("<img", self.rendered["empty"])

    def test_course_heading_survives_empty_lesson_text(self):
        for html in self.rendered.values():
            self.assertEqual(html.count("<h1>Course title</h1>"), 1)


if __name__ == "__main__":
    unittest.main(verbosity=2)
