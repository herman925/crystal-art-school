<?php
/**
 * The About page.
 *
 * Chapter 13 of the direction document is SILENT on which fields the author may edit, so
 * the whole page was hardcoded under the default-closed rule in 1.4.14. Herman's rule of
 * 2026-08-23 — structure and headings are his, pictures and texts are hers — settles that
 * silence, and the page is now split along it:
 *
 *   OPEN    her prose, in Customize > Pages > About: the opening paragraph, the quotation,
 *           the three essay bodies, the line under A glimpse into the journey, the
 *           Demystifying the process text, the five card paragraphs under it and under
 *           Beginner or experienced artist, and the closing line. Those last eight were
 *           left in PHP by the 2026-08-23 pass; the reason for locking stated below
 *           reaches the honours table and the two credential cards, and reaches none of
 *           them — they are AboutMe.docx word for word. And every photograph: the
 *           portrait in the same panel, the three carousel sets as an About photos post
 *           type, because a set of photographs grows and a fixed slot cannot.
 *   CLOSED  the honours table, its captions and the two credential cards beside it. Those
 *           are claims about her that an earlier build INVENTED and that had to be removed,
 *           and the published 2016 scan is deliberately pixelated. An upload field over
 *           that mount is an upload field over the mask. Opening them needs a decision
 *           that says so in writing, not an inference from this one.
 *
 * Section headings, eyebrows, card titles, button labels and the order of the page stay in
 * code either way. Every editable photograph lands in a fixed-ratio frame filled with object-fit:
 * cover, so a swap of any shape crops to its slot instead of moving the composition.
 *
 * SOURCE OF TRUTH for the copy: assets/06 - About/AboutMe.docx and the certificate
 * scans in the same folder, by way of the prototype's About.dc.html, which is where the
 * text was already localised (color -> colour). Nothing on this page was written for the
 * port. Do not write biography that did not come from her — an earlier prototype build
 * invented credentials here and they had to be removed.
 *
 * Reading order is biography -> community -> instructor -> credentials -> teaching
 * philosophy -> welcome. That puts the honours ABOVE "Demystifying the process", which
 * overrides the section order in docs/20260804_Design_Direction.md on the client's own
 * later instruction (2026-08-16, review ticket I011). The direction's freeze on
 * everything below that heading is a rule about REDESIGN, not about order, and it still
 * holds: the sections from Demystifying the process down are carried over word for word.
 *
 * THE STARS MATCH THE PROTOTYPE. A note here used to claim they did not. They do:
 * every eyebrow star on this page, and the ones cas_page_hero() and cas-home.php
 * render, are &#10022; — U+2726, the star About.dc.html draws. The old note gave
 * that one entity two different code points in consecutive sentences and was wrong
 * on its face. Nothing needs changing here or in either of those files.
 */

defined( 'ABSPATH' ) || exit;

const CAS_ABOUT_PHOTO_POST_TYPE = 'cas_about_photo';
const CAS_ABOUT_PHOTO_META_SET  = '_cas_about_photo_set';

/**
 * The three photograph sets: what each is called, what a fallback file's alt text says,
 * and which files stand in until she publishes her own.
 *
 * The SETS are structure and stay in code — chapter 13 names all three and fixes which
 * side of its essay each one sits on. What goes IN a set is hers, and the count is not
 * fixed: live drawing is six photographs waiting on a second set she still owes us. So
 * the photographs are posts, not Customizer slots.
 *
 * A set switches over ENTIRELY on its first published photograph. Mixing her uploads with
 * the shipped files would leave her looking for the delete button on one of ours.
 */
function cas_about_photo_sets() {
	return [
		'live-drawing'  => [
			'label' => 'Live drawing',
			'alt'   => 'Crystal drawing on location',
			'files' => [ 'livedrawing-1.jpg', 'livedrawing-2.jpg', 'livedrawing-3.jpg', 'livedrawing-4.jpg', 'livedrawing-5.jpg', 'livedrawing-6.jpg' ],
		],
		'exhibition'    => [
			'label' => 'Exhibitions',
			'alt'   => 'Exhibition',
			'files' => [ 'exhibition-1.jpg', 'exhibition-2.jpg', 'exhibition-3.jpg', 'exhibition-4.jpg' ],
		],
		'artful-living' => [
			'label' => 'Artful Living',
			'alt'   => 'Artful Living',
			'files' => [ 'artful-living-1.jpg', 'artful-living-2.jpg', 'artful-living-3.jpg', 'artful-living-4.jpg' ],
		],
	];
}

/**
 * A photograph has no page of its own — it is one cell of a carousel — so it is not
 * public, not queryable and not rewritten. Its picture is the featured image rather than
 * a meta field, which buys the media library's own picker, its alt text and its srcset
 * for nothing. Order is the Page attributes box, as it is for artwork and FAQ entries.
 */
function cas_register_about_photo_post_type() {
	register_post_type(
		CAS_ABOUT_PHOTO_POST_TYPE,
		[
			'labels'              => [
				'name'               => 'About photos',
				'singular_name'      => 'About photo',
				'add_new'            => 'Add photo',
				'add_new_item'       => 'Add photo',
				'edit_item'          => 'Edit photo',
				'new_item'           => 'New photo',
				'view_item'          => 'View photo',
				'search_items'       => 'Search photos',
				'not_found'          => 'No photos yet',
				'not_found_in_trash' => 'No photos in the bin',
				'all_items'          => 'All photos',
				'menu_name'          => 'About photos',
			],
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-format-gallery',
			// 21 Artwork, 22 Student work, 23 Free videos, 26 FAQ, 27 Testimonials.
			'menu_position'       => 24,
			'supports'            => [ 'title', 'thumbnail', 'page-attributes' ],
		]
	);

	/*
	 * Registered as well as saved by the meta box below, so the same clamp runs for
	 * WP-CLI and for an importer, not only for someone using the editor.
	 */
	register_post_meta(
		CAS_ABOUT_PHOTO_POST_TYPE,
		CAS_ABOUT_PHOTO_META_SET,
		[
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_about_photo_set_key',
			'auth_callback'     => function () {
				return current_user_can( 'edit_posts' );
			},
		]
	);
}

add_action( 'init', 'cas_register_about_photo_post_type' );

/**
 * A set key, or the first set. There is no "unassigned" state: a photograph filed nowhere
 * would be invisible on the page and look published in the admin.
 */
function cas_about_photo_set_key( $value ) {
	$sets  = cas_about_photo_sets();
	$value = is_string( $value ) ? $value : '';

	return isset( $sets[ $value ] ) ? $value : key( $sets );
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'cas_about_photo_set', 'Which set', 'cas_about_photo_meta_box', CAS_ABOUT_PHOTO_POST_TYPE, 'side', 'high' );
	}
);

