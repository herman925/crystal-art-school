<?php

defined( 'ABSPATH' ) || exit;

/** Compose existing field groups; all validation completes before the shared single-option write. */
function cas_content_page_fields( $groups ) {
	$fields = array();
	foreach ( $groups as $group ) {
		$config = cas_content_prose_config( $group );
		$fields = array_merge( $fields, call_user_func( $config['fields'] ) );
	}
	return $fields;
}

function cas_content_page_clean( $groups, $input ) {
	$fields = cas_content_page_fields( $groups );
	if ( ! is_array( $input ) || count( $input ) !== count( $fields ) || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'cas_page_input', 'Submit exactly the fields on this editing page. Nothing has been saved.' );
	}
	$clean = array();
	foreach ( $groups as $group ) {
		$config = cas_content_prose_config( $group );
		$part = call_user_func( $config['clean'], array_intersect_key( $input, call_user_func( $config['fields'] ) ) );
		if ( is_wp_error( $part ) ) {
			return new WP_Error( 'cas_page_input', $part->get_error_message() );
		}
		$clean = array_merge( $clean, $part );
	}
	return $clean;
}

function cas_content_page_controls( $groups, $values ) {
	foreach ( $groups as $group ) {
		$config = cas_content_prose_config( $group );
		echo '<fieldset class="cas-editor-section" id="cas-section-' . esc_attr( $group ) . '"><legend>' . esc_html( cas_admin_title( $config['title'] ) ) . '</legend><p class="cas-section-help">' . esc_html( $config['empty'] ) . '</p>';
		call_user_func( $config['controls'], $values, 'cas_page_fields' );
		echo '</fieldset>';
	}
}

