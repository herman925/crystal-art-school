<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gallery — Crystal's own artwork, sold through Etsy.
 *
 * The design session of 2026-08-28 adds a medium field so the locked introduction's
 * filter promise is true. The open fields are now image, name, description, medium,
 * price, Etsy URL, stock status and sort order. Five are native WordPress (featured
 * image, title, editor, menu order and the post date used for date-added sorting); the
 * remaining four are the post meta registered below. Everything else the prototype
 * card shows — the format chip, the "30 x 40 cm" size line, the struck-through
 * was-price and its Sale ribbon — is fed by fields the chapter does not open, so under
 * the default-closed rule (1.4.14) those elements are not rendered at all rather than
 * being backed by a field nobody agreed to.
 */

const CAS_ARTWORK_POST_TYPE    = 'cas_artwork';
const CAS_ARTWORK_META_PRICE   = '_cas_artwork_price';
const CAS_ARTWORK_META_ETSY    = '_cas_artwork_etsy_url';
const CAS_ARTWORK_META_STOCK   = '_cas_artwork_stock';
const CAS_ARTWORK_META_MEDIUM  = '_cas_artwork_medium';


/* ------------------------------------------------------------------
   Post type and meta
   ------------------------------------------------------------------ */

/**
 * The type is public => false because an artwork has no page of its own: it is
 * read through [cas_gallery] and bought on Etsy. A single-artwork permalink would
 * be an empty room with a Buy button in it, and it would compete with the real
 * Etsy listing in search results.
 */
function cas_register_artwork_post_type() {
	register_post_type(
		CAS_ARTWORK_POST_TYPE,
		array(
			'labels'              => array(
				'name'               => 'Artwork',
				'singular_name'      => 'Artwork',
				'add_new'            => 'Add artwork',
				'add_new_item'       => 'Add artwork',
				'edit_item'          => 'Edit artwork',
				'new_item'           => 'New artwork',
				'view_item'          => 'View artwork',
				'search_items'       => 'Search artwork',
				'not_found'          => 'No artwork yet',
				'not_found_in_trash' => 'No artwork in the bin',
				'all_items'          => 'All artwork',
				'menu_name'          => 'Artwork',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			// The block editor is the agreed authoring surface for prose (2026-07-26
			// authoring decision), and it needs the REST route to exist.
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-art',
			'menu_position'       => 21,
			// 'page-attributes' is what puts the Order box on the screen, which is
			// the "sort order" field the chapter opens.
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);
}
add_action( 'init', 'cas_register_artwork_post_type' );

/**
 * Registering the meta rather than trusting the save handler alone means the
 * sanitisers also run for anything that writes through update_post_meta later —
 * an importer, WP-CLI, or a bulk load of the real listings.
 */
function cas_register_artwork_meta() {
	$can_edit = function () {
		return current_user_can( 'edit_posts' );
	};

	register_post_meta(
		CAS_ARTWORK_POST_TYPE,
		CAS_ARTWORK_META_PRICE,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_sanitize_artwork_price',
			'auth_callback'     => $can_edit,
		)
	);

	register_post_meta(
		CAS_ARTWORK_POST_TYPE,
		CAS_ARTWORK_META_ETSY,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_sanitize_artwork_etsy_url',
			'auth_callback'     => $can_edit,
		)
	);

	register_post_meta(
		CAS_ARTWORK_POST_TYPE,
		CAS_ARTWORK_META_STOCK,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_sanitize_artwork_stock',
			'auth_callback'     => $can_edit,
		)
	);

	register_post_meta(
		CAS_ARTWORK_POST_TYPE,
		CAS_ARTWORK_META_MEDIUM,
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'cas_sanitize_artwork_medium',
			'auth_callback'     => $can_edit,
		)
	);
}
add_action( 'init', 'cas_register_artwork_meta' );

/**
 * An empty price stays empty, and the card then omits the price line. Printing a
 * stray zero over a real painting is worse than printing nothing.
 */
function cas_sanitize_artwork_price( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	$value = preg_replace( '/[^0-9.]/', '', $value );
	if ( '' === $value || ! is_numeric( $value ) ) {
		return '';
	}
	// A trailing ".00" is noise beside a marker-set price, so whole pounds print whole.
	$number = (float) $value;
	if ( floor( $number ) === $number ) {
		return (string) (int) $number;
	}
	return rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' );
}

