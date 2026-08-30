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

	return array_values(
		array_filter(
			[
				$runtime ? '~' . $runtime : '',
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
 * Tutor owns the attachment markup, escaping, open-mode behavior and empty state.
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

	if ( ! is_array( $attachments ) ) {
		return '';
	}

	ob_start();
	tutor_load_template( 'global.attachments' );

	return (string) ob_get_clean();
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

	$topics = get_posts(
		[
			'post_type'   => 'topics',
			'post_parent' => $id,
			'numberposts' => -1,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		]
	);

	ob_start();
	?>
	<div class="pg-wrap">
		<div class="crumbs">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'courses' ) ); ?>">Courses</a>
			<span aria-hidden="true">/</span>
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
							<s><?php echo esc_html( $sym . $reg ); ?></s> <b class="now"><?php echo esc_html( $sym . $sale ); ?></b>
						<?php else : ?>
							<b class="now"><?php echo esc_html( $sym . $reg ); ?></b>
						<?php endif; ?>
					</div>
					<button class="cover-btn primary" type="button" data-cas-add-to-cart data-course-id="<?php echo esc_attr( $id ); ?>">Add to cart</button>
				</div>
			</div>

			<div class="course-right">
				<?php if ( $tag ) : ?>
					<div class="eyebrow"><?php echo esc_html( $tag ); ?></div>
				<?php endif; ?>
				<h1 id="co-title" class="marker-title"><?php echo esc_html( get_the_title( $id ) ); ?></h1>
				<div id="co-blurb" class="lead"><?php echo wpautop( wp_kses_post( get_post_field( 'post_content', $id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div id="co-chips" class="course-meta-chips">
					<?php foreach ( cas_course_chips( $id ) as $chip ) : ?>
						<span class="chip"><?php echo esc_html( $chip ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>
		</header>

		<?php if ( $benefits ) : ?>
			<section id="learn" class="section">
				<div class="section-head">
					<div class="eyebrow">What you will learn</div>
					<h2 class="section-title">By the end of this course</h2>
				</div>
				<ul id="co-learn" class="learn-list">
					<?php foreach ( $benefits as $b ) : ?>
						<li><?php echo esc_html( $b ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $topics ) : ?>
			<section id="syllabus" class="section">
				<div class="section-head">
					<div class="eyebrow">Syllabus</div>
					<h2 class="section-title">What is inside</h2>
				</div>
				<div id="co-syllabus" class="syllabus">
					<?php
					$n = 0;

					foreach ( $topics as $topic ) :
						$lessons = get_posts(
							[
								'post_type'   => 'lesson',
								'post_parent' => $topic->ID,
								'numberposts' => -1,
								'orderby'     => 'menu_order',
								'order'       => 'ASC',
							]
						);

						foreach ( $lessons as $lesson ) :
							$n++;
							$video   = get_post_meta( $lesson->ID, '_video', true );
							$rt      = $video['runtime'] ?? [];
							$mins    = (int) ( $rt['hours'] ?? 0 ) * 60 + (int) ( $rt['minutes'] ?? 0 );
							$preview = get_post_meta( $lesson->ID, '_is_preview', true );
							?>
							<div class="syl-item">
								<div class="syl-row">
									<div class="syl-num"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></div>
									<div>
										<div class="syl-title">
											<?php echo esc_html( $lesson->post_title ); ?>
											<?php if ( $preview ) : ?>
												<span class="chip">Free preview</span>
											<?php endif; ?>
										</div>
										<?php if ( $lesson->post_content ) : ?>
											<div class="syl-sub"><?php echo esc_html( wp_strip_all_tags( $lesson->post_content ) ); ?></div>
										<?php endif; ?>
									</div>
									<div class="syl-len"><?php echo esc_html( $mins ? $mins . 'm' : '' ); ?></div>
								</div>
							</div>
							<?php
						endforeach;
					endforeach;
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $audience ) : ?>
			<section id="who" class="section">
				<div class="section-head">
					<div class="eyebrow">Who it is for</div>
					<h2 class="section-title">This course suits</h2>
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
				<div class="cas-course-attachments">
					<?php echo $attachments_markup; // phpcs:ignore WordPress.Security.EscapeOutput -- Tutor's official template escapes attachment values. ?>
				</div>
			</section>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
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

		wp_enqueue_script( 'cas-courses', $uri . '/js/cas-courses.js', [], filemtime( $dir . '/js/cas-courses.js' ), true );

		/* A course with one picture has no strip, so it has nothing to switch. */
		if ( count( cas_course_photos( get_queried_object_id() ) ) > 1 ) {
			wp_enqueue_script( 'cas-course-photos', $uri . '/js/cas-course-photos.js', [], filemtime( $dir . '/js/cas-course-photos.js' ), true );
		}

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
