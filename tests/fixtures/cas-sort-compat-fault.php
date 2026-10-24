<?php
/** LOCAL Review-only fault injector. Never deploy; copy/remove only in the coordinated synthetic pass. */
add_action( 'admin_init', function () {
	$fault = $_POST['w1_sort_compat_fault'] ?? '';
	if ( ! in_array( $fault, array( 'missing_save_hook', 'published_only_ids', 'unrelated_query' ), true )
		|| ! wp_doing_ajax() || ! in_array( $_POST['action'] ?? '', array( 'tutor_update_course', 'tutor_update_course_content_order' ), true )
		|| ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) { return; }
	$ledger = get_option( 'cas_w1_sort_fixture' );
	if ( ! is_array( $ledger ) || ( $ledger['home'] ?? '' ) !== home_url()
		|| (int) ( $ledger['users']['w1sortowner'] ?? 0 ) !== get_current_user_id()
		|| ! function_exists( 'cas_builder_nonce_valid' ) || ! cas_builder_nonce_valid() ) { return; }
	if ( 'missing_save_hook' === $fault ) {
		global $wp_filter;
		foreach ( $wp_filter['save_post_courses']->callbacks[10] ?? array() as $registered ) {
			$cb = $registered['function'];
			if ( is_array( $cb ) && is_object( $cb[0] ) && get_class( $cb[0] ) === \TUTOR\Course::class && 'save_course_meta' === $cb[1] ) {
				remove_action( 'save_post_courses', $cb, 10 );
			}
		}
		return;
	}
	if ( 'unrelated_query' === $fault ) {
		add_action( 'pre_get_posts', function ( $query ) {
			$scope = cas_builder_sort_read_scope();
			if ( ! $scope || 'ids' !== $query->get( 'fields' ) || ! $query->get( 'post_parent__in' ) || $query->get( 'w1_sort_unrelated_probe' ) ) { return; }
			$ids = get_posts( array( 'post_type' => $scope['types'], 'post_parent__in' => $scope['topics'], 'post_status' => 'publish',
				'fields' => 'ids', 'posts_per_page' => -1, 'w1_sort_unrelated_probe' => true ) );
			if ( ! headers_sent() ) { header( 'X-CAS-Sort-Probe: ' . implode( ',', $ids ), true ); }
		}, 50 );
		return;
	}
	add_action( 'pre_get_posts', function ( $query ) {
		if ( 'ids' !== $query->get( 'fields' ) ) { return; }
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 16 ) as $frame ) {
			if ( ( $frame['class'] ?? '' ) === \TUTOR\Course::class && ( $frame['function'] ?? '' ) === 'validate_course_content_order' ) {
				// Simulate an unsupported query filter AFTER the production bridge, without writing statuses.
				$query->set( 'post_status', 'publish' );
				return;
			}
		}
	}, 1000 );
}, -20 );
