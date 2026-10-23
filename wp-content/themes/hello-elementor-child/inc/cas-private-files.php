<?php
/**
 * Private course files.
 *
 * An attachment that becomes course or lesson material (Tutor's `_tutor_attachments`) is
 * moved out of the public uploads folder, together with its generated image sizes, into a
 * private folder above the WordPress root. Its only address becomes the handler below,
 * which serves the file to enrolled students, administrators, and a narrowly verified current
 * cas_content course author with a protected, current course/lesson assignment.
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
const CAS_PRIVATE_META_REFS    = '_cas_private_author_refs'; // protected source/course/list binding, never a growing grant.

/** Actual limited role plus its explicit capability, not instructor or attachment ownership. */
function cas_private_is_content_user() {
	return function_exists( 'cas_content_is_user' ) && cas_content_is_user( get_current_user_id() );
}

function cas_private_is_content_author() {
	return cas_private_is_content_user() && current_user_can( 'manage_cas_content' );
}

/** Read the current graph from the database; Tutor's cached instructor links are not authority. */
function cas_private_author_course( $post_id ) {
	global $wpdb;
	$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_parent,post_author,post_status FROM {$wpdb->posts} WHERE ID=%d", $post_id ) );
	if ( ! $post || 'trash' === $post->post_status ) { return false; }
	if ( 'lesson' === $post->post_type ) {
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_parent,post_status FROM {$wpdb->posts} WHERE ID=%d", $post->post_parent ) );
		if ( ! $post || 'topics' !== $post->post_type || 'trash' === $post->post_status ) { return false; }
	}
	if ( 'topics' === $post->post_type ) {
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_parent,post_author,post_status FROM {$wpdb->posts} WHERE ID=%d", $post->post_parent ) );
	}
	return $post && 'courses' === $post->post_type && 'trash' !== $post->post_status ? $post : false;
}

function cas_private_author_owns( $course ) {
	return $course && cas_private_is_content_author() && (int) $course->post_author === get_current_user_id() && current_user_can( 'edit_post', (int) $course->ID );
}

/** Canonical list, or false for malformed input. Do not deserialize request-provided strings. */
function cas_private_reference_ids( $value ) {
	if ( ! is_array( $value ) ) { return false; }
	$ids = array();
	foreach ( $value as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 || 'attachment' !== get_post_type( (int) $id ) ) { return false; }
		$ids[] = (int) $id;
	}
	$ids = array_values( array_unique( $ids ) );
	sort( $ids, SORT_NUMERIC );
	return $ids;
}

/** WordPress stores these arrays serialized; never instantiate classes while inspecting provenance. */
function cas_private_reference_decode( $raw ) {
	return is_serialized( $raw ) ? @unserialize( $raw, array( 'allowed_classes' => false ) ) : $raw;
}

/** A current reference must also have been validated before assignment, not self-authorised afterwards. */
function cas_private_author_can_download( $attachment_id ) {
	if ( ! cas_private_is_content_author() ) { return false; }
	global $wpdb;
	$rows = (array) $wpdb->get_results( $wpdb->prepare(
		"SELECT r.post_id,r.meta_value,b.meta_value AS binding FROM {$wpdb->postmeta} r INNER JOIN {$wpdb->posts} p ON p.ID=r.post_id AND p.post_type IN ('courses','lesson') INNER JOIN {$wpdb->postmeta} b ON b.post_id=r.post_id AND b.meta_key=%s WHERE r.meta_key='_tutor_attachments'",
		CAS_PRIVATE_META_REFS
	) );
	$counts = array_count_values( array_map( static function ( $row ) { return (int) $row->post_id; }, $rows ) );
	foreach ( $rows as $row ) {
		if ( 1 !== $counts[ (int) $row->post_id ] ) { continue; }
		$ids = cas_private_reference_ids( cas_private_reference_decode( $row->meta_value ) );
		if ( false === $ids || ! in_array( (int) $attachment_id, $ids, true ) ) { continue; }
		$course = cas_private_author_course( $row->post_id );
		$binding = cas_private_reference_decode( $row->binding );
		if ( cas_private_author_owns( $course ) && $binding === array( 'source' => (int) $row->post_id, 'course' => (int) $course->ID, 'ids' => $ids ) ) {
			return true;
		}
	}
	return false;
}

