<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gallery — Crystal's own artwork, sold through Etsy.
 *
 * Direction document section 9 line 1004 opens exactly seven fields per piece:
 * image, name, description, price, Etsy URL, stock status and sort order. Four
 * of those are native WordPress (featured image, title, editor, menu order); the
 * remaining three are the post meta registered below. Everything else the
 * prototype card shows — the medium and format chips, the "30 x 40 cm" size line,
 * the struck-through was-price and its Sale ribbon — is fed by fields the chapter
 * does not open, so under the default-closed rule (1.4.14) those elements are not
 * rendered at all rather than being backed by a field nobody agreed to.
 */

const CAS_ARTWORK_POST_TYPE  = 'cas_artwork';
const CAS_ARTWORK_META_PRICE = '_cas_artwork_price';
const CAS_ARTWORK_META_ETSY  = '_cas_artwork_etsy_url';
const CAS_ARTWORK_META_STOCK = '_cas_artwork_stock';


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

	$price = get_post_meta( $post->ID, CAS_ARTWORK_META_PRICE, true );
	$etsy  = get_post_meta( $post->ID, CAS_ARTWORK_META_ETSY, true );
	$stock = get_post_meta( $post->ID, CAS_ARTWORK_META_STOCK, true );
	$stock = ( 'sold' === $stock ) ? 'sold' : 'available';
	?>
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

	$price = isset( $_POST['cas_artwork_price'] ) ? wp_unslash( $_POST['cas_artwork_price'] ) : '';
	$etsy  = isset( $_POST['cas_artwork_etsy_url'] ) ? wp_unslash( $_POST['cas_artwork_etsy_url'] ) : '';
	$stock = isset( $_POST['cas_artwork_stock'] ) ? wp_unslash( $_POST['cas_artwork_stock'] ) : 'available';

	// The registered sanitize_callback runs on the way in, so these land clean.
	update_post_meta( $post_id, CAS_ARTWORK_META_PRICE, $price );
	update_post_meta( $post_id, CAS_ARTWORK_META_ETSY, $etsy );
	update_post_meta( $post_id, CAS_ARTWORK_META_STOCK, $stock );
}
add_action( 'save_post_' . CAS_ARTWORK_POST_TYPE, 'cas_save_artwork_details' );

/**
 * A missing Etsy link is invisible from the artwork list, and it is the one
 * omission that silently costs a sale. The column makes it countable at a glance.
 */
