<?php
/**
 * Lesson page.
 *
 * Mirrors the prototype's lesson.dc.html structure so the existing CSS applies, but every
 * value comes from Tutor. The prototype is a single page that swaps lessons in script; in
 * WordPress each lesson is its own address, so the phase list becomes links and progress is
 * Tutor's own, kept server-side.
 *
 * HOW IT IS WIRED. Tutor's `learning-area/index.php` is the stock learning area: a dark
 * navy app shell that is not the agreed design. Forking that 226-line file would freeze a
 * copy of it and go stale with every Tutor release, and it also handles quizzes, quiz
 * attempts and the course sub-pages, none of which this file touches. So this claims only
 * the ordinary lesson view through `template_include` — the same route inc/cas-course-single.php
 * takes for the single course — and hands everything else back to Tutor untouched.
 *
 * Tutor still owns access, progress and completion. The access test is Tutor's own
 * CourseModel::has_course_content_access(), the same call the stock template makes, and
 * anyone it refuses falls through to Tutor's enrolment / sign-in screen. Completing a lesson
 * is Tutor's own form post (`tutor_action=tutor_complete_lesson`, with its nonce), so its
 * checks and its `tutor_lesson_completed_*` hooks all run.
 *
 * WHAT THE PROTOTYPE HAS THAT THIS DOES NOT, and why:
 *   - "Taught by", the teach-card quote, the "Share your skies" band and the finish card's
 *     heading and blurb are the prototype's own demo copy. Nothing in Tutor holds them and
 *     none of it is Crystal's supplied wording, so none of it is invented here.
 *   - "Restart course" and "Watch again" reset browser storage in the prototype. Tutor's
 *     progress is on the server; its retake feature is off on this install.
 *   - The course films card and multi-part lessons need data Tutor does not hold.
 *   - Portrait lessons are pillarboxed in the prototype from a measurement taken at import.
 *     Tutor stores no orientation, so every film is shown in the 16:9 stage.
 *   - Quizzes and assignments are not ported; they keep Tutor's own screens.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The course a lesson belongs to, or 0.
 *
 * @param int $lesson_id Lesson post ID.
 *
 * @return int
 */
function cas_lesson_course_id( $lesson_id ) {
	if ( ! function_exists( 'tutor_utils' ) ) {
		return 0;
	}

	return (int) tutor_utils()->get_course_id_by_subcontent( $lesson_id );
}

/**
 * Whether this request is the ordinary lesson view that this file renders.
 *
 * Anything else — another post type, one of Tutor's learning-area sub-pages, or a visitor
 * Tutor would refuse — is left to Tutor. Memoised because the asset hook and the template
 * filter both ask.
 *
 * @return bool
 */
