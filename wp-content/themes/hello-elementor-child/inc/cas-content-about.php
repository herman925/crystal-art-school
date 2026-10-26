<?php

defined( 'ABSPATH' ) || exit;

function cas_content_about_fields() {
	$schema = cas_about_fields();
	$fields = array();
	foreach ( array( 'cas_about_intro', 'cas_about_quote', 'cas_about_journey_body', 'cas_about_community_body', 'cas_about_instructor_body' ) as $key ) {
		$fields[ $key ] = $schema[ $key ];
	}
	return $fields;
}

function cas_content_about_allowed_pages( $pages ) {
	$pages[] = 'cas-content-about';
	return $pages;
}
add_filter( 'cas_content_allowed_pages', 'cas_content_about_allowed_pages' );

function cas_content_about_menu() {
	add_submenu_page( CAS_CONTENT_HUB_SLUG, 'About text', 'About text', CAS_CONTENT_CAP, 'cas-content-about', 'cas_content_about_page' );
}
add_action( 'admin_menu', 'cas_content_about_menu', 20 );

function cas_content_about_plain_value( $value, $depth = 0 ) {
	if ( $depth > 32 || is_object( $value ) || is_resource( $value ) ) {
		return false;
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $child ) {
			if ( ! cas_content_about_plain_value( $child, $depth + 1 ) ) {
				return false;
			}
		}
	}
	return true;
}

function cas_content_about_row( $option ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_id, option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option ), ARRAY_A );
	if ( ! $row || $wpdb->last_error || strlen( $row['option_value'] ) > 1048576 ) {
		return new WP_Error( 'about_storage', 'The saved About text could not be read safely. Stop and contact Herman.' );
	}
	$mods = @unserialize( $row['option_value'], array( 'allowed_classes' => false, 'max_depth' => 32 ) );
	if ( ! is_array( $mods ) || ! cas_content_about_plain_value( $mods ) || serialize( $mods ) !== $row['option_value'] ) {
		return new WP_Error( 'about_storage', 'The existing theme settings need an administrator check. Nothing has been saved.' );
	}
	foreach ( cas_content_about_fields() as $key => $field ) {
		if ( array_key_exists( $key, $mods ) && ! is_string( $mods[ $key ] ) ) {
			return new WP_Error( 'about_storage', 'The existing About text needs an administrator check. Nothing has been saved.' );
		}
	}
	$row['mods'] = $mods;
	return $row;
}

function cas_content_about_snapshot( $option, $mods ) {
	$values = array();
	foreach ( cas_content_about_fields() as $key => $field ) {
		$values[ $key ] = array_key_exists( $key, $mods ) ? array( true, $mods[ $key ] ) : array( false );
	}
	return wp_hash( get_current_user_id() . '|' . $option . '|' . serialize( $values ) );
}

