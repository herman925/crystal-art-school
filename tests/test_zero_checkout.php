<?php
/** Zero promotions must use the native free-order route, never a provider session. */
require __DIR__ . '/test_builder_sale.php';
class ZeroCheckoutOrder { const TYPE_SINGLE_ORDER = 'single_order'; }
class_alias( 'ZeroCheckoutOrder', 'Tutor\Models\OrderModel' );
$request_nonce = true;
seed( '2.00', '' );
verify( cas_price_write_pair( 1, cas_price_proposed( '2', '0', '1' ) ), 'Prepare explicit zero promotion.' );
builder_post( '1', '2.00', '0' ); unset( $_POST['_cas_builder_sale'] );
try { CAS_Builder_Sale::start(); throw new RuntimeException( 'Zero promotion accepted without control state' ); }
catch ( BuilderJSON $e ) { verify( $e->getCode() === 409, 'Legacy saves must not accidentally overwrite a zero promotion.' ); }
$_POST = array( 'object_ids' => '1', 'order_type' => ZeroCheckoutOrder::TYPE_SINGLE_ORDER,
	'payment_method' => 'stripe', 'payment_type' => 'automate' );
cas_zero_sale_checkout_method();
verify( $_POST['payment_method'] === 'free' && $_POST['payment_type'] === 'manual', 'Zero basket must bypass card processing.' );
$_POST['payment_method'] = 'paypal'; $_POST['payment_type'] = 'automate';
cas_zero_sale_checkout_method();
verify( $_POST['payment_method'] === 'free', 'Zero basket must bypass PayPal too.' );
verify( cas_price_write_pair( 1, cas_price_proposed( '2', '0', '0' ) ), 'End the zero promotion.' );
$_POST['payment_method'] = 'stripe'; $_POST['payment_type'] = 'automate';
cas_zero_sale_checkout_method();
verify( $_POST['payment_method'] === 'stripe', 'Positive-price basket must keep its gateway.' );
seed( '2.00', '0' );
cas_zero_sale_checkout_method();
verify( $_POST['payment_method'] === 'stripe', 'Legacy zero/no-sale metadata must not bypass payment.' );
echo "Zero checkout: {$checks} cumulative assertions passed.\n";
