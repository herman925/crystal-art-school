<?php
/**
 * Home — BLOG & TOOLS (direction document §3.7).
 *
 * The blog rail and the TOOLS card that sits beside it, as `[cas_home_blog]`.
 *
 * What §3.7 fixes, and is therefore in code and not editable:
 *   - the section heading reads `BLOG` (it used to read SHARE WITH YOU), in the
 *     existing sign face, noticeably larger than the other signs — `.is-blog`
 *     in css/industrial-1.css already carries the enlargement;
 *   - the carousel shows two blog cards at a time;
 *   - a card is picture, the `.sc-kind` label row and the title, and nothing
 *     else — the excerpt is the one thing §3.7 removes;
 *   - on desktop the card is horizontal, picture left and title right;
 *   - the TOOLS card is near-square, carries only the wrench and the word
 *     TOOLS, links to the Blog page with the Tools filter already applied, and
 *     lifts slightly on hover with no extra text.
 *
 * What is Crystal's, per Herman's Rule (2026-08-23):
 *   - every blog card is a WordPress post — its title, its picture and its
 *     category are edited under Dashboard → Posts, and nothing about a card is
 *     typed into this file;
 *   - the photograph behind the TOOLS card. There is exactly one of it, so it is
 *     a Customizer field under the page's own section rather than a post type.
 *
 * There is no body copy in this section at all — §3.7 deletes the card excerpts
 * and every word from the TOOLS card — so there is nothing else to open.
 *
 * No CSS ships with this file. Every class below is already in the enqueued
 * bundle: `.share-layout`, `.share-carousel`, `.share-arrow`, `.share-viewport`,
 * `.share-rail`, `.tools-btn` and its parts in css/industrial-2.css;
 * `.share-card`, `.sc-img`, `.sc-body`, `.sc-kind` and `.section-title.is-blog`
 * in css/industrial-1.css.
 *
 * @package Crystal_Art_School
 */

defined( 'ABSPATH' ) || exit;

/** Most recent posts shown in the rail: three pages of two. */
const CAS_HOME_BLOG_MAX = 6;

/**
 * The photograph behind the TOOLS card.
 *
 * Optional, and deliberately without a fallback. `.tools-btn` already paints
 * itself `#1f1c18` under a gradient, so with no photograph the card is a dark
 * square carrying the wrench and the word TOOLS — which is exactly what §3.7
 * asks the card to show. Nothing is invented to fill the slot.
 *
 * @return string Uploaded URL, or '' when none is set.
 */
function cas_home_blog_tools_photo() {
	// A Customizer "Remove image" stores '', and get_theme_mod() returns the
	// stored empty string in preference to a default — so the empty case is
	// handled here rather than by relying on a default. Same trap as T1.
	return trim( (string) get_theme_mod( 'cas_home_tools_photo', '' ) );
}

/**
 * Registered at priority 10 with `panel => cas_pages`, the way the Blog and Free
 * Videos sections are. inc/cas-customizer-map.php creates that panel at priority
 * 100 and only re-parents the sections it did not make itself.
 *
 * Priority 17 keeps it with the other Home sections and puts it after the Gallery
 * card's section at 16, which is the order the page itself runs in — §3.6 Gallery,
 * then §3.7 Blog. Checked against every section registered in the panel; two pairs
 * of the sections built alongside this one do collide, and WordPress resolves an
 * equal priority with a hash offset, so the order between them is arbitrary.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_blog',
			array(
				'title'       => 'Home &mdash; blog &amp; tools',
				'panel'       => 'cas_pages',
				'priority'    => 17,
				'description' => 'The BLOG row on the front page. The cards come straight from <strong>Dashboard &rarr; Posts</strong> &mdash; the six most recent published posts that have a Featured image, newest first. A post with no Featured image is not shown here, because the card is a picture beside its title. The heading, the TOOLS wording and where the TOOLS card links are fixed.',
			)
		);

		$wp_customize->add_setting(
			'cas_home_tools_photo',
			array(
				'default'           => '',
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'cas_home_tools_photo',
				array(
					'label'       => 'TOOLS card &mdash; background photograph',
					'description' => 'Optional. Please choose a darker or lower-contrast photograph &mdash; the wrench and the word TOOLS are printed on top of it. Recommended about 400 x 400px. Any size works, because the photograph is cropped to fill the square. With no photograph the card is a plain dark square, which is also correct.',
					'section'     => 'cas_page_home_blog',
					'settings'    => 'cas_home_tools_photo',
				)
			)
		);
	},
	10
);

/**
 * The posts that get a card.
 *
 * A card in this rail is a picture beside a title, in a two-column grid whose
 * picture column is a fixed 150px. A post with no Featured image would leave that
 * column empty, so it is skipped here rather than rendered as a hole. The Blog
 * page itself still lists it — that page has a no-photo layout for exactly this.
 *
 * This also settles the state the site is in today. The only published post is
 * WordPress's own `Hello world!` sample, which has no Featured image, so the
 * whole section renders nothing until Crystal publishes a real post.
 *
 * @return WP_Post[]
 */