function cas_artwork_admin_columns( $columns ) {
	$date = isset( $columns['date'] ) ? array( 'date' => $columns['date'] ) : array();
	unset( $columns['date'] );

	$columns['cas_price'] = 'Price';
	$columns['cas_etsy']  = 'Etsy link';
	$columns['cas_stock'] = 'Availability';

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
		<section class="store-main">
			<div class="store-grid">
				<?php
				$index = 0;
				while ( $query->have_posts() ) :
					$query->the_post();
					$card = cas_artwork_card( get_post(), $index );
					if ( '' === $card ) {
						continue;
					}
					echo $card; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped field by field in cas_artwork_card().
					++$index;
				endwhile;
				wp_reset_postdata();
				?>
			</div>
			<?php if ( 0 === $index ) : ?>
				<div class="store-empty" role="status">No artwork is published yet.</div>
			<?php endif; ?>
		</section>
	</main>

	<?php
	// The dialog is one element for the whole page, so a second [cas_gallery]
	// reuses it instead of duplicating its ids.
	cas_artwork_modal();

	return ob_get_clean();
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

	$title = get_the_title( $post );
	$price = get_post_meta( $post->ID, CAS_ARTWORK_META_PRICE, true );
	$etsy  = get_post_meta( $post->ID, CAS_ARTWORK_META_ETSY, true );
	$sold  = ( 'sold' === get_post_meta( $post->ID, CAS_ARTWORK_META_STOCK, true ) );

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
	<article class="art-card">
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
 * The carved frame overlay. Its CSS lives in the Gallery page's own <style> block
 * in the prototype, not in industrial-*.css, so unless that block was ported this
 * element is display:none and the bevelled .art-frame border shows instead —
 * which is a complete look in its own right. The file check keeps a broken image
 * out of every card if the asset was not copied across.
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
	<script>
	(function () {
		var modal = document.getElementById('art-modal');
		if (!modal) { return; }

		var mImg   = document.getElementById('am-img');
		var mFrame = modal.querySelector('.am-frame');
		var mBox   = modal.querySelector('.am-frame .am-img');
		var mTitle = document.getElementById('am-title');
		var mTags  = document.getElementById('am-tags');
		var mDesc  = document.getElementById('am-desc');
		var mPrice = document.getElementById('am-price');
		var mBuy   = document.getElementById('am-buy');
		var opener = null;

		var FOCUS_SEL = 'a[href],button:not([disabled]),[tabindex]:not([tabindex="-1"])';
		/* getClientRects() is the test rather than the selector alone, because the
		   Buy button is present in the markup for every piece and hidden for the
		   ones with no listing — a trap that included it would land focus nowhere. */
		function focusables() {
			return Array.prototype.filter.call(
				modal.querySelectorAll(FOCUS_SEL),
				function (el) { return el.getClientRects().length > 0; }
			);
		}

		function open(card, button) {
			var img = card.querySelector('.art-img img');
			if (img) {
				/* srcset and sizes are set before src, or the browser picks a
				   candidate from a srcset it has not been given yet. The dialog
				   frame is smaller than the card, so it asks for a smaller rung of
				   the same ladder. */
				if (img.srcset) { mImg.srcset = img.srcset; mImg.sizes = '237px'; }
				else { mImg.removeAttribute('srcset'); mImg.removeAttribute('sizes'); }
				mImg.src = img.currentSrc || img.src;
				mImg.alt = img.alt || '';
				mImg.style.display = '';
			}

			var frame = card.querySelector('.art-frame');
			var box   = card.querySelector('.art-img');
			if (box) { mBox.style.aspectRatio = box.style.getPropertyValue('--r') || ''; }
			mFrame.classList.toggle('land', !!(frame && frame.classList.contains('land')));

			var title = card.querySelector('.art-title');
			mTitle.textContent = title ? title.textContent : '';

			/* Every value below is copied from markup this file printed and
			   escaped, so innerHTML here carries nothing the server did not
			   already approve. */
			var tags = card.querySelector('.art-tagrow');
			mTags.innerHTML = tags ? tags.innerHTML : '';

			var desc = card.querySelector('.cas-art-desc');
			mDesc.innerHTML = desc ? desc.innerHTML : '';

			var price = card.querySelector('.art-price');
			mPrice.textContent = price ? price.textContent : '';
			mPrice.style.display = price ? '' : 'none';

			/* The dialog offers a Buy button only when the card did. A piece with
			   no listing of its own, or one already sold, has nowhere correct to
			   send the visitor. */
			var buy = card.querySelector('.art-btn.etsy');
			if (buy) {
				mBuy.href = buy.getAttribute('href');
				mBuy.setAttribute('aria-label', buy.getAttribute('aria-label') || '');
				mBuy.style.display = '';
			} else {
				mBuy.style.display = 'none';
				mBuy.removeAttribute('href');
			}

			modal.classList.add('open');
			modal.setAttribute('aria-hidden', 'false');
			opener = button || document.activeElement;
			var close_btn = modal.querySelector('.am-close');
			if (close_btn) { close_btn.focus(); }
		}

		function close() {
			if (!modal.classList.contains('open')) { return; }
			modal.classList.remove('open');
			modal.setAttribute('aria-hidden', 'true');
			if (opener && opener.focus) { try { opener.focus(); } catch (e) {} }
			opener = null;
		}

		/* Delegated, so cards rendered by a second [cas_gallery] on the page are
		   wired without this script running twice. */
		document.addEventListener('click', function (e) {
			var button = e.target.closest ? e.target.closest('[data-cas-detail]') : null;
			if (!button) { return; }
			var card = button.closest('.art-card');
			if (card) { open(card, button); }
		});

		Array.prototype.forEach.call(modal.querySelectorAll('[data-close]'), function (el) {
			el.addEventListener('click', close);
		});

		/* The dialog claims aria-modal="true", so Tab has to stay inside it. The
		   prototype shipped without this and eight consecutive Tab presses all
		   landed on the cards behind. */
		modal.addEventListener('keydown', function (e) {
			if (e.key !== 'Tab' || !modal.classList.contains('open')) { return; }
			var f = focusables();
			if (!f.length) { e.preventDefault(); return; }
			var first = f[0], last = f[f.length - 1], a = document.activeElement;
			if (e.shiftKey && (a === first || !modal.contains(a))) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && (a === last || !modal.contains(a))) { e.preventDefault(); first.focus(); }
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { close(); }
		});
	}());
	</script>
	<?php
}
