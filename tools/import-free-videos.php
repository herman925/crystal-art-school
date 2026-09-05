<?php
/**
 * Import Crystal's free films.
 *
 * Idempotent: a film is matched on its post slug and updated rather than duplicated.
 * Only her own material is written — the title and the link are transcribed from her
 * own document and the cover is her own YouTube thumbnail. No description is set,
 * because she supplied none and a blurb written here would be invented.
 */
$manifest = json_decode( file_get_contents( getenv( 'HOME' ) . '/cas-import/free-videos-import.json' ), true );
$dir      = getenv( 'HOME' ) . '/cas-import/free-posters/';

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

foreach ( $manifest as $row ) {
	$existing = get_posts(
		array(
			'post_type'   => 'cas_free_video',
			'name'        => $row['slug'],
			'post_status' => 'any',
			'numberposts' => 1,
		)
	);

	$args = array(
		'post_type'    => 'cas_free_video',
		'post_status'  => 'publish',
		'post_title'   => $row['title'],
		'post_name'    => $row['slug'],
		'menu_order'   => $row['order'],
		'post_content' => '',
	);

	if ( $existing ) {
		$args['ID'] = $existing[0]->ID;
		$id         = wp_update_post( $args, true );
		$action     = 'updated';
	} else {
		$id     = wp_insert_post( $args, true );
		$action = 'created';
	}

	if ( is_wp_error( $id ) ) {
		echo "FAIL {$row['slug']}: " . $id->get_error_message() . "\n";
		continue;
	}

	update_post_meta( $id, '_cas_free_video_url', $row['watch'] );
	update_post_meta( $id, '_cas_free_video_section', $row['section'] );

	if ( ! get_post_thumbnail_id( $id ) ) {
		$tmp  = $dir . $row['poster'];
		$file = array(
			'name'     => $row['poster'],
			'tmp_name' => $tmp,
		);
		// media_handle_sideload moves the file, so hand it a copy.
		$copy = wp_tempnam( $row['poster'] );
		copy( $tmp, $copy );
		$file['tmp_name'] = $copy;

		$att = media_handle_sideload( $file, $id, $row['title'] );

		if ( is_wp_error( $att ) ) {
			echo "  poster FAIL {$row['slug']}: " . $att->get_error_message() . "\n";
		} else {
			set_post_thumbnail( $id, $att );
		}
	}

	printf( "%-8s %-3d %-8s %-26s id=%d thumb=%d\n", $action, $row['order'], $row['section'], $row['title'], $id, (int) get_post_thumbnail_id( $id ) );
}

printf( "\npublished free films: %d\n", count( get_posts( array( 'post_type' => 'cas_free_video', 'post_status' => 'publish', 'numberposts' => -1 ) ) ) );
