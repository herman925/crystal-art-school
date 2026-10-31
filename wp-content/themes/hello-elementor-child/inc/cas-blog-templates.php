<?php
/**
 * The Blog list page and the single article page.
 *
 * `inc/cas-blog.php` is the fence — the block whitelist from direction document
 * section 12.2. This file is the field: the two templates that whitelist has been
 * governing bodies for, with no page to display them on.
 *
 * Two shortcodes, because a post is not a Page:
 *
 *   [cas_blog]      goes in the Blog Page's content, like every other page here.
 *   [cas_blog_post] goes in an Elementor Theme Builder "Single Post" template. It
 *                   reads the post being viewed, so it takes no attributes.
 *
 * WHY THE ARTICLE IS NOT DATA-DRIVEN. The prototype's `guide` layout is built from
 * a JS object with `intro`, `hero`, `sections[]` and `items[]`. That shape cannot
 * survive the 2026-07-26 authoring decision: post CONTENT is Gutenberg, post
 * TEMPLATES are ours, no Block Patterns, no Custom HTML, and every construct must
 * be reachable from the plain toolbar. So Crystal types a Heading, an Image and a
 * List, over and over — and this file turns that stream of blocks back into the
 * prototype's markup: the first paragraph becomes the lede, each `<h2>` opens a
 * `.bp-sec`, and the headings become the table of contents. The transform is in
 * cas_blog_sectionise(); nothing is asked of her that the toolbar does not offer.
 *
 * The numbered items are the one place the markup had to be translated rather than
 * copied. The prototype drew each number in its own `.bp-n` div from a data field;
 * a List block has no such field, so the number is the list's own marker, drawn by
 * `::before` with `counter(list-item)` — which honours the List block's "Start
 * value" control, and that is how one guide numbers 1 to 21 across six sections.
 *
 * The CSS and JS below are inline on purpose, not by preference. Everything the
 * blog needs beyond css/industrial-*.css lived in a page-scoped <style> block in
 * the prototype, and the house pattern for that is a css/cas-<page>.css file (see
 * the header of css/cas-home.css). This build owned one file, so both ride on
 * fileless handles the way cas-about.php does. Move them to css/cas-blog.css and
 * js/cas-blog.js when someone owns those files; nothing else has to change.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Section 12.2: Load More has no ceiling and loads six at a time. The note under
 * that line records that six is a developer default and not a number Crystal asked
 * for, so it is one constant and it is safe to change.
 */
const CAS_BLOG_PER_PAGE = 6;

/**
 * The developer's note. See cas_blog_devnote_meta_box() for what it is for.
 */
const CAS_BLOG_META_DEVNOTE = '_cas_blog_devnote';


/* ------------------------------------------------------------------
   Developer's note — per post
   ------------------------------------------------------------------ */

/**
 * An amber callout at the top of an article, declaring on the page itself that its
 * copy was edited before the author signed the edits off.
 *
 * This exists because of a real event, not as a nicety. Crystal's three posts were
 * transcribed from her documents and corrected on the way in — 留白膠 had been
 * machine-translated as "blank residue glue" and is masking fluid, Windsor became
 * Winsor, Classens became Claessens (CLAUDE.md, 2026-07-26). Those corrections are
 * live. Until she confirms them, the page has to say so where the reader is, and
 * docs/HANDOFF.md lists an article going live with the note still showing as a
 * launch blocker. Clear the field to clear the note; there is nothing else to undo.
 *
 * Registered rather than only saved, so the sanitiser also runs for WP-CLI and for
 * an importer — which is how these three posts will arrive.
 */
function cas_blog_register_devnote_meta() {
	register_post_meta(
		'post',
		CAS_BLOG_META_DEVNOTE,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'cas_blog_register_devnote_meta' );

function cas_blog_add_devnote_meta_box() {
	add_meta_box(
		'cas-blog-devnote',
		'Developer&rsquo;s note',
		'cas_blog_devnote_meta_box',
		'post',
		'side',
		'low'
	);
}
add_action( 'add_meta_boxes', 'cas_blog_add_devnote_meta_box' );

function cas_blog_devnote_meta_box( $post ) {
	wp_nonce_field( 'cas_save_blog_devnote', 'cas_blog_devnote_nonce' );
	?>
	<p>
		<?php
		// The meta box id is already `cas-blog-devnote` on the postbox wrapper, so the
		// field takes its own id — a duplicate id left this label pointing at the box.
		?>
		<label class="screen-reader-text" for="cas-blog-devnote-field">Developer&rsquo;s note</label>
		<textarea id="cas-blog-devnote-field" name="cas_blog_devnote" class="widefat" rows="4"
			><?php echo esc_textarea( get_post_meta( $post->ID, CAS_BLOG_META_DEVNOTE, true ) ); ?></textarea>
		<span class="description">
			Shown to <strong>visitors</strong>, in an amber box at the top of the article.
			Use it to declare an edit the author has not approved yet. Empty the box and
			the callout disappears.
		</span>
	</p>
	<?php
}

function cas_blog_save_devnote( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['cas_blog_devnote_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cas_blog_devnote_nonce'] ) ), 'cas_save_blog_devnote' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// The registered sanitize_callback runs on the way in, so this lands clean.
	update_post_meta( $post_id, CAS_BLOG_META_DEVNOTE, isset( $_POST['cas_blog_devnote'] ) ? wp_unslash( $_POST['cas_blog_devnote'] ) : '' );
}
add_action( 'save_post_post', 'cas_blog_save_devnote' );

/**
 * A note is visitor-facing and invisible from the posts list, which is exactly the
 * combination that ships one. The column makes it countable before launch.
 */
function cas_blog_admin_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );

	$columns['cas_devnote'] = 'Dev note';

	return array_merge( $columns, $date );
}
add_filter( 'manage_post_posts_columns', 'cas_blog_admin_columns' );

function cas_blog_admin_column( $column, $post_id ) {
	if ( 'cas_devnote' === $column ) {
		echo ( '' === (string) get_post_meta( $post_id, CAS_BLOG_META_DEVNOTE, true ) )
			? '&mdash;'
			: 'Showing on the article';
	}
}
add_action( 'manage_post_posts_custom_column', 'cas_blog_admin_column', 10, 2 );


/* ------------------------------------------------------------------
   Customizer
   ------------------------------------------------------------------ */

