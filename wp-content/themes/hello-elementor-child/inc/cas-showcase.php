<?php
defined( 'ABSPATH' ) || exit;

/**
 * Showcase — the student wall.
 *
 * Direction document section 11 (line 1023) settles four things and nothing else:
 * the page title is "Look at their learning outcomes", it is set in Font A, the
 * filter pills are enlarged, and the existing layout stays as it is —
 * 「維持現有版面結構」/「本輪其後只需更新素材，不作整頁重新設計」. It names no backend
 * field at all, so the editable surface here follows Herman's rule of 2026-08-23:
 * structure and headings are code, the pictures and the credits under them are hers.
 *
 * Open to Crystal: the photograph, the artwork title, the student's name, the
 * medium — which is also what the filter pills are built from — and the wall order.
 * Locked here: the page title and eyebrow, both section headings, and the
 * pinned-to-cork layout itself.
 *
 * The prototype's ten sample rows carried invented student names, titles and media,
 * and its own comment says so ("Replace all ten rows together; do not ship these
 * names"). None of them are reproduced. A wall with nothing published prints the
 * empty state, the way the Gallery does — it does not fill itself with samples.
 */

const CAS_SHOWCASE_POST_TYPE    = 'cas_showcase';
const CAS_SHOWCASE_META_STUDENT = '_cas_showcase_student';
const CAS_SHOWCASE_META_USER = '_cas_showcase_user_id';
const CAS_SHOWCASE_META_MEDIUM  = '_cas_showcase_medium';

/* Five tiles to a row is the layout the chapter tells us to keep. The prototype
   stopped at ten because it only had ten photographs; a hard cap here would mean an
   eleventh piece she publishes simply never appears, with nothing in the admin to
   say why — the failure the register logs as trap T3. Rows repeat instead. */
const CAS_SHOWCASE_ROW = 5;


/* ------------------------------------------------------------------
   Post type and meta
   ------------------------------------------------------------------ */

/**
 * public => false because a piece of student work has no page of its own: it is read
 * on the wall and opened in the lightbox. A single-piece permalink would be an empty
 * room, and it would put a student's name into search results on a URL nobody links
 * to.
 *
 * No 'editor'. The prototype gives a tile a photograph, a title, a name and a medium
 * and gives it nowhere to put prose — the lightbox caption is one flex row. A body
 * field would be a box she can type into that never appears anywhere.
 */
function cas_register_showcase_post_type() {
	register_post_type(
		CAS_SHOWCASE_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'Student work',
				'singular_name'      => 'Student work',
				'add_new'            => 'Add student work',
				'add_new_item'       => 'Add student work',
				'edit_item'          => 'Edit student work',
				'new_item'           => 'New student work',
				'view_item'          => 'View student work',
				'search_items'       => 'Search student work',
				'not_found'          => 'No student work yet',
				'not_found_in_trash' => 'No student work in the bin',
				'all_items'          => 'All student work',
				'menu_name'          => 'Student work',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			// Nothing here is prose, so the block editor is not the authoring surface
			// and the REST route it would need is not created.
			'show_in_rest'        => false,
			'menu_icon'           => 'dashicons-images-alt2',
			'menu_position'       => 22,
			// 'page-attributes' is what puts the Order box on screen — the wall order.
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'cas_register_showcase_post_type' );

/**
 * Registered as well as saved, so the sanitisers also run for WP-CLI and for any
 * import of the real credits when they arrive.
 */
function cas_register_showcase_meta() {
	$can_edit = function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta( CAS_SHOWCASE_POST_TYPE, CAS_SHOWCASE_META_USER, array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => $can_edit ) );
	foreach ( array( CAS_SHOWCASE_META_STUDENT, CAS_SHOWCASE_META_MEDIUM ) as $key ) {
		register_post_meta(
			CAS_SHOWCASE_POST_TYPE,
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'cas_sanitize_showcase_text',
				'auth_callback'     => $can_edit,
			)
		);
	}
}
add_action( 'init', 'cas_register_showcase_meta' );

