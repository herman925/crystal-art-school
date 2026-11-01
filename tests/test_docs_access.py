#!/usr/bin/env python3
"""Guide audience acceptance against the explicitly enabled, synthetic localhost HTTPS fixture."""
import http.cookiejar
import os
from pathlib import Path
import ssl
import unittest
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('CAS_DOCS_TEST_URL', '')
BUILD = Path(os.environ.get('CAS_DOCS_TEST_BUILD', '/scratch/app/docs-site/site/.vitepress/dist'))
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs): return None

def client():
    return urllib.request.build_opener(urllib.request.HTTPSHandler(context=ssl._create_unverified_context()),
        urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()), NoRedirect())

def fetch(op, path, data=None):
    try:
        with op.open(urllib.request.Request(BASE + path, data=data), timeout=15) as r:
            return r.status, r.read(), r.headers
    except urllib.error.HTTPError as r:
        return r.code, r.read(), r.headers

def login(name):
    op = client()
    fetch(op, '/login.php')
    data = urllib.parse.urlencode({'username':name, 'password':f'Local-{name.title()}-Guide-2026', 'next':'/'}).encode()
    status, _, _ = fetch(op, '/login.php', data)
    if status != 302: raise AssertionError('Synthetic guide login failed')
    return op

class GuideAccess(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        if BASE != 'https://localhost:18443':
            raise unittest.SkipTest('enable only the dedicated localhost synthetic guide fixture')
        cls.admin_asset = '/' + next((BUILD/'admin/assets').glob('admin_handover.md.*.js')).relative_to(BUILD/'admin').as_posix()
        cls.reader_asset = '/' + next((BUILD/'crystal/assets').glob('courses.md.*.js')).relative_to(BUILD/'crystal').as_posix()

    def test_signed_out_pages_and_assets_require_login(self):
        for path in ['/', '/courses', '/admin/handover', self.admin_asset, self.reader_asset]:
            with self.subTest(path=path):
                status, _, h = fetch(client(), path)
                self.assertEqual(status, 302)
                self.assertTrue(h.get('Location','').startswith('/login.php?next='))

    def test_crystal_cannot_fetch_admin_pages_or_chunks(self):
        op = login('crystal')
        for path in ['/admin/', '/admin/handover', '/admin/payments-and-access.html']:
            self.assertEqual(fetch(op, path)[0], 403)
        status, body, _ = fetch(op, self.admin_asset)
        self.assertEqual(status, 404)
        self.assertNotIn(b'CAS_AUDIT_EXPECTED_ADMINS', body)
        status, body, _ = fetch(op, '/?audience=admin')
        self.assertEqual(status, 200)
        self.assertNotIn(b'/admin/handover', body)

    def test_herman_has_admin_and_reader_content(self):
        op = login('herman')
        for path in ['/courses', '/admin/handover', self.admin_asset]:
            self.assertEqual(fetch(op, path)[0], 200)
        self.assertIn(b'CAS_AUDIT_EXPECTED_ADMINS', fetch(op, '/admin/handover')[1])

    def test_all_resources_are_account_checked_and_not_stored(self):
        op = login('crystal')
        for path in ['/courses', self.reader_asset]:
            status, _, headers = fetch(op, path)
            self.assertEqual(status, 200)
            self.assertIn('no-store', headers.get('Cache-Control',''))
            self.assertIn('Cookie', headers.get('Vary',''))

    def test_logout_revokes_pages_and_assets_and_switching_reader(self):
        op = login('herman')
        self.assertEqual(fetch(op, '/admin/handover')[0], 200)
        status, _, _ = fetch(op, '/logout.php')
        self.assertEqual(status, 302)
        self.assertEqual(fetch(op, '/admin/handover')[0], 302)
        self.assertEqual(fetch(op, self.admin_asset)[0], 302)
        data = urllib.parse.urlencode({'username':'crystal','password':'Local-Crystal-Guide-2026','next':'/'}).encode()
        self.assertEqual(fetch(op, '/login.php', data)[0], 302)
        self.assertEqual(fetch(op, '/admin/handover')[0], 403)
        self.assertEqual(fetch(op, self.admin_asset)[0], 404)

    def test_path_escape_never_reaches_other_edition_or_server_files(self):
        op = login('crystal')
        for path in ['/%2e%2e/admin/admin/handover.html','/_private/admin/admin/handover.html','/../users.php','/auth.php','/gate.php?edition=admin','/admin%2fhandover']:
            with self.subTest(path=path):
                status, body, _ = fetch(op, path)
                self.assertIn(status, [403,404])
                self.assertNotIn(b'CAS_AUDIT_EXPECTED_ADMINS', body)
                self.assertNotIn(b'<?php', body)

    def test_wrong_account_password_and_external_next_are_refused(self):
        op = client()
        wrong = urllib.parse.urlencode({'username':'herman','password':'Local-Crystal-Guide-2026','next':'/admin/'}).encode()
        self.assertEqual(fetch(op, '/login.php', wrong)[0], 401)
        self.assertEqual(fetch(op, '/admin/')[0], 302)
        correct = urllib.parse.urlencode({'username':'crystal','password':'Local-Crystal-Guide-2026','next':'//example.test'}).encode()
        status, _, headers = fetch(op, '/login.php', correct)
        self.assertEqual(status, 302)
        self.assertEqual(headers.get('Location'), '/')

    def test_reader_build_and_search_exclude_operator_content(self):
        self.assertFalse((BUILD/'crystal/admin').exists())
        for path in (BUILD/'crystal').rglob('*'):
            if path.is_file() and path.suffix in ['.html','.js','.json','.map','.txt']:
                text = path.read_text(errors='replace')
                self.assertNotIn('CAS_AUDIT_EXPECTED_ADMINS', text, str(path))
                self.assertNotIn('Administrator operating notes', text, str(path))
                self.assertNotIn('/admin/handover', text, str(path))
                self.assertNotIn('Herman', text, str(path))

if __name__ == '__main__': unittest.main(verbosity=2)
