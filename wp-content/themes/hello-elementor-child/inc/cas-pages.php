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



function cas_faq_intro_default() {
	return '';
}

function cas_faq_intro() {
	return trim( (string) get_theme_mod( 'cas_faq_intro', cas_faq_intro_default() ) );
}

/*
 * cas-customizer-map.php creates the FAQ section as a pointer at priority 100. Re-register
 * it here, at the same priority used by the other page modules, so the page's own editable
 * opening paragraph lives beside that pointer without moving the FAQ entries out of the
 * dashboard post type.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_faq',
			[
				'title'       => 'FAQ',
				'panel'       => 'cas_pages',
				'priority'    => 20,
				'description' => 'The opening paragraph is edited here. Questions and answers are edited under <strong>Dashboard &rarr; FAQ</strong> — each entry is one question, with its answer in the body and its order in Page Attributes.',
			]
		);

		$wp_customize->add_setting(
			'cas_faq_intro',
			[
				'default'           => cas_faq_intro_default(),
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			]
		);

		$wp_customize->add_control(
			'cas_faq_intro',
			[
				'label'       => 'Opening paragraph',
				'description' => 'The paragraph below the wood banner. The hero title and the FAQ heading are part of the page design.',
				'section'     => 'cas_page_faq',
				'type'        => 'textarea',
			]
		);
	},
	110
);

function cas_render_faq() {
	ob_start();
	echo cas_page_hero( 'Questions, answered', 'Ask me anything.' ); // phpcs:ignore WordPress.Security.EscapeOutput
	$intro = cas_faq_intro();
	if ( '' !== $intro ) :
		?>
		<div class="container page-lead-wrap"><p class="page-lead"><?php echo wp_kses_post( $intro ); ?></p></div>
		<?php
	endif;
	?>
	<main class="container" id="content" tabindex="-1">
		<section id="faq" class="section" aria-labelledby="faq-h">
			<div class="section-head">
				<h2 class="section-title" id="faq-h">Frequently asked questions</h2>
			</div>
			<div class="acc-list">
				<?php foreach ( cas_faq_items() as $item ) : ?>
					<details class="acc-item">
						<summary>
							<h3 style="margin:0;font:inherit;"><?php echo wp_kses_post( $item['q'] ); ?></h3>
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

		<section class="section">
			<div class="band-forest reveal-one">
				<div style="flex:1;">
					<div class="eyebrow"><span class="star">&#10022;</span> Still curious?</div>
					<h2 class="marker-title" style="font-size:clamp(31px,3.1vw,44px);">Ask me directly.</h2>
					<p style="margin:0;max-width:56ch;">If your question isn&rsquo;t here, write to the studio &mdash; I read every message myself and usually reply within two days.</p>
				</div>
				<a class="btn-big" href="<?php echo esc_url( cas_page_url( 'about' ) . '#contact' ); ?>" style="flex:none;">Write to the studio &rarr;</a>
			</div>
		</section>
	</main>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_faq', 'cas_render_faq' );
