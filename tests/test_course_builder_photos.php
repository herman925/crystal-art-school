<?php
/** Exercise selection validation and the real native-save callback without WordPress writes. */
define( 'ABSPATH', __DIR__ );
$hooks = array();
function add_action( $name, $callback, ...$args ) { global $hooks; $hooks[ $name ][] = $callback; }
function add_filter( $name, $callback, ...$args ) { add_action( $name, $callback ); }
function add_shortcode( ...$args ) {}
class WP_Error { public function __construct( ...$args ) {} }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_unslash( $value ) { return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_attr( $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }
$fixture_meta = array();
function get_post_meta( $id, $key, $single = true ) { global $fixture_meta; return $fixture_meta[ $id ][ $key ] ?? ''; }
function get_post_field( $key, $id ) { return 'Lesson description'; }
$allowed = true;
$nonce = true;
$writes = array();
function current_user_can( $cap, $id ) { global $allowed; return $allowed && 117 !== $id; }
function wp_attachment_is_image( $id ) { return in_array( $id, array( 115, 116, 117, 118, 119 ), true ); }
function get_post_status( $id ) { return 118 === $id ? 'trash' : 'inherit'; }
function cas_private_is( $id ) { return 119 === $id; }
function get_post_type( $id ) { return 34 === $id ? 'courses' : 'post'; }
function cas_builder_nonce_valid() { global $nonce; return $nonce; }
function update_post_meta( $id, $key, $value ) { global $writes; $writes[ $key ] = $value; }
function delete_post_meta( $id, $key ) { global $writes; $writes[ $key ] = null; }
require __DIR__ . '/../wp-content/themes/hello-elementor-child/inc/cas-course-single.php';
function check( $passed, $message ) { if ( ! $passed ) { throw new RuntimeException( $message ); } }
$key = '_cas_course_photo_large';
check( array() === cas_course_builder_photo_ids( array() ), 'Missing fields must not clear photographs.' );
foreach ( array( 115, '115', array( 'id' => '115', 'url' => 'https://untrusted.invalid/ignored.jpg' ), '{"id":115,"url":"https://untrusted.invalid/ignored.jpg"}', addslashes( '{"id":115,"url":"https://untrusted.invalid/ignored.jpg"}' ) ) as $value ) {
    check( array( $key => 115 ) === cas_course_builder_photo_ids( array( $key => $value ) ), 'Native image ID must be accepted; client URLs are ignored.' );
}
foreach ( array( null, 'null', '', 0, '0', array( 'id' => 0 ), '{"id":0}' ) as $value ) {
    check( array( $key => 0 ) === cas_course_builder_photo_ids( array( $key => $value ) ), 'Explicit remove must clear its slot.' );
}
foreach ( array( -115, '115junk', true, 115.0, array(), array( 'url' => 'https://example.com/a.jpg' ), array( 'id' => array( 115 ) ), '999999999999999999999999', 999, 117, 118, 119 ) as $value ) {
    check( is_wp_error( cas_course_builder_photo_ids( array( $key => $value ) ) ), 'Malformed, missing, unreadable, trashed and private images must be rejected.' );
}
$save = $hooks['tutor_after_prepare_update_post_meta'][0];
$params = array( $key => array( 'id' => 115 ), '_cas_course_photo_1' => array( 'id' => 116 ) );
$save( 34, $params );
check( array( $key => 115, '_cas_course_photo_1' => 116 ) === $writes, 'Native save must persist the supplied slot IDs.' );
$writes = array();
$save( 34, $params + array( '_cas_course_photo_2' => array( 'id' => 119 ) ) );
check( array() === $writes, 'One invalid slot must prevent every photograph write.' );
$save( 34, array( '_cas_course_photo_1' => null ) );
check( array( '_cas_course_photo_1' => null ) === $writes, 'Remove must touch only its supplied slot.' );
foreach ( array( 'permission', 'nonce', 'post_type' ) as $case ) {
    $writes = array(); $allowed = 'permission' !== $case; $nonce = 'nonce' !== $case;
    $save( 'post_type' === $case ? 10 : 34, $params );
    check( array() === $writes, 'Native save must check ' . $case );
}
echo "PASS: native course-photo validation, partial saves, removals and authorization\n";

$allowed = true; $nonce = true;
foreach ( array( 0, -1, 101, '1.5', true, array( 2 ) ) as $value ) {
    check( is_wp_error( cas_course_lesson_values( array( '_cas_lesson_span' => $value ) ) ), 'Invalid combined-lesson counts must be rejected.' );
}
check( array( '_cas_lesson_span' => 2 ) === cas_course_lesson_values( array( '_cas_lesson_span' => '2' ) ), 'Native numeric strings must preserve combined lessons.' );
check( array() === cas_course_lesson_values( array() ), 'Missing lesson details must remain untouched.' );
check( array( '_cas_lesson_focus' => '' ) === cas_course_lesson_values( array( '_cas_lesson_focus' => '' ) ), 'A detail field can be cleared.' );
$save_lesson = $hooks['save_post_lesson'][0];
$_POST = array( 'action' => 'tutor_save_lesson', 'topic_id' => '200', '_cas_lesson_span' => '2', '_cas_lesson_focus' => 'A new focus.' );
$writes = array(); $save_lesson( 201, (object) array( 'post_parent' => 200 ) );
check( array( '_cas_lesson_span' => 2, '_cas_lesson_focus' => 'A new focus.' ) === $writes, 'Native lesson save must store just the supplied supplemental fields.' );
foreach ( array( 'permission', 'nonce', 'parent' ) as $case ) {
    $writes = array(); $allowed = 'permission' !== $case; $nonce = 'nonce' !== $case;
    $save_lesson( 201, (object) array( 'post_parent' => 'parent' === $case ? 999 : 200 ) );
    check( array() === $writes, 'Lesson supplemental save must check ' . $case );
}
$allowed = true; $nonce = true; $_POST = array();
$items = array();
for ( $i = 0; $i < 13; ++$i ) {
    $id = 201 + $i;
    $items[] = array( 'id' => $id, 'topic' => $i < 5 ? 0 : 1, 'topic_t' => $i < 5 ? 'Perspective' : 'Watercolour', 'open' => false, 'done' => false, 'title' => 'Lesson ' . $id, 'seconds' => 60, 'thumb' => 0, 'url' => 'https://example.com/lesson/' . $id );
}
$fixture_meta[202]['_cas_lesson_span'] = 2;
$fixture_meta[202]['_cas_lesson_focus'] = '<img src=x onerror=alert(1)>';
function cas_lesson_clock( $seconds ) { return '1:00'; }
$html = cas_render_course_syllabus( array( 'items' => $items ) );
check( 2 === substr_count( $html, 'class="syl-phase-btn"' ), 'Both real phases must render.' );
check( str_contains( $html, '6 lessons' ) && str_contains( $html, '8 lessons' ) && str_contains( $html, '>2 &amp; 3<' ), 'Numbered lessons must count 14 across 13 films, including 2 & 3.' );
check( 1 === substr_count( $html, 'class="syl-more"' ) && str_contains( $html, 'class="syl-detail"' ), 'Only lessons with supplied details get a disclosure control.' );
check( ! str_contains( $html, '<img src=x' ) && str_contains( $html, '&lt;img src=x' ), 'Public detail text must be escaped.' );
check( ! str_contains( $html, '<a class="syl-row"' ), 'Paid lesson rows must not link for a locked visitor.' );
$flat = cas_render_course_syllabus( array( 'items' => array( $items[0] ) ) );
check( ! str_contains( $flat, 'syl-phase-btn' ) && ! str_contains( $flat, ' hidden' ), 'A single unmodularised course must have the prototype flat syllabus.' );
echo "PASS: lesson authoring guards, combined numbering, phase totals, detail disclosures, escaping and flat syllabuses\n";

$fixture_outline = array( 'items' => array( array( 'seconds' => 9300 ), array( 'seconds' => 35 ) ) );
function cas_lesson_outline( $id ) { global $fixture_outline; return $fixture_outline; }
require __DIR__ . '/../wp-content/themes/hello-elementor-child/inc/cas-courses.php';
check( 9335 === cas_course_total_seconds( 34 ) && '2h 36m' === cas_course_runtime( 34 ), 'Course runtime must sum film seconds and round the displayed minutes.' );
$fixture_outline = array( 'items' => array( array( 'seconds' => 3595 ) ) );
check( '1h' === cas_course_runtime( 34 ), 'Rounded minutes must carry into hours.' );
$fixture_outline = array( 'items' => array() );
check( '' === cas_course_runtime( 34 ), 'A course with no timed films must not invent a duration.' );
echo "PASS: runtime derives from actual films, rounds seconds, carries hours and handles empty courses\n";