/**
 * Registered straight into the Pages panel rather than through
 * inc/cas-customizer-map.php: that file re-parents sections other files own, and
 * every module that edits it is a merge conflict waiting to happen.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_blog',
			array(
				'title'       => 'Blog',
				'panel'       => 'cas_pages',
				'priority'    => 70,
				'description' => 'The articles themselves are written in Dashboard &rarr; Posts. Their topic labels come from the post&rsquo;s category.',
			)
		);

		$wp_customize->add_setting(
			'cas_blog_intro',
			array(
				'default'           => cas_blog_intro_default(),
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'cas_blog_intro',
			array(
				'label'       => 'Introduction',
				'section'     => 'cas_page_blog',
				'type'        => 'textarea',
				'description' => 'The line under the banner. Leave it empty to show no introduction.',
			)
		);

		$wp_customize->add_setting(
			'cas_blog_bio',
			array(
				'default'           => cas_blog_bio_default(),
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			'cas_blog_bio',
			array(
				'label'       => 'Author bio',
				'section'     => 'cas_page_blog',
				'type'        => 'textarea',
				'description' => 'The one-line bio in the article author card. Use Crystal&rsquo;s own wording; leave it empty to show only her name and link.',
			)
		);
	}
);

/**
 * The prototype's sentence, minus its last clause. That clause promised "the
 * most-read posts pinned on the home page", and there is no such thing — no
 * most-read anywhere on the site (the fabricated read counters were deleted on
 * 2026-07-26) and no blog section on the homepage. Shipping the default with a
 * false half in it would put the untrue statement on the page before anyone opened
 * the Customizer. This is developer copy, not the client's; the Gallery
 * introduction next door is hers, and is quoted whole for that reason.
 */
function cas_blog_intro_default() {
	return 'Notes on materials, technique and the slow practice of drawing &mdash; filterable by topic.';
}

/**
 * The bio starts with the sentence already supplied for About. Removing the name
 * keeps the author card from saying "Crystal Lau" twice; the words remain Crystal's
 * own rather than a second invented description of her work.
 */
function cas_blog_bio_default() {
	$fields  = function_exists( 'cas_about_fields' ) ? cas_about_fields() : array();
	$default = isset( $fields['cas_about_intro']['default'] ) ? (string) $fields['cas_about_intro']['default'] : '';
	$intro   = (string) get_theme_mod( 'cas_about_intro', $default );
	$intro   = wp_strip_all_tags( $intro );
	$intro   = html_entity_decode( $intro, ENT_QUOTES, 'UTF-8' );

	foreach ( array( 'I’m Crystal Lau, ', "I'm Crystal Lau, " ) as $prefix ) {
		if ( 0 === strpos( $intro, $prefix ) ) {
			return trim( substr( $intro, strlen( $prefix ) ) );
		}
	}

	return trim( $intro );
}

/**
 * The same featured image is cropped into several article/list slots. Tell the
 * editor the two fixed ratios that matter on the single-post page rather than
 * making her infer them from the front end.
 */
function cas_blog_admin_thumbnail_hint( $content, $post_id ) {
	$post_type = $post_id ? get_post_type( $post_id ) : '';
	if ( ! $post_type && function_exists( 'get_current_screen' ) ) {
		$screen     = get_current_screen();
		$post_type = $screen ? $screen->post_type : '';
	}

	if ( 'post' !== $post_type ) {
		return $content;
	}

	return $content . '<p class="description cas-blog-image-hint">Recommended image size: 1600 &times; 300px for the article banner (48:9) or 904 &times; 678px for Keep reading cards (4:3); the same image is cropped to fit the other blog cards.</p>';
}
add_filter( 'admin_post_thumbnail_html', 'cas_blog_admin_thumbnail_hint', 10, 2 );


/* ------------------------------------------------------------------
   Post helpers
   ------------------------------------------------------------------ */

/**
 * The topic shown on a card, on the ribbon and in the filter chips.
 *
 * Section 12.1 puts 文章分類 — the post's category — on the ribbon, so the source is
 * the core taxonomy Crystal already has an editor for. The default category is
 * skipped: WordPress assigns it to any post left uncategorised, and "Uncategorized"
 * printed on a bookmark ribbon is a WordPress artefact being published as a label.
 * A post with no real category renders with no topic rather than with a wrong one.
 *
 * @return string Category name, or '' when the post carries only the default.
 */
function cas_blog_topic( $post ) {
	$default = (int) get_option( 'default_category' );

	foreach ( (array) get_the_category( $post->ID ) as $term ) {
		if ( (int) $term->term_id !== $default ) {
			return $term->name;
		}
	}

	return '';
}

/**
 * Reading time, derived from the post's own words.
 *
 * Derived and not typed, deliberately. The counters that used to sit in this slot
 * were invented numbers and were removed site-wide; a word count is a measurement
 * of the article in front of the reader, so there is nothing to make up and nothing
 * to keep in step. 200 words a minute is the conventional figure.
 *
 * Counted per script, because Crystal writes in Chinese. Chinese is written
 * without spaces, so str_word_count() saw one "word" in a whole paragraph and
 * every Chinese article read "1 min". CJK is therefore measured in characters at
 * 300 a minute, and everything else in words at 200 — the two are added, so a
 * post that mixes them is counted once rather than twice.
 */
function cas_blog_reading_minutes( $post ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) );

	$cjk_class = '[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]';

	$chars = (int) preg_match_all( '/' . $cjk_class . '/u', $text );
	// Removed rather than left in place, so a CJK run does not also read as a word.
	$words = (int) preg_match_all( '/[\p{L}\p{N}]+/u', (string) preg_replace( '/' . $cjk_class . '/u', ' ', $text ) );

	return max( 1, (int) ceil( $words / 200 + $chars / 300 ) );
}

/**
 * Newest first — 12.1「最新焦點文章按發布時間自動更換」.
 *
 * @return WP_Post[]
 */
function cas_blog_posts() {
	return get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);
}

/**
 * The picture for a card. Returns '' when the post has no featured image, and the
 * caller renders the card without one — a blog card is mostly text and still reads,
 * which is why this does not drop the whole card the way the Gallery does.
 *
 * Every slot an image lands in is a fixed ratio with `object-fit: cover` in
 * css/industrial-2.css, so a photograph of any shape crops to the slot instead of
 * changing the layout around it.
 */
function cas_blog_image( $post, $size, $sizes, $eager = false ) {
	$id = get_post_thumbnail_id( $post );
	if ( ! $id ) {
		return '';
	}

	$attrs = array(
		// The title is the next thing inside the same link on every card, so a
		// described photograph says the headline twice per card.
		'alt'      => '',
		'sizes'    => $sizes,
		'decoding' => 'async',
		'loading'  => $eager ? 'eager' : 'lazy',
	);

	if ( $eager ) {
		$attrs['fetchpriority'] = 'high';
	}

	return wp_get_attachment_image( $id, $size, false, $attrs );
}


