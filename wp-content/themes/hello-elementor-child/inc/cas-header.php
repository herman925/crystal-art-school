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
 * The Courses mega-drawer is deliberately not reproduced. It exists in the prototype
 * but the direction document does not specify it, so it is left out of this pass
 * rather than ported on assumption.
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

		if ( in_array( 'current-menu-item', (array) $item->classes, true ) || ! empty( $item->current ) ) {
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
	<nav class="site-nav" aria-label="Main">
		<div class="nav-top">
			<div class="inner">
				<a class="nav-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_url( cas_brand_image( 'cas_logo' ) ); ?>" alt="Lau. — Crystal&#39;s Art School">
				</a>
				<?php echo $menu; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="nav-auth">
					<a class="cart-btn" href="<?php echo esc_url( get_permalink( get_page_by_path( 'checkout' ) ) ); ?>" aria-label="Shopping cart">
						<?php echo cas_header_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="cart-badge" aria-hidden="true">0</span>
					</a>
					<a class="btn ghost" href="<?php echo esc_url( function_exists( 'cas_account_login_url' ) ? cas_account_login_url() : wp_login_url() ); ?>">Log in</a>
					<a class="btn primary" href="<?php echo esc_url( ( function_exists( 'cas_account_signup_url' ) ? cas_account_signup_url() : '' ) ?: wp_registration_url() ); ?>">Sign up</a>
					<button class="mode-toggle" type="button" aria-pressed="true" aria-label="Dark mode" title="Light / dark mode">
						<?php
						echo cas_header_icon( 'sun' ) . cas_header_icon( 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</button>
					<button class="nav-burger" type="button" aria-label="Menu" aria-expanded="false">☰</button>
				</div>
			</div>
		</div>
		<div class="nav-sheet"><?php echo strip_tags( $sheet, '<a>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</nav>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_header', 'cas_render_header' );