function cas_home_blog_posts() {
	if ( ! function_exists( 'cas_blog_posts' ) || ! function_exists( 'cas_blog_image' ) ) {
		return array();
	}

	$out = array();

	foreach ( cas_blog_posts() as $post ) {
		if ( ! get_post_thumbnail_id( $post ) ) {
			continue;
		}

		$out[] = $post;

		if ( count( $out ) >= CAS_HOME_BLOG_MAX ) {
			break;
		}
	}

	return $out;
}

add_shortcode( 'cas_home_blog', 'cas_render_home_blog' );

function cas_render_home_blog() {
	$posts = cas_home_blog_posts();

	/* Nothing published that can carry a card, so nothing is drawn — not the
	   heading, not an empty rail, and not the TOOLS card on its own, which would
	   link to a filtered Blog page with nothing on it. */
	if ( ! $posts ) {
		return '';
	}

	/* WordPress rewrites `sizes="…"` into `sizes="auto, …"` on every lazy image it
	   finds in filtered content, and Chrome then refuses to resolve a percentage
	   height on that image. `.share-card .sc-img img { height: 100% }`, so each
	   card's photograph is exactly the shape that broke every certificate on the
	   About page. Switched off the same way cas_render_about() does, and for the
	   same reason: cas_blog_image() states an exact `sizes` per slot, so `auto`
	   has no measurement to add. Registered only on a request that renders this. */
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	$sizes = '(max-width: 600px) 358px, (max-width: 1000px) 712px, 150px';

	/* §3.7: the whole card links to the Blog page with the Tools filter applied.
	   cas_blog_render_list() reads `?tag=` and matches it against the category
	   names, case-insensitively. */
	$tools_href = add_query_arg( 'tag', 'Tools', cas_page_url( 'blog' ) );

	$photo = cas_home_blog_tools_photo();

	/* Two cards are on screen at once, so a rail of two or fewer is a single page
	   and the arrows can do nothing. They are rendered only when they can move. */
	$paged = count( $posts ) > 2;

	ob_start();
	?>
	<section class="pg-section">
		<?php
		/* Outside .pg-wrap, as the sign is on every other section. `in-view` is in
		   the markup because the homepage has no scroll driver that adds it — it
		   only starts the ornament's entrance, and nothing is hidden without it. */
		?>
		<h2 class="section-title is-blog in-view">Blog</h2>
		<div class="pg-wrap">
			<div class="share-layout">
				<div class="share-carousel">
					<?php if ( $paged ) : ?>
						<button class="share-arrow" type="button" data-share="prev" aria-label="Previous posts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg></button>
						<button class="share-arrow" type="button" data-share="next" aria-label="More posts"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg></button>
					<?php endif; ?>
					<div class="share-viewport">
						<div class="share-rail" id="share-rail">
							<?php
							foreach ( $posts as $post ) :
								$topic = cas_blog_topic( $post );
								?>
								<a class="share-card" href="<?php echo esc_url( get_permalink( $post ) ); ?>">
									<div class="sc-img"><?php echo cas_blog_image( $post, 'medium', $sizes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup. ?></div>
									<div class="sc-body">
										<?php
										/* §3.7, 2026-08-05 amendment: this row stays. It is a
										   label, not the category system, and Crystal's note
										   struck out the body copy only. The topic is the
										   post's category; a post filed nowhere reads "Blog". */
										?>
										<span class="sc-kind">Blog<?php echo $topic ? ' &middot; ' . esc_html( $topic ) : ''; ?></span>
										<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
									</div>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				<a class="tools-btn" href="<?php echo esc_url( $tools_href ); ?>" aria-label="Filter the journal by Tools">
					<?php
					if ( $photo ) :
						$id = attachment_url_to_postid( $photo );

						if ( $id ) {
							// The card is 184px square, so `medium` is the right rung and
							// WordPress builds the srcset from the whole ladder.
							echo wp_get_attachment_image(
								$id,
								'medium',
								false,
								array(
									// Decorative: the word TOOLS is printed over it.
									'alt'      => '',
									'class'    => 'tb-photo',
									'sizes'    => '184px',
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core markup.
						} else {
							?>
							<img src="<?php echo esc_url( $photo ); ?>" alt="" class="tb-photo" sizes="184px" loading="lazy" decoding="async">
							<?php
						}
					endif;
					?>
					<div class="tb-in">
						<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="var(--band-eyebrow)" stroke-width="1.6" style="margin:0 auto 8px" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.2 5.2L3 18v3h3l6.5-6.5a4 4 0 0 0 5.2-5.2l-2.6 2.6-2.1-.5-.5-2.1z"></path></svg>
						<div class="tb-title">TOOLS</div>
					</div>
				</a>
			</div>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * The rail's paging. No stylesheet is registered: every class this section uses
 * is already in the enqueued bundle, so only the script is needed.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$post = get_post();

		$here = is_front_page()
			|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'cas_home_blog' ) );

		if ( ! $here ) {
			return;
		}

		wp_register_script( 'cas-home-blog', false, array(), null, true );
		wp_enqueue_script( 'cas-home-blog' );
		wp_add_inline_script( 'cas-home-blog', cas_home_blog_script() );
	},
	25
);

