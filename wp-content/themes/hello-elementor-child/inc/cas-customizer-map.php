<?php
/**
 * Organises the Customizer the way Crystal thinks about the site: by page, plus one
 * group for the things that appear on every page.
 *
 * The default arrangement grouped fields by what kind of content they were — brand
 * assets here, hero there — which is a developer's mental model, not hers. Finding the
 * hero meant knowing it counts as "Homepage hero" rather than looking under Home.
 *
 * This runs at a late priority so the sections it moves are already registered; it does
 * not create them, it re-parents them.
 *
 * The one thing it cannot fix: FAQ entries, testimonials and artworks are lists that
 * grow, and the Customizer has no sane UI for a growing list, so they stay in the
 * dashboard sidebar as post types. Rather than leave that split undocumented, each page
 * panel carries a pointer saying where those are edited — so this screen is the map
 * even where it is not the editor.
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_panel(
			'cas_sitewide',
			[
				'title'       => 'Site-wide',
				'description' => 'Things that appear on every page.',
				'priority'    => 20,
			]
		);

		$wp_customize->add_panel(
			'cas_pages',
			[
				'title'       => 'Pages',
				'description' => 'Settings for one page at a time.',
				'priority'    => 21,
			]
		);

		$moves = [
			'cas_brand'   => [ 'cas_sitewide', 'Logo & signature', 10 ],
			'cas_contact' => [ 'cas_sitewide', 'Contact & social links', 20 ],
			'cas_hero'    => [ 'cas_pages', 'Home', 10 ],
		];

		foreach ( $moves as $id => $spec ) {
			$section = $wp_customize->get_section( $id );

			if ( ! $section ) {
				continue;
			}

			list( $section->panel, $section->title, $section->priority ) = $spec;
		}

		/*
		 * The homepage is assembled from six modules, each of which picked a free
		 * priority when it was written, so read down the panel they do not match the
		 * order the blocks appear on the page. Only the priority is set here; every
		 * module keeps its own title, description and controls. The Art gallery card
		 * sits beside Shop all courses because on the page it is the second column of
		 * that row, not a section of its own.
		 */
		$order = [
			'cas_page_home_free_videos' => 11,
			'cas_page_home_about'       => 12,
			'cas_page_home_courses'     => 13,
			'cas_page_home_gallery'     => 14,
			'cas_page_home_blog'        => 15,
		];

		foreach ( $order as $id => $priority ) {
			$section = $wp_customize->get_section( $id );

			if ( $section ) {
				$section->priority = $priority;
			}
		}

		/*
		 * Pages whose content is a post-type list rather than a set of fields. The
		 * section exists purely so the page appears in this map at all; its description
		 * says where the editing actually happens.
		 */
		$pointers = [
			'cas_page_faq'       => [ 'FAQ', 20, 'Questions and answers are edited under <strong>Dashboard &rarr; FAQ</strong>. Each entry is one question: the title is the question, the body is the answer. The order comes from the <strong>Order</strong> box under Page Attributes &mdash; lower numbers first.' ],
			'cas_page_gallery'   => [ 'Gallery', 30, 'Artworks are edited under <strong>Dashboard &rarr; Artworks</strong>. Each artwork carries an image, name and description. Under <strong>Sale details</strong>, edit the price, Etsy link, stock status, medium, Size and optional Type. The <strong>Order</strong> box is under Page Attributes.' ],
			'cas_page_about'     => [ 'About', 40, 'The About page is fixed for this round. Its text and images are not editable from here &mdash; ask Herman to change anything on it.' ],
			'cas_page_courses'   => [ 'Courses', 50, 'Courses, lessons, prices and videos are all edited under <strong>Dashboard &rarr; Tutor LMS</strong>, not here.' ],
			'cas_page_showcase'  => [ 'Showcase', 60, 'Student work is edited under <strong>Dashboard &rarr; Student work</strong>. Each piece is one photograph, with the student&rsquo;s name and the medium beside it. Everything else on that page is part of its design. The wall runs by the <strong>Order</strong> number inside each piece, in its Page Attributes box &mdash; lowest first, and anything left at 0 sits at the front, newest first. There is no drag-and-drop.' ],
			'cas_page_home_more' => [ 'Home &mdash; testimonials', 16, 'Testimonials are edited under <strong>Dashboard &rarr; Testimonials</strong>. Write the comment, then pick which member said it; their name and picture come from their own account.' ],
		];

		foreach ( $pointers as $id => $spec ) {
			$wp_customize->add_section(
				$id,
				[
					'title'       => $spec[0],
					'panel'       => 'cas_pages',
					'priority'    => $spec[1],
					'description' => $spec[2],
				]
			);
		}
	},
	100
);

/**
 * A section with no controls is hidden by WordPress, so the four pointer sections — the
 * ones whose only job is to say "this page is edited over there" — were invisible to the
 * person they were written for. They carry a description and nothing else by design,
 * because the content they point at lives in a post type rather than a field.
 *
 * Each gets one read-only control holding that description, which is the smallest thing
 * that makes the section render. Priority 999 so it sees every section the other modules
 * have already added, and it skips any section that already has a real control.
 *
 * The class is declared INSIDE the callback: WP_Customize_Control is not loaded when this
 * file is included, so a top-level `extends` fatals before the customizer ever runs.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		if ( ! class_exists( 'CAS_Customize_Note_Control' ) ) {
			class CAS_Customize_Note_Control extends WP_Customize_Control {
				public $type = 'cas_note';

				public function render_content() {
					if ( $this->description ) {
						printf( '<p class="description" style="margin:0;">%s</p>', wp_kses_post( $this->description ) );
					}
				}
			}
		}

		$has_control = [];

		foreach ( $wp_customize->controls() as $control ) {
			$has_control[ $control->section ] = true;
		}

		foreach ( $wp_customize->sections() as $id => $section ) {
			if ( 0 !== strpos( $id, 'cas_' ) || isset( $has_control[ $id ] ) || ! $section->description ) {
				continue;
			}

			$wp_customize->add_control(
				new CAS_Customize_Note_Control(
					$wp_customize,
					$id . '_note',
					[
						'section'     => $id,
						'settings'    => [],
						'description' => $section->description,
					]
				)
			);
		}
	},
	999
);
