<?php
/**
 * #150 slice 1: one task-based content hub. It only links to the existing editors.
 * It changes no editor, adds no role, and writes nothing to the database. Administrators get its capability at runtime.
 */

defined( 'ABSPATH' ) || exit;

const CAS_CONTENT_HUB_CAP = 'manage_cas_content';

/**
 * Administrators get the hub capability at runtime. Nothing is written to the roles in the database,
 * so a Tutor update that re-runs its role installer cannot remove it, and nothing needs undoing.
 *
 * @param array $allcaps Every capability the user has.
 *
 * @return array
 */
function cas_content_hub_user_has_cap( $allcaps ) {
	if ( ! empty( $allcaps['manage_options'] ) ) {
		$allcaps[ CAS_CONTENT_HUB_CAP ] = true;
	}

	return $allcaps;
}
add_filter( 'user_has_cap', 'cas_content_hub_user_has_cap' );

/**
 * A link is listed only when the viewer can use its editor.
 *
 * @param string $label Link text.
 * @param string $url   Admin URL of the existing editor.
 * @param string $cap   Capability the editor needs.
 *
 * @return array
 */
function cas_content_hub_link( $label, $url, $cap ) {
	return array(
		'label' => $label,
		'url'   => $url,
		'cap'   => $cap,
	);
}

/**
 * The five task groups from the 2026-10-19 interview. Each link goes to an editor that already exists.
 *
 * @return array
 */
function cas_content_hub_groups() {
	$groups = array(
		'Website pages'       => array(
			cas_content_hub_link( 'Pages', admin_url( 'edit.php?post_type=page' ), 'edit_pages' ),
			cas_content_hub_link( 'Homepage and About text (Customizer)', admin_url( 'customize.php' ), 'customize' ),
		),
		'Courses & lessons'   => array(),
		'Artwork & Showcase'  => array(
			cas_content_hub_link( 'Artwork', admin_url( 'edit.php?post_type=cas_artwork' ), 'edit_posts' ),
			cas_content_hub_link( 'Student work showcase', admin_url( 'edit.php?post_type=cas_showcase' ), 'edit_posts' ),
		),
		'Posts & resources'   => array(
			cas_content_hub_link( 'Blog posts', admin_url( 'edit.php' ), 'edit_posts' ),
			cas_content_hub_link( 'Free videos', admin_url( 'edit.php?post_type=cas_free_video' ), 'edit_posts' ),
			cas_content_hub_link( 'Questions and answers', admin_url( 'edit.php?post_type=cas_faq' ), 'edit_posts' ),
			cas_content_hub_link( 'Student comments', admin_url( 'edit.php?post_type=cas_testimonial' ), 'edit_posts' ),
		),
		'Site details'        => array(
			cas_content_hub_link( 'Contact and social links (Customizer)', admin_url( 'customize.php' ), 'customize' ),
			cas_content_hub_link( 'Menus', admin_url( 'nav-menus.php' ), 'edit_theme_options' ),
		),
	);

	if ( function_exists( 'tutor' ) ) {
		$course_type = tutor()->course_post_type;
		$groups['Courses & lessons'][] = cas_content_hub_link( 'Courses', admin_url( 'edit.php?post_type=' . $course_type ), 'edit_posts' );
	}

	return $groups;
}

function cas_content_hub_menu() {
	add_menu_page(
		__( 'Content hub', 'hello-elementor-child' ),
		__( 'Content hub', 'hello-elementor-child' ),
		CAS_CONTENT_HUB_CAP,
		'cas-content-hub',
		'cas_content_hub_render',
		'dashicons-admin-generic',
		3
	);
}
add_action( 'admin_menu', 'cas_content_hub_menu' );

function cas_content_hub_render() {
	if ( ! current_user_can( CAS_CONTENT_HUB_CAP ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'hello-elementor-child' ), 403 );
	}

	echo '<div class="wrap cas-content-hub"><h1>' . esc_html__( 'Content hub', 'hello-elementor-child' ) . '</h1>';

	foreach ( cas_content_hub_groups() as $title => $links ) {
		$visible = array_filter(
			$links,
			static function ( $link ) {
				return current_user_can( $link['cap'] );
			}
		);

		if ( ! $visible ) {
			continue;
		}

		echo '<h2>' . esc_html( $title ) . '</h2><ul>';
		foreach ( $visible as $link ) {
			echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
		}
		echo '</ul>';
	}

	echo '</div>';
}