/**
 * Both fields are printed as text and one of them also becomes a filter chip, so
 * markup is stripped on the way in. An empty value stays empty: the tile then omits
 * that line rather than printing a bare separator, which is what the prototype had
 * to be fixed for.
 */
function cas_sanitize_showcase_text( $value ) {
	return trim( sanitize_text_field( (string) $value ) );
}


/* ------------------------------------------------------------------
   Meta box
   ------------------------------------------------------------------ */

function cas_add_showcase_meta_box() {
	add_meta_box(
		'cas-showcase-credit',
		'Credit',
		'cas_render_showcase_meta_box',
		CAS_SHOWCASE_POST_TYPE,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'cas_add_showcase_meta_box' );

/** Select public user columns only; do not instantiate users or read unrelated metadata. */
function cas_showcase_students() {
	return get_users( array( 'role__in' => array( 'subscriber', 'tutor_student' ), 'role__not_in' => array( 'administrator', 'editor', 'author', 'contributor', 'tutor_instructor' ), 'orderby' => 'display_name', 'fields' => array( 'ID', 'display_name' ), 'number' => -1 ) );
}
function cas_showcase_student_name( $post_id ) {
	if ( ! metadata_exists( 'post', $post_id, CAS_SHOWCASE_META_USER ) ) {
		return (string) get_post_meta( $post_id, CAS_SHOWCASE_META_STUDENT, true );
	}
	$id = (int) get_post_meta( $post_id, CAS_SHOWCASE_META_USER, true );
	if ( ! $id ) { return ''; }
	$users = get_users( array( 'include' => array( $id ), 'fields' => array( 'ID', 'display_name' ), 'number' => 1 ) );
	return $users ? (string) $users[0]->display_name : '';
}

function cas_render_showcase_meta_box( $post ) {
	wp_nonce_field( 'cas_save_showcase_credit', 'cas_showcase_nonce' );

	$student = (int) get_post_meta( $post->ID, CAS_SHOWCASE_META_USER, true );
	$legacy = ! metadata_exists( 'post', $post->ID, CAS_SHOWCASE_META_USER ) ? (string) get_post_meta( $post->ID, CAS_SHOWCASE_META_STUDENT, true ) : '';
	$students = cas_showcase_students();
	$medium  = get_post_meta( $post->ID, CAS_SHOWCASE_META_MEDIUM, true );
	?>
	<p>
		<label for="cas-showcase-student"><strong>Registered Student</strong></label><br>
		<select id="cas-showcase-student" name="cas_showcase_user_id" class="widefat">
			<option value="0" <?php selected( ! $student && ! $legacy ); ?>>No Student Credit</option>
			<?php if ( $legacy ) : ?><option value="legacy" selected>Keep Existing Credit: <?php echo esc_html( $legacy ); ?></option><?php endif; ?>
			<?php if ( $student && ! in_array( $student, array_map( static function ( $user ) { return (int) $user->ID; }, $students ), true ) ) : ?>
			<option value="<?php echo esc_attr( (string) $student ); ?>" selected>Existing Credit: <?php echo esc_html( cas_showcase_student_name( $post->ID ) ?: 'Account Unavailable' ); ?></option>
			<?php endif; ?>
			<?php foreach ( $students as $user ) : ?><option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $student, (int) $user->ID ); ?>><?php echo esc_html( $user->display_name . ' (#' . $user->ID . ')' ); ?></option><?php endforeach; ?>
		</select>
		<span class="description">Choose a registered student. Their current display name appears under the picture. Upload the picture manually using Featured Image.</span>
	</p>
	<p>
		<label for="cas-showcase-medium"><strong>Medium</strong></label><br>
		<input type="text" id="cas-showcase-medium" name="cas_showcase_medium" class="widefat"
			list="cas-showcase-media" placeholder="Watercolour"
			value="<?php echo esc_attr( $medium ); ?>">
		<datalist id="cas-showcase-media">
			<?php foreach ( cas_showcase_media_in_use() as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
		<span class="description">
			The filter buttons above the wall are built from these. Two pieces share a
			button when they share a medium, so pick from the list where you can. The
			row of buttons appears once two different media are in use.
		</span>
	</p>
	<p class="description">
		The <strong>featured image</strong> is the picture on the wall, and a piece
		without one is not shown. It is cropped to a square, so any photograph fits.
	</p>
	<?php
}

/**
 * The title box is the name of the PIECE, and the field one column away asks for
 * the student's name — so the obvious wrong answer is to put the name in both.
 * Nothing else on this screen says which box wants which.
 *
 * Classic-editor hooks, which is the editor this post type gets: show_in_rest is
 * false, so WordPress does not load the block editor here.
 */
function cas_showcase_title_placeholder( $text, $post ) {
	return ( CAS_SHOWCASE_POST_TYPE === $post->post_type ) ? 'Title of the piece' : $text;
}
add_filter( 'enter_title_here', 'cas_showcase_title_placeholder', 10, 2 );

function cas_showcase_title_note( $post ) {
	if ( CAS_SHOWCASE_POST_TYPE !== $post->post_type ) {
		return;
	}
	echo '<p class="description" style="margin:6px 2px 0;">What the painting is called &mdash; not who painted it. The student&rsquo;s name goes in the <strong>Credit</strong> box on the right. Leave this empty and the piece appears without a title.</p>';
}
add_action( 'edit_form_after_title', 'cas_showcase_title_note' );

/**
 * The media already in use, for the datalist. It is the only guard against
 * "Watercolour" and "Water colour" becoming two buttons for the same thing — the
 * filter folds case and punctuation, but a different spelling still splits the row.
 */
function cas_showcase_media_in_use() {
	$found = array();
	foreach ( cas_showcase_pieces() as $piece ) {
		if ( '' !== $piece['medium'] && ! in_array( $piece['medium'], $found, true ) ) {
			$found[] = $piece['medium'];
		}
	}
	sort( $found );

	return $found;
}

function cas_save_showcase_credit( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['cas_showcase_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cas_showcase_nonce'] ) ), 'cas_save_showcase_credit' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$input = $_POST['cas_showcase_user_id'] ?? null;
	$previous = (int) get_post_meta( $post_id, CAS_SHOWCASE_META_USER, true );
	$legacy = 'legacy' === $input && ! metadata_exists( 'post', $post_id, CAS_SHOWCASE_META_USER ) && '' !== (string) get_post_meta( $post_id, CAS_SHOWCASE_META_STUDENT, true );
	$valid = is_string( $input ) && preg_match( '/^(?:0|[1-9][0-9]*)$/D', $input ) && ( '0' === $input || ( $previous > 0 && $previous === (int) $input ) || in_array( (int) $input, array_map( static function ( $user ) { return (int) $user->ID; }, cas_showcase_students() ), true ) );
	if ( ! $legacy && ! $valid ) {
		add_filter( 'redirect_post_location', static function ( $location ) { return add_query_arg( 'cas_credit_error', '1', $location ); } );
		return;
	}
	if ( ! $legacy ) { update_post_meta( $post_id, CAS_SHOWCASE_META_USER, (int) $input ); }

	update_post_meta(
		$post_id,
		CAS_SHOWCASE_META_MEDIUM,
		isset( $_POST['cas_showcase_medium'] ) ? wp_unslash( $_POST['cas_showcase_medium'] ) : ''
	);
}
add_action( 'save_post_' . CAS_SHOWCASE_POST_TYPE, 'cas_save_showcase_credit' );

