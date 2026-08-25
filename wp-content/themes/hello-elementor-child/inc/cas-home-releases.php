<?php
/**
 * Homepage "New release" carousel — direction document section 5.
 *
 * The middle column of the homepage's New release row, between the Free videos card
 * (3.3) and the About Crystal column (3.4). The prototype's column is a square
 * 1:1 carousel (index.html:221-229) whose slides are the three most recent courses,
 * each carrying the short topic, the Media / Lessons / Level facts and an Add to
 * cart + View pair.
 *
 * WHAT IS RENDERED WHERE. The slides are built here, server-side, from the published
 * courses — the same data cas-home-courses.php reads, through the same helpers, so a
 * pricing or category change lands here and on the catalogue together. The dots are
 * also rendered here. js/cas-home-release.js then does what only a browser can: show
 * and hide slides, wire the dots, the 6s autoplay and its pause control, exactly as
 * the prototype's inline builder did.
 *
 * WHILE EVERY COURSE IS A DRAFT THIS COLUMN RENDERS NOTHING. The row's heading is
 * printed by the row module only when this column has content, so a heading never
 * sits over an empty cell.
 *
 * The add-to-cart buttons use the theme's own `data-cas-add-to-cart` contract, which
 * cas-courses.js handles by delegation on any page it is loaded on — so this module
 * enqueues that script (guarded) rather than writing a second cart path.
 *
 * @package Crystal Art School
 */

defined( 'ABSPATH' ) || exit;

/**
 * The carousel's courses: the three most recently published.
 *
 * "New release" means what the direction says — the newest. The prototype sorted its
 * pool by date and took three; the catalogue's menu_order-first ordering is for the
 * Shop all courses rail, whose brief is different. So this is date DESC, not the
 * order she sets — a course she pins to the front of the catalogue is not a new
 * release.
 *
 * @return int[]
 */
function cas_home_release_ids() {
	$q = new WP_Query(
		[
			'post_type'              => 'courses',
			'post_status'            => 'publish',
			'posts_per_page'         => 3,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
		]
	);

	return wp_list_pluck( $q->posts, 'ID' );
}

/**
 * One slide.
 *
 * Mirrors the prototype's slide builder (index.html:757-783) attribute for attribute:
 * the cover with its srcset, the short topic in .rel-title with the size steps, the
 * three facts, and the Add to cart + View pair. The first slide is not lazy — it is
 * the one a visitor actually sees, and the prototype's #17 record says so; the others
 * are demoted to fetchpriority="low" rather than lazy because the visible slide is
 * above the fold.
 *
 * The prototype's "short topic" was the text before the hyphen in a generated title
 * field. Course titles here are full sentences with no hyphen, and the prototype's
 * own fallback was the full title — so that fallback is what this renders, with the
 * same is-mid / is-long size steps keyed to length.
 *
 * @param int $course_id
 * @param int $index      Position in the carousel, 0-based. 0 = first, visible.
 *
 * @return string
 */