function cas_content_prose_config( $group ) {
	if ( ! is_string( $group ) ) {
		return null;
	}
	$combined = array(
		'home-page' => array(
			'title' => 'Home', 'slug' => 'cas-content-home-page', 'groups' => array( 'hero', 'home', 'home-images', 'home-display' ),
			'description' => 'Edit the three-slide banner, page text and photographs together. Repeating content such as courses, questions and artwork uses the lists below.',
		),
		'about-page' => array(
			'title' => 'About', 'slug' => 'cas-content-about-page', 'groups' => array( 'about', 'about-image' ),
			'description' => 'Edit the opening paragraph, quotation, essays and portrait together. Photograph strips use the separate list below. Contact details, logos and signatures are edited separately through Site-Wide Edit.',
		),
		'site-details' => array(
			'title' => 'Site-Wide Edit', 'slug' => 'cas-content-site-details', 'groups' => array( 'contact', 'brand-images' ),
			'description' => 'Edit the shared contact details, social links, logo and signatures together. Each change affects all pages that use it.',
		),
	);
	if ( isset( $combined[ $group ] ) ) {
		$config = $combined[ $group ];
		$groups = $config['groups'];
		return $config + array(
			'callback' => static function () use ( $group ) { cas_content_prose_page( $group ); },
			'fields' => static function () use ( $groups ) { return cas_content_page_fields( $groups ); },
			'clean' => static function ( $input ) use ( $groups ) { return cas_content_page_clean( $groups, $input ); },
			'controls' => static function ( $values ) use ( $groups ) { cas_content_page_controls( $groups, $values ); },
			'input' => 'cas_page_fields', 'snapshot' => 'cas_page_snapshot', 'nonce' => 'cas_content_' . $group . '_save',
			'error' => 'page', 'input_error' => 'cas_page_input',
			'empty' => 'Save applies all fields on this page together. Each section explains what happens when a field is cleared.',
		);
	}
	if ( 'home-gallery' === $group || 'home-display' === $group ) {
		$gallery = 'home-gallery' === $group;
		return array(
			'title' => $gallery ? 'Gallery Image' : 'Student Comment Limit',
			'fields' => $gallery ? 'cas_content_gallery_fields' : 'cas_content_display_fields',
			'clean' => $gallery ? 'cas_content_gallery_clean' : 'cas_content_display_clean',
			'controls' => $gallery ? '' : 'cas_content_display_controls',
			'nonce' => 'cas_content_' . $group . '_save', 'error' => $group, 'input_error' => $group . '_input',
			'empty' => 'Keep extra comments in the list; only the selected number is displayed on Home.',
		);
	}
	$pages = array(
		'about' => array(
			'title' => 'About text', 'slug' => 'cas-content-about', 'callback' => 'cas_content_about_page',
			'fields' => 'cas_content_about_fields', 'clean' => 'cas_content_about_clean', 'controls' => 'cas_content_about_controls',
			'input' => 'cas_about_fields', 'snapshot' => 'cas_about_snapshot', 'nonce' => 'cas_content_about_save',
			'error' => 'about', 'input_error' => 'about_input',
			'description' => 'Edit the opening paragraph, quotation and three essays. Section headings, layout, photographs and the honours table are unchanged.',
			'empty' => 'Leaving a box empty restores its shipped wording; it does not delete the paragraph.',
		),
		'blog' => array(
			'title' => 'Blog introduction', 'slug' => 'cas-content-blog', 'callback' => 'cas_content_blog_page',
			'fields' => 'cas_content_blog_fields', 'clean' => 'cas_content_blog_clean', 'controls' => 'cas_content_blog_controls',
			'input' => 'cas_blog_fields', 'snapshot' => 'cas_blog_snapshot', 'nonce' => 'cas_content_blog_save',
			'error' => 'blog', 'input_error' => 'cas_blog_input',
			'description' => 'Edit the introduction under the Blog banner. Articles, headings and layout are unchanged.',
			'empty' => 'Leaving the box empty hides the introduction; it does not restore shipped wording.',
		),
		'free-videos' => array(
			'title' => 'Free Videos introductions', 'slug' => 'cas-content-free-videos', 'callback' => 'cas_content_free_videos_page',
			'fields' => 'cas_content_free_videos_fields', 'clean' => 'cas_content_free_videos_clean', 'controls' => 'cas_content_free_videos_controls',
			'input' => 'cas_fv_fields', 'snapshot' => 'cas_fv_snapshot', 'nonce' => 'cas_content_free_videos_save',
			'error' => 'free_videos', 'input_error' => 'cas_free_videos_input',
			'description' => 'Edit the opening line and three section introductions. Video records, headings and layout are unchanged.',
			'empty' => 'Leaving a box empty hides that paragraph; it does not restore shipped wording.',
		),
		'home' => array(
			'title' => 'Home text', 'slug' => 'cas-content-home', 'callback' => 'cas_content_home_page',
			'fields' => 'cas_content_home_fields', 'clean' => 'cas_content_home_clean', 'controls' => 'cas_content_home_controls',
			'input' => 'cas_home_fields', 'snapshot' => 'cas_home_snapshot', 'nonce' => 'cas_content_home_save',
			'error' => 'home', 'input_error' => 'cas_home_input',
			'description' => 'Edit the two card descriptions, Shop all courses introduction and six Course overview fields. Images, hero slides, section headings and list entries are unchanged.',
			'empty' => 'Clearing a field hides that text. Clearing a point title also hides that point and its body, without deleting the saved body text.',
		),
		'contact' => array(
			'title' => 'Site-wide contact and social links', 'slug' => 'cas-content-contact', 'callback' => 'cas_content_contact_page',
			'fields' => 'cas_content_contact_fields', 'clean' => 'cas_content_contact_clean', 'controls' => 'cas_content_contact_controls',
			'input' => 'cas_contact_fields', 'snapshot' => 'cas_contact_snapshot', 'nonce' => 'cas_content_contact_save',
			'error' => 'contact', 'input_error' => 'cas_contact_input',
			'description' => 'Edit the existing shared social URLs and contact email used across the homepage, footer, FAQ and About/contact surfaces. Changing one value affects all its existing consumers.',
			'empty' => 'Empty fields stay empty; they do not restore defaults. The existing renderer still prints its links, so clearing a value can leave an empty link or email destination.',
		),
		'hero' => array(
			'title' => 'Homepage carousel', 'slug' => 'cas-content-hero', 'callback' => 'cas_content_hero_page',
			'fields' => 'cas_content_hero_fields', 'clean' => 'cas_content_hero_clean', 'controls' => 'cas_content_hero_controls',
			'input' => 'cas_hero_fields', 'snapshot' => 'cas_hero_snapshot', 'nonce' => 'cas_content_hero_save',
			'error' => 'hero', 'input_error' => 'cas_hero_input',
			'description' => 'Edit the existing three slides: title, description, large image and small image. The CTA, layout, image crop slots and motion remain unchanged.',
			'empty' => 'Cleared fields stay blank rather than restoring defaults. Clearing an image omits it; clearing the first title leaves the main heading empty.',
		),
		'home-images' => array(
			'title' => 'Home photographs', 'slug' => 'cas-content-home-images', 'callback' => 'cas_content_home_images_page',
			'fields' => 'cas_content_home_images_fields', 'clean' => 'cas_content_home_images_clean', 'controls' => 'cas_content_home_images_controls',
			'input' => 'cas_home_image_fields', 'snapshot' => 'cas_home_images_snapshot', 'nonce' => 'cas_content_home_images_save',
			'error' => 'home_images', 'input_error' => 'cas_home_images_input',
			'description' => 'Edit the three existing Home photographs. Text, artwork records, crop slots and layout remain unchanged.',
			'empty' => 'Clearing About Crystal or Free videos restores its shipped photograph. Clearing the optional TOOLS photograph leaves the plain dark card.',
		),
		'about-image' => array(
			'title' => 'About portrait', 'slug' => 'cas-content-about-image', 'callback' => 'cas_content_about_image_page',
			'fields' => 'cas_content_about_image_fields', 'clean' => 'cas_content_about_image_clean', 'controls' => 'cas_content_about_image_controls',
			'input' => 'cas_about_image_fields', 'snapshot' => 'cas_about_image_snapshot', 'nonce' => 'cas_content_about_image_save',
			'error' => 'about_image', 'input_error' => 'cas_about_image_input',
			'description' => 'Edit the existing portrait photograph. The arched crop, text, carousels and honours table remain unchanged.',
			'empty' => 'Clearing the portrait stores an empty URL and restores the shipped photograph; it does not remove the portrait space.',
		),
		'brand-images' => array(
			'title' => 'Site-wide brand images', 'slug' => 'cas-content-brand-images', 'callback' => 'cas_content_brand_images_page',
			'fields' => 'cas_content_brand_images_fields', 'clean' => 'cas_content_brand_images_clean', 'controls' => 'cas_content_brand_images_controls',
			'input' => 'cas_brand_image_fields', 'snapshot' => 'cas_brand_images_snapshot', 'nonce' => 'cas_content_brand_images_save',
			'error' => 'brand_images', 'input_error' => 'cas_brand_images_input',
			'description' => 'Edit the existing header logo, shared signature mark and footer signature. Their native sizing, recolouring and placements remain unchanged.',
			'empty' => 'Clearing a brand URL restores its shipped asset, not an empty layout. These shared images affect their existing site-wide consumers; native upload type restrictions remain.',
		),
	);
	return is_string( $group ) && isset( $pages[ $group ] ) ? $pages[ $group ] : null;
}

