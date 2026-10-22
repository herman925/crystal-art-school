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
		$order = isset( $_POST['tutor_topics_lessons_sorting'] ) && is_string( $_POST['tutor_topics_lessons_sorting'] )
			? json_decode( wp_unslash( $_POST['tutor_topics_lessons_sorting'] ), true ) : null;
		$first = is_array( $order ) && isset( $order[0]['topic_id'] ) && is_scalar( $order[0]['topic_id'] ) ? absint( $order[0]['topic_id'] ) : 0;
		$course = cas_builder_course_for_item( $first, 'topics' );
		// Tutor 4.1.3 validate_course_content_order() then checks every topic/content against this course.
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