/**
 * A piece with no featured image is dropped from the wall, and from the list screen
 * there is nothing to say so. The Gallery learned this with Etsy links; here the
 * missing picture is the whole tile.
 */
function cas_showcase_admin_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );

	$columns['cas_photo']   = 'Picture';
	$columns['cas_student'] = 'Student';
	$columns['cas_medium']  = 'Medium';

	return array_merge( $columns, $date );
}
add_filter( 'manage_' . CAS_SHOWCASE_POST_TYPE . '_posts_columns', 'cas_showcase_admin_columns' );

function cas_showcase_admin_column( $column, $post_id ) {
	if ( 'cas_photo' === $column ) {
		echo has_post_thumbnail( $post_id ) ? 'Set' : 'Missing &mdash; not shown on the wall';
	} elseif ( 'cas_student' === $column ) {
		$student = cas_showcase_student_name( $post_id );
		echo ( '' === $student ) ? '&mdash;' : esc_html( $student );
	} elseif ( 'cas_medium' === $column ) {
		$medium = get_post_meta( $post_id, CAS_SHOWCASE_META_MEDIUM, true );
		echo ( '' === $medium ) ? '&mdash;' : esc_html( $medium );
	}
}
add_action( 'manage_' . CAS_SHOWCASE_POST_TYPE . '_posts_custom_column', 'cas_showcase_admin_column', 10, 2 );


