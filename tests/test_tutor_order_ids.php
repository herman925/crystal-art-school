<?php
/** Actual vendor methods with synthetic persistence/platform boundaries.
 * Usage: php tests/test_tutor_order_ids.php /read-only/path/to/tutor
 * Native deletion and legacy controls are tested without production data.
 */
namespace Tutor\Helpers {
 class QueryHelper {
  static function match($row,$where){foreach($where as$k=>$v)if(($row[$k]??null)!=$v)return false;return true;}
  static function get_all($table,$where,...$rest){return array_map(fn($r)=>(object)$r,array_values(array_filter($GLOBALS['earnings'],fn($r)=>self::match($r,$where))));}
  static function get_row($table,$where,...$rest){if(str_contains($table,'tutor_orders')){$id=(int)$where['id'];return isset($GLOBALS['orders'][$id])?(object)$GLOBALS['orders'][$id]:false;}$rows=self::get_all($table,$where);return $rows?end($rows):false;}
  static function delete($table,$where){$key=str_contains($table,'earning')?'earnings':'orders';foreach($GLOBALS[$key]as$id=>$r)if(self::match($r,$where))unset($GLOBALS[$key][$id]);return true;}
  static function bulk_delete_by_ids($table,$ids){foreach($ids as$id)unset($GLOBALS['posts'][$id]);return true;}
 }
}
namespace Tutor\Ecommerce {class Ecommerce {const MONETIZE_BY='tutor';}}
namespace TUTOR {class User {const TUTOR_STUDENT_META='_is_tutor_student';} class WooCommerce {const MONETIZE_BY='wc';static function hpos_enabled(){return$GLOBALS['hpos']??false;}}}
namespace {
 $GLOBALS['vendorRoot']=rtrim($argv[1]??'/inputs','/');
 function input_path($file){$map=['Course'=>'classes/Course.php','Utils'=>'classes/Utils.php','Earnings'=>'classes/Earnings.php','EnrollmentModel'=>'models/EnrollmentModel.php','OrderModel'=>'models/OrderModel.php'];return $GLOBALS['vendorRoot'].'/'.$map[$file];}
 function method($file,$name){$s=file_get_contents(input_path($file));$start=strpos($s,'function '.$name.'(');if($start===false)throw new \RuntimeException('missing method '.$name);$line=strrpos(substr($s,0,$start),"\n")+1;$end=strpos($s,"\n\t}",$start)+strlen("\n\t}");return substr($s,$line,$end-$line);}
 function optional_method($file,$name){return str_contains(file_get_contents(input_path($file)),'function '.$name.'(')?method($file,$name):'';}
 eval('namespace Tutor\\Models; use TUTOR\\Course;use TUTOR\\User;class CourseModel{static function is_course_accessible($id){return true;}}class EnrollmentModel{const STATUS_COMPLETED="completed",STATUS_PENDING="pending",POST_TYPE="tutor_enrolled",ENROLLMENT_ORDER_ID_META="_tutor_enrolled_by_order_id",ENROLLMENT_PRODUCT_ID_META="_tutor_enrolled_by_product_id";static function is_enrolled($c,$u){foreach($GLOBALS["posts"]as$id=>$p)if(($p["post_type"]??"")==="tutor_enrolled"&&$p["post_parent"]===$c&&$p["post_author"]===$u&&$p["post_status"]==="completed")return(object)["ID"=>$id];return false;}'.method('EnrollmentModel','do_enroll').'}');
 eval('namespace TUTOR;use Tutor\\Models\\EnrollmentModel;class Course{const IS_TUTOR_ORDER_FOR_COURSE_META="_is_tutor_order_for_course",TUTOR_ORDER_FOR_COURSE_ID_META="_tutor_order_for_course_id_";'.method('Course','delete_associated_enrollment').'}');
 eval('namespace TUTOR;use Tutor\\Models\\EnrollmentModel;use Tutor\\Models\\OrderModel;use Tutor\\Helpers\\QueryHelper;use Tutor\\Ecommerce\\Ecommerce;class UtilsHarness{function get_option($k){return$GLOBALS["mode"];}function get_user_id($id){return$id;}function get_post_id($id){return$id;}function is_course_purchasable($id){return false;}function get_course_product_id($id){return$id+1000;}function delete_course_progress($c,$u){$GLOBALS["progress_deleted"][]=[$c,$u];}'.method('Utils','cancel_course_enrol').method('Utils','is_tutor_order').method('Utils','get_course_enrolled_ids_by_order_id').'}');
 eval('namespace TUTOR;use Tutor\\Helpers\\QueryHelper;use Tutor\\Ecommerce\\Ecommerce;class Earnings{const PROCESS_BY_TUTOR="tutor",PROCESS_BY_WOOCOMMERCE="woocommerce",INVALID_DATA_MSG="invalid";public $earning_table="wp_tutor_earnings",$order_id=7,$earning_data=[["order_id"=>7,"process_by"=>"tutor","course_id"=>101,"user_id"=>2]];function store_earnings(){$GLOBALS["earnings"][1000]=["earning_id"=>1000]+$this->earning_data[0];return 1000;}'.method('Earnings','get_process_by').optional_method('Earnings','order_earning_conditions').method('Earnings','get_order_earnings').method('Earnings','is_exist_order_earning').method('Earnings','delete_earning_by_order').method('Earnings','remove_before_store_earnings').'}');
 eval('namespace Tutor\\Models;use Tutor\\Helpers\\QueryHelper;use TUTOR\\Earnings;class OrderModel{public $table_name="wp_tutor_orders";function get_row($where=[]){$id=(int)$where["id"];return isset($GLOBALS["orders"][$id])?(object)$GLOBALS["orders"][$id]:false;}function get_order_items_by_id($id){return array_map(fn($id)=>(object)["id"=>$id],$GLOBALS["orderItems"]);}'.method('OrderModel','get_enrollment_ids').method('OrderModel','delete_order').'}');
 class LegacyOrder {function __construct(public int$id,public string$mode){}function update_meta_data($k,$v){$GLOBALS['legacy'][$this->mode][$this->id][$k]=$v;}function update_meta($k,$v){$this->update_meta_data($k,$v);}function delete_meta_data($k){unset($GLOBALS['legacy'][$this->mode][$this->id][$k]);}function get_meta($k,$single=true){return$GLOBALS["legacy"][$this->mode][$this->id][$k]??"";}function save(){return true;}}
 class EDD_Payment extends LegacyOrder {function __construct($id){parent::__construct($id,'edd');}}
 function wc_get_order($id){return new LegacyOrder($id,'wc');}
 class FakeWpdb {
  public $prefix='wp_',$posts='wp_posts',$postmeta='wp_postmeta';
  function prepare($sql,...$args){return[$sql,$args];}
  function matches($q){[$sql,$args]=$q;$key=$args[0];$val=$args[1];$out=[];foreach($GLOBALS['postmeta']as$id=>$m)if(isset($m[$key])&&(string)$m[$key]===(string)$val)$out[]=(object)['post_id'=>$id];return$out;}
  function get_var($q){return$this->matches($q)[0]->post_id??null;}
  function get_results($q){if(str_contains($q[0],'wc_orders_meta')){$out=[];$id=$q[1][0];foreach(($GLOBALS['legacy']['wc'][$id]??[])as$k=>$v)if(str_starts_with($k,'_tutor_order_for_course_id_'))$out[]=(object)['order_id'=>$id,'meta_key'=>$k,'meta_value'=>$v];return$out;}if(str_contains($q[0],"meta_key LIKE '_tutor_order_for_course_id_%'")){$out=[];$id=$q[1][0];foreach(($GLOBALS['postmeta'][$id]??[])as$k=>$v)if(str_starts_with($k,'_tutor_order_for_course_id_'))$out[]=(object)['post_id'=>$id,'meta_key'=>$k,'meta_value'=>$v];return$out;}return$this->matches($q);}
  function query($sql){$GLOBALS['sql'][]=$sql;return true;}
  function update($table,$set,$where){foreach($GLOBALS['posts']as$id=>&$p)if(\Tutor\Helpers\QueryHelper::match($p,$where))$p=array_merge($p,$set);unset($p);return true;}
  function delete($table,$where){foreach($GLOBALS['posts']as$id=>$p)if(\Tutor\Helpers\QueryHelper::match($p,$where))unset($GLOBALS['posts'][$id]);return true;}
 }
 function absint($v){return abs((int)$v);}
 function tutor_utils(){return new \TUTOR\UtilsHarness;}
 function __($s){return$s;}
 function get_option($k){return$k==='date_format'?'Y-m-d':'H:i';}
 function apply_filters($name,$value,...$rest){return$value;}
 function do_action(...$rest){}
 function is_wp_error($v){return false;}
 function current_time(...$args){return'2026-11-04 00:00:00';}
 function tutor_time(){return 12345;}
 function update_user_meta(...$args){}
 function wp_insert_post($p){$id=901;$GLOBALS['posts'][$id]=['ID'=>$id]+$p;return$id;}
 function update_post_meta($id,$key,$value){$GLOBALS['postmeta'][$id][$key]=$value;return true;}
 function get_post_meta($id,$key,$single=true){return$GLOBALS['postmeta'][$id][$key]??'';}
 function delete_post_meta($id,$key){unset($GLOBALS['postmeta'][$id][$key]);return true;}
 function get_post_field($key,$id){return$GLOBALS['posts'][$id][$key]??'';}
 function get_post_type($id){return get_post_field('post_type',$id);}
 function get_post($id){return isset($GLOBALS['posts'][$id])?(object)$GLOBALS['posts'][$id]:null;}
 function tutor_log(...$args){}
 function reset_case($mode='tutor'){$GLOBALS['mode']=$mode;$GLOBALS['hpos']=false;$GLOBALS['orderItems']=[101];$GLOBALS['posts']=[7=>['ID'=>7,'post_type'=>'post','post_parent'=>0,'post_author'=>42,'post_status'=>'publish'],901=>['ID'=>901,'post_type'=>'tutor_enrolled','post_parent'=>101,'post_author'=>31,'post_status'=>'completed']];$GLOBALS['postmeta']=[901=>['_tutor_enrolled_by_order_id'=>7,'_tutor_enrolled_by_product_id'=>1101],7=>['_is_tutor_order_for_course'=>'unrelated-prior-value','_tutor_order_for_course_id_101'=>888]];$GLOBALS['earnings']=[1=>['earning_id'=>1,'order_id'=>7,'process_by'=>'woocommerce','course_id'=>202,'user_id'=>42],2=>['earning_id'=>2,'order_id'=>7,'process_by'=>'','course_id'=>303,'user_id'=>43],3=>['earning_id'=>3,'order_id'=>7,'process_by'=>'tutor','course_id'=>101,'user_id'=>2]];$GLOBALS['orders']=[7=>['id'=>7,'user_id'=>31]];$GLOBALS['legacy']=[];$GLOBALS['sql']=[];$GLOBALS['progress_deleted']=[];}
 function expect($condition,$message){if(!$condition)throw new \RuntimeException($message);}
 $wpdb=new FakeWpdb;
 $cases=[
 'native-order-reader-works-without-compat-meta'=>function(){reset_case();unset($GLOBALS['postmeta'][7]);expect((bool)tutor_utils()->is_tutor_order(7),'native real order denied without foreign WP post marker');},
 'native-order-reader-ignores-foreign-marker'=>function(){reset_case();$GLOBALS['postmeta'][999]['_is_tutor_order_for_course']=123;expect(!tutor_utils()->is_tutor_order(999),'foreign WP post marker treated as native order');},
 'native-enrol-reader-uses-native-association'=>function(){reset_case();$r=tutor_utils()->get_course_enrolled_ids_by_order_id(7);expect(is_array($r)&&count($r)===1&&(int)$r[0]['course_id']===101&&(int)$r[0]['enrolled_id']===901,'native enrol reader read foreign compatibility record');},

 'native-unrelated-post-delete-preserves-enrolment'=>function(){reset_case();(new \TUTOR\Course)->delete_associated_enrollment(7);expect($GLOBALS['posts'][901]['post_status']==='completed','global WP post deletion cancelled native order enrolment with same numeric ID');},
 'native-enrol-keeps-foreign-postmeta'=>function(){reset_case();unset($GLOBALS['posts'][901],$GLOBALS['postmeta'][901]);$before=$GLOBALS['postmeta'][7];$id=\Tutor\Models\EnrollmentModel::do_enroll(101,7,31);expect($id===901&&$GLOBALS['postmeta'][901]['_tutor_enrolled_by_order_id']===7,'legitimate enrol association missing');expect($GLOBALS['postmeta'][7]===$before,'native custom order ID mutated unrelated WP post compatibility metadata');},
 'native-unenrol-delete-keeps-foreign-postmeta'=>function(){reset_case();$before=$GLOBALS['postmeta'][7];tutor_utils()->cancel_course_enrol(101,31,'delete');expect(!isset($GLOBALS['posts'][901]),'native owned enrol deletion failed');expect($GLOBALS['postmeta'][7]===$before,'native enrol deletion stripped unrelated WP post compatibility metadata');},
 'native-order-earnings-read-is-scoped'=>function(){reset_case();$rows=(new \TUTOR\Earnings)->get_order_earnings(7);expect(count($rows)===1&&$rows[0]->process_by==='tutor','native order earnings read mixed legacy and native namespaces');},
 'native-existence-ignores-only-legacy-earnings'=>function(){reset_case();unset($GLOBALS['earnings'][3]);expect(!(new \TUTOR\Earnings)->is_exist_order_earning(7),'legacy earning treated as native order earning');},
 'native-delete-preserves-legacy-earnings'=>function(){reset_case();(new \TUTOR\Earnings)->delete_earning_by_order(7);expect(array_keys($GLOBALS['earnings'])===[1,2],'native order-ID delete removed legacy namespace earnings');},
 'native-replacement-preserves-legacy-earnings'=>function(){reset_case();(new \TUTOR\Earnings)->remove_before_store_earnings();expect(isset($GLOBALS['earnings'][1],$GLOBALS['earnings'][2],$GLOBALS['earnings'][1000])&&!isset($GLOBALS['earnings'][3]),'actual replacement pipeline erased older-platform earnings');},
 'native-order-delete-still-removes-owned-enrolment'=>function(){reset_case();expect((new \Tutor\Models\OrderModel)->delete_order(7)===true,'native deletion failed');expect(!isset($GLOBALS['orders'][7],$GLOBALS['posts'][901],$GLOBALS['earnings'][3]),'native order deletion retained owned records');expect(isset($GLOBALS['posts'][7],$GLOBALS['earnings'][1],$GLOBALS['earnings'][2]),'native order deletion removed unrelated WP post or legacy earnings');},
 'wc-enrol-preserves-platform-order-api'=>function(){reset_case('wc');unset($GLOBALS['posts'][901],$GLOBALS['postmeta'][901]);\Tutor\Models\EnrollmentModel::do_enroll(101,7,31);expect(($GLOBALS['legacy']['wc'][7]['_tutor_order_for_course_id_101']??null)===901,'WC order metadata integration changed');},
 'edd-enrol-preserves-platform-order-api'=>function(){reset_case('edd');unset($GLOBALS['posts'][901],$GLOBALS['postmeta'][901]);\Tutor\Models\EnrollmentModel::do_enroll(101,7,31);expect(($GLOBALS['legacy']['edd'][7]['_tutor_order_for_course_id_101']??null)===901,'EDD order metadata integration changed');},
 'wc-order-post-delete-still-cancels-associated-enrolment'=>function(){reset_case('wc');$GLOBALS['posts'][7]['post_type']='shop_order';(new \TUTOR\Course)->delete_associated_enrollment(7);expect($GLOBALS['posts'][901]['post_status']==='canceled','WC legacy order cancellation changed');},
 'edd-order-post-delete-still-cancels-associated-enrolment'=>function(){reset_case('edd');$GLOBALS['posts'][7]['post_type']='edd_payment';(new \TUTOR\Course)->delete_associated_enrollment(7);expect($GLOBALS['posts'][901]['post_status']==='canceled','EDD legacy order cancellation changed');}
 ];
 foreach(['type','student','course','missing']as$kind)$cases['native-enrol-reader-rejects-'.$kind]=function()use($kind){reset_case();$p=['ID'=>902,'post_type'=>'tutor_enrolled','post_parent'=>101,'post_author'=>31,'post_status'=>'completed'];if($kind==='type')$p['post_type']='post';if($kind==='student')$p['post_author']=99;if($kind==='course')$p['post_parent']=999;if($kind!=='missing')$GLOBALS['posts'][902]=$p;$GLOBALS['postmeta'][902]['_tutor_enrolled_by_order_id']=7;$out=tutor_utils()->get_course_enrolled_ids_by_order_id(7);expect(is_array($out)&&count($out)===1&&(int)$out[0]['enrolled_id']===901,'foreign or missing candidate was returned');};
 $cases['native-enrol-reader-keeps-string-shape']=function(){reset_case();expect(tutor_utils()->get_course_enrolled_ids_by_order_id(7)===[['course_id'=>'101','enrolled_id'=>'901','order_id'=>'7']],'native reader changed three-field string contract');};
 $cases['native-enrol-reader-handles-multiple-order-items']=function(){reset_case();$GLOBALS['orderItems']=[101,102];$GLOBALS['posts'][902]=['ID'=>902,'post_type'=>'tutor_enrolled','post_parent'=>102,'post_author'=>31,'post_status'=>'completed'];$GLOBALS['postmeta'][902]['_tutor_enrolled_by_order_id']=7;$out=tutor_utils()->get_course_enrolled_ids_by_order_id(7);expect(is_array($out)&&count($out)===2&&(int)$out[1]['course_id']===102,'native multi-item association lost');};
 foreach(['wc','edd']as$mode)$cases[$mode.'-readers-preserve-legacy-backend']=function()use($mode){reset_case($mode);$GLOBALS['legacy'][$mode][7]['_is_tutor_order_for_course']=123;expect(tutor_utils()->is_tutor_order(7)===123,'legacy order API reader changed');$out=tutor_utils()->get_course_enrolled_ids_by_order_id(7);expect((int)$out[0]['enrolled_id']===888,'legacy postmeta reader changed');};
 foreach(['wc','edd']as$mode)$cases[$mode.'-earnings-retain-legacy-unmarked-history']=function()use($mode){reset_case($mode);unset($GLOBALS['earnings'][3]);$out=(new \TUTOR\Earnings)->get_order_earnings(7);expect(count($out)===2,'legacy unmarked earnings history excluded');};
 $cases['wc-hpos-enrol-reader-preserved']=function(){reset_case('wc');$GLOBALS['hpos']=true;$GLOBALS['legacy']['wc'][7]['_tutor_order_for_course_id_101']=901;$out=tutor_utils()->get_course_enrolled_ids_by_order_id(7);expect((int)$out[0]['enrolled_id']===901&&(int)$out[0]['order_id']===7,'HPOS order metadata reader changed');};
 $failed=[];foreach($cases as$name=>$case){try{$case();}catch(\Throwable$e){$failed[]=['case'=>$name,'reason'=>$e->getMessage()];}}
 echo json_encode(['actualMethods'=>true,'syntheticPersistence'=>true,'passed'=>count($cases)-count($failed),'total'=>count($cases),'failures'=>$failed],JSON_PRETTY_PRINT)."\n";exit($failed?1:0);
}
