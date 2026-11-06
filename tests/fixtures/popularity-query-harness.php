<?php
define('ABSPATH',__DIR__);define('ARRAY_A','ARRAY_A');
function add_action(...$args){}
function current_user_can(...$args){return true;}
function esc_html($s){return htmlspecialchars((string)$s);}
class QueryCapture {
 public $prefix='fixture_';public $posts='fixture_posts';public $usermeta='fixture_usermeta';
 public function prepare($sql,$args){foreach($args as $value){$sql=preg_replace('/%[sd]/',"'".str_replace("'","''",(string)$value)."'",$sql,1);}return $sql;}
 public function get_results($sql,$format){$GLOBALS['query']=$sql;return [];}
}
$wpdb=new QueryCapture();
require dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-course-popularity.php';
ob_start();cas_course_popularity_render(['from'=>'2020-01-01','to'=>'2030-01-01','course'=>0,'order_id'=>0]);ob_end_clean();
echo json_encode(['sql'=>$GLOBALS['query']]);