/** Only this module opens the security-metadata write window, always restored on failure. */
function cas_private_internal_write( $callback = null ) {
	static $depth = 0;
	if ( null === $callback ) { return $depth > 0; }
	++$depth;
	try { return $callback(); } finally { --$depth; }
}

/** Preflight evidence is request-local; an arbitrary post-meta callback cannot mint a trusted binding. */
function cas_private_approved_assignment( $post_id, $ids = null ) {
	static $approved = array();
	if ( null !== $ids ) { $approved[ (int) $post_id ] = $ids; }
	return $approved[ (int) $post_id ] ?? null;
}

/** Stamp only an operator-reviewed list or this request's preflight-approved own-course assignment. */
function cas_private_record_assignment( $post_id, $ids ) {
	$course = cas_private_author_course( $post_id );
	if ( ! in_array( get_post_type( $post_id ), array( 'courses', 'lesson' ), true ) || ! $course || ( ! current_user_can( 'manage_options' ) && ! ( defined( 'WP_CLI' ) && WP_CLI )
		&& ( ! cas_private_author_owns( $course ) || cas_private_approved_assignment( $post_id ) !== $ids ) ) ) { return false; }
	global $wpdb;
	$rows = (array) $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_tutor_attachments'", $post_id ) );
	if ( 1 !== count( $rows ) || cas_private_reference_ids( cas_private_reference_decode( $rows[0] ) ) !== $ids ) { return false; }
	if ( array() === $ids ) {
		cas_private_internal_write( static function () use ( $post_id ) { delete_post_meta( $post_id, CAS_PRIVATE_META_REFS ); } );
		return true;
	}
	$binding = array( 'source' => (int) $post_id, 'course' => (int) $course->ID, 'ids' => $ids );
	cas_private_internal_write( static function () use ( $post_id, $binding ) {
		update_post_meta( $post_id, CAS_PRIVATE_META_REFS, $binding );
	} );
	return get_post_meta( $post_id, CAS_PRIVATE_META_REFS, true ) === $binding;
}

