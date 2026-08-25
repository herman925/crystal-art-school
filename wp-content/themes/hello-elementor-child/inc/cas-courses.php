<?php
/**
 * Courses catalogue.
 *
 * The prototype rendered these cards from a generated js/course-data.js. Here they come
 * from real Tutor LMS course posts, so adding a course is publishing a course rather
 * than regenerating a data file.
 *
 * Everything the card shows is read through Tutor's own API rather than raw post meta,
 * so the pricing shape (regular / sale / display, tax handling) stays whatever Tutor
 * decides it is rather than something reimplemented here that drifts on a plugin
 * update. The one exception is the course level, which Tutor exposes only as meta.
 *
 * The filter rail is built from the courses actually on the page and only offers a
 * control that can change the result, so a catalogue with nothing to filter by renders
 * no rail at all rather than a row of chips that all mean "everything".
 */

defined( 'ABSPATH' ) || exit;

/**
 * Human runtime from Tutor's duration array, matching the prototype's format:
 * "2h 35m", "2h", "35m".
 */
function cas_course_runtime( $course_id ) {
	$d = tutor_utils()->get_course_duration( $course_id, true );

	if ( ! $d ) {
		return '';
	}

	$h = (int) ( $d['durationHours'] ?? 0 );
	$m = (int) ( $d['durationMinutes'] ?? 0 );

	if ( $h && $m ) {
		return $h . 'h ' . $m . 'm';
	}

	if ( $h ) {
		return $h . 'h';
	}

	return $m ? $m . 'm' : '';
}

/**
 * Total runtime in seconds.
 *
 * Tutor's `durationSeconds` is the seconds *component* of an h/m/s triple, not a total,
 * so the three parts have to be recombined before anything can be compared against a
 * length band.
 */
function cas_course_total_seconds( $course_id ) {
	$d = tutor_utils()->get_course_duration( $course_id, true );

	if ( ! $d ) {
		return 0;
	}

	return (int) ( $d['durationHours'] ?? 0 ) * 3600
		+ (int) ( $d['durationMinutes'] ?? 0 ) * 60
		+ (int) ( $d['durationSeconds'] ?? 0 );
}

/**
 * Five length bands, not three. With three, every course over two hours fell into one
 * chip and the filter stopped telling you anything (client feedback 2026-08-08). The
 * thresholds are the prototype's, so a course lands in the band it landed in there.
 */
function cas_course_duration_bucket( $course_id ) {
	$s = cas_course_total_seconds( $course_id );

	if ( ! $s ) {
		return '';
	}

	if ( $s < 1800 ) {
		return 'xs';
	}

	if ( $s < 3600 ) {
		return 'short';
	}

	if ( $s < 7200 ) {
		return 'medium';
	}

	if ( $s < 14400 ) {
		return 'long';
	}

	return 'xl';
}

function cas_course_currency() {
	if ( function_exists( 'tutor_get_currency_symbol_by_code' ) ) {
		return tutor_get_currency_symbol_by_code( get_tutor_option( 'currency_code' ) );
	}

	return '';
}

/**
 * A price with its currency symbol.
 *
 * Tutor stores prices as floats, so printing one straight out turns 58.50 into
 * "£58.5". Pence are printed when the price has any and left off when it has none, so
 * the whole-pound prices the catalogue carries today read exactly as they did.
 */
function cas_course_price( $amount ) {
	$amount   = (float) $amount;
	$decimals = abs( $amount - round( $amount ) ) < 0.005 ? 0 : 2;

	return cas_course_currency() . number_format( $amount, $decimals );
}

/**
 * A taxonomy's terms for one course as slug => name.
 */
function cas_course_terms( $course_id, $taxonomy ) {
	$terms = wp_get_post_terms( $course_id, $taxonomy );
	$out   = [];

	if ( is_wp_error( $terms ) ) {
		return $out;
	}

	foreach ( $terms as $term ) {
		$out[ $term->slug ] = $term->name;
	}

	return $out;
}

/**
 * Everything the card and the filter rail need to know about one course.
 *
 * Only facts Tutor actually stores are in here. There is no medium or theme of our own:
 * medium is Tutor's `course-category`, theme is Tutor's `course-tag`, and length is
 * banded from the runtime Tutor already computes. Adding a facet Tutor does not store
 * would mean inventing the data to fill it, and then inventing it again for every
 * course she adds.
 */
