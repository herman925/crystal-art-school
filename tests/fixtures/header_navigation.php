<?php
// Render the real header with synthetic sessions; never bootstrap WordPress.
if ( PHP_SAPI !== 'cli' || defined( 'ABSPATH' ) ) { throw new RuntimeException( 'Standalone fixture only' ); }
define( 'ABSPATH', __DIR__ );
$input = json_decode( stream_get_contents( STDIN ), true, 512, JSON_THROW_ON_ERROR );
function add_filter( ...$args ) {}
function add_shortcode( ...$args ) {}
function is_user_logged_in() { return ! empty( $GLOBALS['input']['member'] ); }
function get_option( $key ) { return 'users_can_register' === $key && ! empty( $GLOBALS['input']['registration'] ); }
function home_url( $path ) { return 'http://localhost' . $path; }
function cas_page_url( $slug ) { return '/' . $slug . '/'; }
function cas_account_cart_url() { return '/cart/'; }
function cas_account_login_url() { return '/login/'; }
function cas_account_signup_url() { return '/student-registration/'; }
function cas_account_dashboard_url() { return '/dashboard/'; }
function wp_login_url() { return '/login/'; }
function wp_registration_url() { return '/student-registration/'; }
function wp_logout_url( $url ) { return '/logout/'; }
function wp_get_current_user() { return (object) array( 'ID' => 7, 'display_name' => $GLOBALS['input']['name'] ?? 'Audit Member' ); }
function get_avatar_url( $id, $args ) { return '/theme/img/ProfilePic-96.jpg'; }
function cas_brand_image( $key ) { return '/theme/img/lau-logo.png'; }
function esc_url( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_attr( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); }
function wp_nav_menu( $args ) {
	$links = '';
	foreach ( array( 'Home' => '', 'Courses' => 'courses', 'Gallery' => 'gallery', 'Showcase' => 'showcase', 'Blog' => 'blog', 'About' => 'about', 'Free videos' => 'free-videos', 'FAQ' => 'faq' ) as $label => $slug ) {
		$links .= '<li><a href="/' . ( $slug ? $slug . '/' : '' ) . '">' . $label . '</a></li>';
	}
	return isset( $args['items_wrap'] ) ? $links : '<ul class="nav-links">' . $links . '</ul>';
}
require dirname( __DIR__, 2 ) . '/wp-content/themes/hello-elementor-child/inc/cas-header.php';
echo cas_render_header();
