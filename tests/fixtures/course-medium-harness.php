<?php
define('ABSPATH', __DIR__);
$hooks=[]; $may_edit=true; $nonce=true; $saved_tags=[7,9];
function add_action($name,$fn,...$rest){$GLOBALS['hooks'][$name][]=$fn;}
function add_filter($name,$fn,...$rest){add_action($name,$fn);}
function add_shortcode(...$args){}
function wp_list_pluck($rows,$field){return array_column($rows,$field);}
function get_posts($args){$GLOBALS['post_query']=$args;return [34,50];}
function wp_get_object_terms($ids,$tax,$args){assert($ids===[34,50]);return ($GLOBALS['argv'][1]??'single')==='single'?[(object)['slug'=>'watercolour','name'=>'Watercolour']]:[(object)['slug'=>'watercolour','name'=>'Watercolour'],(object)['slug'=>'ink-wash','name'=>'Ink & wash']];}
function is_wp_error($value){return false;}
function current_user_can($cap,...$args){return $GLOBALS['may_edit'];}
function cas_builder_nonce_valid(){return $GLOBALS['nonce'];}
function get_post($id){return (object)['post_type'=>'courses'];}
function wp_get_post_terms($id,$tax,$args){return $tax==='course-tag'?$GLOBALS['saved_tags']:['Ink & wash','Watercolour'];}
function cas_page_url($name){return 'https://example.test/courses/';}
function add_query_arg($key,$value,$url){return $url.'?'.http_build_query([$key=>$value]);}
function esc_url($value){return htmlspecialchars($value,ENT_QUOTES);}
function esc_html($value){return htmlspecialchars($value,ENT_QUOTES);}
function esc_attr($value){return htmlspecialchars((string)$value,ENT_QUOTES);}
require dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-courses.php';
require dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-header.php';
require dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-course-single.php';
assert(cas_course_media_label(34)==='Ink & wash & Watercolour');
$terms=cas_course_medium_terms();
assert($GLOBALS['post_query']['post_status']==='publish');
assert($GLOBALS['post_query']['posts_per_page']===-1);
$nav=cas_header_course_drawer();
assert(!str_contains($nav,'Beginners')&&!str_contains($nav,'popular'));
foreach($terms as $slug=>$name){assert(str_contains($nav,'cat='.$slug));}
$row=['medium'=>$terms,'difficulty'=>'beginner','duration'=>'short','now'=>20,'regular'=>20,'is_sale'=>false];
$rail=cas_course_filter_rail([$row,$row]);
assert(str_contains($rail,'data-group="medium"'));
assert(!str_contains($rail,'data-group="theme"'));
foreach($terms as $slug=>$name){assert(str_contains($rail,'data-val="'.$slug.'"'));}
assert(array_values($terms)===(count($terms)===1?['Watercolour']:['Ink & wash','Watercolour']));
$_POST=['course_id'=>'34','course_tags'=>[999]];$_REQUEST=$_POST;
cas_course_preserve_hidden_tags();assert($_POST['course_tags']===[7,9]&&$_REQUEST['course_tags']===[7,9]);
$saved_tags=[];cas_course_preserve_hidden_tags();assert(!isset($_POST['course_tags'])&&!isset($_REQUEST['course_tags']));$saved_tags=[7,9];
$nonce=false;$_POST['course_tags']=[999];cas_course_preserve_hidden_tags();assert($_POST['course_tags']===[999]);
$nonce=true;$may_edit=false;cas_course_preserve_hidden_tags();assert($_POST['course_tags']===[999]);$may_edit=true;
$_POST['course_id']='bad';cas_course_preserve_hidden_tags();assert($_POST['course_tags']===[999]);
function stripe_row($id,$course=34,$live=true){
 return ['order_id'=>$id,'course_id'=>$course,'order_type'=>'single_order','order_status'=>'completed','payment_status'=>'paid','total_price'=>'20.00','item_amount'=>'20.00','refund_amount'=>null,'payment_method'=>'stripe','transaction_id'=>'pi_'.$id,'payment_payloads'=>json_encode(['object'=>'payment_intent','id'=>'pi_'.$id,'status'=>'succeeded','amount_received'=>2000,'metadata'=>['order_id'=>(string)$id],'livemode'=>$live])];
}
function paypal_row($id,$host='api-m.paypal.com'){
 $r=stripe_row($id,50);$r['payment_method']='paypal';$r['transaction_id']='CAP'.$id;
 $r['payment_payloads']=json_encode(['event_type'=>'PAYMENT.CAPTURE.COMPLETED','resource'=>['id'=>'CAP'.$id,'custom_id'=>(string)$id,'status'=>'COMPLETED','amount'=>['value'=>'20.00'],'links'=>[['rel'=>'self','href'=>'https://'.$host.'/v2/payments/captures/CAP'.$id]]]]);return $r;
}
assert(cas_popularity_payment_mode(stripe_row(10001))==='live');
assert(cas_popularity_payment_mode(stripe_row(10002,34,false))==='test');
assert(cas_popularity_payment_mode(paypal_row(10003))==='live');
assert(cas_popularity_payment_mode(paypal_row(10004,'api.sandbox.paypal.com'))==='test');
assert(cas_popularity_payment_mode(paypal_row(10004,'api-m.sandbox.paypal.com'))==='test');
assert(cas_popularity_payment_mode(paypal_row(10004,'api-m.paypal.com.evil.test'))==='unknown');
$r=paypal_row(10005);$r['payment_payloads']=addslashes($r['payment_payloads']);assert(cas_popularity_payment_mode($r)==='live');
$r=stripe_row(10006);$r['transaction_id']='pi_foreign';assert(cas_popularity_payment_mode($r)==='unknown');
$r=stripe_row(10007);$r['order_id']=999;assert(cas_popularity_payment_mode($r)==='unknown');
$r=stripe_row(10008);$r['payment_payloads']='null';assert(cas_popularity_payment_mode($r)==='unknown');
$r=stripe_row(10009);$r['payment_payloads']=str_repeat('x',262145);assert(cas_popularity_payment_mode($r)==='unknown');
$fallback=stripe_row(10011);$fallback['payment_payloads']='null';
$proof=['version'=>1,'verified_by'=>'tutor_payment_hook','order_id'=>10011,'payment_method'=>'stripe','transaction_id'=>'pi_10011','mode'=>'live'];
$fallback['mode_evidence']=json_encode($proof);assert(cas_popularity_payment_mode($fallback)==='live');
$proof['transaction_id']='pi_other';$fallback['mode_evidence']=json_encode($proof);assert(cas_popularity_payment_mode($fallback)==='unknown');
$conflict=stripe_row(10011,34,false);$proof['transaction_id']='pi_10011';$conflict['mode_evidence']=json_encode($proof);assert(cas_popularity_payment_mode($conflict)==='unknown');
$intent=json_decode(stripe_row(10011)['payment_payloads'],true);
$event=['object'=>'event','type'=>'payment_intent.succeeded','livemode'=>true,'data'=>['object'=>$intent]];
assert(cas_popularity_webhook_mode(stripe_row(10011),json_encode($event))==='live');
$event['livemode']=false;assert(cas_popularity_webhook_mode(stripe_row(10011),json_encode($event))==='unknown');
$rows=[stripe_row(10001),stripe_row(10001),paypal_row(10003),stripe_row(10002,34,false),$r];
foreach(['order_status'=>'cancelled','payment_status'=>'partially_refunded','refund_amount'=>'0.50','total_price'=>'0','item_amount'=>'0','order_type'=>'subscription','is_test_account'=>true,'course_id'=>0] as $key=>$value){$bad=stripe_row(10010);$bad[$key]=$value;$rows[]=$bad;}
$data=cas_popularity_counts($rows,[34=>['Watercolour'],50=>['Watercolour','Ink & wash']]);
assert($data['courses']===[34=>1,50=>1]);assert($data['media']===['Watercolour'=>2,'Ink & wash'=>1]);
$may_edit=false;ob_start();cas_course_popularity_render([]);assert(ob_get_clean()==='');
echo json_encode(['medium_terms'=>count($terms),'shared_navigation_filter'=>true,'hidden_tags_preserved'=>true,'payment_qualification'=>true,'purchase_counts'=>$data]);
