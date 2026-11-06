<?php
/** Native Tutor builder sale control; Tutor's active sale metadata still drives checkout. */
defined( 'ABSPATH' ) || exit;

function cas_builder_sale_may_edit( $id ) {
	return ( current_user_can( 'manage_options' ) || current_user_can( 'manage_cas_content' ) )
		&& 'courses' === get_post_type( $id ) && current_user_can( 'edit_post', $id );
}

function cas_builder_sale_version( $id, $price = null ) {
	return wp_create_nonce( 'cas-builder-sale-' . (int) $id . '-' . hash( 'sha256', wp_json_encode( $price ?: cas_price_current( $id ) ) ) );
}

function cas_builder_sale_value( $id ) {
	$price = cas_price_current( $id );
	$amount = cas_price_sale_amount( $price );
	return wp_json_encode( array( 'on' => '' !== $price['sale'] ? '1' : '0', 'amount' => '' === $amount ? '0.00' : $amount, 'version' => cas_builder_sale_version( $id, $price ) ) );
}

final class CAS_Builder_Sale {
	private static $save = null;

	static function active( $id ) { return self::$save && (int) $id === self::$save['id']; }
	static function proposed( $id ) { return self::active( $id ) ? self::$save['new'] : null; }
	static function error( $message, $status = 409 ) {
		wp_send_json( array( 'status_code' => $status, 'message' => $message ), $status );
	}

	static function start() {
		$id = isset( $_POST['course_id'] ) && is_scalar( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		if ( ! cas_builder_sale_may_edit( $id ) || ! cas_builder_nonce_valid()
			|| 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )
			|| ( ! current_user_can( 'manage_options' ) && (int) get_post_field( 'post_author', $id ) !== get_current_user_id() ) ) {
			if ( isset( $_POST['_cas_builder_sale'] ) ) { self::error( 'You cannot change this course price.', 403 ); }
			return;
		}
		if ( ! is_array( $_POST['pricing'] ?? null ) ) { return; }
		if ( 'paid' !== ( $_POST['pricing']['type'] ?? '' ) ) { return; }
		if ( ! in_array( $_POST['course_selling_option'] ?? 'one_time', array( '', 'one_time' ), true ) ) { return; }
		cas_price_native_write_lock( $id );
		clean_post_cache( $id );
		if ( ! cas_builder_sale_may_edit( $id ) || ( ! current_user_can( 'manage_options' ) && (int) get_post_field( 'post_author', $id ) !== get_current_user_id() ) ) {
			self::error( 'This course changed ownership. Reload the course list.', 403 );
		}
		$old = cas_price_current( $id );
		$regular = cas_price_clean( wp_unslash( $_POST['course_price'] ?? '' ) );
		$sale = cas_price_clean( wp_unslash( $_POST['course_sale_price'] ?? '' ) );
		if ( ! isset( $_POST['_cas_builder_sale'] ) ) {
			if ( '0.00' === $sale && '' === $old['zero_sale_regular'] ) { $sale = ''; }
			if ( '' === $old['zero_sale_regular'] && $old['type'] === 'paid' && $regular === $old['regular'] && $sale === $old['sale'] ) { return; }
			self::error( 'Reload the course builder to load the On sale control before changing prices.' );
		}
		$raw = $_POST['_cas_builder_sale'];
		$control = is_string( $raw ) ? json_decode( wp_unslash( $raw ), true ) : null;
		if ( ! is_array( $control ) || ! is_string( $control['version'] ?? null )
			|| ! wp_verify_nonce( $control['version'], 'cas-builder-sale-' . $id . '-' . hash( 'sha256', wp_json_encode( $old ) ) ) ) {
			self::error( 'The saved price changed. Reload this course before saving again.' );
		}
		$amount = cas_price_clean( $control['amount'] ?? null );
		if ( ! array_key_exists( 'amount', $control ) ) { $amount = $sale; } // Existing open editors retain their prior positive-sale behavior.
		if ( null === $amount || ( $amount !== $sale && ! ( '' === $amount && '0.00' === $sale ) ) ) { self::error( 'The Sale Price is still updating. Try saving again.', 400 ); }
		$new = cas_price_proposed( $regular, $amount, $control['on'] ?? null );
		if ( ! $new ) { self::error( 'Use a positive Regular Price and a lower Sale Price. Zero is free while On sale is on; blank is not a sale amount.', 400 ); }
		self::$save = array( 'id' => $id, 'old' => $old, 'new' => $new, 'own_tx' => false, 'applied' => false, 'finished' => false );
		// Keep Tutor's own data preparation consistent with the effective checkout price.
		$stored = cas_price_storage_for( $new );
		$_POST['course_sale_price'] = $stored['sale'];
	}

