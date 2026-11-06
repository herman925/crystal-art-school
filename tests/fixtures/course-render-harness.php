<?php
// Execute the actual complete renderer, with the native query boundary stubbed.
$source=file_get_contents(dirname(__DIR__,2).'/wp-content/themes/hello-elementor-child/inc/cas-courses.php');
$start=strpos($source,'function cas_render_courses(');
$end=strpos($source,"\nadd_shortcode( 'cas_courses'",$start);
if($start===false||$end===false){throw new RuntimeException('Renderer boundary changed');}
eval(substr($source,$start,$end-$start));
$ids=[34,50];
function cas_public_course_ids(){return $GLOBALS['ids'];}
function cas_course_facets($id){return ['id'=>$id];}
function cas_course_sort_panel(){return 'Sort';}
function cas_course_filter_rail($rows){return $rows?'Medium':'';}
function cas_course_card($id,$index){return '<article data-course="'.$id.'">'.$index.'</article>';}
function esc_html($value){return htmlspecialchars($value);}
function wp_reset_postdata(){}
function get_theme_mod($key,$default){return $default;}
function cas_courses_intro_default(){return 'Intro';}
function cas_render_courses_intro($intro){return $intro;}
function cas_render_free_trial(){return 'Preview';}
function cas_page_hero($label,$title){return '<h1>'.$title.'</h1>';}
$html=cas_render_courses([]);
assert(substr_count($html,'<article')===2);
assert(str_contains($html,'data-course="34"')&&str_contains($html,'data-course="50"'));
assert(str_contains($html,'2 courses'));
$ids=[];$empty=cas_render_courses([]);
assert(!str_contains($empty,'<article')&&str_contains($empty,'No courses are published yet.'));
echo 'Complete renderer and empty state passed';
