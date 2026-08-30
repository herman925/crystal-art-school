<?php
/**
 * Homepage course block — direction document sections 3.5.1 and 3.5.2.
 *
 * Renders the `SHOP ALL COURSES` heading, the centred lede beneath it, and the paged
 * rail of three-part course cards. Markup mirrors index.html:251-278 and its
 * courseCard() builder exactly, so the shipped CSS applies without a new rule:
 * `.shop-lede` (industrial-1), `.shop-layout` / `.shop-rail` / `.course-card`
 * (industrial-2), `.cc-photo` / `.cc-facts` / `.cc-photo-empty` (cas-home.css).
 *
 * Course facts come from Tutor through cas-courses.php's own helpers — cas_course_facets(),
 * cas_course_price() — rather than a second reading of the same meta, so a pricing or
 * category change lands on the catalogue card and this card together.
 *
 * WHILE EVERY COURSE IS A DRAFT THE RAIL RENDERS NOTHING. Not a heading over an empty rail,
 * and not an empty state: the shortcode returns just the Course overview tail (3.5) instead.
 * The lede and the section chrome exist only to introduce courses, so with no courses there
 * is nothing for them to introduce — but the overview below them is her copy, not a rail,
 * and renders alone until the first course is published.
 *
 * Deliberately not built here: the Art Gallery promotion card (3.6). It is the right-hand
 * column of `.shop-layout` and therefore has to sit INSIDE this markup, but it is another
 * agent's block — so it is pulled in through the `cas_home_courses_aside` filter, and this
 * file renders nothing in its place when nothing is hooked.
 */

defined( 'ABSPATH' ) || exit;

/**
 * How many cards the rail holds. The prototype capped it at ten. A homepage rail is a
 * sampler, not the catalogue, and the catalogue is one click away.
 */
const CAS_HOME_COURSES_MAX = 10;

/* ------------------------------------------------------------------
   Data
   ------------------------------------------------------------------ */

/**
 * The published courses, in the order she sets in Tutor.
 *
 * The prototype shuffled its pool and took ten, which was a way of filling a rail from a
 * four-item demo file. Shuffling here would mean a course she deliberately ordered first
 * appears anywhere, and — with four courses and three visible — a different course would
 * hide on every reload. The catalogue's own order (menu_order, then date) is used
 * instead, so Page Attributes controls this rail the same way it controls /courses/.
 *
 * `post_status => publish` is load-bearing: the four courses are drafts and must not
 * reach the homepage.
 */
function cas_home_course_ids() {
	$q = new WP_Query(
		[
			'post_type'              => 'courses',
			'post_status'            => 'publish',
			'posts_per_page'         => CAS_HOME_COURSES_MAX,
			'orderby'                => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
		]
	);

	return wp_list_pluck( $q->posts, 'ID' );
}

/**
 * 3.5.2, bottom third: the four small pictures of the course's five-image set, with the
 * main cover excluded.
 *
 * The direction draws from the course detail set minus the large picture, so the four
 * small slots are read by name rather than by trimming the front off cas_course_photos().
 * That helper substitutes the featured image into the large slot when the slot is empty,
 * and trimming its array would then discard a real small picture instead.
 *
 * An attachment deleted from the library leaves its ID behind in the meta, so each one is
 * checked before it is trusted.
 */
function cas_home_course_photo_pool( $course_id ) {
	$out = [];

	foreach ( [ '_cas_course_photo_1', '_cas_course_photo_2', '_cas_course_photo_3', '_cas_course_photo_4' ] as $key ) {
		$att = (int) get_post_meta( $course_id, $key, true );

		if ( $att && wp_attachment_is_image( $att ) ) {
			$out[] = $att;
		}
	}

	return $out;
}

/* ------------------------------------------------------------------
   Markup helpers
   ------------------------------------------------------------------ */

/**
 * One `<img>`, built by hand rather than through wp_get_attachment_image().
 *
 * Both picture slots on this card are `width:100%; height:100%; object-fit:cover` inside
 * an aspect-ratio box, which is the exact shape WordPress's `sizes="auto,"` injection
 * derailed on the About certificates. Writing src/srcset/sizes directly keeps the
 * attribute the one written here, and matches the prototype attribute for attribute.
 *
 * `alt` is empty on purpose: the anchor wrapping each picture already carries the course
 * title as its accessible name, so an alt naming the course reads the title twice, and
 * nothing stored about the picture describes what is in it. `$priority` accepts `lazy`,
 * `high`, or `low`; the last two states use fetchpriority rather than loading.
 */
