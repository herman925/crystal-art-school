<?php
// Execute the real recommendation reader without WordPress, writes or private data.
if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Standalone fixture only' ); }
define( 'ABSPATH', __DIR__ );
set_error_handler( function ( $severity, $message ) { throw new RuntimeException( $message ); } );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function add_shortcode( ...$args ) {}
function absint( $value ) { return abs( (int) $value ); }
class WP_Query {
	public $posts;
	public function __construct( $args ) {
		if ( 'publish' !== $args['post_status'] || array( 7 ) !== $args['post__not_in'] || 3 !== $args['posts_per_page'] ) { throw new RuntimeException( 'Recommendation query scope changed' ); }
		$this->posts = array_map( function ( $id ) { return (object) array( 'ID' => $id ); }, array( 1, 2, 3 ) );
	}
}
function get_permalink( $id ) { return '/course/' . $id; }
function get_the_title( $id ) { return 'Course ' . $id; }
function get_the_post_thumbnail_url( ...$args ) { return false; }
function cas_course_facets( $id ) { return array( 'medium' => array( 'Watercolour' ), 'regular' => 2 === $id ? 58 : 40, 'now' => array( 1 => 0, 2 => 58, 3 => 24 )[ $id ], 'is_sale' => 3 === $id ); }
function cas_course_price( $price ) { return '£' . $price; }
require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-account.php';
$rows = cas_account_dashboard_recommendations( array( 7 ) );
if ( array( '£0', '£58', '£24' ) !== array_column( $rows, 'price' ) ) { throw new RuntimeException( 'Free/paid/sale current prices must use the shared facets contract' ); }
echo "PASS current free/paid/sale recommendation prices and published/enrolment exclusion scope\n";
