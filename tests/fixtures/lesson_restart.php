<?php
namespace Tutor\Models {
	class EnrollmentModel {
		public static function is_enrolled( $course, $user ) { return 10 === $course && in_array( $user, array( 7, 8 ), true ) && empty( $GLOBALS['input']['unenrolled'] ); }
	}
}
namespace Tutor\Cache {
	class TutorCache {
		public static function set( $key, $value ) { $GLOBALS['cache'][ $key ] = $value; }
	}
}
namespace {
	if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { throw new \RuntimeException( 'Standalone fixture only' ); }
	define( 'ABSPATH', __DIR__ );
	$input = json_decode( stream_get_contents( STDIN ), true, 512, JSON_THROW_ON_ERROR );
	$uid = $input['uid'] ?? 7;
	$meta = array( '7:101' => true, '7:102' => true, '7:201' => true, '8:101' => true, '7:_lesson_reading_info' => 'retain', '7:enrolment' => 'retain', '7:order' => 'retain' );
	$comments = array( 1 => array( 7, 10, 'course_completed', 'TutorLMSPlugin' ), 2 => array( 8, 10, 'course_completed', 'TutorLMSPlugin' ), 3 => array( 7, 20, 'course_completed', 'TutorLMSPlugin' ), 4 => array( 7, 10, 'comment', 'TutorLMSPlugin' ), 5 => array( 7, 10, 'course_completed', 'OtherPlugin' ) );
	$initial = array( 'meta' => $meta, 'comments' => $comments );
	$cache = array(); $headers = false;
	class WP_Error {
		public function __construct( public $code, public $message ) {}
		public function get_error_code() { return $this->code; }
		public function get_error_message() { return $this->message; }
	}
	class FixtureDB {
		public $last_error = ''; public $comments = 'comments'; public $args;
		public function prepare( $sql, ...$args ) { $this->args = $args; return $sql; }
		public function get_col( $sql ) { if ( ! empty( $GLOBALS['input']['db_error'] ) ) { $this->last_error = 'Synthetic failure'; return null; } return array_keys( array_filter( $GLOBALS['comments'], fn( $row ) => $row === $this->args ) ); }
	}
	$wpdb = new FixtureDB();
	function add_action( ...$args ) {}
	function get_current_user_id() { return $GLOBALS['uid']; }
	function is_user_logged_in() { return get_current_user_id() > 0; }
	function get_post_type( $id ) { return array( 10 => 'courses', 11 => 'topics', 20 => 'courses', 21 => 'topics', 101 => 'lesson', 102 => 'lesson', 201 => 'lesson' )[ $id ] ?? false; }
	function get_post_status( $id ) { return $GLOBALS['input']['course_status'] ?? 'publish'; }
	function wp_get_post_parent_id( $id ) { return array( 101 => 11, 102 => 11, 201 => 21, 11 => 10, 21 => 20 )[ $id ] ?? 0; }
	function tutor() { return (object) array( 'lesson_post_type' => 'lesson', 'course_post_type' => 'courses' ); }
	function tutor_utils() { return new class { public function get_course_content_ids_by( ...$args ) { return $GLOBALS['input']['ids'] ?? array( 101, 102 ); } }; }
	function cas_lesson_outline( $course ) { return array( 'items' => empty( $GLOBALS['input']['no_first'] ) ? array( array( 'id' => 101, 'url' => '/lesson/101/' ) ) : array() ); }
	function metadata_exists( $type, $user, $key ) { return isset( $GLOBALS['meta'][ $user . ':' . str_replace( '_tutor_completed_lesson_id_', '', $key ) ] ); }
	function delete_user_meta( $user, $key ) { if ( ! empty( $GLOBALS['input']['write_error'] ) ) { return false; } unset( $GLOBALS['meta'][ $user . ':' . str_replace( '_tutor_completed_lesson_id_', '', $key ) ] ); return true; }
	function wp_delete_comment( $id, $force ) { unset( $GLOBALS['comments'][ $id ] ); return true; }
	function nocache_headers() { $GLOBALS['headers'] = true; }
	function wp_unslash( $value ) { return $value; }
	function wp_verify_nonce( $value, $action ) { return $value === 'valid:' . get_current_user_id() . ':' . $action; }
	function is_wp_error( $value ) { return $value instanceof WP_Error; }
	function esc_html( $value ) { return $value; }
	function wp_die( $message, $title, $args ) { throw new \RuntimeException( $message, $args['response'] ); }
	function wp_safe_redirect( $url, $status ) { $GLOBALS['redirect'] = array( 'url' => $url, 'status' => $status ); throw new \RuntimeException( 'Redirect', $status ); }
	$_SERVER['REQUEST_METHOD'] = $input['method'] ?? 'POST';
	$_POST = $input['post'] ?? array( 'course_id' => '10', '_cas_restart_nonce' => 'valid:' . $uid . ':cas_restart_course_10' );
	require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-lesson-restart.php';
	try { cas_lesson_handle_restart(); } catch ( \RuntimeException $e ) { $response = array( 'status' => $e->getCode(), 'message' => $e->getMessage() ); }
	echo json_encode( compact( 'initial', 'meta', 'comments', 'cache', 'headers', 'response' ) + array( 'redirect' => $GLOBALS['redirect'] ?? null ) );
}
