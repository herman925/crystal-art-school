<?php
// Local test of the course page "What comes with it" section (issue #142) against Tutor 4.1.0 (free).
// Loads inc/cas-course-single.php as it is and renders the real course page markup for four cases.
$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = 0;
function t( $name, $ok, $extra = '' ) {
	$ok ? $GLOBALS['pass']++ : $GLOBALS['fail']++;
	echo ( $ok ? 'PASS ' : 'FAIL ' ) . $name . ( $extra ? '  [' . $extra . ']' : '' ) . "\n";
}

const EMPTY_TEXT = 'No Attachment Found';

$mk_course = function ( $title, $materials = '', array $attachment_ids = array() ) {
	$id = wp_insert_post( array( 'post_type' => 'courses', 'post_title' => $title, 'post_status' => 'publish' ) );
	if ( '' !== $materials ) {
		update_post_meta( $id, '_tutor_course_material_includes', $materials );
	}
	if ( $attachment_ids ) {
		update_post_meta( $id, '_tutor_attachments', $attachment_ids );
	}
	return $id;
};
$mk_file = function ( $name ) {
	$up   = wp_upload_dir();
	$path = $up['path'] . '/' . $name;
	file_put_contents( $path, 'sample ' . $name );
	return wp_insert_attachment( array( 'post_title' => $name, 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), $path );
};
// The page's markup, with the course as the current post (as the shortcode sees it).
$page = function ( $course_id ) {
	global $post;
	$post = get_post( $course_id );
	setup_postdata( $post );
	$html = cas_render_course_single();
	wp_reset_postdata();
	return $html;
};

$a1 = $mk_file( 'reference-sheet.pdf' );
$a2 = $mk_file( 'colour-chart.pdf' );

$none     = $mk_course( 'No extras' );
$text     = $mk_course( 'Text only', "Watercolour paper\nA flat brush" );
$files    = $mk_course( 'Files only', '', array( $a1, $a2 ) );
$both     = $mk_course( 'Text and files', "Watercolour paper\nA flat brush", array( $a1, $a2 ) );

// 0. The probe: Tutor's own template does print the stock placeholder for a course with no attachments.
$GLOBALS['post'] = get_post( $none );
setup_postdata( $GLOBALS['post'] );
ob_start();
tutor_load_template( 'global.attachments' );
$stock = ob_get_clean();
wp_reset_postdata();
t( '0 probe: Tutor\'s stock template prints the placeholder for a course with no attachments', str_contains( $stock, EMPTY_TEXT ) );

// 1. The renderer.
t( '1a no attachments: the renderer returns nothing', '' === cas_render_course_attachments( $none ) );
t( '1b a course that only has text lines: the renderer returns nothing', '' === cas_render_course_attachments( $text ) );
$GLOBALS['post'] = get_post( $files );
setup_postdata( $GLOBALS['post'] );
$shelf = cas_render_course_attachments( $files );
wp_reset_postdata();
t( '1c two attachments: both names are in the shelf, no placeholder', str_contains( $shelf, 'reference-sheet.pdf' ) && str_contains( $shelf, 'colour-chart.pdf' ) && ! str_contains( $shelf, EMPTY_TEXT ), substr( wp_strip_all_tags( $shelf ), 0, 120 ) );

// 2. The page, four cases.
$h = $page( $none );
t( '2a no text, no attachments: the whole section is left out', ! str_contains( $h, 'id="materials"' ) && ! str_contains( $h, 'What comes with it' ) );
t( '2b no text, no attachments: no placeholder text and no empty-state picture anywhere on the page', ! str_contains( $h, EMPTY_TEXT ) && ! str_contains( $h, 'emptystate' ) );
$h = $page( $text );
t( '2c text lines only: the section shows them', str_contains( $h, 'id="materials"' ) && str_contains( $h, 'Watercolour paper' ) && str_contains( $h, 'A flat brush' ) );
t( '2d text lines only: no placeholder, no empty shelf box', ! str_contains( $h, EMPTY_TEXT ) && ! str_contains( $h, 'cas-course-attachments' ) );
$h = $page( $files );
t( '2e attachments only: the section appears again with the shelf', str_contains( $h, 'id="materials"' ) && str_contains( $h, 'What comes with it' ) && str_contains( $h, 'cas-course-attachments' ) && str_contains( $h, 'reference-sheet.pdf' ) && str_contains( $h, 'colour-chart.pdf' ) );
t( '2f attachments only: no text list and no placeholder', ! str_contains( $h, 'learn-list' ) && ! str_contains( $h, EMPTY_TEXT ) );
$h = $page( $both );
t( '2g text and attachments: both show', str_contains( $h, 'learn-list' ) && str_contains( $h, 'A flat brush' ) && str_contains( $h, 'cas-course-attachments' ) && str_contains( $h, 'colour-chart.pdf' ) && ! str_contains( $h, EMPTY_TEXT ) );

// 3. A wrong value in the attachment meta does not bring the placeholder back.
update_post_meta( $none, '_tutor_attachments', 'not-an-array' );
t( '3a a non-array meta value: no shelf, no placeholder', '' === cas_render_course_attachments( $none ) && ! str_contains( $page( $none ), EMPTY_TEXT ) );
update_post_meta( $none, '_tutor_attachments', array() );
t( '3b an empty array meta value: no shelf, no placeholder', '' === cas_render_course_attachments( $none ) && ! str_contains( $page( $none ), EMPTY_TEXT ) );

// 4. A page for a post that is not a course renders nothing (unchanged behaviour).
$not_course = wp_insert_post( array( 'post_type' => 'post', 'post_title' => 'Blog', 'post_status' => 'publish' ) );
t( '4 a blog post gets no course markup', '' === $page( $not_course ) );

echo "\npass=" . $GLOBALS['pass'] . ' fail=' . $GLOBALS['fail'] . "\n";
