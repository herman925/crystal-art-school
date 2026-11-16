<?php
/** Administrator-issued, single-use invitations for Tutor student registration. */
defined( 'ABSPATH' ) || exit;

function cas_registration_invites_enabled() {
	return '1' === (string) get_option( 'cas_invite_registration_enabled', '0' );
}

function cas_account_registration_available() {
	return cas_registration_invites_enabled() || (bool) get_option( 'users_can_register' );
}

/** Only Tutor's student form/handler sees registration enabled; core signup stays closed. */
function cas_invites_tutor_registration_option( $pre ) {
	if ( ! cas_registration_invites_enabled() || is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $pre;
	}
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
	$action = $_POST['tutor_action'] ?? '';
	if ( 'POST' === $method && is_string( $action ) && 'tutor_register_student' === $action ) {
		return 1;
	}
	if ( in_array( $method, array( 'GET', 'HEAD' ), true ) && 'register' === cas_account_current_key() ) {
		return 1;
	}
	return $pre;
}
add_filter( 'pre_option_users_can_register', 'cas_invites_tutor_registration_option' );

/** Codes are accepted only in POST bodies, never URL parameters. */
function cas_invite_posted_code() {
	$code = $_POST['cas_invite_code'] ?? '';
	if ( ! is_string( $code ) || strlen( $code ) > 64 ) { return ''; }
	$code = strtoupper( trim( wp_unslash( $code ) ) );
	return preg_match( '/^CAS(?:-[A-F0-9]{6}){4}$/D', $code ) ? $code : '';
}

function cas_invite_option_key( $code ) {
	return 'cas_invite_code_' . hash_hmac( 'sha256', $code, wp_salt( 'auth' ) );
}

/** Bypass the options cache for single-use decisions and compare the exact old row. */
function cas_invite_read( $key ) {
	global $wpdb;
	$raw = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $key ) );
	return is_string( $raw ) ? array( 'raw' => $raw, 'data' => json_decode( $raw, true ) ) : null;
}

function cas_invite_replace( $key, $before, $after ) {
	global $wpdb;
	$changed = $wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND BINARY option_value = %s",
		$after, $key, $before
	) );
	wp_cache_delete( $key, 'options' );
	return 1 === $changed;
}

/** One request owns the reservation until native registration commits or fails. */
function cas_invite_context( $set = null ) {
	static $context = array();
	if ( null !== $set ) { $context = $set; }
	return $context;
}

function cas_invite_registration_errors( $errors ) {
	if ( ! cas_registration_invites_enabled() ) { return $errors; }
	$code = cas_invite_posted_code();
	$action = $_POST['tutor_action'] ?? '';
	$nonce = function_exists( 'tutor' ) ? ( $_POST[ tutor()->nonce ] ?? '' ) : '';
	if ( 'tutor_register_student' !== $action || is_admin() || is_user_logged_in() || ! function_exists( 'tutor' ) || ! doing_action( 'template_redirect' )
		|| ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, tutor()->nonce_action ) || '' === $code ) {
		$errors->add( 'cas_invite', 'A valid invitation code from the site administrator is required.' );
		return $errors;
	}
	// Tutor verifies its nonce before calling registration_errors. Core registration never claims codes.
	$key = cas_invite_option_key( $code );
	$row = cas_invite_read( $key );
	$data = $row['data'] ?? null;
	if ( ! is_array( $data ) || 'ready' !== ( $data['state'] ?? '' ) || (int) ( $data['expires'] ?? 0 ) <= time() ) {
		$errors->add( 'cas_invite', 'This invitation code is invalid, expired or already used. Ask the site administrator for a new code.' );
		return $errors;
	}
	$data['state'] = 'reserved';
	$data['reservation'] = bin2hex( random_bytes( 16 ) );
	$reserved = wp_json_encode( $data );
	if ( ! cas_invite_replace( $key, $row['raw'], $reserved ) ) {
		$errors->add( 'cas_invite', 'This invitation code is unavailable. Ask the site administrator for a new code.' );
		return $errors;
	}
	cas_invite_context( array( 'key' => $key, 'before' => $row['raw'], 'reserved' => $reserved, 'data' => $data, 'finished' => false ) );
	return $errors;
}
add_filter( 'registration_errors', 'cas_invite_registration_errors', 100 );