function cas_about_photo_meta_box( $post ) {
	$sets    = cas_about_photo_sets();
	$current = get_post_meta( $post->ID, CAS_ABOUT_PHOTO_META_SET, true );

	wp_nonce_field( 'cas_about_photo_save', 'cas_about_photo_nonce' );
	?>
	<p>
		<select name="cas_about_photo_set" style="width:100%;">
			<?php foreach ( $sets as $key => $set ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>><?php echo esc_html( $set['label'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p class="description">Which carousel on the About page this photograph belongs to. Put the photograph itself in <strong>Featured image</strong> &mdash; a photo without one does not appear on the page. The title is what a screen reader says in place of the picture. Any size or shape works: photographs are cropped to fill their frame.</p>
	<?php
}

add_action(
	'save_post_' . CAS_ABOUT_PHOTO_POST_TYPE,
	function ( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['cas_about_photo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['cas_about_photo_nonce'] ), 'cas_about_photo_save' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			CAS_ABOUT_PHOTO_META_SET,
			isset( $_POST['cas_about_photo_set'] ) ? sanitize_key( wp_unslash( $_POST['cas_about_photo_set'] ) ) : ''
		);
	}
);

add_filter(
	'manage_' . CAS_ABOUT_PHOTO_POST_TYPE . '_posts_columns',
	function ( $columns ) {
		$out = [];

		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;

			if ( 'title' === $key ) {
				$out['cas_photo'] = 'Photograph';
				$out['cas_set']   = 'Set';
			}
		}

		return $out;
	}
);

/*
 * The list warns about a missing photograph rather than staying quiet about it, the way
 * the artwork list warns about a missing Etsy link. A photo with no featured image is
 * published, looks fine here, and is simply absent from the page.
 */
add_action(
	'manage_' . CAS_ABOUT_PHOTO_POST_TYPE . '_posts_custom_column',
	function ( $column, $post_id ) {
		if ( 'cas_photo' === $column ) {
			$thumb = get_the_post_thumbnail( $post_id, [ 60, 60 ] );

			echo $thumb ? $thumb : '<span style="color:#b32d2e;">Missing &mdash; this photo will not appear</span>'; // phpcs:ignore WordPress.Security.EscapeOutput

			return;
		}

		if ( 'cas_set' === $column ) {
			$sets = cas_about_photo_sets();
			$key  = cas_about_photo_set_key( get_post_meta( $post_id, CAS_ABOUT_PHOTO_META_SET, true ) );

			echo esc_html( $sets[ $key ]['label'] );
		}
	},
	10,
	2
);

/**
 * The photographs of one set, hers if she has published any and the shipped files if not.
 *
 * Each item is [ key, id, file, alt ]: `key` identifies the photograph to the pairing
 * logic in the script, `id` is an attachment or 0, and `alt` is the title she typed or,
 * for a shipped file, the set's wording plus the number in the FILENAME. Never the loop
 * index — the live-drawing deck is shuffled per render, so an index would renumber the
 * same photograph on every visit.
 */
function cas_about_photo_items( $key ) {
	$sets = cas_about_photo_sets();

	if ( ! isset( $sets[ $key ] ) ) {
		return [];
	}

	$items = [];

	$posts = get_posts(
		[
			'post_type'   => CAS_ABOUT_PHOTO_POST_TYPE,
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
			'meta_key'    => CAS_ABOUT_PHOTO_META_SET, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => $key,                     // phpcs:ignore WordPress.DB.SlowDBQuery
		]
	);

	foreach ( $posts as $post ) {
		$attachment = (int) get_post_thumbnail_id( $post );

		if ( ! $attachment ) {
			continue;
		}

		$items[] = [
			'key'  => 'att-' . $attachment,
			'id'   => $attachment,
			'file' => '',
			'alt'  => get_the_title( $post ),
		];
	}

	if ( $items ) {
		return $items;
	}

	foreach ( $sets[ $key ]['files'] as $file ) {
		preg_match( '/-(\d+)\.[a-z0-9]+$/i', $file, $m );

		$items[] = [
			'key'  => $file,
			'id'   => 0,
			'file' => 'about/' . $file,
			'alt'  => trim( $sets[ $key ]['alt'] . ' ' . ( $m ? $m[1] : '' ) ),
		];
	}

	return $items;
}

/**
 * One <img> for a file in the child theme's img/ directory.
 *
 * The prototype served its responsive ladders from a generated manifest
 * (js/static-images.js) that does not exist here, so the ladder is read off the
 * filesystem instead: whichever of the -128/-256/-384/-768 derivatives were actually
 * uploaded are offered, and the master closes the set at its true width. The ladders in
 * img/about/ are ragged — livedrawing-1 has no -768, artful-living-1 has only -384 — so
 * a fixed list of widths would have produced srcset entries that 404. A file with no
 * derivatives at all simply gets no srcset rather than a broken one.
 *
 * width and height come from the file too, so the box is reserved before the image
 * arrives without a table of measured numbers having to be kept in step by hand.
 */
function cas_about_img( $rel, $alt, $sizes = '', $extra = '' ) {
	$dir = get_stylesheet_directory() . '/img/';
	$uri = get_stylesheet_directory_uri() . '/img/';

	if ( ! is_readable( $dir . $rel ) ) {
		return '';
	}

	$ext  = pathinfo( $rel, PATHINFO_EXTENSION );
	$stem = substr( $rel, 0, - ( strlen( $ext ) + 1 ) );
	$set  = [];

	foreach ( [ 96, 128, 160, 256, 384, 640, 768 ] as $w ) {
		$candidate = $stem . '-' . $w . '.' . $ext;

		if ( is_readable( $dir . $candidate ) ) {
			$set[] = esc_url( $uri . $candidate ) . ' ' . $w . 'w';
		}
	}

	$size = getimagesize( $dir . $rel );
	$out  = '<img src="' . esc_url( $uri . $rel ) . '" alt="' . esc_attr( $alt ) . '"';

	if ( $size ) {
		$out .= ' width="' . (int) $size[0] . '" height="' . (int) $size[1] . '"';

		if ( $set ) {
			$set[] = esc_url( $uri . $rel ) . ' ' . (int) $size[0] . 'w';
			$out  .= ' srcset="' . esc_attr( implode( ', ', $set ) ) . '"';

			if ( $sizes ) {
				$out .= ' sizes="' . esc_attr( $sizes ) . '"';
			}
		}
	}

	return $out . ' ' . $extra . '>';
}

/**
 * One photo carousel.
 *
 * The cells are rendered here rather than written by the script the way the prototype
 * did it. On a WordPress page the photographs are content: rendering them server-side
 * means they are in the markup for a crawler, they carry native lazy loading, and the
 * page does not depend on a generated JS manifest to know their sizes.
 *
 * The prototype's per-carousel "photographs to a page" setting is gone with it. Every
 * set became one-up on 2026-08-20 when Live drawing was split into the stacked pair, and
 * its narrow-screen branch also resolved to one — so the value was 1 everywhere and the
 * relayout-on-resize it existed to drive could never fire.
 *
 * It takes the items cas_about_photo_items() built, so one carousel renders her uploads
 * and another the shipped files without either knowing which it has. `data-file` carries
 * the item's key rather than a filename: the script compares it between the two stacked
 * live-drawing rails to keep them off the same photograph, and only needs it to be
 * unique.
 */
function cas_about_carousel( $id, $label, $items, $dwell, $jitter = 0, $pair = '', $cls = '' ) {
	$cells = '';
	$sizes = '(max-width: 620px) 88vw, (max-width: 1000px) 44vw, 560px';

	foreach ( $items as $item ) {
		if ( $item['id'] ) {
			$img = wp_get_attachment_image(
				$item['id'],
				'large',
				false,
				[
					'alt'      => $item['alt'],
					'sizes'    => $sizes,
					'loading'  => 'lazy',
					'decoding' => 'async',
				]
			);
		} else {
			$img = cas_about_img( $item['file'], $item['alt'], $sizes, 'loading="lazy" decoding="async"' );
		}

		if ( $img ) {
			$cells .= '<figure class="phc-cell" data-file="' . esc_attr( $item['key'] ) . '">' . $img . '</figure>';
		}
	}

	if ( ! $cells ) {
		return '';
	}

	return '<div class="ph-carousel' . ( $cls ? ' ' . esc_attr( $cls ) : '' ) . '" id="' . esc_attr( $id ) . '"' .
		' role="group" aria-label="' . esc_attr( $label ) . '"' .
		' data-dwell="' . (int) $dwell . '" data-jitter="' . (int) $jitter . '"' .
		( $pair ? ' data-pair="' . esc_attr( $pair ) . '"' : '' ) .
		' style="--phc-per:1;">' .
		'<div class="phc-viewport"><div class="phc-rail">' . $cells . '</div></div>' .
		'<div class="phc-nav"><span class="phc-dots"></span></div>' .
	'</div>';
}

/**
 * The live-drawing deck, shuffled once per render and shared by the two stacked
 * carousels.
 *
 * SCAFFOLDING, AND THE PAGE SAYS SO: there is still only ONE set of live-drawing
 * photographs. The lower carousel is the SAME set — shuffled once, then the deck rotated
 * by half so the two never open on the same picture, with a skip-ahead guard in the
 * script so they do not land on it later either. One shuffle, not two: two independent
 * shuffles can agree, and the point of the pair is that it does not look like the same
 * strip printed twice. When Crystal supplies a second set, give the lower carousel its
 * own set and drop its data-pair attribute.
 *
 * The deck is now whatever the live-drawing set holds, so it grows with what she
 * publishes rather than with the six filenames that used to be written out here.
 */
function cas_about_livedrawing_decks() {
	$deck = cas_about_photo_items( 'live-drawing' );

	shuffle( $deck );

	$half = (int) floor( count( $deck ) / 2 );

	return [ $deck, array_merge( array_slice( $deck, $half ), array_slice( $deck, 0, $half ) ) ];
}

/**
 * Display handle for a social URL — "@crystalauart", "facebook.com/crystal430".
 *
 * Derived rather than written out, because the URLs themselves are client-editable now:
 * a hardcoded handle beside an edited URL would go on saying the old name.
 */
function cas_about_handle( $url, $prefix ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );

	return $prefix . basename( untrailingslashit( $path ? $path : '' ) );
}

/**
 * Her prose, and her portrait: the fields on this page there is exactly one of, so they
 * are Customizer settings rather than a post type.
 *
 * Every default is the wording and the photograph that are live today, registered as the
 * setting's default rather than only used at render time. The panel then opens showing
 * what is being replaced instead of an empty box, which reads as broken. Same convention
 * as cas-hero.php.
 *
 * The three essay bodies keep their markup, because their cross-headings are part of her
 * writing and not the page's furniture: the field holds the <p> and <h3 class="bio-sub">
 * elements exactly as the page ships them, and she edits the words between the tags.
 * Typing plain text still works - a value with no tag in it goes through wpautop, so
 * blank lines become paragraphs.
 */
function cas_about_fields() {
	return [
		'cas_about_intro'           => [
			'label'   => 'Opening paragraph',
			'type'    => 'inline',
			'desc'    => 'The line under the greeting. One short paragraph &mdash; the greeting itself is part of the page design and is not edited here.',
			'default' => 'I&rsquo;m Crystal Lau, a contemporary artist specialising in watercolour and oil painting, and the founder of SKY GALLERY.',
		],
		'cas_about_portrait'        => [
			'label'   => 'Portrait photograph',
			'type'    => 'image',
			'desc'    => 'Recommended 1168 x 1400px (upright, about 5:6). Any size works, because the photograph is cropped to fill the arched frame. Keep yourself near the centre.',
			'default' => '/img/ProfilePic.jpg',
		],
		'cas_about_quote'           => [
			'label'   => 'Quotation',
			'type'    => 'inline',
			'desc'    => 'The line set between quotation marks under the opening paragraph. One or two sentences; the quote marks are drawn by the page.',
			'default' => 'Art is not merely colour on paper; it is an observation of the world and a timeless extension of one&rsquo;s emotions.',
		],
		'cas_about_journey_body'    => [
			'label'   => 'My artistic journey &mdash; text',
			'type'    => 'block',
			'desc'    => 'Each paragraph is wrapped in &lt;p&gt; and &lt;/p&gt;. A cross-heading is &lt;h3 class="bio-sub"&gt; and &lt;/h3&gt;. The section heading above it is fixed.',
			'default' => <<<'JOURNEY_HTML'
<h3 class="bio-sub">The art of plein air: capturing life as it happens</h3>
<p>For over a decade, my greatest joy has been stepping outdoors with my easel in hand &mdash; embracing the practice of <em>plein air</em> painting. There is something magical about chasing the light, capturing the subtle breeze, and transforming fleeting natural moments into lasting visual stories.</p>
<p>Plein air painting is more than just a technique; it is a conversation between the artist and the world. Unlike working in the controlled environment of a studio, painting outdoors challenges me to react in real time &mdash; to the shifting shadows across an old urban street, or the golden sun rays striking a centuries-old wooden shrine.</p>
<h3 class="bio-sub">Finding beauty in the everyday cityscape</h3>
<p>Look closely at the world around you. There is quiet beauty hidden in the bustle of daily life. Standing on a vibrant city street corner, I am drawn to the strong geometric shapes of weathered buildings, the distinctive warmth of classic store signs, and the interplay of light reflecting off passing vehicles. Painting these scenes isn&rsquo;t about capturing every single detail; it&rsquo;s about translating the rhythmic pulse of the city and its sun-drenched atmosphere onto paper with decisive, confident washes.</p>
<h3 class="bio-sub">Preserving culture and quiet moments</h3>
<p>Every location carries its own unique voice and light. When travelling, my sketchbook becomes my most personal travel diary. Sitting before intricate traditional architecture &mdash; observing the rich warm tones of carved wooden beams and luminous paper lanterns &mdash; plein air painting forces me to slow down. It turns a quick visit into a deep, meditative experience where every stroke pays homage to craftsmanship, history, and sacred tranquility.</p>
<h3 class="bio-sub">Why I love plein air painting</h3>
<p>Plein air painting teaches us to let go of perfectionism and embrace spontaneity. It builds quick observation skills, strong value control, and a deep intuition for colour harmony that no studio setting can replicate.</p>
<p>Through my courses, I want to invite you to step out of your comfort zone, pick up your brush, and experience the world through an artist&rsquo;s eyes. You don&rsquo;t need to travel far &mdash; beauty is waiting right outside your doorstep, ready to be painted.</p>
JOURNEY_HTML,
		],
		'cas_about_community_body'  => [
			'label'   => 'From gallery walls to global community &mdash; text',
			'type'    => 'block',
			'desc'    => 'Each paragraph is wrapped in &lt;p&gt; and &lt;/p&gt;. The section heading above it is fixed.',
			'default' => <<<'COMMUNITY_HTML'
<p>While painting is a personal form of expression, I believe art becomes truly powerful when shared. Over the years, my work has been featured in mainstream media, published in art collections, and acquired by institutional permanent collections, such as the Sun Museum. In 2018, I launched my YouTube channel, <strong>Crystal Lau Art</strong>, to share the joys of plein air sketching and art history with a broader audience. It showed me how art can bridge distances and connect people anywhere in the world.</p>
COMMUNITY_HTML,
		],
		'cas_about_instructor_body' => [
			'label'   => 'Meet your instructor &mdash; text',
			'type'    => 'block',
			'desc'    => 'Each paragraph is wrapped in &lt;p&gt; and &lt;/p&gt;. The section heading above it is fixed.',
			'default' => <<<'INSTRUCTOR_HTML'
<p>Since childhood, art class was always the highlight of my week and my most consistent source of pride. I spent countless hours at home with a paintbrush in hand, sketching whatever caught my eye. Soon my school textbooks were filled with drawings &mdash; and it wasn&rsquo;t long before my classmates&rsquo; books became my canvases too. Over time I realised that painting was more than a passing interest; it was my true passion. Representing my school in art competitions and earning awards brought me immense joy and built my foundation as an artist.</p>
<p>My journey as a teacher began at the age of 16. That was the moment I discovered something incredible: guiding others to paint and watching them fall in love with art is even more rewarding than completing a painting of my own.</p>
<p>Later I opened my own art studio, a vibrant space that has now thrived for over 15 years. Throughout this journey the greatest reward has been connecting with so many fellow art lovers. While I share my technical knowledge with my students, they teach me just as much in return &mdash; showing me new perspectives and deeper ways to feel and experience art. Teaching and learning truly feed into each other.</p>
<p>I am deeply grateful for this online platform, which allows me to reach and connect with art enthusiasts from around the world. Here we learn together, grow together, and celebrate the joy of creation.</p>
INSTRUCTOR_HTML,
		],
		'cas_about_highlights_lead' => [
			'label'   => 'A glimpse into the journey &mdash; opening line',
			'type'    => 'inline',
			'desc'    => 'The sentence under that heading, above the two cards. The honours table below it is fixed.',
			'default' => 'To give you complete confidence in learning with me, here is a glimpse into my journey as an artist and educator.',
		],
		'cas_about_philosophy_body' => [
			'label'   => 'Demystifying the process &mdash; text',
			'type'    => 'block',
			'desc'    => 'Each paragraph is wrapped in &lt;p&gt; and &lt;/p&gt;. Leave the margin written on the last one at 0 &mdash; it is what closes the gap to the three cards below.',
			'default' => <<<'PHILOSOPHY_HTML'
<p style="margin:0 0 14px;">Many people want to paint, but stop themselves before they even begin. They worry about &ldquo;lacking natural talent,&rdquo; struggle with controlling unruly watercolours, or feel overwhelmed by the layers of oil painting.</p>
<p style="margin:0 0 14px;">Here is the truth: <strong style="color:var(--ink);">painting is not an exclusive privilege for a gifted few &mdash; it is a learnable, structured skill.</strong></p>
<p style="margin:0;">In my courses, you won&rsquo;t find overly academic jargon or intimidating rules. Instead, I focus on warm, practical, step-by-step guidance:</p>
PHILOSOPHY_HTML,
		],
		'cas_about_philosophy_card_1' => [
			'label'   => 'Card 1 &mdash; Developing an artist&rsquo;s eye',
			'type'    => 'inline',
			'desc'    => 'The words under that card title. The title itself is part of the page design.',
			'default' => 'Learn to see the world differently by understanding light, proportion, structure, and atmospheric depth.',
		],
		'cas_about_philosophy_card_2' => [
			'label'   => 'Card 2 &mdash; Mastering your medium',
			'type'    => 'inline',
			'desc'    => 'The words under that card title. The title itself is part of the page design.',
			'default' => 'Gain full control over watercolour transparency and oil layering rules so your materials work <em>with</em> you, not against you.',
		],
		'cas_about_philosophy_card_3' => [
			'label'   => 'Card 3 &mdash; Finding your own visual voice',
			'type'    => 'inline',
			'desc'    => 'The words under that card title. The title itself is part of the page design.',
			'default' => 'My goal isn&rsquo;t to teach you how to copy my style, but to help you build the confidence to paint what speaks to <em>your</em> heart.',
		],
		'cas_about_welcome_card_1' => [
			'label'   => 'Card 1 &mdash; Stepping into art for the very first time',
			'type'    => 'inline',
			'desc'    => 'The words under that card title. The title itself is part of the page design.',
			'default' => 'Welcome! Consider this studio your safe space to explore, experiment, and have fun without any pressure.',
		],
		'cas_about_welcome_card_2' => [
			'label'   => 'Card 2 &mdash; An experienced painter',
			'type'    => 'inline',
			'desc'    => 'The words under that card title. The title itself is part of the page design.',
			'default' => 'Looking to refine your brushwork, master light and shadow, or deepen your plein air skills? You&rsquo;ll find structured, practical insights to help you break through to the next level.',
		],
		'cas_about_welcome_close' => [
			'label'   => 'Closing line',
			'type'    => 'inline',
			'desc'    => 'The handwritten line that ends the page, above the contact form. One sentence &mdash; it is set large and centred.',
			'default' => 'Grab a cup of tea, set up your brushes, and let&rsquo;s enjoy the process of bringing colour to life!',
		],
	];
}

/**
 * The saved value, or the shipped one.
 *
 * get_theme_mod( key, default ) is NOT enough: the Customizer's "Remove image" button
 * stores an empty string, which get_theme_mod returns in preference to the default and
 * which renders as src="" - the browser then requests the page itself as an image.
 * cas-brand.php guards this and cas-hero.php does not; this is the guarded one.
 */
function cas_about_value( $key ) {
	$fields = cas_about_fields();

	if ( ! isset( $fields[ $key ] ) ) {
		return '';
	}

	$default = $fields[ $key ]['default'];

	if ( 'image' === $fields[ $key ]['type'] ) {
		$default = get_stylesheet_directory_uri() . $default;
	}

	$set = get_theme_mod( $key );

	return ( '' === $set || false === $set ) ? $default : $set;
}

/**
 * A prose field, ready to echo. Text typed without any markup is paragraphed for her.
 */
function cas_about_prose( $key ) {
	$value = cas_about_value( $key );

	return false === strpos( $value, '<' ) ? wpautop( $value ) : $value;
}

/**
 * The portrait, above the fold at every width and the page's LCP element.
 *
 * While the shipped file is still in place it keeps the responsive ladder in img/ that
 * cas_about_img() reads off disk. Once she uploads her own, the attachment is recovered
 * from the URL so WordPress can build the srcset - a plain <img> at a plain URL would
 * ship the full-size master to a phone.
 */
function cas_about_portrait_img() {
	$alt   = 'Crystal Lau in her studio';
	$sizes = '(max-width: 600px) 358px, (max-width: 1000px) 380px, 584px';
	$url   = cas_about_value( 'cas_about_portrait' );

	if ( $url === get_stylesheet_directory_uri() . '/img/ProfilePic.jpg' ) {
		return cas_about_img( 'ProfilePic.jpg', $alt, $sizes, 'fetchpriority="high" decoding="async"' );
	}

	$attachment = attachment_url_to_postid( $url );

	if ( $attachment ) {
		return wp_get_attachment_image(
			$attachment,
			'large',
			false,
			[
				'alt'           => $alt,
				'sizes'         => $sizes,
				'fetchpriority' => 'high',
				'decoding'      => 'async',
			]
		);
	}

	return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" fetchpriority="high" decoding="async">';
}

/*
 * Priority 110, which is AFTER cas-customizer-map.php builds its pointer sections at 100.
 * That file gives every page a section so the Customizer reads as a map of the site, and
 * About's said the page was not editable. Re-registering the same id here replaces it
 * with the real thing, and keeps the per-page detail in the page's own file.
 */
add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section(
			'cas_page_about',
			[
				'title'       => 'About',
				'panel'       => 'cas_pages',
				'priority'    => 40,
				'description' => 'Your own words and your portrait. The section headings, the buttons and the order of the page are part of its design and are not edited here. The three photo carousels are edited under <strong>Dashboard &rarr; About photos</strong>. The honours and certificates are fixed &mdash; ask Herman to change anything there. <strong>Leaving a box empty puts the wording it shipped with back</strong>, so an empty box is never how you delete a paragraph.',
			]
		);

		foreach ( cas_about_fields() as $key => $field ) {
			$wp_customize->add_setting(
				$key,
				[
					'default'           => cas_about_value( $key ),
					'sanitize_callback' => 'image' === $field['type'] ? 'esc_url_raw' : 'wp_kses_post',
					'transport'         => 'refresh',
				]
			);

			/* cas_about_value() treats an empty saved value as "not set" and puts the
			   shipped wording or photograph back. That guard exists because an empty
			   image URL renders src="" and the browser then requests the page itself as
			   an image. Applied to the text fields it means she can rewrite a paragraph
			   but cannot delete one - she clears the box, presses Publish, and the old
			   words come back with nothing on screen to explain it.

			   The behaviour is kept and STATED. Removing the guard would leave the
			   heading, the engraved quote frame and the card that wrap each of these
			   fields drawn around nothing, so an emptied box would read as a broken
			   page rather than a deleted paragraph; deleting the paragraph means
			   deleting its furniture too, which is a layout change and hers to ask for.
			   Same shape as the Courses tier photos (register T20). */
			$restores = 'image' === $field['type']
				? ' <strong>Removing the photograph puts back the one the page shipped with</strong> &mdash; it does not leave the space empty.'
				: ' <strong>Emptying this box puts the wording above back</strong> &mdash; it does not delete the paragraph. Ask Herman to remove a paragraph and the space it sits in.';

			$args = [
				'label'       => $field['label'],
				'description' => $field['desc'] . $restores,
				'section'     => 'cas_page_about',
				'settings'    => $key,
			];

			if ( 'image' === $field['type'] ) {
				$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, $args ) );
			} else {
				$args['type'] = 'textarea';
				$wp_customize->add_control( $key, $args );
			}
		}
	},
	110
);