/* ------------------------------------------------------------------
   The list page
   ------------------------------------------------------------------ */

add_shortcode( 'cas_blog', 'cas_render_blog' );

function cas_render_blog() {
	$posts = cas_blog_posts();

	ob_start();

	// The helper's contract is not visible from this file, so a banner that is
	// printed and a banner that is returned are both handled. Guarded because a
	// helper that failed to load should cost the banner, not the whole page.
	if ( function_exists( 'cas_page_hero' ) ) {
		$hero = cas_page_hero( 'From the studio', 'The journal' );
		if ( is_string( $hero ) ) {
			echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the theme's own helper.
		}
	}

	$intro = (string) get_theme_mod( 'cas_blog_intro', cas_blog_intro_default() );
	if ( '' !== trim( $intro ) ) {
		// The wood banner carries the title and its eyebrow only; the introduction
		// reads below it (client feedback, 2026-08-08).
		echo '<div class="container page-lead-wrap"><p class="page-lead">' . wp_kses_post( $intro ) . '</p></div>';
	}
	?>

	<main class="container" id="content" tabindex="-1" style="padding-bottom:70px;">
		<section class="section" style="padding-top:24px;">
			<?php if ( ! $posts ) : ?>
				<div class="store-empty" role="status">No posts are published yet.</div>
			<?php else : ?>
				<?php cas_blog_render_list( $posts ); ?>
			<?php endif; ?>
		</section>
	</main>
	<?php

	return ob_get_clean();
}

/**
 * Filters, the feature card and the grid.
 *
 * The whole list is rendered server-side and paged in the browser, because the
 * posts are the page: they are Crystal's words and they belong in the HTML, not
 * behind a fetch. Cards past the first six carry `hidden`, and a hidden card's
 * image is never fetched, so 12.2's lazy-load requirement holds however long the
 * list grows.
 *
 * There is one feature card per topic, not one for the page. 12.1 features the
 * newest article; with a topic selected, the newest article of that topic is the
 * newest article on screen, which is what the prototype does. They are all in the
 * markup and all but one carry `hidden` — a feature card is a headline and a
 * photograph, and a hidden photograph is not fetched.
 */
