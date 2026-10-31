<?php
/**
 * Single course page.
 *
 * Mirrors the prototype's course.dc.html structure so the existing CSS applies, but
 * every value comes from Tutor rather than a generated data file. Tutor renders its own
 * single-course template by default; this replaces it the same way the archive is
 * replaced, and for the same reason — its layout is not the agreed design.
 *
 * Deliberately not reproduced yet: the "test your own photo" block (.own-test). It is
 * developer scaffolding for a purchased/not-purchased switch the CTA does not have.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tutor stores benefits, audience and materials as newline-separated text.
 */
function cas_course_lines( $course_id, $key ) {
	$raw = (string) get_post_meta( $course_id, $key, true );

	return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
}

function cas_course_chips( $course_id ) {
	$level   = get_post_meta( $course_id, '_tutor_course_level', true );
	$lessons = (int) tutor_utils()->get_lesson_count_by_course( $course_id );
	$runtime = cas_course_runtime( $course_id );
	$media   = wp_get_post_terms( $course_id, 'course-category', array( 'fields' => 'names' ) );

	return array_values(
		array_filter(
			[
				! is_wp_error( $media ) && $media ? $media[0] : '',
				$runtime,
				$lessons ? $lessons . ( 1 === $lessons ? ' lesson' : ' lessons' ) : '',
				$level ? str_replace( '-', ' ', $level ) : '',
			]
		)
	);
}

/* ------------------------------------------------------------------
   The course photograph set — direction document section 7.1
   ------------------------------------------------------------------ */

/**
 * 7.1 fixes the set at five pictures per course: one large and four small,
 * named `cloudslesson12.jpg` and `cloudslesson12A`..`D` in her own convention.
 *
 * The featured image is deliberately not one of the five. It is the catalogue
 * card cover, and both courses that have a set ship a different picture for
 * each role — the beginner card carries `Beginner HighlightCover.JPG` while its
 * large detail image is `CloudsLesson12.jpg`. Folding the two together would
 * mean she cannot change one without changing the other.
 */
function cas_course_photo_fields() {
	return [
		'_cas_course_photo_large' => 'Large image',
		'_cas_course_photo_1'     => 'Small image 1',
		'_cas_course_photo_2'     => 'Small image 2',
		'_cas_course_photo_3'     => 'Small image 3',
		'_cas_course_photo_4'     => 'Small image 4',
	];
}