/* ------------------------------------------------------------------
   Data
   ------------------------------------------------------------------ */

/**
 * Every published piece that has a picture, in wall order. Read twice per request at
 * most — the datalist in the admin, the wall on the front end — so it is worth the
 * static.
 */
function cas_showcase_pieces() {
	static $pieces = null;
	if ( null !== $pieces ) {
		return $pieces;
	}

	$posts = get_posts(
		array(
			'post_type'        => CAS_SHOWCASE_POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => -1,
			// Menu order is her own curation. Date only breaks ties between pieces
			// she left at 0.
			'orderby'          => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'suppress_filters' => false,
		)
	);

	$pieces = array();
	foreach ( $posts as $post ) {
		$thumb_id = get_post_thumbnail_id( $post );
		if ( ! $thumb_id ) {
			// The tile IS the photograph. An empty print pinned to the board reads as
			// a broken page rather than as a missing upload.
			continue;
		}
		$pieces[] = array(
			'id'      => $post->ID,
			'thumb'   => (int) $thumb_id,
			'title'   => get_the_title( $post ),
			'student' => cas_showcase_student_name( $post->ID ),
			'medium'  => (string) get_post_meta( $post->ID, CAS_SHOWCASE_META_MEDIUM, true ),
		);
	}

	return $pieces;
}

/**
 * A repeatable float in [$min,$max) derived from a seed.
 *
 * The prototype re-rolled the pinning on every load, which is right for a demo and
 * wrong here: a print that hangs at a different angle on each visit looks like a
 * bug, and these values are written into the markup, so a fresh roll per request
 * would also defeat page caching. Hashing the post id keeps a piece pinned where it
 * was until the wall itself changes.
 */
function cas_showcase_jitter( $seed, $min, $max ) {
	return $min + ( crc32( (string) $seed ) % 10000 ) / 10000 * ( $max - $min );
}


/* ------------------------------------------------------------------
   Front end
   ------------------------------------------------------------------ */

add_shortcode( 'cas_showcase', 'cas_render_showcase' );