function cas_course_facets( $course_id ) {
	$price   = tutor_utils()->get_raw_course_price( $course_id );
	$reg     = (float) ( $price->regular_price ?? 0 );
	$sale    = (float) ( $price->sale_price ?? 0 );
	$is_sale = $sale > 0 && $sale < $reg;

	return [
		'medium'     => cas_course_terms( $course_id, 'course-category' ),
		'theme'      => cas_course_terms( $course_id, 'course-tag' ),
		'difficulty' => (string) get_post_meta( $course_id, '_tutor_course_level', true ),
		'duration'   => cas_course_duration_bucket( $course_id ),
		'regular'    => $reg,
		'now'        => $is_sale ? $sale : $reg,
		'is_sale'    => $is_sale,
	];
}

/**
 * One catalogue card. Markup mirrors the prototype's courseCard() exactly, including
 * the data-* attributes the filter rail matches on.
 */
function cas_course_card( $course_id, $index ) {
	$f = cas_course_facets( $course_id );

	$media = array_keys( $f['medium'] );
	$tag   = $f['medium'] ? reset( $f['medium'] ) : '';

	$lessons = (int) tutor_utils()->get_lesson_count_by_course( $course_id );
	$runtime = cas_course_runtime( $course_id );

	$meta = array_filter(
		[
			$runtime ? '~' . $runtime : '',
			$lessons ? $lessons . ( 1 === $lessons ? ' lesson' : ' lessons' ) : '',
			$f['difficulty'] ? str_replace( '-', ' ', $f['difficulty'] ) : '',
		]
	);

	/* The first row is above the fold at every width, so the first card is eager and
	   high priority and the rest stay lazy. */
	$img_attr = 0 === $index ? 'fetchpriority="high" decoding="async"' : 'loading="lazy" decoding="async"';
	$cover    = get_the_post_thumbnail_url( $course_id, 'large' );

	ob_start();
	?>
	<div class="lesson-cell"
		data-id="<?php echo esc_attr( $course_id ); ?>"
		data-cat="<?php echo esc_attr( $media ? $media[0] : '' ); ?>"
		data-media="<?php echo esc_attr( implode( '|', $media ) ); ?>"
		data-theme="<?php echo esc_attr( implode( '|', array_keys( $f['theme'] ) ) ); ?>"
		data-difficulty="<?php echo esc_attr( $f['difficulty'] ); ?>"
		data-duration="<?php echo esc_attr( $f['duration'] ); ?>"
		data-price="<?php echo esc_attr( $f['now'] ); ?>"
		<?php echo $f['is_sale'] ? 'data-sale="1"' : ''; ?>>
		<div class="cover-card">
			<?php if ( $cover ) : ?>
				<img src="<?php echo esc_url( $cover ); ?>" <?php echo $img_attr; // phpcs:ignore WordPress.Security.EscapeOutput ?> alt="<?php echo esc_attr( get_the_title( $course_id ) . ( $tag ? ' — ' . $tag . ' course' : '' ) ); ?>">
			<?php endif; ?>
			<?php if ( $tag ) : ?>
				<div class="media-tag"><?php echo esc_html( $tag ); ?></div>
			<?php endif; ?>
			<?php if ( $f['is_sale'] ) : ?>
				<div class="sale-ribbon"><span>Sale</span></div>
			<?php endif; ?>
			<div class="scrim"><div class="t"><?php echo esc_html( get_the_title( $course_id ) ); ?></div></div>
			<div class="actions">
				<button class="cover-btn primary" type="button" data-cas-add-to-cart data-course-id="<?php echo esc_attr( $course_id ); ?>">
					<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1.4"></circle><circle cx="18" cy="20" r="1.4"></circle><path d="M2 3h3.2l2.5 12.2a1.5 1.5 0 0 0 1.5 1.2h9a1.5 1.5 0 0 0 1.5-1.2L21.5 7H6.5"></path></svg>
					Add to cart
				</button>
				<a class="cover-btn secondary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">View course <span class="arr" aria-hidden="true">&rarr;</span></a>
			</div>
		</div>
		<div class="meta">
			<span class="level"><?php echo esc_html( implode( ' · ', $meta ) ); ?></span>
			<span class="price">
				<?php if ( $f['is_sale'] ) : ?>
					<s><?php echo esc_html( cas_course_price( $f['regular'] ) ); ?></s> <b class="now"><?php echo esc_html( cas_course_price( $f['now'] ) ); ?></b>
				<?php else : ?>
					<?php echo esc_html( cas_course_price( $f['regular'] ) ); ?>
				<?php endif; ?>
			</span>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/* ---------------------------------------------------------------------------
 * Filter and sort rail
 * ------------------------------------------------------------------------ */

/**
 * One chip. A disabled chip is a band nothing falls into: the length row is a scale, and
 * a scale with holes punched in it no longer says what the range is, so empty bands stay
 * and are marked instead of being dropped.
 */
function cas_filter_chip( $val, $label, $active = false, $empty = false ) {
	return sprintf(
		'<button class="fopt%1$s%2$s" type="button" data-val="%3$s" aria-pressed="%4$s"%5$s>%6$s</button>',
		$active ? ' active' : '',
		$empty ? ' is-empty' : '',
		esc_attr( $val ),
		$active ? 'true' : 'false',
		$empty ? ' disabled aria-disabled="true"' : '',
		esc_html( $label )
	);
}

/**
 * One chip row. $options is a list of [ value, label, is_empty ]; the "Any" chip that
 * clears the group is added here so every group has one and they cannot drift apart.
 */
function cas_filter_group( $key, $title, array $options, $all_label = 'Any' ) {
	$chips = cas_filter_chip( 'all', $all_label, true );

	foreach ( $options as $opt ) {
		$chips .= cas_filter_chip( $opt[0], $opt[1], false, ! empty( $opt[2] ) );
	}

	return '<div class="fgroup" data-group="' . esc_attr( $key ) . '">'
		. '<div class="fg-title">' . esc_html( $title ) . '</div>'
		. '<div class="fg-opts">' . $chips . '</div>'
		. '</div>';
}

/**
 * A round-numbered range for the price slider, ported from the prototype's niceRange():
 * a step small enough to give at least five stops and large enough not to give more than
 * twenty, then the ends rounded outwards onto it.
 */
function cas_price_range( array $prices ) {
	$lo   = min( $prices );
	$hi   = max( $prices );
	$span = max( $hi - $lo, 1 );

	$steps = [ 1, 2, 5, 10, 20, 25, 50, 100, 200 ];
	$step  = 200;

	foreach ( $steps as $candidate ) {
		if ( $span / $candidate <= 20 ) {
			$step = $candidate;
			break;
		}
	}

	while ( $step > 1 && $span / $step < 5 ) {
		$step = $steps[ array_search( $step, $steps, true ) - 1 ];
	}

	return [
		'min'  => (int) ( floor( $lo / $step ) * $step ),
		'max'  => (int) ( ceil( $hi / $step ) * $step ),
		'step' => $step,
	];
}

/**
 * The length bands' labels. These are display strings for values the code derives, so
 * they are locked here rather than being content.
 */
function cas_duration_labels() {
	return [
		'xs'     => 'Under 30m',
		'short'  => '30m–1h',
		'medium' => '1–2h',
		'long'   => '2–4h',
		'xl'     => '4h+',
	];
}

/**
 * The filter rail, built from the courses actually on the page.
 *
 * A group is offered only where it can change the result: two or more terms, two or more
 * levels, two or more occupied length bands, both sides of the sale switch present, and a
 * price range wider than a single value. Four watercolour courses therefore get no Medium
 * row, because every chip in it would return all four. Where nothing survives, the whole
 * rail is dropped by the caller.
 */
function cas_course_filter_rail( array $rows ) {
	$groups = '';

	$union = static function ( $key ) use ( $rows ) {
		$out = [];

		foreach ( $rows as $row ) {
			$out += $row[ $key ];
		}

		return $out;
	};

	$as_options = static function ( array $terms ) {
		$out = [];

		foreach ( $terms as $slug => $name ) {
			$out[] = [ $slug, $name ];
		}

		return $out;
	};

	$media = $union( 'medium' );

	if ( count( $media ) > 1 ) {
		$groups .= cas_filter_group( 'medium', 'Medium', $as_options( $media ) );
	}

	$theme = $union( 'theme' );

	if ( count( $theme ) > 1 ) {
		$groups .= cas_filter_group( 'theme', 'Theme', $as_options( $theme ) );
	}

	$levels = array_values( array_unique( array_filter( wp_list_pluck( $rows, 'difficulty' ) ) ) );

	if ( count( $levels ) > 1 ) {
		$options = [];

		foreach ( $levels as $level ) {
			$options[] = [ $level, ucfirst( str_replace( [ '-', '_' ], ' ', $level ) ) ];
		}

		$groups .= cas_filter_group( 'difficulty', 'Difficulty', $options );
	}

	$prices = array_values( array_filter( wp_list_pluck( $rows, 'now' ), static function ( $p ) {
		return is_numeric( $p );
	} ) );

	if ( $prices && min( $prices ) < max( $prices ) ) {
		$pr  = cas_price_range( $prices );
		$sym = cas_course_currency();

		/* The prototype built this input in JS to dodge a preview-runtime quirk that
		   re-pinned its value attribute. Nothing does that here, so it is markup. */
		$groups .= '<div class="fgroup">'
			. '<div class="fg-title">Max price</div>'
			. '<div class="price-val" id="lesson-price-val" data-symbol="' . esc_attr( $sym ) . '">Any price</div>'
			. '<div class="slider-wrap">'
			. '<div class="slider-ui" id="lesson-price-ui" style="--pct:100%;">'
			. '<div class="slider-track"></div><div class="slider-fill"></div>'
			. '<input class="price-slider" id="lesson-price-slider" type="range"'
			. ' min="' . esc_attr( $pr['min'] ) . '" max="' . esc_attr( $pr['max'] ) . '"'
			. ' step="' . esc_attr( $pr['step'] ) . '" value="' . esc_attr( $pr['max'] ) . '"'
			. ' aria-label="Maximum price" aria-valuetext="Any price">'
			. '<div class="slider-knob"></div>'
			. '</div>'
			. '<div class="price-scale">'
			. '<span>' . esc_html( 0 === $pr['min'] ? 'Free' : $sym . $pr['min'] ) . '</span>'
			. '<span>' . esc_html( $sym . $pr['max'] . '+' ) . '</span>'
			. '</div>'
			. '</div></div>';
	}

	$buckets = array_filter( wp_list_pluck( $rows, 'duration' ) );

	if ( count( array_unique( $buckets ) ) > 1 ) {
		$options = [];

		foreach ( cas_duration_labels() as $band => $label ) {
			$options[] = [ $band, $label, ! in_array( $band, $buckets, true ) ];
		}

		$groups .= cas_filter_group( 'duration', 'Length', $options );
	}

	$sale = wp_list_pluck( $rows, 'is_sale' );

	if ( in_array( true, $sale, true ) && in_array( false, $sale, true ) ) {
		$groups .= cas_filter_group( 'offer', 'Offers', [ [ 'sale', 'On sale' ] ], 'All' );
	}

	if ( ! $groups ) {
		return '';
	}

	return '<aside class="filter-panel" aria-label="Filter courses">'
		. '<div class="fp-head"><span class="fp-title">Filter</span>'
		. '<button class="fp-clear" type="button" id="fp-clear">Clear all</button></div>'
		. $groups
		. '</aside>';
}

/**
 * Sort is its own card, above the filters, as in the prototype. Featured is the
 * catalogue order Tutor's Order box sets; the other two read the price already on each
 * card, so nothing has to be re-queried.
 */
function cas_course_sort_panel() {
	return '<aside class="filter-panel sort-panel" aria-label="Sort courses">'
		. '<div class="fp-head"><span class="fp-title">Sort by</span></div>'
		. '<div class="fg-opts" id="lesson-sort">'
		. cas_filter_chip( 'featured', 'Featured', true )
		. cas_filter_chip( 'price-asc', 'Price ↑' )
		. cas_filter_chip( 'price-desc', 'Price ↓' )
		. '</div></aside>';
}

/* ---------------------------------------------------------------------------
 * Three ways to study — direction document 1.4.8
 * ------------------------------------------------------------------------ */

/**
 * The three membership cards.
 *
 * 1.4.8 opens exactly two fields per card, the description and the background
 * photograph, and locks the rest by name: the tier names are quoted again in 7.2 and in
 * the checkout copy, so a rename would leave the checkout talking about a tier that no
 * longer exists, and the price line, the "most picked" marker and the button are locked
 * in the same paragraph. Everything locked is therefore in this array and not in the
 * Customizer.
 *
 * The photographs are Crystal's own, uploaded to the media library rather than shipped
 * inside the theme: the field is hers to change, and her replacement will land in the
 * library too. They are resolved by attachment ID at render, so deleting one degrades to
 * the card's base colour instead of a broken URL.
 */
function cas_tiers() {
	return [
		'wanderer'   => [
			'name'     => 'Wanderer',
			'price'    => 'Free',
			'class'    => '',
			'btn'      => 'ghost',
			'text'     => 'Browse the blog and watch the free preview lessons.',
			'photo_id' => 125,
		],
		'apprentice' => [
			'name'     => 'Apprentice',
			'price'    => 'Per-course purchase',
			'class'    => ' highlight',
			'btn'      => '',
			'text'     => 'Full video access, downloadable materials, resume from anywhere.',
			'photo_id' => 126,
		],
		'fellow'     => [
			'name'     => 'Fellow',
			'price'    => 'Per-course + Discord',
			'class'    => ' fellow',
			'btn'      => '',
			'text'     => 'Everything in Apprentice, plus teacher Q&amp;A in a private Discord channel.',
			'photo_id' => 127,
		],
	];
}

function cas_tier_default( $slug, $field ) {
	$tiers = cas_tiers();

	if ( 'photo' === $field ) {
		$id = $tiers[ $slug ]['photo_id'] ?? 0;

		return $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';
	}

	return $tiers[ $slug ]['text'] ?? '';
}

/**
 * Falls back to the shipped value when the stored one is empty, which is what a
 * Customizer "Remove image" leaves behind — `get_theme_mod` returns that empty string in
 * preference to the registered default, and the card would then paint no photograph at
 * all. Same guard as `cas_brand_image()`, deliberately, because the hero's missing one
 * is the trap this pattern exists to avoid.
 */
function cas_tier_value( $slug, $field ) {
	$default = cas_tier_default( $slug, $field );
	$set     = get_theme_mod( "cas_tier_{$slug}_{$field}", $default );

	return $set ? $set : $default;
}

/**
 * The Wanderer button needs a lesson that is genuinely free to reach. Tutor marks one
 * with `_is_preview`, so the destination is the first preview lesson whose course is
 * published — a preview lesson under a draft course 404s for a visitor. When none
 * qualifies the button is not rendered, the same posture as the Gallery's Buy button,
 * which stays away rather than pointing at something that is not there.
 */
function cas_first_preview_lesson_url() {
	static $url = null;

	if ( null !== $url ) {
		return $url;
	}

	$lessons = get_posts(
		[
			'post_type'      => tutor()->lesson_post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'meta_key'       => '_is_preview', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => 1, // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		]
	);

	$url = '';

	foreach ( $lessons as $lesson ) {
		$course = tutor_utils()->get_course_id_by_subcontent( $lesson->ID );

		if ( $course && 'publish' === get_post_status( $course ) ) {
			$url = get_permalink( $lesson );
			break;
		}
	}

	return $url;
}

function cas_render_membership_tiers() {
	/* 1.4.8 locks the button destinations as well as their labels. The prototype sent
	   "Browse courses" to its sign-up page, which carried a tier picker; this build has
	   no such picker, so a button reading "Browse courses" goes to the catalogue. */
	$browse = get_post_type_archive_link( tutor()->course_post_type );
	$free   = cas_first_preview_lesson_url();

	ob_start();
	?>
	<section class="section" id="membership">
		<div class="section-head reveal-one">
			<div class="eyebrow"><span class="star">&#10038;</span> Membership</div>
			<h2 class="section-title">Three Ways to Study</h2>
		</div>
		<div class="tier-grid reveal">
			<?php foreach ( cas_tiers() as $slug => $tier ) : ?>
				<?php $photo = cas_tier_value( $slug, 'photo' ); ?>
				<div class="tier-card<?php echo esc_attr( $tier['class'] ); ?>"
					<?php echo $photo ? 'style="background-image:url(\'' . esc_url( $photo ) . '\');"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
					<?php if ( 'apprentice' === $slug ) : ?>
						<div class="tier-pick">most picked &#10038;</div>
					<?php endif; ?>
					<div class="tname"><?php echo esc_html( $tier['name'] ); ?></div>
					<div class="tprice"><?php echo esc_html( $tier['price'] ); ?></div>
					<p style="margin:0;"><?php echo wp_kses_post( cas_tier_value( $slug, 'text' ) ); ?></p>
					<?php if ( 'wanderer' === $slug ) : ?>
						<?php if ( $free ) : ?>
							<a class="btn ghost" href="<?php echo esc_url( $free ); ?>" style="margin-top:16px;">Start the free lesson <span class="arr" aria-hidden="true">&rarr;</span></a>
						<?php endif; ?>
					<?php else : ?>
						<a class="btn" href="<?php echo esc_url( $browse ); ?>" style="margin-top:16px;">Browse courses</a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * Registered after inc/cas-customizer-map.php, which creates the "Pages" panel at
 * priority 100. Registering into a panel that does not exist yet works, but only by
 * accident of when the Customizer resolves the two, and this section is the one place in
 * the theme that would depend on it.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_courses_tiers',
			[
				'title'       => 'Courses &mdash; Three ways to study',
				'panel'       => 'cas_pages',
				'priority'    => 55,
				'description' => 'The three membership cards below the catalogue. The tier names, prices, the "most picked" marker and the buttons are fixed &mdash; the checkout and the course pages quote them, so they cannot be renamed here.',
			]
		);

		foreach ( cas_tiers() as $slug => $tier ) {
			$fields = [
				'text'  => [
					'label' => sprintf( '%s &mdash; description', $tier['name'] ),
					'desc'  => 'One or two short sentences. The card is a fixed height beside the other two, so keep it to about the length of the current text.',
				],
				'photo' => [
					'label' => sprintf( '%s &mdash; background photograph', $tier['name'] ),
					/* 1.4.8 requires this warning to sit beside the field: the overlay is
					   built to hold up against a bright photograph, but a darker one
					   leaves far more room and the text reads better on it. */
					'desc'  => 'Please choose a darker or lower-contrast photograph &mdash; the tier name, price and description are printed on top of it. Recommended about 760 x 600px. Any size works, because the photograph is cropped to fill the card.',
				],
			];

			foreach ( $fields as $field => $spec ) {
				$key = "cas_tier_{$slug}_{$field}";

				$wp_customize->add_setting(
					$key,
					[
						'default'           => cas_tier_default( $slug, $field ),
						'sanitize_callback' => 'photo' === $field ? 'esc_url_raw' : 'wp_kses_post',
						'transport'         => 'refresh',
					]
				);

				$args = [
					'label'       => $spec['label'],
					'description' => $spec['desc'],
					'section'     => 'cas_page_courses_tiers',
					'settings'    => $key,
				];

				if ( 'photo' === $field ) {
					$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, $args ) );
				} else {
					$args['type'] = 'textarea';
					$wp_customize->add_control( $key, $args );
				}
			}
		}
	},
	110
);