function cas_render_about() {
	list( $live_a, $live_b ) = cas_about_livedrawing_decks();

	/* WordPress rewrites `sizes="120px"` into `sizes="auto, 120px"` on every lazy-loaded
	   image it finds in filtered content: wp_filter_content_tags() runs on the_content at
	   priority 12, AFTER do_shortcode at 11, so it reaches everything this shortcode
	   returns. Chrome then refuses to resolve a percentage height on an img carrying
	   `sizes=auto`. Measured on this page: `.cert-frame .cert-mat img { height: 100% }`
	   computed as 1500px inside a 172px mount and sat flush with the mount's top, so
	   object-fit: contain centred each scan roughly 665px below the mount's
	   `overflow: hidden` window — every certificate rendered as an empty cream frame while
	   the DOM, the files and the opacity were all correct. Removing the `auto,` restored
	   172px on the same element in the same tab.
	   Nothing is lost by switching it off here: cas_about_img() already states an exact
	   `sizes` per slot, the same values About.dc.html emits, so `auto` had no measurement
	   to add. This is core's own documented switch, registered only on a request that
	   renders this page. */
	add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );

	$social  = cas_social();
	/* Tutor LMS owns the `courses` slug, so no WordPress page can hold it and
	   cas_page_url() falls back to the home URL - which made this button, the site's
	   one route from her biography into the catalogue, a link back to the front page.
	   The archive is the address that resolves; the helper stays as the fallback for
	   the case where Tutor is switched off. Same two lines as cas-showcase.php,
	   cas-free-videos.php and cas-home.php. */
	$courses = get_post_type_archive_link( 'courses' );

	if ( ! $courses ) {
		$courses = cas_page_url( 'courses' );
	}

	$gallery = cas_page_url( 'gallery' );

	ob_start();
	echo cas_page_hero( 'Meet the artist', 'About' ); // phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<?php /* The site's skip link is href="#content" and the page hero sits outside the
	     landmark, exactly as in the prototype's About.dc.html. tabindex="-1" is what makes
	     the link move FOCUS and not just the viewport. Same element as cas-gallery.php,
	     cas-showcase.php, cas-free-videos.php and cas-blog-templates.php. */ ?>
	<main class="container" id="content" tabindex="-1">

	<header class="about-hero">
		<div class="portrait-wrap reveal-one">
			<?php /* Above the fold at every width, so it is fetched eagerly rather than lazily. */ ?>
			<div class="arch-duo in-view"><?php echo cas_about_portrait_img(); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="portrait-note">that&rsquo;s me &#10022;</div>
		</div>
		<div>
			<?php /* Two lines, split at the exclamation mark, the greeting set larger than the line under it. They are explicit line elements rather than a <br> because the prototype's splitter rebuilt the heading from textContent and ran "Crystal!Welcome" together. (client feedback 2026-08-08) */ ?>
			<h2 class="marker-title font-a reveal-one about-greet">
				<span class="ag-line ag-1">Hi, I&rsquo;m Crystal!</span>
				<span class="ag-line ag-2">Welcome to my studio.</span>
			</h2>
			<div class="reveal-one">
				<p class="lead" style="font-size:17px;"><?php echo cas_about_value( 'cas_about_intro' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<?php /* 13: 「用此符號設計」. The engraved wooden panel from About ref.jpg, with the blog feature card's quote marks straddling the top and bottom rules where the reference puts its chapter title. (client feedback 2026-08-08) */ ?>
				<div class="engraved is-quote" style="margin:22px 0 24px;">
					<div class="eng-head">
						<span class="eng-title" aria-hidden="true">&#8220;</span>
						<span class="eng-rule"></span>
					</div>
					<div class="eng-body">
						<blockquote><?php echo cas_about_value( 'cas_about_quote' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></blockquote>
					</div>
					<div class="eng-foot">
						<span class="eng-rule"></span>
						<span class="eng-title" aria-hidden="true">&#8221;</span>
					</div>
				</div>
				<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:6px;">
					<a class="btn primary" href="<?php echo esc_url( $courses ); ?>">Browse courses <span class="arr" aria-hidden="true">&rarr;</span></a>
					<a class="btn ghost" href="<?php echo esc_url( $gallery ); ?>">Walk the gallery</a>
				</div>
			</div>
		</div>
	</header>

	<?php
	/* Three sections above the credentials, each with its own photo carousel, alternating
	   left and right so they do not read as one template repeated three times. The
	   bio-facts list that used to close the first block is deleted outright — the board
	   marks it "delete" and all four of its items with it. */
	?>
	<section class="section" id="biography">
		<div class="bio-layout">
			<?php
			/* TWO carousels stacked, not one two-up strip. The essay beside this column is
			   tall and a single strip left the left column half empty; stacking fills it and
			   each photograph is painted about twice as wide. (client feedback 2026-08-20)

			   The dwell values are deliberately co-prime-ish and each is jittered in the
			   script, so the two rails never settle into step — "make sure their
			   'transition' is not synced". */
			?>
			<div class="phc-stack">
				<?php
				echo cas_about_carousel( 'car-livedrawing', 'Live drawing, upper set', $live_a, 5300, 700, 'car-livedrawing-b' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo cas_about_carousel( 'car-livedrawing-b', 'Live drawing, lower set', $live_b, 6900, 700, 'car-livedrawing' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
			<?php
			/* The three biography blocks take the same engraved panel as the quote above, so
			   the whole page reads as one device. The heading straddles the top rule exactly
			   as About ref.jpg draws it; the paragraphs stay left-aligned because the
			   reference is the frame, not its centred text, and centring 900px of body copy
			   would be unreadable. (client feedback 2026-08-08)

			   The drawn rules either side of each heading and the three-sided frame around
			   each body were dropped on 2026-08-20 — they were inherited from the ruled
			   notepaper these panels used to be, and the blog feature card in the same
			   leather has neither. Herman's own design note, not a client instruction. */
			?>
			<div class="bio-copy engraved">
				<div class="eng-head">
					<span class="eng-rule"></span>
					<h2 class="eng-title marker-title" style="font-size:clamp(31px,3.4vw,44px);">My artistic journey</h2>
					<span class="eng-rule"></span>
				</div>
				<div class="eng-body">
					<?php echo cas_about_prose( 'cas_about_journey_body' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
	</section>

	<section class="section" id="community">
		<div class="bio-layout bio-layout--flip">
			<div class="bio-copy engraved">
				<div class="eng-head">
					<span class="eng-rule"></span>
					<h2 class="eng-title marker-title" style="font-size:clamp(31px,3.4vw,44px);">From gallery walls to global community</h2>
					<span class="eng-rule"></span>
				</div>
				<div class="eng-body">
					<?php echo cas_about_prose( 'cas_about_community_body' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
			<?php echo cas_about_carousel( 'car-exhibition', 'Exhibitions', cas_about_photo_items( 'exhibition' ), 6700 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</section>

	<section class="section" id="instructor">
		<div class="bio-layout">
			<?php echo cas_about_carousel( 'car-artful', 'Artful Living', cas_about_photo_items( 'artful-living' ), 8100, 0, '', 'is-portrait' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="bio-copy engraved">
				<div class="eng-head">
					<span class="eng-rule"></span>
					<?php /* "A lifetime" capitalised after the colon. (client feedback 2026-08-08) */ ?>
					<h2 class="eng-title marker-title" style="font-size:clamp(31px,3.4vw,44px);">Meet your instructor: A lifetime of passion &amp; art</h2>
					<span class="eng-rule"></span>
				</div>
				<div class="eng-body">
					<?php echo cas_about_prose( 'cas_about_instructor_body' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</div>
	</section>

	<?php
	/* ORDER (client feedback 2026-08-16, ticket I011: 「放在Demystifying the process上面」).
	   She pinned the International honours card and asked for it ABOVE the Demystifying
	   the process block, so the whole Highlights section moved above Philosophy rather
	   than the one card: the card is the third of three in that section and lifting it
	   alone would leave it with no heading and split the honours away from the two cards
	   that introduce them. If she meant the single card only, this is the one block to
	   move back.

	   THIS OVERRIDES THE DIRECTION, deliberately. docs/20260804_Design_Direction.md draws
	   a line at Demystifying the process and marks everything below it 「以下全部不修改」,
	   naming `A glimpse into the journey` — this section — as 「在界線以下，不動」. That
	   freeze is from 2026-08-04; ticket I011 is from 2026-08-16 and is the same client
	   asking for this section to move. The later instruction wins. Do NOT "restore" the
	   board order on the strength of the direction document without checking with her. */
	?>
	<section class="section" id="highlights">
		<div class="section-head reveal-one" style="text-align:center;">
			<div class="eyebrow" style="justify-content:center;"><span class="star">&#10022;</span> Highlights &amp; professional background</div>
			<h2 class="section-title">A glimpse into the journey</h2>
			<p class="lead" style="font-size:16px;max-width:620px;margin:12px auto 0;"><?php echo cas_about_value( 'cas_about_highlights_lead' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		</div>
		<div class="tc-grid">
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Art education &amp; outreach</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;">Founder of SKY GALLERY with over 10 years of teaching experience; recipient of the Outstanding Art Education Award; invited artist exchange at the Repin Academy of Fine Arts in Russia.</p>
			</div>
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Collections &amp; media features</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;">Permanent collection at the Sun Museum; work featured on local magazine cover stories and dedicated art publications.</p>
			</div>
		</div>
		<div class="card" style="margin-top:18px;">
			<h3 class="marker-title" style="font-size:19px;margin:0 0 4px;">International honours &amp; exhibitions</h3>
			<?php
			/* ONE table, not two. Her honours list WAS the table-style list all along; a
			   second list of certificates underneath it repeated the same years and the same
			   shape. The honours are the rows and each row's certificate is the picture
			   beside it. (client feedback 2026-08-09)

			   The rows are generated from the mounts below, so the two cannot disagree about
			   how many there are or what order they are in. Ordering is BY HAND: the page
			   renders the sequence written here and does not sort by year or by awarding
			   body. Treat the arrangement as content, not as a display option.

			   The table is the CONTROL: five rows visible, the rest reached by scrolling it,
			   and the pictures move with it.

			   NOTE on the 2025 row: her honours list says 25th / 2025 while the scan itself
			   says 26th / 2026. The row keeps HER wording and the caption keeps the SCAN's;
			   neither is edited to agree with the other. Open with her. The same convention
			   governs the 2016 row, where she says "1st Hong Kong International Biennial
			   Exhibition" and the scan reads 「第一屆香港國際水彩雙年展 / 1st International
			   Watercolor Biennale Hong Kong 2016」.

			   NO HONOUR IS INVENTED HERE and none may be: FOUR of these rows are honours and
			   they are the four Crystal supplied. The 2016 poster is a fifth dated row that
			   says on its face that it is a poster, and the three Demo rows say on their face
			   that they are layout scaffolding. Read the row count and the honour count as
			   different numbers, and keep the note under the table in step with both.

			   HIGHLIGHT (client feedback 2026-08-16, ticket I010): one certificate is shown
			   on its own, larger, above the rest. Which one is the single `data-highlight`
			   attribute below — move it and the page follows; delete it and this block
			   disappears and the rail carries all of them again. WHICH ONE IS A DEFAULT, NOT
			   HER CHOICE: it is on the 2026 Accademia certificate because that is the most
			   recent and most widely recognised of the four and something had to be picked to
			   build against. Her note may instead have meant a control that lets her pick the
			   highlight from the CMS, which is a different build. Both still to put to her.

			   MASKED: direction 13.3 asks for 模糊／像素化 over the recipient name and anything
			   else that could be misused. The published 2016 scan pixelates her name and both
			   handwritten signatures at a 20px block — pixelation rather than blur, because
			   blurred print is partially recoverable. The show, venue, issuing body and date
			   stay: they are public facts about a public exhibition, and without them the
			   scan evidences nothing. The alt text carries no legal name either, it being the
			   more exposed half for being indexable text rather than pixels. The unmasked
			   original is in her assets folder, which is never committed. */
			?>
			<div class="cert-highlight" id="cert-highlight" hidden></div>
			<div class="cert-split">
				<div class="cert-list" id="cert-list" role="group" aria-label="International honours and certificates"></div>
				<div class="bc-row is-rail" id="cert-rail">
					<figure class="bc-card bc-card--cert" data-highlight data-year="2026" data-honour="Top 18 Finalist, International Watercolor Academy Award (Accademia Internazionale dell&#39;Acquarello, Italy)">
						<div class="cert-frame"><div class="cert-mat">
							<?php echo cas_about_img( 'about/cert-accademia-2026.jpg', 'Official Finalist certificate, International Watercolor Academy Award 2026, Accademia Internazionale dell\'Acquarello, Italy', '120px', 'loading="lazy" decoding="async"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div></div>
						<figcaption>Official Finalist &middot; International Watercolor Academy Award 2026 &middot; Italy</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert" data-year="2025" data-honour="Selected, 25th International Watercolor Spring Exhibition (Japan International Watercolour Institute)">
						<div class="cert-frame"><div class="cert-mat">
							<?php echo cas_about_img( 'about/cert-jiwi-2026.jpg', 'Winning Work certificate, 26th JIWI Online Spring Exhibition, Japan International Watercolour Institute, for the painting City of Japan', '120px', 'loading="lazy" decoding="async"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div></div>
						<figcaption>Winning Work &middot; 26th JIWI Online Spring Exhibition &middot; 《City of Japan》</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert is-ph" data-year="2017" data-honour="Selected, Fabriano in Acquarello (Italy)">
						<div class="cert-frame"><div class="cert-mat">
							<span class="cert-slot is-portrait">Scan not supplied</span>
						</div></div>
						<figcaption>Scan not supplied</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert" data-year="2016" data-honour="Selected, 1st Hong Kong International Biennial Exhibition">
						<div class="cert-frame"><div class="cert-mat">
							<?php echo cas_about_img( 'about/cert-iws-hk-2016.jpg', 'Certificate for participating in Love . United, the 1st International Watercolor Biennale Hong Kong 2016, issued by the International Watercolor Society Hong Kong on 14 October 2016. The recipient name and the two signatures are masked on the published scan.', '120px', 'loading="lazy" decoding="async"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div></div>
						<figcaption>Certificate &middot; Love . United &middot; 1st International Watercolor Biennale Hong Kong 2016</figcaption>
					</figure>
					<?php
					/* NOT A CERTIFICATE, and it must never be captioned as one. This is the
					   printed EXHIBITION POSTER for the same 2016 show as the row above: it
					   carries the show's dates, the venue and the organiser credits, and it bears
					   no recipient name and no award wording. It was supplied as one of two
					   "certificates"; the decision to show it anyway was taken on our side on
					   2026-08-16 and has NOT been put to the client, so the word "poster" comes
					   first in both the row and the caption, and she is being asked.

					   It carries no marker class on purpose: `is-ph` means "no scan" and would
					   disable a row that does have one, and `is-demo` means "layout scaffolding"
					   and would have the note below call a real printed artefact scaffolding. The
					   distinction is carried by the TEXT, which is where a reader gets it. */
					?>
					<figure class="bc-card bc-card--cert" data-year="2016" data-honour="Exhibition poster for Love . United, 1st International Watercolor Biennale Hong Kong &mdash; the printed poster for the show above, not an award">
						<div class="cert-frame"><div class="cert-mat">
							<?php echo cas_about_img( 'about/poster-iws-hk-2016.jpg', 'Printed exhibition poster for Love . United, the 1st International Watercolor Biennale Hong Kong 2016, held 13 to 16 October 2016 at Leung Fong Oi Wan Art Gallery; organised by the International Watercolor Society of Hong Kong, co-organised by Lingnan University Chaplain\'s Office', '120px', 'loading="lazy" decoding="async"' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div></div>
						<figcaption>Exhibition poster &middot; Love . United &middot; 13&ndash;16 October 2016 &middot; Leung Fong Oi Wan Art Gallery</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert is-ph is-demo" data-year="Demo" data-honour="Layout scaffolding, not an award &mdash; a landscape scan fitting the mount">
						<div class="cert-frame"><div class="cert-mat">
							<span class="cert-slot is-landscape">Landscape certificate</span>
						</div></div>
						<figcaption>Layout scaffolding &mdash; not an award</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert is-ph is-demo" data-year="Demo" data-honour="Layout scaffolding, not an award &mdash; a portrait scan fitting the mount">
						<div class="cert-frame"><div class="cert-mat">
							<span class="cert-slot is-portrait">Portrait certificate</span>
						</div></div>
						<figcaption>Layout scaffolding &mdash; not an award</figcaption>
					</figure>
					<figure class="bc-card bc-card--cert is-ph is-demo" data-year="Demo" data-honour="Layout scaffolding, not an award &mdash; a square scan fitting the mount">
						<div class="cert-frame"><div class="cert-mat">
							<span class="cert-slot is-square">Square certificate</span>
						</div></div>
						<figcaption>Layout scaffolding &mdash; not an award</figcaption>
					</figure>
				</div><!-- /.bc-row.is-rail -->
			</div><!-- /.cert-split -->
			<?php /* inline, not a class: a new class with no rule behind it is dead CSS. */ ?>
			<p style="margin:14px 0 0;font-size:13px;line-height:1.6;color:var(--muted);max-width:70ch;">Four of the dated rows are Crystal&rsquo;s honours. The <strong>2016 exhibition poster</strong> is the printed poster for the show listed above it &mdash; it is not an award. The rows marked <strong>Demo</strong> are layout scaffolding &mdash; they show the table scrolling past its five visible rows and how a landscape, portrait or square scan fits the same mount. They are not awards and will be removed before launch.</p>
		</div><!-- /.card -->
	</section>

	<?php
	/* FREEZE LINE. The direction document marks everything from this heading down as not
	   to be redesigned this round. Everything below is carried over word for word and in
	   the prototype's order; the only thing that moved is the Highlights section above,
	   on the client's own later instruction. Do not restyle, reword or reorder anything
	   from here to the end of the page without a new instruction from her. */
	?>
	<section class="section" id="philosophy">
		<div class="section-head reveal-one" style="text-align:center;">
			<div class="eyebrow" style="justify-content:center;"><span class="star">&#10022;</span> My teaching philosophy</div>
			<h2 class="section-title">Demystifying the process</h2>
		</div>
		<?php /* Colour, size and line height sit on the wrapper and are inherited, so the
		     editable field holds her words and a margin instead of the same style attribute
		     written out three times. Rendering is unchanged: 15.5px / 26.66px / --muted. */ ?>
		<div style="max-width:760px;margin:0 auto 26px;color:var(--muted);font-size:15.5px;line-height:1.72;">
			<?php echo cas_about_prose( 'cas_about_philosophy_body' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<div class="about-tri">
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Developing an artist&rsquo;s eye</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;"><?php echo cas_about_value( 'cas_about_philosophy_card_1' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Mastering your medium</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;"><?php echo cas_about_value( 'cas_about_philosophy_card_2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Finding your own visual voice</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;"><?php echo cas_about_value( 'cas_about_philosophy_card_3' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
		</div>
	</section>

	<section class="section" id="welcome">
		<div class="section-head reveal-one" style="text-align:center;">
			<div class="eyebrow" style="justify-content:center;"><span class="star">&#10022;</span> Whoever you are</div>
			<h2 class="section-title">Beginner or experienced artist</h2>
		</div>
		<div class="tc-grid">
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">Stepping into art for the very first time</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;"><?php echo cas_about_value( 'cas_about_welcome_card_1' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
			<div class="card">
				<h3 class="marker-title" style="font-size:19px;margin:0 0 8px;">An experienced painter</h3>
				<p style="margin:0;font-size:14.5px;color:var(--muted);line-height:1.7;"><?php echo cas_about_value( 'cas_about_welcome_card_2' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
		</div>
		<div style="text-align:center;margin-top:30px;">
			<p style="font-family:var(--font-marker);font-weight:600;font-size:clamp(20px,2.2vw,26px);color:var(--teal-ink);margin:0;"><?php echo cas_about_value( 'cas_about_welcome_close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
		</div>
	</section>

	<section class="section" id="contact">
		<div class="contact-wrap">
			<div class="contact-intro">
				<div class="eyebrow"><span class="star">&#10022;</span> Say hello</div>
				<h2 class="marker-title" style="font-size:clamp(26px,3vw,40px);margin:6px 0 12px;">Write to the studio.</h2>
				<p class="lead" style="font-size:16px;">A question about a course, a commission, or just to show me what you&rsquo;re painting this week &mdash; write to the studio and it comes straight to me.</p>
				<?php /* The five channel icons are the footer's, from cas_social_icon(), so the two cannot drift apart. Discord and Originals have no equivalent there and keep their own. */ ?>
				<ul class="contact-channels">
					<li>
						<span class="ch-ico"><?php echo cas_social_icon( 'email' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><strong>Email</strong><em><a href="mailto:<?php echo esc_attr( $social['email'] ); ?>"><?php echo esc_html( $social['email'] ); ?></a></em></span>
					</li>
					<li>
						<span class="ch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="12" r="1"></circle><circle cx="15" cy="12" r="1"></circle><path d="M7.5 7.2A16 16 0 0 1 12 6.5a16 16 0 0 1 4.5.7c2 3 2.6 6 2.5 9a13 13 0 0 1-4 2l-1-1.6"></path><path d="M9.5 16.6 8.5 18a13 13 0 0 1-4-2c-.1-3 .5-6 2.5-9"></path></svg></span>
						<span><strong>Discord</strong><em>Fellows channel &mdash; invite arrives after enrolment</em></span>
					</li>
					<li>
						<span class="ch-ico"><?php echo cas_social_icon( 'instagram' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><strong>Instagram</strong><em><a href="<?php echo esc_url( $social['instagram'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( cas_about_handle( $social['instagram'], '@' ) ); ?></a> &mdash; daily studies</em></span>
					</li>
					<li>
						<span class="ch-ico"><?php echo cas_social_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<span><strong>Facebook</strong><em><a href="<?php echo esc_url( $social['facebook'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( cas_about_handle( $social['facebook'], 'facebook.com/' ) ); ?></a></em></span>
					</li>
					<li>
						<span class="ch-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.1-7-11a7 7 0 0 1 14 0c0 5.9-7 11-7 11z"></path><circle cx="12" cy="10" r="2.6"></circle></svg></span>
						<?php /* The link names where it actually goes. There is no per-artwork Etsy URL yet — the Gallery's Buy buttons still resolve to the shop front — so this says where buying happens without pretending to link there. */ ?>
						<span><strong>Originals</strong><em>Originals and prints are sold through Etsy &mdash; start in the <a href="<?php echo esc_url( $gallery ); ?>" style="color:var(--ink);text-decoration:underline;text-underline-offset:2px;">gallery</a></em></span>
					</li>
				</ul>
			</div>
			<?php
			/* novalidate is deliberate: the native validation bubble is not announced by a
			   screen reader on this theme and cannot be styled to match the page, so the
			   script owns the message. The `required` attributes are asserted again in the
			   script so a later markup edit cannot quietly take them away.

			   The prototype's "Sent — thank you! ✦" note is NOT carried over. Nothing here
			   sends anything, so nothing may claim it did — see the handler in
			   cas_about_script() for what a valid submit does instead, and why it is
			   interim. */
			?>
			<form class="contact-card" id="contact-form" novalidate>
				<div class="field">
					<label for="c-name">Name</label>
					<input id="c-name" name="name" type="text" required="required" autocomplete="name" placeholder="Your name">
				</div>
				<div class="field">
					<label for="c-email">Email</label>
					<input id="c-email" name="email" type="email" required="required" autocomplete="email" placeholder="you@example.com">
				</div>
				<div class="field">
					<label for="c-msg">Message</label>
					<textarea id="c-msg" name="message" rows="6" required="required" placeholder="A question about a course, a commission, anything&hellip;"></textarea>
				</div>
				<button class="btn primary" type="submit">Send message <span class="arr" aria-hidden="true">&rarr;</span></button>
				<p id="contact-error" style="display:none;margin:10px 0 0;font-family:var(--font-cond);font-size:13px;letter-spacing:var(--tracking-label);text-transform:uppercase;color:var(--coral-ink);"></p>
			</form>
		</div>
	</section>

	<?php
	/* The section and its #terms anchor stay: cas-footer.php links to about#terms from
	   every page of the site, so removing the anchor would make a dead link site-wide.
	   What it CARRIES is another matter — see the note above the accordion. */
	?>
	<section class="section" id="terms" style="padding-bottom:80px;">
		<div class="section-head reveal-one" style="text-align:center;">
			<div class="eyebrow" style="justify-content:center;"><span class="star">&#10022;</span> The small print</div>
			<h2 class="section-title">Terms &amp; privacy</h2>
		</div>
		<?php
		/* THREE PANELS ARE MISSING FROM THIS ACCORDION ON PURPOSE. The prototype carried
		   four; only the first is here.

		   Not published, and needing copy from Crystal or her adviser before they can be:

		     1. "Using the course materials" — the licence for personal study, and the rule
		        against re-uploading, re-selling or sharing outside the household.
		     2. "Cookies" — which cookies are set, and the analytics opt-in.
		     3. "Privacy choices & data rights" — the data-subject rights statement and the
		        time limit on a deletion request.

		   The prototype's own comment above this section said its legal copy was
		   placeholder and NOT client-supplied, and it showed: it asserted UK GDPR rights, a
		   UK cookie banner and a 30-day deletion deadline on behalf of a Hong Kong-based
		   artist. That is Crystal making a data-protection commitment, with a deadline, that
		   she has never seen. Being on the wrong side of a jurisdiction makes it likelier to
		   be wrong than merely unverified.

		   DO NOT write replacement wording here. Wording invented by a second author is no
		   better than wording invented by the first. These panels come back when she or her
		   adviser supplies the text, and not before.

		   The one panel that survives says only that the terms are still being confirmed. It
		   names no refund period, no access duration, no download limit, no device
		   requirement and no payment-failure rule, and none may be added until she supplies
		   official terms. */
		?>
		<div class="acc-list">
			<details class="acc-item">
				<summary>Purchases &amp; refunds<span class="acc-ico" aria-hidden="true">+</span></summary>
				<div class="acc-body">
					<p>Courses are one-time purchases &mdash; no subscription. Pricing and the terms of access are being finalised and will be confirmed here before launch.</p>
					<p>A refund policy will apply to course purchases. The exact terms are still being confirmed and will be published here before launch.</p>
				</div>
			</details>
		</div>
	</section>

	</main>

	<?php
	/* Certificates open here from the table's info disc. On a phone this is the only way
	   to see a scan, because the mounts are not rendered at that width.

	   The <img> deliberately ships with NO src, and the src is removed again on close. Do
	   not "fix" it with src="": an empty src makes the browser request the page itself and
	   leaves a broken image behind the panel. */
	?>
	<div class="cert-lb" id="cert-lb" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Certificate" aria-labelledby="clb-cap">
		<div class="clb-backdrop" data-close></div>
		<div class="clb-panel">
			<button class="clb-close" type="button" data-close aria-label="Close certificate">&#10005;</button>
			<img id="clb-img" alt="">
			<p class="clb-cap" id="clb-cap"></p>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

add_shortcode( 'cas_about', 'cas_render_about' );

/**
 * The page's behaviour, on a dummy handle so the whole page stays in one file. If this
 * grows, move it to js/cas-about.js and enqueue it the way cas-courses.php does.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_page( 'about' ) ) {
			return;
		}

		wp_register_script( 'cas-about', false, [], null, true );
		wp_enqueue_script( 'cas-about' );
		wp_add_inline_script( 'cas-about', cas_about_script() );
	}
);

function cas_about_script() {
	$mail_to = esc_js( cas_social()['email'] );

	return <<<JS
(function () {
  var root = document.querySelector('.cas-root') || document;
  var say = function (msg, level) { if (window.CasAnnounce) window.CasAnnounce(msg, level); };

  /* ONE resize listener and ONE rAF for the whole page. Everything that wants a resize
     registers here rather than adding an unthrottled listener of its own. */
  var resizeJobs = [], resizeFrame = null;
  var runResizeJobs = function () { resizeFrame = null; for (var i = 0; i < resizeJobs.length; i++) resizeJobs[i](); };
  window.addEventListener('resize', function () {
    if (resizeFrame == null) resizeFrame = requestAnimationFrame(runResizeJobs);
  }, { passive: true });

  /* ---- the photo carousels ------------------------------------------------
     One photograph to a page, advancing on their own, with a dot per page rather than a
     "1 / 2" counter. This reverses the direction's "nothing on the page moves by itself"
     for these four only, on the client's explicit instruction (2026-08-08).

     Dwell is set per carousel in PHP and jittered here, so the four never settle into
     step. They wrap rather than stopping at the end, which is what lets them run
     unattended, and is why there are no arrows to disable at the ends.

     Autoplay pauses while the pointer is over a carousel, while it holds focus, while
     its tab is in the background, and entirely under prefers-reduced-motion. */
  var stillPlease = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  /* what each carousel is showing right now, so a paired one can avoid it */
  var SHOWING = {};

  Array.prototype.forEach.call(root.querySelectorAll('.ph-carousel'), function (host) {
    var rail = host.querySelector('.phc-rail');
    var cells = Array.prototype.slice.call(host.querySelectorAll('.phc-cell'));
    var dotBox = host.querySelector('.phc-dots');
    var navBox = host.querySelector('.phc-nav');
    if (!rail || !cells.length || !dotBox || !navBox) return;

    var id = host.id;
    var title = host.getAttribute('aria-label') || 'Photo';
    var pairId = host.getAttribute('data-pair') || '';
    var pages = cells.length;
    var page = 0;
    var paused = false, held = false, timer = null;

    /* WCAG 2.2.2: these advance by themselves for longer than five seconds beside other
       content, so they need a control that is always there — pausing on hover, on focus
       and on a hidden tab is not one. Under prefers-reduced-motion nothing advances at
       all, so no control is rendered: there is nothing to stop.
       It carries its own declarations rather than a new class, because a class with no
       rule behind it is dead CSS. The colours are custom properties, so it still follows
       the theme toggle, and 44x44 keeps it inside the touch-target rule. */
    var playBtn = null;
    if (!stillPlease && pages > 1) {
      playBtn = document.createElement('button');
      playBtn.type = 'button';
      playBtn.setAttribute('data-phc-play', '');
      playBtn.setAttribute('aria-label', 'Pause the ' + title + ' slideshow');
      playBtn.style.cssText = 'display:inline-grid;place-items:center;width:44px;height:44px;flex:none;' +
        'padding:0;border-radius:50%;cursor:pointer;font-size:11px;line-height:1;' +
        'border:1px solid color-mix(in srgb, var(--ink) 30%, transparent);' +
        'background:var(--surface);color:var(--ink);';
      navBox.insertBefore(playBtn, dotBox);
    }
    var setPlayUI = function () {
      if (!playBtn) return;
      playBtn.textContent = paused ? '▶' : '❚❚';
      playBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
    };

    var apply = function () {
      rail.style.transform = 'translateX(' + (-page * 100) + '%)';
      cells.forEach(function (c, i) { c.classList.toggle('is-current', i === page); });
      Array.prototype.forEach.call(dotBox.querySelectorAll('.phc-dot'), function (d, i) {
        d.classList.toggle('active', i === page);
        d.setAttribute('aria-current', i === page ? 'true' : 'false');
      });
      SHOWING[id] = cells[page] ? cells[page].getAttribute('data-file') : null;
    };

    /* Wraps, so the set can run unattended.
       `avoid` is on for the automatic advance and OFF for a dot press: the two stacked
       live-drawing rails run the same six photographs, so left alone they would
       eventually show the same picture twice on screen. On its own clock a rail steps
       past a page its partner is already showing. A dot press does not — pressing
       "page 4" and arriving at page 5 is a bug, not a courtesy. The loop is bounded by
       the page count, so a set whose every page collides just stays where it was asked. */
    var go = function (n, avoid) {
      var want = ((n % pages) + pages) % pages;
      var other = (avoid !== false && pairId) ? SHOWING[pairId] : null;
      if (other) {
        var tries = 0;
        while (tries < pages && cells[want] && cells[want].getAttribute('data-file') === other) {
          want = (want + 1) % pages;
          tries++;
        }
      }
      page = want;
      apply();
    };

    var stop = function () { if (timer) { clearInterval(timer); timer = null; } };
    /* Fixed once, not re-rolled per tick: a rail whose interval changed every step would
       read as stuttering rather than as two rails running at their own speeds. */
    var jitter = parseInt(host.getAttribute('data-jitter'), 10) || 0;
    var dwell = (parseInt(host.getAttribute('data-dwell'), 10) || 6000) +
      (jitter ? Math.round((Math.random() * 2 - 1) * jitter) : 0);
    var start = function () {
      stop();
      if (stillPlease || paused || held || pages < 2 || document.hidden) return;
      timer = setInterval(function () { go(page + 1); }, dwell);
    };

    dotBox.innerHTML = '';
    for (var i = 0; i < pages; i++) {
      var dot = document.createElement('button');
      dot.className = 'phc-dot';
      dot.type = 'button';
      dot.setAttribute('aria-label', 'Page ' + (i + 1) + ' of ' + pages);
      /* A dot press restarts the clock, so a page reached by hand gets its full dwell
         rather than whatever was left of the previous one. It also announces: the dots
         were the only thing that changed, and a class change announces nothing. */
      (function (n, el) {
        el.addEventListener('click', function () {
          go(n, false);
          start();
          say(title + ', page ' + (n + 1) + ' of ' + pages + '.');
        });
      })(i, dot);
      dotBox.appendChild(dot);
    }
    navBox.style.display = pages > 1 ? '' : 'none';

    if (playBtn) playBtn.addEventListener('click', function () {
      paused = !paused;
      setPlayUI();
      if (paused) stop(); else start();
      say(title + ' slideshow ' + (paused ? 'paused' : 'playing') + '.');
    });
    host.addEventListener('pointerenter', function () { held = true; stop(); });
    host.addEventListener('pointerleave', function () { held = false; start(); });
    host.addEventListener('focusin', function () { held = true; stop(); });
    host.addEventListener('focusout', function () { held = false; start(); });
    document.addEventListener('visibilitychange', function () { if (document.hidden) { stop(); } else { start(); } });

    apply();
    setPlayUI();
    start();
  });

  /* ---- the engraved panels ------------------------------------------------
     The heading straddles the frame's top rule, so the body has to be pulled up by half
     the heading's height for the four edges to meet. Measured rather than guessed: the
     headings run to two lines at some widths and a fixed value would leave a step in the
     frame.
     Every rectangle is read first, then every property is written. Alternating a read
     with a write forces a synchronous layout once per panel. */
  var measureEngraved = function () {
    var panels = Array.prototype.slice.call(root.querySelectorAll('.engraved'));
    var sizes = panels.map(function (el) {
      var head = el.querySelector('.eng-head');
      var foot = el.querySelector('.eng-foot');
      return [head ? head.getBoundingClientRect().height : null,
              foot ? foot.getBoundingClientRect().height : null];
    });
    panels.forEach(function (el, i) {
      if (sizes[i][0] != null) el.style.setProperty('--eng-h', sizes[i][0] + 'px');
      if (sizes[i][1] != null) el.style.setProperty('--eng-f', sizes[i][1] + 'px');
    });
  };
  measureEngraved();
  resizeJobs.push(measureEngraved);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(measureEngraved);

  /* ---- the certificate rail -----------------------------------------------
     A continuous drag rather than pages: the mounts are all the same size and there is
     no natural page boundary between them, so click-and-drag (or a trackpad swipe, or
     the arrow keys once focused) reads better than a step. */
  var rail = document.getElementById('cert-rail');
  var list = document.getElementById('cert-list');

  if (rail && list) {
    /* The mounts are collected BEFORE the highlight is promoted, and that ordering is the
       whole fix. Promoting moves the highlighted figure OUT of the rail; reading the rail
       afterwards therefore missed it, so the certificate the page makes the most of had no
       row in the table below, could not be opened in the lightbox from either the row or
       the mount, and the note under the table counted one honour fewer than it claimed.
       Collected first, the promoted figure keeps its place in the table — first, because it
       is first in the rail markup — which is what this code always said it did. */
    var mounts = Array.prototype.slice.call(rail.querySelectorAll('.bc-card--cert'));

    /* The highlight is promoted after the mounts are collected, so the table still lists
       every certificate in rail order and the promoted one keeps its row. */
    var hiSlot = document.getElementById('cert-highlight');
    var hiFig = rail.querySelector('.bc-card--cert[data-highlight]');
    if (hiSlot && hiFig) {
      hiSlot.hidden = false;
      hiSlot.appendChild(hiFig);
      /* Beside a 340px frame the one-line caption a 240px rail mount carries leaves most
         of the row empty. The rest of the panel is built from what the mount ALREADY
         holds — her honour as she words it in the table, and the year — so nothing here
         is written for the occasion. The caption stays as the second line: it describes
         the scan, and the two do not always agree, which is the point of keeping both. */
      var hiCap = hiFig.querySelector('figcaption');
      if (hiCap) {
        var hiYear = hiFig.getAttribute('data-year') || '';
        var hiHonour = hiFig.getAttribute('data-honour') || '';
        hiCap.innerHTML =
          '<span class="cert-hi-eyebrow"><span class="star" aria-hidden="true">✦</span> Highlight' +
            (hiYear ? ' <span class="cert-hi-year">' + hiYear + '</span>' : '') + '</span>' +
          (hiHonour ? '<span class="cert-hi-honour">' + hiHonour + '</span>' : '') +
          '<span class="cert-hi-cap">' + hiCap.innerHTML + '</span>';
      }
    }

    /* The table is built FROM the mounts, so the two cannot disagree about how many
       certificates there are or what order they are in. It is also the CONTROL:
       scrolling the table scrolls the pictures. There is no second horizontal strip of
       text — that existed only to force the sync and was not wanted. (2026-08-09) */
    var esc = function (s) {
      return String(s == null ? '' : s).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
      });
    };

    list.innerHTML = mounts.map(function (fig, i) {
      var noScan = fig.classList.contains('is-ph');
      var demo = fig.classList.contains('is-demo');
      /* The row text is HER honour, not the certificate's caption: the caption describes
         the scan and the two do not always agree. */
      var honour = fig.getAttribute('data-honour') || '';
      var year = fig.getAttribute('data-year') || '—';
      /* role="listitem" on a <button> REPLACES the implicit button role, so the rows that
         actually do something would announce as list items. The container is a named
         group rather than a list — a list whose children are not listitems is broken.
         The info disc only goes on a row that HAS a scan to open; it is the narrow-layout
         affordance and was painting a pointer cursor on rows that cannot open at all.
         The row does not carry `is-demo`: that rule drops the name to opacity .55, which
         measured 2.16:1 in theme A and 3.00:1 in theme D — so the sentence saying "this
         is scaffolding, not an award" was itself unreadable, which defeats the marking.
         The scaffolding is called out by the year cell reading "Demo" at full contrast
         and by the note under the table; the italic that rule also carried is kept. */
      return '<button class="cert-row' + (noScan ? ' is-pending' : '') +
          '" type="button" data-i="' + i + '"' + (noScan ? ' disabled' : '') + '>' +
        (noScan ? '' : '<span class="cert-info" aria-hidden="true">i</span>') +
        '<span class="cert-year">' + esc(year) + '</span>' +
        '<span class="cert-name"' + (demo ? ' style="font-style:italic;"' : '') + '>' + esc(honour) + '</span>' +
      '</button>';
    }).join('');

    /* Both scrollers are driven as a FRACTION of their own scrollable room, not by
       pixels: the table scrolls vertically in 62px rows and the rail scrolls
       horizontally in mount widths, so pixel-for-pixel would drift immediately.

       Whichever scroller the reader touches OWNS the gesture, and the other only
       follows. A single-frame guard is not enough: writing to a scroller makes it emit
       its own scroll events, and those arrive over many frames. With a one-frame lock
       the echo came back and wrote into the scroller the reader was still moving, which
       is why dragging the table stuttered and snapped row by row. Ownership is released
       a short time after the last event, so a wheel gesture stays with its scroller from
       start to finish. */
    var owner = null, releaseOwner = null;
    var claim = function (el) {
      owner = el;
      clearTimeout(releaseOwner);
      releaseOwner = setTimeout(function () { owner = null; }, 160);
    };
    var mirror = function (from, to, fromAxis, toAxis) {
      if (owner && owner !== from) return;
      claim(from);
      var fromRoom = fromAxis === 'y' ? from.scrollHeight - from.clientHeight : from.scrollWidth - from.clientWidth;
      var toRoom = toAxis === 'y' ? to.scrollHeight - to.clientHeight : to.scrollWidth - to.clientWidth;
      var at = fromRoom > 0 ? (fromAxis === 'y' ? from.scrollTop : from.scrollLeft) / fromRoom : 0;
      var want = at * toRoom;
      /* assign only on a real change, so the follower does not emit a scroll event of
         its own for a value it already holds */
      if (toAxis === 'y') { if (Math.abs(to.scrollTop - want) > 0.5) to.scrollTop = want; }
      else { if (Math.abs(to.scrollLeft - want) > 0.5) to.scrollLeft = want; }
    };
    list.addEventListener('scroll', function () { mirror(list, rail, 'y', 'x'); }, { passive: true });
    rail.addEventListener('scroll', function () { mirror(rail, list, 'x', 'y'); }, { passive: true });

    /* the lightbox — on the narrow layout, where the mounts are not rendered, this is
       the only route to a scan. The name comes from the caption (aria-labelledby in the
       markup); focus goes to the close button on open and back to whatever opened it on
       close; Tab cycles inside the panel; a focusin that lands outside while it is open
       is pulled back. `inert` while closed is belt and braces — the dialog is
       display:none, so nothing was tabbable, but the attribute keeps the accessibility
       tree honest. */
    var lb = document.getElementById('cert-lb');
    var lbImg = document.getElementById('clb-img');
    var lbCap = document.getElementById('clb-cap');
    var lbClose = lb ? lb.querySelector('.clb-close') : null;

    if (lb && lbImg && lbCap && lbClose) {
      var lbOpener = null;
      var lbIsOpen = function () { return lb.classList.contains('open'); };
      var lbFocusable = function () {
        return Array.prototype.filter.call(
          lb.querySelectorAll('a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])'),
          function (el) { return el.getClientRects().length > 0; });
      };
      var openCert = function (i) {
        var fig = mounts[i];
        var img = fig && fig.querySelector('img');
        if (!img) return;
        lbOpener = document.activeElement;
        lbImg.src = img.src;
        lbImg.alt = img.alt;
        /* The promoted mount's figcaption was rebuilt into an eyebrow, her honour and the
           original caption. Only the last of those describes the scan, and it is the one
           the other seven mounts pass here — reading the whole caption would name the
           dialog "2026Top 18 Finalist...Official Finalist...". */
        var cap = fig.querySelector('.cert-hi-cap') || fig.querySelector('figcaption');
        lbCap.textContent = cap ? cap.textContent : '';
        lb.classList.add('open');
        lb.setAttribute('aria-hidden', 'false');
        lb.removeAttribute('inert');
        document.body.style.overflow = 'hidden';
        lbClose.focus();
      };
      var closeCert = function () {
        lb.classList.remove('open');
        lb.setAttribute('aria-hidden', 'true');
        lb.setAttribute('inert', '');
        lbImg.removeAttribute('src');
        document.body.style.overflow = '';
        if (lbOpener && document.contains(lbOpener) && lbOpener.focus) lbOpener.focus();
        lbOpener = null;
      };
      lb.setAttribute('inert', '');
      lb.addEventListener('keydown', function (e) {
        if (e.key !== 'Tab' || !lbIsOpen()) return;
        var f = lbFocusable();
        if (!f.length) { e.preventDefault(); return; }
        var first = f[0], last = f[f.length - 1], a = document.activeElement;
        if (e.shiftKey && (a === first || !lb.contains(a))) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && a === last) { e.preventDefault(); first.focus(); }
      });
      document.addEventListener('focusin', function (e) {
        if (!lbIsOpen() || lb.contains(e.target)) return;
        lbClose.focus();
      });
      Array.prototype.forEach.call(list.querySelectorAll('.cert-row'), function (r, i) {
        r.addEventListener('click', function () {
          if (r.classList.contains('is-pending')) return;
          openCert(i);
        });
        /* hovering a row marks the matching MOUNT — the row's own :hover rule already
           covers the row itself */
        r.addEventListener('pointerenter', function () {
          mounts.forEach(function (m, j) { m.classList.toggle('is-active', i === j); });
        });
      });
      list.addEventListener('pointerleave', function () {
        mounts.forEach(function (m) { m.classList.remove('is-active'); });
      });
      mounts.forEach(function (fig, i) {
        if (!fig.classList.contains('is-ph')) fig.addEventListener('click', function () { openCert(i); });
      });
      Array.prototype.forEach.call(lb.querySelectorAll('[data-close]'), function (el) {
        el.addEventListener('click', closeCert);
      });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && lbIsOpen()) closeCert(); });
    }
  }

  if (rail) {
    var down = false, startX = 0, startLeft = 0, moved = 0, dragging = false;
    rail.addEventListener('pointerdown', function (e) {
      down = true; moved = 0; dragging = false; startX = e.clientX; startLeft = rail.scrollLeft;
      rail.classList.add('is-dragging');
    });
    rail.addEventListener('pointermove', function (e) {
      if (!down) return;
      var dx = e.clientX - startX;
      moved = Math.max(moved, Math.abs(dx));
      /* Capture only once this is genuinely a drag, past the same 6px threshold the click
         guard below uses. Capturing on pointerdown retargets the click that follows to
         the rail, which meant a plain click on a certificate never reached the mount's
         own handler and the pictures could not be opened at all. */
      if (!dragging && moved > 6) {
        dragging = true;
        try { rail.setPointerCapture(e.pointerId); } catch (err) {}
      }
      rail.scrollLeft = startLeft - dx;
    });
    var releaseDrag = function (e) {
      if (!down) return;
      down = false;
      rail.classList.remove('is-dragging');
      if (dragging) { try { rail.releasePointerCapture(e.pointerId); } catch (err) {} }
      dragging = false;
    };
    rail.addEventListener('pointerup', releaseDrag);
    rail.addEventListener('pointercancel', releaseDrag);
    /* a drag that ends on a mount must not also count as a click on it */
    rail.addEventListener('click', function (e) {
      if (moved > 6) { e.preventDefault(); e.stopPropagation(); }
    }, true);
  }

  /* ---- the contact form ---------------------------------------------------
     INTERIM ROUTE — REPLACE BEFORE LAUNCH. A valid submit hands the message to the
     visitor's own mail client rather than to the server, because there is no server-side
     handler yet, and the prototype's version showed "Sent — thank you!" while sending
     nothing at all. A form that silently swallows enquiries is worse than no form.

     Elementor Pro is licensed on this site and includes Form Builder: the launch version
     should capture the submission to the database AND email her, because mailto: depends
     on the visitor having a configured mail client and many will not have one.

     The address comes from cas_social() rather than being written in here, because it is
     client-editable and a hardcoded copy would go on using the old one after she changed
     it.

     The form is `novalidate` and this owns the message: the native bubble is not
     announced on this theme and cannot be styled to match the page. The reason is
     written into a real element AND spoken through the shared live region, and focus
     moves to the first field at fault. */
  var form = document.getElementById('contact-form');
  if (form) {
    var errEl = document.getElementById('contact-error');
    var fields = ['c-name', 'c-email', 'c-msg'].map(function (x) { return document.getElementById(x); })
      .filter(Boolean);
    /* asserted here as well as in the markup, so a later markup edit cannot quietly take
       the requirement away */
    fields.forEach(function (f) { if (!f.hasAttribute('required')) f.setAttribute('required', 'required'); });

    var labelOf = function (f) {
      var lab = form.querySelector('label[for="' + f.id + '"]');
      return lab ? lab.textContent.trim() : 'This field';
    };
    var why = function (f) {
      if (f.validity.valueMissing) return labelOf(f) + ' is required.';
      if (f.validity.typeMismatch) return 'Enter an email address in the form you@example.com.';
      return labelOf(f) + ' needs checking.';
    };
    var mark = function () {
      fields.forEach(function (f) {
        if (f.checkValidity()) { f.removeAttribute('aria-invalid'); f.removeAttribute('aria-describedby'); }
        else { f.setAttribute('aria-invalid', 'true'); }
      });
    };
    fields.forEach(function (f) {
      f.addEventListener('input', function () {
        if (f.checkValidity()) { f.removeAttribute('aria-invalid'); f.removeAttribute('aria-describedby'); }
      });
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var bad = fields.filter(function (f) { return !f.checkValidity(); });
      mark();

      if (bad.length) {
        var msg = why(bad[0]) + (bad.length > 1 ? ' ' + bad.length + ' fields still need filling in.' : '');
        errEl.textContent = msg;
        errEl.style.display = 'block';
        bad[0].setAttribute('aria-describedby', 'contact-error');
        bad[0].focus();
        say(msg, 'assertive');
        return;
      }

      errEl.textContent = '';
      errEl.style.display = 'none';

      var name = document.getElementById('c-name').value;
      var from = document.getElementById('c-email').value;
      /* The sender's name goes in the subject so the message is scannable in her inbox.
         encodeURIComponent, not a hand-rolled escape: a message containing an ampersand
         would otherwise truncate at it, because & separates the mailto parameters. */
      var subject = encodeURIComponent('Message from ' + name + ' via the website');
      var body = encodeURIComponent(document.getElementById('c-msg').value + '\\n\\n— ' + name + ' (' + from + ')');
      window.location.href = 'mailto:$mail_to?subject=' + subject + '&body=' + body;
      /* No "sent" note. The mail window opening IS the feedback, and this page cannot
         know whether the visitor actually sent the message. */
    });
  }

  /* reveals */
  Array.prototype.forEach.call(root.querySelectorAll('.reveal-one, .section-title'), function (el) {
    el.classList.add('in-view');
  });
})();
JS;
}
