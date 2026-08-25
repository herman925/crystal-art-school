<?php
defined( 'ABSPATH' ) || exit;

/**
 * Free Videos — direction document section 15.
 *
 * The chapter fixes the page's shape: one featured block above three sections,
 * the first two 16:9 and the third 9:16 (1.4.12, one player and two container
 * ratios rather than two components). Those headings, that order and those
 * ratios are structure and stay here in code. The films themselves are content,
 * so each one is a post: title, cover photograph, link and section.
 *
 * THE FEATURED BLOCK OVERRIDES THE CHAPTER, DELIBERATELY. The section 15 table
 * names the featured row `Studio Notes from Crystal Lau`. Review tickets D005
 * and D006 (2026-08-16) — hers and Herman's — both say the stage should carry
 * step-by-step demos, and CLAUDE.md's 2026-08-21 entry records the override.
 * The heading moved with the content: a stage of demos under a Studio Notes
 * title states something untrue. Everything else the table asks of that row is
 * unchanged — latest first, 16:9, carousel dots, Crystal's portrait beside it.
 *
 * A consequence still to put to the client: the featured film is also the first
 * card in the demos rail below, so it appears twice. That is inherent to
 * featuring a film from a row that also lists it. If she would rather the rail
 * skipped it, that is a filter on the rail.
 *
 * Nothing on this page is invented. A section with no published films shows one
 * sentence saying so; the featured block is not printed at all when there are no
 * demos, rather than standing empty the way the prototype's did for weeks. The
 * prototype's sample tiles and its [PLACEHOLDER] banner are not ported.
 */

const CAS_FV_POST_TYPE    = 'cas_free_video';
const CAS_FV_META_URL     = '_cas_free_video_url';
const CAS_FV_META_SECTION = '_cas_free_video_section';

/**
 * Bunny Stream library 734616, the one this site's films are encoded into. It is
 * needed only when a bare video id is pasted rather than a full player URL — a
 * URL carries its own library id and that one is used instead.
 */
const CAS_FV_BUNNY_LIBRARY = '734616';


/* ------------------------------------------------------------------
   Structure: the sections, which are not data
   ------------------------------------------------------------------ */

/**
 * The three sections of section 15, in the order the chapter lists them. The
 * key is what is stored against a film; the heading is what a visitor reads.
 * They are deliberately different strings — renaming a heading must not orphan
 * every film filed under it.
 */
function cas_fv_sections() {
	return array(
		'demos'  => array(
			'heading'  => 'Step-by-step demos',
			'label'    => 'Step-by-step demos',
			'aria'     => 'Step-by-step demos, scrollable list of videos',
			'vertical' => false,
			'setting'  => 'cas_fv_intro_demos',
		),
		'quick'  => array(
			'heading'  => 'Quick inspiration &amp; bite-sized tips',
			'label'    => 'Quick inspiration & bite-sized tips',
			'aria'     => 'Quick inspiration and bite-sized tips, scrollable list of videos',
			'vertical' => false,
			'setting'  => 'cas_fv_intro_quick',
		),
		// Section three was never drawn on the board and has no agreed name yet;
		// `Shorts` is the developer's placeholder and the alternatives on offer
		// are Reels, Sketchbook Moments and In the Studio. It is the only 9:16
		// section (1.4.12).
		'shorts' => array(
			'heading'  => 'Shorts',
			'label'    => 'Shorts',
			'aria'     => 'Shorts, scrollable list of videos',
			'vertical' => true,
			'setting'  => 'cas_fv_intro_shorts',
		),
	);
}

/**
 * Where this page's links to the catalogue point. Tutor's course post type owns
 * the `/courses/` URL, so there is no WordPress page with that slug and
 * cas_page_url() would fall back to the homepage.
 */
function cas_fv_courses_url() {
	$url = get_post_type_archive_link( 'courses' );

	return $url ? $url : home_url( '/courses/' );
}


/* ------------------------------------------------------------------
   Post type and meta
   ------------------------------------------------------------------ */

/**
 * public => false because a free video has no page of its own: it is watched in
 * the player on this page, or on YouTube. A single-video permalink would be an
 * empty room, and for the YouTube films it would compete with Crystal's own
 * channel in search results.
 */
