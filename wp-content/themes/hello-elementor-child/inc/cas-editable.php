<?php
/**
 * The things Crystal maintains herself.
 *
 * Three pieces of content were hardcoded on the reasoning that the direction document's
 * CMS rule is default-closed. That reading was too strict: the rule names body text,
 * images, links and prices as generally open, and these are all body text or links.
 * Section headings stay locked, which is what the rule actually protects.
 *
 * FAQ entries and testimonials are post types rather than Customizer fields because the
 * number of them changes. A Customizer panel with ten fixed slots cannot grow, and
 * fixed slots are how a CMS ends up with "FAQ 11" living in the footer.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Contact details and social links
 * ---------------------------------------------------------------------- */

function cas_social_defaults() {
	return [
		'instagram' => [ 'label' => 'Instagram URL', 'default' => 'https://www.instagram.com/crystalauart' ],
		'facebook'  => [ 'label' => 'Facebook URL', 'default' => 'https://www.facebook.com/crystal430' ],
		'youtube'   => [ 'label' => 'YouTube URL', 'default' => 'https://youtube.com/@crystallauart' ],
		'email'     => [ 'label' => 'Contact email', 'default' => 'crystalau430@gmail.com' ],
	];
}

/**
 * Overrides cas_social() from cas-footer.php. Same four keys, so the footer, the FAQ
 * contact block and the homepage follow-along strip all keep reading one source — they
 * simply read a stored value now instead of a constant.
 */
function cas_social() {
	$out = [];

	foreach ( cas_social_defaults() as $key => $spec ) {
		$out[ $key ] = get_theme_mod( 'cas_social_' . $key, $spec['default'] );
	}

	return $out;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_contact',
			[
				'title'       => 'Contact & social links',
				'description' => 'Used in the footer, on the FAQ page and in the Follow along strip. Changing one here changes all three.',
				'priority'    => 24,
			]
		);

		foreach ( cas_social_defaults() as $key => $spec ) {
			$id = 'cas_social_' . $key;

			$wp_customize->add_setting(
				$id,
				[
					'default'           => $spec['default'],
					'sanitize_callback' => 'email' === $key ? 'sanitize_email' : 'esc_url_raw',
					'transport'         => 'refresh',
				]
			);

			$wp_customize->add_control(
				$id,
				[
					'label'   => $spec['label'],
					'section' => 'cas_contact',
					'type'    => 'email' === $key ? 'email' : 'url',
				]
			);
		}
	}
);

/* -------------------------------------------------------------------------
 * FAQ entries
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	function () {
		register_post_type(
			'cas_faq',
			[
				'labels'       => [
					'name'          => 'FAQ',
					'singular_name' => 'FAQ entry',
					'add_new_item'  => 'Add FAQ entry',
					'edit_item'     => 'Edit FAQ entry',
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-editor-help',
				'menu_position' => 26,
				'supports'     => [ 'title', 'editor', 'page-attributes' ],
				'has_archive'  => false,
			]
		);

		register_post_type(
			'cas_testimonial',
			[
				'labels'       => [
					'name'          => 'Testimonials',
					'singular_name' => 'Testimonial',
					'add_new_item'  => 'Add testimonial',
					'edit_item'     => 'Edit testimonial',
				],
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-format-quote',
				'menu_position' => 27,
				'supports'     => [ 'editor', 'page-attributes' ],
				'has_archive'  => false,
			]
		);
	}
);

/**
 * The question is the post title and the answer is the body, so writing an FAQ entry is
 * writing a post. Order comes from page-attributes.
 */
function cas_faq_items() {
	$posts = get_posts(
		[
			'post_type'   => 'cas_faq',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => [ 'menu_order' => 'ASC', 'date' => 'ASC' ],
		]
	);

	$out = [];

	foreach ( $posts as $p ) {
		$out[] = [
			'q' => $p->post_title,
			'a' => apply_filters( 'the_content', $p->post_content ),
		];
	}

	return $out;
}

/* -------------------------------------------------------------------------
 * Testimonials
 * ---------------------------------------------------------------------- */

/**
 * Crystal types the quote and picks which member said it. The username and avatar are
 * then read from that member's own record rather than typed again, which is what the
 * prototype's note beside each card describes ("Profile picture & username — from the
 * member record"). It also means a member who changes their display name changes it
 * everywhere at once.
 */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'cas_testimonial_user',
			'Who said it',
			function ( $post ) {
				$selected = (int) get_post_meta( $post->ID, '_cas_user_id', true );
				$users    = get_users( [ 'orderby' => 'display_name', 'number' => 500 ] );

				wp_nonce_field( 'cas_testimonial_user', 'cas_testimonial_nonce' );
				echo '<p>The name and profile picture shown on the card come from this account.</p>';
				echo '<select name="cas_user_id" style="width:100%">';
				echo '<option value="0">— select a member —</option>';

				foreach ( $users as $u ) {
					printf(
						'<option value="%d" %s>%s (%s)</option>',
						$u->ID,
						selected( $selected, $u->ID, false ),
						esc_html( $u->display_name ),
						esc_html( $u->user_email )
					);
				}

				echo '</select>';
			},
			'cas_testimonial',
			'side'
		);
	}
);

add_action(
	'save_post_cas_testimonial',
	function ( $post_id ) {
		if ( ! isset( $_POST['cas_testimonial_nonce'] ) || ! wp_verify_nonce( $_POST['cas_testimonial_nonce'], 'cas_testimonial_user' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_cas_user_id', (int) ( $_POST['cas_user_id'] ?? 0 ) );
	}
);

function cas_testimonial_items() {
	$posts = get_posts(
		[
			'post_type'   => 'cas_testimonial',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => [ 'menu_order' => 'ASC', 'date' => 'DESC' ],
		]
	);

	$out = [];

	foreach ( $posts as $p ) {
		$uid  = (int) get_post_meta( $p->ID, '_cas_user_id', true );
		$user = $uid ? get_userdata( $uid ) : null;

		$out[] = [
			'quote'  => wp_strip_all_tags( $p->post_content ),
			'name'   => $user ? $user->display_name : '',
			'avatar' => $user ? get_avatar_url( $user->ID, [ 'size' => 96 ] ) : '',
		];
	}

	return $out;
}