/** Native Tutor builder fields use the same five attachment IDs as the classic editor. */
add_action( 'tutor_course_builder_footer', function () {
	$id = isset( $_GET['course_id'] ) && is_scalar( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0;
	if ( ! $id || 'courses' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) { return; }
	$fields = array();
	foreach ( cas_course_photo_fields() as $key => $label ) {
		$attachment = (int) get_post_meta( $id, $key, true );
		$fields[] = array(
			'name' => $key, 'type' => 'image', 'label' => 'Course Page — ' . $label,
			'defaultValue' => array( 'id' => $attachment, 'url' => $attachment ? (string) wp_get_attachment_image_url( $attachment, 'large' ) : '' ),
			'buttonText' => 'Choose or Upload Picture',
			'helpText' => '_cas_course_photo_large' === $key
				? 'The main course-page photograph (3:2). This five-photo set is separate from the Featured Image used on course cards. Save with Update.'
				: 'One of the four detail photographs below the main picture (4:3). Clicked thumbnails enlarge on the course page. Save with Update.',
		);
	}
	$dir = get_stylesheet_directory();
	wp_enqueue_script( 'cas-course-builder-photos', get_stylesheet_directory_uri() . '/js/cas-course-builder-photos.js', array( 'tutor-course-builder' ), filemtime( $dir . '/js/cas-course-builder-photos.js' ), true );
	wp_localize_script( 'cas-course-builder-photos', 'casCoursePhotoFields', $fields );
} );

add_filter( 'tutor_course_details_response', function ( $data ) {
	foreach ( cas_course_photo_fields() as $key => $label ) {
		$id = (int) get_post_meta( (int) $data['ID'], $key, true );
		$data[ $key ] = array( 'id' => $id, 'url' => $id ? (string) wp_get_attachment_image_url( $id, 'large' ) : '' );
	}
	return $data;
} );

/** Validate the whole selection before changing any of the stored photographs. */
function cas_course_builder_photo_ids( $params ) {
	$ids = array();
	foreach ( cas_course_photo_fields() as $key => $label ) {
		if ( ! array_key_exists( $key, $params ) ) { continue; }
		$value = $params[ $key ];
		if ( is_string( $value ) && '' !== $value && ! ctype_digit( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) { $decoded = json_decode( wp_unslash( $value ), true ); }
			if ( JSON_ERROR_NONE !== json_last_error() ) { return new WP_Error( 'cas_course_photo', 'Invalid course photograph selection. Please choose it again.' ); }
			$value = $decoded;
		}
		$raw = is_array( $value ) ? ( $value['id'] ?? null ) : $value;
		if ( null === $value || '' === $value ) { $raw = 0; }
		if ( ( ! is_int( $raw ) && ! is_string( $raw ) ) || ! preg_match( '/^(0|[1-9][0-9]*)$/', (string) $raw ) ) {
			return new WP_Error( 'cas_course_photo', 'Please choose a valid course photograph from the Media Library.' );
		}
		$id = filter_var( $raw, FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 0 ) ) );
		if ( false === $id || ( $id && ( ! wp_attachment_is_image( $id ) || 'trash' === get_post_status( $id )
			|| ! current_user_can( 'read_post', $id ) || ( function_exists( 'cas_private_is' ) && cas_private_is( $id ) ) ) ) ) {
			return new WP_Error( 'cas_course_photo', 'Course photographs must be available public images, not private course materials.' );
		}
		$ids[ $key ] = $id;
	}
	return $ids;
}

add_filter( 'tutor_is_error_before_course_update', function ( $error, $params ) {
	if ( is_wp_error( $error ) ) { return $error; }
	$ids = cas_course_builder_photo_ids( $params );
	return is_wp_error( $ids ) ? $ids : $error;
}, 10, 2 );

add_action( 'tutor_after_prepare_update_post_meta', function ( $id, $params ) {
	if ( 'courses' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id )
		|| ! function_exists( 'cas_builder_nonce_valid' ) || ! cas_builder_nonce_valid() ) { return; }
	$ids = cas_course_builder_photo_ids( $params );
	if ( is_wp_error( $ids ) ) { return; }
	foreach ( $ids as $key => $attachment ) {
		if ( $attachment ) { update_post_meta( $id, $key, $attachment ); }
		else { delete_post_meta( $id, $key ); }
	}
}, 10, 2 );

/**
 * Registering the meta rather than trusting the save handler alone means absint
 * also runs for anything that writes later — WP-CLI, or the import script that
 * seeded the beginner set.
 */
add_action(
	'init',
	function () {
		foreach ( array_keys( cas_course_photo_fields() ) as $key ) {
			register_post_meta(
				'courses',
				$key,
				[
					'type'              => 'integer',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'absint',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				]
			);
		}
	}
);

/**
 * The set as the page should draw it: the large picture first, then whichever of
 * the four small slots are filled.
 *
 * An attachment that has been deleted from the library leaves its ID behind in
 * the meta, so each one is checked before it is trusted — otherwise a removed
 * picture becomes a thumbnail-shaped hole that clicks to nothing.
 *
 * The large slot falls back to the featured image so a course with no set at all
 * still shows a picture, which is what little-house and urban-sketch do today.
 */
