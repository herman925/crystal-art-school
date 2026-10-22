<?php
/** Test-only isolated guard harness. Does NOT bootstrap WordPress/Tutor or touch a database/files. */
namespace TUTOR {
	class Course {
		public function __construct( $hooks = true ) {}
		public function get_course_contents( $course ) { ++$GLOBALS['native_getter_calls']; return array(); }
	}
	class Utils {}
}
namespace {
	$mode = $argv[2];
	define( 'ABSPATH', __DIR__ . '/' );
	define( 'CAS_CONTENT_CAP', 'manage_cas_content' );
	define( 'OBJECT_K', 'OBJECT_K' );
	define( 'TUTOR_VERSION', 'unsupported_version' === $mode ? '4.1.4' : '4.1.3' );
	$GLOBALS['native_getter_calls'] = 0;
	$GLOBALS['context_reads'] = 0;
	class WP_Error {
		public function __construct( public $code, public $message, public $data ) {}
		public function get_error_data() { return $this->data; }
		public function get_error_message() { return $this->message; }
	}
	class SortFakeDB {
		public $posts = 'unit_posts';
		public $last_error = '';
		public function prepare( $sql, ...$args ) { return $sql; }
		public function get_results( $sql, $format = null ) {
			if ( str_contains( $sql, "post_type='topics'" ) ) { return array( 3 => (object) array( 'ID' => 3, 'post_status' => 'publish' ) ); }
			return array( (object) array( 'ID' => 4, 'post_type' => 'lesson', 'post_status' => 'draft' ) );
		}
	}
	$GLOBALS['wpdb'] = new SortFakeDB();
	function add_action( ...$args ) {}
	function add_filter( ...$args ) {}
	function apply_filters( $hook, $value ) { return $value; }
	function cas_content_is_user( $id ) { return 7 === $id; }
	function get_current_user_id() { return 7; }
	function current_user_can( ...$args ) { return true; }
	function cas_price_assert_publishable( ...$args ) { return true; }
	function get_post( $id ) {
		return (object) array( 'ID' => $id, 'post_type' => 2 === $id ? 'courses' : 'topics', 'post_author' => 7, 'post_parent' => 2, 'post_status' => 'publish' );
	}
	function get_post_type( $id ) { return 2 === $id ? 'courses' : 'topics'; }
	function tutor() { return (object) array( 'path' => '/unsupported-unit-tutor/', 'lesson_post_type' => 'lesson', 'quiz_post_type' => 'tutor_quiz' ); }
	function tutor_utils() { return new \TUTOR\Utils(); }
	function trailingslashit( $path ) { return rtrim( $path, '/' ) . '/'; }
	function get_file_data( ...$args ) { return array( 'Version' => '4.1.3' ); }
	function is_admin() { ++$GLOBALS['context_reads']; return 'unsupported_context' !== $GLOBALS['mode']; }
	function wp_doing_ajax() { return false; }
	function wp_unslash( $value ) { return $value; }
	function is_wp_error( $value ) { return $value instanceof WP_Error; }
	function esc_html( $value ) { return $value; }
	function wp_die( $message, $title, $args ) { throw new \RuntimeException( $message, $args['response'] ); }
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = array( 'action' => 'editpost', 'post_ID' => 2, 'tutor_topics_lessons_sorting' => '[{"topic_id":3,"lesson_ids":[4]}]' );
	require $argv[1];
	$row = array( 'post_title' => 'Original', 'post_content' => 'Original body', 'post_status' => 'publish', 'post_author' => 7, 'post_parent' => 0, 'menu_order' => 0 );
	$before = $row;
	$status = null;
	try {
		cas_builder_assert_course_sort( 2 );
		$row['post_title'] = 'MUST NOT SAVE';
		$row['post_content'] = 'MUST NOT SAVE';
		$row['post_status'] = 'draft';
	} catch ( \RuntimeException $error ) { $status = $error->getCode(); }
	echo json_encode( array( 'status' => $status, 'before' => $before, 'after' => $row, 'native_getter_calls' => $GLOBALS['native_getter_calls'], 'context_reads' => $GLOBALS['context_reads'], 'scope' => cas_builder_sort_read_scope() ) );
}
