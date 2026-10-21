#!/usr/bin/env python3
"""Private course files, end to end on the local test site (cas-local).

Needs the local stack (container cas-local-wordpress-1 on 127.0.0.1:18089) and Tutor free.
It copies the module into the local site as a temporary mu-plugin, creates synthetic
students, course, topic, lesson and attachments, runs the attachment hook, then checks:
logged-out 403; enrolled 200 with a matching sha256; not enrolled 403; administrator 200;
the lesson's attachment resolves to its course; direct uploads URLs (original and every
image size) are gone; running the hook a second time changes nothing.

Everything it creates is removed at the end. Application passwords are kept in memory only.
Run: python3 tests/test_private_course_files.py
"""
import base64
import hashlib
import json
import os
import subprocess
import sys
import unittest
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MODULE = ROOT / "wp-content/themes/hello-elementor-child/inc/cas-private-files.php"
CONTAINER = "cas-local-wordpress-1"
BASE = "http://127.0.0.1:18089"


def php(script: str) -> str:
    result = subprocess.run(
        ["docker", "exec", "-i", "-u", "www-data", CONTAINER, "php"], input="<?php\n" + script,
        capture_output=True, text=True, timeout=180,
    )
    if result.returncode:
        raise RuntimeError("local php failed: " + result.stderr[-800:])
    return result.stdout


class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def ensure_private_root() -> None:
    """The private root sits beside public_html, owned by the site user; create it that way locally."""
    subprocess.run(["docker", "exec", "-u", "root", CONTAINER, "sh", "-c",
                    "mkdir -p /var/www/cas-private && chown www-data:www-data /var/www/cas-private"], check=True)


def session(login: str | None, password: str | None):
    """An opener with the WordPress login cookie for login/password, or an anonymous opener."""
    import http.cookiejar
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), _NoRedirect)
    if login:
        body = urllib.parse.urlencode({"log": login, "pwd": password, "wp-submit": "Log In",
                                       "redirect_to": BASE + "/", "testcookie": "1"}).encode()
        opener.open(urllib.request.Request(BASE + "/wp-login.php", data=body), timeout=30)
    return opener


def fetch(url: str, opener=None) -> tuple[int, bytes]:
    opener = opener or session(None, None)
    try:
        with opener.open(url, timeout=30) as resp:
            return resp.status, resp.read()
    except urllib.error.HTTPError as err:
        return err.code, b""