function cas_invites_no_page_cache() {
	if ( cas_registration_invites_enabled() && 'register' === cas_account_current_key() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		nocache_headers();
	}
}
add_action( 'template_redirect', 'cas_invites_no_page_cache', -100 );

function cas_invite_student_role( $pre ) {
	return cas_invite_context() ? 'subscriber' : $pre;
}
add_filter( 'pre_option_default_role', 'cas_invite_student_role' );

/** Runs inside Tutor's transaction, before verification mail and before COMMIT. */
function cas_invite_user_created( $user_id ) {
	$context = cas_invite_context();
	if ( ! $context ) { return; }
	$user = get_userdata( $user_id );
	if ( ! $user || array( 'subscriber' ) !== $user->roles ) {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		clean_user_cache( $user_id );
		wp_die( 'Student registration could not be completed.', '', array( 'response' => 400 ) );
	}
	$data = $context['data'];
	$data['state'] = 'used';
	$data['user'] = (int) $user_id;
	$data['used_at'] = time();
	unset( $data['reservation'] );
	if ( ! cas_invite_replace( $context['key'], $context['reserved'], wp_json_encode( $data ) ) ) {
		global $wpdb;
		$wpdb->query( 'ROLLBACK' );
		clean_user_cache( $user_id );
		wp_die( 'The invitation is no longer available. Ask the site administrator for a new code.', '', array( 'response' => 400 ) );
	}
	$context['new_user'] = (int) $user_id;
	cas_invite_context( $context );
}
add_action( 'user_register', 'cas_invite_user_created', -1000 );

function cas_invite_registration_finished() {
	$context = cas_invite_context();
	if ( $context ) {
		$context['finished'] = true;
		cas_invite_context( $context );
	}
}
add_action( 'tutor_new_user_registered', 'cas_invite_registration_finished', -1000 );

/** Failed validation/verification must not burn a code or leave Tutor's transaction open. */
function cas_invite_release_failed_registration() {
	$context = cas_invite_context();
	if ( ! $context || $context['finished'] ) { return; }
	global $wpdb;
	$wpdb->query( 'ROLLBACK' );
	if ( ! empty( $context['new_user'] ) ) { clean_user_cache( $context['new_user'] ); }
	cas_invite_replace( $context['key'], $context['reserved'], $context['before'] );
}
add_action( 'shutdown', 'cas_invite_release_failed_registration', 1 );

function cas_invite_registration_field() {
	if ( ! cas_registration_invites_enabled() ) { return; }
	?>
	<div class="tutor-form-group">
		<label class="tutor-block tutor-mb-3" for="cas-invite-code">Invitation code</label>
		<div class="tutor-input-field tutor-mb-8">
			<input class="tutor-form-control tutor-input" id="cas-invite-code" name="cas_invite_code" type="text" value="<?php echo esc_attr( cas_invite_posted_code() ); ?>" required maxlength="31" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="cas-invite-help">
			<p id="cas-invite-help">Registration is by invitation. Enter the single-use code supplied by the site administrator, then verify your email to finish.</p>
		</div>
	</div>
	<?php
}
add_action( 'tutor_student_reg_form_start', 'cas_invite_registration_field' );

function cas_invites_admin_menu() {
	add_users_page( 'Student Invitations', 'Student Invitations', 'manage_options', 'cas-student-invitations', 'cas_invites_admin_page' );
}
add_action( 'admin_menu', 'cas_invites_admin_menu' );