/**
 * Only http(s) survives. A javascript: or data: URL in a field the client edits
 * would run in every visitor's browser from a button we tell them to trust.
 */
function cas_sanitize_artwork_etsy_url( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	return esc_url_raw( $value, array( 'http', 'https' ) );
}

function cas_sanitize_artwork_stock( $value ) {
	return ( 'sold' === $value ) ? 'sold' : 'available';
}

/**
 * Medium is deliberately a free text field rather than a new taxonomy. The filter
 * chips are derived from the values Crystal actually enters, so the UI cannot invent
 * a list of media that the catalogue does not contain.
 */
function cas_sanitize_artwork_medium( $value ) {
	return trim( sanitize_text_field( (string) $value ) );
}


/* ------------------------------------------------------------------
   Meta box
   ------------------------------------------------------------------ */

function cas_add_artwork_meta_box() {
	add_meta_box(
		'cas-artwork-details',
		'Sale details',
		'cas_render_artwork_meta_box',
		CAS_ARTWORK_POST_TYPE,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'cas_add_artwork_meta_box' );

function cas_render_artwork_meta_box( $post ) {
	wp_nonce_field( 'cas_save_artwork_details', 'cas_artwork_nonce' );

	$price  = get_post_meta( $post->ID, CAS_ARTWORK_META_PRICE, true );
	$etsy   = get_post_meta( $post->ID, CAS_ARTWORK_META_ETSY, true );
	$stock  = get_post_meta( $post->ID, CAS_ARTWORK_META_STOCK, true );
	$medium = get_post_meta( $post->ID, CAS_ARTWORK_META_MEDIUM, true );
	$stock  = ( 'sold' === $stock ) ? 'sold' : 'available';
	?>
	<p>
		<label for="cas-artwork-medium"><strong>Medium</strong></label><br>
		<input type="text" id="cas-artwork-medium" name="cas_artwork_medium" class="widefat"
			list="cas-artwork-media" value="<?php echo esc_attr( $medium ); ?>">
		<datalist id="cas-artwork-media">
			<?php foreach ( cas_artwork_media_in_use( $post->ID ) as $option ) : ?>
				<option value="<?php echo esc_attr( $option ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
		<span class="description">
			Used to build the Gallery&rsquo;s medium filter. Pick an existing value where
			possible so the same medium does not become two separate buttons.
		</span>
	</p>
	<p>
		<label for="cas-artwork-price"><strong>Price (&pound;)</strong></label><br>
		<input type="number" id="cas-artwork-price" name="cas_artwork_price" class="widefat"
			step="0.01" min="0" inputmode="decimal"
			value="<?php echo esc_attr( $price ); ?>">
		<span class="description">Leave empty to show the piece without a price.</span>
	</p>
	<p>
		<label for="cas-artwork-etsy"><strong>Etsy listing link</strong></label><br>
		<input type="url" id="cas-artwork-etsy" name="cas_artwork_etsy_url" class="widefat"
			placeholder="https://www.etsy.com/listing/&hellip;"
			value="<?php echo esc_attr( $etsy ); ?>">
		<span class="description">
			The link to <em>this</em> piece on Etsy. Without it the gallery shows the
			artwork with no Buy button, so a buyer is never sent to a different listing.
		</span>
	</p>
	<p>
		<label for="cas-artwork-stock"><strong>Availability</strong></label><br>
		<select id="cas-artwork-stock" name="cas_artwork_stock" class="widefat">
			<option value="available" <?php selected( $stock, 'available' ); ?>>Available</option>
			<option value="sold" <?php selected( $stock, 'sold' ); ?>>Sold</option>
		</select>
	</p>
	<?php
}

/**
 * Existing medium values are suggestions only. The field remains free text because
 * no controlled taxonomy was agreed; this list simply prevents accidental spelling
 * variants when Crystal adds another artwork.
 */
function cas_artwork_media_in_use( $exclude_id = 0 ) {
	$found = array();
	$posts = get_posts(
		array(
			'post_type'      => CAS_ARTWORK_POST_TYPE,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		if ( (int) $exclude_id === (int) $post_id ) {
			continue;
		}

		$medium = trim( (string) get_post_meta( $post_id, CAS_ARTWORK_META_MEDIUM, true ) );
		if ( '' !== $medium && ! in_array( $medium, $found, true ) ) {
			$found[] = $medium;
		}
	}

	natcasesort( $found );

	return array_values( $found );
}

function cas_save_artwork_details( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['cas_artwork_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['cas_artwork_nonce'] ) ), 'cas_save_artwork_details' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$price  = isset( $_POST['cas_artwork_price'] ) ? wp_unslash( $_POST['cas_artwork_price'] ) : '';
	$etsy   = isset( $_POST['cas_artwork_etsy_url'] ) ? wp_unslash( $_POST['cas_artwork_etsy_url'] ) : '';
	$stock  = isset( $_POST['cas_artwork_stock'] ) ? wp_unslash( $_POST['cas_artwork_stock'] ) : 'available';
	$medium = isset( $_POST['cas_artwork_medium'] ) ? wp_unslash( $_POST['cas_artwork_medium'] ) : '';

	// The registered sanitize_callback runs on the way in, so these land clean.
	update_post_meta( $post_id, CAS_ARTWORK_META_PRICE, $price );
	update_post_meta( $post_id, CAS_ARTWORK_META_ETSY, $etsy );
	update_post_meta( $post_id, CAS_ARTWORK_META_STOCK, $stock );
	update_post_meta( $post_id, CAS_ARTWORK_META_MEDIUM, $medium );
}
add_action( 'save_post_' . CAS_ARTWORK_POST_TYPE, 'cas_save_artwork_details' );

/**
 * A missing Etsy link is invisible from the artwork list, and it is the one
 * omission that silently costs a sale. The column makes it countable at a glance.
 */
function cas_artwork_admin_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );

	$columns['cas_price']  = 'Price';
	$columns['cas_etsy']   = 'Etsy link';
	$columns['cas_stock']  = 'Availability';
	$columns['cas_medium'] = 'Medium';

	return array_merge( $columns, $date );
}
add_filter( 'manage_' . CAS_ARTWORK_POST_TYPE . '_posts_columns', 'cas_artwork_admin_columns' );

function cas_artwork_admin_column( $column, $post_id ) {
	if ( 'cas_price' === $column ) {
		$price = get_post_meta( $post_id, CAS_ARTWORK_META_PRICE, true );
		echo ( '' === $price ) ? '&mdash;' : '&pound;' . esc_html( $price );
	} elseif ( 'cas_etsy' === $column ) {
		$etsy = get_post_meta( $post_id, CAS_ARTWORK_META_ETSY, true );
		echo ( '' === $etsy ) ? 'Missing &mdash; no Buy button' : 'Linked';
	} elseif ( 'cas_stock' === $column ) {
		echo ( 'sold' === get_post_meta( $post_id, CAS_ARTWORK_META_STOCK, true ) ) ? 'Sold' : 'Available';
	} elseif ( 'cas_medium' === $column ) {
		$medium = get_post_meta( $post_id, CAS_ARTWORK_META_MEDIUM, true );
		echo ( '' === $medium ) ? '&mdash;' : esc_html( $medium );
	}
}
add_action( 'manage_' . CAS_ARTWORK_POST_TYPE . '_posts_custom_column', 'cas_artwork_admin_column', 10, 2 );


/* ------------------------------------------------------------------
   Front end
   ------------------------------------------------------------------ */

add_shortcode( 'cas_gallery', 'cas_render_gallery' );

function cas_render_gallery() {
	$query = new WP_Query(
		array(
			'post_type'      => CAS_ARTWORK_POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			// Menu order is the client's own curation — the "sort order" field the
			// chapter opens. Date only breaks ties between pieces she left at 0.
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'  => true,
		)
	);

	$items = array();
	$index = 0;

	foreach ( $query->posts as $post ) {
		$card = cas_artwork_card( $post, $index );
		if ( '' === $card ) {
			continue;
		}

		$price = get_post_meta( $post->ID, CAS_ARTWORK_META_PRICE, true );
		$items[] = array(
			'html'   => $card,
			'medium' => trim( (string) get_post_meta( $post->ID, CAS_ARTWORK_META_MEDIUM, true ) ),
			'price'  => is_numeric( $price ) ? (float) $price : null,
			'date'   => (int) get_post_time( 'U', true, $post ),
		);
		++$index;
	}

	wp_reset_postdata();
	$rail = cas_gallery_filter_rail( $items );

	ob_start();

	// The helper's contract is not visible from this file, so a banner that is
	// printed and a banner that is returned are both handled. Guarded because a
	// helper that failed to load should cost the banner, not the whole page.
	if ( function_exists( 'cas_page_hero' ) ) {
		$hero = cas_page_hero( 'The gallery shop', 'Take a Piece Home' );
		if ( is_string( $hero ) ) {
			echo $hero; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup from the theme's own helper.
		}
	}
	?>

	<!-- Crystal's own introduction, quoted in full in the design direction (9.1)
	     with the instruction 不改寫 — do not rewrite. It was once replaced by a
	     paraphrase during an anti-fabrication sweep, which misread her copy as an
	     invented claim. The fulfilment promises in it are hers to make. -->
	<div class="container page-lead-wrap"><p class="page-lead">Original artworks and limited-edition prints directly from the studio — each piece is signed by the artist and comes with a certificate of authenticity and elegant packaging. You can filter by medium, price, or date added, then head over to Etsy to make your purchase.</p></div>

	<main class="container store-head" id="content" tabindex="-1">
		<?php if ( $rail ) : ?>
			<div class="store-layout">
				<div class="filter-col">
					<?php echo $rail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- filter values are escaped in cas_gallery_filter_rail(). ?>
				</div>
		<?php endif; ?>
			<section class="store-main">
				<div class="store-bar">
					<span class="store-count" id="store-count" role="status" aria-live="polite"><?php echo esc_html( $index . ( 1 === $index ? ' piece' : ' pieces' ) ); ?></span>
					<div class="active-tags" id="active-tags"></div>
				</div>
				<div class="store-grid" id="store-grid">
					<?php foreach ( $items as $item ) : ?>
						<?php echo $item['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in cas_artwork_card(). ?>
					<?php endforeach; ?>
				</div>
				<?php if ( 0 === $index ) : ?>
					<div class="store-empty" id="store-empty" role="status">No artwork is published yet.</div>
				<?php else : ?>
					<div class="store-empty" id="store-empty" role="status" style="display:none;">No pieces match those filters &mdash; try widening your search.</div>
				<?php endif; ?>
			</section>
		<?php if ( $rail ) : ?>
			</div>
		<?php endif; ?>
	</main>

	<?php
	// The dialog is one element for the whole page, so a second [cas_gallery]
	// reuses it instead of duplicating its ids.
	cas_artwork_modal();

	return ob_get_clean();
}

/**
 * Build the left rail from the visible cards rather than from every published post.
 * An artwork without a featured image is intentionally not a card, so it must not
 * create a medium chip or a price range that can never return a visible result.
 */
function cas_gallery_filter_rail( array $items ) {
	$media  = array();
	$prices = array();

	foreach ( $items as $item ) {
		if ( '' !== $item['medium'] && ! in_array( $item['medium'], $media, true ) ) {
			$media[] = $item['medium'];
		}
		if ( null !== $item['price'] ) {
			$prices[] = $item['price'];
		}
	}

	natcasesort( $media );
	$media = array_values( $media );
	$groups = '';

	if ( count( $media ) > 1 ) {
		$groups .= '<div class="fgroup" data-group="medium">'
			. '<div class="fg-title">Medium</div>'
			. '<div class="fg-opts">'
			. cas_gallery_filter_chip( 'all', 'Any', true );

		foreach ( $media as $medium ) {
			$groups .= cas_gallery_filter_chip( $medium, $medium );
		}

		$groups .= '</div></div>';
	}

	if ( $prices && min( $prices ) < max( $prices ) ) {
		$range = cas_gallery_price_range( $prices );
		$groups .= '<div class="fgroup" data-group="price">'
			. '<div class="fg-title">Max price</div>'
			. '<div class="price-val" id="artwork-price-val" data-symbol="£">Any price</div>'
			. '<div class="slider-wrap">'
			. '<div class="slider-ui" id="artwork-price-ui" style="--pct:100%;">'
			. '<div class="slider-track"></div><div class="slider-fill"></div>'
			. '<input class="price-slider" id="artwork-price-slider" type="range"'
			. ' min="' . esc_attr( $range['min'] ) . '" max="' . esc_attr( $range['max'] ) . '"'
			. ' step="' . esc_attr( $range['step'] ) . '" value="' . esc_attr( $range['max'] ) . '"'
			. ' aria-label="Maximum price" aria-valuetext="Any price">'
			. '<div class="slider-knob"></div>'
			. '</div>'
			. '<div class="price-scale">'
			. '<span>' . esc_html( '£' . $range['min'] ) . '</span>'
			. '<span>' . esc_html( '£' . $range['max'] . '+' ) . '</span>'
			. '</div>'
			. '</div></div>';
	}

	if ( $groups ) {
		$filters = '<aside class="filter-panel" aria-label="Filter artwork">'
			. '<div class="fp-head"><span class="fp-title">Filter</span><button class="fp-clear" id="artwork-filter-clear" type="button">Clear all</button></div>'
			. $groups
			. '</aside>';
	} else {
		$filters = '';
	}

	if ( count( $items ) > 1 ) {
		$sort = '<aside class="filter-panel sort-panel" aria-label="Sort artwork">'
			. '<div class="fp-head"><span class="fp-title">Sort by</span></div>'
			. '<div class="fg-opts" id="artwork-sort">'
			. cas_gallery_filter_chip( 'featured', 'Featured', true )
			. cas_gallery_filter_chip( 'newest', 'Newest' )
			. cas_gallery_filter_chip( 'oldest', 'Oldest' )
			. cas_gallery_filter_chip( 'price-asc', 'Price ↑' )
			. cas_gallery_filter_chip( 'price-desc', 'Price ↓' )
			. '</div></aside>';
	} else {
		$sort = '';
	}

	return $sort . $filters;
}

function cas_gallery_filter_chip( $value, $label, $active = false ) {
	return sprintf(
		'<button class="fopt%1$s" type="button" data-val="%2$s" aria-pressed="%3$s">%4$s</button>',
		$active ? ' active' : '',
		esc_attr( $value ),
		$active ? 'true' : 'false',
		esc_html( $label )
	);
}

/**
 * Same rounded slider range as the Courses rail, kept local so Gallery does not
 * depend on another page module's helper existing first.
 */
function cas_gallery_price_range( array $prices ) {
	$lo    = min( $prices );
	$hi    = max( $prices );
	$span  = max( $hi - $lo, 1 );
	$steps = array( 1, 2, 5, 10, 20, 25, 50, 100, 200 );
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

	return array(
		'min'  => (int) ( floor( $lo / $step ) * $step ),
		'max'  => (int) ( ceil( $hi / $step ) * $step ),
		'step' => $step,
	);
}

/**
 * One card. Returns an empty string for an artwork with no featured image: the
 * card IS the framed photograph, and an empty frame reads as a broken page rather
 * than as a missing upload.
 */
function cas_artwork_card( $post, $index ) {
	$thumb_id = get_post_thumbnail_id( $post );
	if ( ! $thumb_id ) {
		return '';
	}

	$title  = get_the_title( $post );
	$price  = get_post_meta( $post->ID, CAS_ARTWORK_META_PRICE, true );
	$etsy   = get_post_meta( $post->ID, CAS_ARTWORK_META_ETSY, true );
	$sold   = ( 'sold' === get_post_meta( $post->ID, CAS_ARTWORK_META_STOCK, true ) );
	$medium = trim( (string) get_post_meta( $post->ID, CAS_ARTWORK_META_MEDIUM, true ) );
	$date   = (int) get_post_time( 'U', true, $post );

	// The ratio comes from the file the client actually uploaded. The prototype
	// learned this the hard way: a filename-keyed lookup table fell back to 0.8
	// and squashed every piece it did not recognise.
	$ratio = cas_artwork_ratio( $thumb_id );

	// The first row is above the fold at every width — three cards at 1692, one at
	// 390 — so those three load eagerly and the first carries the priority.
	$img_attr = array(
		// A fixed 306px is correct at every width this grid has, so no per-viewport
		// override is needed.
		'sizes' => '306px',
		'alt'   => $title,
	);
	if ( 0 === $index ) {
		$img_attr['loading']       = 'eager';
		$img_attr['fetchpriority'] = 'high';
	} elseif ( $index < 3 ) {
		$img_attr['loading'] = 'eager';
	}
	$img = wp_get_attachment_image( $thumb_id, 'large', false, $img_attr );

	// The Buy button exists only when this piece has its own listing to open. A
	// shop-front or search URL here would look like a working link and land the
	// buyer on the wrong page, which is worse than no button at all.
	$can_buy = ( ! $sold && '' !== $etsy );

	// Only ever read in the quick view, so the description rides along hidden
	// rather than in a data- attribute, which would flatten the client's
	// paragraphs into one attribute value.
	//
	// Blocks and paragraphs are rendered directly instead of through
	// apply_filters( 'the_content' ). That filter is already running one level up
	// for the page this shortcode sits in, and anything hooked to it — sharing
	// buttons, related posts, a subscribe box — would be injected into every
	// artwork's two-line description.
	$description = wpautop( wptexturize( do_blocks( $post->post_content ) ) );
	$has_desc    = ( '' !== trim( wp_strip_all_tags( $description ) ) );

	ob_start();
	?>
	<article class="art-card" data-medium="<?php echo esc_attr( $medium ); ?>" data-price="<?php echo esc_attr( is_numeric( $price ) ? $price : '' ); ?>" data-date="<?php echo esc_attr( $date ); ?>">
		<div class="art-frame-wrap">
			<figure class="art-frame<?php echo ( $ratio > 1.15 ) ? ' land' : ''; ?>">
				<div class="art-img" style="--r:<?php echo esc_attr( $ratio ); ?>"><?php
					echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() escapes its own attributes.
				?></div>
				<?php echo cas_artwork_ornate(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url() inside the helper. ?>
			</figure>
		</div>
		<div class="art-meta">
			<h3 class="art-title"><?php echo esc_html( $title ); ?></h3>
			<?php if ( $sold ) : ?>
				<div class="art-tagrow"><span class="art-tagchip">Sold</span></div>
			<?php endif; ?>
			<?php if ( $has_desc ) : ?>
				<div class="cas-art-desc" hidden><?php echo wp_kses_post( $description ); ?></div>
			<?php endif; ?>
			<div class="art-row">
				<?php if ( '' !== $price ) : ?>
					<span class="art-price">&pound;<?php echo esc_html( $price ); ?></span>
				<?php endif; ?>
				<div class="art-actions">
					<?php if ( $can_buy ) : ?>
						<a class="art-btn etsy" href="<?php echo esc_url( $etsy ); ?>" target="_blank" rel="noopener" aria-label="Buy <?php echo esc_attr( $title ); ?> on Etsy (opens in a new tab)">Buy on Etsy</a>
					<?php endif; ?>
					<button class="art-btn detail" type="button" data-cas-detail aria-label="View details of <?php echo esc_attr( $title ); ?>">View details</button>
				</div>
			</div>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/**
 * width / height of the uploaded file, which is what both the --r custom property
 * and the rotated .land frame are keyed on. 0.8 is the same fallback the
 * stylesheet already carries, so an attachment with no stored dimensions lands
 * where the CSS would have put it anyway.
 */
function cas_artwork_ratio( $thumb_id ) {
	$meta = wp_get_attachment_metadata( $thumb_id );
	if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
		return 0.8;
	}
	return round( $meta['width'] / $meta['height'], 3 );
}

/**
 * The carved frame overlay. The Gallery module owns both the SVG and the page-scoped
 * CSS that positions it around portrait and landscape uploads. The file check still
 * keeps a broken image out of every card if the asset is ever removed accidentally.
 */
function cas_artwork_ornate() {
	static $markup = null;
	if ( null !== $markup ) {
		return $markup;
	}

	$markup = '';
	if ( file_exists( get_stylesheet_directory() . '/img/deer-black.svg' ) ) {
		$markup = '<img class="art-ornate" src="' . esc_url( get_stylesheet_directory_uri() . '/img/deer-black.svg' ) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">';
	}
	return $markup;
}

/**
 * The quick-view dialog, printed once per page. It carries no artwork of its own;
 * the script fills it from the card that opened it, so there is a single source
 * for every value and no chance of the card and the dialog disagreeing.
 */
function cas_artwork_modal() {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;

	$ornate = str_replace( 'class="art-ornate"', 'class="am-ornate"', cas_artwork_ornate() );
	?>
	<div class="art-modal" id="art-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="am-title">
		<div class="am-backdrop" data-close></div>
		<div class="am-panel">
			<button class="am-close" type="button" aria-label="Close" data-close>&#10005;</button>
			<div class="am-frame-wrap">
				<figure class="am-frame">
					<div class="am-img"><img id="am-img" alt="" style="display:none;"></div>
					<?php echo $ornate; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_url() in cas_artwork_ornate(). ?>
				</figure>
			</div>
			<div class="am-body">
				<h2 class="am-title" id="am-title">&mdash;</h2>
				<div class="am-tags" id="am-tags"></div>
				<div class="am-desc" id="am-desc"></div>
				<div class="am-price" id="am-price" style="display:none;"></div>
				<div class="am-actions">
					<!-- Hidden by inline style, not by the `hidden` attribute: the UA rule
					     for [hidden] loses to `.art-btn { display: inline-flex }`, so the
					     attribute alone would leave the button on screen. -->
					<a class="art-btn etsy" id="am-buy" target="_blank" rel="noopener" style="padding:12px 18px;display:none;">Buy on Etsy</a>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/* ------------------------------------------------------------------
   Assets
   ------------------------------------------------------------------ */

function cas_gallery_is_here() {
	if ( ! is_singular() ) {
		return false;
	}

	$post = get_post();

	return is_page( 'gallery' )
		|| ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'cas_gallery' ) );
}

/**
 * The rail and carved-frame layout lived in Gallery.dc.html's page-scoped style
 * block. Keep that CSS owned by the Gallery module instead of changing the shared
 * industrial bundle or the shared enqueue file.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! cas_gallery_is_here() ) {
			return;
		}

		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();

		wp_register_style( 'cas-gallery', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-gallery' );
		wp_add_inline_style( 'cas-gallery', cas_gallery_css() );
		wp_enqueue_script( 'cas-gallery', $uri . '/js/cas-gallery.js', array(), filemtime( $dir . '/js/cas-gallery.js' ), true );
	},
	27
);

function cas_gallery_css() {
	return <<<'CSS'
/* The carved deer frame is the Gallery's page-scoped frame in every theme. */
.art-frame, .art-modal .am-frame { margin: 0; }
.art-frame { background: transparent; border: 0; box-shadow: none; position: relative; aspect-ratio: 18426 / 21301; height: 92cqh; width: auto; max-width: 92cqw; max-height: 92cqh; }
.art-frame .art-img { position: absolute; top: 7.4%; bottom: 7.4%; left: 9.1%; right: 9.1%; height: auto; aspect-ratio: auto; box-shadow: inset 0 0 0 1px rgba(0,0,0,.35); }
.art-ornate { display: block; position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; filter: drop-shadow(0 16px 26px rgba(0,0,0,.55)); }
.art-frame.land { container-type: size; aspect-ratio: 21301 / 18426; width: 92cqw; height: auto; max-width: 92cqw; max-height: 92cqh; }
.art-frame.land .art-ornate { inset: auto; left: 50%; top: 50%; width: 100cqh; height: 100cqw; transform: translate(-50%, -50%) rotate(90deg); }
.art-frame.land .art-img { top: 9.1%; bottom: 9.1%; left: 7.4%; right: 7.4%; }

/* Quick view uses the same carved frame, with a landscape rotation when needed. */
.art-modal .am-frame { background: transparent; border: 0; box-shadow: none; position: relative; aspect-ratio: 18426 / 21301; width: 78%; }
.art-modal .am-frame .am-img { position: absolute; top: 7.4%; bottom: 7.4%; left: 9.1%; right: 9.1%; aspect-ratio: auto !important; box-shadow: inset 0 0 0 1px rgba(0,0,0,.35); }
.am-ornate { display: block; position: absolute; inset: 0; width: 100%; height: 100%; pointer-events: none; filter: drop-shadow(0 16px 26px rgba(0,0,0,.55)); }
.art-modal .am-frame.land { container-type: size; aspect-ratio: 21301 / 18426; width: 88%; }
.art-modal .am-frame.land .am-ornate { inset: auto; left: 50%; top: 50%; width: 100cqh; height: 100cqw; transform: translate(-50%, -50%) rotate(90deg); }
.art-modal .am-frame.land .am-img { top: 9.1%; bottom: 9.1%; left: 7.4%; right: 7.4%; }

/* Theme A uses the same asset with a lighter treatment. */
.cas-root:not([data-theme="D"]) .art-ornate,
.cas-root:not([data-theme="D"]) .am-ornate {
	filter: brightness(2.15) sepia(.5) saturate(1.1) drop-shadow(0 12px 20px rgba(96,66,34,.38));
}

/* Active-filter chips are real buttons, so neutralise browser button chrome. */
button.active-tag { margin: 0; border: 0; line-height: 1.25; font-weight: 500; -webkit-appearance: none; appearance: none; }
CSS;
}