function cas_course_photos( $course_id ) {
	$fields = cas_course_photo_fields();
	$ids    = [];

	foreach ( array_keys( $fields ) as $key ) {
		$att = (int) get_post_meta( $course_id, $key, true );

		if ( $att && wp_attachment_is_image( $att ) ) {
			$ids[ $key ] = $att;
		}
	}

	if ( empty( $ids['_cas_course_photo_large'] ) ) {
		$thumb = (int) get_post_thumbnail_id( $course_id );

		if ( $thumb && wp_attachment_is_image( $thumb ) ) {
			$ids = [ '_cas_course_photo_large' => $thumb ] + $ids;
		}
	}

	return array_values( $ids );
}

/**
 * Render Tutor's course attachment shelf without creating course content.
 *
 * The official template resolves attachments from the current course post. This helper
 * is called while that post is current, after the course ID has been validated above.
 * Tutor owns the attachment markup, escaping and open-mode behavior. A course with no
 * attachments returns an empty string: Tutor's empty state is never printed on a public page.
 */
function cas_render_course_attachments( $course_id ) {
	if ( ! $course_id || ! function_exists( 'tutor_utils' ) || ! function_exists( 'tutor_load_template' ) ) {
		return '';
	}

	$utils = tutor_utils();

	if ( ! is_object( $utils ) || ! method_exists( $utils, 'get_attachments' ) ) {
		return '';
	}

	$attachments = $utils->get_attachments( $course_id );

	// get_attachments() returns an empty array for a course with none, and Tutor's template then
	// prints its own empty state ("No Attachment Found" and a stock picture). Nothing to show means
	// no markup at all, so the caller can leave the whole shelf out.
	if ( ! is_array( $attachments ) || ! $attachments ) {
		return '';
	}

	ob_start();
	tutor_load_template( 'global.attachments' );

	return (string) ob_get_clean();
}

