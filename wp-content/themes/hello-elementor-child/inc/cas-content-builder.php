<?php
/** Narrow cas_content adapter to Tutor's existing builder, never an administrator/instructor WP role. */
defined( 'ABSPATH' ) || exit;

function cas_builder_is_editor() {
	return cas_content_is_user( get_current_user_id() ) && current_user_can( CAS_CONTENT_CAP ) && current_user_can( 'edit_tutor_courses' )
		&& function_exists( 'cas_price_assert_publishable' );
}

function cas_builder_owns_course( $id ) {
	$post = get_post( (int) $id );
	return cas_builder_is_editor() && $post && 'courses' === $post->post_type
		&& (int) $post->post_author === get_current_user_id() && 'trash' !== $post->post_status
		&& current_user_can( 'edit_post', $post->ID );
}

function cas_builder_nonce_valid() {
	return function_exists( 'tutor' ) && isset( $_POST[ tutor()->nonce ] ) && is_string( $_POST[ tutor()->nonce ] )
		&& wp_verify_nonce( wp_unslash( $_POST[ tutor()->nonce ] ), tutor()->nonce_action );
}

/** Change only the native course_builder menu entry, only for the limited role. */
add_filter( 'tutor_admin_menu', function ( $menu ) {
	if ( cas_content_is_user( get_current_user_id() ) && isset( $menu['group_one']['course_builder'] ) ) {
		$menu['group_one']['course_builder']['capability'] = CAS_CONTENT_CAP;
		$menu['group_one']['course_builder']['callback']   = 'cas_builder_render';
	}
	return $menu;
} );
add_filter( 'cas_content_allowed_pages', function ( $pages ) {
	$pages[] = 'create-course'; // Exact native builder slug; its callback also checks own-author and course type.
	return $pages;
} );

function cas_builder_render() {
	$id = isset( $_GET['course_id'] ) && is_scalar( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
	if ( ! $id || ! cas_builder_owns_course( $id ) ) {
		wp_die( esc_html__( 'You may only open your own non-trashed course drafts.', 'hello-elementor-child' ), '', array( 'response' => 403 ) );
	}
	if ( ! class_exists( '\TUTOR\Course' ) || ! method_exists( '\TUTOR\Course', 'load_course_builder_view' ) ) {
		wp_die( esc_html__( 'The native course builder is unavailable. Please contact Herman.', 'hello-elementor-child' ), '', array( 'response' => 503 ) );
	}
	$course = new \TUTOR\Course( false ); // Same hook-free construction as Tutor's own menu callback.
	if ( ! is_callable( array( $course, 'load_course_builder_view' ) ) ) {
		wp_die( 'The native course builder is unavailable. Please contact Herman.', '', array( 'response' => 503 ) );
	}
	$course->load_course_builder_view();
	echo '<p class="cas-builder-price-help">';
	echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . CAS_PRICE_PAGE_SLUG ) ) . '">' . esc_html__( 'Course prices', 'hello-elementor-child' ) . '</a> — ';
	echo esc_html__( 'Use Set first price before publishing a new course. All later price changes also need confirmation here.', 'hello-elementor-child' );
	echo '</p>';
}

/** Authenticated, nonce-backed POST to Tutor's own draft-creation action; never a mutating GET link. */
add_action( 'admin_notices', function () {
	global $pagenow;
	if ( ! cas_builder_is_editor() || 'admin.php' !== $pagenow || 'cas-content-hub' !== ( $_GET['page'] ?? '' ) ) {
		return;
	}
	if ( ! function_exists( 'tutor' ) ) {
		echo '<p>' . esc_html__( 'Tutor is unavailable. Please contact Herman.', 'hello-elementor-child' ) . '</p>';
		return;
	}
	echo '<form id="cas-builder-add-course" method="post" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="tutor_create_new_draft_course">';
	wp_nonce_field( tutor()->nonce_action, tutor()->nonce );
	echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Add course', 'hello-elementor-child' ) . '</button> ';
	echo esc_html__( 'Create a draft in the normal Tutor builder, edit its content, then use Course prices → Set first price before publishing.', 'hello-elementor-child' ) . '</p>';
	echo '<p role="status" class="cas-builder-feedback"></p></form>';
	wp_enqueue_script( 'cas-content-builder', get_stylesheet_directory_uri() . '/js/cas-content-builder.js', array(), '1', true );
}, 30 );