function cas_lesson_is_ours() {
	static $ours = null;

	if ( null !== $ours ) {
		return $ours;
	}

	$ours = false;

	if ( ! is_singular( 'lesson' ) || ! class_exists( '\Tutor\Models\CourseModel' ) ) {
		return $ours;
	}

	// Course sub-pages ("Course info" and the like) are addressed with ?subpage=.
	if ( isset( $_GET['subpage'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $ours;
	}

	$lesson_id = get_queried_object_id();
	$course_id = cas_lesson_course_id( $lesson_id );

	if ( ! $lesson_id || ! $course_id ) {
		return $ours;
	}

	try {
		$ours = (bool) \Tutor\Models\CourseModel::has_course_content_access(
			array(
				'current_post_type' => 'lesson',
				'current_post_id'   => $lesson_id,
				'course_id'         => $course_id,
				'is_public'         => \TUTOR\Course_List::is_public( $course_id ),
				'is_enrolled'       => \Tutor\Models\EnrollmentModel::is_enrolled( $course_id ),
			)
		);
	} catch ( \Throwable $e ) {
		$ours = false;
	}

	return $ours;
}

/**
 * Tutor swaps the learning-area template in through `template_include`, so the theme file
 * has to be claimed back at a later priority. See the note at the top of this file for why
 * this is a route and not a fork of Tutor's own template.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! cas_lesson_is_ours() ) {
			return $template;
		}

		$own = get_stylesheet_directory() . '/single-lesson.php';

		return file_exists( $own ) ? $own : $template;
	},
	999
);

/**
 * Print one lesson row's length as m:ss, the way the prototype's fmt0() does.
 *
 * @param int $seconds Length in seconds.
 *
 * @return string
 */
function cas_lesson_clock( $seconds ) {
	$seconds = max( 0, (int) $seconds );

	return floor( $seconds / 60 ) . ':' . str_pad( (string) ( $seconds % 60 ), 2, '0', STR_PAD_LEFT );
}

/**
 * Whether the current visitor may open a lesson that is not free, public or theirs by
 * enrolment — an administrator or the course's own instructor, for instance. Tutor's own
 * test decides, so the list never offers a link that Tutor would then refuse.
 *
 * @param int $lesson_id Lesson post ID.
 * @param int $course_id Course post ID.
 *
 * @return bool
 */
function cas_lesson_can_open( $lesson_id, $course_id ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	try {
		return (bool) \Tutor\Models\CourseModel::has_course_content_access(
			array(
				'current_post_type' => 'lesson',
				'current_post_id'   => (int) $lesson_id,
				'course_id'         => (int) $course_id,
				'is_public'         => false,
				'is_enrolled'       => false,
			)
		);
	} catch ( \Throwable $e ) {
		return false;
	}
}

/**
 * Every lesson of a course, in Tutor's own order, with the state the page needs.
 *
 * Read from the same topics -> lessons tree the single-course syllabus reads, so the two
 * cannot disagree about order or count.
 *
 * @param int $course_id Course post ID.
 *
 * @return array{ topics: int, items: array<int, array<string, mixed>> }
 */
function cas_lesson_outline( $course_id ) {
	$user_id  = get_current_user_id();
	$enrolled = (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $course_id );
	$public   = (bool) \TUTOR\Course_List::is_public( $course_id );
	$topics   = get_posts(
		array(
			'post_type'   => 'topics',
			'post_parent' => $course_id,
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		)
	);
	$items    = array();

	foreach ( $topics as $ti => $topic ) {
		$lessons = get_posts(
			array(
				'post_type'   => 'lesson',
				'post_parent' => $topic->ID,
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'ASC',
			)
		);

		foreach ( $lessons as $lesson ) {
			$video   = get_post_meta( $lesson->ID, '_video', true );
			$runtime = is_array( $video ) && ! empty( $video['runtime'] ) ? (array) $video['runtime'] : array();
			$preview = (bool) get_post_meta( $lesson->ID, '_is_preview', true );

			$items[] = array(
				'id'       => (int) $lesson->ID,
				'title'    => get_the_title( $lesson ),
				'sub'      => trim( wp_strip_all_tags( (string) $lesson->post_content ) ),
				'topic'    => (int) $ti,
				'topic_t'  => get_the_title( $topic ),
				'thumb'    => (int) get_post_thumbnail_id( $lesson->ID ),
				'seconds'  => (int) ( $runtime['hours'] ?? 0 ) * 3600 + (int) ( $runtime['minutes'] ?? 0 ) * 60 + (int) ( $runtime['seconds'] ?? 0 ),
				'open'     => $enrolled || $public || $preview || cas_lesson_can_open( $lesson->ID, $course_id ),
				'done'     => $user_id && $enrolled && (bool) tutor_utils()->is_completed_lesson( $lesson->ID, $user_id ),
				'url'      => get_permalink( $lesson ),
			);
		}
	}

	return array(
		'topics' => count( $topics ),
		'items'  => $items,
	);
}

/**
 * The film for a lesson, as the stage should draw it.
 *
 * Every lesson on this site stores its film as an embed snippet (`source_embedded`) that
 * points at the video host. The prototype's stage is a poster with the player's own
 * iframe laid over it, so the address is lifted out of the snippet and the frame is drawn
 * here. That address is read through get_post_meta(), which inc/cas-video-token.php signs
 * as it is read, and is passed through cas_video_sign_url() as well. That call leaves an
 * address that already carries a signature exactly as it found it, and returns it
 * unchanged when no signing key is configured.
 *
 * A lesson with a different kind of source (YouTube, Vimeo, an uploaded file) keeps
 * Tutor's own player markup, so nothing that works today stops working.
 *
 * @param int $lesson_id Lesson post ID.
 *
 * @return array{ src: string, native: string }
 */
function cas_lesson_film( $lesson_id ) {
	$film  = array(
		'src'    => '',
		'native' => '',
	);
	$video = get_post_meta( $lesson_id, '_video', true );

	if ( ! is_array( $video ) || empty( $video['source'] ) ) {
		return $film;
	}

	if ( 'embedded' === $video['source'] && ! empty( $video['source_embedded'] ) ) {
		if ( preg_match( '~\bsrc\s*=\s*(["\'])(.*?)\1~i', (string) $video['source_embedded'], $m ) ) {
			$src = html_entity_decode( $m[2], ENT_QUOTES, 'UTF-8' );

			if ( function_exists( 'cas_video_sign_url' ) ) {
				$src = cas_video_sign_url( $src );
			}

			$film['src'] = $src;
		}

		return $film;
	}

	if ( function_exists( 'tutor_lesson_video' ) && function_exists( 'tutor_utils' ) ) {
		$info = tutor_utils()->get_video_info( $lesson_id );

		if ( is_object( $info ) && ! empty( $info->source ) ) {
			$film['native'] = (string) tutor_lesson_video( false );
		}
	}

	return $film;
}

/**
 * A lesson's attachments, split the way the prototype splits them: the pictures the lesson
 * is painted from, and everything else.
 *
 * @param int $lesson_id Lesson post ID.
 *
 * @return array{ photos: array<int, object>, files: array<int, object> }
 */
function cas_lesson_attachments( $lesson_id ) {
	$out = array(
		'photos' => array(),
		'files'  => array(),
	);

	if ( ! function_exists( 'tutor_utils' ) ) {
		return $out;
	}

	foreach ( (array) tutor_utils()->get_attachments( $lesson_id ) as $att ) {
		if ( empty( $att->id ) || empty( $att->url ) ) {
			continue;
		}

		if ( wp_attachment_is_image( (int) $att->id ) ) {
			$out['photos'][] = $att;
		} else {
			$out['files'][] = $att;
		}
	}

	return $out;
}

/**
 * The inline icon on a download row. The prototype draws two: a document and a picture.
 *
 * @param object $att Tutor attachment record.
 *
 * @return string
 */
function cas_lesson_file_icon( $att ) {
	$paths = 'image' === ( $att->icon ?? '' )
		? '<rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.5"></circle><path d="M21 16l-5-5-9 8"></path>'
		: '<rect x="5" y="3" width="14" height="18" rx="2"></rect><path d="M9 8h6M9 12h6M9 16h4"></path>';

	return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
}

/**
 * The lesson page.
 *
 * @return string
 */
function cas_render_lesson() {
	$lesson_id = get_queried_object_id();
	$course_id = cas_lesson_course_id( $lesson_id );

	if ( ! $lesson_id || ! $course_id ) {
		return '';
	}

	$user_id  = get_current_user_id();
	$enrolled = (bool) \Tutor\Models\EnrollmentModel::is_enrolled( $course_id );
	$outline  = cas_lesson_outline( $course_id );
	$items    = $outline['items'];
	$phased   = $outline['topics'] > 1;
	$position = 0;

	foreach ( $items as $i => $item ) {
		if ( $item['id'] === $lesson_id ) {
			$position = $i;
			break;
		}
	}

	$current = $items[ $position ] ?? null;
	$next    = $items[ $position + 1 ] ?? null;
	$title   = get_the_title( $course_id );
	$stats   = tutor_utils()->get_course_completed_percent( $course_id, $user_id, true );
	$done_n  = (int) ( $stats['completed_count'] ?? 0 );
	$total_n = (int) ( $stats['total_count'] ?? count( $items ) );
	$percent = $total_n ? (int) round( $done_n / $total_n * 100 ) : 0;
	$film    = cas_lesson_film( $lesson_id );
	$attach  = cas_lesson_attachments( $lesson_id );
	$course_files = function_exists( 'tutor_utils' ) ? (array) tutor_utils()->get_attachments( $course_id ) : array();
	$files   = array_merge( $course_files, $attach['files'] );
	$cats    = wp_get_post_terms( $course_id, 'course-category', array( 'fields' => 'names' ) );
	$tag     = ( ! is_wp_error( $cats ) && $cats ) ? $cats[0] : '';
	$level   = (string) get_post_meta( $course_id, '_tutor_course_level', true );
	$level   = $level ? ucfirst( str_replace( '-', ' ', $level ) ) : '';
	$runtime = cas_course_runtime( $course_id );
	$blurb   = trim( (string) get_post_field( 'post_content', $course_id ) );
	$photos  = cas_course_photos( $course_id );
	$thumb   = $current ? $current['thumb'] : 0;
	$is_tall = false;

	if ( $thumb ) {
		$meta    = wp_get_attachment_metadata( $thumb );
		$is_tall = ! empty( $meta['width'] ) && ! empty( $meta['height'] ) && $meta['height'] > $meta['width'];
	}

	$facts = array_filter(
		array(
			'Media'        => $tag,
			'Level'        => $level,
			'Lessons'      => $items ? (string) count( $items ) : '',
			'Running time' => $runtime ? '~' . $runtime : '',
		)
	);

	ob_start();
	?>
	<header class="page-hero">
		<div class="container">
			<div class="crumbs"><a href="<?php echo esc_url( cas_page_url( 'courses' ) ); ?>">Courses</a> <span aria-hidden="true">&rarr;</span> <a id="lx-course-link" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( $title ); ?></a></div>
			<div class="lesson-meta-row" id="lx-meta">
				<?php foreach ( $facts as $label => $value ) : ?>
					<span class="lf"><i><?php echo esc_html( $label ); ?></i><?php echo esc_html( $value ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="logo2"><img src="<?php echo esc_url( cas_brand_image( 'cas_logo2' ) ); ?>" alt="Crystal Lau Art" decoding="async"></div>
		</div>
	</header>

	<main class="container" id="content" tabindex="-1" style="padding-bottom:30px;">
		<div class="lesson-cols">

			<div>
				<div class="player">
					<div class="stage<?php echo ( $film['src'] || $film['native'] ) ? ' has-video' : ' is-missing'; ?><?php echo $is_tall ? ' is-poster-tall' : ''; ?>" id="stage">
						<?php
						if ( $thumb ) {
							echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
								$thumb,
								'large',
								false,
								array(
									'class'         => 'poster',
									'id'            => 'stage-poster',
									'alt'           => $current ? sprintf( 'Lesson %d — %s', $position + 1, $current['title'] ) : '',
									'sizes'         => '(max-width: 1024px) 94vw, (max-width: 1280px) 64vw, 56vw',
									'fetchpriority' => 'high',
									'decoding'      => 'async',
								)
							);
						}
						?>
						<?php if ( $film['src'] ) : ?>
							<iframe class="gdrive-frame" id="gdrive-frame" title="Lesson video player" src="<?php echo esc_url( $film['src'] ); ?>" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>
						<?php elseif ( $film['native'] ) : ?>
							<div class="cas-lesson-native"><?php echo $film['native']; // phpcs:ignore WordPress.Security.EscapeOutput -- Tutor's own player markup. ?></div>
						<?php endif; ?>
						<div class="stage-missing" id="stage-missing">Video coming soon</div>
						<span class="unit-flag" id="unit-flag"><?php echo esc_html( sprintf( 'Lesson %d — %s', $position + 1, $current ? $current['title'] : '' ) ); ?></span>
					</div>
				</div>

				<?php if ( $enrolled && $total_n && $done_n >= $total_n ) : ?>
					<div class="finish-card show" id="finish-card">
						<span class="fc-star">&#10022;</span>
						<div class="cert">
							<div class="c1">Crystal's Art School</div>
							<div class="c2"><?php echo esc_html( $title ); ?></div>
							<div class="c3"><?php echo esc_html( sprintf( 'completed · %1$d of %1$d %2$s', $total_n, 1 === $total_n ? 'lesson' : 'lessons' ) ); ?></div>
						</div>
						<div class="fc-btns">
							<a class="btn primary big" href="<?php echo esc_url( cas_page_url( 'courses' ) ); ?>">Browse all courses <span class="arr" aria-hidden="true">&rarr;</span></a>
						</div>
					</div>
				<?php endif; ?>

				<div class="unit-panel">
					<?php if ( '' !== $blurb ) : ?>
						<div class="course-desc" id="course-desc">
							<h1><?php echo esc_html( $title ); ?></h1>
							<div class="cd-body">
								<?php
								if ( $photos ) {
									echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
										$photos[0],
										'medium_large',
										false,
										array(
											'class'    => 'cd-cover',
											'alt'      => $title,
											'sizes'    => '(max-width: 620px) 94vw, 320px',
											'decoding' => 'async',
										)
									);
								}
								echo wpautop( wp_kses_post( $blurb ) ); // phpcs:ignore WordPress.Security.EscapeOutput
								?>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $current ) : ?>
						<article class="unit-notes active" data-unit="<?php echo esc_attr( $position ); ?>">
							<?php if ( $phased ) : ?>
								<div style="font-family:var(--font-cond);font-weight:600;font-size:12.5px;letter-spacing:var(--tracking-label);text-transform:uppercase;color:var(--coral);margin-bottom:8px;"><?php echo esc_html( sprintf( 'Phase %d · %s', $current['topic'] + 1, $current['topic_t'] ) ); ?></div>
							<?php endif; ?>
							<h2><?php echo esc_html( $current['title'] ); ?></h2>
							<?php if ( '' !== $current['sub'] ) : ?>
								<p><?php echo esc_html( $current['sub'] ); ?></p>
							<?php endif; ?>
						</article>
					<?php endif; ?>

					<?php if ( $enrolled && $current ) : ?>
						<form class="complete-row" method="post">
							<?php wp_nonce_field( tutor()->nonce_action, tutor()->nonce, false ); ?>
							<input type="hidden" name="lesson_id" value="<?php echo esc_attr( $lesson_id ); ?>">
							<input type="hidden" name="tutor_action" value="tutor_complete_lesson">
							<?php if ( $next ) : ?>
								<input type="hidden" name="cas_next" value="<?php echo esc_attr( $next['id'] ); ?>">
							<?php endif; ?>
							<button class="btn primary big" id="complete-btn" type="submit" name="complete_lesson_btn"><?php echo $next ? 'Mark complete &amp; continue' : 'Mark complete &amp; finish'; ?> <span class="arr" aria-hidden="true">&rarr;</span></button>
							<?php if ( $current['done'] ) : ?>
								<span class="done-tag" id="done-tag">done ✓ — nicely painted</span>
							<?php endif; ?>
						</form>
					<?php endif; ?>
				</div>

				<?php if ( $attach['photos'] ) : ?>
					<div class="curr-card materials-card ref-card" id="ref-card">
						<div class="ch">Reference photos</div>
						<p class="res-note">The photographs this lesson is painted from. Open one to see it full size, or download it to work from.</p>
						<div class="ref-grid" id="ref-grid">
							<?php foreach ( $attach['photos'] as $k => $photo ) : ?>
								<figure>
									<a href="<?php echo esc_url( $photo->url ); ?>" target="_blank" rel="noopener">
										<?php
										echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
											(int) $photo->id,
											'large',
											false,
											array(
												'class'    => 'ref-shot',
												'alt'      => sprintf( 'Reference photograph %d for lesson %d — %s', $k + 1, $position + 1, $current ? $current['title'] : '' ),
												'loading'  => 'lazy',
												'decoding' => 'async',
											)
										);
										?>
									</a>
									<figcaption><span><?php echo esc_html( 'Reference ' . ( $k + 1 ) ); ?></span><a download="<?php echo esc_attr( $photo->name ); ?>" href="<?php echo esc_url( $photo->url ); ?>">Download</a></figcaption>
								</figure>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="curr-card materials-card" id="materials-card">
					<div class="ch">Course materials</div>
					<?php if ( $files ) : ?>
						<div class="res-all" id="res-all">
							<button class="btn primary" type="button" id="res-all-btn">Download all <span class="arr" aria-hidden="true">&darr;</span></button>
							<span class="res-all-note"><?php echo esc_html( count( $files ) . ( 1 === count( $files ) ? ' file' : ' files' ) ); ?></span>
						</div>
						<div class="res-list" id="res-list" style="padding:0 4px 8px;">
							<?php foreach ( $files as $file ) : ?>
								<a class="res-item" download="<?php echo esc_attr( $file->name ); ?>" href="<?php echo esc_url( $file->url ); ?>">
									<span class="res-ico" aria-hidden="true"><?php echo cas_lesson_file_icon( $file ); // phpcs:ignore WordPress.Security.EscapeOutput -- static markup. ?></span>
									<span class="res-body"><span class="nm"><?php echo esc_html( $file->title ); ?></span><br>
										<span class="sz"><?php echo esc_html( strtoupper( (string) $file->ext ) . ' · ' . $file->size ); ?></span></span>
									<span class="res-get">Download &darr;</span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<p class="res-note" id="res-note">Course materials for this course are still to be supplied.</p>
					<?php endif; ?>
				</div>
			</div>

			<aside class="side-stick">
				<?php if ( $enrolled ) : ?>
					<div class="progress-card">
						<div class="pc-top">
							<span class="pc-label">Your progress</span>
							<span class="pc-count"><span id="done-count"><?php echo esc_html( $done_n ); ?></span> of <span id="lx-total"><?php echo esc_html( $total_n ); ?></span></span>
						</div>
						<div class="pbar" id="pbar" role="progressbar" aria-label="Course progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $percent ); ?>" aria-valuetext="<?php echo esc_attr( sprintf( '%d of %d %s complete', $done_n, $total_n, 1 === $total_n ? 'lesson' : 'lessons' ) ); ?>"><div class="pfill" id="pfill" style="width:<?php echo esc_attr( $percent ); ?>%;"></div></div>
					</div>
				<?php endif; ?>

				<div class="curr-card">
					<div id="rows-holder">
						<?php
						foreach ( $items as $i => $item ) :
							$is_now = $i === $position;
							$row_cl = 'unit-row' . ( $item['done'] ? ' done' : '' ) . ( $is_now ? ' current' : '' ) . ( $item['open'] ? '' : ' is-locked' );
							$tag_el = $item['open'] ? 'a' : 'span';

							if ( $phased && ( 0 === $i || $items[ $i - 1 ]['topic'] !== $item['topic'] ) ) :
								?>
								<div class="ch"<?php echo $i ? ' style="margin-top:16px;"' : ''; ?>><?php echo esc_html( sprintf( 'Phase %d — %s', $item['topic'] + 1, $item['topic_t'] ) ); ?></div>
							<?php endif; ?>
							<<?php echo $tag_el; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="<?php echo esc_attr( $row_cl ); ?>" data-odd="<?php echo esc_attr( $i % 2 ); ?>"<?php echo $item['open'] ? ' href="' . esc_url( $item['url'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-current="<?php echo $is_now ? 'true' : 'false'; ?>">
								<span class="st"><?php echo $item['done'] ? '✓' : esc_html( $i + 1 ); ?></span>
								<span class="uthumb-wrap<?php echo $item['thumb'] ? '' : ' is-empty'; ?>">
									<?php
									if ( $item['thumb'] ) {
										echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
											$item['thumb'],
											'thumbnail',
											false,
											array(
												'class'    => 'uthumb',
												'alt'      => '',
												'sizes'    => '78px',
												'loading'  => $i < 6 ? false : 'lazy',
												'decoding' => 'async',
											)
										);
									}
									?>
									<?php if ( $is_now ) : ?>
										<span class="now"><span class="now-dot" aria-hidden="true"></span>Now playing</span>
									<?php endif; ?>
								</span>
								<span class="ut"><?php echo esc_html( $item['title'] ); ?></span><span class="ud"><?php echo esc_html( $item['seconds'] ? cas_lesson_clock( $item['seconds'] ) : '' ); ?></span>
							</<?php echo $tag_el; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
						<?php endforeach; ?>
					</div>
				</div>
			</aside>
		</div>
	</main>
	<?php

	return ob_get_clean();
}