function cas_register_free_video_post_type() {
	register_post_type(
		CAS_FV_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'Free videos',
				'singular_name'      => 'Free video',
				'add_new'            => 'Add video',
				'add_new_item'       => 'Add free video',
				'edit_item'          => 'Edit free video',
				'new_item'           => 'New free video',
				'view_item'          => 'View free video',
				'search_items'       => 'Search free videos',
				'not_found'          => 'No free videos yet',
				'not_found_in_trash' => 'No free videos in the bin',
				'all_items'          => 'All free videos',
				'menu_name'          => 'Free videos',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			// The block editor is the agreed authoring surface for prose
			// (2026-07-26 authoring decision), and it needs the REST route.
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-video-alt3',
			// 21 Artwork, 22 Student work, 24 About photos, 26 FAQ, 27 Testimonials.
			'menu_position'       => 23,
			// 'page-attributes' is what puts the Order box on screen, which is
			// how she decides which film leads a rail and the featured stage.
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'cas_register_free_video_post_type' );

/**
 * Registered as well as saved by the meta box, so the same sanitisers run for
 * WP-CLI and for an importer loading her films in bulk.
 */
function cas_register_free_video_meta() {
	$can_edit = function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta(
		CAS_FV_POST_TYPE,
		CAS_FV_META_URL,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_fv_sanitize_url',
			'auth_callback'     => $can_edit,
		)
	);

	register_post_meta(
		CAS_FV_POST_TYPE,
		CAS_FV_META_SECTION,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_fv_sanitize_section',
			'auth_callback'     => $can_edit,
		)
	);
}
add_action( 'init', 'cas_register_free_video_meta' );

/**
 * One field takes either a player URL or a bare Bunny video id, because the id
 * is what the Bunny dashboard offers to copy and a URL is what the browser bar
 * offers. An id is not a URL and esc_url_raw() would mangle it, so it is
 * matched first.
 */
function cas_fv_sanitize_url( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	if ( preg_match( '~^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$~i', $value ) ) {
		return strtolower( $value );
	}

	// Only http(s) survives. A javascript: URL in a field the client edits would
	// run in every visitor's browser from a link the page tells them to trust.
	return esc_url_raw( $value, array( 'http', 'https' ) );
}

function cas_fv_sanitize_section( $value ) {
	$sections = cas_fv_sections();
	$value    = (string) $value;

	return isset( $sections[ $value ] ) ? $value : 'demos';
}

/**
 * What actually gets embedded, worked out from the one link she pasted. Returns
 * null when the field is empty or the link is not one this page can play — the
 * card is then rendered without a player rather than with a guess.
 *
 * `watch` is never derived for YouTube: it is her own link, and a Short reached
 * through watch?v= opens in the landscape player instead of the Shorts one.
 */
function cas_fv_video( $post_id ) {
	$raw = trim( (string) get_post_meta( $post_id, CAS_FV_META_URL, true ) );
	if ( '' === $raw ) {
		return null;
	}

	// Every form of YouTube URL she is likely to copy: watch, shorts, youtu.be,
	// embed, live.
	if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:[^\s]*&)?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $raw, $m ) ) {
		return array(
			'host' => 'youtube',
			// youtube-nocookie.com is the same player from the domain that sets
			// no tracking cookie until the reader presses play. rel=0 keeps the
			// end cards inside Crystal's own channel; playsinline stops iOS
			// hijacking the page into its own full-screen player, which would
			// defeat the pillarbox.
			'embed' => 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $m[1] ) . '?rel=0&playsinline=1',
			'watch' => $raw,
		);
	}

	// Bunny Stream, as a player URL or as a bare video id.
	if ( preg_match( '~mediadelivery\.net/(?:embed|play)/([0-9]+)/([0-9a-f-]{36})~i', $raw, $m ) ) {
		$library = $m[1];
		$guid    = strtolower( $m[2] );
	} elseif ( preg_match( '~^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$~i', $raw ) ) {
		$library = CAS_FV_BUNNY_LIBRARY;
		$guid    = strtolower( $raw );
	} else {
		return null;
	}

	return array(
		'host'  => 'bunny',
		'embed' => 'https://iframe.mediadelivery.net/embed/' . $library . '/' . $guid . '?autoplay=true',
		// The pull zone allows this site as a referer, so the escape-hatch link
		// works from here and stops working the moment the URL is pasted
		// anywhere else. That is the intended behaviour, not a limitation.
		'watch' => 'https://iframe.mediadelivery.net/play/' . $library . '/' . $guid,
	);
}


