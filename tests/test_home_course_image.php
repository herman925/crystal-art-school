<?php
/** Real save/response/card functions with synthetic images; no WordPress writes. */
define('ABSPATH',__DIR__);$hooks=[];$meta=[34=>['_cas_course_photo_large'=>10,'_cas_course_photo_1'=>20]];
function add_action($name,$fn,...$args){$GLOBALS['hooks'][$name][]=$fn;}
function add_filter($name,$fn,...$args){add_action($name,$fn);}
function add_shortcode(...$args){}
function home_url($path){return 'https://example.test'.$path;}
function do_action($name,...$args){$GLOBALS['events'][]=[$name,$args];}
class WP_Error{public function __construct(...$args){}}
function is_wp_error($v){return $v instanceof WP_Error;}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function update_post_meta($id,$key,$v){$GLOBALS['meta'][$id][$key]=$v;}
function delete_post_meta($id,$key){unset($GLOBALS['meta'][$id][$key]);}
function wp_attachment_is_image($id){return in_array($id,[10,20,30,40,901],true);}
function get_post_status($id){return $id===40?'trash':'inherit';}
function cas_private_is($id){return $id===30;}
function current_user_can($cap,$id=null){return $GLOBALS['allowed']??true;}
function cas_builder_nonce_valid(){return $GLOBALS['nonce']??true;}
function get_post_type($id){return $id===34?'courses':'attachment';}
function wp_unslash($v){return is_string($v)?stripslashes($v):$v;}
function get_post_thumbnail_id($id){return 901;}
function get_the_title($id){return 'Synthetic course';}
function get_permalink($id){return '/course/'.$id;}
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_html($s);}
function wp_get_attachment_image_src($id,$size){return ['/image/'.$id.'.jpg',200,200];}
function wp_get_attachment_image_srcset(...$args){return false;}
function wp_get_attachment_image_url($id,$size){return '/image/'.$id.'.jpg';}
function wp_get_post_terms(...$args){return ['Watercolour'];}
function cas_course_facets($id){return ['medium'=>['watercolour'],'difficulty'=>'beginner','regular'=>20,'now'=>20,'is_sale'=>false];}
function cas_course_price($v){return '£'.$v;}
function tutor_utils(){return new class{function get_course_content_ids_by(...$args){return [];}};}
require dirname(__DIR__).'/wp-content/themes/hello-elementor-child/inc/cas-course-single.php';
require dirname(__DIR__).'/wp-content/themes/hello-elementor-child/inc/cas-home-courses.php';
for($i=0;$i<10;$i++)assert(cas_home_course_photo_id(34)===10,'default is deterministic Gallery Photo 1');
$save=$hooks['tutor_after_prepare_update_post_meta'][0];
$save(34,['_cas_home_course_image'=>['id'=>20,'url'=>'https://untrusted.invalid/a.jpg']]);
assert(cas_home_course_photo_id(34)===20);
$html=cas_home_course_card(34,0);
assert(preg_match('~class="cc-cover"[^>]*><img src="/image/901.jpg"~',$html));
assert(preg_match('~class="cc-photo"[^>]*>\s*<img src="/image/20.jpg"~',$html));
assert(count(cas_course_photo_fields())===5&&count(cas_course_photo_set(34))===2,'override is not a sixth gallery image');
$response=['ID'=>34];foreach($hooks['tutor_course_details_response'] as $filter){$response=$filter($response);}
assert($response['_cas_home_course_image']['id']===20,'native editor reloads saved choice');
$before=$meta;$save(34,['_cas_home_course_image'=>['id'=>30],'_cas_course_photo_large'=>20]);assert($meta===$before,'private image rejects all writes');
foreach([30,40,999,-1,true,['id'=>[]]] as $value)assert(is_wp_error(cas_course_builder_photo_ids(['_cas_home_course_image'=>$value])));
$nonce=false;$save(34,['_cas_home_course_image'=>10]);assert($meta===$before);$nonce=true;
$allowed=false;$save(34,['_cas_home_course_image'=>10]);assert($meta===$before);$allowed=true;
$save(34,[]);assert($meta===$before,'omitted field preserves explicit choice');
$save(34,['_cas_home_course_image'=>null]);assert(!isset($meta[34]['_cas_home_course_image'])&&cas_home_course_photo_id(34)===10,'Remove restores default');
$meta[34]['_cas_course_photo_large']=20;assert(cas_home_course_photo_id(34)===20,'default follows later Photo 1 changes');
$meta[34]['_cas_home_course_source']='custom';$meta[34]['_cas_home_course_image']=10;assert(cas_home_course_photo_id(34)===10,'explicit choice remains independent');
$meta[34]['_cas_home_course_image']=40;assert(cas_home_course_photo_id(34)===20,'trashed override falls back');
$meta[34]['_cas_home_course_image']=30;assert(cas_home_course_photo_id(34)===20,'private override never leaks');
unset($meta[34]['_cas_course_photo_large']);assert(cas_home_course_photo_id(34)===0,'missing default never substitutes cover or another gallery slot');
$slots=array_keys(cas_course_photo_fields());foreach($slots as $i=>$slot){$meta[34][$slot]=$i%2?20:10;}
foreach(range(1,5) as $n){$save(34,['_cas_home_course_source'=>'gallery_'.$n]);assert(cas_home_course_photo_id(34)===($n%2?10:20),'each gallery source follows its own slot');}
$save(34,['_cas_home_course_source'=>'gallery_3']);$meta[34]['_cas_course_photo_2']=20;assert(cas_home_course_photo_id(34)===20,'slot source follows a later replacement');
unset($meta[34]['_cas_course_photo_2']);assert(cas_home_course_photo_id(34)===0,'empty selected slot does not substitute a different photo');
$before=$meta;$save(34,['_cas_home_course_source'=>'bad','_cas_home_course_image'=>20]);assert($meta===$before,'invalid source rejects all writes');
foreach([[],true,0,'gallery_0','gallery_6',''] as $bad)assert(is_wp_error(cas_home_course_source_param(['_cas_home_course_source'=>$bad])));
$save(34,['_cas_home_course_source'=>'custom','_cas_home_course_image'=>20]);assert(cas_home_course_photo_id(34)===20);
$save(34,['_cas_home_course_source'=>'gallery_2']);assert($meta[34]['_cas_home_course_image']===20,'gallery choice retains custom image for later reuse');
$response=['ID'=>34];foreach($hooks['tutor_course_details_response'] as $filter){$response=$filter($response);}assert($response['_cas_home_course_source']==='gallery_2','native reload restores gallery source');
$save(34,['_cas_home_course_source'=>'custom','_cas_home_course_image'=>null]);assert(cas_home_course_image_source(34)==='gallery_1','clearing custom returns to default');
unset($meta[34]['_cas_home_course_source']);$meta[34]['_cas_home_course_image']=20;assert(cas_home_course_image_source(34)==='custom','old explicit custom choice is preserved');
$events=[];cas_home_course_image_changed(1,34,'_cas_home_course_image');assert($events===[['litespeed_purge_url',['https://example.test/']]]);cas_home_course_image_changed(1,34,'unrelated');assert(count($events)===1);
echo "PASS five gallery sources, custom/legacy choices, slot replacement, native save/reload/remove, cover/gallery isolation and image guards\n";
