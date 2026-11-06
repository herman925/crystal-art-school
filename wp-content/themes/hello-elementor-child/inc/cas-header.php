<?php
/**
 * Site header, reproducing the markup the prototype's CSS is written against.
 *
 * The prototype generated this from js/site-nav.js and re-ran it on a 50ms poll to
 * survive the dc-runtime overwriting the DOM. None of that applies here, so the
 * polling, the data-cas-synced guards and the hardcoded LINKS array are all dropped:
 * the links come from the `cas-primary` WordPress menu instead, so they stay editable
 * in Appearance > Menus.
 *
 * The Courses disclosure follows the desktop prototype; its medium links come from
 * the published catalogue taxonomy, while labels/order of the main menu stay in WordPress.
 */

defined( 'ABSPATH' ) || exit;

function cas_header_icon( $name ) {
	$icons = [
		'cart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle><path d="M2 3h3.2l2.5 12.2a1.5 1.5 0 0 0 1.5 1.2h9a1.5 1.5 0 0 0 1.5-1.2L21.5 7H6.5"></path></svg>',
		'sun'  => '<svg class="ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><circle cx="12" cy="12" r="4.2"></circle><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M4.9 4.9l1.6 1.6M17.5 17.5l1.6 1.6M19.1 4.9l-1.6 1.6M6.5 17.5l-1.6 1.6"></path></svg>',
		'moon' => '<svg class="ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4a8.5 8.5 0 1 0 10.5 10.5z"></path></svg>',
	];

	return $icons[ $name ] ?? '';
}

/** Match destinations, so renaming/reordering a menu label does not break its behaviour. */
function cas_header_item_is( $item, $slug ) {
	if ( untrailingslashit( cas_page_url( $slug ) ) === untrailingslashit( home_url( '/' ) ) ) { return false; }
	$url = wp_parse_url( (string) ( $item->url ?? '' ) );
	$target = wp_parse_url( cas_page_url( $slug ) );
	if ( ! is_array( $url ) || ! is_array( $target )
		|| strtolower( $url['host'] ?? $target['host'] ?? '' ) !== strtolower( $target['host'] ?? '' )
		|| untrailingslashit( $url['path'] ?? '/' ) !== untrailingslashit( $target['path'] ?? '/' ) ) { return false; }
	wp_parse_str( $target['query'] ?? '', $expected );
	wp_parse_str( $url['query'] ?? '', $actual );
	foreach ( $expected as $key => $value ) { if ( ! isset( $actual[ $key ] ) || $actual[ $key ] !== $value ) { return false; } }
	return true;
}

function cas_header_course_drawer() {
	$base = cas_page_url( 'courses' );
	$links = array( array( 'All courses', $base, true ) );
	foreach ( cas_course_medium_terms() as $slug => $name ) { $links[] = array( $name, add_query_arg( 'cat', $slug, $base ), false ); }
	$out = '<button class="nav-caret-btn" type="button" aria-expanded="false" aria-controls="cas-nav-courses" aria-label="Show course categories"><svg class="nav-caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 4.5 6 8.5 10 4.5"></path></svg></button>';
	$out .= '<div class="nav-mega" id="cas-nav-courses"><span class="mega-eyebrow">By Medium</span><div class="mega-cats">';
	foreach ( $links as $link ) { $out .= '<a class="mega-cat' . ( $link[2] ? ' is-all' : '' ) . '" href="' . esc_url( $link[1] ) . '"><span>' . esc_html( $link[0] ) . '</span></a>'; }
	return $out . '</div></div>';
}
add_filter( 'nav_menu_css_class', function ( $classes, $item, $args ) {
	if ( 'cas-primary' === ( $args->theme_location ?? '' ) && 'nav-links' === ( $args->menu_class ?? '' ) && cas_header_item_is( $item, 'courses' ) ) { $classes[] = 'nav-drop'; }
	return $classes;
}, 10, 3 );
add_filter( 'walker_nav_menu_start_el', function ( $html, $item, $depth, $args ) {
	return 0 === $depth && 'cas-primary' === ( $args->theme_location ?? '' ) && 'nav-links' === ( $args->menu_class ?? '' ) && cas_header_item_is( $item, 'courses' ) ? $html . cas_header_course_drawer() : $html;
}, 10, 4 );

/**
 * The prototype marks the current page with `class="active"` on the anchor. WordPress
 * marks it on the <li> instead, so the class is moved onto the link and the CSS needs
 * no alias rule.
 */
