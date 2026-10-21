<?php
/**
 * Private course files.
 *
 * An attachment that becomes course or lesson material (Tutor's `_tutor_attachments`) is
 * moved out of the public uploads folder, together with its generated image sizes, into a
 * private folder above the WordPress root. Its only address becomes the handler below,
 * which serves the file to enrolled students and administrators, and to nobody else.
 *
 * Existing public links to the file stop working once it is private; that is intended.
 * The private folder is CAS_PRIVATE_DIR when defined, else the folder beside the
 * WordPress root (outside the web root on the live host).
 */

defined( 'ABSPATH' ) || exit;

const CAS_PRIVATE_META_FLAG    = '_cas_private';         // attachment meta: 1 once moved.
const CAS_PRIVATE_META_COURSES = '_cas_private_courses'; // attachment meta: course IDs allowed to download.
const CAS_PRIVATE_META_ORIGIN  = '_cas_private_origin';  // attachment meta: original path, for rollback.
const CAS_PRIVATE_QUERY_VAR    = 'cas_private_file';

/** The private folder, without a trailing slash. Created, and kept at mode 0700, when it is missing. */
function cas_private_dir() {
	$dir = defined( 'CAS_PRIVATE_DIR' ) ? CAS_PRIVATE_DIR : dirname( untrailingslashit( ABSPATH ) ) . '/cas-private';
	return cas_private_ensure_dir( untrailingslashit( $dir ) );
}

/** Create a folder with mode 0700 if needed, and correct its mode if something else set it. */
function cas_private_ensure_dir( $path ) {
	if ( ! is_dir( $path ) ) {
		mkdir( $path, 0700, true );
	}
	if ( is_dir( $path ) && ! is_link( $path ) ) {
		chmod( $path, 0700 );
	}
	return $path;
}

/** One audit line per serve, denial or miss: the attachment id and the outcome, nothing else. */
function cas_private_log( $attachment_id, $outcome ) {
	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( 'private_file', 'attachment=' . (int) $attachment_id . ' outcome=' . $outcome );
	}
}

/** True once an attachment has been moved out of the public uploads folder. */
function cas_private_is( $attachment_id ) {
	return (bool) get_post_meta( (int) $attachment_id, CAS_PRIVATE_META_FLAG, true );
}

/** The only public address of a private attachment; `$size` names a generated image size. */
function cas_private_url( $attachment_id, $size = '' ) {
	$args = array( CAS_PRIVATE_QUERY_VAR => (int) $attachment_id );
	if ( '' !== $size ) {
		$args['size'] = $size;
	}
	return add_query_arg( $args, home_url( '/' ) );
}

/** The course IDs a course or lesson belongs to. Fails closed: no course, no access except for administrators. */
function cas_private_course_ids_for( $post_id ) {
	$post = get_post( (int) $post_id );
	if ( ! $post ) {
		return array();
	}
	if ( 'courses' === $post->post_type ) {
		return array( (int) $post->ID );
	}
	if ( function_exists( 'tutor_utils' ) ) {
		$course_id = (int) tutor_utils()->get_course_id_by_subcontent( $post->ID );
		return $course_id ? array( $course_id ) : array();
	}
	return array();
}

/*
 * Hook: whenever Tutor writes an attachment list, secure every attachment in it.
 * Tutor stores course and lesson attachments under the same key.
 */
add_action( 'added_post_meta', 'cas_private_on_attachment_meta', 10, 4 );
add_action( 'updated_post_meta', 'cas_private_on_attachment_meta', 10, 4 );

function cas_private_on_attachment_meta( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( '_tutor_attachments' !== $meta_key ) {
		return;
	}
	$ids = maybe_unserialize( $meta_value );
	if ( ! is_array( $ids ) ) {
		return;
	}
	$course_ids = cas_private_course_ids_for( $post_id );
	foreach ( $ids as $id ) {
		cas_private_secure_attachment( (int) $id, $course_ids );
	}
}

/**
 * One-time sweep for attachments that were referenced before this module existed.
 * Idempotent, like the hook. Run with: wp eval 'cas_private_sweep();'
 * Returns the number of posts that were checked.
 */
