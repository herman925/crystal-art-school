<?php
/**
 * Shared page furniture, and the FAQ page.
 *
 * The wood-grain page hero is used by six pages, so it lives here once rather than
 * being copied into each. Its Logo2 is right-aligned, which is not a styling choice:
 * the direction document's placement rule (§1.4.5) keys off whether a page has a
 * wood-grain banner — pages that have one get Logo2 right-aligned, pages without it get
 * Logo2 centred. The homepage has no banner, which is why its Logo2 carries `is-center`
 * and this one does not.
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $eyebrow Small line above the title.
 * @param string $title   The page title.
 * @param string $size    Optional clamp() override for the title.
 */
function cas_page_hero( $eyebrow, $title, $size = 'clamp(44px,6vw,80px)' ) {
	ob_start();
	?>
	<header class="page-hero">
		<div class="wash" aria-hidden="true"></div>
		<div class="container">
			<?php if ( $eyebrow ) : ?>
				<div class="eyebrow reveal-one"><span class="star">&#10022;</span> <?php echo esc_html( $eyebrow ); ?></div>
			<?php endif; ?>
			<h1 class="marker-title font-a reveal-one" style="font-size:<?php echo esc_attr( $size ); ?>;"><?php echo esc_html( $title ); ?></h1>
			<div class="logo2"><img src="<?php echo esc_url( cas_brand_image( 'cas_logo2' ) ); ?>" alt="Crystal Lau Art" decoding="async"></div>
		</div>
	</header>
	<?php

	return ob_get_clean();
}

add_shortcode(
	'cas_page_hero',
	function ( $atts ) {
		$atts = shortcode_atts( [ 'eyebrow' => '', 'title' => '', 'size' => 'clamp(44px,6vw,80px)' ], $atts );

		return cas_page_hero( $atts['eyebrow'], $atts['title'], $atts['size'] );
	}
);



function cas_render_faq() {
	ob_start();
	echo cas_page_hero( 'Questions, answered', 'Ask me anything.' ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<div class="pg-wrap">
		<section id="faq" class="section">
			<div class="section-head">
				<h2 class="section-title" id="faq-h">Frequently asked questions</h2>
			</div>
			<div class="acc-list">
				<?php foreach ( cas_faq_items() as $item ) : ?>
					<details class="acc-item">
						<summary>
							<?php echo wp_kses_post( $item['q'] ); ?>
							<span class="acc-ico" aria-hidden="true">+</span>
						</summary>
						<div class="acc-body"><?php echo wp_kses_post( $item['a'] ); ?></div>
					</details>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="section">
			<div class="section-head">
				<h2 class="section-title">Ask me directly.</h2>
			</div>
			<p class="shop-lede">
				If your question isn&rsquo;t here, email
				<a href="mailto:<?php echo esc_attr( cas_social()['email'] ); ?>"><?php echo esc_html( cas_social()['email'] ); ?></a>
				and Crystal will answer.
			</p>
			<div class="social-row"><?php echo cas_social_links( 'social-btn' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		</section>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_faq', 'cas_render_faq' );
