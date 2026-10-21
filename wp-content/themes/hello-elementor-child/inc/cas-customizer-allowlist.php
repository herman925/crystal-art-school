<?php
/**
 * cas_content Customizer allowlist (issue #150, Route A, piece W1-2).
 *
 * The role may use the Customizer, but only for the settings on the allowlist below. Those
 * settings carry manage_cas_content; every other setting keeps core's edit_theme_options, so the
 * role cannot save it, and its sections are removed from the screen. Customizer drafts
 * (changesets) are the Customizer's own storage: the role may touch its own drafts only.
 *
 * The list is the register's OK rows for Customizer settings, plus the three brand images, which
 * are Class B rows the register records as intended. Tutor course and price settings are not here.
 */

const CAS_CUSTOMIZER_ALLOW_SETTINGS = array(
	'cas_social_instagram',
	'cas_social_facebook',
	'cas_social_youtube',
	'cas_social_email',
	'cas_hero_1_title', 'cas_hero_1_subtitle', 'cas_hero_1_large', 'cas_hero_1_small',
	'cas_hero_2_title', 'cas_hero_2_subtitle', 'cas_hero_2_large', 'cas_hero_2_small',
	'cas_hero_3_title', 'cas_hero_3_subtitle', 'cas_hero_3_large', 'cas_hero_3_small',
	'cas_home_about_image',
	'cas_home_free_videos_photo',
	'cas_home_free_videos_text',
	'cas_home_gallery_sub',
	'cas_home_overview_lede_1',
	'cas_home_overview_lede_2',
	'cas_home_overview_point_1_t',
	'cas_home_overview_point_1_b',
	'cas_home_overview_point_2_t',
	'cas_home_overview_point_2_b',
	'cas_home_shop_lede',
	'cas_home_tools_photo',
	'cas_about_intro',
	'cas_about_quote',
	'cas_about_journey_body',
	'cas_about_community_body',
	'cas_about_instructor_body',
	'cas_about_portrait',
	'cas_blog_intro',
	'cas_fv_intro_demos',
	'cas_fv_intro_quick',
	'cas_fv_intro_shorts',
	'cas_fv_lead',
	'cas_logo',
	'cas_logo2',
	'cas_signature',
);

/** Panels the role may see. Every other panel is removed for the role. */
const CAS_CUSTOMIZER_ALLOW_PANELS = array( 'cas_pages', 'cas_sitewide' ); // 'cas_sitewide' is the Site-wide panel id (contact, brand).

function cas_customizer_allowed_setting( $id ) {
	return in_array( $id, CAS_CUSTOMIZER_ALLOW_SETTINGS, true );
}

/**
 * Drafts are customize_changeset posts. Core's role-level gate refuses that type, so this runs after it
 * (priority 11) and lets the role act on its own drafts only. Other users' drafts stay refused.
 */
function cas_customizer_changeset_cap( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'read_post', 'delete_post' ), true ) || ! cas_content_is_user( $user_id ) ) {
		return $caps;
	}
	$post = isset( $args[0] ) ? get_post( (int) $args[0] ) : null;
	if ( ! $post || 'customize_changeset' !== $post->post_type ) {
		return $caps;
	}
	return ( (int) $post->post_author === (int) $user_id ) ? array( CAS_CONTENT_CAP ) : array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'cas_customizer_changeset_cap', 11, 4 );

/** Late in customize_register: open the allowlisted settings, their controls and sections; remove the rest. */
function cas_customizer_restrict( $wp_customize ) {
	if ( ! cas_content_is_user( get_current_user_id() ) ) {
		return;
	}
	foreach ( $wp_customize->settings() as $id => $setting ) {
		if ( cas_customizer_allowed_setting( $id ) ) {
			$setting->capability = CAS_CONTENT_CAP;
		}
	}
	$open = array();
	foreach ( $wp_customize->controls() as $control ) {
		$setting = isset( $control->settings['default'] ) ? $control->settings['default'] : null;
		if ( $setting && cas_customizer_allowed_setting( $setting->id ) ) {
			$control->capability = CAS_CONTENT_CAP;
			$open[ $control->section ] = true;
		}
	}
	foreach ( array_keys( $wp_customize->sections() ) as $id ) {
		if ( isset( $open[ $id ] ) ) {
			$wp_customize->get_section( $id )->capability = CAS_CONTENT_CAP;
		} else {
			$wp_customize->remove_section( $id );
		}
	}
	foreach ( array_keys( $wp_customize->panels() ) as $id ) {
		if ( in_array( $id, CAS_CUSTOMIZER_ALLOW_PANELS, true ) ) {
			$wp_customize->get_panel( $id )->capability = CAS_CONTENT_CAP;
		} else {
			$wp_customize->remove_panel( $id );
		}
	}
}
add_action( 'customize_register', 'cas_customizer_restrict', 1000 );