function cas_private_sweep() {
	$posts = get_posts(
		array(
			'post_type'   => array( 'courses', 'lesson' ),
			'post_status' => 'any',
			'numberposts' => -1,
			'meta_key'    => '_tutor_attachments', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'      => 'ids',
		)
	);
	foreach ( $posts as $post_id ) {
		$ids = maybe_unserialize( get_post_meta( $post_id, '_tutor_attachments', true ) );
		if ( ! is_array( $ids ) ) {
			continue;
		}
		$course_ids = cas_private_course_ids_for( $post_id );
		foreach ( $ids as $id ) {
			cas_private_secure_attachment( (int) $id, $course_ids );
		}
	}
	return count( $posts );
}

/**
 * Make one attachment private. Idempotent: the allow-list only grows, and an attachment that is
 * already private is not moved again.
 */
function cas_private_secure_attachment( $attachment_id, $course_ids ) {
	if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
		return false;
	}

	$existing = array_map( 'intval', (array) get_post_meta( $attachment_id, CAS_PRIVATE_META_COURSES, true ) );
	$allowed  = array_values( array_unique( array_filter( array_merge( $existing, array_map( 'intval', $course_ids ) ) ) ) );
	if ( $allowed !== $existing ) {
		update_post_meta( $attachment_id, CAS_PRIVATE_META_COURSES, $allowed );
	}

	if ( cas_private_is( $attachment_id ) ) {
		return true;
	}
	return cas_private_move_attachment( $attachment_id );
}

/**
 * Move the original and every generated size into the private folder, verify each copy by
 * SHA-256 before removing the public one, then point the attachment at the private files.
 */
function cas_private_move_attachment( $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! is_file( $file ) ) {
		return false;
	}

	$target_dir = cas_private_dir() . '/' . (int) $attachment_id;
	cas_private_ensure_dir( $target_dir );
	if ( ! is_dir( $target_dir ) ) {
		return false;
	}

	$moves = array();
	$moved = cas_private_move_file( $file, $target_dir . '/' . basename( $file ) );
	if ( ! $moved ) {
		return false;
	}
	$moves[] = $moved;

	$meta  = wp_get_attachment_metadata( $attachment_id );
	$sizes = isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ? $meta['sizes'] : array();
	$dir   = dirname( $file );
	foreach ( $sizes as $name => $size ) {
		if ( empty( $size['file'] ) ) {
			continue;
		}
		$src = $dir . '/' . $size['file'];
		if ( is_file( $src ) ) {
			$done = cas_private_move_file( $src, $target_dir . '/' . $size['file'] );
			if ( ! $done ) {
				return false; // Stop with the original still public; the next run retries.
			}
			$moves[] = $done;
		}
	}

	update_post_meta( $attachment_id, CAS_PRIVATE_META_ORIGIN, $file );
	update_attached_file( $attachment_id, $target_dir . '/' . basename( $file ) );
	update_post_meta( $attachment_id, CAS_PRIVATE_META_FLAG, 1 );
	return true;
}

/** Copy, verify by SHA-256, then remove the public copy. Returns the new path, or false. */
function cas_private_move_file( $src, $dst ) {
	if ( ! copy( $src, $dst ) ) {
		return false;
	}
	if ( hash_file( 'sha256', $src ) !== hash_file( 'sha256', $dst ) ) {
		wp_delete_file( $dst );
		return false;
	}
	chmod( $dst, 0600 );
	wp_delete_file( $src );
	return $dst;
}

/*
 * Deleting a private attachment deletes its folder. WordPress removes files only under the
 * uploads folder, so the private copies would otherwise stay behind.
 *
 * Only a flagged attachment is touched, and only the folder named after its id, found by
 * realpath under the private root. Symbolic links are never followed: a link inside the folder
 * is removed as a link, and its target is left alone. Subfolders are left in place.
 */
add_action( 'delete_attachment', 'cas_private_on_delete', 10, 2 );

