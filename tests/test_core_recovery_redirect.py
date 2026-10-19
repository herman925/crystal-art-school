#!/usr/bin/env python3
"""Exercise the actual theme callback in PHP stubs, without network or WordPress writes.

Uses a cached PHP image only. This is not an HTTP/runtime acceptance test.
"""
from __future__ import annotations

import json
from pathlib import Path
import re
import subprocess
import unittest

ROOT = Path(__file__).resolve().parents[1]
ACCOUNT = ROOT / 'wp-content/themes/hello-elementor-child/inc/cas-account.php'
CALLBACK = 'cas_account_redirect_password_request'


def callback_source(source: str) -> tuple[str, str]:
    match = re.search(r'function ' + CALLBACK + r'\(\)\s*\{.*?^\}', source, re.MULTILINE | re.DOTALL)
    if not match:
        raise AssertionError('core recovery redirect callback is missing')
    hooks = re.findall(r"^add_action\( '[^']+', '" + CALLBACK + r"' \);", source, re.MULTILINE)
    return match.group(0), '\n'.join(hooks)


def run_php(script: str) -> dict:
    result = subprocess.run(
        ['docker', 'run', '--rm', '--pull=never', '--network', 'none', '-i',
         '--entrypoint', 'php', 'wordpress:php8.3-apache', '-n'],
        input='<?php\n' + script, capture_output=True, text=True, timeout=60,
    )
    if result.returncode:
        raise AssertionError('offline PHP callback test failed: ' + result.stderr[-800:])
    return json.loads(result.stdout)


class CoreRecoveryRedirectTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls) -> None:
        cls.source = ACCOUNT.read_text(encoding='utf-8')
        callback, hooks = callback_source(cls.source)
        cls.php = r'''
$hooks=array();$fixture=array();
class CapturedRedirect extends Exception {
    public $target;public $status;
    public function __construct($target,$status){$this->target=$target;$this->status=$status;}
}
function add_action($name,$callback){$GLOBALS['hooks'][$name]=$callback;}
function cas_account_page_ids(){return array('dashboard'=>$GLOBALS['fixture']['id']??42);}
function get_post($id){return (object)array('post_type'=>$GLOBALS['fixture']['type']??'page','post_status'=>$GLOBALS['fixture']['status']??'publish');}
function home_url($path){return $GLOBALS['fixture']['home']??'https://school.example.test/';}
function add_query_arg($key,$value,$url){return $url.(strpos($url,'?')===false?'?':'&').rawurlencode($key).'='.rawurlencode($value);}
function wp_login_url(){return 'https://school.example.test/wp-login.php';}
function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
function wp_unslash($value){return stripslashes($value);}
function esc_html__($message,$domain){return htmlspecialchars($message,ENT_QUOTES);}
function tutor_flash_set($type,$message){$GLOBALS['flashes'][$type]=$message;}
function wp_safe_redirect($target,$status){
    if(!empty($GLOBALS['fixture']['redirect_failure'])){return false;}
    throw new CapturedRedirect($target,$status);
}
function probe($action,$method,$fixture=array(),$identity='known@example.test',$native=0,$error=null){
    $GLOBALS['fixture']=$fixture;$GLOBALS['native_login']=$native;$GLOBALS['flashes']=array();
    $_SERVER['REQUEST_METHOD']=$method;
    $_GET=array('action'=>$action,'redirect_to'=>'https://untrusted.example.test/');
    if(null!==$error){$_GET['error']=$error;}
    $_POST=array('action'=>$action,'user_login'=>$identity,'redirect_to'=>'https://untrusted.example.test/','pwd'=>'synthetic-must-not-forward');
    if(!isset($GLOBALS['hooks']['login_form_'.$action])){return array('fallback'=>true);}
    try{call_user_func($GLOBALS['hooks']['login_form_'.$action]);}
    catch(CapturedRedirect $r){return array('url'=>$r->target,'status'=>$r->status);}
    return array('fallback'=>true);
}
''' + callback + '\n' + hooks
        tutor = r'''
class TutorFixture {
    public function tutor_dashboard_url($route){
        if('retrieve-password'!==$route){throw new Exception('unexpected Tutor route');}
        return $GLOBALS['fixture']['target']??'https://school.example.test/dashboard/retrieve-password/';
    }
}
function tutor_utils(){return new TutorFixture();}
'''
        matrix = r'''
$out=array('matrix'=>array(),'fallbacks'=>array(),'tokens'=>array());
foreach(array('lostpassword','retrievepassword') as $action){
 foreach(array('GET','POST') as $method){
  foreach(array(0,1) as $native){
   foreach(array('known@example.test','unknown@example.test') as $identity){
    $out['matrix'][]=array('action'=>$action,'method'=>$method,'native'=>$native,'result'=>probe($action,$method,array(),$identity,$native));
   }
  }
 }
}
foreach(array(
 'no_dashboard'=>array('id'=>0),
 'draft_dashboard'=>array('status'=>'draft'),
 'wrong_post_type'=>array('type'=>'post'),
 'foreign_host'=>array('target'=>'https://untrusted.example.test/dashboard/retrieve-password/'),
 'foreign_port'=>array('target'=>'https://school.example.test:8443/dashboard/retrieve-password/'),
 'foreign_scheme'=>array('target'=>'http://school.example.test/dashboard/retrieve-password/'),
 'url_credentials'=>array('target'=>'https://name:secret@school.example.test/dashboard/retrieve-password/'),
 'core_loop'=>array('target'=>'https://school.example.test/wp-login.php?action=lostpassword'),
 'missing_target'=>array('target'=>''),
 'redirect_failure'=>array('redirect_failure'=>true)
) as $key=>$fixture){$out['fallbacks'][$key]=probe('lostpassword','POST',$fixture);}
$out['fallbacks']['wrong_method']=probe('lostpassword','PUT');
$out['local_custom_route']=probe('lostpassword','GET',array('home'=>'http://127.0.0.1:18089/','target'=>'http://127.0.0.1:18089/my-studio/retrieve-password/'));
foreach(array('rp','resetpass','login','logout','register') as $action){
 foreach(array('GET','POST') as $method){$out['tokens'][$action.'_'.$method]=probe($action,$method);}
}
$out['error_flags']=array();
foreach(array('invalidkey','expiredkey','unknown',array('invalidkey'),123,'invalidkey<script>') as $error){
 foreach(array('GET','POST') as $method){
  $result=probe('lostpassword',$method,array(),'known@example.test',0,$error);
  $out['error_flags'][]=array('error'=>$error,'method'=>$method,'result'=>$result,'flashes'=>$GLOBALS['flashes']);
 }
}
$out['invalid_route_error']=probe('lostpassword','GET',array('target'=>'https://untrusted.example.test/'),'known@example.test',0,'expiredkey');
$out['invalid_route_flashes']=$GLOBALS['flashes'];
$out['notice_existing_query']=probe('lostpassword','GET',array('target'=>'https://school.example.test/dashboard/retrieve-password/?lang=en'),'known@example.test',0,'invalidkey');
echo json_encode($out);
'''
        cls.results = run_php(cls.php + tutor + matrix)
        cls.absent_tutor = run_php(cls.php + "echo json_encode(array('GET'=>probe('lostpassword','GET'),'POST'=>probe('retrievepassword','POST')));")

    def test_only_password_request_hooks_are_registered(self) -> None:
        _, hooks = callback_source(self.source)
        self.assertEqual(set(re.findall(r"add_action\( '([^']+)'", hooks)),
                         {'login_form_lostpassword', 'login_form_retrievepassword'})

    def test_get_302_post_303_for_both_native_modes_and_identifiers(self) -> None:
        self.assertEqual(len(self.results['matrix']), 16)
        for case in self.results['matrix']:
            with self.subTest(action=case['action'], method=case['method'], native=case['native']):
                self.assertEqual(case['result'], {
                    'url': 'https://school.example.test/dashboard/retrieve-password/',
                    'status': 303 if case['method'] == 'POST' else 302,
                })

    def test_configured_same_origin_nondefault_dashboard(self) -> None:
        self.assertEqual(self.results['local_custom_route'], {
            'url': 'http://127.0.0.1:18089/my-studio/retrieve-password/', 'status': 302,
        })

    def test_invalid_configuration_and_redirect_loop_fall_back(self) -> None:
        self.assertEqual(len(self.results['fallbacks']), 11)
        for name, result in self.results['fallbacks'].items():
            with self.subTest(name=name):
                self.assertEqual(result, {'fallback': True})

    def test_tutor_absent_keeps_core_fallback_for_get_and_post(self) -> None:
        self.assertEqual(self.absent_tutor, {'GET': {'fallback': True}, 'POST': {'fallback': True}})

    def test_core_reset_token_and_other_actions_are_untouched(self) -> None:
        self.assertEqual(len(self.results['tokens']), 10)
        for name, result in self.results['tokens'].items():
            with self.subTest(name=name):
                self.assertEqual(result, {'fallback': True})

    def test_only_allowlisted_scalar_get_errors_set_fixed_feedback(self) -> None:
        self.assertEqual(len(self.results['error_flags']), 12)
        for case in self.results['error_flags']:
            with self.subTest(case=case):
                show = case['method'] == 'GET' and case['error'] in ('invalidkey', 'expiredkey')
                self.assertEqual(case['flashes'], [])
                expected = 'https://school.example.test/dashboard/retrieve-password/'
                if show:
                    expected += '?cas_reset_notice=invalid'
                self.assertEqual(case['result']['url'], expected)
        self.assertEqual(self.results['invalid_route_error'], {'fallback': True})
        self.assertEqual(self.results['invalid_route_flashes'], [])
        self.assertEqual(self.results['notice_existing_query']['url'], 'https://school.example.test/dashboard/retrieve-password/?lang=en&cas_reset_notice=invalid')

    def test_notice_renders_fixed_escaped_copy_without_session_start(self) -> None:
        match = re.search(r'function cas_account_retrieve_password_before\(\)\s*\{.*?^\}', self.source, re.MULTILINE | re.DOTALL)
        self.assertIsNotNone(match)
        self.assertNotIn('session_start', match.group(0))
        result = run_php(r'''
function cas_account_is_retrieve_password(){return true;}
function cas_account_retrieve_password_capture_active($value=null){static $active=false;if($value!==null){$active=$value;}return $active;}
function wp_unslash($value){return stripslashes($value);}
function esc_html__($message,$domain){return htmlspecialchars($message,ENT_QUOTES);}
function tutor_alert($message,$type,$echo,$component){if($echo){echo $message;}}
''' + match.group(0) + r'''
$out=array();foreach(array('invalid','expired','unknown',array('invalid'),'<script>invalid</script>') as $notice){
$_GET=array('cas_reset_notice'=>$notice);cas_account_retrieve_password_capture_active(false);
ob_start();cas_account_retrieve_password_before();$inner=ob_get_clean();ob_end_clean();$out[]=$inner;
}
echo json_encode($out);
''')
        self.assertEqual(result, ['<p>This password reset link is invalid or has expired. Please request a new link.</p>', '', '', '', ''])

    def test_callback_never_reads_or_forwards_identity_or_redirect_input(self) -> None:
        callback, _ = callback_source(self.source)
        for forbidden in ('$_POST', "$_GET['redirect_to']", "$_GET['key']", "$_GET['login']", '$_REQUEST', 'user_login', 'get_user_by', '307', '308'):
            self.assertNotIn(forbidden, callback)
        self.assertIn('wp_safe_redirect', callback)
        self.assertIn('exit;', callback)


if __name__ == '__main__':
    unittest.main(verbosity=2)