/* ---------------------------------------------------------------------------
 * Page introduction and the FREE TRIAL band - direction document 1.4.8, 4.2
 * ------------------------------------------------------------------------ */

/**
 * Crystal's own introduction to the catalogue.
 *
 * Supplied 2026-08-16 as `assets/01 - Courses/All courses 簡介.docx` and printed here word
 * for word. 4.2 also asks for a Read More fold and says she marks the cut point herself
 * rather than the system truncating at a fixed length. She has marked none, and this is
 * two sentences, so nothing is folded.
 */
function cas_courses_intro_default() {
	return 'This online course platform offers high-definition video tutorials covering a variety of themes and mediums. Through step-by-step guidance, detailed supply lists, and practice handouts, learners can build a solid foundation while enjoying the creative process and developing their own unique style.';
}

/**
 * Her Free Trial photograph, uploaded to the media library rather than shipped inside
 * the theme so the field is hers to change and her replacement lands beside it.
 */
const CAS_TRIAL_PHOTO_ID = 229;

function cas_trial_photo_default() {
	return (string) wp_get_attachment_image_url( CAS_TRIAL_PHOTO_ID, 'large' );
}

/**
 * The FREE TRIAL band, 1.4.8: the eyebrow, the Font A heading and the button, in the
 * prototype's `.band-forest.is-trial`.
 *
 * Two parts are conditional on purpose. The button needs a lesson that is genuinely
 * free to reach, so it uses the same published-course test as the Wanderer card and
 * stays away rather than pointing at a 404. And the band ships no paragraph: 1.4.8
 * lists three parts and a paragraph is not one of them, and the prototype's sentence
 * describes a free intro course - "Materials & setting up", six units - that does not
 * exist on this site. The field is here and empty, for her words.
 */