/** Track fresh IDs within this creation request; an old ID passed to the Tutor action is never fresh. */
function cas_builder_fresh_course( $id, $record = false ) {
	static $fresh = array();
	if ( $record ) {
		$fresh[ (int) $id ] = true;
	}
	return isset( $fresh[ (int) $id ] );
}
add_action( 'wp_after_insert_post', function ( $id, $post, $update ) {
	if ( ! $update && cas_builder_owns_course( $id ) && in_array( $post->post_status, array( 'draft', 'auto-draft' ), true ) ) {
		cas_builder_fresh_course( $id, true );
	}
}, 10, 3 );

/** Native requests still use Tutor's own handlers; reject unauthorised role/author input before them. */
add_action( 'admin_init', function () {
	if ( ! cas_content_is_user( get_current_user_id() ) || ! wp_doing_ajax() ) {
		return;
	}
	$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? wp_unslash( $_REQUEST['action'] ) : '';
	$closed = array( 'tutor_create_course', 'tutor_save_withdraw_account', 'tutor_make_an_withdraw', 'tutor_order_refund',
		'tutor_option_save', 'tutor_option_default_save', 'tutor_import_settings', 'tutor_apply_settings',
		'tutor_add_instructor', 'instructor_approval_action', 'tutor_instructor_bulk_action', 'tutor_admin_withdraw_action', 'reset_settings_data', 'load_saved_data' );
	if ( in_array( $action, $closed, true ) ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	if ( ! in_array( $action, array( 'tutor_create_new_draft_course', 'tutor_update_course', 'tutor_course_details', 'tutor_course_contents' ), true ) ) {
		return;
	}
	if ( ! cas_builder_is_editor() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )
		|| $action !== ( $_POST['action'] ?? '' ) || ! cas_builder_nonce_valid() ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	foreach ( array( 'post_author', 'post_author_override' ) as $author_key ) {
		if ( isset( $_POST[ $author_key ] ) && ( ! is_scalar( $_POST[ $author_key ] ) || (int) $_POST[ $author_key ] !== get_current_user_id() ) ) {
			wp_send_json_error( array( 'message' => 'Course ownership cannot be changed here.' ), 403 );
		}
	}
	if ( 'tutor_create_new_draft_course' !== $action ) {
		$raw_id = $_POST['course_id'] ?? $_POST['ID'] ?? 0;
		$id = is_scalar( $raw_id ) ? absint( $raw_id ) : 0;
		if ( ! cas_builder_owns_course( $id ) ) {
			wp_send_json_error( array( 'message' => 'You may only edit your own courses.' ), 403 );
		}
		if ( 'tutor_update_course' === $action ) {
			cas_builder_assert_course_sort( $id ); // Before any vendor handler can save the course row.
			if ( 'yes' === ( $_POST['is_public_course'] ?? '' ) || 'yes' === ( $_POST['_tutor_is_public_course'] ?? '' ) ) {
				wp_send_json_error( array( 'message' => 'Paid courses cannot be made publicly accessible here.' ), 403 );
			}
		}
	}
}, -10 );

/** Resolve curriculum ownership from the current post-parent chain, never stale instructor links. */
function cas_builder_course_for_item( $id, $type ) {
	$post = get_post( (int) $id );
	if ( ! $post || $post->post_type !== $type || 'trash' === $post->post_status ) {
		return 0;
	}
	if ( 'lesson' === $type ) {
		return cas_builder_course_for_item( $post->post_parent, 'topics' );
	}
	return 'topics' === $type && 'courses' === get_post_type( $post->post_parent ) ? (int) $post->post_parent : 0;
}

/** Native JS sends IDs as digit strings; reject floats, booleans, coercion and overflow. */
function cas_builder_sort_id( $value ) {
	if ( ( ! is_int( $value ) && ! is_string( $value ) ) || ! preg_match( '/^[1-9][0-9]*$/', (string) $value ) ) {
		return false;
	}
	return filter_var( $value, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
}

/** Pin the implementation we inspected, not just private names that a future Tutor may reuse. */
function cas_builder_sort_compatible( $course, $needs_widening ) {
	if ( $needs_widening && ( ! defined( 'TUTOR_VERSION' ) || '4.1.3' !== TUTOR_VERSION || ! is_admin() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) { return false; }
	if ( ! function_exists( 'tutor' ) || ! function_exists( 'tutor_utils' ) ) { return false; }
	$plugin = tutor();
	if ( ! is_object( $plugin ) || empty( $plugin->path ) || ! is_string( $plugin->path ) ) { return false; }
	$root = trailingslashit( $plugin->path );
	$header = get_file_data( $root . 'tutor.php', array( 'Version' => 'Version' ) );
	if ( $needs_widening && '4.1.3' !== ( $header['Version'] ?? '' ) ) { return false; }
	try {
		// shortcut: exact Tutor 4.1.3 package pins; re-review before supporting a different vendor build.
		foreach ( array(
			\TUTOR\Course::class => array( 'classes/Course.php', 'ff2420312a655fda5c853d4e8fa5e270ec8fed835f369117b2adba7544855467' ),
			\TUTOR\Utils::class => array( 'classes/Utils.php', 'b5fbf74b3747b742f5f75abb3ce9043ca566670aead26fa5908087f4cacf4cf9' ),
		) as $class => $pin ) {
			$file = realpath( $root . $pin[0] );
			$reflection = new ReflectionClass( $class );
			$loaded_file = $reflection->getFileName();
			if ( ! $file || ! is_string( $loaded_file ) || realpath( $loaded_file ) !== $file || hash_file( 'sha256', $file ) !== $pin[1] ) { return false; }
		}
		foreach ( array( 'get_course_contents' => array( false, 1, 1 ), 'validate_course_content_order' => array( true, 2, 3 ),
			'save_course_content_order' => array( true, 0, 1 ), 'save_course_meta' => array( false, 2, 2 ),
			'ajax_update_course' => array( false, 0, 0 ), 'ajax_update_course_content_order' => array( false, 0, 0 ) ) as $name => $signature ) {
			$method = new ReflectionMethod( \TUTOR\Course::class, $name );
			if ( $method->getDeclaringClass()->getName() !== \TUTOR\Course::class || $method->isStatic()
				|| $method->isPrivate() !== $signature[0] || ( ! $signature[0] && ! $method->isPublic() )
				|| $method->getNumberOfRequiredParameters() !== $signature[1] || $method->getNumberOfParameters() !== $signature[2] ) { return false; }
		}
	} catch ( Throwable $error ) { return false; }
	$utils = tutor_utils();
	if ( ! is_object( $utils ) || get_class( $utils ) !== \TUTOR\Utils::class ) { return false; }
	$hooks = array( 'save_post_courses' => 'save_course_meta' );
	if ( wp_doing_ajax() ) {
		$action = $_REQUEST['action'] ?? '';
		$actions = array( 'tutor_update_course' => 'ajax_update_course', 'tutor_update_course_content_order' => 'ajax_update_course_content_order' );
		if ( ! is_string( $action ) || ! isset( $actions[ $action ] ) || $action !== ( $_POST['action'] ?? '' ) || ! cas_builder_nonce_valid() ) { return false; }
		$hooks[ 'wp_ajax_' . $action ] = $actions[ $action ];
	} elseif ( $needs_widening && ( 'editpost' !== ( $_POST['action'] ?? '' ) || cas_builder_sort_id( $_POST['post_ID'] ?? null ) !== $course
		|| ! isset( $_POST['_wpnonce'] ) || ! is_string( $_POST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['_wpnonce'] ), 'update-post_' . $course ) ) ) { return false; }
	global $wp_filter;
	foreach ( $hooks as $hook => $method ) {
		$found = false;
		foreach ( $wp_filter[ $hook ]->callbacks[10] ?? array() as $registered ) {
			$callback = $registered['function'];
			if ( is_array( $callback ) && is_object( $callback[0] ) && get_class( $callback[0] ) === \TUTOR\Course::class && $callback[1] === $method ) { $found = true; break; }
		}
		if ( ! $found ) { return false; }
	}
	return true;
}

/** Only a fully checked matrix may widen Tutor's IDs-only existence checks for this request. */
function cas_builder_sort_read_scope( $set = null ) {
	static $scope = array();
	if ( null !== $set ) { $scope = $set; }
	return $scope;
}

/** Tutor lists drafts in the builder but its private sorter hardcodes publish in get_posts(). */
add_action( 'pre_get_posts', function ( $query ) {
	$scope = cas_builder_sort_read_scope();
	if ( ! $scope || empty( $scope['draft_compatible'] ) || ! is_admin() || $scope['user'] !== get_current_user_id() || ! cas_builder_owns_course( $scope['course'] )
		|| 'ids' !== $query->get( 'fields' ) || 'publish' !== $query->get( 'post_status' ) ) { return; }
	$types = (array) $query->get( 'post_type' );
	sort( $types, SORT_STRING );
	$parents = array_map( 'intval', (array) $query->get( 'post_parent__in' ) );
	sort( $parents, SORT_NUMERIC );
	if ( array( 'topics' ) === $types && (int) $query->get( 'post_parent' ) === $scope['course'] && ! $parents ) {
		$ids = $scope['topics'];
		$statuses = $scope['topic_statuses'];
	} elseif ( $types === $scope['types'] && $parents === $scope['topics'] ) {
		$ids = $scope['contents'];
		$statuses = $scope['content_statuses'];
	} else { return; }
	$vendor_check = false;
	// The first global get_posts() must be called directly by the pinned validator, not a nested query.
	$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 16 );
	foreach ( $trace as $index => $frame ) {
		if ( ! isset( $frame['class'] ) && ( $frame['function'] ?? '' ) === 'get_posts' ) {
			$caller = $trace[ $index + 1 ] ?? array();
			$vendor_check = ( $caller['class'] ?? '' ) === \TUTOR\Course::class && ( $caller['function'] ?? '' ) === 'validate_course_content_order';
			break;
		}
	}
	if ( ! $vendor_check ) { return; }
	// Pin to the already native-listed, graph-checked IDs; never broaden a body/front-end query.
	$query->set( 'post__in', $ids ? $ids : array( 0 ) );
	$query->set( 'post_status', $statuses ? $statuses : array( 'publish' ) );
	$query->set( 'cache_results', false );
}, 99 );

/** Validate the complete native matrix before Tutor detaches/re-attaches any children. No writes here. */
function cas_builder_validate_content_order( $raw, $target_course = null ) {
	cas_builder_sort_read_scope( array() );
	$invalid = new WP_Error( 'cas_sort_shape', 'Invalid curriculum order. Reload the course and try the native sorting controls again.', array( 'status' => 400 ) );
	if ( ! is_string( $raw ) ) { return $invalid; }
	try {
		$order = json_decode( $raw, true, 512, JSON_THROW_ON_ERROR );
	} catch ( JsonException $error ) {
		return $invalid;
	}
	// JS uses {0: row, 1: row} and {0: id}, including {} for an empty lesson list.
	if ( ! is_array( $order ) || ! array_is_list( $order ) ) { return $invalid; }
	if ( array() === $order && null === $target_course ) { return $invalid; }
	// A new, not-yet-inserted course has nothing for Tutor's empty sorter to detach.
	if ( array() === $order && 0 === (int) $target_course ) { return 0; }
	$topics = array();
	$contents = array();
	foreach ( $order as $row ) {
		if ( ! is_array( $row ) || 2 !== count( $row ) || ! array_key_exists( 'topic_id', $row ) || ! array_key_exists( 'lesson_ids', $row )
			|| ! is_array( $row['lesson_ids'] ) || ! array_is_list( $row['lesson_ids'] ) ) { return $invalid; }
		$topic = cas_builder_sort_id( $row['topic_id'] );
		if ( false === $topic || isset( $topics[ $topic ] ) ) { return $invalid; }
		$topics[ $topic ] = true;
		foreach ( $row['lesson_ids'] as $raw_id ) {
			$id = cas_builder_sort_id( $raw_id );
			if ( false === $id || isset( $contents[ $id ] ) ) { return $invalid; }
			$contents[ $id ] = true;
		}
	}
	$course = array() === $order ? (int) $target_course : cas_builder_course_for_item( array_key_first( $topics ), 'topics' );
	if ( ! cas_builder_owns_course( $course ) ) {
		return new WP_Error( 'cas_sort_forbidden', 'You may only sort content belonging to your own course.', array( 'status' => 403 ) );
	}
	$unavailable = new WP_Error( 'cas_sort_read', 'The current curriculum could not be checked. Nothing was sorted; reload the course.', array( 'status' => 503 ) );
	$compat_error = new WP_Error( 'cas_sort_compat', 'Sorting is unavailable for this Tutor version or request structure. Contact Herman before changing this course.', array( 'status' => 503 ) );
	$topic_ids = array_keys( $topics );
	sort( $topic_ids, SORT_NUMERIC );
	global $wpdb;
	$stored_topics = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_status FROM {$wpdb->posts} WHERE post_type='topics' AND post_parent=%d", $course ), OBJECT_K );
	if ( null === $stored_topics || '' !== $wpdb->last_error ) { return $unavailable; }
	$topic_statuses = array();
	foreach ( $topic_ids as $id ) {
		if ( ! isset( $stored_topics[ $id ] ) || 'trash' === $stored_topics[ $id ]->post_status ) {
			return new WP_Error( 'cas_sort_topics', 'The curriculum changed or some topics are missing. Reload the course before sorting.', array( 'status' => 409 ) );
		}
		$topic_statuses[] = $stored_topics[ $id ]->post_status;
	}
	$children = array();
	if ( $topic_ids ) {
		$slots = implode( ',', array_fill( 0, count( $topic_ids ), '%d' ) );
		$children = $wpdb->get_results( $wpdb->prepare( "SELECT ID,post_type,post_status FROM {$wpdb->posts} WHERE post_parent IN ($slots)", $topic_ids ) );
		if ( null === $children || '' !== $wpdb->last_error ) { return $unavailable; }
	}
	$types = apply_filters( 'tutor_course_contents_post_types', array( tutor()->lesson_post_type, tutor()->quiz_post_type ) );
	if ( ! is_array( $types ) ) { return $unavailable; }
	$types = array_unique( $types );
	sort( $types, SORT_STRING );
	$content_statuses = array();
	foreach ( $children as $child ) {
		if ( 'trash' === $child->post_status || ! in_array( $child->post_type, $types, true ) ) {
			return new WP_Error( 'cas_sort_hidden', 'This curriculum contains unlisted, trashed or unsupported items. Check those items before sorting.', array( 'status' => 409 ) );
		}
		$content_statuses[] = $child->post_status;
	}
	$needs_widening = (bool) array_diff( array_merge( $topic_statuses, $content_statuses ), array( 'publish' ) );
	// Check compatibility BEFORE calling vendor code or allowing any ordinary course-row save.
	if ( ! cas_builder_sort_compatible( $course, $needs_widening ) ) { return $compat_error; }
	try {
		// Exactly the API used by ajax_course_contents(), under this checked editor's current context.
		$native = ( new \TUTOR\Course( false ) )->get_course_contents( $course );
	} catch ( Throwable $error ) { return $unavailable; }
	if ( ! is_array( $native ) ) { return $unavailable; }
	$current_topics = array();
	$native_contents = array();
	foreach ( $native as $row ) {
		if ( ! is_array( $row ) || ! isset( $row['id'], $row['contents'] ) || ! is_array( $row['contents'] ) ) { return $unavailable; }
		$id = cas_builder_sort_id( $row['id'] );
		if ( false === $id || isset( $current_topics[ $id ] ) ) { return $unavailable; }
		$current_topics[ $id ] = true;
		foreach ( $row['contents'] as $item ) {
			$id = cas_builder_sort_id( is_object( $item ) ? ( $item->ID ?? null ) : null );
			if ( false === $id || isset( $native_contents[ $id ] ) ) { return $unavailable; }
			$native_contents[ $id ] = true;
		}
	}
	$current_topics = array_keys( $current_topics );
	$topic_ids = array_keys( $topics );
	sort( $current_topics, SORT_NUMERIC );
	sort( $topic_ids, SORT_NUMERIC );
	if ( $topic_ids !== $current_topics ) {
		return new WP_Error( 'cas_sort_topics', 'The curriculum changed or some topics are missing. Reload the course before sorting.', array( 'status' => 409 ) );
	}
	if ( array() === $topic_ids ) { return $course; }
	$current_contents = array();
	foreach ( $children as $child ) {
		// Native membership and the raw graph must agree; never silently drop unlisted children.
		if ( ! isset( $native_contents[ (int) $child->ID ] ) ) {
			return new WP_Error( 'cas_sort_hidden', 'This curriculum contains unlisted, trashed or unsupported items. Check those items before sorting.', array( 'status' => 409 ) );
		}
		$current_contents[] = (int) $child->ID;
	}
	$content_ids = array_keys( $contents );
	$native_contents = array_keys( $native_contents );
	sort( $current_contents, SORT_NUMERIC );
	sort( $native_contents, SORT_NUMERIC );
	sort( $content_ids, SORT_NUMERIC );
	if ( $native_contents !== $current_contents || $content_ids !== $current_contents ) {
		return new WP_Error( 'cas_sort_omission', 'Sorting cannot add or detach course items. Include every existing item, move it to another topic, or use the authorised removal controls.', array( 'status' => 409 ) );
	}
	if ( $needs_widening ) {
		cas_builder_sort_read_scope( array( 'draft_compatible' => true, 'user' => get_current_user_id(), 'course' => $course, 'topics' => $topic_ids, 'contents' => $current_contents,
			'types' => $types, 'topic_statuses' => array_values( array_unique( $topic_statuses ) ), 'content_statuses' => array_values( array_unique( $content_statuses ) ) ) );
	}
	try {
		// This pinned method is read-only: prove its actual queries accept the matrix BEFORE a course save.
		$parent = 'tutor_update_course_content_order' === ( $_REQUEST['action'] ?? '' ) ? \TUTOR\Input::post( 'content_parent', array(), \TUTOR\Input::TYPE_ARRAY ) : array();
		$validator = new ReflectionMethod( \TUTOR\Course::class, 'validate_course_content_order' );
		$validator->invoke( new \TUTOR\Course( false ), $course, $order, $parent );
	} catch ( Throwable $error ) {
		cas_builder_sort_read_scope( array() );
		return $compat_error;
	}
	return $course;
}

/** The same vendor saver also runs on course saves, not only the dedicated AJAX reorder action. */
function cas_builder_assert_course_sort( $course_id ) {
	if ( ! cas_content_is_user( get_current_user_id() ) || ! array_key_exists( 'tutor_topics_lessons_sorting', $_POST ) ) { return; }
	$check = cas_builder_validate_content_order( is_string( $_POST['tutor_topics_lessons_sorting'] ) ? wp_unslash( $_POST['tutor_topics_lessons_sorting'] ) : $_POST['tutor_topics_lessons_sorting'], (int) $course_id );
	if ( ! is_wp_error( $check ) && $check !== (int) $course_id ) {
		$check = new WP_Error( 'cas_sort_target', 'The sorting request does not belong to this course.', array( 'status' => 403 ) );
	}
	if ( is_wp_error( $check ) ) {
		cas_builder_sort_read_scope( array() );
		$status = (int) $check->get_error_data()['status'];
		if ( wp_doing_ajax() ) { wp_send_json_error( array( 'message' => $check->get_error_message() ), $status ); }
		wp_die( esc_html( $check->get_error_message() ), '', array( 'response' => $status ) );
	}
}
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( 'courses' === $data['post_type'] ) { cas_builder_assert_course_sort( (int) ( $postarr['ID'] ?? 0 ) ); }
	return $data;
}, -10, 2 );
add_action( 'save_post_courses', 'cas_builder_assert_course_sort', -10 );