function cas_blog_render_list( $posts ) {
	$feat_sizes = '(max-width: 760px) 92vw, (max-width: 1100px) 50vw, 736px';
	$card_sizes = '(max-width: 560px) 92vw, (max-width: 980px) 46vw, 30vw';

	// Topics, in the order the posts introduce them, so the chips follow the page.
	$topics = array();
	foreach ( $posts as $post ) {
		$topic = cas_blog_topic( $post );
		if ( '' !== $topic && ! in_array( $topic, $topics, true ) ) {
			$topics[] = $topic;
		}
	}
	$keys = array_merge( array( 'All' ), $topics );

	// The newest post under each chip. One post is the newest under several chips —
	// "All" and its own topic, always — so its card is written once and carries
	// every key it answers to.
	$feature_keys = array();
	foreach ( $keys as $key ) {
		foreach ( $posts as $post ) {
			if ( 'All' === $key || cas_blog_topic( $post ) === $key ) {
				$feature_keys[ $post->ID ][] = $key;
				break;
			}
		}
	}

	$active = 'All';
	// An unrecognised ?tag= selects itself and shows the empty state, rather than
	// being ignored and silently showing everything.
	if ( isset( $_GET['tag'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a public, read-only filter.
		$wanted = sanitize_text_field( wp_unslash( $_GET['tag'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $wanted ) {
			$active = $wanted;
			foreach ( $keys as $key ) {
				if ( 0 === strcasecmp( $key, $wanted ) ) {
					$active = $key;
					break;
				}
			}
		}
	}
	?>
	<div class="filter-row" id="blog-filters" role="group" aria-label="Filter posts">
		<?php foreach ( $keys as $key ) : ?>
			<button class="chip<?php echo ( $key === $active ) ? ' active' : ''; ?>" type="button"
				aria-pressed="<?php echo ( $key === $active ) ? 'true' : 'false'; ?>"
				data-tag="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $key ); ?></button>
		<?php endforeach; ?>
	</div>

	<?php
	foreach ( $posts as $post ) :
		if ( ! isset( $feature_keys[ $post->ID ] ) ) {
			continue;
		}
		$for   = $feature_keys[ $post->ID ];
		$topic = cas_blog_topic( $post );
		$link  = get_permalink( $post );
		$title = get_the_title( $post );
		// One feature card is on screen and the rest are hidden alternates for the
		// other chips. 12.2 defers article images, so only the card that is actually
		// visible may take the eager, high-priority fetch; the hidden ones stay lazy
		// and are never fetched at all.
		$on    = in_array( $active, $for, true );
		$img   = cas_blog_image( $post, 'large', $feat_sizes, $on );
		?>
		<div class="blog-feature<?php echo $img ? '' : ' no-photo'; ?>" data-id="<?php echo esc_attr( $post->ID ); ?>"
			data-for="<?php echo esc_attr( implode( '|', $for ) ); ?>"
			<?php echo $on ? '' : 'hidden'; ?>>
			<?php if ( $img ) : ?>
				<?php
				// The picture is decorative beside its own headline, and the link
				// wrapping it duplicates the CTA below, so it leaves the tab order.
				?>
				<a class="bf-img" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup. ?></a>
			<?php endif; ?>
			<div class="bf-body">
				<?php // 12.1 as drawn on the board: 最新標記 · 分類 · 日期. No author, no excerpt. ?>
				<span class="bf-kicker">Latest<?php echo $topic ? ' &middot; ' . esc_html( $topic ) : ''; ?> &middot; <?php echo esc_html( get_the_date( 'M j', $post ) ); ?></span>
				<?php
				// 12.1: the title sits in a debossed frame, pressed into the card rather
				// than painted onto it, so no new background colour is introduced. The
				// two marks are empty spans whose glyphs come from CSS content — chrome,
				// with no field behind them; only the <h2> is content. They own their own
				// grid rows because as pseudo-elements on the <h2> they shared its line
				// box and the closing one collided with the last line of the title.
				?>
				<div class="bf-frame">
					<span class="bf-quote is-open" aria-hidden="true"></span>
					<h2><?php echo str_replace( array( "—", "–" ), array( '<span class="cas-title-dash">—</span>', '<span class="cas-title-dash">–</span>' ), esc_html( $title ) ); ?></h2>
					<span class="bf-quote is-close" aria-hidden="true"></span>
				</div>
				<span class="bf-reads"><?php echo esc_html( cas_blog_reading_minutes( $post ) ); ?> min read</span>
				<a class="bf-cta" href="<?php echo esc_url( $link ); ?>" aria-label="Read the post: <?php echo esc_attr( $title ); ?>">Read the post <span class="arr" aria-hidden="true">&rarr;</span></a>
			</div>
			<?php if ( $topic ) : ?>
				<?php // --rib-len lets the ribbon size its own type to whatever topic it is given. ?>
				<span class="bf-ribbon" aria-hidden="true" style="--rib-len:<?php echo esc_attr( mb_strlen( $topic ) ); ?>"><span><?php echo esc_html( $topic ); ?></span></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>

	<?php
	// What the browser would show for the topic that is on when the page arrives,
	// worked out here as well as in the script. Marking every card `hidden` and
	// leaving the script to reveal six would mean a script that failed to run left
	// a page of no posts at all; this way it costs Load More and nothing else.
	$featured_now = 0;
	foreach ( $feature_keys as $id => $for ) {
		if ( in_array( $active, $for, true ) ) {
			$featured_now = $id;
		}
	}

	$matching = array();
	foreach ( $posts as $post ) {
		if ( $post->ID !== $featured_now && ( 'All' === $active || cas_blog_topic( $post ) === $active ) ) {
			$matching[] = $post->ID;
		}
	}

	$first_page = array_slice( $matching, 0, CAS_BLOG_PER_PAGE );
	$more_left  = count( $matching ) - count( $first_page );
	?>
	<div class="blog-grid" id="blog-grid">
		<?php
		foreach ( $posts as $post ) :
			$topic = cas_blog_topic( $post );
			$img   = cas_blog_image( $post, 'large', $card_sizes );
			?>
			<a class="blog-card" href="<?php echo esc_url( get_permalink( $post ) ); ?>"
				data-id="<?php echo esc_attr( $post->ID ); ?>"
				data-tag="<?php echo esc_attr( $topic ); ?>"
				<?php echo in_array( $post->ID, $first_page, true ) ? '' : 'hidden'; ?>>
				<?php if ( $img ) : ?>
					<div class="bc-img"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup. ?></div>
				<?php endif; ?>
				<div class="bc-body">
					<?php if ( $topic ) : ?>
						<span class="bc-tag"><?php echo esc_html( $topic ); ?></span>
					<?php endif; ?>
					<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
					<p><?php echo esc_html( get_the_excerpt( $post ) ); ?></p>
					<span class="bc-date"><?php echo esc_html( get_the_date( 'M j', $post ) ); ?> &middot; <span class="bc-reads"><?php echo esc_html( cas_blog_reading_minutes( $post ) ); ?> min</span></span>
				</div>
			</a>
		<?php endforeach; ?>
	</div>

	<?php
	// Reachable: an unrecognised ?tag= selects itself, so the list really can be
	// empty. Chips only ever offer topics that have posts.
	$nothing = ( ! $matching && ! $featured_now );
	?>
	<div class="store-empty" id="blog-empty" style="display:<?php echo $nothing ? 'block' : 'none'; ?>;">No posts under that topic yet &mdash; pick another.</div>
	<?php // 12.2: no ceiling — keeps loading six at a time to the last post. ?>
	<div class="blog-more" id="blog-more" style="display:<?php echo $more_left > 0 ? 'flex' : 'none'; ?>;">
		<button class="btn-big" type="button" id="blog-more-btn">Load more<?php echo $more_left > 0 ? ' (' . (int) $more_left . ' to go)' : ''; ?></button>
	</div>
	<?php
}


/* ------------------------------------------------------------------
   The article page
   ------------------------------------------------------------------ */

add_shortcode( 'cas_blog_post', 'cas_render_blog_post' );

function cas_render_blog_post() {
	$post = get_post();

	// The shortcode reads whatever post is being viewed, so it renders nothing
	// anywhere else rather than rendering the Blog Page as an article.
	if ( ! $post || 'post' !== $post->post_type || ! is_singular( 'post' ) ) {
		return '';
	}

	// wp_filter_content_tags() prepends sizes="auto," to lazy images. The related
	// cards use height:100% inside a fixed-ratio box, so keep the explicit slot
	// sizes that were measured for this template.
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	$banner_sizes = '(max-width: 700px) calc(100vw - 32px), (max-width: 1488px) calc(100vw - 48px), 1392px';
	$rel_sizes    = '(max-width: 700px) calc(100vw - 32px), 452px';

	$topic   = cas_blog_topic( $post );
	$devnote = (string) get_post_meta( $post->ID, CAS_BLOG_META_DEVNOTE, true );
	$banner  = cas_blog_image( $post, 'full', $banner_sizes, true );
	$article = cas_blog_sectionise( apply_filters( 'the_content', get_the_content( null, false, $post ) ) );

	ob_start();
	?>
	<div class="container" style="padding-top:28px;">
		<?php
		// Section 20 (2026-08-07 correction) puts Logo2 centred on this page: the
		// exclusion list in 1.4.5 covers the four account and payment pages only, and
		// its placement table lists blog-post under "no wood banner → centred".
		?>
		<div class="logo2 is-center"><img src="<?php echo esc_url( cas_brand_image( 'cas_logo2' ) ); ?>" alt="Crystal Lau Art" decoding="async"></div>
		<?php if ( $banner ) : ?>
			<header class="post-banner">
				<?php // alt="" — the <h1> sits on top of this photograph and says the same thing. ?>
				<?php echo $banner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup. ?>
				<div class="chip-h1"><div class="chip-inner"><h1><?php echo esc_html( get_the_title( $post ) ); ?></h1></div></div>
			</header>
		<?php endif; ?>
	</div>

	<main class="container" id="content" tabindex="-1">
		<div class="article">
			<?php if ( ! $banner ) : ?>
				<?php
				// Without a featured image there is no banner, and the title has nowhere
				// else to be — the page would otherwise have no <h1> at all.
				?>
				<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
			<?php endif; ?>

			<div class="byline">
				<?php echo cas_blog_portrait( 36 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below. ?>
				<?php
				// A post whose author is 0 — which is how the four courses arrived on
				// this site — has no display name, and an empty <strong> beside a
				// portrait reads as a name that failed to load. Nothing is printed
				// instead.
				$author = (string) get_the_author_meta( 'display_name', $post->post_author );
				?>
				<?php if ( '' !== $author ) : ?>
					<strong><?php echo esc_html( $author ); ?></strong>
				<?php endif; ?>
				<span class="hand-note">
					<?php echo esc_html( get_the_date( 'M j Y', $post ) ); ?> &middot;
					<?php echo esc_html( cas_blog_reading_minutes( $post ) ); ?> min read<?php echo $topic ? ' &middot; ' . esc_html( $topic ) : ''; ?>
				</span>
			</div>

			<?php if ( '' !== trim( $devnote ) ) : ?>
				<div class="bp-devnote" role="note">
					<strong>Developer&rsquo;s note</strong>
					<p><?php echo esc_html( $devnote ); ?></p>
				</div>
			<?php endif; ?>

			<?php echo $article; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content, re-serialised by cas_blog_sectionise(). ?>

			<?php cas_blog_author_card(); ?>

			<?php cas_blog_comments( $post ); ?>

			<?php cas_blog_prevnext(); ?>
		</div>

		<?php cas_blog_related( $post, $rel_sizes ); ?>
	</main>
	<?php

	return ob_get_clean();
}

/**
 * The article's author panel. The portrait and name are fixed chrome; the one-line
 * bio is a Pages > Blog Customizer field so Crystal can correct or replace her copy.
 */
function cas_blog_author_card() {
	$bio       = trim( (string) get_theme_mod( 'cas_blog_bio', cas_blog_bio_default() ) );
	$about_url = function_exists( 'cas_page_url' ) ? cas_page_url( 'about' ) : home_url( '/' );
	?>
	<aside class="card blush blog-author-card" aria-labelledby="cas-blog-author-name">
		<?php echo cas_blog_portrait( 72 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below. ?>
		<div>
			<strong id="cas-blog-author-name">Crystal Lau</strong>
			<p>
				<?php if ( '' !== $bio ) : ?>
					<?php echo esc_html( $bio ); ?>
					<span aria-hidden="true"> </span>
				<?php endif; ?>
				<a class="link-squiggle" href="<?php echo esc_url( $about_url ); ?>">More about me <span aria-hidden="true">&rarr;</span></a>
			</p>
		</div>
	</aside>
	<?php
}

/**
 * Native WordPress comments, with the prototype's field labels but without its
 * obsolete membership-tier line. Core owns moderation, nonce fields and the
 * comment_registration login gate; the site's configured honeypot plugin remains
 * in that native submission path.
 */
function cas_blog_comment_form_defaults( $defaults ) {
	$defaults['id_form']       = 'comment-form';
	$defaults['class_form']    = 'comment-form card';
	$defaults['class_submit']  = 'btn primary';
	$defaults['label_submit']  = 'Post comment';
	$defaults['comment_field'] = '<p class="comment-form-comment"><label for="comment-box">Join the conversation <span class="required">*</span></label><textarea id="comment-box" name="comment" class="post-field" rows="3" placeholder="Be kind, be curious&hellip;" required="required"></textarea></p>';

	return $defaults;
}

function cas_blog_comments( $post ) {
	if ( post_password_required( $post ) || ( ! comments_open( $post->ID ) && ! have_comments() ) ) {
		return;
	}

	add_filter( 'comment_form_defaults', 'cas_blog_comment_form_defaults' );
	?>
	<section class="blog-comments" aria-labelledby="cas-blog-comments-title">
		<h2 id="cas-blog-comments-title">Comments</h2>
		<?php comments_template(); ?>
	</section>
	<?php
	remove_filter( 'comment_form_defaults', 'cas_blog_comment_form_defaults' );
}

/**
 * Crystal's portrait, from the theme's own ladder rather than from get_avatar():
 * the avatar service returns a grey silhouette for an account with no Gravatar, and
 * a silhouette beside a byline reads as a broken image rather than as a missing
 * setting. alt="" because the name is the next thing in the byline.
 */
function cas_blog_portrait( $px ) {
	$uri = get_stylesheet_directory_uri() . '/img/';

	return sprintf(
		'<img src="%1$sProfilePic-96.jpg" srcset="%1$sProfilePic-96.jpg 96w, %1$sProfilePic-160.jpg 160w, %1$sProfilePic-384.jpg 384w" sizes="%2$dpx" alt="" width="%2$d" height="%2$d" loading="lazy" decoding="async">',
		esc_url( $uri ),
		(int) $px
	);
}

/**
 * Older on the left, newer on the right — the direction the dates run.
 */
function cas_blog_prevnext() {
	$older = get_previous_post();
	$newer = get_next_post();

	if ( ! $older && ! $newer ) {
		return;
	}
	?>
	<div class="prevnext">
		<?php if ( $older ) : ?>
			<a class="btn ghost" href="<?php echo esc_url( get_permalink( $older ) ); ?>">&larr; <?php echo esc_html( get_the_title( $older ) ); ?></a>
		<?php else : ?>
			<span></span>
		<?php endif; ?>
		<?php if ( $newer ) : ?>
			<a class="btn ghost" href="<?php echo esc_url( get_permalink( $newer ) ); ?>"><?php echo esc_html( get_the_title( $newer ) ); ?> &rarr;</a>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * The three newest posts other than this one. The rail takes one column per card
 * and centres them, so two cards do not leave a card-shaped hole beside them.
 */
function cas_blog_related( $post, $sizes ) {
	$others = get_posts(
		array(
			'post_type'        => 'post',
			'post_status'      => 'publish',
			'numberposts'      => 3,
			'post__not_in'     => array( $post->ID ),
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	if ( ! $others ) {
		return;
	}
	?>
	<section class="section" id="keep-reading">
		<div class="section-head reveal-one" style="text-align:center;">
			<div class="eyebrow" style="justify-content:center;"><span class="star">&#10022;</span> Keep reading</div>
			<h2 class="section-title">More from Crystal</h2>
		</div>
		<div class="related-rail" id="related-rail" style="margin-top:30px;--rel-cols:<?php echo (int) count( $others ); ?>;">
			<?php foreach ( $others as $other ) : ?>
				<a class="cover-card" href="<?php echo esc_url( get_permalink( $other ) ); ?>" style="aspect-ratio:4/3;">
					<?php
					// alt="" — the scrim under it carries the title, which is also this
					// link's accessible name.
					echo cas_blog_image( $other, 'large', $sizes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup.
					?>
					<div class="scrim"><div class="t"><?php echo esc_html( get_the_title( $other ) ); ?></div></div>
				</a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}


/* ------------------------------------------------------------------
   Blocks in, article out
   ------------------------------------------------------------------ */

/**
 * Turns rendered block markup into the prototype's article layout.
 *
 * Three things happen, all of them keyed off what Crystal typed rather than off a
 * setting she would have to remember:
 *
 * 1. The first paragraph becomes the lede.
 * 2. Every top-level `<h2>` opens a `.bp-sec`, which runs to the next `<h2>`. That
 *    is what gives each section its own anchor and its own scroll margin, and it is
 *    why the photograph and the list she puts under a heading belong to it.
 * 3. Two or more sections produce the table of contents. One section is not a
 *    contents list, it is a heading with a link to itself.
 *
 * An essay has no headings, so it gets a lede and nothing else — which is the
 * prototype's second layout, arrived at by having written an essay rather than by
 * having chosen "essay" from a menu.
 *
 * Only direct children are considered. A heading inside a Group or a Column is a
 * heading inside a box, and hoisting it out of that box would take the box apart.
 *
 * If the parse fails the content is returned untouched: an article that is unstyled
 * still reads, an article that is empty does not.
 */
function cas_blog_sectionise( $html ) {
	if ( '' === trim( (string) $html ) ) {
		return '';
	}

	$doc = new DOMDocument();

	$previous = libxml_use_internal_errors( true );
	// The XML declaration is what tells libxml the bytes are UTF-8; without it the
	// parser assumes Latin-1 and mangles every character above 127.
	$ok = $doc->loadHTML(
		'<?xml encoding="utf-8" ?><div class="cas-article-body">' . $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	$wrap = $ok ? $doc->getElementsByTagName( 'div' )->item( 0 ) : null;
	if ( ! $wrap ) {
		return $html;
	}

	$nodes = iterator_to_array( $wrap->childNodes );

	$intro    = '';
	$sections = array();
	$current  = null;
	$lede     = false;
	$used     = array();

	foreach ( $nodes as $node ) {
		$is_h2 = ( XML_ELEMENT_NODE === $node->nodeType && 'h2' === strtolower( $node->nodeName ) );

		if ( $is_h2 ) {
			$title = trim( $node->textContent );

			// The heading's own HTML anchor — the editor's Advanced > HTML anchor box —
			// is the anchor its section gets. It moves from the heading to the section
			// so the same id is not on the page twice, and so a link she wrote to it
			// still lands where she pointed it.
			$anchor = trim( $node->getAttribute( 'id' ) );
			if ( '' !== $anchor ) {
				$node->removeAttribute( 'id' );
			}

			$slug = ( '' !== $anchor ) ? $anchor : sanitize_title( $title );
			if ( '' === $slug ) {
				$slug = 'section';
			}
			// Two headings with the same words would otherwise share one anchor, and
			// both contents entries would go to the first of them.
			$base = $slug;
			$n    = 2;
			while ( in_array( $slug, $used, true ) ) {
				$slug = $base . '-' . $n;
				++$n;
			}
			$used[] = $slug;

			$sections[] = array(
				'slug'  => $slug,
				'title' => $title,
				// Serialised from the node itself. Rebuilding it as a bare <h2> threw
				// away every attribute the block editor writes onto a heading — the
				// colour class or inline style, the alignment class, and anything else
				// she sets — so a heading she styled in Gutenberg arrived unstyled.
				'html'  => $doc->saveHTML( $node ),
			);
			$current = count( $sections ) - 1;
			continue;
		}

		if ( null === $current ) {
			// The first paragraph of the article is the lede. Whitespace text nodes
			// between blocks are not paragraphs and must not consume the class. The
			// class goes on through the DOM and not by rewriting the opening tag: a
			// Gutenberg paragraph can already carry a class, and prepending a second
			// `class` attribute is invalid — the browser keeps the first and the
			// block's own classes vanish.
			if ( ! $lede && XML_ELEMENT_NODE === $node->nodeType && 'p' === strtolower( $node->nodeName ) ) {
				$lede = true;
				$node->setAttribute( 'class', trim( $node->getAttribute( 'class' ) . ' bp-lede' ) );
			}
			$intro .= $doc->saveHTML( $node );
			continue;
		}

		$sections[ $current ]['html'] .= $doc->saveHTML( $node );
	}

	if ( ! $sections ) {
		return $intro;
	}

	$toc = '';
	if ( count( $sections ) > 1 ) {
		$toc = '<nav class="toc" aria-label="Contents"><div class="toc-title">Contents</div><ol>';
		foreach ( $sections as $section ) {
			$toc .= '<li><a href="#' . esc_attr( $section['slug'] ) . '">' . esc_html( $section['title'] ) . '</a></li>';
		}
		$toc .= '</ol></nav>';
	}

	$body = '';
	foreach ( $sections as $section ) {
		// tabindex="-1" so a contents link can put focus in the section it scrolls to,
		// rather than leaving it on the list it was clicked in.
		$body .= '<section class="bp-sec" id="' . esc_attr( $section['slug'] ) . '" tabindex="-1">' . $section['html'] . '</section>';
	}

	return $intro . $toc . $body;
}


/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * True on the Blog list page. Matched on the shortcode rather than on the slug, so
 * renaming the page cannot silently strip its behaviour.
 */
function cas_blog_is_list_page() {
	if ( ! is_page() ) {
		return false;
	}

	return has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'cas_blog' );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$list   = cas_blog_is_list_page();
		$single = is_singular( 'post' );

		if ( ! $list && ! $single ) {
			return;
		}

		// See the file header: these belong in css/cas-blog.css and js/cas-blog.js.
		wp_register_style( 'cas-blog', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-blog' );
		wp_add_inline_style( 'cas-blog', cas_blog_styles() );

		if ( $list ) {
			wp_register_script( 'cas-blog', false, array(), null, true );
			wp_enqueue_script( 'cas-blog' );
			wp_add_inline_script( 'cas-blog', cas_blog_list_script() );
		}
	},
	27
);

/**
 * Everything the blog needs that css/industrial-*.css does not already carry.
 * Ported from the page-scoped <style> blocks in Blog.dc.html and blog-post.dc.html;
 * the comments are theirs, because the reasons are still the reasons.
 */
function cas_blog_styles() {
	return <<<'CSS'
/* A filtered-out or not-yet-loaded card. [hidden] is display:none at UA
   specificity and both of these set display in a class rule, so both have to say
   it again here. */
.blog-card[hidden], .blog-feature[hidden] { display: none; }

.cas-root .blog-card:hover, .cas-root .blog-card:focus-visible { color: var(--ink); }
.cas-root[data-theme="D"] .blog-card:hover, .cas-root[data-theme="D"] .blog-card:focus-visible { color: #f4d2a5; }
.cas-root .blog-card:is(:hover, :focus-visible) h3 { color: inherit; }
.blog-feature .cas-title-dash { font-family: Georgia, serif; font-weight: 700; font-size: .85em; }

/* A featured image is optional in WordPress, and a post without one renders no
   .bf-img — which left the body in the first column of a 1.1fr .9fr grid beside
   601px of nothing (measured). No photograph, no column for one. */
.blog-feature.no-photo { grid-template-columns: 1fr; }

/* 13.5px so the reading time still sits on the same line as .bc-date. The feature
   card's own .bf-reads keeps its larger 14px override in css/industrial-2.css. */
.bc-reads, .bf-reads { display: inline-flex; align-items: center; gap: 5px; font-family: var(--font-cond); font-size: 13.5px; font-weight: 600; letter-spacing: var(--tracking-label); text-transform: uppercase; color: var(--linen-ink); }

/* The banner was 39% clipped between 701px and 1221px: aspect-ratio 48/9 with
   width:auto and min-height:220px made Chrome resolve the WIDTH from the
   min-height — a fixed 1173.3px at every width in that band. A definite width
   takes the inline size back off the aspect ratio. */
.post-banner { width: 100%; }

/* repeat(3, 1fr) left a card-shaped hole whenever the rail had fewer than three
   cards, which it does on a site with three posts. One column per card, centred. */
.related-rail { grid-template-columns: repeat(var(--rel-cols, 3), minmax(0, 452px)); justify-content: center; }
@media (max-width: 700px) { .related-rail { grid-template-columns: minmax(0, 1fr); } }

.bp-lede { font-size: 19px; line-height: 1.65; margin-top: 26px; }

/* Developer's note — TEMPORARY by design. Declares an edit the author has not
   approved. It appears when a post carries one and disappears when the field is
   emptied; see cas_blog_devnote_meta_box(). */
.bp-devnote { border: 1px dashed color-mix(in srgb, var(--coral) 55%, transparent); background: color-mix(in srgb, var(--coral) 7%, var(--surface)); border-radius: var(--radius-card); padding: 16px 20px; margin: 26px 0 8px; }
.bp-devnote strong { font-family: var(--font-cond); font-size: 12.5px; font-weight: 600; letter-spacing: var(--tracking-label); text-transform: uppercase; color: var(--coral); display: block; margin-bottom: 6px; }
.bp-devnote p { margin: 0; font-size: 14.5px; line-height: 1.6; }

/* One section of a guide: its heading, its photograph, its list. */
.bp-sec { margin: 40px 0; scroll-margin-top: 96px; }
.bp-sec:focus { outline: none; }
.bp-sec h2 { margin: 0 0 14px; font-size: 26px; }
.bp-sec figure { margin: 0 0 18px; }
.bp-sec figure img { width: 100%; height: auto; display: block; border-radius: var(--radius-card); }

/* The prototype drew each number in a .bp-n div fed by a data field. A List block
   has no such field, so the number is the list's own marker: counter(list-item) is
   the counter the browser already keeps for an <ol>, which means the List block's
   "Start value" control keeps working and one guide can number 1 to 21 across six
   sections. Contiguous inline content in a flex container becomes a single
   anonymous flex item, so an item reads as number-then-text however she words it. */
.bp-sec ol { margin: 0; padding-left: 0; list-style: none; }
.bp-sec ol > li { display: flex; gap: 18px; margin: 0 0 22px; }
.bp-sec ol > li::before { content: counter(list-item); font-family: var(--font-marker); font-weight: 600; font-size: 34px; line-height: 1; color: var(--coral); flex: none; width: 48px; padding-top: 2px; }
@media (max-width: 640px) { .bp-sec ol > li { gap: 12px; } .bp-sec ol > li::before { width: 36px; font-size: 27px; } }

/* The Quote block is on the 12.2 whitelist and the design system's only quote
   treatment is the prototype's .pull-quote, so the block wears it. */
.article blockquote { font-family: var(--font-marker); font-weight: 600; font-size: clamp(20px,2.2vw,28px); line-height: 1.4; padding: 26px 30px; margin: 34px 0; background-color: #cab389; background-image: var(--oak-grain); background-size: 300px 120px; color: #474138; border-radius: var(--radius-card); position: relative; box-shadow: var(--shadow-sm); }
.article blockquote::before { content: "\2726"; position: absolute; top: -14px; left: 24px; color: var(--coral-deep); font-size: 24px; }
.article blockquote p:last-child { margin-bottom: 0; }

/* Single-post apparatus ported from the prototype. The blush card reuses the
   existing material surface; only its layout belongs to the article template. */
.article .blog-author-card { display: flex; align-items: center; gap: 16px; margin: 40px 0; }
.article .blog-author-card > img { width: 72px; height: 72px; border-radius: 50%; object-fit: cover; flex: none; }
.article .blog-author-card > div { min-width: 0; }
.article .blog-author-card strong { font-family: var(--font-cond); font-weight: 600; }
.article .blog-author-card p { margin: 4px 0 0; font-size: 14px; line-height: 1.5; color: #5e4a3d; }
.article .blog-author-card .link-squiggle { font-weight: 700; color: inherit; }
.article .blog-comments { margin-top: 8px; }
.article .blog-comments > h2 { margin-top: 8px; }
.article .blog-comments .comments-area { margin-top: 8px; }
.article .blog-comments .comment-list { margin: 0; padding: 0; list-style: none; }
.article .blog-comments .comment-list .comment { display: block; margin-bottom: 20px; }
.article .blog-comments .comment-body { background: var(--surface); border-radius: var(--radius-card); box-shadow: var(--shadow-sm); padding: 14px 18px; color: var(--ink); }
.article .blog-comments .comment-meta { font-family: var(--font-cond); font-size: 13px; line-height: 1.4; }
.article .blog-comments .comment-content { font-size: 14.5px; line-height: 1.6; }
.article .blog-comments .comment-content p:last-child { margin-bottom: 0; }
.article .blog-comments .comment-form { margin-top: 8px; }
.article .blog-comments .comment-form label { display: block; margin-bottom: 8px; font-family: var(--font-cond); font-weight: 600; letter-spacing: var(--tracking-label); text-transform: uppercase; font-size: 13px; color: var(--ink); }
.article .blog-comments .comment-form input[type="text"], .article .blog-comments .comment-form input[type="email"], .article .blog-comments .comment-form input[type="url"] { width: 100%; border: 1px solid var(--line); border-radius: var(--r-sharp); background: var(--cream); padding: 12px 14px; font-family: var(--font-body); font-size: 14.5px; color: var(--ink); }
.article .blog-comments .comment-form .form-submit { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.article .blog-comments .comment-form .required { color: var(--coral); }
@media (max-width: 560px) { .article .blog-author-card { align-items: flex-start; } }
CSS;
}

/**
 * Filtering and Load More.
 *
 * Everything is already in the document; this decides what is on screen. Six at a
 * time with no ceiling (12.2), and the feature card swaps with the topic, because
 * the newest article of the selected topic is the newest article on screen.
 */
function cas_blog_list_script() {
	$per_page = (int) CAS_BLOG_PER_PAGE;

	return <<<JS
(function () {
  var filters  = document.getElementById('blog-filters');
  var grid     = document.getElementById('blog-grid');
  var empty    = document.getElementById('blog-empty');
  var moreWrap = document.getElementById('blog-more');
  var moreBtn  = document.getElementById('blog-more-btn');
  if (!filters || !grid) return;

  var PER_PAGE = {$per_page};
  var cards    = [].slice.call(grid.querySelectorAll('.blog-card'));
  var features = [].slice.call(document.querySelectorAll('.blog-feature'));
  var chips    = [].slice.call(filters.querySelectorAll('.chip'));
  var on       = filters.querySelector('.chip.active');
  var active   = on ? on.getAttribute('data-tag') : 'All';
  var shown    = PER_PAGE;

  /* The result of a filter is conveyed by the grid redrawing and by nothing else,
     so it is said out loud as well. One region for the page. */
  var live = document.createElement('div');
  live.className = 'screen-reader-text';
  live.setAttribute('aria-live', 'polite');
  document.body.appendChild(live);
  var say = function (msg) {
    live.textContent = '';
    setTimeout(function () { live.textContent = msg; }, 60);
  };

  var counted = function (n) { return n + (n === 1 ? ' post' : ' posts'); };

  /* Blog-specific roll-in controller. The shared motion pass covers the site's
     other choreography; this controller owns Blog feature/card entry and every
     state change that replaces or reveals list items. */
  var reduce = false;
  try {
    reduce = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  } catch (e) {}
  var g = window.gsap;
  var watch = [];
  var bound = false;

  var drive = function () {
    if (!watch.length || !g) return;
    var vh = window.innerHeight || document.documentElement.clientHeight;
    for (var i = 0; i < watch.length; i++) {
      var w = watch[i];
      if (w.done || !w.el.isConnected || w.el.hidden) continue;
      var r = w.el.getBoundingClientRect();
      if (r.top < vh * 0.9 && r.bottom > 20) {
        w.done = true;
        g.fromTo(w.el,
          { opacity: 0, x: w.left ? -60 : 60, y: 28, rotateZ: w.left ? -3 : 3, transformOrigin: 'center top' },
          { opacity: 1, x: 0, y: 0, rotateZ: 0, duration: .7, ease: 'power3.out' });
      }
    }
  };

  var visibleCards = function () {
    return cards.filter(function (card) { return !card.hidden; });
  };
  var allTargets = function () {
    return features.filter(function (feature) { return !feature.hidden; }).concat(visibleCards());
  };

  var arm = function (items) {
    /* The server keeps filtered cards in the document, so replace any pending
       entry for a target before re-arming it rather than growing duplicates. */
    watch = watch.filter(function (w) {
      return items.indexOf(w.el) < 0 && !w.done && w.el.isConnected;
    });
    if (!items.length) return;
    if (!g || reduce) {
      items.forEach(function (el) {
        el.style.opacity = '1';
        el.style.transform = 'none';
      });
      return;
    }
    g.set(items, { opacity: 0 });
    items.forEach(function (el, i) {
      el.__casM = 1;
      watch.push({ el: el, done: false, left: i % 2 === 0 });
    });
    drive();
    if (!bound) {
      bound = true;
      window.addEventListener('scroll', drive, { passive: true });
      window.addEventListener('resize', drive, { passive: true });
    }
    setTimeout(drive, 180);
  };

  var revealNow = function (node) {
    for (var i = 0; i < watch.length; i++) {
      var w = watch[i];
      if (w.done || !(w.el === node || w.el.contains(node))) continue;
      w.done = true;
      if (g) g.set(w.el, { opacity: 1, x: 0, y: 0, rotateZ: 0 });
      else {
        w.el.style.opacity = '1';
        w.el.style.transform = 'none';
      }
    }
  };

  grid.addEventListener('focusin', function (e) { revealNow(e.target); });
  features.forEach(function (feature) {
    feature.addEventListener('focusin', function (e) { revealNow(e.target); });
  });

  var apply = function () {
    var featured = null;
    features.forEach(function (f) {
      var mine = f.getAttribute('data-for').split('|').indexOf(active) >= 0;
      f.hidden = !mine;
      if (mine) featured = f.getAttribute('data-id');
    });

    /* The featured post is the article already on screen above the grid; repeating
       it as a card is the same headline twice. */
    var list = cards.filter(function (c) {
      if (c.getAttribute('data-id') === featured) return false;
      return active === 'All' || c.getAttribute('data-tag') === active;
    });

    cards.forEach(function (c) { c.hidden = true; });
    list.slice(0, shown).forEach(function (c) { c.hidden = false; });

    empty.style.display = (!list.length && !featured) ? 'block' : 'none';

    var left = Math.max(0, list.length - shown);
    moreWrap.style.display = left > 0 ? 'flex' : 'none';
    if (moreBtn) moreBtn.textContent = 'Load more (' + left + ' to go)';

    return list.length;
  };

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      chips.forEach(function (x) {
        x.classList.remove('active');
        x.setAttribute('aria-pressed', 'false');
      });
      chip.classList.add('active');
      chip.setAttribute('aria-pressed', 'true');
      active = chip.getAttribute('data-tag');
      shown = PER_PAGE;   /* a new filter starts from the first page again */
      var n = apply();
      arm(allTargets());
      say(n ? active + ': ' + counted(n) + '.' : 'No posts under ' + active + '.');
    });
  });

  if (moreBtn) moreBtn.addEventListener('click', function () {
    var had = grid.querySelectorAll('.blog-card:not([hidden])').length;
    shown += PER_PAGE;
    apply();
    var now = visibleCards();
    arm(now.slice(had));
    /* On the last press the button hides itself out from under the user's focus,
       which drops focus to <body> with nothing announced. */
    if (moreWrap.style.display === 'none' && now[had]) now[had].focus();
    say(counted(now.length - had) + ' loaded. Showing ' + counted(now.length) + '.');
  });

  apply();
  arm(allTargets());
}());
JS;
}