function cas_content_prose_allowed_pages( $pages ) {
	return array_merge( $pages, array( 'cas-content-about', 'cas-content-blog', 'cas-content-free-videos', 'cas-content-home', 'cas-content-contact', 'cas-content-hero', 'cas-content-home-images', 'cas-content-about-image', 'cas-content-brand-images', 'cas-content-home-page', 'cas-content-about-page', 'cas-content-site-details' ) );
}
add_filter( 'cas_content_allowed_pages', 'cas_content_prose_allowed_pages' );

function cas_content_prose_menu() {
	$visible = array( 'home-page', 'about-page', 'blog', 'free-videos', 'site-details' );
	foreach ( array_merge( $visible, array( 'about', 'home', 'contact', 'hero', 'home-images', 'about-image', 'brand-images' ) ) as $group ) {
		$page = cas_content_prose_config( $group );
		add_submenu_page( CAS_CONTENT_HUB_SLUG, cas_admin_title( $page['title'] ), cas_admin_title( $page['title'] ), CAS_CONTENT_CAP, $page['slug'], $page['callback'] );
		// Keep old bookmarked forms callable with their original guards, without cluttering the menu.
		if ( ! in_array( $group, $visible, true ) ) {
			remove_submenu_page( CAS_CONTENT_HUB_SLUG, $page['slug'] );
		}
	}
}
add_action( 'admin_menu', 'cas_content_prose_menu', 20 );