add_filter(
	'nav_menu_link_attributes',
	function ( $atts, $item, $args ) {
		if ( 'cas-primary' !== ( $args->theme_location ?? '' ) ) {
			return $atts;
		}

		if ( 'nav-links' === ( $args->menu_class ?? '' ) && cas_header_item_is( $item, 'courses' ) ) { $atts['class'] = trim( ( $atts['class'] ?? '' ) . ' nav-trigger' ); }
		$section = ( cas_header_item_is( $item, 'courses' ) && ( is_post_type_archive( 'courses' ) || is_singular( array( 'courses', 'lesson' ) ) || is_tax( 'course-category' ) ) )
			|| ( cas_header_item_is( $item, 'blog' ) && is_singular( 'post' ) );
		if ( $section || in_array( 'current-menu-item', (array) $item->classes, true ) || ! empty( $item->current ) ) {
			$atts['class'] = trim( ( $atts['class'] ?? '' ) . ' active' );
		}

		return $atts;
	},
	10,
	3
);

function cas_render_header() {
	$menu = wp_nav_menu(
		[
			'theme_location' => 'cas-primary',
			'container'      => false,
			'menu_class'     => 'nav-links',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
		]
	);

	$sheet = wp_nav_menu(
		[
			'theme_location' => 'cas-primary',
			'container'      => false,
			'items_wrap'     => '%3$s',
			'depth'          => 1,
			'fallback_cb'    => false,
			'echo'           => false,
		]
	);

	ob_start();
	?>
	<div class="glow-layer" aria-hidden="true"></div>
	<nav class="site-nav" aria-label="Main">
		<div class="nav-top">
			<div class="inner">
				<a class="nav-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_url( cas_brand_image( 'cas_logo' ) ); ?>" alt="Lau. — Crystal&#39;s Art School">
				</a>
				<?php echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="nav-auth">
					<a class="cart-btn" href="<?php echo esc_url( function_exists( 'cas_account_cart_url' ) ? cas_account_cart_url() : cas_page_url( 'checkout' ) ); ?>" aria-label="Shopping cart">
						<?php echo cas_header_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="cart-badge" aria-hidden="true" hidden style="display:none;"></span>
					</a>
					<?php if ( ! is_user_logged_in() ) : ?>
						<a class="btn ghost" href="<?php echo esc_url( function_exists( 'cas_account_login_url' ) ? cas_account_login_url() : wp_login_url() ); ?>">Log in</a>
						<?php if ( get_option( 'users_can_register' ) ) : ?>
							<a class="btn primary" href="<?php echo esc_url( ( function_exists( 'cas_account_signup_url' ) ? cas_account_signup_url() : '' ) ?: wp_registration_url() ); ?>">Sign up</a>
						<?php endif; ?>
					<?php else : ?>
						<?php $member = wp_get_current_user(); $first_name = explode( ' ', trim( $member->display_name ) )[0]; $studio = function_exists( 'cas_account_dashboard_url' ) ? cas_account_dashboard_url() : home_url( '/' ); ?>
						<a class="btn ghost cas-desktop-member" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Log out</a>
						<a class="nav-user" href="<?php echo esc_url( $studio ); ?>" aria-label="My studio" style="text-decoration:none;"><img src="<?php echo esc_url( get_avatar_url( $member->ID, array( 'size' => 64 ) ) ); ?>" alt=""><span class="nm"><?php echo esc_html( $first_name ); ?></span></a>
					<?php endif; ?>
					<button class="mode-toggle" type="button" aria-pressed="true" aria-label="Dark mode" title="Light / dark mode">
						<?php
						echo cas_header_icon( 'sun' ) . cas_header_icon( 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</button>
					<button class="nav-burger" type="button" aria-label="Menu" aria-expanded="false" aria-controls="cas-mobile-nav">☰</button>
				</div>
			</div>
		</div>
		<div class="nav-sheet" id="cas-mobile-nav" aria-hidden="true" inert>
			<?php echo strip_tags( $sheet, '<a>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( is_user_logged_in() ) : ?>
				<a href="<?php echo esc_url( $studio ); ?>">My studio</a>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Log out</a>
			<?php else : ?>
				<a href="<?php echo esc_url( function_exists( 'cas_account_login_url' ) ? cas_account_login_url() : wp_login_url() ); ?>"><?php echo get_option( 'users_can_register' ) ? 'Log in / Sign up' : 'Log in'; ?></a>
			<?php endif; ?>
		</div>
	</nav>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_header', 'cas_render_header' );
