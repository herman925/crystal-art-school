<?php
/**
 * #150 option 1: one content hub card per public page. Links only: it changes no editor, adds no role,
 * and writes nothing to the database. Administrators get manage_cas_content at runtime.
 *
 * Source of truth for what is editable: docs/EDITABILITY-REGISTER.md. Only fields she can edit today are listed.
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
 * A Customizer link that opens the preview on the page the section belongs to.
 *
 * @param string $section  Real Customizer section id (registered in the theme's inc/ files).
 * @param string $page_url Public URL of the page the preview should show.
 *
 * @return string
 */
function cas_content_hub_customize( $section, $page_url ) {
	return admin_url( 'customize.php?autofocus[section]=' . rawurlencode( $section ) . '&url=' . rawurlencode( $page_url ) );
}

/**
 * A plain line with the list screen behind it. The capability is the post type's own edit capability.
 *
 * @param string $text      What she can change, in plain English.
 * @param string $post_type Post type slug.
 *
 * @return array|null Null when the post type is not registered, so the line is not shown.
 */
function cas_content_hub_list_item( $text, $post_type ) {
	$object = get_post_type_object( $post_type );

	if ( ! $object ) {
		return null;
	}

	return array(
		'text' => $text,
		'url'  => admin_url( 'edit.php?post_type=' . $post_type ),
		'cap'  => $object->cap->edit_posts,
	);
}

/**
 * The public URL of a page by slug, or '' when the page has not been created.
 *
 * @param string $slug Page slug.
 *
 * @return string
 */
function cas_content_hub_page_url( $slug ) {
	$page = get_page_by_path( $slug );

	return $page ? get_permalink( $page ) : '';
}

/**
 * The public URL of a page by slug, or the home page when the page has not been created.
 *
 * @param string $slug Page slug.
 * @param string $home Home URL.
 *
 * @return string
 */
function cas_content_hub_page_or_home( $slug, $home ) {
	$url = cas_content_hub_page_url( $slug );

	return $url ? $url : $home;
}

/**
 * The public card for each page, with only the things she can edit today.
 *
 * @return array
 */