function cas_invites_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Administrator access required.', '', array( 'response' => 403 ) ); }
	global $wpdb;
	$code = '';
	$notice = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		check_admin_referer( 'cas-student-invitations' );
		$operation = $_POST['cas_invite_operation'] ?? '';
		if ( 'settings' === $operation ) {
			update_option( 'cas_invite_registration_enabled', isset( $_POST['cas_invites_enabled'] ) ? '1' : '0', false );
			$notice = 'Invitation registration settings saved.';
		} elseif ( 'generate' === $operation ) {
			$code = 'CAS-' . implode( '-', str_split( strtoupper( bin2hex( random_bytes( 12 ) ) ), 6 ) );
			$data = array( 'state' => 'ready', 'suffix' => substr( $code, -6 ), 'created' => time(), 'expires' => time() + 14 * DAY_IN_SECONDS, 'issuer' => get_current_user_id() );
			if ( ! add_option( cas_invite_option_key( $code ), wp_json_encode( $data ), '', false ) ) { wp_die( 'Could not create invitation. Please try again.' ); }
		} elseif ( 'revoke' === $operation ) {
			$key = $_POST['cas_invite_key'] ?? '';
			if ( ! is_string( $key ) || ! preg_match( '/^cas_invite_code_[a-f0-9]{64}$/D', $key ) ) { wp_die( 'Invalid invitation.' ); }
			$row = cas_invite_read( $key );
			if ( is_array( $row['data'] ?? null ) && in_array( $row['data']['state'] ?? '', array( 'ready', 'reserved' ), true ) ) {
				$data = $row['data']; $data['state'] = 'revoked';
				$notice = cas_invite_replace( $key, $row['raw'], wp_json_encode( $data ) ) ? 'Invitation revoked.' : 'Invitation changed. Refresh and check its status.';
			}
		}
	}
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT 100", $wpdb->esc_like( 'cas_invite_code_' ) . '%' ), ARRAY_A );
	?>
	<div class="wrap">
		<h1>Student Invitations</h1>
		<p>Give each student their own code and this signup link: <a href="<?php echo esc_url( cas_account_signup_url() ); ?>"><?php echo esc_html( cas_account_signup_url() ); ?></a></p>
		<p>Each code creates one student account, expires after 14 days and is shown in full only when generated. Codes do not grant course access. Existing students can still log in.</p>
		<?php if ( $notice ) : ?><div class="notice notice-success"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?>
		<?php if ( $code ) : ?>
			<div class="notice notice-success"><p>Copy this code now and send it privately to the student:</p><p><code id="cas-generated-invite" style="font-size:20px;user-select:all"><?php echo esc_html( $code ); ?></code></p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'cas-student-invitations' ); ?>
			<input type="hidden" name="cas_invite_operation" value="settings">
			<p><label><input type="checkbox" name="cas_invites_enabled" value="1" <?php checked( cas_registration_invites_enabled() ); ?>> Enable invitation-only student registration</label></p>
			<?php submit_button( 'Save Settings', 'secondary' ); ?>
		</form>
		<form method="post">
			<?php wp_nonce_field( 'cas-student-invitations' ); ?>
			<input type="hidden" name="cas_invite_operation" value="generate">
			<?php submit_button( 'Generate Invitation Code' ); ?>
		</form>
		<table class="widefat striped"><thead><tr><th>Code ending</th><th>Created</th><th>Expires</th><th>Status</th><th>Action</th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : $data = json_decode( $row['option_value'], true ); if ( ! is_array( $data ) ) { continue; } $state = $data['state'] ?? ''; if ( 'ready' === $state && (int) $data['expires'] <= time() ) { $state = 'expired'; } ?>
			<tr><td><?php echo esc_html( $data['suffix'] ?? '' ); ?></td><td><?php echo esc_html( wp_date( 'j M Y H:i', (int) $data['created'] ) ); ?></td><td><?php echo esc_html( wp_date( 'j M Y H:i', (int) $data['expires'] ) ); ?></td><td><?php echo esc_html( ucfirst( $state ) ); ?></td><td>
			<?php if ( in_array( $state, array( 'ready', 'reserved' ), true ) ) : ?>
				<form method="post"><?php wp_nonce_field( 'cas-student-invitations' ); ?><input type="hidden" name="cas_invite_operation" value="revoke"><input type="hidden" name="cas_invite_key" value="<?php echo esc_attr( $row['option_name'] ); ?>"><button class="button">Revoke</button></form>
			<?php endif; ?>
			</td></tr>
		<?php endforeach; ?>
		<?php if ( ! $rows ) : ?><tr><td colspan="5">No invitations yet.</td></tr><?php endif; ?>
		</tbody></table>
	</div>
	<?php
}