/** Exact legacy-reference preimage for an operator-reviewed manifest; never exposed as a web endpoint. */
function cas_private_reference_snapshot( $source_id ) {
	global $wpdb;
	$chain = array();
	$id = (int) $source_id;
	foreach ( array( 'source', 'parent', 'course' ) as $step ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_parent,post_author,post_status FROM {$wpdb->posts} WHERE ID=%d", $id ), ARRAY_A );
		if ( ! $row || 'trash' === $row['post_status'] ) { return false; }
		$chain[] = $row;
		if ( ( 1 === count( $chain ) && ! in_array( $row['post_type'], array( 'courses', 'lesson' ), true ) )
			|| ( 2 === count( $chain ) && 'topics' !== $row['post_type'] )
			|| ( 3 === count( $chain ) && 'courses' !== $row['post_type'] ) ) { return false; }
		if ( 'courses' === $row['post_type'] ) { break; }
		$id = (int) $row['post_parent'];
	}
	$course = end( $chain );
	if ( 'courses' !== $course['post_type'] ) { return false; }
	$refs = (array) $wpdb->get_results( $wpdb->prepare( "SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_tutor_attachments' ORDER BY meta_id", $source_id ), ARRAY_A );
	$ids = 1 === count( $refs ) ? cas_private_reference_ids( cas_private_reference_decode( $refs[0]['meta_value'] ) ) : false;
	if ( false === $ids ) { return false; }
	$files = array();
	foreach ( $ids as $attachment_id ) {
		wp_cache_delete( $attachment_id, 'post_meta' );
		if ( ! cas_private_is( $attachment_id ) ) { return false; }
		$path = get_attached_file( $attachment_id );
		$meta = wp_get_attachment_metadata( $attachment_id );
		$paths = array( $path );
		foreach ( (array) ( $meta['sizes'] ?? array() ) as $size ) {
			$file = is_array( $size ) ? ( $size['file'] ?? '' ) : '';
			if ( ! is_string( $file ) || '' === $file || basename( $file ) !== $file || false !== strpos( $file, '\\' ) || false !== strpos( $file, chr( 0 ) ) ) { return false; }
			$paths[] = dirname( $path ) . '/' . $file;
		}
		$hashes = array();
		foreach ( $paths as $file ) {
			$real = realpath( $file );
			if ( ! $real || ! is_file( $real ) ) { return false; }
			$digest = hash_file( 'sha256', $real );
			if ( false === $digest ) { return false; }
			$hashes[ $real ] = $digest;
		}
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID,post_type,post_parent,post_author,post_status FROM {$wpdb->posts} WHERE ID=%d", $attachment_id ), ARRAY_A );
		if ( ! $post || 'attachment' !== $post['post_type'] || 'trash' === $post['post_status'] ) { return false; }
		$files[ $attachment_id ] = array( 'post' => $post, 'path' => $path, 'hashes' => $hashes, 'meta' => $wpdb->get_results( $wpdb->prepare( "SELECT meta_id,meta_key,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d ORDER BY meta_id", $attachment_id ), ARRAY_A ) );
	}
	return array( 'source' => (int) $source_id, 'chain' => $chain, 'list' => $refs, 'ids' => $ids, 'files' => $files,
		'binding' => $wpdb->get_results( $wpdb->prepare( "SELECT meta_id,meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id", $source_id, CAS_PRIVATE_META_REFS ), ARRAY_A ) );
}

/** One reviewed legacy source only: compare all preimages, stamp/read back, or remove exactly our stamp. */
function cas_private_apply_reference_manifest( $expected, $rollback = false ) {
	if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) { return false; }
	if ( ! is_array( $expected ) || empty( $expected['source'] ) || empty( $expected['ids'] ) || ! isset( $expected['binding'] ) || array() !== $expected['binding'] ) { return false; }
	$current = cas_private_reference_snapshot( $expected['source'] );
	if ( ! $current ) { return false; }
	$actual_binding = $current['binding'];
	$current['binding'] = array();
	if ( $current !== $expected ) { return false; }
	$course = end( $current['chain'] );
	$binding = array( 'source' => $current['source'], 'course' => (int) $course['ID'], 'ids' => $current['ids'] );
	$ours = 1 === count( $actual_binding ) && cas_private_reference_decode( $actual_binding[0]['meta_value'] ) === $binding;
	if ( $rollback ) {
		if ( array() === $actual_binding ) { return true; }
		if ( ! $ours ) { return false; }
		delete_post_meta( $current['source'], CAS_PRIVATE_META_REFS, $binding );
		wp_cache_delete( $current['source'], 'post_meta' );
		return ! metadata_exists( 'post', $current['source'], CAS_PRIVATE_META_REFS );
	}
	if ( $ours ) { return true; }
	if ( array() !== $actual_binding ) { return false; }
	add_post_meta( $current['source'], CAS_PRIVATE_META_REFS, $binding, true );
	$after = cas_private_reference_snapshot( $current['source'] );
	$stored = $after ? $after['binding'] : array();
	if ( $after ) { $after['binding'] = array(); }
	if ( $after !== $expected || 1 !== count( $stored ) || cas_private_reference_decode( $stored[0]['meta_value'] ) !== $binding ) {
		delete_post_meta( $current['source'], CAS_PRIVATE_META_REFS, $binding );
		return false;
	}
	return true;
}