def sha(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


SETUP = r'''
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
if (!is_plugin_active('tutor/tutor.php')) { activate_plugin('tutor/tutor.php', '', false, true); }

function w1_user($login, $role) {
    $uid = username_exists($login);
    if (!$uid) { $uid = wp_create_user($login, wp_generate_password(40), $login . '@example.test'); }
    (new WP_User($uid))->set_role($role);
    $pw = wp_generate_password(32, false);
    wp_set_password($pw, $uid);
    return array('id' => $uid, 'pw' => $pw);
}
$enrolled = w1_user('w1enrolled', 'subscriber');
$outsider = w1_user('w1outsider', 'subscriber');
$admin    = w1_user('w1admin', 'administrator');

$course = wp_insert_post(array('post_type' => 'courses', 'post_status' => 'publish', 'post_title' => 'W1 private test course'));
$topic  = wp_insert_post(array('post_type' => 'topics', 'post_status' => 'publish', 'post_parent' => $course, 'post_title' => 'W1 topic'));
$lesson = wp_insert_post(array('post_type' => 'lesson', 'post_status' => 'publish', 'post_parent' => $topic, 'post_title' => 'W1 lesson'));
wp_insert_post(array('post_type' => 'tutor_enrolled', 'post_status' => 'completed', 'post_author' => $enrolled['id'],
    'post_parent' => $course, 'post_title' => 'W1 enrolment'));

$dir = wp_upload_dir();
$test_dir = $dir['basedir'] . '/w1test';
wp_mkdir_p($test_dir);

function w1_pdf($name, $test_dir) {
    $path = $test_dir . '/' . $name;
    file_put_contents($path, "%PDF-1.4\n% w1 synthetic test file " . $name . "\n%%EOF\n");
    $id = wp_insert_attachment(array('post_mime_type' => 'application/pdf', 'post_title' => $name,
        'post_status' => 'inherit'), $path);
    return $id;
}
$pdf_course = w1_pdf('course-notes.pdf', $test_dir);
$pdf_lesson = w1_pdf('lesson-sheet.pdf', $test_dir);

$img_path = $test_dir . '/w1-picture.png';
$im = imagecreatetruecolor(900, 600);
imagefill($im, 0, 0, imagecolorallocate($im, 200, 120, 60));
imagepng($im, $img_path);
$img = wp_insert_attachment(array('post_mime_type' => 'image/png', 'post_title' => 'w1-picture',
    'post_status' => 'inherit'), $img_path);
wp_update_attachment_metadata($img, wp_generate_attachment_metadata($img, $img_path));

$info = array(
    'enrolled' => $enrolled, 'outsider' => $outsider, 'admin' => $admin,
    'course' => $course, 'lesson' => $lesson, 'pdf_course' => $pdf_course, 'pdf_lesson' => $pdf_lesson, 'img' => $img,
    'pdf_course_path' => get_attached_file($pdf_course), 'pdf_lesson_path' => get_attached_file($pdf_lesson),
    'img_path' => get_attached_file($img),
);
$meta = wp_get_attachment_metadata($img);
$info['sizes'] = array();
foreach ((array) $meta['sizes'] as $name => $s) {
    $info['sizes'][$name] = array('file' => $s['file'], 'path' => dirname(get_attached_file($img)) . '/' . $s['file']);
}
$info['public_urls_before'] = array(
    'pdf_course' => wp_get_attachment_url($pdf_course), 'img' => wp_get_attachment_url($img),
);
$info['hashes_before'] = array(
    'pdf_course' => hash_file('sha256', $info['pdf_course_path']),
    'pdf_lesson' => hash_file('sha256', $info['pdf_lesson_path']),
    'img_full' => hash_file('sha256', $info['img_path']),
);
foreach ($info['sizes'] as $name => $s) { $info['hashes_before']['size_' . $name] = hash_file('sha256', $s['path']); }

// Tutor writes the attachment lists; this is the path the hook must catch.
update_post_meta($course, '_tutor_attachments', array($pdf_course, $img));
update_post_meta($lesson, '_tutor_attachments', array($pdf_lesson));

$info['after_first'] = array(
    'pdf_course_private' => (bool) get_post_meta($pdf_course, '_cas_private', true),
    'pdf_lesson_private' => (bool) get_post_meta($pdf_lesson, '_cas_private', true),
    'img_private'        => (bool) get_post_meta($img, '_cas_private', true),
    'public_path_gone'   => !file_exists($info['pdf_course_path']) && !file_exists($info['img_path']),
    'url_course_pdf'     => wp_get_attachment_url($pdf_course),
    'courses_of_pdf_lesson' => get_post_meta($pdf_lesson, '_cas_private_courses', true),
);

// Second run of the same write: must be a no-op.
$snapshot = function () use ($pdf_course, $pdf_lesson, $img, $info) {
    $out = array(get_attached_file($pdf_course), get_attached_file($pdf_lesson), get_attached_file($img));
    $out[] = (int) filemtime(get_attached_file($pdf_course));
    foreach (glob(dirname(get_attached_file($img)) . '/*') as $f) { $out[] = $f . ':' . filemtime($f) . ':' . hash_file('sha256', $f); }
    return $out;
};
$before_second = $snapshot();
update_post_meta($course, '_tutor_attachments', array($pdf_course, $img));
update_post_meta($lesson, '_tutor_attachments', array($pdf_lesson));
$info['second_run_noop'] = ($before_second === $snapshot());

echo json_encode($info);
'''


CLEANUP = r'''
require '/var/www/html/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/user.php';
require_once ABSPATH . 'wp-admin/includes/post.php';
foreach (array('w1enrolled', 'w1outsider', 'w1admin') as $login) {
    $uid = username_exists($login);
    if ($uid) { wp_delete_user($uid); }
}
$w1_titles = array('W1 private test course', 'W1 topic', 'W1 lesson', 'W1 enrolment',
    'course-notes.pdf', 'lesson-sheet.pdf', 'w1-picture');
foreach (array('courses', 'topics', 'lesson', 'tutor_enrolled', 'attachment') as $type) {
    foreach (get_posts(array('post_type' => $type, 'post_status' => 'any', 'numberposts' => -1)) as $post) {
        if (in_array($post->post_title, $w1_titles, true)) { wp_delete_post($post->ID, true); }
    }
}
$dir = wp_upload_dir();
foreach (glob($dir['basedir'] . '/w1test/*') as $f) { @unlink($f); }
@rmdir($dir['basedir'] . '/w1test');
$priv = dirname(untrailingslashit(ABSPATH)) . '/cas-private';
if (is_dir($priv)) {
    foreach (glob($priv . '/*/*') as $f) { @unlink($f); }
    foreach (glob($priv . '/*') as $d) { @rmdir($d); }
    @rmdir($priv);
}
echo "cleaned\n";
'''


class PrivateCourseFilesTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        # The module is loaded by the theme (functions.php); do not copy it into mu-plugins, that redeclares it.
        ensure_private_root()
        cls.info = json.loads(php(SETUP))

    @classmethod
    def tearDownClass(cls) -> None:
        php(CLEANUP)

    LOGINS = {"enrolled": "w1enrolled", "outsider": "w1outsider", "admin": "w1admin"}
    _sessions: dict = {}

    def opener(self, who: str):
        if who == "anon":
            return session(None, None)
        if who not in self._sessions:
            self._sessions[who] = session(self.LOGINS[who], self.info[who]["pw"])
        return self._sessions[who]

    def handler(self, attachment: int, who: str = "anon", size: str = "") -> tuple[int, bytes]:
        url = f"{BASE}/?cas_private_file={attachment}" + (f"&size={size}" if size else "")
        return fetch(url, self.opener(who))

    def test_hook_moved_originals_and_sizes_out_of_public_uploads(self) -> None:
        self.assertTrue(self.info["after_first"]["pdf_course_private"])
        self.assertTrue(self.info["after_first"]["pdf_lesson_private"])
        self.assertTrue(self.info["after_first"]["img_private"])
        self.assertTrue(self.info["after_first"]["public_path_gone"])
        self.assertEqual(self.info["after_first"]["url_course_pdf"], f"{BASE}/?cas_private_file={self.info['pdf_course']}")

    def test_logged_out_is_refused(self) -> None:
        status, _ = self.handler(self.info["pdf_course"])
        self.assertEqual(status, 403)
        status, _ = self.handler(self.info["img"], size="thumbnail")
        self.assertEqual(status, 403)

    def test_enrolled_student_gets_the_file_with_matching_sha(self) -> None:
        status, body = self.handler(self.info["pdf_course"], "enrolled")
        self.assertEqual(status, 200)
        self.assertEqual(sha(body), self.info["hashes_before"]["pdf_course"])

    def test_enrolled_student_gets_a_lesson_attachment_through_its_course(self) -> None:
        status, body = self.handler(self.info["pdf_lesson"], "enrolled")
        self.assertEqual(status, 200)
        self.assertEqual(sha(body), self.info["hashes_before"]["pdf_lesson"])

    def test_enrolled_student_gets_an_image_size_with_matching_sha(self) -> None:
        for name in self.info["sizes"]:
            with self.subTest(size=name):
                status, body = self.handler(self.info["img"], "enrolled", size=name)
                self.assertEqual(status, 200)
                self.assertEqual(sha(body), self.info["hashes_before"]["size_" + name])

    def test_student_not_enrolled_is_refused(self) -> None:
        status, _ = self.handler(self.info["pdf_course"], "outsider")
        self.assertEqual(status, 403)

    def test_administrator_gets_the_file(self) -> None:
        status, body = self.handler(self.info["pdf_course"], "admin")
        self.assertEqual(status, 200)
        self.assertEqual(sha(body), self.info["hashes_before"]["pdf_course"])

    def test_direct_uploads_urls_are_gone_for_original_and_every_size(self) -> None:
        base = self.info["public_urls_before"]["img"]
        candidates = [self.info["public_urls_before"]["pdf_course"]]
        candidates.append(base)
        for name, s in self.info["sizes"].items():
            candidates.append(base.replace(base.rsplit("/", 1)[1], s["file"]))
        for url in candidates:
            with self.subTest(url=url.rsplit("/", 1)[1]):
                status, _ = fetch(url)
                self.assertIn(status, (403, 404))

    def test_hook_second_run_is_a_noop(self) -> None:
        self.assertTrue(self.info["second_run_noop"])


class PrivateDeleteRuntimeTests(unittest.TestCase):
    """A real wp_delete_attachment() on the local site removes only that attachment's folder."""

    @classmethod
    def setUpClass(cls) -> None:
        ensure_private_root()
        cls.info = json.loads(php(SETUP))

    @classmethod
    def tearDownClass(cls) -> None:
        php(CLEANUP)

    def test_deleting_a_private_image_removes_its_folder_and_keeps_the_rest(self) -> None:
        script = f"""
require '/var/www/html/wp-load.php';
$root = dirname(untrailingslashit(ABSPATH)) . '/cas-private';
$img = {int(self.info["img"])};
$lesson = {int(self.info["pdf_lesson"])};
$course = {int(self.info["pdf_course"])};
$before = is_dir($root . '/' . $img);
wp_delete_attachment($img, true);
echo json_encode(array(
    'folder_before' => $before,
    'folder_after' => is_dir($root . '/' . $img),
    'lesson_file' => is_file($root . '/' . $lesson . '/lesson-sheet.pdf'),
    'course_file' => is_file($root . '/' . $course . '/course-notes.pdf'),
    'root' => is_dir($root),
));
"""
        result = json.loads(php(script))
        self.assertTrue(result["folder_before"])
        self.assertFalse(result["folder_after"])
        self.assertTrue(result["lesson_file"])
        self.assertTrue(result["course_file"])
        self.assertTrue(result["root"])


if __name__ == "__main__":
    if not MODULE.exists():
        sys.exit("module missing")
    unittest.main(verbosity=2)