/* ------------------------------------------------------------------
   Meta box
   ------------------------------------------------------------------ */

function cas_add_free_video_meta_box() {
	add_meta_box(
		'cas-free-video-details',
		'Video details',
		'cas_render_free_video_meta_box',
		CAS_FV_POST_TYPE,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'cas_add_free_video_meta_box' );

function cas_render_free_video_meta_box( $post ) {
	wp_nonce_field( 'cas_save_free_video_details', 'cas_free_video_nonce' );

	$url     = get_post_meta( $post->ID, CAS_FV_META_URL, true );
	$section = cas_fv_sanitize_section( get_post_meta( $post->ID, CAS_FV_META_SECTION, true ) );
	?>
	<p>
		<label for="cas-fv-section"><strong>Section</strong></label><br>
		<select id="cas-fv-section" name="cas_free_video_section" class="widefat">
			<?php foreach ( cas_fv_sections() as $key => $spec ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $section, $key ); ?>><?php echo esc_html( $spec['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description">
			Which row on the Free videos page this film sits in. Shorts is the
			upright row; the other two are landscape.
		</span>
	</p>
	<p>
		<label for="cas-fv-url"><strong>Video link</strong></label><br>
		<input type="text" id="cas-fv-url" name="cas_free_video_url" class="widefat"
			placeholder="https://www.youtube.com/watch?v=&hellip;"
			value="<?php echo esc_attr( $url ); ?>">
		<span class="description">
			Paste the YouTube link exactly as it appears in the browser &mdash; a
			<code>watch</code> link for a full video, a <code>shorts</code> link for a
			Short, so it opens in the right player. For a film hosted on this site,
			paste its Bunny Stream link or video id. Leave it empty and the card shows
			the cover photograph badged &ldquo;Coming soon&rdquo;.
		</span>
	</p>
	<p class="description">
		<strong>Cover photograph:</strong> set it under <em>Featured image</em>. A card
		with no cover is not shown on the page at all. Landscape rows want roughly
		1280 &times; 720; Shorts want an upright picture. Any size works, because the
		image is cropped to fit the slot.
	</p>
	<?php
}

function cas_save_free_video_details( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['cas_free_video_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cas_free_video_nonce'] ) ), 'cas_save_free_video_details' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url     = isset( $_POST['cas_free_video_url'] ) ? wp_unslash( $_POST['cas_free_video_url'] ) : '';
	$section = isset( $_POST['cas_free_video_section'] ) ? wp_unslash( $_POST['cas_free_video_section'] ) : 'demos';

	// The registered sanitize_callback runs on the way in, so these land clean.
	update_post_meta( $post_id, CAS_FV_META_URL, $url );
	update_post_meta( $post_id, CAS_FV_META_SECTION, $section );
}
add_action( 'save_post_' . CAS_FV_POST_TYPE, 'cas_save_free_video_details' );

/**
 * Both of the omissions that make a film vanish or refuse to play are invisible
 * from the list screen, so the list names them.
 */
function cas_free_video_admin_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );

	$columns['cas_fv_section'] = 'Section';
	$columns['cas_fv_video']   = 'Video';
	$columns['cas_fv_cover']   = 'Cover';

	return array_merge( $columns, $date );
}
add_filter( 'manage_' . CAS_FV_POST_TYPE . '_posts_columns', 'cas_free_video_admin_columns' );