function cas_render_free_trial() {
	$photo = get_theme_mod( 'cas_trial_photo', cas_trial_photo_default() );
	$photo = $photo ? $photo : cas_trial_photo_default();
	$text  = trim( (string) get_theme_mod( 'cas_trial_text', '' ) );
	$free  = cas_first_preview_lesson_url();

	ob_start();
	?>
	<section class="section">
		<div class="band-forest is-trial reveal-one">
			<?php if ( $photo ) : ?>
				<div style="flex:0 0 180px;border-radius:var(--radius-card);overflow:hidden;aspect-ratio:1/1;position:relative;z-index:1;">
					<img src="<?php echo esc_url( $photo ); ?>" alt="Free lesson preview" style="width:100%;height:100%;object-fit:cover;" loading="lazy" decoding="async">
				</div>
			<?php endif; ?>
			<div style="flex:1;">
				<div class="eyebrow"><span class="star">&#10038;</span> Free trial</div>
				<h2 class="marker-title font-a" style="font-size:clamp(31px,3.1vw,44px);">Not sure where to start?</h2>
				<?php if ( $text ) : ?>
					<p style="margin:0;max-width:54ch;"><?php echo wp_kses_post( $text ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $free ) : ?>
				<a class="btn-big btn-marine" href="<?php echo esc_url( $free ); ?>" style="flex:none;">Watch free lesson &rarr;</a>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * These three go into the "Courses" section that inc/cas-customizer-map.php creates at
 * priority 100, which is why this runs at 110. That section carried a description and no
 * controls, and a section with no controls never appears in the panel at all; it does now.
 *
 * The two text fields deliberately have no empty-string guard. Body copy is hers, and a
 * field she may rewrite is one she may also clear - the guard belongs on the image,
 * where an empty value would paint a broken picture rather than nothing.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_setting(
			'cas_courses_intro',
			[
				'default'           => cas_courses_intro_default(),
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			]
		);

		$wp_customize->add_control(
			'cas_courses_intro',
			[
				'label'       => 'Introduction',
				'description' => 'The paragraph under the banner, above the course cards. Clearing it removes the paragraph.',
				'type'        => 'textarea',
				'section'     => 'cas_page_courses',
				'settings'    => 'cas_courses_intro',
			]
		);

		$wp_customize->add_setting(
			'cas_trial_text',
			[
				'default'           => '',
				'sanitize_callback' => 'wp_kses_post',
				'transport'         => 'refresh',
			]
		);

		$wp_customize->add_control(
			'cas_trial_text',
			[
				'label'       => 'Free trial &mdash; paragraph',
				'description' => 'One or two sentences under &ldquo;Not sure where to start?&rdquo;, saying which lesson is free to watch. Empty until you write it &mdash; the heading and the button show without it.',
				'type'        => 'textarea',
				'section'     => 'cas_page_courses',
				'settings'    => 'cas_trial_text',
			]
		);

		$wp_customize->add_setting(
			'cas_trial_photo',
			[
				'default'           => cas_trial_photo_default(),
				'sanitize_callback' => 'esc_url_raw',
				'transport'         => 'refresh',
			]
		);

		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'cas_trial_photo',
				[
					'label'       => 'Free trial &mdash; photograph',
					'description' => 'The square picture beside &ldquo;Not sure where to start?&rdquo;. Recommended about 600 x 600px. Any size works, because the photograph is cropped to a square.',
					'section'     => 'cas_page_courses',
					'settings'    => 'cas_trial_photo',
				]
			)
		);
	},
	110
);

