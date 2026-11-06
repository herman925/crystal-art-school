<?php
/** Exercise native-builder preflight and transactional price saves without WordPress/network. */
require __DIR__ . '/test_sale_toggle.php';
class BuilderJSON extends RuntimeException {}
function absint( $n ) { return abs( (int) $n ); }
function wp_unslash( $n ) { return $n; }
function wp_json_encode( $n ) { return json_encode( $n ); }
function wp_generate_uuid4() { return 'synthetic-lock-owner'; }
function wp_create_nonce( $action ) { return substr( hash( 'sha256', $action ), 0, 12 ); }
function wp_verify_nonce( $nonce, $action ) { return $nonce === wp_create_nonce( $action ); }
function cas_builder_nonce_valid() { return $GLOBALS['request_nonce']; }
function get_post_field( $field, $id ) { return $id === 1 ? 1 : 2; }
function current_user_can( $cap, $id = null ) { return $cap === 'manage_options' ? $GLOBALS['as_admin'] : ( $id === null || $id === 1 ); }
function wp_send_json( $data, $status ) { throw new BuilderJSON( $data['message'], $status ); }
function clean_post_cache( $id ) {}
function cas_audit_log( $event, $line ) { return ! $GLOBALS['audit_fails']; }
function delete_post_meta( $id, $key ) {
	if ( true === cas_price_block_role_write( null, $id, $key ) ) { return false; }
	unset( $GLOBALS['meta'][ $key ] );
	return true;
}
class BuilderDB {
	public $options = 'options', $posts = 'posts', $postmeta = 'postmeta', $active = false, $snapshot;
	function prepare( $q, ...$args ) { return array( $q, $args ); }
	function get_var( $q ) { return $q === 'SELECT @@in_transaction' ? (int) $this->active : '42'; }
	function get_col( $q ) { return array( 'InnoDB', 'InnoDB' ); }
	function delete( ...$args ) { return 1; }
	function query( $q ) {
		if ( is_array( $q ) ) { return 1; } // Atomic course-lock insertion.
		if ( $q === 'START TRANSACTION' ) { $this->snapshot = array( $GLOBALS['meta'], $GLOBALS['content'] ); $this->active = true; }
		if ( $q === 'ROLLBACK' && $this->active ) { list( $GLOBALS['meta'], $GLOBALS['content'] ) = $this->snapshot; $this->active = false; }
		if ( $q === 'COMMIT' ) { $this->active = false; }
		return 1;
	}
}
$wpdb = new BuilderDB();
$request_nonce = true; $as_admin = false; $audit_fails = false; $content = 'old';
require dirname( __DIR__ ) . '/wp-content/themes/hello-elementor-child/inc/cas-builder-sale.php';
function reset_builder( $on, $saved = '' ) {
	seed( '2.00', $on ? '1.25' : '', $saved );
	$GLOBALS['wpdb']->active = false;
	$GLOBALS['audit_fails'] = false;
	$GLOBALS['request_nonce'] = true;
	$GLOBALS['content'] = 'old';
	( new ReflectionProperty( 'CAS_Builder_Sale', 'save' ) )->setValue( null, null );
}
function builder_post( $on, $regular = '2.00', $sale = '1.25' ) {
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = array( 'course_id' => 1, 'pricing' => array( 'type' => 'paid' ), 'course_price' => $regular,
		'course_sale_price' => $sale, '_cas_builder_sale' => wp_json_encode( array( 'on' => $on, 'version' => cas_builder_sale_version( 1 ) ) ) );
}
function builder_save() {
	CAS_Builder_Sale::start(); CAS_Builder_Sale::transaction();
	$GLOBALS['content'] = 'new';
	update_post_meta( 1, 'tutor_course_sale_price', '0.01' ); // Vendor writes cannot bypass staged prices.
	CAS_Builder_Sale::apply( 1 ); CAS_Builder_Sale::finish( 1 );
}
reset_builder( true ); builder_post( '0' ); builder_save();
verify( cas_price_current( 1 )['sale'] === '' && cas_price_sale_amount( cas_price_current( 1 ) ) === '1.25', 'Normal save must disable and retain the offer.' );
verify( ! $wpdb->active && $content === 'new', 'Successful save must commit content and pricing.' );
reset_builder( false, '1.25' ); builder_post( '1' ); builder_save();
verify( cas_price_current( 1 )['sale'] === '1.25', 'Normal save must enable the remembered offer.' );

reset_builder( true ); builder_post( '1', '2.00', '0' ); builder_save();
verify( get_post_meta( 1, 'tutor_course_price' ) === '0.00' && cas_price_current( 1 )['regular'] === '2.00', 'Native builder save must activate free price and retain reference price.' );
( new ReflectionProperty( 'CAS_Builder_Sale', 'save' ) )->setValue( null, null );
builder_post( '0', '2.00', '0' ); builder_save();
verify( get_post_meta( 1, 'tutor_course_price' ) === '2.00' && cas_price_sale_amount( cas_price_current( 1 ) ) === '0.00', 'Native builder Off must restore regular and retain the zero offer.' );

reset_builder( false, '1.25' ); builder_post( '1' ); $meta['tutor_course_price'] = '3.00';
try { CAS_Builder_Sale::start(); throw new RuntimeException( 'Stale save accepted' ); }
catch ( BuilderJSON $e ) { verify( $e->getCode() === 409, 'Stale price snapshot must be refused.' ); }
verify( $meta['tutor_course_price'] === '3.00', 'Stale save must preserve the newer value.' );

reset_builder( false ); builder_post( '1', '2.00', '' );
try { CAS_Builder_Sale::start(); throw new RuntimeException( 'Empty sale accepted' ); }
catch ( BuilderJSON $e ) { verify( $e->getCode() === 400, 'On requires a valid lower Sale Price.' ); }

reset_builder( true ); builder_post( '0' ); $request_nonce = false;
try { CAS_Builder_Sale::start(); throw new RuntimeException( 'Bad nonce accepted' ); }
catch ( BuilderJSON $e ) { verify( $e->getCode() === 403, 'Request nonce is mandatory.' ); }

foreach ( array( 'audit', 'write' ) as $failure ) {
	reset_builder( true ); $old = cas_price_current( 1 ); builder_post( '0' );
	if ( $failure === 'audit' ) { $audit_fails = true; }
	else { $faults = array( '_cas_inactive_sale_price' => array( '1.25' ) ); }
	try { builder_save(); throw new RuntimeException( 'Failed save accepted' ); }
	catch ( BuilderJSON $e ) { verify( $e->getCode() === 503, 'Failed save must report failure.' ); }
	verify( cas_price_current( 1 ) === $old && $content === 'old', 'Failure must roll back content and price together.' );
	verify( ! $wpdb->active && ! cas_price_gate_open(), 'Failure must close the transaction and privileged window.' );
}
reset_builder( true ); builder_post( '1' ); unset( $_POST['_cas_builder_sale'] );
CAS_Builder_Sale::start();
verify( ! cas_builder_sale_active( 1 ), 'Unchanged legacy content saves need no new price mutation.' );
reset_builder( true ); builder_post( '0' ); CAS_Builder_Sale::start();
$role = false; $as_admin = true; update_post_meta( 1, 'tutor_course_price', '50.00' );
verify( get_post_meta( 1, 'tutor_course_price' ) === '2.00', 'Staged native administrator writes must also stay blocked.' );
CAS_Builder_Sale::rollback();
( new ReflectionProperty( 'CAS_Builder_Sale', 'save' ) )->setValue( null, null );
echo "Builder sale: {$checks} cumulative assertions passed.\n";