function cas_content_prose_plain_value( $value, $depth = 0 ) {
	if ( $depth > 32 || is_object( $value ) || is_resource( $value ) ) {
		return false;
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $child ) {
			if ( ! cas_content_prose_plain_value( $child, $depth + 1 ) ) {
				return false;
			}
		}
	}
	return true;
}

function cas_content_prose_row( $group, $option ) {
	global $wpdb;
	$config = cas_content_prose_config( $group );
	if ( ! $config ) {
		return new WP_Error( 'prose_denied', 'This editing page is not available.' );
	}
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_id, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option ), ARRAY_A );
	if ( ! $row || $wpdb->last_error || strlen( $row['option_value'] ) > 1048576 ) {
		return new WP_Error( $config['error'] . '_storage', 'The saved page content could not be read safely. Stop and contact the site administrator.' );
	}
	$mods = @unserialize( $row['option_value'], array( 'allowed_classes' => false, 'max_depth' => 32 ) );
	if ( ! is_array( $mods ) || ! cas_content_prose_plain_value( $mods ) || serialize( $mods ) !== $row['option_value'] ) {
		return new WP_Error( $config['error'] . '_storage', 'The existing theme settings need an administrator check. Nothing has been saved.' );
	}
	foreach ( call_user_func( $config['fields'] ) as $key => $field ) {
		if ( array_key_exists( $key, $mods ) && ! is_string( $mods[ $key ] ) ) {
			return new WP_Error( $config['error'] . '_storage', 'The existing page text needs an administrator check. Nothing has been saved.' );
		}
	}
	$row['mods'] = $mods;
	return $row;
}

function cas_content_prose_snapshot( $group, $option, $mods ) {
	$config = cas_content_prose_config( $group );
	if ( ! $config ) {
		return '';
	}
	$values = array();
	foreach ( call_user_func( $config['fields'] ) as $key => $field ) {
		$values[ $key ] = array_key_exists( $key, $mods ) ? array( true, $mods[ $key ] ) : array( false );
	}
	return wp_hash( get_current_user_id() . '|' . $group . '|' . $option . '|' . serialize( $values ) );
}

