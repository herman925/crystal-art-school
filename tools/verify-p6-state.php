<?php
/**
 * Verify the production state established by P6.
 *
 * Run from the WordPress root with:
 *     wp eval-file tools/verify-p6-state.php
 *
 * This is deliberately read-only. Publishing and registration are database decisions, not
 * theme boot behaviour, so the theme must never change them on page load.
 */

defined( 'ABSPATH' ) || exit;
global $wpdb;

$expected = [
	'clouds-beginner'    => [
		'title'   => 'Beginner’s Guide to Drawing & Painting Clouds',
		'regular' => 72.0,
		'sale'    => 58.0,
	],
	'clouds-intermediate' => [
		'title'   => 'Intermediate Guide to Drawing & Painting Clouds',
		'regular' => 68.0,
		'sale'    => 0.0,
	],
	'little-house'       => [
		'title'   => 'Little House in Watercolour',
		'regular' => 24.0,
		'sale'    => 0.0,
	],
	'urban-sketch'       => [
		'title'   => 'Urban Sketching in Watercolour',
		'regular' => 24.0,
		'sale'    => 0.0,
	],
];

$failures = [];
$course_state = [];
$close_enough = static function ( $actual, $expected_value ) {
	return abs( (float) $actual - (float) $expected_value ) < 0.001;
};

if ( ! function_exists( 'tutor_utils' ) ) {
	$failures[] = 'Tutor utilities are unavailable.';
}

foreach ( $expected as $slug => $wanted ) {
	$posts = get_posts(
		[
			'post_type'      => 'courses',
			'post_status'    => 'any',
			'name'           => $slug,
			'posts_per_page' => 1,
		]
	);
	$post  = $posts[0] ?? null;

	if ( ! $post instanceof WP_Post ) {
		$failures[] = "Missing course: {$slug}";
		continue;
	}

	$price = function_exists( 'tutor_utils' )
		? tutor_utils()->get_raw_course_price( $post->ID )
		: (object) [];
	$regular = (float) ( $price->regular_price ?? 0 );
	$sale    = (float) ( $price->sale_price ?? 0 );

	$course_state[] = [
		'id'      => (int) $post->ID,
		'slug'    => $slug,
		'title'   => $post->post_title,
		'status'  => $post->post_status,
		'regular' => $regular,
		'sale'    => $sale,
	];

	if ( 'publish' !== $post->post_status ) {
		$failures[] = "Course {$slug} is not published.";
	}
	if ( $wanted['title'] !== $post->post_title ) {
		$failures[] = "Course {$slug} title changed from the accepted placeholder wording.";
	}
	if ( ! $close_enough( $regular, $wanted['regular'] ) || ! $close_enough( $sale, $wanted['sale'] ) ) {
		$failures[] = "Course {$slug} price metadata does not match P6.";
	}
}

$currency = function_exists( 'get_tutor_option' )
	? (string) get_tutor_option( 'currency_code' )
	: '';
if ( 'GBP' !== $currency ) {
	$failures[] = 'Tutor currency is not GBP.';
}

$registration_page = function_exists( 'tutor_utils' )
	? (int) tutor_utils()->get_option( 'student_register_page', 0 )
	: 0;
if ( 1 !== (int) get_option( 'users_can_register', 0 ) ) {
	$failures[] = 'WordPress visitor registration is disabled.';
}
if ( ! $registration_page ) {
	$failures[] = 'Tutor has no student registration page configured.';
}

$payment_patterns = [ 'payment', 'stripe', 'paypal', 'paystack', 'mollie', 'razorpay', 'woocommerce' ];
$payment_sql      = implode(
	' OR ',
	array_map(
		static function ( $pattern ) {
			return "option_name LIKE '%" . esc_sql( $pattern ) . "%'";
		},
		$payment_patterns
	)
);
$payment_options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE {$payment_sql}" );
$tutor_option    = (array) get_option( 'tutor_option', [] );
$tutor_payment_keys = array_values(
	array_filter(
	array_keys( $tutor_option ),
	static function ( $key ) {
		return (bool) preg_match( '/payment|stripe|paypal|paystack|mollie|razorpay|woocommerce/i', (string) $key );
	}
)
);
$active_plugins = (array) get_option( 'active_plugins', [] );
$payment_plugins = array_values(
	array_filter(
		$active_plugins,
		static function ( $plugin ) {
			return (bool) preg_match( '/payment|gateway|stripe|paypal|paystack|mollie|razorpay|woocommerce/i', (string) $plugin );
		}
	)
);
if ( $payment_options || $tutor_payment_keys || $payment_plugins ) {
	$failures[] = 'Payment-related option or plugin signals are present.';
}

$result = [
	'ok'                 => ! $failures,
	'courses'            => $course_state,
	'currency'           => $currency,
	'users_can_register' => (int) get_option( 'users_can_register', 0 ),
	'registration_page'  => $registration_page,
	'payment_options'    => array_values( $payment_options ),
	'tutor_payment_keys' => $tutor_payment_keys,
	'payment_plugins'    => $payment_plugins,
	'failures'           => $failures,
];

echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
if ( $failures ) {
	exit( 1 );
}