/** The real Tutor curriculum, with the prototype's collapsed phases and lesson covers. */
function cas_render_course_syllabus( $outline ) {
	$phases = array();
	foreach ( $outline['items'] as $index => $item ) { $phases[ $item['topic'] ][ $index ] = $item; }
	ob_start();
	?>
	<div id="co-syllabus" class="syllabus">
		<?php foreach ( $phases as $topic => $items ) : $first = reset( $items ); ?>
			<div class="syl-phase">
				<button type="button" class="syl-phase-btn" aria-expanded="false" aria-controls="cas-phase-<?php echo esc_attr( $topic ); ?>">
					<span class="sp-n">Phase <?php echo esc_html( $topic + 1 ); ?></span>
					<span class="sp-name"><?php echo esc_html( $first['topic_t'] ); ?></span>
					<span class="sp-count"><?php echo esc_html( count( $items ) . ( 1 === count( $items ) ? ' lesson' : ' lessons' ) ); ?></span>
					<svg width="12" height="12" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16L12 18z"/></svg>
				</button>
				<div id="cas-phase-<?php echo esc_attr( $topic ); ?>" class="syl-phase-body" hidden>
					<?php foreach ( $items as $index => $item ) : ?>
						<div class="syl-item" data-odd="<?php echo esc_attr( $index % 2 ); ?>">
							<?php if ( $item['open'] ) : ?><a class="syl-row" href="<?php echo esc_url( $item['url'] ); ?>"><?php else : ?><div class="syl-row is-locked"><?php endif; ?>
								<div class="syl-num"><?php echo $item['done'] ? '&#10003;' : esc_html( $index + 1 ); ?></div>
								<div>
									<div class="syl-title"><?php echo esc_html( $item['title'] ); ?><?php if ( get_post_meta( $item['id'], '_is_preview', true ) ) : ?> <span class="chip">Free preview</span><?php endif; ?></div>
									<?php $subtitle = wp_strip_all_tags( get_post_field( 'post_content', $item['id'] ) ); ?>
									<?php if ( $subtitle ) : ?><div class="syl-sub"><?php echo esc_html( $subtitle ); ?></div><?php endif; ?>
								</div>
								<div class="syl-len">
									<?php if ( ! $item['open'] ) : ?><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" role="img" aria-label="Locked"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><?php endif; ?>
									<?php echo esc_html( $item['seconds'] ? cas_lesson_clock( $item['seconds'] ) : '' ); ?>
								</div>
								<?php if ( $item['thumb'] ) { echo wp_get_attachment_image( $item['thumb'], 'thumbnail', false, array( 'class' => 'syl-thumb', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) ); } ?>
							<?php if ( $item['open'] ) : ?></a><?php else : ?></div><?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

function cas_render_course_single() {
	$id = get_the_ID();

	if ( ! $id || 'courses' !== get_post_type( $id ) ) {
		return '';
	}

	$price   = tutor_utils()->get_raw_course_price( $id );
	$reg     = (float) ( $price->regular_price ?? 0 );
	$sale    = (float) ( $price->sale_price ?? 0 );
	$is_sale = $sale > 0 && $sale < $reg;
	$sym     = cas_course_currency();

	$cats = wp_get_post_terms( $id, 'course-category', [ 'fields' => 'names' ] );
	$tag  = ( ! is_wp_error( $cats ) && $cats ) ? $cats[0] : '';

	$benefits  = cas_course_lines( $id, '_tutor_course_benefits' );
	$audience  = cas_course_lines( $id, '_tutor_course_target_audience' );
	$materials = cas_course_lines( $id, '_tutor_course_material_includes' );
	$attachments_markup = cas_render_course_attachments( $id );

	$outline = cas_lesson_outline( $id );

	ob_start();
	?>
	<div class="container cas-course-page">
		<div class="logo2 is-center"><img src="<?php echo esc_url( cas_brand_image( 'cas_logo2' ) ); ?>" alt="Crystal Lau Art" decoding="async"></div>
		<div class="crumbs">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>">Courses</a>
			<span aria-hidden="true">&rarr;</span>
			<span id="crumb-title"><?php echo esc_html( get_the_title( $id ) ); ?></span>
		</div>

		<header class="course-hero">
			<div class="course-left">
				<?php
				/* 7.1: one large picture, then the strip beneath it. Both slots crop
				   what they are given — .course-cover is 3/2 and .co-thumb is 4/3, both
				   with object-fit: cover — so a picture of any shape lands in its slot
				   rather than moving what is under it. */
				$shots = cas_course_photos( $id );
				$sizes = [
					'hero'  => '(max-width: 900px) 100vw, 446px',
					'thumb' => '(max-width: 900px) 18vw, 80px',
				];
				?>
				<div class="course-cover">
					<?php
					if ( $shots ) {
						echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
							$shots[0],
							'large',
							false,
							[
								'id'            => 'co-cover',
								'sizes'         => $sizes['hero'],
								'alt'           => get_the_title( $id ),
								'fetchpriority' => 'high',
								'decoding'      => 'async',
							]
						);
					}
					?>
					<?php if ( $is_sale ) : ?><span class="sale-flag">Sale</span><?php endif; ?>
				</div>
				<?php
				/* The strip lists every picture in the set, the large one included, so
				   that nothing becomes unreachable once a small one has been clicked.
				   The column count is fixed at five rather than following the set size:
				   with three pictures, three columns of 1fr each make every thumbnail
				   half again as tall and push the CTA below the fold, which is the one
				   thing 7.1 asks this block to avoid. A short set leaves the trailing
				   columns empty. Not a carousel and no timer — 7.1 struck auto-rotation
				   out, and a purchase decision must not be made against moving content. */
				if ( count( $shots ) > 1 ) :
					?>
					<div class="co-thumbs" id="co-thumbs" style="grid-template-columns:repeat(5,1fr)">
						<?php foreach ( $shots as $i => $att ) : ?>
							<button class="co-thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" type="button"
								aria-pressed="<?php echo 0 === $i ? 'true' : 'false'; ?>"
								aria-controls="co-cover"
								aria-label="<?php echo esc_attr( sprintf( 'Show photograph %d of %d', $i + 1, count( $shots ) ) ); ?>"
								data-full="<?php echo esc_url( (string) wp_get_attachment_image_url( $att, 'large' ) ); ?>"
								data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $att, 'large' ) ); ?>"
								data-alt="<?php echo esc_attr( 0 === $i ? get_the_title( $id ) : sprintf( '%s — photograph %d', get_the_title( $id ), $i + 1 ) ); ?>">
								<?php
								echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput -- escapes its own attributes.
									$att,
									'medium',
									false,
									[
										'sizes'    => $sizes['thumb'],
										'alt'      => '',
										'loading'  => 'lazy',
										'decoding' => 'async',
									]
								);
								?>
							</button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<div class="course-buy">
					<div id="co-price" class="course-price">
						<?php if ( $is_sale ) : ?>
							<s class="was"><?php echo esc_html( $sym . $reg ); ?></s> <b class="now"><?php echo esc_html( $sym . $sale ); ?></b>
						<?php else : ?>
							<b class="now"><?php echo esc_html( $sym . $reg ); ?></b>
						<?php endif; ?>
					</div>
					<button class="btn primary" type="button" data-cas-add-to-cart data-course-id="<?php echo esc_attr( $id ); ?>">Add to cart</button>
				</div>
			</div>

			<div class="course-right">
				<?php if ( $tag ) : ?>
					<div class="eyebrow"><span class="star" aria-hidden="true">&#10038;</span> <?php echo esc_html( $tag ); ?> course</div>
				<?php endif; ?>
				<h1 id="co-title" class="marker-title font-a"><?php echo esc_html( get_the_title( $id ) ); ?></h1>
				<?php $film = cas_lesson_film( $id ); ?>
				<div id="co-highlight" class="co-highlight<?php echo $film['src'] || $film['native'] ? ' has-video' : ''; ?>">
					<?php if ( $film['src'] ) : ?>
						<iframe class="gdrive-frame" src="<?php echo esc_url( $film['src'] ); ?>" title="Course preview" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>
					<?php elseif ( $film['native'] ) : ?>
						<?php echo $film['native']; // phpcs:ignore WordPress.Security.EscapeOutput -- Tutor's native video renderer. ?>
					<?php else : ?>
						<div class="co-highlight-empty">Course highlights video &mdash; to be supplied</div>
					<?php endif; ?>
				</div>
				<div id="co-blurb" class="lead"><?php echo wpautop( wp_kses_post( get_post_field( 'post_content', $id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div id="co-chips" class="course-meta-chips">
					<?php foreach ( cas_course_chips( $id ) as $chip ) : ?>
						<span class="cmc <?php echo preg_match( '/^[0-9]+ lessons?$/', $chip ) ? 'is-fact' : 'is-filter'; ?>"><?php echo esc_html( $chip ); ?></span>
					<?php endforeach; ?>
				</div>
				<p class="course-terms-note">Access period and refund terms to be confirmed &mdash; both will be published here before launch.</p>
			</div>
		</header>

		<?php if ( $benefits ) : ?>
			<section id="learn" class="section">
				<div class="section-head">
					<div class="eyebrow"><span class="star" aria-hidden="true">&#10038;</span> By the end</div>
					<h2 class="section-title">What you'll learn</h2>
				</div>
				<ul id="co-learn" class="learn-list">
					<?php foreach ( $benefits as $b ) : ?>
						<li><?php echo esc_html( $b ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $outline['items'] ) : ?>
			<section id="syllabus" class="section">
				<div class="section-head">
					<div class="eyebrow"><span class="star" aria-hidden="true">&#10038;</span> Phase by phase, lesson by lesson</div>
					<h2 class="section-title">Syllabus</h2>
				</div>
				<?php echo cas_render_course_syllabus( $outline ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the renderer. ?>
				<div class="course-teacher">
					<img src="<?php echo esc_url( cas_about_value( 'cas_about_portrait' ) ); ?>" alt="Crystal Lau" width="74" height="74" loading="lazy" decoding="async">
					<div>
						<h3 class="marker-title">Taught by Crystal Lau</h3>
						<p><?php echo esc_html( wp_strip_all_tags( html_entity_decode( cas_about_value( 'cas_about_intro' ), ENT_QUOTES, 'UTF-8' ) ) ); ?> <a class="link-squiggle" href="<?php echo esc_url( cas_page_url( 'about' ) ); ?>">More about Crystal &rarr;</a></p>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $audience ) : ?>
			<section id="who" class="section">
				<div class="section-head">
					<div class="eyebrow"><span class="star" aria-hidden="true">&#10038;</span> Is this you?</div>
					<h2 class="section-title">Who this course is for</h2>
				</div>
				<div id="co-who">
					<?php
					foreach ( $audience as $line ) :
						/* Stored as "Audience — explanation"; split so the two halves can be
						   styled differently, and fall back gracefully if the dash is absent. */
						$parts = array_map( 'trim', explode( '—', $line, 2 ) );
						?>
						<div class="card">
							<h3 class="marker-title"><?php echo esc_html( $parts[0] ); ?></h3>
							<?php if ( ! empty( $parts[1] ) ) : ?>
								<p><?php echo esc_html( $parts[1] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $materials || $attachments_markup ) : ?>
			<section id="materials" class="section">
				<div class="section-head">
					<div class="eyebrow">Included</div>
					<h2 class="section-title">What comes with it</h2>
				</div>
				<?php if ( $materials ) : ?>
					<ul class="learn-list">
						<?php foreach ( $materials as $m ) : ?>
							<li><?php echo esc_html( $m ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $attachments_markup ) : ?>
					<div class="cas-course-attachments">
						<?php echo $attachments_markup; // phpcs:ignore WordPress.Security.EscapeOutput -- Tutor's official template escapes attachment values. ?>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php
		/* The prototype's closing band, `.band-forest` in course.dc.html: a picture, an
		   eyebrow, a Font A heading and a button through to the Free videos page. It was
		   never ported.

		   Built the way the Courses page's FREE TRIAL band is built, and conditional for
		   the same reason: the button must lead somewhere with something on it, so the
		   whole band stays away until a free video is published. The picture is the newest
		   free film's own cover rather than a fixed file, so it cannot go stale, and no
		   paragraph is shipped - the prototype's sentence names lessons on skies, colour
		   mixing and materials, and nothing on this site promises those. */
		$fv_page  = cas_page_url( 'free-videos' );
		$fv_cover = cas_course_free_video_cover();
		?>
		<?php if ( $fv_page && $fv_cover ) : ?>
			<section class="section">
				<div class="band-forest reveal-one">
					<div style="flex:0 0 180px;border-radius:var(--radius-card);overflow:hidden;aspect-ratio:1/1;position:relative;z-index:1;">
						<img src="<?php echo esc_url( $fv_cover ); ?>" alt="Free videos preview" style="width:100%;height:100%;object-fit:cover;" loading="lazy" decoding="async">
					</div>
					<div style="flex:1;">
						<div class="eyebrow"><span class="star">&#10038;</span> Not ready to buy?</div>
						<h2 class="marker-title font-a" style="font-size:clamp(31px,3.1vw,44px);">Try the free videos first.</h2>
					</div>
					<a class="btn-big" href="<?php echo esc_url( $fv_page ); ?>" style="flex:none;">Watch free videos &rarr;</a>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * The cover picture for the Free videos band.
 *
 * The newest published free film that carries a Featured image. Returns an empty
 * string when there is none, which is what keeps the band off the page.
 *
 * @return string
 */
function cas_course_free_video_cover() {
	if ( ! defined( 'CAS_FV_POST_TYPE' ) ) {
		return '';
	}

	$films = get_posts(
		array(
			'post_type'   => CAS_FV_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => 10,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);

	foreach ( $films as $film ) {
		$url = get_the_post_thumbnail_url( $film, 'large' );

		if ( $url ) {
			return (string) $url;
		}
	}

	return '';
}

add_shortcode( 'cas_course', 'cas_render_course_single' );

/**
 * Tutor swaps the single-course template through `template_include` just as it does the
 * archive, so the theme file has to be claimed back at a later priority.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! is_singular( 'courses' ) ) {
			return $template;
		}

		$own = get_stylesheet_directory() . '/single-courses.php';

		return file_exists( $own ) ? $own : $template;
	},
	999
);

/**
 * The add-to-cart button exists here too, so its script and config must load.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_singular( 'courses' ) ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();
		wp_enqueue_style( 'cas-course-single', $uri . '/css/cas-course-single.css', array( 'cas-industrial-3' ), filemtime( $dir . '/css/cas-course-single.css' ) );

		wp_enqueue_script( 'cas-courses', $uri . '/js/cas-courses.js', [], filemtime( $dir . '/js/cas-courses.js' ), true );

		wp_enqueue_script( 'cas-course-photos', $uri . '/js/cas-course-photos.js', [], filemtime( $dir . '/js/cas-course-photos.js' ), true );

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
	},
	25
);

/* ------------------------------------------------------------------
   Editing the photograph set
   ------------------------------------------------------------------ */

/**
 * Five media-library pickers on the course screen. 7.1 opens the pictures for
 * editing and names recommended proportions rather than pixel sizes, so the
 * slots crop what they are given and the box says what each one is shaped like.
 *
 * Tutor puts its own panels below the editor; this sits in the sidebar with the
 * featured image, because that is where the person adding a course is already
 * looking for pictures.
 */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'cas-course-photos',
			'Course photographs',
			'cas_render_course_photos_meta_box',
			'courses',
			'side',
			'default'
		);
	}
);

function cas_render_course_photos_meta_box( $post ) {
	wp_nonce_field( 'cas_save_course_photos', 'cas_course_photos_nonce' );
	?>
	<p class="description" style="margin-top:0">
		The large picture and the four beneath it on the course page. Each one is
		cropped to fit its slot, so any shape works — the large slot is a wide
		landscape and the four are slightly wider than square. Leave a slot empty
		and the strip simply gets shorter. With every slot empty the page falls
		back to the featured image on its own.
	</p>
	<?php
	foreach ( cas_course_photo_fields() as $key => $label ) :
		$att = (int) get_post_meta( $post->ID, $key, true );
		$src = $att ? wp_get_attachment_image_url( $att, 'medium' ) : '';
		?>
		<div class="cas-photo-slot" data-field="<?php echo esc_attr( $key ); ?>" style="margin:0 0 14px">
			<strong><?php echo esc_html( $label ); ?></strong>
			<div class="cas-photo-preview" style="margin:6px 0">
				<?php if ( $src ) : ?>
					<img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:100%;height:auto;display:block;border-radius:4px">
				<?php endif; ?>
			</div>
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $att ?: '' ); ?>">
			<button type="button" class="button cas-photo-choose"><?php echo $att ? 'Replace' : 'Choose picture'; ?></button>
			<button type="button" class="button-link cas-photo-clear" style="margin-left:8px<?php echo $att ? '' : ';display:none'; ?>">Remove</button>
		</div>
		<?php
	endforeach;
}

function cas_save_course_photos( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['cas_course_photos_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cas_course_photos_nonce'] ) ), 'cas_save_course_photos' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array_keys( cas_course_photo_fields() ) as $key ) {
		$att = isset( $_POST[ $key ] ) ? absint( wp_unslash( $_POST[ $key ] ) ) : 0;

		if ( $att ) {
			update_post_meta( $post_id, $key, $att );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}
add_action( 'save_post_courses', 'cas_save_course_photos' );

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'courses' !== $screen->post_type ) {
			return;
		}

		$dir = get_stylesheet_directory();

		wp_enqueue_media();
		wp_enqueue_script(
			'cas-course-photos-admin',
			get_stylesheet_directory_uri() . '/js/cas-course-photos-admin.js',
			[ 'jquery' ],
			filemtime( $dir . '/js/cas-course-photos-admin.js' ),
			true
		);
	}
);