/**
 * "Mark complete & continue" moves to the next lesson.
 *
 * The prototype's button marks the lesson done and then opens the next one. Tutor's own
 * completion form only records the lesson and re-shows the same page, so the form above
 * carries the next lesson's ID and this follows it — but only after Tutor's handler has run
 * to the end, so its nonce, enrolment and validation checks have all already passed. The
 * ID is trusted only if it is a published lesson of the same course.
 *
 * @param int $lesson_id Lesson that has just been completed.
 * @param int $user_id   The student.
 *
 * @return void
 */
function cas_lesson_continue_after_complete( $lesson_id, $user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Tutor verified its nonce before firing this action.
	$next = isset( $_POST['cas_next'] ) ? absint( wp_unslash( $_POST['cas_next'] ) ) : 0;

	if ( ! $next || 'lesson' !== get_post_type( $next ) || 'publish' !== get_post_status( $next ) ) {
		return;
	}

	if ( cas_lesson_course_id( $next ) !== cas_lesson_course_id( (int) $lesson_id ) ) {
		return;
	}

	wp_safe_redirect( get_permalink( $next ) );
	exit;
}
add_action( 'tutor_lesson_completed_after', 'cas_lesson_continue_after_complete', 99, 2 );

/**
 * The lesson page's own stylesheet and script — only when this file is rendering the page.
 *
 * The rules the prototype keeps in the page's own <style> block; the shared lesson layout
 * (`.lesson-cols`, `.stage`, `.unit-row`, `.progress-card` and the rest) is already in
 * industrial-2.css and industrial-3.css.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! cas_lesson_is_ours() ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_style( 'cas-lesson', $uri . '/css/cas-lesson.css', array( 'cas-industrial-3' ), filemtime( $dir . '/css/cas-lesson.css' ) );
		wp_enqueue_script( 'cas-lesson', $uri . '/js/cas-lesson.js', array( 'cas-site-extras' ), filemtime( $dir . '/js/cas-lesson.js' ), true );
	},
	27
);