function cas_render_showcase() {
	$pieces = cas_showcase_pieces();

	ob_start();

	// Guarded because a helper that failed to load should cost the banner, not the
	// page; and its contract is not visible from this file, so a hero that is
	// printed and one that is returned are both handled.
	if ( function_exists( 'cas_page_hero' ) ) {
		// Section 11 fixes this wording and Font A. The helper's h1 carries font-a
		// and the default clamp, which is the size the prototype hero uses.
		$hero = cas_page_hero( 'Student showcase', 'Look at their learning outcomes' );
		if ( is_string( $hero ) ) {
			echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the theme's own helper.
		}
	}

	// No introduction on this page: the lead was removed on the client's instruction
	// of 2026-08-08, and section 11 asks only for new material, so nothing in the
	// direction is left unimplemented by its absence.
	?>

	<main class="container" id="content" tabindex="-1">
		<section class="section" id="sw-section" style="padding-top:18px;">
			<?php
			echo cas_showcase_filter_rail( $pieces ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in the helper.

			if ( $pieces ) :
				?>
				<div class="pol-frame reveal">
					<div class="pol-wall" id="sw-grid" role="group" aria-label="Student artwork wall">
						<?php echo cas_showcase_rows( $pieces ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in cas_showcase_tile(). ?>
					</div>
				</div>
				<div class="no-results" id="sw-empty" role="status">Nothing in this medium yet.</div>
			<?php else : ?>
				<div class="store-empty" role="status">No student work is published yet.</div>
			<?php endif; ?>
		</section>

	</main>

	<?php
	if ( $pieces ) {
		cas_showcase_lightbox();
	}

	return ob_get_clean();
}

/**
 * The medium filter.
 *
 * Built only when the pieces offer a real choice, which is the prototype's rule and
 * not an omission: with one medium the row is a single "All" button that cannot
 * change what is on the wall, and the "nothing in this medium" state is unreachable.
 * The moment a second medium is used, both appear with nothing to re-wire.
 *
 * Values are keyed on a slug so "Watercolour" and "watercolour" are one button; the
 * label shown is the first spelling in wall order.
 */
function cas_showcase_filter_rail( $pieces ) {
	$media = array();
	foreach ( $pieces as $piece ) {
		if ( '' === $piece['medium'] ) {
			continue;
		}
		$key = sanitize_title( $piece['medium'] );
		if ( '' !== $key && ! isset( $media[ $key ] ) ) {
			$media[ $key ] = $piece['medium'];
		}
	}

	if ( count( $media ) < 2 ) {
		return '';
	}

	ob_start();
	?>
	<div class="filter-rail" style="margin-bottom:18px;">
		<div class="fg-opts" id="sw-filter">
			<button class="fopt active" type="button" data-val="all" aria-pressed="true">All</button>
			<?php foreach ( $media as $key => $label ) : ?>
				<button class="fopt" type="button" data-val="<?php echo esc_attr( $key ); ?>" aria-pressed="false"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * The wall, in rows of five.
 *
 * One pair per wall hangs from a single pin through both top corners — the lead
 * print from its top-right, its neighbour from its top-left — which is the detail
 * that stops the board reading as a grid. It needs both prints in the same row, so
 * it only forms once a first row is full.
 */
function cas_showcase_rows( $pieces ) {
	$seed = 0;
	foreach ( $pieces as $piece ) {
		$seed = crc32( $seed . '-' . $piece['id'] );
	}

	$pair_lead = ( count( $pieces ) >= CAS_SHOWCASE_ROW ) ? 1 + ( $seed % 3 ) : -1;

	$html = '';
	foreach ( array_chunk( $pieces, CAS_SHOWCASE_ROW, true ) as $row ) {
		$html .= '<div class="pol-row">';
		foreach ( $row as $index => $piece ) {
			$html .= cas_showcase_tile( $piece, $index, $pair_lead );
		}
		$html .= '</div>';
	}

	return $html;
}

function cas_showcase_tile( $piece, $index, $pair_lead ) {
	$pins    = array( '#4d6625', '#a13d2d', '#2f4c6b', '#c9992e' );
	$id      = $piece['id'];
	$is_lead = ( $index === $pair_lead );
	// -1 is the no-pair sentinel, and -1 + 1 is 0, so the mate test needs the same
	// guard the lead test has. Without it every wall of fewer than five prints hung
	// its FIRST print as a mate: no pin, pulled 26px left, z-index 0.
	$is_mate = ( $pair_lead >= 0 && $index === $pair_lead + 1 );

	$classes = 'pol-tile';
	if ( $is_lead ) {
		$rot  = cas_showcase_jitter( $id . 'rot', -7, -4 );
		$pinx = '94%';
		$mx   = cas_showcase_jitter( $id . 'mx', -8, -2 );
	} elseif ( $is_mate ) {
		$rot      = cas_showcase_jitter( $id . 'rot', 4, 7 );
		$pinx     = '6%';
		$mx       = -26;
		$classes .= ' pol-nopin';
	} else {
		$rot  = cas_showcase_jitter( $id . 'rot', -3.5, 3.5 );
		$pinx = round( cas_showcase_jitter( $id . 'pinx', 25, 75 ) ) . '%';
		$mx   = cas_showcase_jitter( $id . 'mx', -14, 0 );
	}
	$mt  = cas_showcase_jitter( $id . 'mt', -10, 22 );
	$z   = $is_mate ? 0 : 1 + (int) cas_showcase_jitter( $id . 'z', 0, 9 );
	$pin = $pins[ (int) cas_showcase_jitter( $id . 'pin', 0, count( $pins ) ) ];

	$style = sprintf(
		'--rot:%sdeg;--mx:%dpx;--mt:%dpx;--z:%d;--pinx:%s;--pin:%s;',
		number_format( $rot, 2, '.', '' ),
		(int) round( $mx ),
		(int) round( $mt ),
		$z,
		$pinx,
		$pin
	);

	// An absent field renders nothing rather than an empty chip, a bare separator or
	// an accessible name reading " by ". The button still has to be nameable, so a
	// piece with no title is named for what it is and where it is on the wall.
	$caption = implode( ' by ', array_filter( array( $piece['title'], $piece['student'] ) ) );
	$label   = 'View ' . ( '' !== $caption ? $caption : 'student artwork ' . ( $index + 1 ) );

	// alt="" on the picture: the button already carries the same words, and an alt
	// here would read them twice. The slot is a fixed square with object-fit: cover,
	// so any photograph she uploads is cropped to it and cannot reshape a row.
	$img_attr = array(
		'alt'   => '',
		'sizes' => '(max-width: 700px) 76vw, 194px',
	);
	if ( 0 === $index ) {
		$img_attr['loading']       = 'eager';
		$img_attr['fetchpriority'] = 'high';
	} elseif ( $index < CAS_SHOWCASE_ROW ) {
		// The first row is above the fold at every width.
		$img_attr['loading'] = 'eager';
	}

	$full = wp_get_attachment_image_url( $piece['thumb'], 'large' );

	ob_start();
	?>
	<button class="<?php echo esc_attr( $classes ); ?>" type="button"
		style="<?php echo esc_attr( $style ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
		data-full="<?php echo esc_url( (string) $full ); ?>"
		data-title="<?php echo esc_attr( $piece['title'] ); ?>"
		data-name="<?php echo esc_attr( $piece['student'] ); ?>"
		data-index="<?php echo esc_attr( (string) ( $index + 1 ) ); ?>"
		<?php if ( '' !== $piece['medium'] ) : ?>
		data-med="<?php echo esc_attr( sanitize_title( $piece['medium'] ) ); ?>"
		data-med-label="<?php echo esc_attr( $piece['medium'] ); ?>"
		<?php endif; ?>><span class="pol-photo"><?php
			echo wp_get_attachment_image( $piece['thumb'], 'medium_large', false, $img_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() escapes its own attributes.
			if ( '' !== $piece['medium'] ) :
				?><span class="pol-med"><?php echo esc_html( $piece['medium'] ); ?></span><?php
			endif;
		?></span><?php if ( '' !== $caption ) : ?><span class="pol-cap"><?php
			if ( '' !== $piece['title'] ) :
				?><em><?php echo esc_html( $piece['title'] ); ?></em><?php
			endif;
			if ( '' !== $piece['student'] ) :
				?><span><?php echo esc_html( $piece['student'] ); ?></span><?php
			endif;
		?></span><?php endif; ?></button>
	<?php

	return ob_get_clean();
}

/**
 * The lightbox, printed once. It carries no piece of its own: the script fills it
 * from the tile that opened it, so the tile and the large view cannot disagree.
 *
 * #swm-img ships with NO src attribute. src="" would make the browser request this
 * document as the image.
 */
function cas_showcase_lightbox() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	?>
	<div class="sw-modal" id="sw-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Student artwork">
		<button class="sw-close" type="button" aria-label="Close">&#10005;</button>
		<figure>
			<img id="swm-img" alt="">
			<figcaption id="swm-figcap"><span class="sw-ava" id="swm-ava"></span><span id="swm-cap"></span></figcaption>
		</figure>
	</div>
	<?php
}


/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

/**
 * Enqueued from this file rather than from functions.php, so the page owns its own
 * assets — the pattern the Courses archive and the course page already use.
 *
 * The cork wall's CSS is the prototype's page-scoped <style> block ported as is.
 * industrial-3.css carries the lightbox (.sw-modal, .sw-close, .sw-ava) and
 * industrial-2.css the filter chips, but nothing in the bundle styles .pol-*, so
 * without this the wall is a column of unstyled buttons. It goes in as an inline
 * style on a file-less handle — the trick cas-about.php already uses for its script
 * — so this page needs no file outside the two it owns. Lifting it into
 * css/cas-showcase.css later is a copy and paste.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		$here = ( $post && has_shortcode( (string) $post->post_content, 'cas_showcase' ) ) || is_page( 'showcase' );
		if ( ! $here ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_register_style( 'cas-showcase', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-showcase' );
		wp_add_inline_style( 'cas-showcase', cas_showcase_css( $uri ) );

		wp_enqueue_script( 'cas-showcase', $uri . '/js/cas-showcase.js', array(), filemtime( $dir . '/js/cas-showcase.js' ), true );
	},
	27
);

/**
 * The cork board, the maple frame and the pinned prints, ported from
 * Showcase.dc.html lines 26-84. Two lines carry decisions worth keeping:
 * `align-items:flex-start` on the row, because the default `stretch` fed a tile's
 * negative --mt into the polaroid chin and made a 48px chin measure 48-73px; and the
 * enlarged .fopt, which is section 11's 「所有 Filter Pills 的字體加大」.
 */
function cas_showcase_css( $uri ) {
	$cork = esc_url( $uri . '/img/corkboard.jpg' );

	return <<<CSS
/* "instant photo" wall (client-approved concept) —
   Desktop: prints pinned to a cork board (水松板), loosely overlapping, coloured pins.
   Phone: one vertical strip, taped down. Motion = pendulum physics in cas-showcase.js. */
.pol-frame { padding: 18px; border-radius: 12px;
  background-color:#7a5427;
  background-image: var(--oak-grain), linear-gradient(145deg, #9c6f39 0%, #82582a 32%, #63421d 64%, #472e12 100%);
  background-size: 300px 120px, 100% 100%;
  box-shadow:
    inset 0 2px 3px rgba(255,238,205,.6),
    inset 0 -3px 6px rgba(70,45,15,.55),
    inset 3px 0 4px rgba(255,238,205,.25), inset -3px 0 5px rgba(70,45,15,.35),
    0 16px 36px rgba(0,0,0,.35), 0 4px 10px rgba(0,0,0,.22); }
.pol-wall { position:relative; display:flex; flex-direction:column; gap:38px; padding: 48px 34px 44px;
  background-color:#c9aa7d;
  background-image:
    radial-gradient(120% 90% at 50% 0%, rgba(255,244,222,.16), transparent 60%),
    url("{$cork}");
  background-size: cover;
  background-position: center;
  border-radius: 5px;
  box-shadow: 0 0 0 2px rgba(84,56,22,.6), inset 0 3px 22px rgba(60,40,16,.35), inset 0 -2px 12px rgba(60,40,16,.2); }
.pol-row { display:flex; flex-wrap:wrap; justify-content:center; align-items:flex-start; gap: 28px 14px; }
.pol-tile { position:relative; display:block; border:0; cursor:pointer; background:#f6f1e6; padding:11px 11px 48px; width:216px;
  margin: var(--mt, 0) var(--mx, 0) 0; z-index: var(--z, 1);
  box-shadow: 0 22px 34px rgba(40,26,12,.42), 0 6px 12px rgba(40,26,12,.28);
  transform: rotate(var(--rot, 0deg)); transform-origin: var(--pinx, 50%) -6px; transition: box-shadow .25s ease; text-align:left; }
.pol-tile:hover, .pol-tile:focus-visible { box-shadow: 0 30px 52px rgba(40,26,12,.55); z-index: 30; }
.pol-nopin::before { display: none !important; }
.pol-photo { display:block; position:relative; width:194px; height:194px; overflow:hidden; background:#2b2823; }
.pol-photo img { width:100%; height:100%; object-fit:cover; display:block; filter: saturate(.94) contrast(1.02); }
.filter-rail .fg-opts .fopt { font-size: 14px; padding: 10px 18px; }
.pol-med { position:absolute; left:8px; bottom:8px; font-family:var(--font-cond); font-size:12px; font-weight:600; letter-spacing:var(--tracking-label); text-transform:uppercase; color:#f3ede1; background:rgba(43,40,35,.72); padding:4px 9px; border-radius:2px; }
.pol-cap { position:absolute; left:13px; right:13px; bottom:10px; display:flex; flex-direction:column; gap:1px; }
.pol-cap em { font-family:var(--font-marker); font-style:italic; font-weight:600; font-size:14.5px; color:#474138; line-height:1.18; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pol-cap span { font-family:var(--font-cond); font-size:12.5px; font-weight:500; letter-spacing:var(--tracking-label); text-transform:uppercase; color:rgba(71,65,56,.62); }
.pol-tile::before { content:""; position:absolute; top:-8px; left: var(--pinx, 50%); margin-left:-8px; width:16px; height:16px; border-radius:50%;
  background: radial-gradient(circle at 35% 30%, color-mix(in srgb, var(--pin, #9a6436) 45%, white), var(--pin, #9a6436) 55%, color-mix(in srgb, var(--pin, #9a6436) 55%, black) 95%);
  box-shadow: 0 3px 5px rgba(0,0,0,.45); z-index:3; }
@media (max-width: 700px) {
  .pol-wall { gap: 26px; padding: 36px 20px 34px; }
  .pol-row { flex-direction: column; align-items: center; gap: 26px; }
  .pol-tile { width: min(76vw, 300px); padding: 12px 12px 54px; margin: var(--mt, 0) 0 0; }
  .pol-photo { width: 100%; height: auto; aspect-ratio: 1 / 1; }
  .pol-cap em { font-size: 16px; }
  .pol-tile::before { border-radius: 1px; width: 88px; height: 26px; top: -14px; margin-left: -44px; transform: rotate(-2deg); background: linear-gradient(180deg, rgba(226,209,172,.88), rgba(206,186,146,.82)); box-shadow: 0 2px 5px rgba(0,0,0,.25); border-left: 1px dashed rgba(120,100,70,.35); border-right: 1px dashed rgba(120,100,70,.35); }
}
CSS;
}

add_action( 'admin_notices', function () {
	if ( '1' === ( $_GET['cas_credit_error'] ?? '' ) && get_current_screen() && CAS_SHOWCASE_POST_TYPE === get_current_screen()->post_type ) {
		echo '<div class="notice notice-error"><p>Student credit and medium were not saved. Choose a registered student from the list. Other post changes may have been saved.</p></div>';
	}
} );