/** Keep the native topic/lesson handlers, adding current author checks before their link-based checks. */
add_action( 'admin_init', function () {
	if ( ! wp_doing_ajax() || ! cas_content_is_user( get_current_user_id() ) ) {
		return;
	}
	$action = $_REQUEST['action'] ?? '';
	if ( ! in_array( $action, array( 'tutor_save_topic', 'tutor_delete_topic', 'tutor_save_lesson', 'tutor_delete_lesson', 'tutor_lesson_details', 'tutor_update_course_content_order' ), true ) ) {
		return;
	}
	if ( ! cas_builder_is_editor() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )
		|| $action !== ( $_POST['action'] ?? '' ) || ! cas_builder_nonce_valid() ) {
		wp_send_json_error( array( 'message' => 'Not allowed.' ), 403 );
	}
	$topic = isset( $_POST['topic_id'] ) && is_scalar( $_POST['topic_id'] ) ? absint( $_POST['topic_id'] ) : 0;
	$lesson = isset( $_POST['lesson_id'] ) && is_scalar( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
	$course = 0;
	if ( 'tutor_save_topic' === $action ) {
		$course = isset( $_POST['course_id'] ) && is_scalar( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		if ( $topic && cas_builder_course_for_item( $topic, 'topics' ) !== $course ) {
			$course = 0;
		}
	} elseif ( 'tutor_delete_topic' === $action ) {
		$course = cas_builder_course_for_item( $topic, 'topics' );
	} elseif ( 'tutor_update_course_content_order' === $action ) {
		$course = cas_builder_validate_content_order( isset( $_POST['tutor_topics_lessons_sorting'] ) && is_string( $_POST['tutor_topics_lessons_sorting'] )
			? wp_unslash( $_POST['tutor_topics_lessons_sorting'] ) : null );
		if ( is_wp_error( $course ) ) {
			wp_send_json_error( array( 'message' => $course->get_error_message() ), (int) $course->get_error_data()['status'] );
		}
	} else {
		$course = 'tutor_delete_lesson' === $action ? cas_builder_course_for_item( $lesson, 'lesson' ) : cas_builder_course_for_item( $topic, 'topics' );
		if ( $lesson && cas_builder_course_for_item( $lesson, 'lesson' ) !== $course ) {
			$course = 0;
		}
	}
	if ( ! cas_builder_owns_course( $course ) ) {
		wp_send_json_error( array( 'message' => 'You may only manage content belonging to your own courses.' ), 403 );
	}
}, -10 );

/** Publish only for an authenticated, nonce-backed own-course native save with the actual publish cap. */
function cas_builder_publish_context() {
	return wp_doing_ajax() && cas_builder_is_editor() && 'tutor_update_course' === ( $_POST['action'] ?? '' )
		&& 'tutor_update_course' === ( $_REQUEST['action'] ?? '' )
		&& 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && cas_builder_nonce_valid()
		&& cas_builder_owns_course( absint( $_POST['course_id'] ?? 0 ) ) && current_user_can( 'publish_tutor_courses' );
}

/** Tutor skips its option-key filter when the key is absent; introduce a request-only false default. */
add_filter( 'option_tutor_option', function ( $options ) {
	if ( cas_builder_publish_context() ) {
		$options = (array) $options;
		if ( ! array_key_exists( 'instructor_can_publish_course', $options ) ) {
			$options['instructor_can_publish_course'] = false;
		}
	}
	return $options;
} );
add_filter( 'default_option_tutor_option', function ( $default ) {
	return cas_builder_publish_context() ? array( 'instructor_can_publish_course' => false ) : $default;
} );
add_filter( 'instructor_can_publish_course', function ( $value ) {
	return cas_builder_publish_context() ? true : $value;
} );

/** A vendor read-modify-write may read the overlay: never persist the injected key. */
add_filter( 'pre_update_option_tutor_option', function ( $value, $old_value ) {
	if ( ! cas_builder_publish_context() || ! is_array( $value ) ) {
		return $value;
	}
	global $wpdb;
	$stored = maybe_unserialize( $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s", 'tutor_option' ) ) );
	if ( ! is_array( $stored ) || ! array_key_exists( 'instructor_can_publish_course', $stored ) ) {
		unset( $value['instructor_can_publish_course'] );
	}
	if ( $value === (array) $stored ) {
		return $old_value; // Do not turn an overlay-only/no-op write into a stored option change.
	}
	return $value;
}, 10, 2 );
add_filter( 'tutor_course_builder_settings', function ( $settings, $id ) {
	if ( cas_builder_owns_course( $id ) && current_user_can( 'publish_tutor_courses' ) ) {
		$settings['instructor_can_publish_course'] = true;
	}
	return $settings;
}, 10, 2 );

/** Approved internal instructor metadata never opens public application or payout paths to this role. */
add_action( 'template_redirect', function () {
	if ( cas_content_is_user( get_current_user_id() ) && in_array( $_REQUEST['tutor_action'] ?? '', array( 'tutor_apply_instructor', 'tutor_register_instructor' ), true ) ) {
		wp_die( 'Not allowed.', '', array( 'response' => 403 ) );
	}
}, -10 );
