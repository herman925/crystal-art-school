<?php
/**
 * #150: one content hub card per public page, linking to fixed page forms and native content lists.
 * The hub itself adds no role and writes nothing. Administrators get manage_cas_content at runtime.
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
				$item( 'Edit Home: banner, page text and photographs', admin_url( 'admin.php?page=cas-content-home-page' ), CAS_CONTENT_HUB_CAP ),
				cas_content_hub_list_item( 'Course overview questions', 'cas_overview_qa' ),
				cas_content_hub_list_item( 'Art gallery card picture: the first artwork in the Artwork list', 'cas_artwork' ),
				cas_content_hub_list_item( 'Blog posts shown here: the six newest with a picture', 'post' ),
				cas_content_hub_list_item( 'Student comments shown here: which three, their member and text', 'cas_testimonial' ),
				cas_content_hub_list_item( 'Course rail: the courses shown here', 'courses' ),
			),
			'muted' => 'Layout and the order of sections: ask the webmaster.',
		),
		array(
			'name'  => 'Courses',
			'view'  => $courses,
			'items' => array(
				cas_content_hub_list_item( 'Course titles, covers, prices, levels, categories and order', 'courses' ),
				$customize( 'Introduction paragraph under the banner', 'cas_page_courses', $courses ),
				cas_content_hub_list_item( 'Each course page: description, what you will learn, who it is for, what comes with it, and the syllabus', 'courses' ),
				cas_content_hub_list_item( 'Course photo strip: five photos per course', 'courses' ),
				cas_content_hub_list_item( 'Lessons: titles, videos and descriptions', 'lesson' ),
			),
			'muted' => 'Course page layout, and currency, checkout and payments: ask the webmaster.',
		),
		array(
			'name'  => 'Gallery',
			'view'  => cas_content_hub_page_url( 'gallery' ),
			'items' => array(
				cas_content_hub_list_item( 'Artworks: image, name and description; Sale details: price, Etsy link, stock, medium, Size and optional Type; Page Attributes: Order', 'cas_artwork' ),
			),
			'muted' => 'Grid layout: ask the webmaster.',
		),
		array(
			'name'  => 'Showcase',
			'view'  => cas_content_hub_page_url( 'showcase' ),
			'items' => array(
				cas_content_hub_list_item( 'Student work: photo, title, student credit, medium and order', 'cas_showcase' ),
			),
			'muted' => 'Wall layout and filters: ask the webmaster.',
		),
		array(
			'name'  => 'Blog',
			'view'  => cas_content_hub_page_url( 'blog' ),
			'items' => array(
				$item( 'Introduction paragraph under the blog heading', admin_url( 'admin.php?page=cas-content-blog' ), CAS_CONTENT_HUB_CAP ),
				cas_content_hub_list_item( 'Posts: title, text, pictures, topic and date', 'post' ),
				$item( 'Topics (categories)', admin_url( 'edit-tags.php?taxonomy=category' ), 'manage_categories' ),
			),
			'muted' => 'Article layout and colours: ask the webmaster.',
		),
		array(
			'name'  => 'About',
			'view'  => cas_content_hub_page_url( 'about' ),
			'items' => array(
				$item( 'Edit About: opening paragraph, quotation, essays and portrait', admin_url( 'admin.php?page=cas-content-about-page' ), CAS_CONTENT_HUB_CAP ),
				cas_content_hub_list_item( 'Carousel photos: live drawing, exhibitions and Artful Living', 'cas_about_photo' ),
				$item( 'Shared contact details and social links', admin_url( 'admin.php?page=cas-content-site-details' ), CAS_CONTENT_HUB_CAP ),
			),
			'muted' => 'Layout and the honours table: ask the webmaster.',
		),
		array(
			'name'  => 'Free videos',
			'view'  => cas_content_hub_page_url( 'free-videos' ),
			'items' => array(
				$item( 'Opening line and the three section introductions', admin_url( 'admin.php?page=cas-content-free-videos' ), CAS_CONTENT_HUB_CAP ),
				cas_content_hub_list_item( 'Films: title, cover, blurb, section, video link and order', 'cas_free_video' ),
			),
			'muted' => 'Section layout: ask the webmaster.',
		),
		array(
			'name'  => 'FAQ',
			'view'  => cas_content_hub_page_url( 'faq' ),
			'items' => array(
				cas_content_hub_list_item( 'Questions, answers and their order', 'cas_faq' ),
			),
			'muted' => 'Page layout: ask the webmaster.',
		),
		array(
			'name'  => 'Every page',
			'view'  => null,
			'items' => array(
				$item( 'Edit Site details: contact, social links, logo and signatures', admin_url( 'admin.php?page=cas-content-site-details' ), CAS_CONTENT_HUB_CAP ),
				$item( 'Top menu labels and order (Appearance > Menus)', admin_url( 'nav-menus.php' ), 'edit_theme_options' ),
			),
			'muted' => 'Footer layout and colours: ask the webmaster.',
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

	$presentation = array(
		'Home' => array( 'Banner, introductions and photographs for your front page.', 'Edit Home' ),
		'Courses' => array( 'Course content, lessons and course photographs.', 'Manage courses' ),
		'Gallery' => array( 'Your paintings, sale details and external Etsy links.', 'Manage artwork' ),
		'Showcase' => array( 'Student work, public credits and photographs.', 'Manage student work' ),
		'Blog' => array( 'Your journal introduction, articles and resources.', 'Edit introduction' ),
		'About' => array( 'Your story, quotation, essays and portrait.', 'Edit About' ),
		'Free videos' => array( 'Page introductions and your public teaching films.', 'Edit introductions' ),
		'FAQ' => array( 'The questions and answers your visitors read.', 'Manage questions' ),
		'Every page' => array( 'Shared contact details, social links, logo and signatures.', 'Edit Site details' ),
	);
	echo '<div class="wrap cas-content-ui cas-content-hub"><header class="cas-editor-header"><h1>' . esc_html__( 'Content hub', 'hello-elementor-child' ) . '</h1>';
	echo '<p>' . esc_html__( 'Choose a page to update its words, photographs and content.', 'hello-elementor-child' ) . '</p></header><div class="cas-hub-grid">';

	foreach ( cas_content_hub_cards() as $card ) {
		$visible = array_filter(
			$card['items'],
			static function ( $line ) {
				return current_user_can( $line['cap'] );
			}
		);
		$unique = array();
		foreach ( $visible as $line ) {
			if ( ! isset( $unique[ $line['url'] ] ) ) { $unique[ $line['url'] ] = $line; }
		}
		$primary = array_shift( $unique );
		$copy = $presentation[ $card['name'] ];
		echo '<section class="cas-hub-card cas-leather"><h2>' . esc_html( $card['name'] ) . '</h2><p class="cas-hub-description">' . esc_html( $copy[0] ) . '</p>';
		if ( $primary ) {
			echo '<a class="button button-primary cas-hub-primary" href="' . esc_url( $primary['url'] ) . '">' . esc_html( $copy[1] ) . '<span aria-hidden="true"> &rarr;</span></a>';
		} else {
			echo '<p>No editing controls are available for this account.</p>';
		}
		if ( $card['view'] ) {
			echo '<a class="cas-hub-preview" href="' . esc_url( $card['view'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( 'View ' . $card['name'] . ' (opens in a new tab)' ) . '">View page <span aria-hidden="true">&nearr;</span></a>';
		} elseif ( null !== $card['view'] ) {
			echo '<p class="description">This page is not set up yet.</p>';
		}
		if ( $unique ) {
			echo '<details class="cas-hub-more"><summary>More editing tasks</summary><ul>';
			foreach ( $unique as $line ) { echo '<li><a href="' . esc_url( $line['url'] ) . '">' . esc_html( $line['text'] ) . '</a></li>'; }
			echo '</ul></details>';
		}

		if ( ! empty( $card['muted'] ) ) {
			echo '<p class="description">' . esc_html( $card['muted'] ) . '</p>';
		}

		echo '</section>';
	}

	echo '</div></div>';
}