function cas_free_video_admin_column( $column, $post_id ) {
	if ( 'cas_fv_section' === $column ) {
		$sections = cas_fv_sections();
		$key      = cas_fv_sanitize_section( get_post_meta( $post_id, CAS_FV_META_SECTION, true ) );
		echo esc_html( $sections[ $key ]['label'] );
	} elseif ( 'cas_fv_video' === $column ) {
		$raw   = trim( (string) get_post_meta( $post_id, CAS_FV_META_URL, true ) );
		$video = cas_fv_video( $post_id );
		if ( '' === $raw ) {
			echo 'Missing &mdash; card reads &ldquo;Coming soon&rdquo;';
		} elseif ( ! $video ) {
			echo 'Not recognised &mdash; will not play';
		} else {
			echo ( 'youtube' === $video['host'] ) ? 'YouTube' : 'This site';
		}
	} elseif ( 'cas_fv_cover' === $column ) {
		echo has_post_thumbnail( $post_id ) ? 'Set' : 'Missing &mdash; not shown on the page';
	}
}
add_action( 'manage_' . CAS_FV_POST_TYPE . '_posts_custom_column', 'cas_free_video_admin_column', 10, 2 );


/* ------------------------------------------------------------------
   Customizer: the four paragraphs on the page that are prose
   ------------------------------------------------------------------ */

/**
 * The lede is the whole first line of Crystal's `FREE VIDEOS.docx`, and section
 * 15 says the first two sections' introductions come from the same file, so all
 * three of those are her copy. The third section's is the developer's, written
 * when the section was added in the meeting. Headings, the eyebrow and the
 * closing band stay in code: they are structure.
 *
 * Defaults are the live shipped values, not empty, so the panel shows what is
 * being replaced.
 */
function cas_fv_intro_default( $key ) {
	switch ( $key ) {
		case 'lead':
			return 'Free painting video: including short Reels, lessons, tutorials and painting tips.';
		case 'demos':
			return 'Complete tutorials, free to watch &mdash; no sign-up, no preview cut short. Paint along at your own pace, then pick up the <a href="' . esc_url( cas_fv_courses_url() ) . '">step-by-step courses</a> when you want the slow version.';
		case 'quick':
			return 'Short on time? Browse our collection of quick painting tips, colour-mixing secrets, and plein air mini-demos. Perfect for a daily dose of creative inspiration or a quick refresh between paintings &mdash; 100% free, no sign-up needed.';
		case 'shorts':
			return 'Quick vertical clips from the studio and from location, the same ones that go out on Instagram and YouTube Shorts.';
	}

	return '';
}

/**
 * Registered straight into the Pages panel rather than through
 * inc/cas-customizer-map.php, which is shared and re-parents sections other
 * files registered. Inside that panel the three pages built last run in nav order:
 * Showcase 60, Blog 70, Free videos 80.
 */
function cas_fv_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'cas_page_free_videos',
		array(
			'title'       => 'Free videos',
			'panel'       => 'cas_pages',
			'priority'    => 80,
			'description' => 'The films themselves are in Dashboard &rarr; Free videos. Each one takes a title, a cover photograph, a link and a section.',
		)
	);

	$fields = array(
		'cas_fv_lead'         => array( 'lead', 'Opening line', 'Reads under the banner, above the featured film.' ),
		'cas_fv_intro_demos'  => array( 'demos', 'Step-by-step demos &mdash; introduction', 'The paragraph under that row&rsquo;s heading.' ),
		'cas_fv_intro_quick'  => array( 'quick', 'Quick inspiration &mdash; introduction', 'The paragraph under that row&rsquo;s heading.' ),
		'cas_fv_intro_shorts' => array( 'shorts', 'Shorts &mdash; introduction', 'The paragraph under that row&rsquo;s heading.' ),
	);

	foreach ( $fields as $setting => $spec ) {
		list( $key, $label, $help ) = $spec;

		$wp_customize->add_setting(
			$setting,
			array(
				'default'           => cas_fv_intro_default( $key ),
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			$setting,
			array(
				'label'       => $label,
				'description' => $help,
				'section'     => 'cas_page_free_videos',
				'type'        => 'textarea',
			)
		);
	}
}
add_action( 'customize_register', 'cas_fv_customize_register' );

/**
 * Emptying a paragraph in the Customizer means it is not printed. That is safe
 * for prose in a way it is not for an image src, so get_theme_mod()'s habit of
 * returning the stored empty string in preference to the default is what is
 * wanted here.
 */