function cas_content_prose_save( $group, $option, $snapshot, $input ) {
	global $wpdb;
	$config = cas_content_prose_config( $group );
	if ( ! $config || ! current_user_can( CAS_CONTENT_CAP ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return new WP_Error( $config ? $config['error'] . '_denied' : 'prose_denied', 'You cannot save page text from this request.' );
	}
	$nonce = $_POST['_wpnonce'] ?? null;
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), $config['nonce'] ) ) {
		return new WP_Error( $config['error'] . '_denied', 'The security check expired. Copy your text, then reload before saving.' );
	}
	if ( $option !== 'theme_mods_' . get_option( 'stylesheet' ) ) {
		return new WP_Error( $config['error'] . '_stale', 'The active theme changed. Nothing has been saved.' );
	}
	$clean = call_user_func( $config['clean'], $input );
	if ( is_wp_error( $clean ) ) {
		return $clean;
	}
	$row = cas_content_prose_row( $group, $option );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	if ( ! is_string( $snapshot ) || ! hash_equals( cas_content_prose_snapshot( $group, $option, $row['mods'] ), $snapshot ) ) {
		return new WP_Error( $config['error'] . '_stale', 'This page text changed after you opened the form. Nothing has been saved. Copy your text, reload the latest version and compare before editing again.' );
	}
	$mods = $row['mods'];
	foreach ( $clean as $key => $value ) {
		$mods[ $key ] = $value;
	}
	$raw = serialize( $mods );
	if ( $raw === $row['option_value'] ) {
		return 'unchanged';
	}
	if ( strlen( $raw ) > 1048576 ) {
		return new WP_Error( $config['input_error'], 'These settings exceed the safe editing size. Nothing has been saved; contact the site administrator.' );
	}
	// The whole raw option and autoload preimage must still match at the SQL write boundary.
	$changed = $wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->options} SET option_value = %s WHERE option_id = %d AND BINARY option_name = BINARY %s AND BINARY option_value = BINARY %s AND BINARY autoload = BINARY %s",
		$raw, $row['option_id'], $option, $row['option_value'], $row['autoload']
	) );
	foreach ( array( $option, 'alloptions', 'notoptions' ) as $cache_key ) {
		wp_cache_delete( $cache_key, 'options' );
	}
	if ( 0 === $changed ) {
		return new WP_Error( $config['error'] . '_stale', 'Settings changed while saving. Nothing was overwritten. Copy your text and reload the latest version before editing again.' );
	}
	if ( 1 !== $changed ) {
		return new WP_Error( $config['error'] . '_storage', 'Saving could not be confirmed. Do not retry; contact the site administrator to check the saved version.' );
	}
	do_action( "update_option_{$option}", $row['mods'], $mods, $option );
	do_action( 'updated_option', $option, $row['mods'], $mods );
	$after = cas_content_prose_row( $group, $option );
	if ( is_wp_error( $after ) || $after['option_id'] !== $row['option_id'] || $after['autoload'] !== $row['autoload'] || $after['option_value'] !== $raw || serialize( get_option( $option ) ) !== $raw ) {
		return new WP_Error( $config['error'] . '_storage', 'A write occurred, but its saved and cached readback could not be confirmed. Do not retry; contact the site administrator to check the saved version.' );
	}
	return 'saved';
}

