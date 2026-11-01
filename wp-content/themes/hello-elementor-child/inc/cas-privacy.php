<?php
/** Student sharing preferences and functional browser privacy choices. Legal copy stays separately gated. */
defined( 'ABSPATH' ) || exit;

/** Defaults are preferences, never records of consent. Explicit choices always win. */
function cas_showcase_sharing_enabled( $user_id ) {
	if ( ! $user_id || ! get_userdata( $user_id ) ) { return false; }
	$value = get_user_meta( $user_id, '_cas_showcase_sharing', true );
	return in_array( $value, array( '0', '1' ), true ) ? '1' === $value : '1' === (string) get_option( 'cas_showcase_default', '1' );
}

function cas_showcase_piece_visible( $post_id ) {
	return cas_showcase_sharing_enabled( (int) get_post_meta( $post_id, CAS_SHOWCASE_META_USER, true ) );
}

function cas_showcase_purge_page() {
	do_action( 'litespeed_purge_url', cas_page_url( 'showcase' ) );
}
add_action( 'update_option_cas_showcase_default', 'cas_showcase_purge_page' );
add_action( 'add_option_cas_showcase_default', 'cas_showcase_purge_page' );
foreach ( array( 'added_user_meta', 'updated_user_meta', 'deleted_user_meta' ) as $hook ) {
	add_action( $hook, static function ( $meta_id, $user_id, $key ) {
		if ( '_cas_showcase_sharing' === $key ) { cas_showcase_purge_page(); }
	}, 10, 3 );
}
add_action( 'template_redirect', static function () {
	if ( is_page( 'showcase' ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		nocache_headers();
		do_action( 'litespeed_control_set_nocache', 'Student Showcase preferences must remain current.' );
	}
}, 0 );

function cas_showcase_preference_html( $user_id ) {
	ob_start(); ?>
	<div class="dash-card cas-privacy-card">
		<h3>Student Showcase</h3>
		<p>Let Crystal consider your artwork for the public Student Showcase. Crystal still needs permission to publish each picture and any name credit.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="cas_save_showcase_preference">
			<?php wp_nonce_field( 'cas_save_showcase_preference', 'cas_showcase_preference_nonce' ); ?>
			<label class="cas-privacy-choice"><input type="checkbox" name="cas_showcase_sharing" value="1" <?php checked( cas_showcase_sharing_enabled( $user_id ) ); ?>> Allow my artwork to be considered for the Showcase</label>
			<p>Switch off to remove your work from the Showcase on future visits. Previously downloaded copies and direct image links may remain; contact Crystal for image removal.</p>
			<button class="btn primary" type="submit">Save Showcase preference</button>
			<?php if ( isset( $_GET['cas_showcase_saved'] ) ) : ?><span role="status">Preference saved.</span><?php endif; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		</form>
	</div>
	<div class="dash-card cas-privacy-card"><h3>Cookies &amp; privacy choices</h3><p>Manage external video players for this browser. This is separate from your account’s Showcase preference.</p><button class="btn" type="button" data-cas-privacy-open>Manage browser privacy</button></div>
	<?php return (string) ob_get_clean();
}

add_action( 'admin_post_cas_save_showcase_preference', static function () {
	$user_id = get_current_user_id();
	$nonce = $_POST['cas_showcase_preference_nonce'] ?? '';
	$value = $_POST['cas_showcase_sharing'] ?? '0';
	if ( ! $user_id || ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'cas_save_showcase_preference' ) ) {
		wp_die( 'The link has expired. Go back and try again.', '', array( 'response' => 403 ) );
	}
	if ( ! is_string( $value ) || ! in_array( $value, array( '0', '1' ), true ) ) {
		wp_die( 'Invalid Showcase preference.', '', array( 'response' => 400 ) );
	}
	update_user_meta( $user_id, '_cas_showcase_sharing', $value );
	if ( (string) get_user_meta( $user_id, '_cas_showcase_sharing', true ) !== $value ) {
		wp_die( 'Your preference could not be saved. Please try again.', '', array( 'response' => 500 ) );
	}
	wp_safe_redirect( add_query_arg( 'cas_showcase_saved', '1', tutor_utils()->tutor_dashboard_url() ) . '#panel-settings' );
	exit;
} );

add_filter( 'cas_content_allowed_pages', static function ( $pages ) {
	$pages[] = 'cas-privacy-settings';
	return $pages;
} );
add_action( 'admin_menu', static function () {
	add_submenu_page( 'cas-content-hub', 'Privacy & Showcase', 'Privacy & Showcase', CAS_CONTENT_CAP, 'cas-privacy-settings', 'cas_privacy_settings_page' );
} );