function cas_home_release_slide( $course_id, $index ) {
	$facts   = cas_course_facets( $course_id );
	$title   = get_the_title( $course_id );
	$href    = get_permalink( $course_id );
	$short   = trim( (string) $title );
	$fit     = mb_strlen( $short ) <= 12 ? '' : ( mb_strlen( $short ) <= 22 ? ' is-mid' : ' is-long' );
	$tag     = $facts['medium'] ? reset( $facts['medium'] ) : '';
	$lessons = (int) tutor_utils()->get_lesson_count_by_course( $course_id );
	$level   = (string) $facts['difficulty'];
	$level   = $level ? ucwords( str_replace( [ '-', '_' ], ' ', $level ) ) : '';

	/* The slot is square and about 373px on a desktop, the full column on a phone —
	   the same shape as the About column beside it, so the same sizes string. */
	$sizes = '(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px';

	$cover_id = (int) get_post_thumbnail_id( $course_id );
	$cover    = ( $cover_id && wp_attachment_is_image( $cover_id ) )
		? cas_home_course_img( $cover_id, $sizes, false )
		: '';

	$cart_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1.6"></circle><circle cx="19" cy="21" r="1.6"></circle><path d="M2 3h3l2.6 12.6a1.8 1.8 0 0 0 1.8 1.4h8.9a1.8 1.8 0 0 0 1.8-1.4L22 7H6"></path></svg>';

	ob_start();
	?>
	<div class="rel-slide<?php echo 0 === $index ? ' is-active' : ''; ?>"<?php echo 0 === $index ? '' : ' inert aria-hidden="true"'; ?>>
		<?php echo $cover; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cas_home_course_img() escapes its own attributes. ?>
		<div class="rel-title<?php echo esc_attr( $fit ); ?>" title="<?php echo esc_attr( $title ); ?>"><?php echo esc_html( $short ); ?></div>
		<?php if ( $tag || $lessons || $level ) : ?>
			<div class="rel-facts">
				<?php if ( $tag ) : ?>
					<span><i>Media</i><?php echo esc_html( $tag ); ?></span>
				<?php endif; ?>
				<?php if ( $lessons ) : ?>
					<span><i>Lessons</i><?php echo esc_html( $lessons ); ?></span>
				<?php endif; ?>
				<?php if ( $level ) : ?>
					<span><i>Level</i><?php echo esc_html( $level ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<div class="rel-actions">
			<button class="cc-btn primary" type="button" data-cas-add-to-cart data-course-id="<?php echo esc_attr( $course_id ); ?>"><?php echo $cart_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?> Add to cart</button>
			<a class="cc-btn secondary" href="<?php echo esc_url( $href ); ?>">View <span class="arr" aria-hidden="true">&#8594;</span></a>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * One dot. The full title is the accessible name, not the short topic — two courses
 * can share a topic, and "Show release 2: Clouds" would name two different dots.
 *
 * @param int    $course_id
 * @param int    $index
 * @param string $title
 *
 * @return string
 */
function cas_home_release_dot( $course_id, $index, $title ) {
	$active = 0 === $index;

	ob_start();
	?>
	<button type="button" class="<?php echo $active ? 'active' : ''; ?>"<?php echo $active ? ' aria-current="true"' : ''; ?> aria-label="<?php echo esc_attr( 'Show release ' . ( $index + 1 ) . ': ' . $title ); ?>" data-release-id="<?php echo esc_attr( $course_id ); ?>"></button>
	<?php

	return ob_get_clean();
}

/**
 * The carousel column. Returns the bare column, not the row — the row module
 * supplies `.grid-12.release-trio` and the heading that names this column.
 *
 * @return string
 */
function cas_render_home_release() {
	$ids = cas_home_release_ids();

	if ( ! $ids ) {
		return '';
	}

	$slides = '';
	$dots   = '';

	foreach ( $ids as $i => $course_id ) {
		$slides .= cas_home_release_slide( $course_id, $i );
		$dots   .= cas_home_release_dot( $course_id, $i, get_the_title( $course_id ) );
	}

	ob_start();
	?>
	<div class="span-4 reveal-up" style="--i:1">
		<div class="rel-carousel" id="rel-carousel" aria-roledescription="carousel" aria-label="New releases">
			<span class="rel-chip">New release</span>
			<div class="rel-dots" id="rel-dots"><?php echo $dots; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cas_home_release_dot() escapes its own attributes. ?></div>
			<button class="cas-pause" type="button" data-rel-pause aria-pressed="false" aria-label="Pause the new releases carousel">
				<svg class="ico-pause" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="5" width="4" height="14" rx="1"></rect><rect x="14" y="5" width="4" height="14" rx="1"></rect></svg>
				<svg class="ico-play" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.2v13.6L19 12z"></path></svg>
			</button>
			<?php echo $slides; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cas_home_release_slide() escapes its own output. ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_home_release', 'cas_render_home_release' );

/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * The carousel behaviour (show/hide, dots, autoplay, pause) plus the shared
 * add-to-cart handler, on the front page only. The add-to-cart script is guarded
 * the same way cas-home-courses.php guards it, so the localisation never prints
 * twice in one request.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_front_page() ) {
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

		wp_enqueue_script( 'cas-home-release', $uri . '/js/cas-home-release.js', [], filemtime( $dir . '/js/cas-home-release.js' ), true );
	},
	25
);
