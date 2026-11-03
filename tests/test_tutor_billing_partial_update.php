<?php
/** Actual BillingController + BaseController + BillingModel, with an in-memory SQL boundary.
 * php this.php BillingController.php BaseController.php BillingModel.php
 * Validation failure is injected; this does not test or repair email-validation rules.
 */
namespace Tutor\Traits {trait JsonResponse {function json_response($message,$data=null,$status=200){throw new \BillingResponse($status);}}}
namespace Tutor\Helpers {
 class HttpHelper {const STATUS_BAD_REQUEST=400,STATUS_UNPROCESSABLE_ENTITY=422,STATUS_INTERNAL_SERVER_ERROR=500;}
 class ValidationHelper {static function validate($rules,$data){return(object)['success'=>!$GLOBALS['rejectValidation'],'errors'=>[]];}}
 class QueryHelper {
  static function get_row($table,$where,...$args){return $GLOBALS['billing']===null?false:(object)$GLOBALS['billing'];}
  static function update($table,$data,$where){if($where!==['user_id'=>12])throw new \RuntimeException('wrong account');$GLOBALS['writes']++;$GLOBALS['billing']=array_merge($GLOBALS['billing'],$data);return true;}
  static function insert($table,$data){$GLOBALS['writes']++;$GLOBALS['billing']=$data;return 1;}
 }
}
namespace {
 define('ABSPATH','/synthetic/');$wpdb=(object)['prefix'=>'wp_'];class BillingResponse extends \Exception{public function __construct(public int $status){parent::__construct('response');}}
 function __($text,$domain=null){return$text;}
 function get_current_user_id(){return 12;}
 function tutor_utils(){return new class{function is_nonce_verified(){return$GLOBALS['nonce'];}function error_message(...$args){return'nonce';}function get_user_id($id=0){return$id?:12;}};}
 require $argv[2];require $argv[3];require $argv[1];
 function full_profile(){return['user_id'=>12,'billing_first_name'=>'Test','billing_last_name'=>'Learner','billing_email'=>'learner@example.test','billing_phone'=>'0000000000','billing_zip_code'=>'SW1A 1AA','billing_address'=>'Synthetic address','billing_country'=>'United Kingdom','billing_state'=>'London','billing_city'=>'London'];}
 function save_case($before,$post,$nonce=true,$validation=true){$GLOBALS['billing']=$before;$GLOBALS['writes']=0;$GLOBALS['nonce']=$nonce;$GLOBALS['rejectValidation']=!$validation;$_POST=$post;try{(new \Tutor\Ecommerce\BillingController(false))->save_billing_info();}catch(BillingResponse$e){return$e->status;}throw new \RuntimeException('no response');}
 function check($v,$why){if(!$v)throw new \RuntimeException($why);}
 $cases=[
 'country-only-preserves-other-fields'=>function(){$before=full_profile();check(save_case($before,['billing_country'=>'Canada'])===200,'save failed');$after=$before;$after['billing_country']='Canada';check($GLOBALS['billing']===$after,'omitted fields erased');},
 'state-only-preserves-other-fields'=>function(){$before=full_profile();save_case($before,['billing_state'=>'Other']);$after=$before;$after['billing_state']='Other';check($GLOBALS['billing']===$after,'state save erased fields');},
 'email-only-preserves-address'=>function(){$before=full_profile();save_case($before,['billing_email'=>'updated@example.test']);$after=$before;$after['billing_email']='updated@example.test';check($GLOBALS['billing']===$after,'email save erased address');},
 'explicit-empty-value-is-honoured'=>function(){$before=full_profile();save_case($before,['billing_address'=>'']);$after=$before;$after['billing_address']='';check($GLOBALS['billing']===$after,'explicit clear changed unrelated fields');},
 'full-update-keeps-full-payload'=>function(){$before=full_profile();$after=$before;$after['billing_first_name']='Changed';$after['billing_city']='Other';check(save_case($before,$after)===200&&$GLOBALS['billing']===$after,'full update broken');},
 'new-partial-profile-keeps-required-defaults'=>function(){save_case(null,['billing_country'=>'Canada']);$required=(new \Tutor\Models\BillingModel)->get_required_fields();foreach($required as$k)check(array_key_exists($k,$GLOBALS['billing']),'required insert column absent');check($GLOBALS['billing']['billing_country']==='Canada'&&$GLOBALS['billing']['billing_zip_code']===''&&$GLOBALS['billing']['user_id']===12,'new profile defaults changed');},
 'new-full-profile-keeps-values'=>function(){$after=full_profile();save_case(null,$after);check($GLOBALS['billing']==$after&&$GLOBALS['billing']['user_id']===12,'new full profile changed');},
 'nonce-failure-makes-no-write'=>function(){$before=full_profile();check(save_case($before,['billing_country'=>'Canada'],false)===400&&$GLOBALS['writes']===0&&$GLOBALS['billing']===$before,'nonce gate changed');},
 'injected-validation-failure-makes-no-write'=>function(){$before=full_profile();check(save_case($before,['billing_country'=>'Canada'],true,false)===422&&$GLOBALS['writes']===0&&$GLOBALS['billing']===$before,'validation result ignored');},
 'unfillable-and-foreign-user-fields-ignored'=>function(){$before=full_profile();save_case($before,['billing_country'=>'Canada','user_id'=>99,'role'=>'administrator']);$after=$before;$after['billing_country']='Canada';check($GLOBALS['billing']===$after,'unfillable fields or omitted data changed');},
 ];
 $fail=[];foreach($cases as$name=>$case){try{$case();}catch(\Throwable$e){$fail[]=['case'=>$name,'reason'=>$e->getMessage()];}}echo json_encode(['passed'=>count($cases)-count($fail),'total'=>count($cases),'failures'=>$fail],JSON_PRETTY_PRINT)."\n";exit($fail?1:0);
}
