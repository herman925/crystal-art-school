<?php
/** Loopback synthetic transport for the already-verified Tutor payment-hook contract. */
namespace Tutor\Models {
    class OrderMetaModel {
        public static function update_meta($id,$key,$value) { $GLOBALS['evidence']=json_decode($value,true); $GLOBALS['evidence_key']=$key; return true; }
    }
}
namespace {
    define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
    function add_action(...$args){}
    function did_action($name){return $name==='rest_api_init'?1:0;}
    function wp_json_encode($value){return json_encode($value);}
    class FakeNativeOrders {
        public $prefix='fixture_';
        public function prepare($sql,...$args){return $sql;}
        public function get_row($sql,$format){return ['order_id'=>10001,'payment_status'=>'paid','payment_method'=>'stripe','transaction_id'=>isset($_GET['foreign'])?'pi_other':'pi_10001'];}
    }
    $wpdb=new FakeNativeOrders();
    require dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-course-popularity.php';
    $result=(object)['id'=>10001,'payment_status'=>'paid','payment_method'=>'stripe','transaction_id'=>'pi_10001'];
    cas_course_record_payment_evidence($result);
    header('Content-Type: application/json');echo json_encode(['evidence'=>$GLOBALS['evidence']??null,'key'=>$GLOBALS['evidence_key']??null]);
}
