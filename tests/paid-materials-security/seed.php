<?php
/**
 * Seed or clean one synthetic paid-materials access test.
 *
 * This file does nothing outside an explicitly opted-in WP-CLI local/staging runtime.
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "Run this seeder only through WP-CLI.\n" );
	exit( 1 );
}

function cas_paid_materials_test_fail( $message ) {
	throw new RuntimeException( $message );
}

function cas_paid_materials_test_inside( $path, $parent ) {
	$path   = trailingslashit( wp_normalize_path( $path ) );
	$parent = trailingslashit( wp_normalize_path( $parent ) );

	if ( '\\' === DIRECTORY_SEPARATOR ) {
		$path   = strtolower( $path );
		$parent = strtolower( $parent );
	}

	return 0 === strpos( $path, $parent );
}

function cas_paid_materials_test_is_absolute_path( $path ) {
	$path = wp_normalize_path( $path );

	return isset( $path[0] ) && ( '/' === $path[0] || preg_match( '/^[A-Za-z]:\//', $path ) );
}

function cas_paid_materials_test_is_loopback_url( $url ) {
	$parts = wp_parse_url( $url );

	return is_array( $parts )
		&& 'http' === ( $parts['scheme'] ?? '' )
		&& in_array( strtolower( $parts['host'] ?? '' ), array( 'localhost', '127.0.0.1', '::1' ), true )
		&& empty( $parts['user'] )
		&& empty( $parts['pass'] )
		&& empty( $parts['query'] )
		&& empty( $parts['fragment'] );
}

function cas_paid_materials_test_check_runtime() {
	if ( '1' !== getenv( 'CAS_PAID_TEST_ISOLATED' ) || '1' !== getenv( 'CAS_PAID_TEST_LOCAL_OPT_IN' ) ) {
		cas_paid_materials_test_fail( 'Explicit isolated-runtime opt-in is required.' );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		cas_paid_materials_test_fail( 'Run WP-CLI as a staging administrator.' );
	}

	if ( ! function_exists( 'wp_get_environment_type' ) || ! in_array( wp_get_environment_type(), array( 'local', 'staging' ), true ) ) {
		cas_paid_materials_test_fail( 'WordPress environment must be marked local or staging.' );
	}

	if ( ! defined( 'TUTOR_VERSION' ) || '4.1.0' !== TUTOR_VERSION ) {
		cas_paid_materials_test_fail( 'This fixture requires Tutor LMS 4.1.0.' );
	}

	if ( ! class_exists( '\\Tutor\\Models\\CourseModel' )
		|| ! class_exists( '\\Tutor\\Models\\EnrollmentModel' )
		|| ! class_exists( '\\TUTOR\\Course_List' )
		|| ! post_type_exists( 'courses' )
		|| ! post_type_exists( 'topics' )
		|| ! post_type_exists( 'lesson' )
		|| ! post_type_exists( 'tutor_enrolled' ) ) {
		cas_paid_materials_test_fail( 'Expected Tutor models or post types are unavailable.' );
	}

	if ( ! defined( 'DB_NAME' ) || ! defined( 'DB_HOST' ) ) {
		cas_paid_materials_test_fail( 'Database identity is unavailable.' );
	}

	foreach ( array( DB_NAME, DB_HOST ) as $database_identity ) {
		if ( preg_match( '/crystalau|(?:^|[^a-z0-9])(?:prod|production|live)(?:$|[^a-z0-9])/i', (string) $database_identity ) ) {
			cas_paid_materials_test_fail( 'Production database indicator refused.' );
		}
	}

	$home    = home_url( '/' );
	$siteurl = site_url( '/' );
	if ( ! cas_paid_materials_test_is_loopback_url( $home ) || ! cas_paid_materials_test_is_loopback_url( $siteurl ) ) {
		cas_paid_materials_test_fail( 'Home and Site URL must use loopback HTTP.' );
	}

	$home_parts = wp_parse_url( $home );
	$site_parts = wp_parse_url( $siteurl );
	if ( strtolower( $home_parts['host'] ?? '' ) !== strtolower( $site_parts['host'] ?? '' )
		|| (int) ( $home_parts['port'] ?? 80 ) !== (int) ( $site_parts['port'] ?? 80 ) ) {
		cas_paid_materials_test_fail( 'Home and Site URL must use the same loopback origin.' );
	}

	$repo_raw = getenv( 'CAS_PAID_TEST_REPO_ROOT' );
	$repo_root = is_string( $repo_raw ) && '' !== trim( $repo_raw ) ? realpath( wp_normalize_path( trim( $repo_raw ) ) ) : false;
	if ( ! $repo_root ) {
		cas_paid_materials_test_fail( 'CAS_PAID_TEST_REPO_ROOT must name the mounted source root.' );
	}

	return array(
		'environment' => wp_get_environment_type(),
		'home'        => $home,
		'siteurl'     => $siteurl,
		'web_root'    => realpath( ABSPATH ),
		'repo_root'   => $repo_root,
		'database'    => array(
			'host' => (string) DB_HOST,
			'name' => (string) DB_NAME,
		),
	);
}

function cas_paid_materials_test_manifest_path( $for_cleanup, $repo_root ) {
	$raw = getenv( 'CAS_PAID_TEST_MANIFEST' );
	if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
		cas_paid_materials_test_fail( 'CAS_PAID_TEST_MANIFEST must point to a private external JSON file.' );
	}

	$path = wp_normalize_path( trim( $raw ) );
	if ( '.json' !== strtolower( substr( $path, -5 ) ) || ! cas_paid_materials_test_is_absolute_path( $path ) ) {
		cas_paid_materials_test_fail( 'Manifest path must be absolute and end in .json.' );
	}

	$parent = realpath( dirname( $path ) );
	$web = realpath( ABSPATH );
	$repo = realpath( $repo_root );
	if ( ! $parent || ! $web || ! $repo || ! is_writable( $parent ) ) {
		cas_paid_materials_test_fail( 'Manifest directory must exist and be writable.' );
	}

	if ( cas_paid_materials_test_inside( $parent, $web ) || cas_paid_materials_test_inside( $parent, $repo ) ) {
		cas_paid_materials_test_fail( 'Manifest must stay outside WordPress and the repository.' );
	}

	$resolved = trailingslashit( wp_normalize_path( $parent ) ) . basename( $path );
	if ( is_link( $resolved ) ) {
		cas_paid_materials_test_fail( 'Manifest symlinks are refused.' );
	}
	if ( $for_cleanup ? ! is_file( $resolved ) : file_exists( $resolved ) ) {
		cas_paid_materials_test_fail( $for_cleanup ? 'Manifest file does not exist.' : 'Manifest already exists; use a new path.' );
	}

	return $resolved;
}

function cas_paid_materials_test_save_manifest( $path, array $manifest, $create = false ) {
	$json = wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	if ( ! is_string( $json ) ) {
		cas_paid_materials_test_fail( 'Could not encode the private fixture manifest.' );
	}

	if ( $create ) {
		$handle = @fopen( $path, 'x' );
	} else {
		if ( ! is_file( $path ) || is_link( $path ) ) {
			cas_paid_materials_test_fail( 'Fixture manifest disappeared or changed type.' );
		}
		$permissions = fileperms( $path );
		if ( false === $permissions || 0 !== ( $permissions & 0077 ) ) {
			cas_paid_materials_test_fail( 'Fixture manifest permissions are too broad.' );
		}
		$handle = @fopen( $path, 'c+' );
	}

	if ( ! $handle ) {
		cas_paid_materials_test_fail( 'Could not open the private fixture manifest.' );
	}

	if ( ! chmod( $path, 0600 ) ) {
		fclose( $handle );
		if ( $create && is_file( $path ) ) {
			unlink( $path );
		}
		cas_paid_materials_test_fail( 'Could not restrict fixture manifest permissions.' );
	}
	$permissions = fileperms( $path );
	if ( false === $permissions || 0 !== ( $permissions & 0077 ) ) {
		fclose( $handle );
		cas_paid_materials_test_fail( 'Fixture manifest permissions are too broad.' );
	}
	if ( ! $create && ( ! ftruncate( $handle, 0 ) || ! rewind( $handle ) ) ) {
		fclose( $handle );
		cas_paid_materials_test_fail( 'Could not update the private fixture manifest.' );
	}

	$written = fwrite( $handle, $json . "\n" );
	fflush( $handle );
	fclose( $handle );
	if ( ! chmod( $path, 0600 ) ) {
		cas_paid_materials_test_fail( 'Fixture manifest permissions could not be verified.' );
	}
	$permissions = fileperms( $path );
	if ( false === $permissions || 0 !== ( $permissions & 0077 ) ) {
		cas_paid_materials_test_fail( 'Fixture manifest permissions could not be verified.' );
	}

	if ( false === $written || $written !== strlen( $json . "\n" ) ) {
		cas_paid_materials_test_fail( 'Fixture manifest write was incomplete.' );
	}
}

function cas_paid_materials_test_run_id( $run_id ) {
	if ( ! is_string( $run_id ) || ! preg_match( '/^cas-paid-materials-[a-f0-9]{12}$/', $run_id ) ) {
		cas_paid_materials_test_fail( 'Synthetic run ID is invalid.' );
	}
}

function cas_paid_materials_test_user_id( $login ) {
	global $wpdb;

	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE user_login = %s", $login ) );
}

function cas_paid_materials_test_user_login( $user_id ) {
	global $wpdb;

	return (string) $wpdb->get_var( $wpdb->prepare( "SELECT user_login FROM {$wpdb->users} WHERE ID = %d", $user_id ) );
}

function cas_paid_materials_test_create_user( $path, array &$manifest, $role, $wp_role ) {
	$login = 'caspaid_' . $manifest['run_id'] . '_' . $role;
	$email = $login . '@example.test';
	$pass  = wp_generate_password( 40, true, true );

	$manifest['users'][ $role ] = array(
		'id'       => 0,
		'login'    => $login,
		'email'    => $email,
		'password' => $pass,
		'role'     => $wp_role,
	);
	cas_paid_materials_test_save_manifest( $path, $manifest );

	if ( ! get_role( $wp_role ) ) {
		cas_paid_materials_test_fail( 'Expected WordPress role is unavailable.' );
	}

	$user_id = wp_insert_user(
		array(
			'user_login' => $login,
			'user_pass'  => $pass,
			'user_email' => $email,
			'role'       => $wp_role,
		)
	);
	if ( is_wp_error( $user_id ) || ! $user_id ) {
		cas_paid_materials_test_fail( 'Could not create a synthetic test user.' );
	}

	$manifest['users'][ $role ]['id'] = (int) $user_id;
	cas_paid_materials_test_save_manifest( $path, $manifest );

	return (int) $user_id;
}

function cas_paid_materials_test_pdf( $marker ) {
	$text = 'BT /F1 12 Tf 36 740 Td (' . $marker . ') Tj ET';
	$objects = array(
		'<< /Type /Catalog /Pages 2 0 R >>',
		'<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
		'<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
		'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
		'<< /Length ' . strlen( $text ) . " >>\nstream\n" . $text . "\nendstream",
	);

	$pdf     = "%PDF-1.4\n";
	$offsets = array( 0 );
	foreach ( $objects as $index => $object ) {
		$offsets[] = strlen( $pdf );
		$pdf      .= ( $index + 1 ) . " 0 obj\n" . $object . "\nendobj\n";
	}
	$xref = strlen( $pdf );
	$pdf .= "xref\n0 6\n0000000000 65535 f \n";
	for ( $i = 1; $i <= 5; $i++ ) {
		$pdf .= sprintf( '%010d 00000 n ', $offsets[ $i ] ) . "\n";
	}
	$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

	return $pdf;
}

function cas_paid_materials_test_png( $marker ) {
	if ( ! function_exists( 'gzcompress' ) ) {
		cas_paid_materials_test_fail( 'zlib is required to build the synthetic PNG.' );
	}

	$chunk = static function ( $type, $data ) {
		$payload = $type . $data;

		return pack( 'N', strlen( $data ) ) . $payload . pack( 'N', crc32( $payload ) );
	};

	return "\x89PNG\r\n\x1a\n"
		. $chunk( 'IHDR', pack( 'N2C5', 1, 1, 8, 6, 0, 0, 0 ) )
		. $chunk( 'tEXt', 'Comment' . "\0" . $marker )
		. $chunk( 'IDAT', gzcompress( "\x00\x66\x99\xcc\xff" ) )
		. $chunk( 'IEND', '' );
}

function cas_paid_materials_test_create_attachment( $path, array &$manifest, $key, $label, $extension, $mime, $bytes, $parent_id ) {
	$filename = $manifest['run_id'] . '-' . $label . '.' . $extension;
	$filetype = wp_check_filetype( $filename );
	if ( $mime !== ( $filetype['type'] ?? '' ) ) {
		cas_paid_materials_test_fail( 'Synthetic attachment MIME type did not match its extension.' );
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) || empty( $upload_dir['basedir'] ) || empty( $upload_dir['path'] ) || ! wp_mkdir_p( $upload_dir['path'] ) ) {
		cas_paid_materials_test_fail( 'WordPress upload directory is unavailable.' );
	}
	$base        = realpath( $upload_dir['basedir'] );
	$upload_path = realpath( $upload_dir['path'] );
	if ( ! $base || ! $upload_path || ! cas_paid_materials_test_inside( $upload_path, $base ) ) {
		cas_paid_materials_test_fail( 'WordPress upload directory is outside its configured base.' );
	}
	$expected_path = trailingslashit( $upload_path ) . $filename;

	$title = 'CAS Paid Materials ' . $manifest['run_id'] . ' ' . $label;
	$manifest['files'][ $key ] = array(
		'id'        => 0,
		'name'      => $filename,
		'title'     => $title,
		'mime'      => $mime,
		'path'      => $expected_path,
		'url'       => '',
		'sha256'    => hash( 'sha256', $bytes ),
		'parent_id' => (int) $parent_id,
	);
	cas_paid_materials_test_save_manifest( $path, $manifest );

	$upload = wp_upload_bits( $filename, null, $bytes );
	if ( ! is_array( $upload ) || ! empty( $upload['error'] ) || empty( $upload['file'] ) || empty( $upload['url'] ) ) {
		cas_paid_materials_test_fail( 'WordPress could not create a synthetic attachment.' );
	}

	$real_file     = realpath( $upload['file'] );
	$expected_real = realpath( $expected_path );
	if ( ! $real_file || ! $expected_real || $real_file !== $expected_real || ! cas_paid_materials_test_inside( $real_file, $base ) || basename( $real_file ) !== $filename ) {
		if ( $real_file && is_file( $real_file ) ) {
			unlink( $real_file );
		}
		cas_paid_materials_test_fail( 'Synthetic file escaped expected upload directory.' );
	}

	$attachment_id = wp_insert_attachment(
		array(
			'post_title'     => $title,
			'post_mime_type' => $mime,
			'post_status'    => 'inherit',
			'post_parent'    => (int) $parent_id,
		),
		$real_file,
		(int) $parent_id,
		true
	);
	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		cas_paid_materials_test_fail( 'Could not register a synthetic WordPress attachment.' );
	}

	$manifest['files'][ $key ]['id']   = (int) $attachment_id;
	$manifest['files'][ $key ]['path'] = $real_file;
	$manifest['files'][ $key ]['url']  = (string) $upload['url'];
	cas_paid_materials_test_save_manifest( $path, $manifest );

	return (int) $attachment_id;
}

function cas_paid_materials_test_create_post( $path, array &$manifest, $key, $type, $title, $content, $parent = 0, $author = null, $status = 'publish' ) {
	$author = null === $author ? get_current_user_id() : (int) $author;
	$slug   = sanitize_title( $title );
	$manifest['posts'][ $key ] = array(
		'id'        => 0,
		'type'      => $type,
		'title'     => $title,
		'slug'      => $slug,
		'parent_id' => (int) $parent,
		'author_id' => $author,
		'status'    => $status,
	);
	cas_paid_materials_test_save_manifest( $path, $manifest );

	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_parent'  => (int) $parent,
			'post_author'  => $author,
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		cas_paid_materials_test_fail( 'Could not create a synthetic Tutor post.' );
	}

	$post = get_post( $id );
	if ( ! $post || $post->post_name !== $slug ) {
		cas_paid_materials_test_fail( 'Synthetic post slug was changed or collided.' );
	}

	$manifest['posts'][ $key ]['id'] = (int) $id;
	cas_paid_materials_test_save_manifest( $path, $manifest );

	return (int) $id;
}

function cas_paid_materials_test_lesson_access( $lesson_id, $course_id, $user_id ) {
	wp_set_current_user( (int) $user_id );
	$is_enrolled = \Tutor\Models\EnrollmentModel::is_enrolled( (int) $course_id, (int) $user_id );

	return (bool) \Tutor\Models\CourseModel::has_course_content_access(
		array(
			'current_post_type' => 'lesson',
			'current_post_id'   => (int) $lesson_id,
			'course_id'         => (int) $course_id,
			'is_public'         => (bool) \TUTOR\Course_List::is_public( (int) $course_id ),
			'is_enrolled'       => (bool) $is_enrolled,
		)
	);
}

function cas_paid_materials_test_editor_authorization( $editor_id, $course_id, $lesson_id, $course_attachment_id, $lesson_attachment_id, $reference_attachment_id ) {
	$course_post = get_post( $course_id );
	$lesson_post = get_post( $lesson_id );
	wp_set_current_user( (int) $editor_id );

	$authorization = array(
		'owns_course'                => $course_post && (int) $course_post->post_author === (int) $editor_id,
		'owns_lesson'                => $lesson_post && (int) $lesson_post->post_author === (int) $editor_id,
		'owns_course_attachment'     => (int) get_post_field( 'post_author', $course_attachment_id ) === (int) $editor_id,
		'owns_lesson_attachment'       => (int) get_post_field( 'post_author', $lesson_attachment_id ) === (int) $editor_id,
		'owns_reference_attachment'    => (int) get_post_field( 'post_author', $reference_attachment_id ) === (int) $editor_id,
		'can_edit_course'              => current_user_can( 'edit_post', (int) $course_id ),
		'can_edit_lesson'              => current_user_can( 'edit_post', (int) $lesson_id ),
		'can_edit_course_attachment'   => current_user_can( 'edit_post', (int) $course_attachment_id ),
		'can_edit_lesson_attachment'   => current_user_can( 'edit_post', (int) $lesson_attachment_id ),
		'can_edit_reference_attachment'=> current_user_can( 'edit_post', (int) $reference_attachment_id ),
		'manage_options'             => current_user_can( 'manage_options' ),
		'tutor_course_access'        => cas_paid_materials_test_lesson_access( $lesson_id, $course_id, $editor_id ),
	);
	$authorization['may_access_private_materials'] = $authorization['can_edit_course']
		|| $authorization['can_edit_lesson']
		|| $authorization['can_edit_course_attachment']
		|| $authorization['can_edit_lesson_attachment']
		|| $authorization['can_edit_reference_attachment']
		|| $authorization['manage_options']
		|| $authorization['tutor_course_access'];

	return $authorization;
}

function cas_paid_materials_test_seed( $path, array $runtime ) {
	if ( file_exists( $path ) ) {
		cas_paid_materials_test_fail( 'Manifest already exists; choose a new private path.' );
	}

	$run_id = 'cas-paid-materials-' . bin2hex( random_bytes( 6 ) );
	$wp_cli_user_id = get_current_user_id();
	cas_paid_materials_test_run_id( $run_id );
	$manifest = array(
		'schema'      => 1,
		'synthetic'   => true,
		'run_id'      => $run_id,
		'environment' => $runtime['environment'],
		'home'        => $runtime['home'],
		'siteurl'     => $runtime['siteurl'],
		'web_root'     => $runtime['web_root'],
		'repo_root'    => $runtime['repo_root'],
		'manifest_path'=> $path,
		'database'     => $runtime['database'],
		'users'       => array(),
		'posts'       => array(),
		'files'       => array(),
		'markers'     => array(
			'course'         => 'CAS_PAID_MATERIALS_COURSE_' . $run_id,
			'paid_lesson'    => 'CAS_PAID_MATERIALS_PAID_LESSON_' . $run_id,
			'preview_lesson' => 'CAS_PAID_MATERIALS_PREVIEW_LESSON_' . $run_id,
		),
	);
	cas_paid_materials_test_save_manifest( $path, $manifest, true );

	$users = array(
		'unenrolled' => cas_paid_materials_test_create_user( $path, $manifest, 'unenrolled', 'subscriber' ),
		'enrolled'   => cas_paid_materials_test_create_user( $path, $manifest, 'enrolled', 'subscriber' ),
		'editor'     => cas_paid_materials_test_create_user( $path, $manifest, 'editor', 'editor' ),
	);

	$course_title = 'CAS Paid Materials ' . $run_id . ' Course';
	$course_id    = cas_paid_materials_test_create_post( $path, $manifest, 'course', 'courses', $course_title, $manifest['markers']['course'] );
	update_post_meta( $course_id, '_tutor_is_public_course', 'no' );
	update_post_meta( $course_id, '_tutor_course_price_type', 'paid' );
	update_post_meta( $course_id, 'tutor_course_price', '1.00' );
	if ( \TUTOR\Course_List::is_public( $course_id ) ) {
		cas_paid_materials_test_fail( 'Synthetic paid course resolved as public.' );
	}

	$topic_id = cas_paid_materials_test_create_post( $path, $manifest, 'topic', 'topics', 'CAS Paid Materials ' . $run_id . ' Topic', '', $course_id );
	$paid_id  = cas_paid_materials_test_create_post( $path, $manifest, 'paid_lesson', 'lesson', 'CAS Paid Materials ' . $run_id . ' Paid Lesson', $manifest['markers']['paid_lesson'], $topic_id );
	$preview_id = cas_paid_materials_test_create_post( $path, $manifest, 'preview_lesson', 'lesson', 'CAS Paid Materials ' . $run_id . ' Preview Lesson', $manifest['markers']['preview_lesson'], $topic_id );
	update_post_meta( $preview_id, '_is_preview', 1 );

	$course_pdf = cas_paid_materials_test_create_attachment( $path, $manifest, 'paid_course', 'course', 'pdf', 'application/pdf', cas_paid_materials_test_pdf( 'COURSE_' . $run_id ), $course_id );
	$lesson_pdf = cas_paid_materials_test_create_attachment( $path, $manifest, 'paid_lesson', 'paid-lesson', 'pdf', 'application/pdf', cas_paid_materials_test_pdf( 'LESSON_' . $run_id ), $paid_id );
	$reference_png = cas_paid_materials_test_create_attachment( $path, $manifest, 'paid_reference', 'paid-reference', 'png', 'image/png', cas_paid_materials_test_png( 'REFERENCE_' . $run_id ), $paid_id );
	$preview_pdf = cas_paid_materials_test_create_attachment( $path, $manifest, 'preview_attachment', 'preview', 'pdf', 'application/pdf', cas_paid_materials_test_pdf( 'PREVIEW_' . $run_id ), $preview_id );
	$public_png = cas_paid_materials_test_create_attachment( $path, $manifest, 'public_control', 'public-control', 'png', 'image/png', cas_paid_materials_test_png( 'PUBLIC_' . $run_id ), 0 );

	update_post_meta( $course_id, '_tutor_attachments', array( $course_pdf ) );
	update_post_meta( $paid_id, '_tutor_attachments', array( $lesson_pdf, $reference_png ) );
	update_post_meta( $preview_id, '_tutor_attachments', array( $preview_pdf ) );

	$enrolment_id = cas_paid_materials_test_create_post( $path, $manifest, 'enrollment', 'tutor_enrolled', 'CAS Paid Materials ' . $run_id . ' Enrollment', '', $course_id, $users['enrolled'], 'completed' );
	if ( ! \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $users['enrolled'] ) ) {
		cas_paid_materials_test_fail( 'Synthetic completed enrolment did not validate.' );
	}
	if ( \Tutor\Models\EnrollmentModel::is_enrolled( $course_id, $users['unenrolled'], false ) ) {
		cas_paid_materials_test_fail( 'Unenrolled synthetic subscriber has an enrolment.' );
	}

	$manifest['pages'] = array(
		'course'         => get_permalink( $course_id ),
		'paid_lesson'    => get_permalink( $paid_id ),
		'preview_lesson' => get_permalink( $preview_id ),
	);
	cas_paid_materials_test_save_manifest( $path, $manifest );

	foreach ( $manifest['pages'] as $url ) {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts )
			|| ! cas_paid_materials_test_is_loopback_url( $url )
			|| ! empty( $parts['query'] )
			|| ! empty( $parts['fragment'] )
			|| false === strpos( $parts['path'] ?? '', $run_id ) ) {
			cas_paid_materials_test_fail( 'Synthetic permalink is not a loopback fixture route.' );
		}
	}

	foreach ( $manifest['files'] as $file ) {
		$parts = wp_parse_url( $file['url'] );
		if ( ! is_array( $parts )
			|| ! cas_paid_materials_test_is_loopback_url( $file['url'] )
			|| false === strpos( $parts['path'] ?? '', '/wp-content/uploads/' )
			|| false === strpos( basename( $parts['path'] ?? '' ), $run_id . '-' ) ) {
			cas_paid_materials_test_fail( 'Synthetic attachment URL is not a loopback uploads fixture.' );
		}
	}

	foreach ( array( 0, $users['unenrolled'] ) as $user_id ) {
		if ( cas_paid_materials_test_lesson_access( $paid_id, $course_id, $user_id ) ) {
			cas_paid_materials_test_fail( 'Paid lesson unexpectedly allows an un-enrolled synthetic user.' );
		}
	}
	if ( ! cas_paid_materials_test_lesson_access( $paid_id, $course_id, $users['enrolled'] ) ) {
		cas_paid_materials_test_fail( 'Enrolled synthetic user cannot access paid lesson.' );
	}
	if ( ! cas_paid_materials_test_lesson_access( $preview_id, $course_id, 0 ) ) {
		cas_paid_materials_test_fail( 'Tutor did not allow the explicit preview lesson.' );
	}

	$manifest['users']['editor']['authorization'] = cas_paid_materials_test_editor_authorization(
		$users['editor'],
		$course_id,
		$paid_id,
		$course_pdf,
		$lesson_pdf,
		$reference_png
	);
	cas_paid_materials_test_save_manifest( $path, $manifest );
	wp_set_current_user( $wp_cli_user_id );

	WP_CLI::success( 'Synthetic fixtures seeded. Run ID: ' . $run_id . '. Manifest: ' . $path . '. Passwords omitted.' );
}

function cas_paid_materials_test_validate_cleanup( $manifest ) {
	cas_paid_materials_test_run_id( $manifest['run_id'] ?? null );
	if ( true !== ( $manifest['synthetic'] ?? false ) || ! is_array( $manifest['users'] ?? null ) || ! is_array( $manifest['posts'] ?? null ) || ! is_array( $manifest['files'] ?? null ) ) {
		cas_paid_materials_test_fail( 'Cleanup manifest is not a complete synthetic run.' );
	}

	global $wpdb;
	$run_id = $manifest['run_id'];
	$valid_post_ids = array();
	foreach ( $manifest['posts'] as $key => $record ) {
		if ( ! is_array( $record ) ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: post record is invalid.' );
		}
		$post_id = (int) ( $record['id'] ?? 0 );
		if ( ! $post_id && ! empty( $record['slug'] ) && ! empty( $record['type'] ) ) {
			$found = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s", $record['slug'], $record['type'] ) );
			if ( count( $found ) > 1 ) {
				cas_paid_materials_test_fail( 'Cleanup stopped: synthetic post slug is not unique.' );
			}
			$post_id = $found ? (int) $found[0] : 0;
		}
		if ( ! $post_id ) {
			continue;
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			continue;
		}
		if ( $post->post_type !== ( $record['type'] ?? '' )
			|| $post->post_title !== ( $record['title'] ?? '' )
			|| $post->post_name !== ( $record['slug'] ?? '' )
			|| (int) $post->post_parent !== (int) ( $record['parent_id'] ?? 0 )
			|| (int) $post->post_author !== (int) ( $record['author_id'] ?? 0 )
			|| $post->post_status !== ( $record['status'] ?? '' )
			|| false === strpos( $post->post_title, $run_id ) ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: a recorded post does not match its synthetic marker.' );
		}
		$valid_post_ids[ $key ] = $post_id;
	}

	$upload_dir  = wp_upload_dir();
	$upload_root = realpath( $upload_dir['basedir'] ?? '' );
	if ( ! $upload_root ) {
		cas_paid_materials_test_fail( 'Cleanup stopped: upload directory is unavailable.' );
	}
	$attachment_ids = array();
	foreach ( $manifest['files'] as $key => $file ) {
		if ( ! is_array( $file ) || empty( $file['name'] ) || 0 !== strpos( $file['name'], $run_id . '-' ) || empty( $file['title'] ) || empty( $file['mime'] ) ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: attachment marker is invalid.' );
		}
		$attachment_id = (int) ( $file['id'] ?? 0 );
		if ( ! $attachment_id ) {
			$found = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_title = %s AND post_parent = %d",
					'attachment',
					$file['title'],
					(int) ( $file['parent_id'] ?? 0 )
				)
			);
			if ( count( $found ) > 1 ) {
				cas_paid_materials_test_fail( 'Cleanup stopped: synthetic attachment title is not unique.' );
			}
			$attachment_id = $found ? (int) $found[0] : 0;
		}
		if ( $attachment_id ) {
			$attachment = get_post( $attachment_id );
			if ( $attachment && ( 'attachment' !== $attachment->post_type
				|| $attachment->post_title !== $file['title']
				|| $attachment->post_mime_type !== $file['mime']
				|| (int) $attachment->post_parent !== (int) ( $file['parent_id'] ?? 0 ) ) ) {
				cas_paid_materials_test_fail( 'Cleanup stopped: attachment ID does not match the fixture.' );
			}
			if ( $attachment ) {
				$attached_file = get_attached_file( $attachment->ID );
				if ( $attached_file ) {
					$real_file = realpath( $attached_file );
					if ( $real_file && ( ! cas_paid_materials_test_inside( $real_file, $upload_root ) || basename( $real_file ) !== $file['name'] ) ) {
						cas_paid_materials_test_fail( 'Cleanup stopped: attachment file is outside the fixture uploads path.' );
					}
				}
				$attachment_ids[ $key ] = $attachment_id;
			}
		}
		if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
			$real_file = realpath( $file['path'] );
			if ( ! $real_file || ! cas_paid_materials_test_inside( $real_file, $upload_root ) || basename( $real_file ) !== $file['name'] ) {
				cas_paid_materials_test_fail( 'Cleanup stopped: orphan file is outside the fixture uploads path.' );
			}
		}
	}

	foreach ( $manifest['users'] as $role => $user ) {
		if ( ! is_array( $user ) || empty( $user['login'] ) || 0 !== strpos( $user['login'], 'caspaid_' . $run_id . '_' ) ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: synthetic username marker is invalid.' );
		}
		$user_id = cas_paid_materials_test_user_id( $user['login'] );
		if ( ! empty( $user['id'] ) && $user_id && $user_id !== (int) $user['id'] ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: synthetic user ID no longer matches its login.' );
		}
	}

	return array( $valid_post_ids, $attachment_ids, $upload_root );
}

function cas_paid_materials_test_cleanup( $path, array $runtime ) {
	$permissions = fileperms( $path );
	if ( false === $permissions || 0 !== ( $permissions & 0077 ) ) {
		cas_paid_materials_test_fail( 'Cleanup stopped: manifest permissions are too broad.' );
	}
	$manifest = json_decode( file_get_contents( $path ), true );
	if ( ! is_array( $manifest ) ) {
		cas_paid_materials_test_fail( 'Cleanup stopped: manifest JSON is invalid.' );
	}
	if ( $manifest['home'] !== $runtime['home']
		|| $manifest['siteurl'] !== $runtime['siteurl']
		|| $manifest['database'] !== $runtime['database']
		|| $manifest['web_root'] !== $runtime['web_root']
		|| $manifest['repo_root'] !== $runtime['repo_root']
		|| wp_normalize_path( $manifest['manifest_path'] ?? '' ) !== wp_normalize_path( $path ) ) {
		cas_paid_materials_test_fail( 'Cleanup stopped: current staging identity differs from the fixture manifest.' );
	}

	list( $post_ids, $attachment_ids, $upload_root ) = cas_paid_materials_test_validate_cleanup( $manifest );
	$deleted_files = 0;
	$deleted_posts = 0;
	$deleted_users = 0;

	foreach ( $manifest['files'] as $key => $file ) {
		$path_existed = ! empty( $file['path'] ) && file_exists( $file['path'] );
		if ( isset( $attachment_ids[ $key ] ) ) {
			wp_delete_attachment( $attachment_ids[ $key ], true );
			if ( get_post( $attachment_ids[ $key ] ) ) {
				cas_paid_materials_test_fail( 'Cleanup failed to remove a synthetic attachment record.' );
			}
		}
		if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
			$real_file = realpath( $file['path'] );
			if ( ! $real_file || ! cas_paid_materials_test_inside( $real_file, $upload_root ) || basename( $real_file ) !== $file['name'] ) {
				cas_paid_materials_test_fail( 'Cleanup stopped: remaining file no longer matches the fixture.' );
			}
			if ( ! unlink( $real_file ) ) {
				cas_paid_materials_test_fail( 'Cleanup failed to remove a synthetic file.' );
			}
		}
		if ( ! empty( $file['path'] ) && file_exists( $file['path'] ) ) {
			cas_paid_materials_test_fail( 'Cleanup verification found a remaining synthetic file.' );
		}
		if ( $path_existed || isset( $attachment_ids[ $key ] ) ) {
			$deleted_files++;
		}
	}

	foreach ( array( 'enrollment', 'paid_lesson', 'preview_lesson', 'topic', 'course' ) as $key ) {
		if ( isset( $post_ids[ $key ] ) && wp_delete_post( $post_ids[ $key ], true ) ) {
			$deleted_posts++;
		}
	}
	foreach ( $post_ids as $post_id ) {
		if ( get_post( $post_id ) ) {
			cas_paid_materials_test_fail( 'Cleanup verification found a remaining synthetic post.' );
		}
	}

	global $wpdb;
	foreach ( $manifest['users'] as $user ) {
		$user_id = cas_paid_materials_test_user_id( $user['login'] );
		if ( ! $user_id ) {
			continue;
		}
		if ( ! empty( $user['id'] ) && $user_id !== (int) $user['id'] ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: user identity changed during cleanup.' );
		}
		if ( 0 !== strpos( cas_paid_materials_test_user_login( $user_id ), 'caspaid_' . $manifest['run_id'] . '_' ) ) {
			cas_paid_materials_test_fail( 'Cleanup stopped: user is not a synthetic fixture.' );
		}
		$wpdb->delete( $wpdb->usermeta, array( 'user_id' => $user_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->users, array( 'ID' => $user_id ), array( '%d' ) );
		if ( cas_paid_materials_test_user_id( $user['login'] ) ) {
			cas_paid_materials_test_fail( 'Cleanup verification found a remaining synthetic user.' );
		}
		$deleted_users++;
	}

	if ( ! unlink( $path ) || file_exists( $path ) ) {
		cas_paid_materials_test_fail( 'Fixtures were removed, but the private manifest could not be deleted.' );
	}
	WP_CLI::success( sprintf( 'Synthetic cleanup complete. Files: %d; posts: %d; users: %d. Manifest removed.', $deleted_files, $deleted_posts, $deleted_users ) );
}

function cas_paid_materials_test_main() {
	$runtime = cas_paid_materials_test_check_runtime();
	$action  = getenv( 'CAS_PAID_TEST_ACTION' );
	if ( ! in_array( $action, array( 'seed', 'cleanup' ), true ) ) {
		cas_paid_materials_test_fail( 'Set CAS_PAID_TEST_ACTION to seed or cleanup.' );
	}

	$path = cas_paid_materials_test_manifest_path( 'cleanup' === $action, $runtime['repo_root'] );
	if ( 'cleanup' === $action ) {
		cas_paid_materials_test_cleanup( $path, $runtime );

		return;
	}

	$upload_dir = wp_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		cas_paid_materials_test_fail( 'WordPress upload directory is unavailable.' );
	}
	cas_paid_materials_test_seed( $path, $runtime );
}

try {
	cas_paid_materials_test_main();
} catch ( Throwable $error ) {
	WP_CLI::error( $error->getMessage() );
}