function cas_private_on_delete( $post_id, $post = null ) {
	$id = (int) $post_id;
	if ( $id <= 0 || ! cas_private_is( $id ) ) {
		return;
	}
	$root = realpath( cas_private_dir() );
	$path = $root ? $root . '/' . $id : '';
	if ( '' === $path || ! is_dir( $path ) || is_link( $path ) || realpath( $path ) !== $path ) {
		return;
	}
	foreach ( scandir( $path ) as $name ) {
		if ( '.' === $name || '..' === $name ) {
			continue;
		}
		$item = $path . '/' . $name;
		if ( is_link( $item ) ) {
			unlink( $item ); // The link only; never its target.
		} elseif ( is_file( $item ) && realpath( $item ) === $item ) {
			unlink( $item );
		}
	}
	if ( 0 === count( array_diff( scandir( $path ), array( '.', '..' ) ) ) ) {
		rmdir( $path );
	}
}

/*
 * Make WordPress hand out the handler address for private attachments, so the templates
 * (Tutor's attachment list, image blocks) need no edits.
 */
add_filter(
	'wp_get_attachment_url',
	function ( $url, $attachment_id ) {
		return cas_private_is( $attachment_id ) ? cas_private_url( $attachment_id ) : $url;
	},
	10,
	2
);

add_filter(
	'wp_get_attachment_image_src',
	function ( $image, $attachment_id, $size ) {
		if ( ! $image || ! cas_private_is( $attachment_id ) ) {
			return $image;
		}
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$name  = ( is_string( $size ) && isset( $meta['sizes'][ $size ] ) ) ? $size : '';
		$image[0] = cas_private_url( $attachment_id, $name );
		return $image;
	},
	10,
	3
);

/* No srcset: it would list the public uploads addresses of the sizes. The plain src above is used. */
add_filter(
	'wp_calculate_image_srcset',
	function ( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		return cas_private_is( $attachment_id ) ? array() : $sources;
	},
	10,
	5
);

/** Who may download a private attachment: logged in and enrolled in one of its courses, or an administrator. */
function cas_private_can_download( $attachment_id ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( ! class_exists( '\Tutor\Models\EnrollmentModel' ) ) {
		return false;
	}
	foreach ( array_map( 'intval', (array) get_post_meta( $attachment_id, CAS_PRIVATE_META_COURSES, true ) ) as $course_id ) {
		if ( $course_id && \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, get_current_user_id() ) ) {
			return true;
		}
	}
	return false;
}

/** The file to send for an attachment or one of its image sizes, or '' when there is none. */
function cas_private_path_for( $attachment_id, $size ) {
	$origin = get_attached_file( $attachment_id );
	if ( '' === $size ) {
		return $origin;
	}
	$meta = wp_get_attachment_metadata( $attachment_id );
	if ( empty( $meta['sizes'][ $size ]['file'] ) ) {
		return '';
	}
	return dirname( $origin ) . '/' . $meta['sizes'][ $size ]['file'];
}

/** The handler. Every denied or missing request answers with a plain status and no body. */
add_action(
	'template_redirect',
	function () {
		if ( ! isset( $_GET[ CAS_PRIVATE_QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$id   = absint( wp_unslash( $_GET[ CAS_PRIVATE_QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$size = isset( $_GET['size'] ) ? sanitize_key( wp_unslash( $_GET['size'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $id || 'attachment' !== get_post_type( $id ) || ! cas_private_is( $id ) ) {
			cas_private_log( $id, 'not_found' );
			status_header( 404 );
			nocache_headers();
			exit;
		}
		if ( ! cas_private_can_download( $id ) ) {
			cas_private_log( $id, 'denied' );
			status_header( 403 );
			nocache_headers();
			exit;
		}
		$path = cas_private_path_for( $id, $size );
		if ( '' === $path || ! is_file( $path ) ) {
			cas_private_log( $id, 'not_found' );
			status_header( 404 );
			nocache_headers();
			exit;
		}
		cas_private_log( $id, 'served' );

		$mime = get_post_mime_type( $id );
		if ( '' !== $size ) {
			$meta = wp_get_attachment_metadata( $id );
			$mime = isset( $meta['sizes'][ $size ]['mime-type'] ) ? $meta['sizes'][ $size ]['mime-type'] : $mime;
		}
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // Plain readfile: course files are PDFs and images, a few megabytes at most.
		exit;
	},
	0
);