function cas_content_hub_cards() {
	$home    = home_url( '/' );
	$courses = function_exists( 'tutor' ) ? get_post_type_archive_link( tutor()->course_post_type ) : '';
	$courses = $courses ? $courses : $home;

	$customize = static function ( $text, $section, $page_url ) {
		return array(
			'text' => $text,
			'url'  => cas_content_hub_customize( $section, $page_url ),
			'cap'  => 'customize',
		);
	};

	$item = static function ( $text, $url, $cap ) {
		return array(
			'text' => $text,
			'url'  => $url,
			'cap'  => $cap,
		);
	};

	$cards = array(
		array(
			'name'  => 'Home',
			'view'  => $home,
			'items' => array(
				$customize( 'Homepage banner: three slides, each with a title, a description and two photos', 'cas_hero', $home ),
				$customize( 'Free videos card: its photo and hover text', 'cas_page_home_free_videos', $home ),
				$customize( 'About Crystal card: its photo', 'cas_page_home_about', $home ),
				$customize( 'Shop all courses introduction', 'cas_page_home_courses', $home ),
				$customize( 'Course overview: two opening paragraphs and two principles', 'cas_page_home_course_overview', $home ),
				cas_content_hub_list_item( 'Course overview questions', 'cas_overview_qa' ),
				$customize( 'Art gallery card: its hover line', 'cas_page_home_gallery', $home ),
				cas_content_hub_list_item( 'Art gallery card picture: the first artwork in the Artwork list', 'cas_artwork' ),
				$customize( 'Blog and tools: the TOOLS background photo', 'cas_page_home_blog', $home ),
				cas_content_hub_list_item( 'Blog posts shown here: the six newest with a picture', 'post' ),
				cas_content_hub_list_item( 'Student comments shown here: which three, their member and text', 'cas_testimonial' ),
				cas_content_hub_list_item( 'Course rail: the courses shown here', 'courses' ),
			),
			'muted' => 'Layout and the order of sections: ask Herman.',
		),
		array(
			'name'  => 'Courses',
			'view'  => $courses,
			'items' => array(
				$customize( 'Introduction paragraph under the banner', 'cas_page_courses', $courses ),
				cas_content_hub_list_item( 'Course titles, covers, prices, levels, categories and order', 'courses' ),
				cas_content_hub_list_item( 'Each course page: description, what you will learn, who it is for, what comes with it, and the syllabus', 'courses' ),
				cas_content_hub_list_item( 'Course photo strip: five photos per course', 'courses' ),
				cas_content_hub_list_item( 'Lessons: titles, videos and one-line subtitles', 'lesson' ),
				$item( 'Currency and cart page (Tutor LMS settings)', admin_url( 'admin.php?page=tutor_settings' ), 'manage_options' ),
			),
			'muted' => 'Course page layout: ask Herman.',
		),
		array(
			'name'  => 'Gallery',
			'view'  => cas_content_hub_page_url( 'gallery' ),
			'items' => array(
				cas_content_hub_list_item( 'Artworks: image, name, description, price, Etsy link, stock and order', 'cas_artwork' ),
			),
			'muted' => 'Grid layout: ask Herman.',
		),
		array(
			'name'  => 'Showcase',
			'view'  => cas_content_hub_page_url( 'showcase' ),
			'items' => array(
				cas_content_hub_list_item( 'Student work: photo, title, student credit, medium and order', 'cas_showcase' ),
			),
			'muted' => 'Wall layout and filters: ask Herman.',
		),
		array(
			'name'  => 'Blog',
			'view'  => cas_content_hub_page_url( 'blog' ),
			'items' => array(
				$customize( 'Introduction paragraph under the blog heading', 'cas_page_blog', cas_content_hub_page_or_home( 'blog', $home ) ),
				cas_content_hub_list_item( 'Posts: title, text, pictures, topic, date and developer\'s note', 'post' ),
				$item( 'Topics (categories)', admin_url( 'edit-tags.php?taxonomy=category' ), 'manage_categories' ),
			),
			'muted' => 'Article layout and colours: ask Herman.',
		),
		array(
			'name'  => 'About',
			'view'  => cas_content_hub_page_url( 'about' ),
			'items' => array(
				$customize( 'Opening paragraph, quotation and the three essays', 'cas_page_about', cas_content_hub_page_or_home( 'about', $home ) ),
				$customize( 'Portrait photograph', 'cas_page_about', cas_content_hub_page_or_home( 'about', $home ) ),
				cas_content_hub_list_item( 'Carousel photos: live drawing, exhibitions and Artful Living', 'cas_about_photo' ),
				$customize( 'Contact email and social handles shown on the page', 'cas_contact', cas_content_hub_page_or_home( 'about', $home ) ),
			),
			'muted' => 'Layout and the honours table: ask Herman.',
		),
		array(
			'name'  => 'Free videos',
			'view'  => cas_content_hub_page_url( 'free-videos' ),
			'items' => array(
				$customize( 'Opening line and the three section introductions', 'cas_page_free_videos', cas_content_hub_page_or_home( 'free-videos', $home ) ),
				$customize( 'Courses link inside the demos introduction', 'cas_page_free_videos', cas_content_hub_page_or_home( 'free-videos', $home ) ),
				cas_content_hub_list_item( 'Films: title, cover, blurb, section, video link and order', 'cas_free_video' ),
			),
			'muted' => 'Section layout: ask Herman.',
		),
		array(
			'name'  => 'FAQ',
			'view'  => cas_content_hub_page_url( 'faq' ),
			'items' => array(
				cas_content_hub_list_item( 'Questions, answers and their order', 'cas_faq' ),
			),
			'muted' => 'Page layout: ask Herman.',
		),
		array(
			'name'  => 'Every page',
			'view'  => null,
			'items' => array(
				$customize( 'Logo, hero signature and footer signature', 'cas_brand', $home ),
				$customize( 'Contact email and social links (buttons and footer)', 'cas_contact', $home ),
				$item( 'Top menu labels and order (Appearance > Menus)', admin_url( 'nav-menus.php' ), 'edit_theme_options' ),
			),
			'muted' => 'Footer layout and colours: ask Herman.',
		),
	);

	foreach ( $cards as &$card ) {
		$card['items'] = array_values( array_filter( $card['items'] ) );
	}
	unset( $card );

	return $cards;
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
	echo '<p>' . esc_html__( 'One card per page. Each line opens the exact place where that thing is changed.', 'hello-elementor-child' ) . '</p>';

	foreach ( cas_content_hub_cards() as $card ) {
		echo '<div class="cas-hub-card"><h2>' . esc_html( $card['name'] ) . '</h2>';

		if ( null === $card['view'] ) {
			// No public page behind this card (the site-wide settings), so there is nothing to view.
		} elseif ( $card['view'] ) {
			echo '<p><a class="button" href="' . esc_url( $card['view'] ) . '">' . esc_html__( 'View page', 'hello-elementor-child' ) . '</a></p>';
		} else {
			echo '<p class="description">' . esc_html__( 'This page is not set up yet.', 'hello-elementor-child' ) . '</p>';
		}

		$visible = array_filter(
			$card['items'],
			static function ( $line ) {
				return current_user_can( $line['cap'] );
			}
		);

		if ( $visible ) {
			echo '<ul>';
			foreach ( $visible as $line ) {
				echo '<li><a href="' . esc_url( $line['url'] ) . '">' . esc_html( $line['text'] ) . '</a></li>';
			}
			echo '</ul>';
		}

		if ( ! empty( $card['muted'] ) ) {
			echo '<p class="description">' . esc_html( $card['muted'] ) . '</p>';
		}

		echo '</div>';
	}

	echo '</div>';
}
