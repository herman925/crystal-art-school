#!/usr/bin/env python3
"""Run the #151 recovery accessibility helpers in PHP with Tutor 4.1.0 markup. Offline, cached image only.

Fixtures copy the pinned Tutor 4.1.0 templates retrieve-password.php and form-retrieve-password.php.
Tutor's alert markup is a stand-in, because its Alert component was not retrieved.
"""
from __future__ import annotations

import json
import re
import subprocess
import unittest
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
ACCOUNT = ROOT / 'wp-content/themes/hello-elementor-child/inc/cas-account.php'
HELPERS = ('cas_account_recovery_status_active', 'cas_account_recovery_alert', 'cas_account_recovery_accessible')
SUCCESS = '<p>If an account matches those details, a reset email will be sent.</p>'
ALERT = '<div class="tutor-alert tutor-alert-danger"><p>Invalid username or email.</p></div>'

RETRIEVE = (
    '<div class="tutor-card"><form method="post" class="tutor-forgot-password-form tutor-ResetPassword lost_reset_password">'
    '<div class="tutor-mb-8">{alert}</div>'
    '<input type="hidden" name="tutor_action" value="tutor_retrieve_password">'
    '<div class="tutor-form-group"><label class="tutor-block tutor-mb-2">Username or email</label>'
    '<div class="tutor-input-field tutor-mb-8"><input class="tutor-form-control tutor-input" type="text" name="user_login" id="user_login" autocomplete="username"></div></div>'
    '<button type="submit" class="tutor-btn tutor-btn-primary tutor-btn-block">Reset password</button></form></div>'
)
RESET = (
    '<div class="tutor-card"><form method="post" class="tutor-reset-password-form tutor-ResetPassword lost_reset_password">'
    '<input type="hidden" name="tutor_action" value="tutor_process_reset_password">'
    '<input type="hidden" name="reset_key" value="k">'
    '<div class="tutor-form-group"><label class="tutor-block tutor-mb-2">Password</label>'
    '<div class="tutor-input-field tutor-mb-8"><input class="tutor-form-control tutor-input" type="password" name="password" id="password"></div></div>'
    '<div class="tutor-form-group"><label class="tutor-block tutor-mb-2">Confirm Password</label>'
    '<div class="tutor-input-field tutor-mb-8"><input class="tutor-form-control tutor-input" type="password" name="confirm_password" id="confirm_password"></div></div>'
    '<button type="submit" class="tutor-btn tutor-btn-primary tutor-btn-block">Reset password</button></form></div>'
)
CUSTOM_FOR = RESET.replace(
    '<label class="tutor-block tutor-mb-2">Password',
    '<label class="tutor-block tutor-mb-2" for="custom">Password',
)


def helper_source(source: str) -> str:
    chunks = []
    for name in HELPERS:
        match = re.search(r'function ' + name + r'\(.*?\n\}', source, re.DOTALL)
        if not match:
            raise AssertionError(f'helper {name} is missing')
        chunks.append(match.group(0))
    return '\n'.join(chunks)


def run_php(script: str) -> dict:
    result = subprocess.run(
        ['docker', 'run', '--rm', '--pull=never', '--network', 'none', '-i',
         '--entrypoint', 'php', 'wordpress:php8.3-apache', '-n'],
        input='<?php\n' + script, capture_output=True, text=True, timeout=60,
    )
    if result.returncode:
        raise AssertionError('offline PHP accessibility test failed: ' + (result.stderr or result.stdout)[-800:])
    return json.loads(result.stdout)


class RecoveryFormAccessibilityTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        source = ACCOUNT.read_text(encoding='utf-8')
        fixtures = {
            'retrieve': RETRIEVE,
            'reset': RESET,
            'alert': ALERT,
            'success': SUCCESS,
            'custom_for': CUSTOM_FOR,
        }
        php = 'function esc_attr($text){return htmlspecialchars($text,ENT_QUOTES);}\n'
        php += helper_source(source) + '\n'
        literal = json.dumps(fixtures).replace('\\', '\\\\').replace("'", "\\'")
        php += "$f=json_decode('" + literal + "', true);\n"
        php += r'''
function render_alert($alert){ob_start();cas_account_recovery_alert($alert);return ob_get_clean();}
$out=array();
cas_account_recovery_status_active(false);
$out['retrieve_plain']=cas_account_recovery_accessible(str_replace('{alert}','',$f['retrieve']));
$out['retrieve_error']=cas_account_recovery_accessible(str_replace('{alert}',$f['alert'],$f['retrieve']));
cas_account_recovery_status_active(true);
$out['retrieve_success']=cas_account_recovery_accessible(str_replace('{alert}',$f['success'],$f['retrieve']));
cas_account_recovery_status_active(false);
$out['reset_plain']=cas_account_recovery_accessible($f['reset']);
$out['reset_error']=cas_account_recovery_accessible(render_alert($f['alert']).$f['reset']);
$out['empty_alert']=render_alert('   ');
$out['existing_for']=cas_account_recovery_accessible($f['custom_for']);
echo json_encode($out);
'''
        cls.results = run_php(php)

    def test_labels_name_their_fields_on_both_forms(self) -> None:
        self.assertIn('<label class="tutor-block tutor-mb-2" for="user_login">', self.results['retrieve_plain'])
        self.assertIn('<label class="tutor-block tutor-mb-2" for="password">', self.results['reset_plain'])
        self.assertIn('<label class="tutor-block tutor-mb-2" for="confirm_password">', self.results['reset_plain'])

    def test_no_error_adds_no_container_and_no_description(self) -> None:
        html = self.results['retrieve_plain']
        self.assertNotIn('cas-recovery-errors', html)
        self.assertNotIn('aria-describedby', html)
        self.assertNotIn('role="', html)

    def test_retrieve_error_is_focusable_alert_and_described(self) -> None:
        html = self.results['retrieve_error']
        self.assertIn('id="cas-recovery-errors"', html)
        self.assertIn('role="alert"', html)
        self.assertIn('tabindex="-1"', html)
        self.assertIn('aria-describedby="cas-recovery-errors"', html)
        self.assertIn('Invalid username or email.', html)

    def test_generic_success_is_status_not_alert(self) -> None:
        html = self.results['retrieve_success']
        self.assertIn('role="status"', html)
        self.assertNotIn('role="alert"', html)
        self.assertIn(SUCCESS, html)

    def test_reset_error_container_from_feedback_helper(self) -> None:
        html = self.results['reset_error']
        self.assertIn('id="cas-recovery-errors"', html)
        self.assertIn('role="alert"', html)
        self.assertEqual(html.count('id="cas-recovery-errors"'), 1)
        self.assertIn('aria-describedby="cas-recovery-errors"', html)

    def test_empty_alert_prints_nothing(self) -> None:
        self.assertEqual(self.results['empty_alert'], '')

    def test_existing_label_association_is_not_overwritten(self) -> None:
        self.assertIn('for="custom"', self.results['existing_for'])
        self.assertNotIn('for="password"', self.results['existing_for'])


if __name__ == '__main__':
    unittest.main(verbosity=2)