function cas_home_course_img( $att_id, $sizes, $priority = 'lazy' ) {
	$src = wp_get_attachment_image_src( $att_id, 'large' );

	if ( ! $src ) {
		return '';
	}

	$srcset = wp_get_attachment_image_srcset( $att_id, 'large' );

	$out = '<img src="' . esc_url( $src[0] ) . '"';

	if ( $srcset ) {
		$out .= ' srcset="' . esc_attr( $srcset ) . '" sizes="' . esc_attr( $sizes ) . '"';
	}

	$out .= ' width="' . (int) $src[1] . '" height="' . (int) $src[2] . '" alt=""';

	if ( 'low' === $priority ) {
		$out .= ' fetchpriority="low"';
	} elseif ( 'high' === $priority ) {
		$out .= ' fetchpriority="high"';
	} else {
		$out .= ' loading="lazy"';
	}

	$out .= ' decoding="async">';

	return $out;
}

/**
 * One three-part course card (3.5.2).
 *
 * 1. Cover  — the course's featured image, static, never a carousel.
 * 2. Banner — media tag, title, price, ADD.
 * 3. Photo  — one picture drawn from the four small course images, with the lesson count
 *             and the level over it. No total runtime: 1.4.7 removed it.
 *
 * The cover, the title and the bottom photo are all links to the course. The price and
 * the ADD button are not.
 *
 * No Sale ribbon — 3.5.2 holds it off the homepage. The struck-through former price is
 * not the ribbon and stays.
 */