function cas_privacy_settings_page() {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) { wp_die( 'Permission denied.', '', array( 'response' => 403 ) ); }
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		check_admin_referer( 'cas_privacy_settings' );
		$value = $_POST['cas_showcase_default'] ?? null;
		if ( ! is_string( $value ) || ! in_array( $value, array( '0', '1' ), true ) ) { wp_die( 'Invalid default.', '', array( 'response' => 400 ) ); }
		update_option( 'cas_showcase_default', $value, false );
		if ( (string) get_option( 'cas_showcase_default', null ) !== $value ) {
			wp_die( 'The default could not be saved. Please try again.', '', array( 'response' => 500 ) );
		}
		echo '<div class="notice notice-success"><p>Showcase default saved.</p></div>';
	}
	?>
	<div class="wrap"><h1>Privacy &amp; Showcase</h1>
	<form method="post"><?php wp_nonce_field( 'cas_privacy_settings' ); ?>
		<h2>Showcase default for students without a saved choice</h2>
		<select name="cas_showcase_default" aria-label="Default Showcase preference"><option value="1" <?php selected( (string) get_option( 'cas_showcase_default', '1' ), '1' ); ?>>ON — consider artwork for the Showcase</option><option value="0" <?php selected( (string) get_option( 'cas_showcase_default', '1' ), '0' ); ?>>OFF — exclude artwork from the Showcase</option></select>
		<p>Applies to existing and new accounts without a saved choice. Never overwrites a student’s saved ON or OFF choice.</p>
		<p><strong>This default is not student consent.</strong> Obtain appropriate permission for each picture and public credit before publishing. An account, purchase or default setting does not record consent. Policy wording and the legal basis need adviser review.</p>
		<?php submit_button( 'Save Showcase default' ); ?>
	</form>
	<h2>Work excluded from the public wall</h2><p>Unlinked work and work belonging to students whose preference is OFF are hidden. Hiding a name does not remove the student link. Review any existing public image URLs separately; removing a wall tile does not delete the media file or copies.</p>
	<ul><?php
	foreach ( get_posts( array( 'post_type' => CAS_SHOWCASE_POST_TYPE, 'post_status' => 'publish', 'numberposts' => -1 ) ) as $piece ) {
		if ( ! cas_showcase_piece_visible( $piece->ID ) && current_user_can( 'edit_post', $piece->ID ) ) {
			echo '<li><a href="' . esc_url( get_edit_post_link( $piece->ID ) ) . '">' . esc_html( $piece->post_title ?: 'Untitled piece' ) . '</a> — review image removal or student link</li>';
		}
	} ?></ul>
	<h2>Browser privacy controls</h2><p>External video players are OFF until enabled in that browser. Visitors can change this using Privacy choices in the footer. No analytics or marketing toggle is offered because these controls do not manage such integrations. New integrations require their own review and controls.</p>
	</div><?php
}

/** Transform before HTML reaches the browser; removing a live src in JS is too late. */
function cas_privacy_video_html( $html ) {
	$tags = new WP_HTML_Tag_Processor( $html );
	while ( $tags->next_tag() ) {
		if ( ! in_array( $tags->get_tag(), array( 'IFRAME', 'VIDEO', 'SOURCE' ), true ) ) { continue; }
		$src = $tags->get_attribute( 'src' );
		if ( ! is_string( $src ) || ! preg_match( '~^(?:https?:)?//~i', $src ) ) { continue; }
		if ( wp_parse_url( $src, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) { continue; }
		$tags->set_attribute( 'data-cas-media-src', $src );
		$tags->remove_attribute( 'src' );
	}
	return $tags->get_updated_html();
}

add_action( 'wp_enqueue_scripts', static function () {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'cas-privacy', $uri . '/css/cas-privacy.css', array( 'cas-industrial-3' ), filemtime( $dir . '/css/cas-privacy.css' ) );
	wp_enqueue_script( 'cas-privacy', $uri . '/js/cas-privacy.js', array( 'cas-site-extras' ), filemtime( $dir . '/js/cas-privacy.js' ), true );
}, 25 );

add_action( 'wp_footer', static function () {
	$social = cas_social();
	if ( ! did_action( 'cas_footer_rendered' ) ) {
		echo '<div class="container"><button type="button" class="cas-privacy-link" data-cas-privacy-open>Privacy choices</button></div>';
	}
	?>
	<noscript><p class="container">External video players stay off without JavaScript. Contact Crystal if you need help accessing a lesson.</p></noscript>
	<aside class="cas-cookie-banner" id="cas-cookie-banner" aria-label="Cookies and privacy" hidden>
		<p>We use browser storage for account, basket, security and display settings. External video players stay off until you enable them.</p>
		<div class="cas-privacy-actions"><button type="button" class="btn" data-cas-privacy-reject>Keep videos off</button><button type="button" class="btn" data-cas-privacy-open>Choose settings</button><button type="button" class="btn primary" data-cas-privacy-allow>Allow video players</button></div>
	</aside>
	<div class="cas-privacy-modal" id="cas-privacy-modal" role="dialog" aria-modal="true" aria-labelledby="cas-privacy-title" tabindex="-1" hidden inert>
		<div class="cas-privacy-panel"><button type="button" class="cas-privacy-close btn" data-cas-privacy-close aria-label="Close privacy choices">×</button>
		<h2 id="cas-privacy-title">Cookies &amp; privacy choices</h2>
		<p>These settings apply to this browser, not every device or your Showcase account preference.</p>
		<h3>Site functions &amp; saved settings</h3><p>Sign-in, basket and security use cookies or storage. Display choices such as your theme, and the privacy choice you save here, are stored on this browser. These controls do not switch off those site functions.</p>
		<label class="cas-privacy-choice"><input type="checkbox" id="cas-privacy-media"> Allow external video players</label>
		<p>Site fonts load from Google independently of this setting. Loading a video connects to Bunny or YouTube (or the video host named by the player). They receive your IP address and browser details and may use cookies or storage under their policies. Turning this off unloads players on this page and blocks future loads here; it cannot remove data already held by those services.</p>
		<p>Your choice is remembered for six months if browser storage is available. You can change it at any time using Privacy choices.</p>
		<p>For access, correction or deletion requests, <a href="mailto:<?php echo esc_attr( $social['email'] ); ?>">contact Crystal</a>. See <a href="<?php echo esc_url( cas_page_url( 'about' ) . '#terms' ); ?>">Terms &amp; privacy</a> for the site’s current published information.</p>
		<div class="cas-privacy-actions"><button type="button" class="btn" data-cas-privacy-reject>Keep videos off</button><button type="button" class="btn primary" data-cas-privacy-save>Save choices</button></div>
		<p id="cas-privacy-status" role="status"></p></div>
	</div>
	<?php
}, 15 );
