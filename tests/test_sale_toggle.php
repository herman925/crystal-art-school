<?php
/** Isolated checks of the actual price-state/write/rollback functions; no WordPress or network. */
define( 'ABSPATH', __DIR__ );
$meta = array();
$faults = array();
$role = true;
$checks = 0;
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function wp_cache_delete( ...$args ) {}
function get_post_type( $id ) { return 'courses'; }
function get_current_user_id() { return 1; }
function cas_content_is_user( $id ) { return $GLOBALS['role']; }
function get_post_meta( $id, $key, $single = true ) { return $GLOBALS['meta'][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) {
	if ( true === cas_price_block_role_write( null, $id, $key ) ) { return true; }
	if ( in_array( (string) $value, $GLOBALS['faults'][ $key ] ?? array(), true ) ) { return false; }
	$GLOBALS['meta'][ $key ] = $value;
	return true;
}
require dirname( __DIR__ ) . '/wp-content/themes/hello-elementor-child/inc/cas-price-gate.php';
function verify( $ok, $message ) {
	$GLOBALS['checks']++;
	if ( ! $ok ) { throw new RuntimeException( $message ); }
}
function seed( $regular, $sale, $saved = '' ) {
	$GLOBALS['meta'] = array( '_tutor_course_price_type' => 'paid', 'tutor_course_price' => $regular,
		'tutor_course_sale_price' => $sale, '_cas_inactive_sale_price' => $saved, '_cas_zero_sale_regular' => '' );
	$GLOBALS['faults'] = array();
}

seed( '100', '80' );
$old = cas_price_current( 1 );
verify( $old['sale'] === '80.00', 'Existing active sale must stay on without migration.' );
verify( cas_price_sale_amount( $old ) === '80.00', 'Legacy sale amount must be shown.' );
$off = cas_price_proposed( '100', '80', '0' );
verify( cas_price_write_pair( 1, $off ), 'Off state must save and verify all fields.' );
verify( get_post_meta( 1, 'tutor_course_sale_price' ) === '', 'Native checkout must see no sale while Off.' );
verify( cas_price_sale_amount( cas_price_current( 1 ) ) === '80.00', 'Off must remember the offer.' );
$on = cas_price_proposed( '100', '80', '1' );
verify( cas_price_write_pair( 1, $on ), 'Saved offer must turn back on.' );
verify( get_post_meta( 1, 'tutor_course_sale_price' ) === '80.00', 'Native checkout must see the enabled offer.' );
verify( get_post_meta( 1, '_cas_inactive_sale_price' ) === '', 'Active amount must not leave a competing remembered value.' );
verify( ! cas_price_gate_open(), 'Privileged write window must close.' );

foreach ( array(
	array( '0', '80', '1' ), array( '100', '', '1' ),
	array( '100', '100', '1' ), array( '100', '101', '1' ), array( '100', '-1', '0' ),
	array( '100', '80', null ), array( '100', '80', array( '1' ) ), array( array( '100' ), '80', '1' ),
) as $invalid ) {
	verify( null === cas_price_proposed( ...$invalid ), 'Invalid or missing sale selection must be refused.' );
}
verify( cas_price_proposed( '100', '', '0' )['sale'] === '', 'Off may have no stored offer.' );

// Direct content-role metadata writes must not bypass the confirmed workflow.
$before = cas_price_current( 1 );
update_post_meta( 1, '_cas_inactive_sale_price', '1.00' );
update_post_meta( 1, 'tutor_course_sale_price', '1.00' );
verify( cas_price_current( 1 ) === $before, 'Both native and remembered amounts must be protected.' );

// A failed remembered-field write must restore the native active price too.
seed( '100', '80' );
$old = cas_price_current( 1 );
$faults = array( '_cas_inactive_sale_price' => array( '80.00' ) );
verify( ! cas_price_write_pair( 1, $off ), 'A partial Off write must fail verification.' );
verify( cas_price_restore_pair( 1, $old, $off ), 'Partial Off write must restore the old sale.' );
verify( cas_price_current( 1 ) === $old, 'Rollback must restore all old values.' );

seed( '100', '', '80' );
$old = cas_price_current( 1 );
$faults = array( '_cas_inactive_sale_price' => array( '' ) );
verify( ! cas_price_write_pair( 1, $on ), 'A partial On write must fail verification.' );
verify( cas_price_restore_pair( 1, $old, $on ), 'Partial On write must restore the inactive offer.' );
verify( cas_price_current( 1 ) === $old, 'Inactive offer must survive rollback.' );

seed( '100', '80' );
$old = cas_price_current( 1 );
verify( cas_price_write_pair( 1, $off ), 'Prepare a completed owned change.' );
$meta['_cas_inactive_sale_price'] = '70.00';
verify( ! cas_price_restore_pair( 1, $old, $off ), 'Rollback must flag a newer foreign value.' );
verify( $meta['_cas_inactive_sale_price'] === '70.00', 'Rollback must preserve the foreign value.' );
verify( ! cas_price_gate_open(), 'Write window must also close after rollback.' );

seed( '100', '0' );
verify( cas_price_current( 1 )['sale'] === '', 'Old zero-valued sale metadata must remain inactive.' );
$zero = cas_price_proposed( '100', '0', '1' );
verify( $zero !== null && cas_price_write_pair( 1, $zero ), 'Explicit zero sale must save.' );
verify( get_post_meta( 1, 'tutor_course_price' ) === '0.00', 'Native checkout must receive a real zero base price.' );
verify( cas_price_current( 1 )['regular'] === '100.00' && cas_price_current( 1 )['sale'] === '0.00', 'Editor must retain configured regular price and active zero sale.' );
$zero_off = cas_price_proposed( '100', '0', '0' );
verify( cas_price_write_pair( 1, $zero_off ), 'Zero sale must turn off normally.' );
verify( get_post_meta( 1, 'tutor_course_price' ) === '100.00' && cas_price_sale_amount( cas_price_current( 1 ) ) === '0.00', 'Off must restore regular and remember zero.' );
verify( cas_price_write_pair( 1, $zero ), 'Zero sale must re-enable without retyping.' );
verify( cas_price_write_pair( 1, cas_price_proposed( '100', '80', '1' ) ), 'Changing a free sale to a positive sale must restore the regular base.' );
verify( get_post_meta( 1, 'tutor_course_price' ) === '100.00' && cas_price_current( 1 )['sale'] === '80.00', 'Positive sale must leave no zero-price projection.' );

foreach ( array( 'tutor_course_price' => '0.00', 'tutor_course_sale_price' => '0.00', '_cas_zero_sale_regular' => '100.00' ) as $key => $bad ) {
	seed( '100', '80' ); $old = cas_price_current( 1 ); $faults = array( $key => array( $bad ) );
	verify( ! cas_price_write_pair( 1, $zero ), 'Partial zero-sale writes must fail verification.' );
	verify( cas_price_restore_pair( 1, $old, $zero ), 'Partial zero-sale writes must restore safely.' );
	verify( cas_price_current( 1 ) === $old, 'Partial zero sale must preserve original pricing.' );
}
echo "Sale toggle: {$checks} assertions passed.\n";
