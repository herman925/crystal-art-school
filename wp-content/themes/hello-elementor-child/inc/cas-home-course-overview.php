<?php
/**
 * Homepage "Course overview" block — direction document section 3.5 (the intro
 * beneath the Shop all courses rail).
 *
 * Crystal's own copy from `Under_Shopallcourses.docx`: two ledes, two numbered
 * principles and four questions she answers herself. DECIDED 2026-08-24 (issue #26,
 * register brief 3): the two ledes and two principles are six Customizer fields —
 * fixed-shape body copy — and the four questions are a post type, because they can
 * grow without a developer. The heading, the 01/02 numerals and the section
 * structure stay in code under Herman's Rule.
 *
 * The markup contract is the prototype's (index.html:279-319): `.overview` >
 * `.overview-h`, two `.overview-lede`, two `.ov-point` (each `.ov-n` numeral, `strong`
 * title, `p` text), then `.overview-qa` with one `.ov-q` (h4 + p) per question.
 * css/cas-home.css already ships every rule for it — measured, zero CSS work.
 *
 * The sentence promising lifetime access is deliberately NOT ported: the prototype
 * comment (index.html:273-278) holds it under §7.4 until Crystal confirms the term.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
   The post type — the four (growable) self-answered questions
   ------------------------------------------------------------------ */

add_action(
	'init',
	function () {
		register_post_type(
			'cas_overview_qa',
			[
				'labels'        => [
					'name'          => 'Course overview questions',
					'singular_name' => 'Course overview question',
					'add_new_item'  => 'Add question',
					'edit_item'     => 'Edit question',
				],
				'public'        => false,
				'show_ui'       => true,
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-format-chat',
				'menu_position' => 25, // the ladder: 21 Artwork, 22 Student work, 23 Free videos, 24 About photos, 26 FAQ, 27 Testimonials — 25 is free.
				'supports'      => [ 'title', 'editor', 'page-attributes' ],
				'has_archive'   => false,
			]
		);
	}
);

/**
 * The questions, ordered as she sets them.
 *
 * @return WP_Post[]
 */
function cas_overview_questions() {
	$posts = get_posts(
		[
			'post_type'      => 'cas_overview_qa',
			'post_status'    => 'publish',
			'numberposts'    => -1,
			'orderby'        => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
			'no_found_rows'  => true,
		]
	);

	return $posts;
}

/* ------------------------------------------------------------------
   Customizer — the six fixed-shape prose fields
   ------------------------------------------------------------------ */

/**
 * Her copy, verbatim from the prototype (which is the authoritative source), less
 * the lifetime-access clause held under §7.4.
 */

function cas_overview_lede_1_default() {
	return 'Designed for both beginners and experienced painters, this self-paced video curriculum guides you through colour mixing, essential tools, and refined techniques. It offers a comprehensive, step-by-step framework built on my years of experience as a student, artist, and instructor.';
}

function cas_overview_lede_2_default() {
	return 'Inside the studio, you will explore a single subject from multiple angles and perspectives, mastering every stage of the painting process &mdash; from initial sketch to final touch.';
}

function cas_overview_principle_default( $n, $part ) {
	$copy = [
		1 => [
			'title' => 'Step-by-step curriculum',
			'text'  => 'Learn comfortably at your own pace.',
		],
		2 => [
			'title' => 'Flexible stage-by-stage steps',
			'text'  => 'Break complex paintings into approachable, manageable phases.',
		],
	];

	return $copy[ $n ][ $part ] ?? '';
}