/**
 * index.html's initPager, trimmed to this one rail, with one correction.
 *
 * The prototype pages by a hardcoded two. At 768px and below the stylesheet gives
 * each card the full width of the viewport, so a hardcoded two skipped a card on
 * every press on a phone. The step is measured from the viewport and the card
 * instead — two at the desktop widths §3.7 specifies, one where the stylesheet
 * shows one.
 *
 * window.CasAnnounce is guarded because it is called by four modules on this site
 * and defined by none of them. Reported with this build; not fixed here.
 */
function cas_home_blog_script() {
	return <<<'JS'
(function () {
	var rail = document.getElementById('share-rail');
	if (!rail) { return; }

	var car = rail.closest('.share-carousel');
	var view = car && car.querySelector('.share-viewport');
	if (!car || !view) { return; }

	var prev = car.querySelector('[data-share="prev"]');
	var next = car.querySelector('[data-share="next"]');
	var page = 0;

	function apply(spoken) {
		var kids = Array.prototype.slice.call(rail.children);
		var first = kids[0];
		var cardW = first ? first.getBoundingClientRect().width : 0;
		var gap = parseFloat(getComputedStyle(rail).gap) || 20;
		var step = cardW + gap;
		var perPage = step > 0 ? Math.max(1, Math.round(view.getBoundingClientRect().width / step)) : 1;
		var pages = Math.max(1, Math.ceil(kids.length / perPage));

		page = Math.max(0, Math.min(page, pages - 1));
		rail.style.transform = 'translateX(' + (-(page * perPage) * step) + 'px)';

		if (prev) { prev.disabled = page <= 0; }
		if (next) { next.disabled = page >= pages - 1; }

		if (spoken && window.CasAnnounce) {
			var from = page * perPage + 1;
			var to = Math.min(kids.length, (page + 1) * perPage);
			window.CasAnnounce('Posts ' + from + ' to ' + to + ' of ' + kids.length + '.');
		}
	}

	if (prev) { prev.addEventListener('click', function () { page--; apply(true); }); }
	if (next) { next.addEventListener('click', function () { page++; apply(true); }); }
	window.addEventListener('resize', function () { apply(false); }, { passive: true });
	setTimeout(function () { apply(false); }, 60);
}());
JS;
}
