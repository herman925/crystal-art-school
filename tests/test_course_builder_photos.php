<?php
/** Exercise selection validation and the real native-save callback without WordPress writes. */
define( 'ABSPATH', __DIR__ );
$hooks = array();
function add_action( $name, $callback, ...$args ) { global $hooks; $hooks[ $name ][] = $callback; }
function add_filter( $name, $callback, ...$args ) { add_action( $name, $callback ); }
function add_shortcode( ...$args ) {}
class WP_Error { public function __construct( ...$args ) {} }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function wp_unslash( $value ) { return stripslashes( $value ); }
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