function cas_home_course_card( $course_id, $index ) {
	$facts   = cas_course_facets( $course_id );
	$title   = get_the_title( $course_id );
	$href    = get_permalink( $course_id );
	$tag     = $facts['medium'] ? reset( $facts['medium'] ) : '';
	$lessons = (int) tutor_utils()->get_lesson_count_by_course( $course_id );
	$level   = (string) $facts['difficulty'];
	$level   = $level ? ucwords( str_replace( [ '-', '_' ], ' ', $level ) ) : '';

	/* The cover and the bottom strip are the same width — about 280px on a desktop, the
	   full column on a phone. The prototype's string, unchanged. */
	$card_sizes = '(max-width: 600px) 358px, (max-width: 1000px) 347px, 280px';

	$cover_id = (int) get_post_thumbnail_id( $course_id );
	$cover    = ( $cover_id && wp_attachment_is_image( $cover_id ) )
		? cas_home_course_img( $cover_id, $card_sizes, $index > 0 ? 'lazy' : 'high' )
		: '';

	/* 3.5.2: drawn once per page load, then left alone. No rotation this round. */
	$pool = cas_home_course_photo_pool( $course_id );
	$shot = $pool ? cas_home_course_img( $pool[ wp_rand( 0, count( $pool ) - 1 ) ], $card_sizes, 'lazy' ) : '';

	$cart_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>';

	ob_start();
	?>
	<div class="course-card">
		<a class="cc-cover" href="<?php echo esc_url( $href ); ?>" aria-label="<?php echo esc_attr( $title ); ?>"><?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<div class="cc-pad">
			<?php if ( $tag ) : ?>
				<div class="cc-meta"><?php echo esc_html( $tag ); ?></div>
			<?php endif; ?>
			<h3><a href="<?php echo esc_url( $href ); ?>" style="color:inherit;text-decoration:none;"><?php echo esc_html( $title ); ?></a></h3>
			<div class="cc-row">
				<span class="cc-price">
					<?php if ( $facts['is_sale'] ) : ?>
						<s><?php echo esc_html( cas_course_price( $facts['regular'] ) ); ?></s> <b class="now"><?php echo esc_html( cas_course_price( $facts['now'] ) ); ?></b>
					<?php else : ?>
						<?php echo esc_html( cas_course_price( $facts['regular'] ) ); ?>
					<?php endif; ?>
				</span>
				<button class="cc-btn primary" type="button" data-cas-add-to-cart data-course-id="<?php echo esc_attr( $course_id ); ?>"><?php echo $cart_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?> Add</button>
			</div>
		</div>
		<a class="cc-photo" href="<?php echo esc_url( $href ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php if ( $shot ) : ?>
				<?php echo $shot; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<span class="cc-photo-empty">Course photographs to come</span>
			<?php endif; ?>
			<?php if ( $lessons || $level ) : ?>
				<div class="cc-facts">
					<?php if ( $lessons ) : ?>
						<span><i>Lessons</i><?php echo esc_html( $lessons ); ?></span>
					<?php endif; ?>
					<?php if ( $level ) : ?>
						<span><i>Level</i><?php echo esc_html( $level ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</a>
	</div>
	<?php

	return ob_get_clean();
}

/* ------------------------------------------------------------------
   The section
   ------------------------------------------------------------------ */

/**
 * 3.5.1 introduction copy. Her own words from `Under_Shopallcourses.docx`, less the
 * clause promising lifetime access, which 7.4 holds until she confirms the term.
 *
 * The registered default is what the prototype shows, so the Customizer field opens on
 * the real sentence rather than an empty box. Emptying the field renders no lede at all —
 * an open field has to be open in both directions, and the About page already carries the
 * defect where clearing a paragraph silently puts the old one back.
 */
function cas_home_courses_lede_default() {
	return 'Each module features both Beginner and Advanced levels. The Beginner courses are clear and easy to follow. All lessons include clear English voiceovers and subtitles for a seamless learning experience.';
}

function cas_render_home_courses() {
	$ids      = cas_home_course_ids();
	$overview = function_exists( 'cas_render_home_course_overview' )
		? cas_render_home_course_overview()
		: '';

	/* Nothing published — the rail and its heading go, but the Course overview tail (3.5)
	   is her copy, not a rail, so it renders alone in the same width. */
	if ( ! $ids ) {
		if ( '' === $overview ) {
			return '';
		}

		ob_start();
		?>
		<section class="pg-section">
			<div class="pg-wrap">
				<?php echo $overview; // phpcs:ignore WordPress.Security.EscapeOutput -- the module escapes its own output. ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/* This shortcode sits in a Page's post_content, so wp_filter_content_tags() runs over
	   its output at the_content priority 12 — after do_shortcode at 11 — and rewrites
	   `sizes="…"` into `sizes="auto, …"` on every lazy image. Both picture slots on this
	   card are `height: 100%` inside an aspect-ratio box, which is the shape that painted
	   every About certificate outside its own frame. The sizes written below are the
	   prototype's, so `auto` has no measurement to add. Core's own documented switch, and
	   the same call cas-about.php:742 and cas-home-gallery.php make for the same reason. */
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	$lede = trim( (string) get_theme_mod( 'cas_home_shop_lede', cas_home_courses_lede_default() ) );

	/* 3.6's Art Gallery card is the second column of this grid and belongs to another
	   module. Nothing hooked means nothing rendered, and the first column takes the
	   width. */
	$aside = (string) apply_filters( 'cas_home_courses_aside', '' );

	$cards = '';

	foreach ( $ids as $i => $id ) {
		$cards .= cas_home_course_card( $id, $i );
	}

	ob_start();
	?>
	<section class="pg-section">
		<h2 class="section-title in-view">Shop all courses</h2>
		<?php if ( '' !== $lede ) : ?>
			<p class="shop-lede"><?php echo esc_html( $lede ); ?></p>
		<?php endif; ?>
		<div class="pg-wrap">
			<div class="shop-layout">
				<div class="shop-left">
					<div class="shop-carousel">
						<button class="shop-arrow" type="button" data-shop="prev" aria-label="Previous courses"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"></path></svg></button>
						<button class="shop-arrow" type="button" data-shop="next" aria-label="More courses"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg></button>
						<div class="shop-viewport"><div class="shop-rail" id="shop-rail"><?php echo $cards; // phpcs:ignore WordPress.Security.EscapeOutput ?></div></div>
					</div>
				</div>
				<?php echo $aside; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<?php
			/* 3.5's Course overview — her two ledes, two numbered principles and the
			   four self-answered questions — is the tail of this section, exactly as
			   the prototype places it (index.html:279-319). Its module renders the
			   markup against the shipped CSS; nothing here needs to know its fields.
			   Computed once at the top of this function so the no-courses branch
			   above can render it alone. */
			echo $overview; // phpcs:ignore WordPress.Security.EscapeOutput -- the module escapes its own output.
			?>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_courses', 'cas_render_home_courses' );

/* ------------------------------------------------------------------
   Customizer — one field, the 3.5.1 lede
   ------------------------------------------------------------------ */

/**
 * Registered at priority 10 with `panel => cas_pages`, which is how cas-blog-templates.php
 * and cas-free-videos.php add their own page sections. Priority 13 puts it between
 * `Home - About Crystal` (12) and `Home - testimonials` (15), which is the order the
 * sections appear in down the page. Two sections on one priority are not lost, but
 * WordPress then orders them by a hash and the panel reads arbitrarily.
 *
 * One field only. Everything else on these cards — title, cover, price, level, category,
 * the five course photographs, the running order — is already hers under Tutor LMS, and
 * duplicating any of it into a setting here would give one fact two homes.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_home_courses',
			[
				'title'       => 'Home — Shop all courses',
				'panel'       => 'cas_pages',
				'priority'    => 13,
				'description' => 'The introduction under the SHOP ALL COURSES heading. The cards themselves — course titles, covers, prices, levels, categories and the five photographs per course — are edited under Dashboard → Tutor LMS → Courses, and the order they appear in is the Page Attributes order on each course. While no course is published the rail and this introduction stay hidden, but the Course overview block below them still shows.',
			]
		);

		$wp_customize->add_setting(
			'cas_home_shop_lede',
			[
				'default'           => cas_home_courses_lede_default(),
				'sanitize_callback' => 'sanitize_textarea_field',
				'transport'         => 'refresh',
			]
		);

		$wp_customize->add_control(
			'cas_home_shop_lede',
			[
				'label'       => 'Introduction',
				'section'     => 'cas_page_home_courses',
				'type'        => 'textarea',
				'description' => 'One short paragraph, centred under the heading. Leave it empty to show no introduction at all.',
			]
		);
	},
	10
);

/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * Two scripts, neither of them a new file.
 *
 * `cas-courses.js` already carries the add-to-cart handler that posts to Tutor's native
 * cart, and it is written as a delegated document listener, so it works on any page it is
 * loaded on. Its second half returns immediately without `#lesson-grid`, which the
 * homepage does not have. It is enqueued here rather than copied, so the cart behaviour
 * stays defined in one place. The guard keeps the localisation from printing twice if
 * this block and the catalogue ever render in one request.
 *
 * The rail pager is a handful of lines and goes inline against a file-less handle, which
 * is the pattern cas-about.php and cas-showcase.php already use.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$post = get_post();
		$here = is_front_page()
			|| ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'cas_home_courses' ) );

		if ( ! $here ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		if ( ! wp_script_is( 'cas-courses', 'enqueued' ) ) {
			wp_enqueue_script( 'cas-courses', $uri . '/js/cas-courses.js', [], filemtime( $dir . '/js/cas-courses.js' ), true );

			wp_localize_script(
				'cas-courses',
				'casCourses',
				[
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => is_user_logged_in() ? 'tutor_add_course_to_cart' : 'tutor_guest_add_course_to_cart',
					'nonce'   => wp_create_nonce( tutor()->nonce_action ),
					'cartUrl' => get_permalink( get_tutor_option( 'tutor_cart_page_id' ) ),
				]
			);
		}

		wp_register_script( 'cas-home-courses', false, [], null, true );
		wp_enqueue_script( 'cas-home-courses' );
		wp_add_inline_script( 'cas-home-courses', cas_home_courses_js() );
	},
	25
);

/**
 * The rail pager.
 *
 * The prototype paged a fixed three cards at a time, but `.shop-rail .course-card` is a
 * half-width card below 860px and a full-width one below 560px, so a fixed three slid the
 * rail past cards nobody had seen. The page size is measured from the viewport instead,
 * which also keeps the spoken "1 to 3 of 10" honest at every width.
 *
 * CasAnnounce is guarded because it is called by several modules and defined by none —
 * the site's single live region was never ported from the prototype's site-extras.js.
 */
function cas_home_courses_js() {
	return <<<'JS'
( function () {
	'use strict';

	var rail = document.getElementById( 'shop-rail' );

	if ( ! rail ) {
		return;
	}

	var car = rail.closest( '.shop-carousel' );

	if ( ! car ) {
		return;
	}

	var prev = car.querySelector( '[data-shop="prev"]' );
	var next = car.querySelector( '[data-shop="next"]' );
	var page = 0;

	function say( msg ) {
		if ( window.CasAnnounce ) {
			window.CasAnnounce( msg );
		}
	}

	function step() {
		var first = rail.children[ 0 ];
		var cardW = first ? first.getBoundingClientRect().width : 0;
		var gap = parseFloat( getComputedStyle( rail ).gap ) || 18;
		var view = car.querySelector( '.shop-viewport' );
		var viewW = view ? view.getBoundingClientRect().width : 0;
		var per = cardW ? Math.max( 1, Math.round( ( viewW + gap ) / ( cardW + gap ) ) ) : 1;

		return { per: per, width: cardW + gap };
	}

	function apply( spoken ) {
		var kids = rail.children;
		var s = step();
		var pages = Math.max( 1, Math.ceil( kids.length / s.per ) );

		page = Math.max( 0, Math.min( page, pages - 1 ) );
		rail.style.transform = 'translateX(' + ( -( page * s.per ) * s.width ) + 'px)';

		if ( prev ) {
			prev.disabled = page <= 0;
		}

		if ( next ) {
			next.disabled = page >= pages - 1;
		}

		if ( spoken ) {
			var from = page * s.per + 1;
			var to = Math.min( kids.length, ( page + 1 ) * s.per );

			say( 'Courses ' + from + ' to ' + to + ' of ' + kids.length + '.' );
		}
	}

	if ( prev ) {
		prev.addEventListener( 'click', function () {
			page--;
			apply( true );
		} );
	}

	if ( next ) {
		next.addEventListener( 'click', function () {
			page++;
			apply( true );
		} );
	}

	window.addEventListener( 'resize', function () {
		apply( false );
	} );

	window.setTimeout( function () {
		apply( false );
	}, 60 );
} )();
JS;
}
