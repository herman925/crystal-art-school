<?php
// The theme files under test, loaded as they are, plus stand-ins for the two theme helpers that need the rest of the theme.
require_once "/theme-src/inc/cas-courses.php";
require_once "/theme-src/inc/cas-course-single.php";
if ( ! function_exists( "cas_page_url" ) ) { function cas_page_url( $slug ) { return home_url( "/" . $slug . "/" ); } }
if ( ! function_exists( "cas_course_free_video_cover" ) ) { function cas_course_free_video_cover() { return ""; } }
