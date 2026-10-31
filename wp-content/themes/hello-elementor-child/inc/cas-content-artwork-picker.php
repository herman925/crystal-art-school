<?php
/** Owner-requested Home artwork selection, independent of unsaved page text. */
defined( 'ABSPATH' ) || exit;

function cas_content_gallery_fields() {
	return array( CAS_HOME_GALLERY_ARTWORK => array( 'default' => '', 'label' => 'Gallery Image' ) );
}
function cas_content_gallery_clean( $input ) {
	$key = CAS_HOME_GALLERY_ARTWORK;
	if ( ! is_array( $input ) || array_keys( $input ) !== array( $key ) || ! is_string( $input[ $key ] ) || ! preg_match( '/^[1-9][0-9]*$/D', $input[ $key ] ) || ! cas_home_gallery_image( (int) $input[ $key ] ) ) {
		return new WP_Error( 'home-gallery_input', 'Choose one published artwork with a public picture. Nothing has been saved.' );
	}
	return array( $key => (string) (int) $input[ $key ] );
}

add_action( 'wp_ajax_cas_home_gallery_save', function () {
	if ( ! current_user_can( CAS_CONTENT_CAP ) ) { wp_send_json_error( array( 'message' => 'You cannot change the Home gallery image.', 'reload' => true ), 403 ); }
	$option = 'theme_mods_' . get_option( 'stylesheet' );
	$result = cas_content_prose_save( 'home-gallery', $option, $_POST['snapshot'] ?? null, array( CAS_HOME_GALLERY_ARTWORK => $_POST['artwork'] ?? null ) );
	if ( is_wp_error( $result ) ) {
		$reload = 'home-gallery_input' !== $result->get_error_code();
		wp_send_json_error( array( 'message' => $result->get_error_message(), 'reload' => $reload ), $reload ? 409 : 422 );
	}
	$row = cas_content_prose_row( 'home-gallery', $option );
	$id = (int) ( $_POST['artwork'] ?? 0 );
	if ( is_wp_error( $row ) || ! cas_home_gallery_image( $id ) ) {
		wp_send_json_error( array( 'message' => 'The saved image could not be confirmed. Keep any unsaved text and reload this page before continuing.', 'reload' => true ), 409 );
	}
	wp_send_json_success( array( 'id' => $id, 'title' => get_the_title( $id ), 'image' => wp_get_attachment_image_url( cas_home_gallery_image( $id ), 'medium' ), 'snapshot' => cas_content_prose_snapshot( 'home-gallery', $option, $row['mods'] ), 'nonce' => wp_create_nonce( 'cas_content_home-gallery_save' ) ) );
} );

function cas_content_gallery_picker() {
	$option = 'theme_mods_' . get_option( 'stylesheet' );
	$row = cas_content_prose_row( 'home-gallery', $option );
	if ( is_wp_error( $row ) ) { echo '<p role="alert">The gallery choice could not be read. Reload before choosing a picture.</p>'; return; }
	$chosen = cas_home_gallery_artwork_id();
	$artworks = cas_home_gallery_artworks( array( 'title' => 'ASC' ) );
	$dir = get_stylesheet_directory(); $uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'cas-artwork-picker', $uri . '/css/cas-artwork-picker.css', array(), filemtime( $dir . '/css/cas-artwork-picker.css' ) );
	wp_enqueue_script( 'cas-artwork-picker', $uri . '/js/cas-artwork-picker.js', array(), filemtime( $dir . '/js/cas-artwork-picker.js' ), true );
	?>
	<section id="home-gallery-image" class="cas-gallery-choice"
		data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'cas_content_home-gallery_save' ) ); ?>"
		data-snapshot="<?php echo esc_attr( cas_content_prose_snapshot( 'home-gallery', $option, $row['mods'] ) ); ?>"
		data-chosen="<?php echo esc_attr( (string) $chosen ); ?>">
		<h2>Gallery Image</h2>
		<p>Choose an existing Artwork picture for the Home gallery card. This saves separately from the text below. Replacing that artwork’s picture also updates Home.</p>
		<div class="cas-gallery-current">
			<img alt="" <?php if ( $chosen ) { echo 'src="' . esc_url( wp_get_attachment_image_url( cas_home_gallery_image( $chosen ), 'medium' ) ) . '"'; } ?> <?php echo $chosen ? '' : 'hidden'; ?>>
			<strong data-current-title><?php echo esc_html( $chosen ? get_the_title( $chosen ) : 'No published artwork with a picture' ); ?></strong>
		</div>
		<p><button type="button" class="button" data-gallery-open <?php disabled( ! $artworks ); ?>>Choose Artwork</button></p>
		<p role="status" data-gallery-status></p>
		<dialog aria-labelledby="cas-gallery-title">
			<h2 id="cas-gallery-title">Choose the Home Gallery Image</h2>
			<p>Select one picture, then save. Cancel keeps the current choice.</p>
			<div class="cas-artwork-grid" role="radiogroup" aria-label="Published Artwork">
				<?php foreach ( $artworks as $id ) : ?>
				<label class="cas-artwork-option">
					<input type="radio" name="cas-gallery-choice" value="<?php echo esc_attr( (string) $id ); ?>" <?php checked( $chosen, $id ); ?>>
					<?php echo wp_get_attachment_image( cas_home_gallery_image( $id ), 'medium', false, array( 'alt' => '', 'loading' => 'lazy' ) ); ?>
					<span><?php echo esc_html( get_the_title( $id ) ?: 'Untitled Artwork' ); ?></span>
				</label>
				<?php endforeach; ?>
			</div>
			<p role="alert" data-gallery-error></p>
			<div class="cas-gallery-footer"><button type="button" class="button" data-gallery-cancel>Cancel</button><button type="button" class="button button-primary" data-gallery-save>Save Gallery Image</button></div>
		</dialog>
	</section>
	<?php
}
