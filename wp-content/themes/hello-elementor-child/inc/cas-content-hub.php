<?php
/**
 * #150: one content hub card per public page, linking to fixed page forms and native content lists.
 * The hub itself adds no role and writes nothing. Administrators get manage_cas_content at runtime.
 *
 * Source of truth for what is editable: docs/EDITABILITY-REGISTER.md. Only fields she can edit today are listed.
 */

defined( 'ABSPATH' ) || exit;

const CAS_CONTENT_HUB_CAP = 'manage_cas_content';

/** Title case for project-owned UI labels; sentences and user content are not passed here. */
function cas_admin_title( $text ) {
	$minor = array( 'a', 'an', 'and', 'as', 'at', 'by', 'for', 'from', 'in', 'of', 'on', 'or', 'the', 'to', 'with', 'under', 'over', 'after', 'before', 'between', 'through', 'into', 'onto', 'within', 'without', 'against', 'around', 'near', 'off', 'out', 'per', 'until', 'upon', 'via' );
	$brands = array( 'youtube' => 'YouTube', 'wordpress' => 'WordPress', 'etsy' => 'Etsy', 'bunny' => 'Bunny' );
	$index = 0;
	return preg_replace_callback( '/[A-Za-z]+(?:[-\x27][A-Za-z]+)*/', static function ( $m ) use ( $minor, $brands, &$index ) {
		$word = $m[0]; $lower = strtolower( $word ); $first = $index++ === 0;
		if ( ! $first && in_array( $lower, $minor, true ) ) { return $lower; }
		if ( isset( $brands[ $lower ] ) ) { return $brands[ $lower ]; }
		if ( strlen( $word ) > 1 && ctype_upper( $word ) ) { return $word; }
		return ucwords( $lower, '-' );
	}, html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
}

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
				$item( 'Choose the Homepage Gallery Image', admin_url( 'admin.php?page=cas-content-home-page#home-gallery-image' ), CAS_CONTENT_HUB_CAP ),
				cas_content_hub_list_item( 'Blog posts shown here: the six newest with a picture', 'post' ),
				cas_content_hub_list_item( 'Student Comments: Order, Member and Text (Display Limit in Edit Home)', 'cas_testimonial' ),
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
				$item( 'Manage Media', admin_url( 'edit-tags.php?taxonomy=cas_artwork_medium&post_type=cas_artwork' ), 'manage_categories' ),
				$item( 'Manage Artwork Types', admin_url( 'edit-tags.php?taxonomy=cas_artwork_type&post_type=cas_artwork' ), 'manage_categories' ),
			),
			'muted' => 'Grid layout: ask the webmaster.',
		),
		array(
			'name'  => 'Showcase',
			'view'  => cas_content_hub_page_url( 'showcase' ),
			'items' => array(
				cas_content_hub_list_item( 'Student work: photo, title, student credit, medium and order', 'cas_showcase' ),
				$item( 'Manage Media', admin_url( 'edit-tags.php?taxonomy=cas_showcase_medium&post_type=cas_showcase' ), 'manage_categories' ),
			),
			'muted' => 'Wall layout and filters: ask the webmaster.',
		),
		array(
			'name'  => 'Blog',
			'view'  => cas_content_hub_page_url( 'blog' ),
			'items' => array(
				cas_content_hub_list_item( 'Posts: title, text, pictures, topic and date', 'post' ),
				$item( 'Introduction paragraph under the blog heading', admin_url( 'admin.php?page=cas-content-blog' ), CAS_CONTENT_HUB_CAP ),
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
			),
			'muted' => 'Layout and the honours table: ask the webmaster.',
		),
		array(
			'name'  => 'Free videos',
			'view'  => cas_content_hub_page_url( 'free-videos' ),
			'items' => array(
				cas_content_hub_list_item( 'Films: title, cover, blurb, section, video link and order', 'cas_free_video' ),
				$item( 'Opening line and the three section introductions', admin_url( 'admin.php?page=cas-content-free-videos' ), CAS_CONTENT_HUB_CAP ),
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
			'name'  => 'Site-Wide Edit',
			'view'  => null,
			'items' => array(
				$item( 'Site-Wide Edit: contact, social links, logo and signatures', admin_url( 'admin.php?page=cas-content-site-details' ), CAS_CONTENT_HUB_CAP ),
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
		__( 'Content Hub', 'hello-elementor-child' ),
		__( 'Content Hub', 'hello-elementor-child' ),
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
		'Blog' => array( 'Manage your articles, topics and resources.', 'Manage Posts' ),
		'About' => array( 'Your story, quotation, essays and portrait.', 'Edit About' ),
		'Free videos' => array( 'Page introductions and your public teaching films.', 'Manage Films' ),
		'FAQ' => array( 'The questions and answers your visitors read.', 'Manage questions' ),
		'Site-Wide Edit' => array( 'Shared contact details, social links, logo and signatures.', 'Site-Wide Edit' ),
	);
	echo '<div class="wrap cas-content-ui cas-content-hub"><header class="cas-editor-header"><h1>' . esc_html__( 'Content Hub', 'hello-elementor-child' ) . '</h1>';
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
		echo '<section class="cas-hub-card cas-leather"><h2>' . esc_html( cas_admin_title( $card['name'] ) ) . '</h2><p class="cas-hub-description">' . esc_html( $copy[0] ) . '</p>';
		if ( $primary ) {
			echo '<a class="button button-primary cas-hub-primary" href="' . esc_url( $primary['url'] ) . '">' . esc_html( cas_admin_title( $copy[1] ) ) . '<span aria-hidden="true"> &rarr;</span></a>';
		} else {
			echo '<p>No editing controls are available for this account.</p>';
		}
		if ( $card['view'] ) {
			echo '<a class="cas-hub-preview" href="' . esc_url( $card['view'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( 'View ' . $card['name'] . ' (opens in a new tab)' ) . '">View page <span aria-hidden="true">&nearr;</span></a>';
		} elseif ( null !== $card['view'] ) {
			echo '<p class="description">This page is not set up yet.</p>';
		}
		if ( $unique ) {
			echo '<details class="cas-hub-more"><summary>More Editing Tasks</summary><ul>';
			foreach ( $unique as $line ) { echo '<li><a href="' . esc_url( $line['url'] ) . '">' . esc_html( cas_admin_title( $line['text'] ) ) . '</a></li>'; }
			echo '</ul></details>';
		}

		if ( ! empty( $card['muted'] ) ) {
			echo '<p class="description">' . esc_html( $card['muted'] ) . '</p>';
		}

		echo '</section>';
	}

	echo '</div></div>';
}

// Apply the same casing to labels owned by this theme, without rewriting WordPress or Tutor translations.
add_filter( 'register_post_type_args', function ( $args, $type ) {
	if ( str_starts_with( $type, 'cas_' ) && isset( $args['labels'] ) ) {
		foreach ( $args['labels'] as $key => $label ) { if ( is_string( $label ) ) { $args['labels'][ $key ] = cas_admin_title( $label ); } }
	}
	return $args;
}, 20, 2 );
add_action( 'add_meta_boxes', function () {
	global $wp_meta_boxes;
	foreach ( $wp_meta_boxes ?? array() as $screen => $contexts ) {
		foreach ( $contexts as $context => $priorities ) {
			foreach ( $priorities as $priority => $boxes ) {
				foreach ( $boxes as $id => $box ) {
					if ( str_starts_with( (string) $id, 'cas' ) && is_array( $box ) && is_string( $box['title'] ) ) { $wp_meta_boxes[ $screen ][ $context ][ $priority ][ $id ]['title'] = cas_admin_title( $box['title'] ); }
				}
			}
		}
	}
}, 100 );

add_action( 'admin_menu', function () {
	global $menu, $submenu;
	foreach ( $menu ?? array() as $key => $item ) {
		if ( isset( $item[2] ) && ( str_starts_with( $item[2], 'cas-' ) || str_starts_with( $item[2], 'edit.php?post_type=cas_' ) ) ) { $menu[ $key ][0] = cas_admin_title( $item[0] ); }
	}
	foreach ( $submenu ?? array() as $parent => $items ) {
		foreach ( $items as $key => $item ) {
			if ( str_starts_with( $parent, 'cas-' ) || ( isset( $item[2] ) && str_starts_with( $item[2], 'cas-' ) ) ) { $submenu[ $parent ][ $key ][0] = cas_admin_title( $item[0] ); }
		}
	}
}, 1001 );
