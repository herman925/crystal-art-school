<?php
/**
 * Site footer, reproducing the markup the prototype's CSS is written against.
 *
 * cas_social_links() renders the channels; the values themselves now live in
 * cas-editable.php so Crystal can change them without a deploy. This is still the one
 * place they are rendered, and it
 * is exposed as [cas_social] as well, because the homepage "About Crystal" and
 * "Follow along" strips use the same four links. In the prototype that sharing was
 * what stopped the three areas drifting apart; keeping one function preserves it.
 *
 * The prototype's is-pending placeholder branch for a missing YouTube link is not
 * carried over — the channel was supplied 2026-08-08 and the branch is unreachable.
 */

defined( 'ABSPATH' ) || exit;



function cas_social_icon( $name ) {
	$icons = [
		'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"></circle></svg>',
		'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M14 9h3V6h-3c-1.7 0-3 1.3-3 3v2H9v3h2v6h3v-6h2.5l.5-3H14v-1.5c0-.3.2-.5.5-.5z"></path></svg>',
		'youtube'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="4"></rect><path d="M10.5 9.2l5 2.8-5 2.8z" fill="currentColor" stroke="none"></path></svg>',
		'email'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path></svg>',
	];

	return $icons[ $name ] ?? '';
}

function cas_social_links( $cls = 'social-btn' ) {
	$s   = cas_social();
	$out = '';

	foreach ( [ 'instagram', 'facebook', 'youtube' ] as $k ) {
		$out .= sprintf(
			'<a class="%s" href="%s" target="_blank" rel="noopener" aria-label="%s">%s</a>',
			esc_attr( $cls ),
			esc_url( $s[ $k ] ),
			esc_attr( ucfirst( $k ) ),
			cas_social_icon( $k )
		);
	}

	return $out . sprintf(
		'<a class="%s" href="mailto:%s" aria-label="Email">%s</a>',
		esc_attr( $cls ),
		esc_attr( $s['email'] ),
		cas_social_icon( 'email' )
	);
}

add_shortcode(
	'cas_social',
	function ( $atts ) {
		$atts = shortcode_atts( [ 'class' => 'social-btn' ], $atts );

		return cas_social_links( $atts['class'] );
	}
);

function cas_page_url( $slug ) {
	// Tutor LMS registers its course post type on the `courses` slug, so no WordPress
	// page can ever hold it: get_page_by_path() returns null and the fallback below
	// sends every catalogue link to the homepage. The archive is the address that
	// resolves; the page lookup stays as the fallback for the case where Tutor is off.
	if ( 'courses' === $slug ) {
		$archive = get_post_type_archive_link( 'courses' );

		if ( $archive ) {
			return $archive;
		}
	}

	$page = get_page_by_path( $slug );

	return $page ? get_permalink( $page ) : home_url( '/' );
}

function cas_render_footer() {
	$s = cas_social();

	ob_start();
	?>
	<footer class="site-footer">
		<div class="inner">
			<div class="footer-grid">
				<div>
					<?php /* Her own signature, in place of the typeset wordmark (client feedback 2026-08-08). Decorative — the studio name is written out in the copyright line — so it is hidden from screen readers rather than announced twice. */ ?>
					<div class="footer-brand footer-sig"><img src="<?php echo esc_url( cas_brand_image( 'cas_signature' ) ); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async"></div>
					<p style="font-size:14px;opacity:.85;max-width:34ch;margin:0;">Watercolour &amp; oil painting, taught step by step by Crystal Lau — artist and founder of SKY GALLERY.</p>
					<div class="social-row"><?php echo cas_social_links( 'social-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				</div>
				<div>
					<h4>Learn</h4>
					<a href="<?php echo esc_url( cas_page_url( 'courses' ) ); ?>">All courses</a>
					<a href="<?php echo esc_url( cas_page_url( 'courses' ) ); ?>#membership">Membership</a>
					<a href="<?php echo esc_url( cas_page_url( 'free-videos' ) ); ?>">Free videos</a>
				</div>
				<div>
					<h4>Explore</h4>
					<a href="<?php echo esc_url( cas_page_url( 'gallery' ) ); ?>">Gallery</a>
					<a href="<?php echo esc_url( cas_page_url( 'showcase' ) ); ?>">Student showcase</a>
					<a href="<?php echo esc_url( cas_page_url( 'blog' ) ); ?>">Blog</a>
					<a href="<?php echo esc_url( cas_page_url( 'about' ) ); ?>">About</a>
				</div>
				<div>
					<h4>Contact</h4>
					<a href="mailto:<?php echo esc_attr( $s['email'] ); ?>"><?php echo esc_html( $s['email'] ); ?></a>
					<a href="<?php echo esc_url( $s['instagram'] ); ?>" target="_blank" rel="noopener">Instagram</a>
					<a href="<?php echo esc_url( $s['facebook'] ); ?>" target="_blank" rel="noopener">Facebook</a>
					<a href="<?php echo esc_url( $s['youtube'] ); ?>" target="_blank" rel="noopener">YouTube</a>
					<a href="<?php echo esc_url( cas_page_url( 'about' ) ); ?>#terms">Terms &amp; privacy</a>
				</div>
			</div>
			<div class="footer-meta"><span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Crystal&#39;s Art School</span></div>
		</div>
	</footer>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_footer', 'cas_render_footer' );
