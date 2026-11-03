<?php
/** Exercise the actual installed HooksHandler method using synthetic order/enrolment dependencies.
 * Usage: php tests/test_tutor_refund_enrolment.php /read-only/path/HooksHandler.php
 * The baseline must reproduce failures; the patched source must pass every case.
 */
namespace TUTOR {
 class Input { public static array $data=[]; const POST_REQUEST='post'; const TYPE_BOOL='bool'; static function post($k,$default=null,$type=null){return self::$data[$k]??$default;} static function has($k,$type=null){return array_key_exists($k,self::$data);} }
 class User { static function is_admin(){return true;} }
 class Earnings { static function get_instance(){return new self;} function prepare_order_earnings($id){} function remove_before_store_earnings(){} }
}
namespace Tutor\Models {
 class OrderModel {
  const ORDER_COMPLETED='completed',ORDER_INCOMPLETE='incomplete',PAYMENT_REFUNDED='refunded',PAYMENT_PARTIALLY_REFUNDED='partially_refunded',TYPE_SINGLE_ORDER='single';
  function get_order_by_id($id){$o=clone $GLOBALS['orders'][$id];foreach($GLOBALS['enrolments']as$r)if($r['order_id']===$id){$o->enrollment=(object)['status'=>$r['status']];break;}return$o;}
  function is_single_order($order){return true;}
  function get_enrollment_ids($id){return array_keys(array_filter($GLOBALS['enrolments'],fn($r)=>$r['order_id']===$id));}
 }
 class EnrollmentModel {
  const STATUS_COMPLETED='completed',STATUS_CANCEL='cancel',POST_TYPE='tutor_enrolled',ENROLLMENT_ORDER_ID_META='_tutor_enrolled_by_order_id';
  static function is_enrolled($course,$user,$complete=true){foreach($GLOBALS['enrolments']as$id=>$r)if($r['type']===self::POST_TYPE&&$r['course']===$course&&$r['user']===$user&&(!$complete||$r['status']==='completed'))return(object)['ID'=>$id,'order_id'=>$r['order_id']];return false;}
  static function do_enroll($course,$order,$user){$old=self::is_enrolled($course,$user);if($old)return$old->ID;$id=empty($GLOBALS['enrolments'])?414:max(array_keys($GLOBALS['enrolments']))+1;$GLOBALS['enrolments'][$id]=['course'=>$course,'user'=>$user,'order_id'=>$order,'status'=>'completed','type'=>self::POST_TYPE];return$id;}
  static function update_enrollments($status,$ids){foreach($ids as$id){$GLOBALS['enrolments'][$id]['status']=$status;$GLOBALS['updated'][]=$id;}return true;}
 }
}
namespace {
 function tutor_utils(){return new class {function is_addon_enabled($name){return false;}};}
 function tutor(){return(object)['course_post_type'=>'courses','enrollment_post_type'=>'tutor_enrolled'];}
 function get_tutor_course_settings(...$args){return 0;}
 function get_tutor_option(...$args){return false;}
 function apply_filters($name,$value,...$args){return$value;}
 function add_filter(...$args){}
 function do_action(...$args){}
 function get_post($id){if(!isset($GLOBALS['enrolments'][$id]))return null;$r=$GLOBALS['enrolments'][$id];return(object)['ID'=>$id,'post_type'=>$r['type'],'post_parent'=>$r['course'],'post_author'=>$r['user']];}
 function get_post_meta(...$args){return false;}
 function get_post_type($id){return'courses';}
 function update_post_meta($id,$key,$value){if($key===\Tutor\Models\EnrollmentModel::ENROLLMENT_ORDER_ID_META)$GLOBALS['enrolments'][$id]['order_id']=(int)$value;return true;}
 $source=file_get_contents($argv[1]??'');$start=strpos($source,'public function manage_earnings_and_enrollments(');if($start===false)throw new \RuntimeException('method missing');$end=strpos($source,"\n\t/**",$start);if($end===false)throw new \RuntimeException('method boundary missing');$method=substr($source,$start,$end-$start);
 eval('namespace Tutor\\Ecommerce; use TUTOR\\Input;use TUTOR\\User;use TUTOR\\Earnings;use Tutor\\Models\\OrderModel;use Tutor\\Models\\EnrollmentModel;use TutorPro\\CourseBundle\\Models\\BundleModel;use TutorPro\\CourseBundle\\CustomPosts\\CourseBundle;class RefundHarness {public $order_model;function __construct(){$this->order_model=new OrderModel;}function is_bundle_order(...$args){return false;}'.$method.'}');
 function record($order=6,$status='completed',$course=411,$user=12,$type='tutor_enrolled'){return['order_id'=>$order,'status'=>$status,'course'=>$course,'user'=>$user,'type'=>$type];}
 function order($id,$payment='refunded',$status='completed'){return(object)['id'=>$id,'student'=>(object)['id'=>12],'items'=>[(object)['id'=>411,'primary_id'=>1]],'order_type'=>'single','order_status'=>$status,'payment_status'=>$payment];}
 function reset_state($enrolments,$orders){$GLOBALS['enrolments']=$enrolments;$GLOBALS['orders']=$orders;$GLOBALS['updated']=[];\TUTOR\Input::$data=['context'=>'backend_refund','is_remove_enrolment'=>false];}
 function run_order($id,$remove=false){\TUTOR\Input::$data['is_remove_enrolment']=$remove;(new \Tutor\Ecommerce\RefundHarness)->manage_earnings_and_enrollments($GLOBALS['orders'][$id]->order_status,$id);}
 function expect($condition,$why){if(!$condition)throw new \RuntimeException($why);}
 $cases=[
 'refunded-unlinked-order-cannot-rebind'=>function(){reset_state([414=>record(6)],[4=>order(4)]);run_order(4);expect($GLOBALS['enrolments'][414]['order_id']===6,'refund rebound another paid order');},
 'refunded-order-cannot-create-access'=>function(){reset_state([],[4=>order(4)]);run_order(4);expect($GLOBALS['enrolments']===[],'fully refunded order created access');},
 'two-order-refunds-remove-access'=>function(){reset_state([414=>record(6)],[4=>order(4),6=>order(6,'refunded','cancelled')]);run_order(4);run_order(6,true);expect($GLOBALS['enrolments'][414]['status']==='cancel','both refunds left access');},
 'cancelled-old-row-does-not-hide-active-order'=>function(){reset_state([414=>record(6,'cancel'),415=>record(7)],[7=>order(7,'refunded','cancelled')]);run_order(7,true);expect($GLOBALS['enrolments'][414]['status']==='cancel'&&$GLOBALS['enrolments'][415]['status']==='cancel','new active enrolment missed');},
 'matching-refund-can-keep-access'=>function(){reset_state([414=>record(6)],[6=>order(6)]);run_order(6);expect($GLOBALS['enrolments'][414]['status']==='completed','keep choice lost');},
 'matching-refund-can-remove-access'=>function(){reset_state([414=>record(6)],[6=>order(6,'refunded','cancelled')]);run_order(6,true);expect($GLOBALS['enrolments'][414]['status']==='cancel','remove choice lost');},
 'paid-order-creates-enrolment'=>function(){reset_state([],[6=>order(6,'paid')]);run_order(6);expect(count($GLOBALS['enrolments'])===1&&$GLOBALS['enrolments'][414]['order_id']===6,'paid order denied');},
 'free-completed-order-creates-enrolment'=>function(){reset_state([],[6=>order(6,'paid')]);$GLOBALS['orders'][6]->total_price=0;run_order(6);expect(count($GLOBALS['enrolments'])===1,'free order denied');},
 'partial-refund-keeps-access'=>function(){reset_state([414=>record(6)],[6=>order(6,'partially_refunded')]);run_order(6);expect($GLOBALS['enrolments'][414]['status']==='completed','partial refund denied');},
 'partial-refund-without-record-can-enrol'=>function(){reset_state([],[6=>order(6,'partially_refunded')]);run_order(6);expect(count($GLOBALS['enrolments'])===1,'partial payment access denied');},
 'paid-repurchase-after-cancelled-row'=>function(){reset_state([414=>record(4,'cancel')],[6=>order(6,'paid')]);run_order(6);expect(count($GLOBALS['enrolments'])===2&&$GLOBALS['enrolments'][415]['order_id']===6,'repurchase denied');},
 'paid-handler-repeat-is-idempotent'=>function(){reset_state([],[6=>order(6,'paid')]);run_order(6);run_order(6);expect(count($GLOBALS['enrolments'])===1,'repeat created duplicate');},
 ];
 foreach(['wrong-type'=>record(6,'completed',411,12,'post'),'wrong-course'=>record(6,'completed',999),'wrong-student'=>record(6,'completed',411,99)]as$name=>$foreign)$cases['linked-candidate-'.$name]=function()use($foreign){reset_state([414=>$foreign],[6=>order(6,'refunded','cancelled')]);run_order(6,true);expect($GLOBALS['enrolments'][414]===$foreign,'foreign record changed');};
 $failed=[];foreach($cases as$name=>$case){try{$case();}catch(\Throwable$e){$failed[]=['case'=>$name,'reason'=>$e->getMessage()];}}echo json_encode(['passed'=>count($cases)-count($failed),'total'=>count($cases),'failures'=>$failed],JSON_PRETTY_PRINT)."\n";exit($failed?1:0);
}
