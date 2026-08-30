<?php
/**
 * Homepage sections below the hero.
 *
 * The course and blog rails are separate — they need published content, and there is
 * none yet. These are the sections whose content is fixed.
 *
 * The testimonials are no longer hardcoded. They come from the Testimonials post type,
 * so the prototype's invented quotes and the placeholder banner warning about them have
 * both been removed together — which is the only safe way to remove such a warning.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Testimonials.
 *
 * The prototype carried three invented quotes under a visible placeholder banner. Both
 * are gone: quotes now come from the Testimonials post type, where Crystal writes the
 * comment and picks which member said it, and the name and avatar are read from that
 * member's record. With nothing published the section renders nothing at all, rather
 * than a heading over an empty grid.
 */
function cas_render_testimonials() {
	$quotes = cas_testimonial_items();

	if ( ! $quotes ) {
		return '';
	}

	ob_start();
	?>
	<section class="pg-section" style="padding-bottom:36px;">
		<div class="pg-wrap">
			<h2 class="section-title">Real hands, real progress</h2>
			<div class="testi-grid">
				<?php foreach ( $quotes as $i => $q ) : ?>
					<div class="testi-card reveal-up" style="--i:<?php echo esc_attr( $i ); ?>">
						<div class="who">
							<?php if ( $q['avatar'] ) : ?><img class="who-avatar" src="<?php echo esc_url( $q['avatar'] ); ?>" alt="" loading="lazy"><?php else : ?><span class="who-avatar" aria-hidden="true"></span><?php endif; ?>
							<div>
								<strong><?php echo esc_html( $q['name'] ); ?></strong>
								<div class="hand-note">Profile picture &amp; username &mdash; from the member record</div>
							</div>
						</div>
						<div class="q"><?php echo esc_html( $q['quote'] ); ?></div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_testimonials', 'cas_render_testimonials' );

/**
 * Follow-along strip. Uses the same four links as the footer and the About block, from
 * cas_social_links(), so the three can never drift apart.
 */
function cas_render_social_strip() {
	ob_start();
	?>
	<section class="pg-section" style="padding-bottom:80px;">
		<div class="pg-wrap">
			<div class="social-strip">
				<span class="ss-label">Follow along</span>
				<div class="ss-icons"><?php echo cas_social_links( 'ss-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_social_strip', 'cas_render_social_strip' );
