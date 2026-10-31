<?php
namespace Tutor\Models {
 class EnrollmentModel {
  public static $enrolled = array( 10 );
  public static function is_enrolled( $course, $user ) { return in_array( $course, self::$enrolled, true ); }
 }
}
namespace {
 define('ABSPATH', '/scratch/');
 $logged = true; $admin = false;
 function add_action(...$args) {} function add_filter(...$args) {}
 function is_user_logged_in() { global $logged; return $logged; }
 function get_current_user_id() { return 7; }
 function current_user_can($cap) { global $admin; return $admin; }
 function get_post_meta($id,$key,$single) { return array(10,20); }
 function get_post_type($id) { return in_array($id,array(100,101),true) ? 'attachment' : ''; }
 function is_serialized($s) { return is_string($s) && str_starts_with($s,'a:'); }
 class FixtureDB {
  public $posts='posts'; public $postmeta='postmeta'; public $rows=[]; public $graph=[];
  function get_results($sql) { return $this->rows; }
  function prepare($sql,...$args) { return $args[0]; }
  function get_row($id) { return $this->graph[$id] ?? null; }
 }
 $wpdb=new FixtureDB;
 function post($id,$type,$parent=0,$status='publish') { return (object)['ID'=>$id,'post_type'=>$type,'post_parent'=>$parent,'post_status'=>$status,'post_author'=>8]; }
 function refs($source,$ids) { return (object)['post_id'=>$source,'meta_value'=>serialize($ids)]; }
 require $argv[1];
 $wpdb->graph=[10=>post(10,'courses'),20=>post(20,'courses'),30=>post(30,'topics',10),40=>post(40,'lesson',30)];
 $cases=0;
 function check($want,$label) { global $cases; $actual=cas_private_can_download(100); if($actual!==$want){fwrite(STDERR,"FAIL $label\n");exit(1);} ++$cases; }
 $wpdb->rows=[refs(10,[100])]; check(true,'current course');
 $wpdb->rows=[]; check(false,'removed');
 $wpdb->rows=[refs(10,[101])]; check(false,'replaced');
 $wpdb->rows=[refs(20,[100])]; check(false,'shared only in unrelated course');
 \Tutor\Models\EnrollmentModel::$enrolled=[20]; check(true,'shared current entitled course');
 \Tutor\Models\EnrollmentModel::$enrolled=[10];
 $wpdb->rows=[refs(40,[100])]; check(true,'lesson');
 $wpdb->graph[30]->post_parent=20; check(false,'lesson moved');
 $wpdb->graph[30]->post_parent=10; $wpdb->graph[30]->post_status='trash'; check(false,'trashed topic');
 $wpdb->graph[30]->post_status='publish'; $wpdb->graph[40]->post_status='trash'; check(false,'trashed lesson');
 $wpdb->rows=[refs(10,[100])]; $wpdb->graph[10]->post_status='trash'; check(false,'trashed course');
 $wpdb->graph[10]->post_status='publish'; $wpdb->rows=[refs(10,[100]),refs(10,[100])]; check(false,'duplicate list');
 $wpdb->rows=[refs(10,[100,'bad'])]; check(false,'malformed list');
 $wpdb->rows=[refs(999,[100])]; check(false,'missing course');
 $wpdb->rows=[refs(10,[100])]; $logged=false; check(false,'guest');
 $logged=true; \Tutor\Models\EnrollmentModel::$enrolled=[]; check(false,'unrelated learner');
 $admin=true; $wpdb->rows=[]; check(true,'administrator manages orphan');
 echo "$cases revocation cases passed\n";
}