/* ------------------------------------------------------------------------ */

function cas_render_courses( $atts ) {
	$atts = shortcode_atts( [ 'per_page' => 24 ], $atts );

	$q = new WP_Query(
		[
			'post_type'      => tutor()->course_post_type,
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['per_page'],
			'orderby'        => 'menu_order date',
			'order'          => 'DESC',
		]
	);

	$ids  = wp_list_pluck( $q->posts, 'ID' );
	$rows = [];

	foreach ( $ids as $id ) {
		$rows[ $id ] = cas_course_facets( $id );
	}

	/* Sorting one card, or filtering a catalogue whose courses differ in nothing, is a
	   control that cannot change what is on screen. Both are left out rather than shown
	   inert, which is why a one-course catalogue has no rail at all. */
	$rail = count( $rows ) > 1 ? cas_course_sort_panel() . cas_course_filter_rail( $rows ) : '';

	ob_start();

	if ( $ids ) {
		?>
		<div class="lesson-meta-bar">
			<span class="lesson-count" id="lesson-count" aria-live="polite" aria-atomic="true"><?php echo esc_html( count( $ids ) . ( 1 === count( $ids ) ? ' course' : ' courses' ) ); ?></span>
		</div>
		<div class="lesson-grid" id="lesson-grid">
			<?php
			$i = 0;

			while ( $q->have_posts() ) {
				$q->the_post();
				echo cas_course_card( get_the_ID(), $i ); // phpcs:ignore WordPress.Security.EscapeOutput
				$i++;
			}

			wp_reset_postdata();
			?>
		</div>
		<?php if ( $rail ) : ?>
			<div class="no-results" id="no-results" role="status">No courses match these filters &mdash; try clearing a few.</div>
		<?php endif; ?>
		<?php
	}

	$catalogue = ob_get_clean();

	if ( $rail ) {
		$catalogue = '<div class="catalog-layout"><div class="filter-col reveal-one">' . $rail . '</div><div>' . $catalogue . '</div></div>';
	}

	$intro = trim( (string) get_theme_mod( 'cas_courses_intro', cas_courses_intro_default() ) );

	ob_start();
	/* The wood-grain banner every other content page carries. 4.3 sets the title in
	   Font A; cas_page_hero() right-aligns Logo2, which is the placement 1.4.5 gives a
	   page that has a banner. */
	echo cas_page_hero( 'Course catalog', 'Pick up a brush.' ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<div class="pg-wrap">
		<?php if ( $intro ) : ?>
			<div class="page-lead-wrap"><p class="page-lead"><?php echo wp_kses_post( $intro ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! $ids ) : ?>
			<p class="shop-lede">No courses are published yet.</p>
		<?php else : ?>
			<section class="section" style="padding-top:6px;">
				<?php echo $catalogue; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</section>
		<?php endif; ?>
		<?php echo cas_render_membership_tiers(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo cas_render_free_trial(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_courses', 'cas_render_courses' );

/**
 * The add-to-cart button posts to Tutor's own native cart endpoint. Tutor uses a
 * different action for guests, so the correct one and a fresh nonce are handed to the
 * script rather than guessed at in JS. The same script carries the filter and sort
 * behaviour, which needs no data of its own — it reads the rail and the cards.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		/* The term archive runs archive-courses.php too, through the filter below, so
		   it needs the same script and the same page CSS. */
		if ( ! is_post_type_archive( tutor()->course_post_type ) && ! is_tax( 'course-category' ) && ! is_page( 'courses' ) ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_enqueue_script( 'cas-courses', $uri . '/js/cas-courses.js', [], filemtime( $dir . '/js/cas-courses.js' ), true );

		/* hello-elementor caps `.site-main` at the Elementor content width, and the
		   catalogue is the one page whose template puts our markup inside it. That box
		   held the wood-grain banner off the viewport edges, which no other page does,
		   and squeezed the page 76px narrower than every other page's `.pg-wrap`. Its
		   10px side padding below 1024px inset the banner there too. The inner wrappers
		   keep their own widths and insets; only the box around them is released. */
		wp_register_style( 'cas-courses', false, [ 'cas-industrial-3' ], null );
		wp_enqueue_style( 'cas-courses' );
		wp_add_inline_style( 'cas-courses', '.cas-root main.site-main.cas-archive{max-width:none;padding-left:0;padding-right:0;}' );

		wp_localize_script(
			'cas-courses',
			'casCourses',
			[
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'action'   => is_user_logged_in() ? 'tutor_add_course_to_cart' : 'tutor_guest_add_course_to_cart',
				'nonce'    => wp_create_nonce( tutor()->nonce_action ),
				'cartUrl'  => get_permalink( get_tutor_option( 'tutor_cart_page_id' ) ),
			]
		);
	},
	24
);

/**
 * Tutor swaps the course archive template through `template_include`, which runs after
 * the theme template hierarchy has already chosen archive-courses.php — so the theme
 * file alone is never reached. Claiming it back at a later priority is the documented
 * way round that, and it leaves Tutor's single-course template alone.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( ! is_post_type_archive( tutor()->course_post_type ) && ! is_tax( 'course-category' ) ) {
			return $template;
		}

		$own = get_stylesheet_directory() . '/archive-courses.php';

		return file_exists( $own ) ? $own : $template;
	},
	999
);