	static function transaction() {
		if ( ! self::$save ) { return; }
		global $wpdb;
		$active = $wpdb->get_var( 'SELECT @@in_transaction' );
		if ( null === $active ) { self::error( 'The course save could not establish a safe transaction.', 503 ); }
		if ( ! (int) $active ) {
			$tables = $wpdb->get_col( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN (%s,%s)', $wpdb->posts, $wpdb->postmeta ) );
			if ( count( $tables ) !== 2 || array_filter( $tables, static function ( $engine ) { return 'INNODB' !== strtoupper( $engine ); } )
				|| false === $wpdb->query( 'START TRANSACTION' ) ) { self::error( 'The course price could not be saved safely.', 503 ); }
			self::$save['own_tx'] = true;
		}
		self::$save['connection'] = (string) $wpdb->get_var( 'SELECT CONNECTION_ID()' );
	}

	static function apply( $id ) {
		if ( ! self::active( $id ) ) { return; }
		global $wpdb;
		$s = self::$save;
		$line = 'course=' . $id . ' old=' . wp_json_encode( $s['old'] ) . ' new=' . wp_json_encode( $s['new'] );
		$changed = $s['old'] !== $s['new'];
		try {
			if ( ! isset( $s['connection'] ) || $s['connection'] !== (string) $wpdb->get_var( 'SELECT CONNECTION_ID()' )
				|| cas_price_current( $id ) !== $s['old'] ) { throw new RuntimeException( 'The course price changed during this save.' ); }
			if ( $changed && ( ! function_exists( 'cas_audit_log' ) || ! cas_audit_log( 'course_builder_price_attempt', $line ) ) ) { throw new RuntimeException( 'The price change could not be recorded.' ); }
			cas_price_gate_open( true );
			try { update_post_meta( $id, '_tutor_course_price_type', 'paid' ); }
			finally { cas_price_gate_open( false ); }
			if ( ! cas_price_write_pair( $id, $s['new'] ) ) { throw new RuntimeException( 'The course price did not save.' ); }
			cas_price_gate_open( true );
			try { delete_post_meta( $id, CAS_PRICE_PENDING_META ); }
			finally { cas_price_gate_open( false ); }
			if ( get_post_meta( $id, CAS_PRICE_PENDING_META, true ) || ( $changed && ! cas_audit_log( 'course_builder_price_prepared', $line ) ) ) { throw new RuntimeException( 'The price save could not be verified.' ); }
			self::$save['applied'] = true;
		} catch ( Throwable $error ) {
			self::rollback();
			self::error( $error->getMessage() . ' Reload the course before retrying.', 503 );
		}
	}

	static function finish( $id ) {
		if ( ! self::active( $id ) || ! self::$save['applied'] ) { return; }
		global $wpdb;
		if ( self::$save['connection'] !== (string) $wpdb->get_var( 'SELECT CONNECTION_ID()' )
			|| cas_price_current( $id ) !== self::$save['new'] ) { self::rollback(); self::error( 'The saved price needs a fresh check. Reload the course.', 503 ); }
		if ( self::$save['own_tx'] && false === $wpdb->query( 'COMMIT' ) ) { self::rollback(); self::error( 'The course price save could not be confirmed.', 503 ); }
		self::$save['finished'] = true;
		if ( self::$save['old'] !== self::$save['new'] && function_exists( 'cas_audit_log' ) ) { cas_audit_log( 'course_builder_price', 'course=' . $id . ' verified=yes' ); }
	}

	static function rollback() {
		if ( ! self::$save || self::$save['finished'] ) { return; }
		global $wpdb;
		if ( self::$save['own_tx'] ) { $wpdb->query( 'ROLLBACK' ); }
		elseif ( class_exists( 'CAS_Bunny_Uploader' ) ) { CAS_Bunny_Uploader::rollback(); }
		clean_post_cache( self::$save['id'] );
	}
}

function cas_builder_sale_active( $id ) { return CAS_Builder_Sale::active( $id ); }
function cas_builder_sale_proposed( $id ) { return CAS_Builder_Sale::proposed( $id ); }

add_action( 'wp_ajax_tutor_update_course', array( 'CAS_Builder_Sale', 'start' ), -6 );
add_action( 'wp_ajax_tutor_update_course', array( 'CAS_Builder_Sale', 'transaction' ), 6 );
add_action( 'tutor_after_prepare_update_post_meta', array( 'CAS_Builder_Sale', 'apply' ), PHP_INT_MAX - 1 );
add_action( 'tutor_after_prepare_update_post_meta', array( 'CAS_Builder_Sale', 'finish' ), PHP_INT_MAX );
register_shutdown_function( array( 'CAS_Builder_Sale', 'rollback' ) );

add_filter( 'tutor_course_details_response', static function ( $data ) {
	$id = absint( $data['ID'] ?? 0 );
	if ( $id && cas_builder_sale_may_edit( $id ) ) {
		$data['_cas_builder_sale'] = cas_builder_sale_value( $id );
		$price = cas_price_current( $id );
		$data['course_pricing']['price'] = $price['regular'];
		$amount = cas_price_sale_amount( $price );
		$data['course_pricing']['sale_price'] = '' === $amount ? '0.00' : $amount;
	}
	return $data;
}, 1002 );

add_filter( 'tutor_localize_data', static function ( $data ) {
	$id = isset( $_GET['course_id'] ) && is_scalar( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
	if ( $id && cas_builder_owns_course( $id ) ) { $data['visibility_control']['course_builder']['basics_pricing_options_instructor'] = 'on'; }
	return $data;
}, 1002 );

add_action( 'tutor_course_builder_footer', static function () {
	$id = isset( $_GET['course_id'] ) && is_scalar( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
	if ( ! $id || ! cas_builder_sale_may_edit( $id ) ) { return; }
	wp_enqueue_script( 'cas-builder-sale', get_stylesheet_directory_uri() . '/js/cas-builder-sale.js', array( 'wp-element', 'tutor-course-builder' ), filemtime( get_stylesheet_directory() . '/js/cas-builder-sale.js' ), true );
	wp_enqueue_style( 'cas-builder-sale', get_stylesheet_directory_uri() . '/css/cas-builder-sale.css', array(), filemtime( get_stylesheet_directory() . '/css/cas-builder-sale.css' ) );
	wp_localize_script( 'cas-builder-sale', 'casBuilderSale', array( 'value' => cas_builder_sale_value( $id ) ) );
} );