function cas_content_about_clean( $input ) {
	$fields = cas_content_about_fields();
	if ( ! is_array( $input ) || count( $input ) !== count( $fields ) || array_diff_key( $input, $fields ) || array_diff_key( $fields, $input ) ) {
		return new WP_Error( 'about_input', 'Submit exactly the five About text fields. Nothing has been saved.' );
	}
	$clean = array();
	foreach ( $fields as $key => $field ) {
		if ( ! is_string( $input[ $key ] ) ) {
			return new WP_Error( 'about_input', 'Each About text field must contain text. Nothing has been saved.' );
		}
		$value = wp_kses_post( wp_unslash( $input[ $key ] ) );
		if ( 'inline' === $field['type'] ) {
			preg_match_all( '/<\/?([a-z][a-z0-9]*)\b[^>]*>/i', $value, $tags );
			$inline = array( 'a', 'abbr', 'acronym', 'b', 'br', 'cite', 'code', 'del', 'em', 'i', 'ins', 'kbd', 'mark', 'q', 's', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u', 'var' );
			if ( array_diff( array_map( 'strtolower', $tags[1] ), $inline ) ) {
				return new WP_Error( 'about_input', 'Opening paragraph and quotation accept inline formatting only. Remove paragraph wrappers, headings, lists or images from these two fields. Nothing has been saved.' );
			}
		}
		$clean[ $key ] = $value;
	}
	return $clean;
}

function cas_content_about_save( $option, $snapshot, $input ) {
	global $wpdb;
	if ( ! current_user_can( CAS_CONTENT_CAP ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return new WP_Error( 'about_denied', 'You cannot save About text from this request.' );
	}
	$nonce = $_POST['_wpnonce'] ?? null;
	if ( ! is_string( $nonce ) || ! wp_verify_nonce( wp_unslash( $nonce ), 'cas_content_about_save' ) ) {
		return new WP_Error( 'about_denied', 'The security check expired. Copy your text, then reload before saving.' );
	}
	if ( $option !== 'theme_mods_' . get_option( 'stylesheet' ) ) {
		return new WP_Error( 'about_stale', 'The active theme changed. Nothing has been saved.' );
	}
	$clean = cas_content_about_clean( $input );
	if ( is_wp_error( $clean ) ) {
		return $clean;
	}
	$row = cas_content_about_row( $option );
	if ( is_wp_error( $row ) ) {
		return $row;
	}
	if ( ! is_string( $snapshot ) || ! hash_equals( cas_content_about_snapshot( $option, $row['mods'] ), $snapshot ) ) {
		return new WP_Error( 'about_stale', 'About text changed after you opened this form. Nothing has been saved. Copy your text, reload the latest version and compare before editing again.' );
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
		return new WP_Error( 'about_input', 'These settings exceed the safe editing size. Nothing has been saved; contact Herman.' );
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
		return new WP_Error( 'about_stale', 'Settings changed while saving. Nothing was overwritten. Copy your text and reload the latest version before editing again.' );
	}
	if ( 1 !== $changed ) {
		return new WP_Error( 'about_storage', 'Saving could not be confirmed. Do not retry; contact Herman to check the saved version.' );
	}
	do_action( "update_option_{$option}", $row['mods'], $mods, $option );
	do_action( 'updated_option', $option, $row['mods'], $mods );
	$after = cas_content_about_row( $option );
	if ( is_wp_error( $after ) || $after['option_id'] !== $row['option_id'] || $after['autoload'] !== $row['autoload'] || $after['option_value'] !== $raw || serialize( get_option( $option ) ) !== $raw ) {
		return new WP_Error( 'about_storage', 'A write occurred, but its saved and cached readback could not be confirmed. Do not retry; contact Herman to check the saved version.' );
	}
	return 'saved';
}

function cas_content_about_page() {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) {
		wp_die( 'You cannot edit About text.', '', array( 'response' => 403 ) );
	}
	$option = 'theme_mods_' . get_option( 'stylesheet' );
	$row    = cas_content_about_row( $option );
	$result = null;
	$posted = null;
	$submitted_snapshot = $_POST['cas_about_snapshot'] ?? null;
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		$posted = $_POST['cas_about_fields'] ?? null;
		$result = cas_content_about_save( $option, $submitted_snapshot, $posted );
		$row    = cas_content_about_row( $option );
	}
	$blocked = is_wp_error( $row ) || ( is_wp_error( $result ) && ( 'about_input' !== $result->get_error_code() || ! is_string( $submitted_snapshot ) || ! hash_equals( cas_content_about_snapshot( $option, $row['mods'] ), $submitted_snapshot ) ) );
	echo '<div class="wrap"><h1>About text</h1><p>Edit the opening paragraph, quotation and three essays. Section headings, layout, photographs and the honours table are unchanged.</p>';
	echo '<p><strong>Leaving a box empty restores its shipped wording; it does not delete the paragraph.</strong> Saving changes the About text. Cached public pages may take time to refresh.</p>';
	if ( is_wp_error( $result ) || is_wp_error( $row ) ) {
		$error = is_wp_error( $result ) ? $result : $row;
		echo '<div class="notice notice-error" role="alert"><p>' . esc_html( $error->get_error_message() ) . '</p></div>';
	} elseif ( $result ) {
		echo '<div class="notice notice-success" role="status"><p>' . esc_html( 'saved' === $result ? 'About text saved and read back successfully.' : 'No text changes were needed.' ) . '</p></div>';
	}
	if ( is_wp_error( $row ) ) {
		echo '</div>';
		return;
	}
	$url = admin_url( 'admin.php?page=cas-content-about' );
	if ( $blocked ) {
		echo '<p>Copy any unsaved text before <a href="' . esc_url( $url ) . '">reloading the latest saved version</a>. Saving is stopped on this form.</p>';
	}
	echo '<form method="post" action="' . esc_url( $url ) . '">';
	wp_nonce_field( 'cas_content_about_save' );
	$snapshot = $blocked ? '' : cas_content_about_snapshot( $option, $row['mods'] );
	echo '<input type="hidden" name="cas_about_snapshot" value="' . esc_attr( $snapshot ) . '">';
	foreach ( cas_content_about_fields() as $key => $field ) {
		$value = $row['mods'][ $key ] ?? $field['default'];
		if ( is_wp_error( $result ) && is_array( $posted ) && isset( $posted[ $key ] ) && is_string( $posted[ $key ] ) ) {
			$value = wp_kses_post( wp_unslash( $posted[ $key ] ) );
		}
		$inline = 'inline' === $field['type'];
		echo '<h2><label for="' . esc_attr( $key ) . '">' . esc_html( html_entity_decode( $field['label'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) . '</label></h2>';
		echo '<p>' . esc_html( $inline ? 'One paragraph, with emphasis or links. Headings and lists belong in the essays below.' : 'Use paragraphs and emphasis. Cross-headings use the Cross-heading format; the section heading above this essay stays fixed.' ) . '</p>';
		wp_editor( $value, $key, array(
			'textarea_name' => 'cas_about_fields[' . $key . ']',
			'textarea_rows' => $inline ? 3 : 10,
			'media_buttons' => false,
			'quicktags'     => false,
			'wpautop'       => ! $inline,
			'tinymce'       => array(
				'forced_root_block' => $inline ? false : 'p',
				'toolbar1'          => $inline ? 'bold italic link unlink undo redo' : 'styleselect bold italic bullist numlist link unlink undo redo',
				'toolbar2'          => '',
				'style_formats'     => array( array( 'title' => 'Paragraph', 'block' => 'p' ), array( 'title' => 'Cross-heading', 'block' => 'h3', 'classes' => 'bio-sub' ) ),
			),
		) );
	}
	submit_button( 'Save About text', 'primary', 'submit', true, $blocked ? array( 'disabled' => 'disabled' ) : array() );
	echo '</form></div>';
}