/**
 * Registered at priority 14 with `panel => cas_pages`, slotting between
 * `Home - Shop all courses` (13) and `Home - testimonials` (15) — the order the
 * sections appear down the page, and the overview is the tail of the Shop block.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_course_overview',
			[
				'title'       => 'Home — Course overview',
				'panel'       => 'cas_pages',
				'priority'    => 14,
				'description' => 'The "Course overview" block below the Shop all courses rail. The heading, the 01/02 numerals and the layout are fixed; the two opening paragraphs, the two numbered points and the four questions are yours. The questions are a list under Dashboard → Course overview questions, so you can add a fifth whenever you like.',
			]
		);

		$fields = [
			'cas_home_overview_lede_1'      => [ 'label' => 'Opening paragraph 1', 'default' => cas_overview_lede_1_default() ],
			'cas_home_overview_lede_2'      => [ 'label' => 'Opening paragraph 2', 'default' => cas_overview_lede_2_default() ],
			'cas_home_overview_point_1_t'   => [ 'label' => 'Point 1 — title', 'default' => cas_overview_principle_default( 1, 'title' ) ],
			'cas_home_overview_point_1_b'   => [ 'label' => 'Point 1 — text', 'default' => cas_overview_principle_default( 1, 'text' ) ],
			'cas_home_overview_point_2_t'   => [ 'label' => 'Point 2 — title', 'default' => cas_overview_principle_default( 2, 'title' ) ],
			'cas_home_overview_point_2_b'   => [ 'label' => 'Point 2 — text', 'default' => cas_overview_principle_default( 2, 'text' ) ],
		];

		foreach ( $fields as $id => $spec ) {
			$wp_customize->add_setting(
				$id,
				[
					'default'           => $spec['default'],
					'sanitize_callback' => 'sanitize_textarea_field',
					'transport'         => 'refresh',
				]
			);

			$wp_customize->add_control(
				$id,
				[
					'label'   => $spec['label'],
					'section' => 'cas_page_home_course_overview',
					'type'    => 'textarea',
				]
			);
		}
	},
	10
);

/* ------------------------------------------------------------------
   The block
   ------------------------------------------------------------------ */

/**
 * The block. Called by cas-home-courses.php inside its own `.pg-wrap`, so it
 * renders as the tail of the Shop all courses section exactly as the prototype
 * places it — the course cards stay above, her overview below.
 *
 * @return string
 */
function cas_render_home_course_overview() {
	$lede_1  = trim( (string) get_theme_mod( 'cas_home_overview_lede_1', cas_overview_lede_1_default() ) );
	$lede_2  = trim( (string) get_theme_mod( 'cas_home_overview_lede_2', cas_overview_lede_2_default() ) );
	$p1t     = trim( (string) get_theme_mod( 'cas_home_overview_point_1_t', cas_overview_principle_default( 1, 'title' ) ) );
	$p1b     = trim( (string) get_theme_mod( 'cas_home_overview_point_1_b', cas_overview_principle_default( 1, 'text' ) ) );
	$p2t     = trim( (string) get_theme_mod( 'cas_home_overview_point_2_t', cas_overview_principle_default( 2, 'title' ) ) );
	$p2b     = trim( (string) get_theme_mod( 'cas_home_overview_point_2_b', cas_overview_principle_default( 2, 'text' ) ) );
	$qa      = cas_overview_questions();

	/* With every field emptied and no questions published, a heading over nothing
	   is the one thing the build rules forbid. */
	if ( '' === $lede_1 && '' === $lede_2 && '' === $p1t && '' === $p2t && ! $qa ) {
		return '';
	}

	ob_start();
	?>
	<div class="overview">
		<h3 class="overview-h">Course overview</h3>
		<?php if ( '' !== $lede_1 ) : ?>
			<p class="overview-lede"><?php echo esc_html( $lede_1 ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $lede_2 ) : ?>
			<p class="overview-lede"><?php echo esc_html( $lede_2 ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $p1t || '' !== $p2t ) : ?>
			<div class="overview-points">
				<?php if ( '' !== $p1t ) : ?>
					<div class="ov-point">
						<span class="ov-n">01</span>
						<strong><?php echo esc_html( $p1t ); ?></strong>
						<?php if ( '' !== $p1b ) : ?>
							<p><?php echo esc_html( $p1b ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $p2t ) : ?>
					<div class="ov-point">
						<span class="ov-n">02</span>
						<strong><?php echo esc_html( $p2t ); ?></strong>
						<?php if ( '' !== $p2b ) : ?>
							<p><?php echo esc_html( $p2b ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $qa ) : ?>
			<div class="overview-qa">
				<?php foreach ( $qa as $q ) : ?>
					<div class="ov-q">
						<h4><?php echo esc_html( get_the_title( $q ) ); ?></h4>
						<p><?php echo wp_kses_post( $q->post_content ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
}