function cas_content_prose_page( $group ) {
	$config = cas_content_prose_config( $group );
	if ( ! $config || ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit this page text.', '', array( 'response' => 403 ) );
	}
	$option = 'theme_mods_' . get_option( 'stylesheet' );
	$row    = cas_content_prose_row( $group, $option );
	$result = null;
	$posted = null;
	$submitted_snapshot = $_POST[ $config['snapshot'] ] ?? null;
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		$posted = $_POST[ $config['input'] ] ?? null;
		$result = cas_content_prose_save( $group, $option, $submitted_snapshot, $posted );
		$row    = cas_content_prose_row( $group, $option );
	}
	$blocked = is_wp_error( $row ) || ( is_wp_error( $result ) && ( $config['input_error'] !== $result->get_error_code() || ! is_string( $submitted_snapshot ) || ! hash_equals( cas_content_prose_snapshot( $group, $option, $row['mods'] ), $submitted_snapshot ) ) );
	echo '<div class="wrap cas-content-ui cas-content-editor"><header class="cas-editor-header"><h1>' . esc_html( cas_admin_title( $config['title'] ) ) . '</h1><p>' . esc_html( $config['description'] ) . '</p></header>';
	echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=' . CAS_CONTENT_HUB_SLUG ) ) . '">Back to Content Hub</a></p>';
	echo '<p><strong>' . esc_html( $config['empty'] ) . '</strong> Saving changes the values used on the site. Cached public pages may take time to refresh.</p>';
	if ( is_wp_error( $result ) || is_wp_error( $row ) ) {
		$error = is_wp_error( $result ) ? $result : $row;
		echo '<div class="notice notice-error" role="alert"><p>' . esc_html( $error->get_error_message() ) . '</p></div>';
	} elseif ( $result ) {
		echo '<div class="notice notice-success" role="status"><p>' . esc_html( 'saved' === $result ? $config['title'] . ' saved and read back successfully.' : 'No changes were needed.' ) . '</p></div>';
	}
	if ( is_wp_error( $row ) ) {
		echo '</div>';
		return;
	}
	$url = admin_url( 'admin.php?page=' . $config['slug'] );
	if ( $blocked ) {
		echo '<p>Copy any unsaved text before <a href="' . esc_url( $url ) . '">reloading the latest saved version</a>. Saving is stopped on this form.</p>';
	}
	if ( $group === 'home-page' && function_exists( 'cas_content_gallery_picker' ) ) { cas_content_gallery_picker(); }
	echo '<form method="post" action="' . esc_url( $url ) . '">';
	wp_nonce_field( $config['nonce'] );
	$snapshot = $blocked ? '' : cas_content_prose_snapshot( $group, $option, $row['mods'] );
	echo '<input type="hidden" name="' . esc_attr( $config['snapshot'] ) . '" value="' . esc_attr( $snapshot ) . '">';
	$values = $row['mods'];
	foreach ( call_user_func( $config['fields'] ) as $key => $field ) {
		if ( is_wp_error( $result ) && is_array( $posted ) && isset( $posted[ $key ] ) && is_string( $posted[ $key ] ) ) {
			$values[ $key ] = wp_kses_post( wp_unslash( $posted[ $key ] ) );
		}
	}
	echo '<div class="cas-editor-fields" id="cas-section-' . esc_attr( $group ) . '">';
	call_user_func( $config['controls'], $values );
	echo '</div>';
	echo '<aside class="cas-editor-rail"><nav class="cas-editor-toc" aria-label="Page Sections"><h2>On This Page</h2><ul>';
	if ( $group === 'home-page' ) { echo '<li><a href="#home-gallery-image">Gallery Image</a></li>'; }
	foreach ( $config['groups'] ?? array( $group ) as $section ) {
		$section_config = cas_content_prose_config( $section );
		echo '<li><a href="#cas-section-' . esc_attr( $section ) . '">' . esc_html( cas_admin_title( $section_config['title'] ) ) . '</a></li>';
	}
	echo '</ul></nav><div class="cas-editor-actions"><p>Save the fields in this form.</p>';
	submit_button( cas_admin_title( 'Save ' . $config['title'] ), 'primary', 'submit', false, $blocked ? array( 'disabled' => 'disabled' ) : array() );
	echo '</div></aside>';
	echo '</form>';
	cas_content_prose_related( $group, $url );
	echo '</div>';
}

/** Reuse the hub's existing view/list links so page editing and repeating content stay connected. */
function cas_content_prose_related( $group, $url ) {
	$cards = array( 'home-page' => 'Home', 'about-page' => 'About', 'blog' => 'Blog', 'free-videos' => 'Free videos', 'site-details' => 'Every page' );
	if ( ! isset( $cards[ $group ] ) || ! function_exists( 'cas_content_hub_cards' ) ) {
		return;
	}
	foreach ( cas_content_hub_cards() as $card ) {
		if ( $card['name'] !== $cards[ $group ] ) {
			continue;
		}
		echo '<h2>Related Content</h2><p>Save your changes before opening another editor.</p>';
		if ( $card['view'] ) {
			echo '<p><a class="button" href="' . esc_url( $card['view'] ) . '" target="_blank" rel="noopener">View saved page (opens a new tab)</a></p>';
		}
		$links = array_filter( $card['items'], static function ( $item ) use ( $url ) {
			return $item['url'] !== $url && current_user_can( $item['cap'] );
		} );
		if ( $links ) {
			echo '<ul>';
			foreach ( $links as $link ) {
				echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( cas_admin_title( $link['text'] ) ) . '</a></li>';
			}
			echo '</ul>';
		}
		return;
	}
}
