<?php
defined( 'ABSPATH' ) || exit;
final class CAS_Bunny_Uploader {
 const VERSION = 2;
 private static $tx = null;
 private static $locks = array();
 static function boot() {
  add_action('init',array(__CLASS__,'install'));
  add_action('rest_api_init',array(__CLASS__,'routes'));
  add_action('tutor_course_builder_footer',array(__CLASS__,'editor'));
  add_filter('tutor_localize_data',array(__CLASS__,'visibility'),1000);
  add_filter('tutor_lesson_details_response',array(__CLASS__,'details'),30,2);
  add_filter('tutor_course_details_response',array(__CLASS__,'course_details'),30);
  add_action('wp_ajax_tutor_update_course',array(__CLASS__,'course_start'),5);
  add_action('tutor_after_prepare_update_post_meta',array(__CLASS__,'course_finish'),PHP_INT_MAX);
  add_filter('wp_insert_post_data',array(__CLASS__,'preserve_author'),100,4);
  add_action('wp_ajax_tutor_save_lesson',array(__CLASS__,'save_start'),5);
  add_action('tutor/lesson/created',array(__CLASS__,'save_finish'),100);
  add_action('tutor/lesson_update/after',array(__CLASS__,'save_finish'),100);
  foreach(array('add_post_metadata','update_post_metadata','delete_post_metadata') as $hook) add_filter($hook,array(__CLASS__,'guard_meta'),1,5);
  register_shutdown_function(array(__CLASS__,'shutdown'));
 }
 static function config() {
  static $config;
  if(isset($config))return $config;
  $config=array();
  if(defined('CAS_BUNNY_UPLOAD_CONFIG') && is_string(CAS_BUNNY_UPLOAD_CONFIG)){
   $path=realpath(CAS_BUNNY_UPLOAD_CONFIG);$web=realpath(ABSPATH);
   if($path && $web && 0===(fileperms($path)&0077) && !str_starts_with($path,$web.DIRECTORY_SEPARATOR) && is_file($path) && !is_link(CAS_BUNNY_UPLOAD_CONFIG)){
    $value=require $path;if(is_array($value))$config=$value;
   }
  }
  return $config;
 }
 static function enabled(){ $c=self::config();return !empty($c['enabled']) && is_string($c['library_key']??null) && !empty($c['library_key']) && function_exists('cas_video_token_key') && ''!==cas_video_token_key() && preg_match('/^[1-9][0-9]*$/',(string)($c['library_id']??'')); }
 static function table(){global $wpdb;return $wpdb->prefix.'cas_video_jobs';}
 static function install(){
  if(wp_installing())return;
  if((int)get_option('cas_bunny_schema')===self::VERSION)return;
  global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$t=self::table();$charset=$wpdb->get_charset_collate();
  dbDelta("CREATE TABLE $t (
   id char(32) NOT NULL,
   kind varchar(12) NOT NULL,
   target varchar(12) NOT NULL DEFAULT 'lesson',
   user_id bigint unsigned NOT NULL,
   course_id bigint unsigned NOT NULL,
   lesson_id bigint unsigned NOT NULL DEFAULT 0,
   context_id char(32) NOT NULL DEFAULT '',
   request_key varchar(64) DEFAULT NULL,
   video_id char(36) NOT NULL DEFAULT '',
   state varchar(20) NOT NULL,
   selection varchar(12) NOT NULL DEFAULT 'keep',
   selected_id char(32) NOT NULL DEFAULT '',
   revision bigint unsigned NOT NULL DEFAULT 0,
   data longtext NOT NULL,
   created bigint unsigned NOT NULL,
   expires bigint unsigned NOT NULL,
   PRIMARY KEY (id),
   UNIQUE KEY request_key (request_key),
   KEY user_state (user_id,state),
   KEY course (course_id),
   KEY video (video_id)
  ) $charset;");
  if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($t)))===$t&&$wpdb->get_var("SHOW COLUMNS FROM $t LIKE 'target'")==='target')update_option('cas_bunny_schema',self::VERSION,false);
 }
 static function error($code,$message,$status=400){return new WP_Error('cas_video_'.$code,$message,array('status'=>$status));}
 static function fail($code,$message,$status=400){throw new RuntimeException(wp_json_encode(array($code,$message,$status)));}
 static function row($id){global $wpdb;if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/',$id))return null;return $wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table().' WHERE id=%s',$id),ARRAY_A);}
 static function data($row){$v=json_decode($row['data']??'{}',true);return is_array($v)?$v:array();}
 static function update($id,$values){global $wpdb;if(false===$wpdb->update(self::table(),$values,array('id'=>$id)))self::fail('store','The video change could not be recorded.',503);}
 static function course($course){
  $p=get_post((int)$course);$u=get_current_user_id();
  return $u && (current_user_can('manage_cas_content') || current_user_can('manage_options')) && current_user_can('upload_files') && $p && 'courses'===$p->post_type && 'trash'!==$p->post_status
   && current_user_can('edit_post',$p->ID) && ((int)$p->post_author===$u || current_user_can('manage_options'));
 }
 static function lesson_course($id){global $wpdb;return (int)$wpdb->get_var($wpdb->prepare("SELECT c.ID FROM {$wpdb->posts} l JOIN {$wpdb->posts} t ON t.ID=l.post_parent AND t.post_type='topics' AND t.post_status<>'trash' JOIN {$wpdb->posts} c ON c.ID=t.post_parent AND c.post_type='courses' AND c.post_status<>'trash' WHERE l.ID=%d AND l.post_type='lesson' AND l.post_status<>'trash'",$id));}
 static function topic_course($id){$p=get_post((int)$id);return $p && 'topics'===$p->post_type && 'trash'!==$p->post_status?(int)$p->post_parent:0;}
 static function target($row){return $row['target']??'lesson';}
 static function object_id($row){return 'course'===self::target($row)?(int)$row['course_id']:(int)$row['lesson_id'];}
 static function authority($row,$creator=true){
  if(!$row || !self::course((int)$row['course_id']) || ($creator && (int)$row['user_id']!==get_current_user_id()))self::fail('forbidden','You cannot manage this video.',403);
  if($creator && (int)$row['lesson_id'] && !current_user_can('edit_post',(int)$row['lesson_id']))self::fail('forbidden','You cannot edit this lesson.',403);
  if((int)$row['lesson_id'] && self::lesson_course((int)$row['lesson_id'])!==(int)$row['course_id'])self::fail('moved','The lesson moved or is unavailable. Reopen it before continuing.',409);
 }
 static function lock($course){
  global $wpdb;$name='cas-video-'.sha1(DB_NAME.':'.$wpdb->prefix.':'.$course);
  if(isset(self::$locks[$name]))return;
  if(1!==(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$name)))self::fail('busy','This course is being saved. Please try again.',409);
  self::$locks[$name]=true;
 }
 static function unlock(){global $wpdb;foreach(self::$locks as $name=>$yes)$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$name));self::$locks=array();}
 static function raw($lesson){global $wpdb;$rows=$wpdb->get_col($wpdb->prepare("SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_video' ORDER BY meta_id",$lesson));if(count($rows)>1)self::fail('duplicate','This lesson has duplicate video records. Ask the administrator to reconcile them.',409);if(!$rows)return array();$value=is_serialized($rows[0])?@unserialize($rows[0],array('allowed_classes'=>false,'max_depth'=>8)):$rows[0];if(!is_array($value)||!self::plain($value))self::fail('shape','This lesson has an unsupported video record. Ask the administrator to reconcile it.',409);return $value;}
 static function plain($value,$depth=0){if($depth>8||is_object($value)||is_resource($value))return false;if(is_array($value))foreach($value as $child)if(!self::plain($child,$depth+1))return false;return true;}
 static function identity($video){
  if(!is_array($video))$video=array();$media=false;foreach(array('source_video_id','source_html5','source_external_url','source_youtube','source_vimeo','source_shortcode','source_embedded') as $key)if(!empty($video[$key]))$media=true;if(!$media)return hash('sha256','empty');$ref=self::reference($video);
  if($ref)return hash('sha256','bunny:'.$ref['library'].':'.$ref['video']);
  $out=array();foreach(array('source','source_video_id','source_html5','source_external_url','source_youtube','source_vimeo','source_shortcode','source_embedded') as $k)$out[$k]=is_scalar($video[$k]??'')?(string)($video[$k]??''):wp_json_encode($video[$k]);
  return hash('sha256',wp_json_encode($out));
 }
 static function reference($video){
  if(!is_array($video)||'embedded'!==($video['source']??''))return null;
  if(!preg_match('~https://(?:iframe|video)\.mediadelivery\.net/(?:embed|play)/([0-9]+)/([a-f0-9-]{36})(?:[?"\'\s<>]|$)~i',(is_string($video['source_embedded']??null)?$video['source_embedded']:''),$m))return null;
  if(!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i',$m[2]))return null;
  return array('library'=>$m[1],'video'=>strtolower($m[2]));
 }
 static function insert($kind,$course,$lesson,$extra=array()){
  global $wpdb;$id=bin2hex(random_bytes(16));$row=array_merge(array('id'=>$id,'kind'=>$kind,'target'=>'lesson','user_id'=>get_current_user_id(),'course_id'=>$course,'lesson_id'=>$lesson,'state'=>'editing','data'=>'{}','created'=>time(),'expires'=>time()+DAY_IN_SECONDS),$extra);
  if(false===$wpdb->insert(self::table(),$row))self::fail('store','The upload job could not be created.',503);return self::row($id);
 }
 static function context($course,$lesson,$target='lesson'){
  if(!in_array($target,array('lesson','course'),true)||('course'===$target&&$lesson))self::fail('target','Invalid video destination.');
  if(!self::course($course)||($lesson && (self::lesson_course($lesson)!==$course||!current_user_can('edit_post',$lesson))))self::fail('forbidden','You cannot edit this video destination.',403);
  $object='course'===$target?$course:$lesson;
  return self::insert('context',$course,$lesson,array('target'=>$target,'data'=>wp_json_encode(array('initial_lesson'=>$lesson,'base'=>self::identity($object?self::raw($object):array())))));
 }
 static function http($method,$video='',$body=null){
  $cfg=self::config();$url='https://video.bunnycdn.com/library/'.rawurlencode((string)$cfg['library_id']).'/videos'.($video?'/'.rawurlencode($video):'');
  $args=array('method'=>$method,'timeout'=>25,'redirection'=>0,'headers'=>array('AccessKey'=>$cfg['library_key'],'Accept'=>'application/json','Content-Type'=>'application/json'));
  if(null!==$body)$args['body']=wp_json_encode($body);
  $response=apply_filters('cas_bunny_provider_response',null,$method,$video,$body);
  if(null===$response)$response=wp_remote_request($url,$args);
  if(is_wp_error($response))self::fail('provider','Bunny could not be reached. Check this upload status before retrying.',503);
  $status=wp_remote_retrieve_response_code($response);$data=json_decode(wp_remote_retrieve_body($response),true);
  if($status<200||$status>=300||!is_array($data))self::fail('provider','Bunny did not confirm the request. The existing lesson video is unchanged.',503);
  return $data;
 }
 static function refresh($job){
  self::authority($job,false);if(in_array($job['state'],array('cancelled','unknown','creating'),true))return $job;
  $v=self::http('GET',$job['video_id']);$cfg=self::config();
  if(($v['guid']??'')!==$job['video_id']||(string)($v['videoLibraryId']??'')!==(string)$cfg['library_id'])self::fail('provider_identity','Bunny returned an unexpected video.',503);
  $state=(int)($v['status']??-1);$ready=4===$state && (int)($v['length']??0)>0 && !empty($v['availableResolutions']) && !empty($v['outputCodecs']);
  $meta=array_merge(self::data($job),array('duration'=>max(0,(int)($v['length']??0)),'progress'=>max(0,min(100,(int)($v['encodeProgress']??0)))));
  global $wpdb;$wpdb->query($wpdb->prepare('UPDATE '.self::table().' SET state=%s,data=%s WHERE id=%s AND state NOT IN (\'cancelled\',\'unknown\')',$ready?'ready':(in_array($state,array(5,6),true)?'failed':(0===$state?'uploading':'processing')),wp_json_encode($meta),$job['id']));
  return self::row($job['id']);
 }
 static function preview($guid){
  if('local'===wp_get_environment_type()){ $local=apply_filters('cas_bunny_test_preview',null,$guid);if($local)return $local; }
  $cfg=self::config();$url='https://iframe.mediadelivery.net/embed/'.(int)$cfg['library_id'].'/'.$guid.'?autoplay=false&preload=false';
  return function_exists('cas_video_sign_url')?cas_video_sign_url($url):null;
 }
 static function view($row){
  global $wpdb;
  $data=self::data($row);$out=array('id'=>$row['id'],'target'=>self::target($row),'lesson'=>(int)$row['lesson_id'],'state'=>$row['state'],'revision'=>(int)$row['revision'],'selection'=>$row['selection'],'selected'=>$row['selected_id'],'duration'=>(int)($data['duration']??0),'progress'=>(int)($data['progress']??0),'expires'=>(int)$row['expires']);
  if('ready'===$row['state'])$out['preview']=self::preview($row['video_id']);
  if('context'===$row['kind']){
   $object=self::object_id($row);$raw=$object?self::raw($object):array();$ref=self::reference($raw);$cfg=self::config();
   $runtime=is_array($raw['runtime']??null)?$raw['runtime']:array();$bound=(int)$row['lesson_id']||'course'===self::target($row);$pending=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table()." WHERE kind='video' AND user_id=%d AND course_id=%d AND lesson_id=%d AND target=%s AND state IN ('uploading','processing') AND expires>%d".($bound?'':' AND context_id=%s')." ORDER BY created DESC LIMIT 1",...array_merge(array(get_current_user_id(),(int)$row['course_id'],(int)$row['lesson_id'],self::target($row),time()),$bound?array():array($row['id']))),ARRAY_A);$out['pending']=$pending?self::view($pending):null;$choice=$row['selected_id']?self::row($row['selected_id']):null;$out['candidate']=$choice&&'use'===$row['selection']&&'ready'===$choice['state']&&self::target($choice)===self::target($row)&&self::course((int)$choice['course_id'])?self::view($choice):null;
   $out['current']=array('duration'=>(int)($runtime['hours']??0)*3600+(int)($runtime['minutes']??0)*60+(int)($runtime['seconds']??0),'has_video'=>self::identity($raw)!==self::identity(array()),'preview'=>$ref&&(string)$ref['library']===(string)$cfg['library_id']?self::preview($ref['video']):null);
  }
  return $out;
 }
 static function permission($request){return self::enabled() && get_current_user_id() && wp_verify_nonce($request->get_header('X-WP-Nonce'),'wp_rest') && (current_user_can('manage_cas_content') || current_user_can('manage_options')) && current_user_can('upload_files')?true:self::error('forbidden','Sign in with an authorised content account.',403);}
 static function routes(){
  foreach(array('contexts','uploads','library','contexts/(?P<id>[a-f0-9]{32})','contexts/(?P<id>[a-f0-9]{32})/selection','uploads/(?P<id>[a-f0-9]{32})','uploads/(?P<id>[a-f0-9]{32})/authorisation','uploads/(?P<id>[a-f0-9]{32})/cancel') as $route){
   $method=in_array($route,array('library','contexts/(?P<id>[a-f0-9]{32})','uploads/(?P<id>[a-f0-9]{32})'),true)?'GET':'POST';
   register_rest_route('cas-video/v1','/'.$route,array('methods'=>$method,'permission_callback'=>array(__CLASS__,'permission'),'callback'=>static function($r)use($route){try{return CAS_Bunny_Uploader::api($route,$r);}catch(Throwable $e){$d=json_decode($e->getMessage(),true);return CAS_Bunny_Uploader::error($d[0]??'unexpected',$d[1]??'The video operation could not be verified. Please reload.',(int)($d[2]??503));}finally{CAS_Bunny_Uploader::unlock();}}));
  }
 }
 static function api($route,$r){
  global $wpdb;$course=absint($r['course']);$id=$r['id'];
  if('contexts'===$route)return self::view(self::context($course,absint($r['lesson']),$r['target']??'lesson'));
  if('library'===$route){
   $target=$r['target']??'lesson';if(!in_array($target,array('lesson','course'),true))self::fail('target','Invalid video destination.');
   if(!self::course($course))self::fail('forbidden','You cannot open this video list.',403);
   $owned=get_posts(array('post_type'=>'courses','post_status'=>array('publish','draft','pending','private','future'),'author'=>current_user_can('manage_options')?'':get_current_user_id(),'fields'=>'ids','numberposts'=>-1));$owned=array_filter(array_map('absint',$owned),array(__CLASS__,'course'));if(!$owned)return array();$rows=$wpdb->get_results('SELECT * FROM '.self::table()." WHERE kind IN ('video','catalog') AND state='ready' AND target='".esc_sql($target)."' AND course_id IN (".implode(',',$owned).") ORDER BY created DESC LIMIT 200",ARRAY_A);$out=array();
   foreach($rows as $row)if(self::course((int)$row['course_id'])){$v=self::view($row);$v['title']=html_entity_decode(self::data($row)['title']??get_the_title((int)$row['lesson_id']),ENT_QUOTES,'UTF-8');$out[]=$v;}return $out;
  }
  if('uploads'===$route){
   $ctx=self::row($r['context']);self::authority($ctx);if('context'!==$ctx['kind']||'editing'!==$ctx['state']||(int)$ctx['expires']<time())self::fail('expired','Reopen the lesson before uploading.',409);
   $request=$r['request'];if(!is_string($request)||!preg_match('/^[a-f0-9]{32}$/',$request))self::fail('request','Invalid upload request.');
   $title=sanitize_text_field((string)$r['title']);if(!$title||strlen($title)>500)self::fail('title','Enter a lesson name before uploading.');
   $key=hash('sha256',get_current_user_id().':'.$ctx['id'].':'.$request);$existing=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table().' WHERE request_key=%s',$key));if($existing)return self::view(self::row($existing));
   self::lock((int)$ctx['course_id']);$count=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.self::table()." WHERE kind='video' AND user_id=%d AND created>%d",get_current_user_id(),time()-DAY_IN_SECONDS));if($count>=20)self::fail('quota','The daily upload-job allowance has been reached. Ask the administrator.',429);
   $active=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.self::table()." WHERE kind='video' AND user_id=%d AND state IN ('creating','uploading','processing') AND expires>%d",get_current_user_id(),time()));if($active>=2)self::fail('active','Finish or cancel an existing upload first.',429);
   $job=self::insert('video',(int)$ctx['course_id'],(int)$ctx['lesson_id'],array('target'=>self::target($ctx),'context_id'=>$ctx['id'],'state'=>'creating','request_key'=>$key,'data'=>wp_json_encode(array('title'=>$title))));self::unlock();
   try{$v=self::http('POST','',array('title'=>$title.' ['.substr($job['id'],0,8).']'));if((isset($v['videoLibraryId'])&&(string)$v['videoLibraryId']!==(string)self::config()['library_id'])||!preg_match('/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/i',$v['guid']??''))self::fail('provider','Bunny did not confirm a video ID.',503);self::update($job['id'],array('video_id'=>strtolower($v['guid'])));$wpdb->query($wpdb->prepare('UPDATE '.self::table()." SET state='uploading' WHERE id=%s AND state='creating'",$job['id']));}catch(Throwable $e){$wpdb->query($wpdb->prepare('UPDATE '.self::table()." SET state='unknown' WHERE id=%s AND state='creating'",$job['id']));throw $e;}
   return self::view(self::row($job['id']));
  }
  $row=self::row($id);self::authority($row);if(str_starts_with($route,'contexts/')){
   if('context'!==$row['kind'])self::fail('wrong_job','Invalid editor session.');
   if(str_ends_with($route,'/selection')){
    if('editing'!==$row['state']||(int)$row['expires']<time())self::fail('saved','Reopen the lesson before choosing another video.',409);
    $selection=$r['selection'];if(!in_array($selection,array('keep','use','remove'),true))self::fail('selection','Invalid video choice.');$chosen='';
    if('use'===$selection){$job=self::row($r['video']);self::authority($job,false);if(self::target($job)!==self::target($row))self::fail('target','Choose a film approved for this destination.',403);if('course'===self::target($row)&&true!==$r->get_param('public_preview'))self::fail('public_preview','Confirm that this film is approved for the public course preview.',403);if(!in_array($job['kind'],array('video','catalog'),true))self::fail('wrong_job','Invalid video choice.');$job=self::refresh($job);if('ready'!==$job['state'])self::fail('not_ready','The video is not ready yet.',409);$chosen=$job['id'];}
    self::lock((int)$row['course_id']);$row=self::row($id);if('editing'!==$row['state']||(int)$row['expires']<time())self::fail('saved','Reopen the lesson.',409);$data=self::data($row);$data['public_ack']='course'===self::target($row)&&'use'===$selection?$chosen:'';self::update($id,array('selection'=>$selection,'selected_id'=>$chosen,'revision'=>(int)$row['revision']+1,'data'=>wp_json_encode($data)));return self::view(self::row($id));
   }return self::view($row);
  }
  if('video'!==$row['kind'])self::fail('wrong_job','Invalid upload job.');
  if(str_ends_with($route,'/cancel')){self::lock((int)$row['course_id']);$ctx=self::row($row['context_id']);if(!$ctx||'editing'!==$ctx['state'])self::fail('saved','This video may already be saved. Reopen the lesson to check.',409);$changed=$wpdb->query($wpdb->prepare('UPDATE '.self::table()." SET state='cancelled' WHERE id=%s AND state IN ('creating','uploading','processing','failed','unknown')",$id));if(1!==$changed)self::fail('complete','The upload is complete or already cancelled. Keep or remove its lesson selection instead.',409);if($ctx['selected_id']===$id)self::update($ctx['id'],array('selected_id'=>'','selection'=>'keep','revision'=>(int)$ctx['revision']+1));return self::view(self::row($id));}
  if(str_ends_with($route,'/authorisation')){
   $ctx=self::row($row['context_id']);$next=$r['context']?self::row($r['context']):null;if($next){self::authority($next);if('context'!==$next['kind']||'editing'!==$next['state']||(int)$next['course_id']!==(int)$row['course_id']||self::target($next)!==self::target($row)||((int)$row['lesson_id'] && (int)$next['lesson_id']!==(int)$row['lesson_id']))self::fail('context','Resume this upload in its original lesson.',409);self::update($row['id'],array('context_id'=>$next['id']));$ctx=$next;}if(!$ctx||'editing'!==$ctx['state']||(int)$ctx['expires']<time()||!in_array($row['state'],array('uploading','processing'),true)||(int)$row['expires']<time()||!$row['video_id'])self::fail('expired','This upload cannot be resumed. Check its status.',409);
   $cfg=self::config();$expires=min((int)$row['expires'],time()+2*HOUR_IN_SECONDS);$endpoint='https://video.bunnycdn.com/tusupload';if('local'===wp_get_environment_type())$endpoint=apply_filters('cas_bunny_test_upload_endpoint',$endpoint,$row);
   return array('endpoint'=>$endpoint,'video'=>$row['video_id'],'library'=>(string)$cfg['library_id'],'expires'=>$expires,'signature'=>hash('sha256',$cfg['library_id'].$cfg['library_key'].$expires.$row['video_id']));
  }
  return self::view(self::refresh($row));
 }
 static function editor(){
  $course=isset($_GET['course_id'])&&is_scalar($_GET['course_id'])?absint($_GET['course_id']):0;
  if(!self::course($course))return;
  if(!self::enabled()){echo '<p class="notice notice-info">Lesson video uploads are awaiting private server configuration and provider verification. Existing videos are unchanged.</p>';return;}
  wp_enqueue_script('cas-bunny-tus',plugins_url('vendor/tus.min.js',__FILE__),array(),'4.3.1',true);
  wp_enqueue_script('cas-bunny-editor',plugins_url('assets/editor.js',__FILE__),array('cas-bunny-tus','wp-element','tutor-course-builder'),'1.1.2',true);
  wp_enqueue_style('cas-bunny-editor',plugins_url('assets/editor.css',__FILE__),array(),'1.1.2');
  wp_localize_script('cas-bunny-editor','casBunnyEditor',array('course'=>$course,'user'=>get_current_user_id(),'root'=>rest_url('cas-video/v1/'),'nonce'=>wp_create_nonce('wp_rest')));
 }
 static function visibility($data){
  $course=isset($_GET['course_id'])&&is_scalar($_GET['course_id'])?absint($_GET['course_id']):0;
  if(self::enabled()&&self::course($course)){
   $role=current_user_can('administrator')?'admin':'instructor';
   if(!is_array($data['visibility_control']??null))$data['visibility_control']=array();if(!is_array($data['visibility_control']['course_builder']??null))$data['visibility_control']['course_builder']=array();
   $data['visibility_control']['course_builder']['basics_intro_video_'.$role]='off';
   if(!(current_user_can('manage_options')&&!empty($_GET['cas_bunny_advanced']))){$data['visibility_control']['course_builder']['curriculum_lesson_video_'.$role]='off';
   $data['visibility_control']['course_builder']['curriculum_lesson_video_playback_time_'.$role]='off';}
  }return $data;
 }
 static function details($data,$id){
  if(!self::enabled())return $data;
  try{$course=self::lesson_course((int)$id);if(self::course($course)){$ctx=self::context($course,(int)$id);$data['_cas_bunny_edit']=$ctx['id'].':0';}}catch(Throwable $e){$data['_cas_bunny_edit']='';}
  return $data;
 }
 static function course_details($data){
  if(!self::enabled())return $data;
  try{$course=absint($data['ID']??0);if(self::course($course)){$ctx=self::context($course,0,'course');$data['_cas_bunny_course_edit']=$ctx['id'].':0';}}catch(Throwable $e){$data['_cas_bunny_course_edit']='';}
  return $data;
 }
 static function course_start(){self::begin('course');}
 static function course_finish($id){if(self::$tx&&'course'===self::$tx['target'])self::save_finish($id);}
 static function save_start(){self::begin('lesson');}
 static function begin($target){
  if(!self::enabled())return;
  try{
   if(!function_exists('cas_builder_nonce_valid')||!cas_builder_nonce_valid()||'POST'!==($_SERVER['REQUEST_METHOD']??''))self::fail('nonce','Reopen the editor and try again.',403);
   $courseMode='course'===$target;
   $topic=isset($_POST['topic_id'])&&is_scalar($_POST['topic_id'])?absint($_POST['topic_id']):0;
   $course=$courseMode?(isset($_POST['course_id'])&&is_scalar($_POST['course_id'])?absint($_POST['course_id']):0):self::topic_course($topic);
   $lesson=$courseMode?0:(isset($_POST['lesson_id'])&&is_scalar($_POST['lesson_id'])?absint($_POST['lesson_id']):0);$object=$courseMode?$course:$lesson;
   if(!self::course($course)||($lesson&&self::lesson_course($lesson)!==$course)||($lesson&&!current_user_can('edit_post',$lesson)))self::fail('forbidden','You cannot save this video destination.',403);
   $field=$courseMode?'_cas_bunny_course_edit':'_cas_bunny_edit';$token=$_POST[$field]??'';if(!is_string($token))self::fail('context','Invalid editor session.');
   $ctx=null;$revision=0;
   if($token){
    if(!preg_match('/^([a-f0-9]{32}):([0-9]+)$/',$token,$m))self::fail('context','Reopen the editor before saving.',409);
    $ctx=self::row($m[1]);$revision=(int)$m[2];self::authority($ctx);
    if('context'!==$ctx['kind']||self::target($ctx)!==$target||(int)$ctx['course_id']!==$course||(int)('saved'===$ctx['state']?(self::data($ctx)['initial_lesson']??$ctx['lesson_id']):$ctx['lesson_id'])!==$lesson)self::fail('context','The video selection belongs to a different destination.',409);
   }
   if(!$courseMode&&$lesson&&!$ctx)self::fail('context','Reopen the lesson to load its current video before saving.',409);
   $posted=is_array($_POST['video']??null)?wp_unslash($_POST['video']):array();$payload=wp_unslash($_POST);unset($payload['_tutor_nonce'],$payload['_wpnonce']);$payload['video']=self::identity($posted);$fingerprint=hash('sha256',wp_json_encode($payload));
   if($ctx&&'saved'===$ctx['state']){
    $savedObject=$courseMode?$course:(int)$ctx['lesson_id'];
    if((self::data($ctx)['payload']??'')===$fingerprint&&(self::data($ctx)['saved_identity']??'')===self::identity(self::raw($savedObject)))wp_send_json(array('status_code'=>200,'message'=>'This change was already saved.','data'=>$savedObject));
    self::fail('used','This editor session was already saved. Reopen the editor.',409);
   }
   $selected=null;
   if($ctx&&'use'===$ctx['selection']&&0!==$revision){
    $selected=self::row($ctx['selected_id']);self::authority($selected,false);
    if(self::target($selected)!==$target)self::fail('target','Choose a film approved for this destination.',403);
    $selected=self::refresh($selected);if('ready'!==$selected['state'])self::fail('ready','Wait until the chosen video is ready before saving.',409);
   }
   self::lock($course);global $wpdb;$tables=array($wpdb->posts,$wpdb->postmeta,self::table());$formats=implode(',',array_fill(0,3,'%s'));
   $engines=$wpdb->get_col($wpdb->prepare("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ($formats)",$tables));
   if(count($engines)!==3||array_filter($engines,static function($engine){return 'INNODB'!==strtoupper($engine);}))self::fail('engine','Video saving needs transactional database tables. Contact the administrator.',503);
   if(false===$wpdb->query('START TRANSACTION'))self::fail('transaction','The save could not be started.',503);
   self::$tx=array('target'=>$target,'object'=>$object,'course'=>$course,'lesson'=>$lesson,'context'=>$ctx?$ctx['id']:null,'selected'=>$selected?$selected['id']:null,'payload'=>$fingerprint,'initial_lesson'=>$lesson,'expected'=>null,'connection'=>self::connection(),'touched'=>array($object));
   $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$course));
   if(!$courseMode){$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$topic));if($lesson)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE ID=%d FOR UPDATE",$lesson));}
   if(!self::course($course)||(!$courseMode&&self::topic_course($topic)!==$course)||($lesson&&self::lesson_course($lesson)!==$course))self::fail('moved','The course or lesson changed. Reopen the editor.',409);
   $raw=$object?self::raw($object):array();
   if($ctx){
    $wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table().' WHERE id=%s FOR UPDATE',$ctx['id']));$ctx=self::row($ctx['id']);
    if(0===$revision&&(int)$ctx['revision']>0){$ctx['selection']='keep';$ctx['selected_id']='';$selected=null;self::$tx['selected']=null;self::update($ctx['id'],array('selection'=>'keep','selected_id'=>''));}
    if('editing'!==$ctx['state']||(0!==$revision&&(int)$ctx['revision']!==$revision)||(int)$ctx['expires']<time())self::fail('stale','The video choice changed. Reopen the editor.',409);
    if((self::data($ctx)['base']??'')!==self::identity($raw))self::fail('conflict','The saved video changed in another editor. Reopen it to keep the latest version.',409);
   }
   if($selected){
    $selected=self::row($selected['id']);if('ready'!==$selected['state'])self::fail('cancelled','The chosen upload is no longer available.',409);
    if($courseMode&&(self::data($ctx)['public_ack']??'')!==$selected['id'])self::fail('public_preview','Confirm this film is approved for the public course preview.',403);
    $cfg=self::config();$seconds=(int)(self::data($selected)['duration']??0);
    $posted=array('source'=>'embedded','source_embedded'=>'<iframe src="https://iframe.mediadelivery.net/embed/'.(int)$cfg['library_id'].'/'.$selected['video_id'].'?autoplay=false&amp;preload=false" loading="lazy" allow="accelerometer;gyroscope;encrypted-media;picture-in-picture" allowfullscreen></iframe>','runtime'=>array('hours'=>(int)floor($seconds/3600),'minutes'=>(int)floor($seconds%3600/60),'seconds'=>$seconds%60));
   }elseif($ctx&&'remove'===$ctx['selection']){$posted=array();if($courseMode)unset($_POST['video']);else $_POST['video']=array('source'=>'-1');}
   elseif($courseMode||!current_user_can('manage_options')){
    if(self::identity($posted)!==self::identity($raw))self::fail('unapproved','Use the upload or approved-film picker for a new film.',403);$posted=$raw;
   }
   if(!($ctx&&'remove'===$ctx['selection'])){
    if(!empty($posted['source_embedded']))$posted['source_embedded']=preg_replace_callback('~https://(?:iframe|video)\.mediadelivery\.net/[^"\'\s<>]+~i',static function($m){return remove_query_arg(array('token','expires'),html_entity_decode($m[0],ENT_QUOTES,'UTF-8'));},$posted['source_embedded']);
    if($courseMode&&!$posted)unset($_POST['video']);else $_POST['video']=$posted?wp_slash($posted):array('source'=>'-1');
   }
   self::$tx['expected']=self::identity($posted);
  }catch(Throwable $e){self::rollback();$d=json_decode($e->getMessage(),true);wp_send_json(array('status_code'=>(int)($d[2]??503),'message'=>$d[1]??'The save could not be verified. Reopen the editor before retrying.'),(int)($d[2]??503));}
 }
 static function guard_meta($check,$id,$key,$value=null,$previous=null){
  if('_video'!==$key||!in_array(get_post_type($id),array('lesson','courses'),true)||!self::enabled())return $check;
  try{
   $type=get_post_type($id);$course='courses'===$type?(int)$id:self::lesson_course((int)$id);if(!$course)self::fail('graph','The lesson has no valid owning course.',403);
   if(self::$tx){if(self::connection()!==self::$tx['connection'])self::fail('uncertain','The database connection changed. Check the lesson before retrying.',503);if($course!==self::$tx['course']||('course'===self::$tx['target']?'courses'!==$type||self::$tx['object']!==(int)$id:'lesson'!==$type||(self::$tx['lesson']&&(int)$id!==self::$tx['lesson'])))self::fail('target','The save targeted a different video destination.',409);self::$tx['object']=(int)$id;if('lesson'===self::$tx['target'])self::$tx['lesson']=(int)$id;self::$tx['touched'][]=(int)$id;}
   else{if(!((defined('WP_CLI')&&WP_CLI)||(current_user_can('manage_options')&&current_user_can('edit_post',$id))))self::fail('context','Use the native lesson editor to change its video.',403);self::lock($course);}
  }catch(Throwable $e){self::rollback();$d=json_decode($e->getMessage(),true);wp_die(esc_html($d[1]??'The video write was refused.'),'',array('response'=>(int)($d[2]??403)));}return $check;
 }
 static function preserve_author($data,$postarr,$raw,$update){if(self::$tx && $update && in_array($data['post_type']??'',array('lesson','courses'),true) && !empty($postarr['ID'])){$post=get_post((int)$postarr['ID']);if($post)$data['post_author']=$post->post_author;}return $data;}
 static function save_finish($id){
  if(!self::$tx)return;
  try{global $wpdb;$tx=self::$tx;$id=(int)$id;
   if('lesson'===$tx['target']&&!$tx['lesson']&&self::lesson_course($id)===$tx['course']){self::$tx['lesson']=$id;self::$tx['object']=$id;self::$tx['touched'][]=$id;$tx=self::$tx;}if($tx['object']!==$id||('lesson'===$tx['target']?self::lesson_course($id)!==$tx['course']:$id!==$tx['course'])||self::identity(self::raw($id))!==$tx['expected'])self::fail('readback','The saved video could not be verified.',503);
   if(self::connection()!==$tx['connection'])self::fail('uncertain','The database connection changed. Check the saved lesson before retrying.',503);if($tx['context']){self::update($tx['context'],array('state'=>'saved','lesson_id'=>'lesson'===$tx['target']?$id:0,'data'=>wp_json_encode(array('initial_lesson'=>$tx['initial_lesson'],'payload'=>$tx['payload'],'saved_identity'=>$tx['expected']))));}
   if('lesson'===$tx['target'])$wpdb->query($wpdb->prepare('UPDATE '.self::table()." SET lesson_id=%d WHERE context_id=%s AND kind='video' AND lesson_id=0 AND target='lesson'",$id,$tx['context']??''));
   if($tx['selected']){$job=self::row($tx['selected']);if(!$job||'ready'!==$job['state'])self::fail('cancelled','The chosen video changed before the save completed.',409);if('lesson'===$tx['target']&&!(int)$job['lesson_id'])self::update($job['id'],array('lesson_id'=>$id));}
   if(self::connection()!==$tx['connection'])self::fail('uncertain','The database connection changed. Check the saved lesson before retrying.',503);if(false===$wpdb->query('COMMIT'))self::fail('commit','The save could not be confirmed.',503);self::$tx=null;self::unlock();
  }catch(Throwable $e){self::rollback();$d=json_decode($e->getMessage(),true);wp_send_json(array('status_code'=>503,'message'=>$d[1]??'The lesson save failed and was rolled back.'),503);}
 }
 static function connection(){global $wpdb;try{return $wpdb->dbh instanceof mysqli?(int)mysqli_thread_id($wpdb->dbh):0;}catch(Throwable $e){return 0;}}
 static function rollback(){if(self::$tx){global $wpdb;$uncertain=self::connection()!==self::$tx['connection'];$wpdb->query('ROLLBACK');if($uncertain&&self::$tx['context'])$wpdb->update(self::table(),array('state'=>'uncertain'),array('id'=>self::$tx['context']));foreach(self::$tx['touched'] as $id)if($id){clean_post_cache($id);wp_cache_delete($id,'post_meta');}self::$tx=null;}self::unlock();}
 static function shutdown(){self::rollback();self::unlock();}
 static function import_references($approved,$target='lesson'){
  if(!current_user_can('manage_options'))self::fail('forbidden','Administrator import required.',403);
  global $wpdb;$cfg=self::config();$created=array();if(!in_array($target,array('lesson','course'),true))self::fail('target','Invalid catalogue destination.');
  foreach($approved as $item){$object=(int)($item['lesson']??$item['course']??0);$lesson='lesson'===$target?$object:0;$raw=self::raw($object);if(!hash_equals((string)($item['fingerprint']??''),hash('sha256',wp_json_encode($raw))))self::fail('preimage','The approved lesson video changed before catalogue import.',409);$ref=self::reference($raw);$course='course'===$target&&'courses'===get_post_type($object)?$object:self::lesson_course($lesson);if(!$ref||!$course||(string)$ref['library']!==(string)($cfg['library_id']??''))self::fail('library','The approved reference is outside this configured library.',409);
   $old=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table()." WHERE video_id=%s AND course_id=%d AND kind='catalog' AND target=%s",$ref['video'],$course,$target));if($old){$created[]=$old;continue;}
   $runtime=$raw['runtime']??array();$duration=(int)($runtime['hours']??0)*3600+(int)($runtime['minutes']??0)*60+(int)($runtime['seconds']??0);
   $row=self::insert('catalog',$course,$lesson,array('target'=>$target,'state'=>'ready','video_id'=>$ref['video'],'data'=>wp_json_encode(array('duration'=>$duration,'title'=>get_the_title($object)))));$created[]=$row['id'];
  }return $created;
 }

}