/** Core whole-object deletion may remove that object's metadata, not edit another object's permissions. */
function cas_private_deleting_post( $post_id, $record = false ) {
	static $deleting = array();
	if ( $record ) { $deleting[ (int) $post_id ] = true; }
	return isset( $deleting[ (int) $post_id ] );
}
foreach ( array( 'before_delete_post', 'delete_attachment' ) as $hook ) {
	add_action( $hook, static function ( $post_id ) { cas_private_deleting_post( $post_id, true ); }, 0 );
}

/** Stop guessed private IDs and security-meta/path rewrites before WordPress mutates anything. */
function cas_private_guard_metadata( $check, $post_id, $key, $value ) {
	if ( cas_private_internal_write() || current_user_can( 'manage_options' ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) { return $check; }
	$deleting = in_array( current_filter(), array( 'delete_post_metadata', 'delete_post_metadata_by_mid' ), true );
	if ( $deleting && cas_private_deleting_post( $post_id ) ) { return $check; }
	if ( in_array( $key, array( CAS_PRIVATE_META_REFS, CAS_PRIVATE_META_COURSES, CAS_PRIVATE_META_FLAG, CAS_PRIVATE_META_ORIGIN ), true ) ) {
		wp_die( 'Private file permissions can only be changed by the authorised file workflow.', '', array( 'response' => 403 ) );
	}
	if ( '_tutor_attachments' === $key ) {
		$course = cas_private_author_course( $post_id );
		if ( 'add_post_metadata' === current_filter() && metadata_exists( 'post', $post_id, '_tutor_attachments' ) ) {
			wp_die( 'Update the existing material list instead of adding a duplicate.', '', array( 'response' => 403 ) );
		}
		$ids = $deleting ? array() : cas_private_reference_ids( $value );
		if ( ! in_array( get_post_type( $post_id ), array( 'courses', 'lesson' ), true ) || ! cas_private_author_owns( $course ) || false === $ids ) {
			wp_die( 'You may only assign files to your own real course or lesson.', '', array( 'response' => 403 ) );
		}
		foreach ( $ids as $id ) {
			if ( cas_private_is( $id ) ? ! cas_private_author_can_download( $id ) : ! current_user_can( 'edit_post', $id ) ) {
				wp_die( 'That file is not authorised for your course. Upload your own file or contact Herman.', '', array( 'response' => 403 ) );
			}
		}
		cas_private_approved_assignment( $post_id, $ids );
	}
	if ( in_array( $key, array( '_wp_attached_file', '_wp_attachment_metadata' ), true ) && cas_private_is( $post_id ) ) {
		wp_die( 'Private file storage cannot be changed here.', '', array( 'response' => 403 ) );
	}
	if ( '_wp_attached_file' === $key && ! $deleting ) {
		$uploads = wp_upload_dir();
		$root = realpath( $uploads['basedir'] );
		$path = is_string( $value ) ? realpath( path_is_absolute( $value ) ? $value : $uploads['basedir'] . '/' . $value ) : false;
		if ( ! $root || ! $path || 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
			wp_die( 'Only an actual public upload may be attached.', '', array( 'response' => 403 ) );
		}
	}
	if ( '_wp_attachment_metadata' === $key && is_array( $value ) ) {
		foreach ( (array) ( $value['sizes'] ?? array() ) as $size ) {
			$file = is_array( $size ) ? ( $size['file'] ?? '' ) : '';
			if ( ! is_string( $file ) || '' === $file || basename( $file ) !== $file || false !== strpos( $file, '\\' ) || false !== strpos( $file, chr( 0 ) ) ) {
				wp_die( 'Image size filenames must stay inside their upload folder.', '', array( 'response' => 403 ) );
			}
		}
	}
	return $check;
}
add_filter( 'add_post_metadata', 'cas_private_guard_metadata', 0, 4 );
add_filter( 'update_post_metadata', 'cas_private_guard_metadata', 0, 4 );
add_filter( 'delete_post_metadata', 'cas_private_guard_metadata', 0, 4 );

/** Core custom-field helpers also write/delete by meta ID, including key renames. */
function cas_private_guard_metadata_by_mid( $check, $meta_id, $value = null, $key = false ) {
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT post_id,meta_key FROM {$wpdb->postmeta} WHERE meta_id=%d", $meta_id ) );
	if ( ! $row ) { return $check; }
	$check = cas_private_guard_metadata( $check, (int) $row->post_id, $row->meta_key, $value );
	return $key && $key !== $row->meta_key ? cas_private_guard_metadata( $check, (int) $row->post_id, $key, $value ) : $check;
}
add_filter( 'update_post_metadata_by_mid', 'cas_private_guard_metadata_by_mid', 0, 4 );
add_filter( 'delete_post_metadata_by_mid', 'cas_private_guard_metadata_by_mid', 0, 2 );

/** Content authors cannot take over courses or graft another course's material-bearing tree. */
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( ! cas_private_is_content_user() || ! in_array( $data['post_type'], array( 'courses', 'topics', 'lesson' ), true ) ) { return $data; }
	$id = (int) ( $postarr['ID'] ?? 0 );
	$old = $id ? cas_private_author_course( $id ) : false;
	$course = 'courses' === $data['post_type'] ? $old : cas_private_author_course( (int) $data['post_parent'] );
	if ( ! cas_private_is_content_author() || ( 'lesson' === $data['post_type'] && 'topics' !== get_post_type( (int) $data['post_parent'] ) )
		|| ( 'topics' === $data['post_type'] && 'courses' !== get_post_type( (int) $data['post_parent'] ) )
		|| ( $id && ! cas_private_author_owns( $old ) )
		|| ( 'courses' === $data['post_type'] && (int) $data['post_author'] !== get_current_user_id() )
		|| ( 'courses' !== $data['post_type'] && ( ! cas_private_author_owns( $course ) || ( $old && (int) $old->ID !== (int) $course->ID ) ) ) ) {
		wp_die( 'Course ownership and cross-course material moves require Herman.', '', array( 'response' => 403 ) );
	}
	return $data;
}, 0, 2 );

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
	$values = cas_private_reference_decode( $meta_value );
	if ( ! is_array( $values ) ) {
		return;
	}
	$course_ids = cas_private_course_ids_for( $post_id );
	foreach ( $values as $id ) {
		if ( is_scalar( $id ) && ctype_digit( (string) $id ) ) {
			cas_private_secure_attachment( (int) $id, $course_ids );
		}
	}
	$ids = cas_private_reference_ids( $values );
	if ( false !== $ids ) { cas_private_record_assignment( $post_id, $ids ); }
}
add_action( 'deleted_post_meta', function ( $meta_ids, $post_id, $key ) {
	if ( '_tutor_attachments' === $key ) {
		cas_private_internal_write( static function () use ( $post_id ) { delete_post_meta( $post_id, CAS_PRIVATE_META_REFS ); } );
	}
}, 10, 3 );

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
		cas_private_on_attachment_meta( 0, $post_id, '_tutor_attachments', get_post_meta( $post_id, '_tutor_attachments', true ) );
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

	return cas_private_internal_write( static function () use ( $attachment_id, $course_ids ) {
		$existing = array_map( 'intval', (array) get_post_meta( $attachment_id, CAS_PRIVATE_META_COURSES, true ) );
		$allowed  = array_values( array_unique( array_filter( array_merge( $existing, array_map( 'intval', $course_ids ) ) ) ) );
		if ( $allowed !== $existing ) {
			update_post_meta( $attachment_id, CAS_PRIVATE_META_COURSES, $allowed );
		}

		if ( cas_private_is( $attachment_id ) ) {
			return true;
		}
		return cas_private_move_attachment( $attachment_id );
	} );
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

/** Admin/enrolled access is unchanged; current limited authors additionally need protected current references. */
function cas_private_can_download( $attachment_id ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}
	if ( cas_private_author_can_download( $attachment_id ) ) {
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
		if ( '' === $size ) { // Only the original is logged: one page view must not write a line per image size.
			cas_private_log( $id, 'served' );
		}

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