function cas_fv_intro( $setting, $key ) {
	return trim( (string) get_theme_mod( $setting, cas_fv_intro_default( $key ) ) );
}


/* ------------------------------------------------------------------
   Front end
   ------------------------------------------------------------------ */

add_shortcode( 'cas_free_videos', 'cas_render_free_videos' );

/**
 * @param string $section One of the keys in cas_fv_sections().
 * @return WP_Post[]
 */
function cas_fv_films( $section ) {
	return get_posts(
		array(
			'post_type'   => CAS_FV_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_key'    => CAS_FV_META_SECTION, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'  => $section,            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			// Menu order is her own curation; date only breaks ties between films
			// she left at 0, newest first, which is what section 15 asks of the
			// featured stage.
			'orderby'     => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		)
	);
}

function cas_render_free_videos() {
	$sections = cas_fv_sections();
	$films    = array();
	foreach ( $sections as $key => $spec ) {
		$films[ $key ] = cas_fv_films( $key );
	}

	$lead = cas_fv_intro( 'cas_fv_lead', 'lead' );

	ob_start();

	// The helper's contract is not visible from this file, so a banner that is
	// printed and a banner that is returned are both handled. Guarded because a
	// helper that failed to load should cost the banner, not the whole page.
	if ( function_exists( 'cas_page_hero' ) ) {
		// The title runs larger here than the six-page default.
		$hero = cas_page_hero( 'Watch, then paint along', 'Free videos', 'clamp(49px,6.5vw,88px)' );
		if ( is_string( $hero ) ) {
			echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the theme's own helper.
		}
	}

	if ( '' !== $lead ) :
		?>
		<div class="container page-lead-wrap"><p class="page-lead"><?php echo wp_kses_post( $lead ); ?></p></div>
		<?php
	endif;
	?>

	<main class="container" id="content" tabindex="-1" style="padding-bottom:70px;">

		<?php echo cas_fv_featured( $films['demos'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in the helper. ?>

		<?php foreach ( $sections as $key => $spec ) : ?>
			<section class="fv-sec">
				<h2 class="font-a"><?php echo wp_kses_post( $spec['heading'] ); ?></h2>
				<?php
				$intro = cas_fv_intro( $spec['setting'], $key );
				if ( '' !== $intro ) :
					?>
					<p class="fv-sec-intro"><?php echo wp_kses_post( $intro ); ?></p>
					<?php
				endif;

				$cards = '';
				foreach ( $films[ $key ] as $film ) {
					$cards .= cas_fv_card( $film, $spec['vertical'] );
				}

				if ( '' === $cards ) :
					?>
					<p class="fv-sec-intro fv-empty">Nothing published in this section yet.</p>
					<?php
				else :
					?>
					<div class="fv-rail">
						<div class="fv-grid<?php echo $spec['vertical'] ? ' is-vertical' : ''; ?>" role="group" tabindex="0" aria-label="<?php echo esc_attr( $spec['aria'] ); ?>"><?php
							echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in cas_fv_card().
						?></div>
					</div>
					<?php
				endif;
				?>
			</section>
		<?php endforeach; ?>

		<section class="section" style="margin-top:54px;">
			<div class="band-forest reveal-one">
				<div style="flex:1;">
					<div class="eyebrow"><span class="star">&#10022;</span> Enjoyed the free lessons?</div>
					<h2 class="marker-title" style="font-size:clamp(31px,3.1vw,44px);">The full courses go deeper.</h2>
					<p style="margin:0;max-width:60ch;">Complete slow-paced series with real reference material, downloadable notes, and room for your questions.</p>
				</div>
				<a class="btn-big" href="<?php echo esc_url( cas_fv_courses_url() ); ?>" style="flex:none;">Browse all courses &rarr;</a>
			</div>
		</section>

	</main>

	<?php
	cas_fv_modal();

	return ob_get_clean();
}

/**
 * The featured stage. Every demo is rendered into it and all but the first are
 * hidden; the script builds the dots and swaps which one is shown, so with no
 * script the newest demo still plays and there are no dead dots beneath it.
 *
 * Nothing is printed when there are no demos. The prototype fell back to an
 * empty grey box reading "The latest free video will play here", which is what
 * the live page actually showed for weeks.
 */
function cas_fv_featured( $films ) {
	$cards = '';
	$index = 0;
	foreach ( $films as $film ) {
		$card = cas_fv_card( $film, false, ( 0 !== $index ) );
		if ( '' === $card ) {
			continue;
		}
		$cards .= $card;
		++$index;
	}

	if ( '' === $cards ) {
		return '';
	}

	ob_start();
	?>
	<section class="fv-sec fv-featured" style="margin-top:26px;">
		<div class="fv-feat-head">
			<?php echo cas_fv_portrait(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url() in the helper. ?>
			<h2 class="font-a">Step-by-step demos from Crystal Lau</h2>
		</div>
		<div class="fv-feat-stage" id="fv-feat"><?php
			echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in cas_fv_card().
		?></div>
		<div class="fv-feat-dots" id="fv-feat-dots"></div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * Crystal's portrait beside the featured heading, which section 15 asks for. It
 * is the theme's own file rather than a Customizer field: the same photograph is
 * used on About and on the lesson pages, and it is a brand mark here rather than
 * a slot with a picture in it. The file check keeps a broken image out of the
 * heading if the asset was not copied across.
 */
function cas_fv_portrait() {
	$dir = get_stylesheet_directory();
	if ( ! file_exists( $dir . '/img/ProfilePic-96.jpg' ) ) {
		return '';
	}

	$uri    = get_stylesheet_directory_uri();
	$srcset = '';
	// The disc is about 53px at its largest, so 96 and 160 are the only rungs a
	// screen can use; the 384 and 640 files exist for About's portrait.
	if ( file_exists( $dir . '/img/ProfilePic-160.jpg' ) ) {
		$srcset = ' srcset="' . esc_url( $uri . '/img/ProfilePic-96.jpg' ) . ' 96w, '
			. esc_url( $uri . '/img/ProfilePic-160.jpg' ) . ' 160w" sizes="53px"';
	}

	return '<img class="fv-feat-avatar" src="' . esc_url( $uri . '/img/ProfilePic-96.jpg' ) . '"' . $srcset
		. ' alt="Crystal Lau" width="96" height="96" loading="lazy" decoding="async">';
}

/**
 * One card. Returns an empty string for a film with no cover photograph.
 *
 * That is the rule the generated prototype data broke: build-data.py emitted a
 * null poster for every YouTube film on the reasoning that the embed brings its
 * own thumbnail. True of the player once it opens, false of the card in the
 * rail, and twelve empty dark rectangles shipped. A card IS its photograph.
 *
 * @param WP_Post $film
 * @param bool    $vertical True for the 9:16 section, which sits in a narrower
 *                          column and so asks for a smaller rung of the ladder.
 * @param bool    $hidden   True for the featured stage's off-screen cards.
 */
function cas_fv_card( $film, $vertical = false, $hidden = false ) {
	$thumb_id = get_post_thumbnail_id( $film );
	if ( ! $thumb_id ) {
		return '';
	}

	$title = get_the_title( $film );
	$video = cas_fv_video( $film->ID );

	// A one-line blurb under the title, clamped to three lines by the stylesheet.
	// Rendered as text rather than as blocks: the card has room for a sentence,
	// and the same string is read out again in the player's caption.
	$blurb = trim( wp_strip_all_tags( do_blocks( $film->post_content ) ) );

	$sizes = $vertical
		? '(max-width: 620px) 52vw, (max-width: 900px) 31vw, (max-width: 1440px) 19vw, 270px'
		: '(max-width: 620px) 84vw, (max-width: 900px) 45vw, (max-width: 1440px) 31vw, 440px';

	$img = wp_get_attachment_image(
		$thumb_id,
		'large',
		false,
		array(
			'sizes' => $sizes,
			'alt'   => $title,
		)
	);

	$attr = ' data-host="' . esc_attr( $video ? $video['host'] : '' ) . '"'
		. ' data-embed="' . esc_attr( $video ? $video['embed'] : '' ) . '"'
		. ( $vertical ? ' data-vertical="1"' : '' )
		. ( $hidden ? ' hidden' : '' );

	// A tile is a link only when there is a film behind it, and that link is a
	// real destination so the card still goes somewhere with no script. A film
	// with no link is not a control at all: no invented href, and no dead end
	// behind a click.
	$open  = $video
		? '<a class="fv-card" href="' . esc_url( $video['watch'] ) . '"' . $attr . '>'
		: '<span class="fv-card"' . $attr . '>';
	$close = $video ? '</a>' : '</span>';

	// No run time is stored for either host, so no badge is printed rather than
	// an invented one. "Coming soon" belongs only on a tile with nothing behind
	// it — badging a film that plays perfectly well would be a lie.
	$badge = $video ? '' : '<span class="fv-dur">Coming soon</span>';

	return $open
		. '<span class="fv-thumb">' . $img
			. ( $video ? '<span class="fv-playbtn"><span>&#9654;</span></span>' : '' )
			. $badge
		. '</span>'
		. '<h3>' . esc_html( $title ) . '</h3>'
		. ( '' !== $blurb ? '<p>' . esc_html( $blurb ) . '</p>' : '' )
		. $close;
}

/**
 * The player, printed once per page. It carries no film of its own; the script
 * fills it from the card that opened it, so there is a single source for every
 * value and no chance of the card and the player disagreeing.
 */
function cas_fv_modal() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	?>
	<div class="fv-modal" id="fv-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="fvm-title">
		<div class="fvm-backdrop" data-close></div>
		<div class="fvm-panel">
			<button class="fvm-close" type="button" data-close aria-label="Close video">&#10005;</button>
			<div class="fvm-stage" id="fvm-stage">
				<!-- Deliberately shipped with NO src, and the src is removed again on
				     close. Do not "fix" it by adding src="" — an empty src makes the
				     browser request the page itself and leaves a broken image behind
				     the player. It is the backdrop the player paints over. -->
				<img id="fvm-poster" alt="" style="display:none;">
				<!-- The class name is the stylesheet's hook and it is shared with the
				     course and lesson players, so it keeps its name even though this
				     frame carries a YouTube or a Bunny embed rather than the Drive one
				     it was named for. `title` here is a fallback only: the script
				     replaces it with the film's own title on every open, because an
				     embedded player is a tab stop and its accessible name is the only
				     thing that tells one film from another. `allow` lists what both
				     players need — autoplay and fullscreen for either, encrypted-media
				     and picture-in-picture for YouTube. -->
				<iframe class="gdrive-frame" id="fvm-frame" title="Video player"
					allow="autoplay; fullscreen; encrypted-media; picture-in-picture"
					referrerpolicy="strict-origin-when-cross-origin"
					allowfullscreen loading="lazy"></iframe>
			</div>
			<!-- The only affordance a reader has when the embed is blocked, and a
			     school or office network blocks YouTube outright. It is its own row,
			     and it is not hidden on a phone: that is exactly where it is needed. -->
			<p class="fvm-note" id="fvm-note"></p>
			<div class="fvm-body"><h3 id="fvm-title"></h3><p id="fvm-desc"></p></div>
		</div>
	</div>
	<?php
}


/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * Enqueued from this file rather than from functions.php, the way the courses
 * archive does it.
 *
 * The page CSS is a port of the block that lived in the prototype's own <style>
 * element — the same move css/cas-home.css records for the homepage. It is not
 * new design: without it the rails, the player and the featured stage have no
 * rules at all in industrial-1/2/3.css.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$post = get_post();
		$here = is_page( 'free-videos' )
			|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'cas_free_videos' ) );

		if ( ! $here ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_style(
			'cas-free-videos',
			$uri . '/css/cas-free-videos.css',
			array( 'cas-industrial-3' ),
			filemtime( $dir . '/css/cas-free-videos.css' )
		);

		wp_enqueue_script(
			'cas-free-videos',
			$uri . '/js/cas-free-videos.js',
			array(),
			filemtime( $dir . '/js/cas-free-videos.js' ),
			true
		);
	},
	27
);
