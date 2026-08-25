<?php
/**
 * Category, tag, date, author and search archives.
 *
 * These five surfaces were the last unstyled pages on the site. Until now they fell
 * all the way through the template hierarchy to hello-elementor's own index.php,
 * which prints a bare `<h1 class="entry-title">Category: Uncategorized</h1>` over a
 * stack of `<article class="post">` — correct header and footer, and nothing of the
 * design in between. A category link inside one of Crystal's articles is a normal
 * way to arrive at one.
 *
 * WHAT THIS RENDERS. The blog list page's own cards, unchanged: the same
 * `.blog-card` markup, the same `.blog-grid`, the same reading time and topic chip,
 * all of it already styled in css/industrial-2.css. The three helpers that build a
 * card — cas_blog_topic(), cas_blog_reading_minutes() and cas_blog_image() — are
 * called rather than copied, so an archive card and a blog card cannot drift. They
 * live in inc/cas-blog-templates.php, which this file does not touch; every call is
 * guarded, because a helper that failed to load should cost a field, not the page.
 *
 * WHAT IT DOES NOT RENDER. No topic chips: the chips on the Blog page filter the
 * whole list, and an archive already IS the filter. No Load More: an archive is a
 * paged WordPress query, so it pages the way WordPress pages, and Settings >
 * Reading > "Blog pages show at most" governs it. Nothing here is client-editable —
 * every string is a heading, a button label or an empty state, which Herman's Rule
 * (2026-08-23) keeps in code. The direction document never describes an archive at
 * all, so that rule is the whole authority for this page.
 *
 * HOW IT GETS ON SCREEN. The house pattern is one PHP module per block exposed as a
 * shortcode, with a template file beside it holding nothing but that shortcode —
 * archive-courses.php does exactly that for `[cas_courses]`. This build owned one
 * file, so there is no template file to hold `[cas_archive]`, and the page is
 * printed from the `template_include` filter instead. See the filter for why the
 * obvious one-file workaround does not work, and for the five lines that turn this
 * back into the house pattern the day someone owns a second file.
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------
   Which pages this module claims
   ------------------------------------------------------------------ */

/**
 * The five archive surfaces, and only those.
 *
 * Tag archives are included although nothing on the site links to one: the Posts
 * screen offers a Tags box, nothing on the blog reads it (register trap T19), and a
 * tag URL typed by hand would otherwise land on the one remaining unstyled page. It
 * costs nothing here.
 *
 * `is_404()` is excluded deliberately. WordPress does not 404 an empty category,
 * tag or author archive whose object exists, and never 404s a search — but it does
 * 404 an empty date archive and any page past the last one. Those keep their 404
 * status and their 404 page rather than being answered with a heading and an
 * apology at HTTP 200.
 *
 * The course archive and the course-category taxonomy are matched by none of these
 * tags — they are `is_post_type_archive()` and `is_tax()` — so this cannot collide
 * with the two filters inc/cas-courses.php and inc/cas-course-single.php already run
 * at this priority.
 */
function cas_archives_is_here() {
	if ( is_404() || is_embed() || is_feed() ) {
		return false;
	}

	return is_category() || is_tag() || is_author() || is_date() || is_search();
}

/**
 * The page, printed from the filter, which then returns an empty string.
 *
 * wp-includes/template-loader.php ends with `if ( $template ) { include $template; }`
 * — so an empty return means WordPress includes no template and what is printed
 * here is the page. Everything a template does is done here: get_header(), the
 * block, get_footer(). Elementor's header and footer are inside those two, exactly
 * as they are for archive-courses.php.
 *
 * WHY NOT A TEMPLATE FILE. Because this build owned one file, and the obvious
 * one-file workaround does not work. Returning __FILE__ so the module is included a
 * second time as its own template fatals with "Cannot redeclare cas_archives_is_here()"
 * — PHP binds a file's unconditional top-level functions when the file is compiled,
 * so the second include dies before any `if ( defined( … ) )` guard at the top of
 * the file can run. Measured on this server, not assumed.
 *
 * TO PUT IT BACK ON THE HOUSE PATTERN, which is where it belongs: add archive.php
 * and search.php to the theme root, each `get_header(); <main id="content"
 * class="site-main cas-archive">[cas_archive]</main>; get_footer();` — the body of
 * this closure — and reduce the closure to `return $template;`. The shortcode, the
 * conditionals and the enqueue all stay as they are.
 *
 * PRIORITY 999, matching the two filters already in the theme. Measured on the live
 * site, the template_include chain is Tutor at 10 and 99, Elementor and Elementor
 * Pro at 11, and Tutor Pro's OTP page plus the theme's two course filters at 999;
 * every Tutor entry is a course, dashboard, profile or order page, and none claims
 * an archive. 999 is chosen so a plugin that later claims one at its default 10
 * cannot silently take the design away. If an Elementor Theme Builder archive
 * template is ever created, revisit this — it would win over it.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! cas_archives_is_here() ) {
			return $template;
		}

		get_header();
		?>
		<main id="content" class="site-main cas-archive">
			<?php echo do_shortcode( '[cas_archive]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</main>
		<?php
		get_footer();

		return '';
	},
	999
);


/* ------------------------------------------------------------------
   The heading
   ------------------------------------------------------------------ */

