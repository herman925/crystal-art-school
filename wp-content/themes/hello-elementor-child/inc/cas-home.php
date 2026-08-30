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

function cas_render_community_band() {
	$courses = get_post_type_archive_link( 'courses' );
	$img_uri = get_stylesheet_directory_uri() . '/img/';
	$img     = $img_uri . 'Rubber.jpg';
	$srcset  = implode(
		', ',
		[
			esc_url( $img_uri . 'Rubber-224.jpg' ) . ' 224w',
			esc_url( $img_uri . 'Rubber-448.jpg' ) . ' 448w',
			esc_url( $img ) . ' 1438w',
		]
	);

	ob_start();
	?>
	<section class="pg-section">
		<div class="pg-wrap">
			<div class="band-forest band-community reveal-up">
				<div style="flex:0 0 160px;border-radius:var(--radius-card);overflow:hidden;aspect-ratio:1/1;">
					<img src="<?php echo esc_url( $img ); ?>" srcset="<?php echo esc_attr( $srcset ); ?>" sizes="200px" width="1438" height="890" alt="Students sharing work in the studio community" style="width:100%;height:100%;object-fit:cover;" loading="lazy" decoding="async">
				</div>
				<div style="flex:1;min-width:200px;">
					<div class="eyebrow"><span class="star">&#10022;</span> Fellow tier</div>
					<h2 class="font-a" style="font-size:clamp(29px,3.1vw,42px);">Paint alongside others.</h2>
					<p>Every course can be bought at the Fellow tier, which adds Crystal&rsquo;s private Discord channel to the course videos &mdash; somewhere to post work in progress and ask questions as you paint.</p>
				</div>
				<a class="btn-big btn-marine" href="<?php echo esc_url( $courses . '#membership' ); ?>" style="flex:none;">See the tiers &rarr;</a>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_community_band', 'cas_render_community_band' );

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