/**
 * Eyebrow, title and lead line for whichever archive is being viewed.
 *
 * Headings are structure, so they are code. Each names what the reader actually
 * asked for — the category, the tag, the month, the author, the search phrase — and
 * the lead line under it counts what the query returned. Both are measurements of
 * the page in front of the reader; there is nothing to invent and nothing to keep
 * in step with the client's copy.
 *
 * @return array{eyebrow:string,title:string,lead:string}
 */
function cas_archives_context() {
	$found = isset( $GLOBALS['wp_query'] ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
	$count = $found . ( 1 === $found ? ' post' : ' posts' );

	if ( is_search() ) {
		// get_search_query() escapes by default, and truncating an escaped string
		// can cut inside &amp; — a phrase whose 60th character falls mid-entity
		// renders a literal "&am" to the visitor. Truncate the raw query, and let
		// the escaping happen downstream exactly once.
		$term = trim( (string) get_search_query( false ) );

		if ( '' === $term ) {
			// Reachable: /?s= with nothing after it.
			return array(
				'eyebrow' => '',
				'title'   => 'Search',
				'lead'    => 'Type something to search for.',
			);
		}

		// A pasted paragraph would otherwise become this page's H1.
		$shown = mb_strimwidth( $term, 0, 60, '&hellip;' );

		return array(
			'eyebrow' => '',
			'title'   => 'Search results for &ldquo;' . $shown . '&rdquo;',
			'lead'    => ( $found ? $found . ( 1 === $found ? ' result' : ' results' ) : 'No results' )
				. ' for &ldquo;' . $shown . '&rdquo;.',
		);
	}

	if ( is_category() ) {
		return array(
			'eyebrow' => 'Topic',
			'title'   => single_cat_title( '', false ),
			'lead'    => $count . ' filed under this topic.',
		);
	}

	if ( is_tag() ) {
		return array(
			'eyebrow' => 'Tag',
			'title'   => single_tag_title( '', false ),
			'lead'    => $count . ' with this tag.',
		);
	}

	if ( is_author() ) {
		$who = get_queried_object();

		return array(
			'eyebrow' => 'Written by',
			'title'   => ( $who instanceof WP_User ) ? $who->display_name : 'Author',
			'lead'    => $count . '.',
		);
	}

	/*
	 * Date. Built from the query vars and the locale's own month names rather than
	 * from a timestamp, so a constructed local time cannot shift the label across a
	 * month boundary on a site whose timezone is not the server's.
	 */
	global $wp_locale;

	$year  = (int) get_query_var( 'year' );
	$month = (int) get_query_var( 'monthnum' );
	$day   = (int) get_query_var( 'day' );

	$month_name = ( $month && $wp_locale ) ? $wp_locale->get_month( zeroise( $month, 2 ) ) : '';

	if ( $day && $month_name ) {
		$title = $day . ' ' . $month_name . ' ' . $year;
	} elseif ( $month_name ) {
		$title = $month_name . ' ' . $year;
	} else {
		$title = (string) $year;
	}

	return array(
		'eyebrow' => 'Archive',
		'title'   => $title,
		'lead'    => $count . ' from this period.',
	);
}


/* ------------------------------------------------------------------
   The page
   ------------------------------------------------------------------ */

add_shortcode( 'cas_archive', 'cas_render_archive' );

function cas_render_archive() {
	$ctx   = cas_archives_context();
	$sizes = '(max-width: 560px) 92vw, (max-width: 980px) 46vw, 30vw';

	ob_start();

	/*
	 * The helper's contract is not visible from this file, so a banner that is
	 * printed and a banner that is returned are both handled — the same guard the
	 * blog list uses. A helper that failed to load costs the banner, not the page.
	 */
	if ( function_exists( 'cas_page_hero' ) ) {
		// A term that does not exist cannot reach this template — WordPress 404s it,
		// and cas_archives_is_here() declines a 404 — so a nameless archive should be
		// unreachable. The H1 is the one element on the page worth a belt and braces.
		$title = ( '' !== trim( $ctx['title'] ) ) ? $ctx['title'] : 'Archive';

		// A long category name or a pasted search phrase runs off the banner in the
		// 80px marker face, so the title sizes itself to its own length.
		$size = ( mb_strlen( wp_specialchars_decode( $title, ENT_QUOTES ) ) > 24 )
			? 'clamp(26px,3.4vw,44px)'
			: 'clamp(44px,6vw,80px)';

		$hero = cas_page_hero( $ctx['eyebrow'], $title, $size );
		if ( is_string( $hero ) ) {
			echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the theme's own helper.
		}
	}

	if ( '' !== $ctx['lead'] ) {
		echo '<div class="container page-lead-wrap"><p class="page-lead">' . wp_kses_post( $ctx['lead'] ) . '</p></div>';
	}
	?>

	<div class="container" style="padding-bottom:70px;">
		<section class="section" style="padding-top:24px;">
			<?php if ( ! have_posts() ) : ?>
				<div class="store-empty" role="status"><?php echo esc_html( cas_archives_empty_line() ); ?></div>
			<?php else : ?>
				<div class="blog-grid">
					<?php
					while ( have_posts() ) {
						the_post();
						cas_archive_card( get_post(), $sizes );
					}
					wp_reset_postdata();
					?>
				</div>
				<?php cas_archives_pager(); ?>
			<?php endif; ?>

			<?php cas_archives_back_link(); ?>
		</section>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * The empty state. Reachable on a category, tag or author archive whose object
 * exists but holds nothing, and on any search that matches nothing — WordPress does
 * not 404 those. It says which of the two happened, because "nothing here" and
 * "nothing matched what you typed" are different facts.
 */
function cas_archives_empty_line() {
	if ( is_search() ) {
		return '' === trim( (string) get_search_query() )
			? 'Nothing to search for yet.'
			: 'Nothing matched that search.';
	}

	if ( is_author() ) {
		return 'Nothing published under this name yet.';
	}

	return 'Nothing published here yet.';
}

/**
 * One card, in the blog list's own markup.
 *
 * The three fields come from inc/cas-blog-templates.php so that an archive card and
 * a blog card cannot drift; each call is guarded, because a missing helper should
 * cost that field alone. Every image lands in a fixed-ratio slot with
 * `object-fit: cover` in css/industrial-2.css, and the helper emits
 * `loading="lazy" decoding="async"` — section 12.2 requires article images to be
 * deferred, and an archive is a page of article images.
 *
 * A result that is not a post — a Page, reachable through search — has no category,
 * so the chip carries its post type's own registered label rather than nothing. It
 * is derived from what WordPress already knows, not a label anyone typed.
 *
 * The card heading is an `<h2>`: the page's `<h1>` is the archive title in the
 * banner, and the blog list's `h3` there sits under its own section heading.
 */
function cas_archive_card( $post, $sizes ) {
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$topic = function_exists( 'cas_blog_topic' ) ? cas_blog_topic( $post ) : '';

	if ( '' === $topic && 'post' !== $post->post_type ) {
		$type = get_post_type_object( $post->post_type );
		if ( $type && ! empty( $type->labels->singular_name ) ) {
			$topic = (string) $type->labels->singular_name;
		}
	}

	$img     = function_exists( 'cas_blog_image' ) ? cas_blog_image( $post, 'large', $sizes ) : '';
	$minutes = function_exists( 'cas_blog_reading_minutes' ) ? (int) cas_blog_reading_minutes( $post ) : 0;
	$excerpt = trim( (string) get_the_excerpt( $post ) );
	?>
	<a class="blog-card" href="<?php echo esc_url( (string) get_permalink( $post ) ); ?>">
		<?php if ( $img ) : ?>
			<div class="bc-img"><?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup. ?></div>
		<?php endif; ?>
		<div class="bc-body">
			<?php if ( '' !== $topic ) : ?>
				<span class="bc-tag"><?php echo esc_html( $topic ); ?></span>
			<?php endif; ?>
			<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
			<?php if ( '' !== $excerpt ) : ?>
				<p><?php echo esc_html( $excerpt ); ?></p>
			<?php endif; ?>
			<span class="bc-date">
				<?php echo esc_html( get_the_date( 'M j', $post ) ); ?>
				<?php if ( $minutes ) : ?>
					&middot; <span class="bc-reads"><?php echo esc_html( $minutes ); ?> min</span>
				<?php endif; ?>
			</span>
		</div>
	</a>
	<?php
}

/**
 * Newer / older, and where the reader is.
 *
 * Server-side, because an archive is a paged query and the browser has only the
 * page it was handed. The Blog page's Load More is a different mechanism for a
 * different job — it holds the whole list in the document and reveals six at a time
 * — and copying it here would mean fetching every post in a category to show ten.
 */
function cas_archives_pager() {
	global $wp_query;

	$max   = isset( $wp_query->max_num_pages ) ? (int) $wp_query->max_num_pages : 0;
	$paged = max( 1, (int) get_query_var( 'paged' ) );

	if ( $max < 2 ) {
		return;
	}

	$newer = ( $paged > 1 ) ? get_previous_posts_page_link() : '';
	$older = ( $paged < $max ) ? get_next_posts_page_link( $max ) : '';
	?>
	<nav class="blog-more cas-pager" aria-label="Archive pages">
		<?php if ( $newer ) : ?>
			<a class="btn-big" href="<?php echo esc_url( $newer ); ?>"><span class="arr" aria-hidden="true">&larr;</span> Newer</a>
		<?php endif; ?>
		<span class="cas-pager-at">Page <?php echo (int) $paged; ?> of <?php echo (int) $max; ?></span>
		<?php if ( $older ) : ?>
			<a class="btn-big" href="<?php echo esc_url( $older ); ?>">Older <span class="arr" aria-hidden="true">&rarr;</span></a>
		<?php endif; ?>
	</nav>
	<?php
}

/**
 * A way back to the Blog page, which is where the topic chips are.
 *
 * An archive is otherwise a dead end: no navigation item points at one, and the
 * only route in is a category link inside an article. The page is looked up
 * directly rather than through cas_page_url(), because that helper answers a
 * missing page with the home URL (register trap T9), and a button labelled "All
 * posts" that quietly lands on the homepage is worse than no button. No Blog page,
 * no button.
 */
function cas_archives_back_link() {
	$blog = get_page_by_path( 'blog' );

	if ( ! $blog instanceof WP_Post || 'publish' !== $blog->post_status ) {
		return;
	}
	?>
	<div class="blog-more">
		<a class="btn-big" href="<?php echo esc_url( (string) get_permalink( $blog ) ); ?>">All posts <span class="arr" aria-hidden="true">&rarr;</span></a>
	</div>
	<?php
}


/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * The cards are styled in css/industrial-2.css, which is enqueued on every page, so
 * there is nothing to add for them. Two things are not in that bundle:
 *
 * 1. `.bc-reads` carries only its colour there; its size and face live in
 *    cas_blog_styles(), which is enqueued on the Blog page and on single posts and
 *    not here. That function is CALLED rather than its rules copied, so there is
 *    still one definition of them, and the two enqueue conditions do not overlap so
 *    the block cannot print twice. If that block ever becomes css/cas-blog.css as
 *    its own file header proposes, this handle should take that stylesheet as a
 *    dependency instead — the guard below means the reading time would otherwise
 *    quietly lose its type styling with nothing breaking loudly.
 *
 * 2. The pager. `.blog-more` centres a single button; a pager is three items, so it
 *    needs a gap and something to style the position line. Two rules, written here
 *    because the prototype has no pager anywhere to copy one from.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! cas_archives_is_here() ) {
			return;
		}

		wp_register_style( 'cas-archives', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-archives' );

		if ( function_exists( 'cas_blog_styles' ) ) {
			wp_add_inline_style( 'cas-archives', cas_blog_styles() );
		}

		wp_add_inline_style( 'cas-archives', cas_archives_styles() );
	},
	27
);

function cas_archives_styles() {
	return <<<'CSS'
.cas-pager { gap: 14px; flex-wrap: wrap; align-items: center; }
.cas-pager .cas-pager-at { font-family: var(--font-cond); font-size: 13px; letter-spacing: var(--tracking-label); text-transform: uppercase; color: var(--muted); }
CSS;
}
