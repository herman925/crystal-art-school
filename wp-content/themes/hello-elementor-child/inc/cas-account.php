<?php
/**
 * Log in, Sign up, Dashboard, Basket and Checkout — the shell around Tutor's own pages.
 *
 * WHAT THIS FILE IS NOT. It does not authenticate anybody, does not create an account,
 * does not take a payment and does not enrol anyone on a course. Tutor LMS owns all of
 * that, and the forms below are Tutor's own forms rendered from Tutor's own templates.
 * This file supplies the page furniture around them — the wood banner, the auth card,
 * the dashboard band — and remaps Tutor's design tokens onto the site's palette so its
 * controls stop looking like a different product. If a future change here starts
 * touching credentials, orders or enrolment, it is in the wrong file.
 *
 * WHY THERE ARE TWO WAYS IN. Four shortcodes are registered, and they work on any page
 * the integrator puts them on. But three of these five surfaces cannot be reached by a
 * shortcode at all:
 *
 *   - /dashboard/ (logged in) is not a WordPress page render. Tutor swaps the template
 *     through `template_include` at priority 99 and `templates/dashboard.php` prints its
 *     own <!doctype html> document, so the theme's header and footer never run and any
 *     shortcode in the page content is never reached. It is adopted through Tutor's own
 *     `tutor_dashboard/before/wrap` and `after/wrap` actions instead.
 *   - /cart/, /checkout/ and /student-registration/ are ordinary pages, but Tutor's
 *     `convert_static_page_to_template()` filter *replaces* `the_content` wholesale, so
 *     again a shortcode in the content never runs. They are adopted by filtering
 *     `the_content` at priority 20 — after Tutor has produced its markup, wrapping it
 *     rather than replacing it.
 *
 * Neither adoption changes Tutor's routing, its templates or its form handlers.
 *
 * WHAT THE DESIGN SAYS. `docs/20260804_Design_Direction.md` §20 records that these pages
 * were never walked through with the client and get no individual layout this round, but
 * that they DO inherit the site-wide changes; it also states that Checkout carries a
 * `.page-hero` wood banner and that §1.4.5 excludes all four from the Logo2 placement.
 * The prototype does specify markup for them — `Login.dc.html`, `Signup.dc.html`,
 * `Dashboard.dc.html`, `Checkout.dc.html` — and every class used below (`.auth-wrap`,
 * `.auth-card`, `.auth-head`, `.auth-stage`, `.auth-foot`, `.auth-success`, `.co-field`,
 * `.dash-hero`, `.dash-stat`, `.steps`) is already in the shipped bundle. The banner is
 * hand-written here rather than taken from `cas_page_hero()` precisely because that
 * helper always emits Logo2, which §1.4.5 forbids on these pages.
 *
 * WHAT WAS DELIBERATELY LEFT OUT. The prototype's Login/Signup/Checkout screens are demo
 * machinery — a localStorage session, a Google button that is not connected, a three-step
 * sign-up wizard that picks a "focus" and a "plan", a card-number field that posts
 * nowhere. None of it is reproduced. The dashboard band also carries no plan chip:
 * Wanderer / Apprentice / Fellow is a pricing idea, there is no field in Tutor that
 * records it, and a guessed label is a fabricated one.
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Which page are we on
 * ---------------------------------------------------------------------- */

/**
 * The four page ids Tutor is configured to use, read from Tutor rather than hardcoded
 * so that moving a page in Tutor's settings moves the furniture with it.
 *
 * @return array<string,int>
 */
function cas_account_page_ids() {
	static $ids = null;

	if ( null !== $ids ) {
		return $ids;
	}

	$ids = array(
		'dashboard' => 0,
		'register'  => 0,
		'cart'      => 0,
		'checkout'  => 0,
	);

	if ( function_exists( 'tutor_utils' ) ) {
		$ids['dashboard'] = (int) tutor_utils()->get_option( 'tutor_dashboard_page_id' );
		$ids['register']  = (int) tutor_utils()->get_option( 'student_register_page' );
		$ids['cart']      = (int) tutor_utils()->get_option( 'tutor_cart_page_id' );
		$ids['checkout']  = (int) tutor_utils()->get_option( 'tutor_checkout_page_id' );
	}

	return $ids;
}

/**
 * Whether Tutor has redirected to its order-placement success screen.
 *
 * Tutor's native payment handler renders the success template from a URL on the
 * site's home route, so it is not one of the configured Tutor pages. Detect the
 * read-only success request here so the account CSS and checkout footer variant
 * are still available to the template override below.
 *
 * @return bool
 */
function cas_account_is_checkout_success() {
	if ( is_admin() || ! isset( $_GET['tutor_order_placement'], $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	if ( ! is_scalar( $_GET['tutor_order_placement'] ) || ! is_scalar( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	return 'success' === (string) wp_unslash( $_GET['tutor_order_placement'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& absint( wp_unslash( $_GET['order_id'] ) ) > 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Whether this request is one of Tutor's pages, or '' for anything else.
 *
 * @return string
 */
function cas_account_current_key() {
	if ( cas_account_is_checkout_success() ) {
		return 'checkout-success';
	}

	if ( is_admin() || ! is_page() ) {
		return '';
	}

	$id = (int) get_queried_object_id();

	if ( ! $id ) {
		return '';
	}

	foreach ( cas_account_page_ids() as $key => $page_id ) {
		if ( $page_id && $page_id === $id ) {
			return $key;
		}
	}

	return '';
}

/**
 * One-shot guard so a surface never gets its furniture twice — once because the
 * integrator placed the shortcode, and once because the adoption filter fired.
 *
 * @param string $key  Surface key.
 * @param bool   $mark Pass true to record that it has been rendered.
 *
 * @return bool
 */
function cas_account_rendered( $key, $mark = false ) {
	static $done = array();

	if ( $mark ) {
		$done[ $key ] = true;
	}

	return ! empty( $done[ $key ] );
}

/**
 * Does this page carry one of our shortcodes? Used only to decide whether to enqueue.
 *
 * @return bool
 */
function cas_account_has_shortcode() {
	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	foreach ( array( 'cas_login', 'cas_signup', 'cas_dashboard', 'cas_checkout' ) as $tag ) {
		if ( has_shortcode( $post->post_content, $tag ) ) {
			return true;
		}
	}

	return false;
}

/* -------------------------------------------------------------------------
 * URLs
 *
 * Neither of these uses cas_page_url(): that helper falls back to the home URL for a
 * slug with no page (register trap T9), which is exactly how a broken account link
 * would go unreported. These fall back to WordPress's own URLs instead, which are at
 * worst plain but are never wrong.
 * ---------------------------------------------------------------------- */

/**
 * The site's log-in page if one exists, otherwise WordPress's own.
 *
 * @return string
 */
function cas_account_login_url() {
	$page = get_page_by_path( 'login' );

	return $page ? get_permalink( $page ) : wp_login_url();
}

/**
 * Send only core password requests to Tutor; keep core reset-token routes intact.
 */
function cas_account_redirect_password_request() {
	$method = $_SERVER['REQUEST_METHOD'] ?? 'GET'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! in_array( $method, array( 'GET', 'POST' ), true ) || ! function_exists( 'tutor_utils' ) ) {
		return;
	}

	$id   = cas_account_page_ids()['dashboard'];
	$page = $id ? get_post( $id ) : null;
	if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status ) {
		return;
	}

	$target = tutor_utils()->tutor_dashboard_url( 'retrieve-password' );
	$site   = wp_parse_url( home_url( '/' ) );
	$url    = wp_parse_url( $target );
	if ( ! is_array( $url ) || ! is_array( $site )
		|| ! in_array( $url['scheme'] ?? '', array( 'http', 'https' ), true )
		|| ( $url['scheme'] ?? '' ) !== ( $site['scheme'] ?? '' )
		|| strtolower( $url['host'] ?? '' ) !== strtolower( $site['host'] ?? '' )
		|| ( $url['port'] ?? ( 'https' === $url['scheme'] ? 443 : 80 ) ) !== ( $site['port'] ?? ( 'https' === $site['scheme'] ? 443 : 80 ) )
		|| isset( $url['user'] ) || isset( $url['pass'] )
		|| rtrim( $url['path'] ?? '', '/' ) === rtrim( (string) wp_parse_url( wp_login_url(), PHP_URL_PATH ), '/' ) ) {
		return;
	}

	// Preserve core reset-link failures without forwarding their query parameters.
	if ( 'GET' === $method && isset( $_GET['error'] ) && is_scalar( $_GET['error'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& in_array( wp_unslash( $_GET['error'] ), array( 'invalidkey', 'expiredkey' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$target = add_query_arg( 'cas_reset_notice', 'invalid', $target );
	}

	// Do not forward posted identifiers or untrusted redirect_to parameters.
	if ( wp_safe_redirect( $target, 'POST' === $method ? 303 : 302 ) ) {
		exit;
	}
}
add_action( 'login_form_lostpassword', 'cas_account_redirect_password_request' );
add_action( 'login_form_retrievepassword', 'cas_account_redirect_password_request' );

/**
 * Tutor's student registration page, or '' when it is not configured.
 *
 * @return string
 */
function cas_account_signup_url() {
	$id = cas_account_page_ids()['register'];

	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Tutor's dashboard page, or '' when it is not configured.
 *
 * @return string
 */
function cas_account_dashboard_url() {
	$id = cas_account_page_ids()['dashboard'];

	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * The course catalogue. Tutor owns the /courses/ slug, so this is the archive link.
 *
 * @return string
 */
function cas_account_courses_url() {
	return (string) cas_page_url( 'courses' );
}

/**
 * Tutor's basket page, or '' when it is not configured.
 *
 * @return string
 */
function cas_account_cart_url() {
	if ( class_exists( '\\Tutor\\Ecommerce\\CartController' ) && method_exists( '\\Tutor\\Ecommerce\\CartController', 'get_page_url' ) ) {
		return (string) \Tutor\Ecommerce\CartController::get_page_url();
	}

	$id = cas_account_page_ids()['cart'];

	return $id ? (string) get_permalink( $id ) : '';
}

/**
 * Whether the request is part of the checkout journey.
 *
 * The cart and checkout pages are normal WordPress pages, while Tutor renders
 * the success screen from a home URL with query arguments. Keeping this test in
 * one place lets the PHP footer mirror the prototype's checkout variant without
 * changing the footer used by unrelated pages.
 *
 * @return bool
 */
function cas_account_is_checkout_surface() {
	return in_array(
		cas_account_current_key(),
		array( 'cart', 'checkout', 'checkout-success' ),
		true
	);
}

/* -------------------------------------------------------------------------
 * The card shell
 * ---------------------------------------------------------------------- */

/**
 * The prototype's auth card: `.auth-wrap > .auth-card > .auth-head + body + .auth-foot`.
 *
 * @param string $eyebrow Small line above the title.
 * @param string $title   The page heading.
 * @param string $lede    Optional sentence under the heading.
 * @param string $body    Already-escaped markup for the card body.
 * @param string $foot    Optional already-escaped markup for the foot line.
 *
 * @return string
 */
function cas_account_card( $eyebrow, $title, $lede, $body, $foot = '' ) {
	if ( '' === trim( (string) $body ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="auth-wrap cas-account-shell">
		<div class="auth-card">
			<div class="auth-head">
				<?php if ( $eyebrow ) : ?>
					<div class="eyebrow"><span class="star">&#10022;</span> <?php echo esc_html( $eyebrow ); ?></div>
				<?php endif; ?>
				<h1><?php echo esc_html( $title ); ?></h1>
				<?php if ( $lede ) : ?>
					<p><?php echo esc_html( $lede ); ?></p>
				<?php endif; ?>
			</div>
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $foot ) : ?>
				<div class="auth-foot"><?php echo $foot; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endif; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * The card body shown to somebody who is already signed in. Both Log in and Sign up
 * land here, because both are dead ends for a signed-in visitor and sending them on
 * is more use than showing them a form they cannot use.
 *
 * @param string $heading Heading for the success block.
 *
 * @return string
 */
function cas_account_signed_in_body( $heading ) {
	$user      = wp_get_current_user();
	$dashboard = cas_account_dashboard_url();
	$courses   = cas_account_courses_url();

	ob_start();
	?>
	<div class="auth-stage active">
		<div class="auth-success">
			<div class="as-check" aria-hidden="true">&#10003;</div>
			<h2 style="font-family:var(--font-marker);font-weight:600;font-size:26px;color:var(--ink);margin:0 0 8px;"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $user->display_name ) : ?>
				<p style="color:var(--muted);margin:0 0 20px;">You are signed in as <b style="color:var(--ink);"><?php echo esc_html( $user->display_name ); ?></b>.</p>
			<?php endif; ?>
			<div class="fc-btns" style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
				<?php if ( $dashboard ) : ?>
					<a class="btn primary big" href="<?php echo esc_url( $dashboard ); ?>">Go to my dashboard <span class="arr" aria-hidden="true">&#8594;</span></a>
				<?php endif; ?>
				<a class="btn ghost" href="<?php echo esc_url( $courses ); ?>">Browse courses</a>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/* -------------------------------------------------------------------------
 * [cas_login]
 * ---------------------------------------------------------------------- */

/**
 * Flag read by the `tutor_after_login_redirect_url` filter below. Tutor's login form
 * defaults its redirect to the current URL, which on a dedicated /login/ page means
 * signing in returns you to the login page. The filter is global, so it is armed only
 * while our own form is being rendered and returns the URL untouched everywhere else.
 *
 * @param bool|null $on Pass true/false to set, null to read.
 *
 * @return bool
 */
function cas_account_login_redirect_active( $on = null ) {
	static $active = false;

	if ( null !== $on ) {
		$active = (bool) $on;
	}

	return $active;
}

add_filter(
	'tutor_after_login_redirect_url',
	function ( $url ) {
		if ( ! cas_account_login_redirect_active() ) {
			return $url;
		}

		// wp_validate_redirect() keeps this on this host, so a crafted ?redirect_to
		// cannot bounce a signed-in visitor off-site.
		$requested = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), '' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $requested ) {
			return $requested;
		}

		$dashboard = cas_account_dashboard_url();

		return $dashboard ? $dashboard : $url;
	}
);

/**
 * Log in. Tutor's own form, in the site's card.
 *
 * The form is `templates/login-form.php` from the plugin: it posts to itself with
 * `tutor_action=tutor_user_login` and Tutor's handler does the authentication. Nothing
 * here reads a password or writes a session.
 *
 * @return string
 */
function cas_render_login() {
	cas_account_rendered( 'login', true );

	if ( is_user_logged_in() ) {
		return cas_account_card(
			'Welcome back',
			'Log in',
			'',
			cas_account_signed_in_body( 'Already signed in' )
		);
	}

	if ( ! function_exists( 'tutor_load_template' ) ) {
		// Tutor is what authenticates. With it gone there is no honest form to show,
		// so the block renders nothing rather than a card with a hole in it.
		return '';
	}

	cas_account_login_redirect_active( true );
	ob_start();
	tutor_load_template( 'login-form' );
	$form = ob_get_clean();
	cas_account_login_redirect_active( false );

	if ( '' === trim( (string) $form ) ) {
		return '';
	}

	$body = '<div class="auth-stage active cas-account-form">' . $form . '</div>';
	$foot = '';

	// Only offer the sign-up link when accounts are actually open. Linking to a page
	// that answers "Access Denied" is worse than not linking at all.
	if ( get_option( 'users_can_register' ) ) {
		$signup = cas_account_signup_url();

		if ( $signup ) {
			$foot = 'New to the studio? <a href="' . esc_url( $signup ) . '">Create an account</a>';
		}
	}

	return cas_account_card( 'Welcome back', 'Log in', 'Pick up exactly where you left off.', $body, $foot );
}

add_shortcode( 'cas_login', 'cas_render_login' );

/* -------------------------------------------------------------------------
 * Tutor's retrieve-password endpoint
 *
 * This is a sub-route of Tutor's dashboard page, not the dedicated Log in page.
 * Tutor renders it through `template-part.retrieve-password`, so there is no
 * shortcode content to adopt and the dashboard wrap actions do not run. Its form
 * hooks are the stable extension point: capture Tutor's own form, put it in the
 * prototype card, and restore the missing main landmark around that card.
 * ---------------------------------------------------------------------- */

/**
 * Is this Tutor's public retrieve-password dashboard endpoint?
 *
 * @return bool
 */
function cas_account_is_retrieve_password() {
	return 'dashboard' === cas_account_current_key()
		&& 'retrieve-password' === (string) get_query_var( 'tutor_dashboard_page' );
}

/**
 * Track whether one of Tutor's retrieve/reset form hooks started our buffer.
 *
 * @param bool|null $on Pass true/false to set, null to read.
 *
 * @return bool
 */
function cas_account_retrieve_password_capture_active( $on = null ) {
	static $active = false;

	if ( null !== $on ) {
		$active = (bool) $on;
	}

	return $active;
}

/**
 * Start capturing Tutor's form after its custom header has rendered.
 *
 * @return void
 */
function cas_account_retrieve_password_before() {
	if ( ! cas_account_is_retrieve_password() || cas_account_retrieve_password_capture_active() ) {
		return;
	}

	echo '<main id="main" class="site-main">'; // phpcs:ignore WordPress.Security.EscapeOutput
	cas_account_retrieve_password_capture_active( true );
	ob_start();

	if ( isset( $_GET['cas_reset_notice'] ) && is_string( $_GET['cas_reset_notice'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		&& 'invalid' === wp_unslash( $_GET['cas_reset_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		tutor_alert( '<p>' . esc_html__( 'This password reset link is invalid or has expired. Please request a new link.', 'hello-elementor-child' ) . '</p>', 'danger', true, true );
	}
}

/**
 * Finish Tutor's form capture and render the prototype's password-reset card.
 *
 * @return void
 */
function cas_account_retrieve_password_after() {
	if ( ! cas_account_retrieve_password_capture_active() ) {
		return;
	}

	$form = ob_get_clean();
	cas_account_retrieve_password_capture_active( false );

	$back = '<a class="btn ghost full cas-account-back-link" href="'
		. esc_url( cas_account_login_url() )
		. '">&#8592; Back to log in</a>';
	$body = '<div class="auth-stage active cas-account-form cas-retrieve-password-form">'
		. $form
		. $back
		. '</div>';

	echo cas_account_card( // phpcs:ignore WordPress.Security.EscapeOutput
		'Reset access',
		'Forgot password?',
		'Tell us the address on the account.',
		$body
	);
	echo '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

add_action( 'tutor_before_retrieve_password_form', 'cas_account_retrieve_password_before', 0 );
add_action( 'tutor_after_retrieve_password_form', 'cas_account_retrieve_password_after', 999 );
add_action( 'tutor_before_reset_password_form', 'cas_account_retrieve_password_before', 0 );
add_action( 'tutor_after_reset_password_form', 'cas_account_retrieve_password_after', 999 );

/* Tutor's native reset template sets error flashes but does not display them. */
function cas_account_reset_password_feedback() {
	if ( cas_account_is_retrieve_password() ) {
		tutor_alert( null, 'any', true, true );
	}
}
add_action( 'tutor_before_reset_password_form', 'cas_account_reset_password_feedback', 1 );

/* Preserve Tutor's nonce/input checks; mask only its account-existence response for valid identifiers. */
function cas_account_password_recovery_request_active( $on = null ) {
	static $active = false;

	if ( null !== $on ) {
		$active = (bool) $on;
	}

	return $active;
}

function cas_account_password_recovery_input_valid() {
	if ( ! isset( $_POST['user_login'] ) || ! is_scalar( $_POST['user_login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	$login = trim( (string) wp_unslash( $_POST['user_login'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	return '' !== sanitize_user( $login );
}

function cas_account_password_recovery_start( $result ) {
	if ( ! is_wp_error( $result ) && cas_account_password_recovery_input_valid() ) {
		cas_account_password_recovery_request_active( true );
	}

	return $result;
}

function cas_account_password_recovery_preflight( $result ) {
	if ( is_wp_error( $result ) ) {
		cas_account_password_recovery_request_active( false );
	}

	return $result;
}

function cas_account_password_recovery_late_validation( $errors ) {
	if ( is_wp_error( $errors ) && $errors->has_errors() ) {
		cas_account_password_recovery_request_active( false );
	}
}

function cas_account_password_recovery_log_mail_failure( $error ) {
	if ( ! cas_account_password_recovery_request_active() ) {
		return;
	}

	$code = is_wp_error( $error ) ? sanitize_key( (string) $error->get_error_code() ) : '';
	$code = '' !== $code ? $code : 'unknown';

	if ( function_exists( 'cas_audit_log' ) ) {
		cas_audit_log( 'password_reset_mail_failed', 'code=' . $code );
	} else {
		error_log( 'CAS password reset mail failed [' . $code . ']' );
	}
}

function cas_account_password_recovery_finish() {
	if ( ! cas_account_password_recovery_request_active() ) {
		return;
	}

	cas_account_password_recovery_request_active( false );
	tutor_flash_get( 'danger' );
	tutor_flash_get( 'success' );
	tutor_flash_set(
		'success',
		'<p>' . esc_html__( 'If an account matches those details, a reset email will be sent.', 'hello-elementor-child' ) . '</p>'
	);
}

add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_start', 1 );
add_filter( 'tutor_before_retrieve_password_form_process', 'cas_account_password_recovery_preflight', PHP_INT_MAX );
add_action( 'lostpassword_post', 'cas_account_password_recovery_late_validation', PHP_INT_MAX );
add_action( 'wp_mail_failed', 'cas_account_password_recovery_log_mail_failure', 10, 1 );
add_action( 'tutor_action_tutor_retrieve_password', 'cas_account_password_recovery_finish', 20 );

/* -------------------------------------------------------------------------
 * [cas_signup]
 * ---------------------------------------------------------------------- */

/**
 * Whether the member-terms checkbox was submitted as checked.
 *
 * Tutor verifies its registration nonce before running the registration validation
 * filters and before firing `tutor_new_user_registered`. This helper only reads the
 * scalar checkbox value; it never trusts it as a substitute for Tutor's nonce check.
 *
 * @return bool
 */
function cas_account_member_terms_checked() {
	if ( ! isset( $_POST['cas_member_terms'] ) || ! is_scalar( $_POST['cas_member_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	return '1' === (string) wp_unslash( $_POST['cas_member_terms'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * The member-terms consent control appended by Tutor's registration-form hook.
 *
 * The copy mount is intentionally empty until the client supplies the final wording.
 * The existing About page's Terms & privacy heading is the only visible destination
 * here; no legal or member-terms text is invented in the account flow.
 *
 * @return string
 */
function cas_account_member_terms_markup() {
	$terms_url = esc_url( cas_page_url( 'about' ) . '#terms' );
	$checked   = checked( cas_account_member_terms_checked(), true, false );

	ob_start();
	?>
	<div class="tutor-form-row tutor-mb-8 cas-member-terms">
		<div class="tutor-input-field">
			<div class="tutor-input-wrapper">
				<input
					id="cas-member-terms"
					type="checkbox"
					class="tutor-checkbox tutor-checkbox-md"
					name="cas_member_terms"
					value="1"
					required
					aria-label="<?php esc_attr_e( 'Member terms consent', 'hello-elementor-child' ); ?>"
					aria-describedby="cas-member-terms-link"
					<?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				>
				<label for="cas-member-terms">
					<span class="cas-member-terms-copy"></span>
					<a id="cas-member-terms-link" href="<?php echo $terms_url; ?>">Terms &amp; privacy</a>
				</label>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Print the consent control inside Tutor's own registration form.
 *
 * @return void
 */
function cas_account_render_member_terms() {
	echo cas_account_member_terms_markup(); // phpcs:ignore WordPress.Security.EscapeOutput
}

add_action( 'tutor_student_reg_form_end', 'cas_account_render_member_terms' );

/**
 * Make the consent control required in Tutor's server-side registration validation.
 *
 * The message describes the missing control, not the pending legal wording. Tutor
 * remains responsible for collecting the rest of the form and creating the user.
 *
 * @param array<string,string> $fields Required registration fields.
 *
 * @return array<string,string>
 */
function cas_account_member_terms_required_field( $fields ) {
	$fields['cas_member_terms'] = __( 'Member terms consent is required to create an account.', 'hello-elementor-child' );

	return $fields;
}

add_filter( 'tutor_student_registration_required_fields', 'cas_account_member_terms_required_field' );

/**
 * Store the accepted consent only after Tutor has successfully created the user.
 *
 * A value and UTC timestamp are kept as user meta. The pending wording has no version
 * or legal text to store yet; adding that copy is a separate client decision.
 *
 * @param WP_User|int $user Newly registered Tutor user.
 *
 * @return void
 */
function cas_account_store_member_terms_consent( $user ) {
	if ( ! cas_account_member_terms_checked() ) {
		return;
	}

	$user_id = $user instanceof WP_User ? (int) $user->ID : absint( $user );

	if ( ! $user_id ) {
		return;
	}

	update_user_meta( $user_id, '_cas_member_terms_consent', '1' );
	update_user_meta( $user_id, '_cas_member_terms_consent_at', current_time( 'mysql', true ) );
}

add_action( 'tutor_new_user_registered', 'cas_account_store_member_terms_consent' );

/**
 * Mark a new student as having finished Tutor's onboarding tour.
 *
 * The tour is switched off by the empty tutor/shared/tour.php override. This is the second
 * line: Tutor records completion in `_tutor_tour_completed`, so a student who is created with
 * it set never sees the tour even if the override is later removed.
 *
 * @param WP_User|int $user Newly registered Tutor user.
 *
 * @return void
 */
function cas_account_skip_tutor_tour( $user ) {
	$user_id = $user instanceof WP_User ? (int) $user->ID : absint( $user );

	if ( ! $user_id ) {
		return;
	}

	update_user_meta( $user_id, '_tutor_tour_completed', true );
}

add_action( 'tutor_new_user_registered', 'cas_account_skip_tutor_tour' );

/**
 * Sign up. Tutor's own registration form, in the site's card.
 *
 * `users_can_register` is 0 on this install and turning it on is Herman's decision, not
 * this file's. While it is off, Tutor's own template answers "Oooh! Access Denied — please
 * refer to your system administrator", which is a plugin's error page shown to a customer.
 * The closed state below replaces it with a plain statement and a way onward. Flip the
 * option and the real form appears with no change here.
 *
 * @param string|null $inner Tutor's already-rendered form, when the adoption filter has it.
 *
 * @return string
 */
function cas_render_signup( $inner = null ) {
	cas_account_rendered( 'register', true );

	$login = cas_account_login_url();
	$foot  = 'Already have an account? <a href="' . esc_url( $login ) . '">Log in</a>';

	if ( is_user_logged_in() ) {
		return cas_account_card(
			'Join the studio',
			'Create your account',
			'',
			cas_account_signed_in_body( 'You already have an account' )
		);
	}

	if ( ! get_option( 'users_can_register' ) ) {
		$body = '<div class="auth-stage active" style="text-align:center;">'
			. '<p style="color:var(--muted);margin:0 0 20px;">New accounts are closed at the moment.</p>'
			. '<a class="btn primary big full" href="' . esc_url( cas_account_courses_url() ) . '">Browse the courses <span class="arr" aria-hidden="true">&#8594;</span></a>'
			. '</div>';

		return cas_account_card( 'Join the studio', 'Create your account', '', $body, $foot );
	}

	if ( null === $inner ) {
		$inner = shortcode_exists( 'tutor_student_registration_form' )
			? do_shortcode( '[tutor_student_registration_form]' )
			: '';
	}

	if ( '' === trim( (string) $inner ) ) {
		return '';
	}

	$body = '<div class="auth-stage active cas-account-form">' . $inner . '</div>';

	return cas_account_card( 'Join the studio', 'Create your account', '', $body, $foot );
}

add_shortcode(
	'cas_signup',
	function () {
		return cas_render_signup();
	}
);

/* -------------------------------------------------------------------------
 * [cas_dashboard]
 * ---------------------------------------------------------------------- */

/**
 * Whether Tutor is rendering the dashboard landing route rather than a native
 * dashboard sub-route. Query vars are used instead of a hard-coded page slug because
 * Tutor lets the integrator move or rename the dashboard page.
 *
 * @return bool
 */
function cas_account_dashboard_home_request() {
	$page = function_exists( 'get_query_var' ) ? (string) get_query_var( 'tutor_dashboard_page' ) : '';
	$sub  = function_exists( 'get_query_var' ) ? (string) get_query_var( 'tutor_dashboard_sub_page' ) : '';

	return '' === $page && '' === $sub;
}

/**
 * The studio panels are the student surface. Instructor/admin dashboard internals stay
 * on Tutor's native landing template, just like every other dashboard sub-route.
 *
 * @return bool
 */
function cas_account_dashboard_should_render_studio() {
	if ( ! cas_account_dashboard_home_request() || ! is_user_logged_in() ) {
		return false;
	}

	if ( class_exists( 'TUTOR\\User' ) && method_exists( 'TUTOR\\User', 'is_student_view' ) ) {
		return \TUTOR\User::is_student_view();
	}

	return true;
}

/**
 * Return the enrolled courses that Tutor says belong to the current student.
 * No browser demo records are consulted; unpublished catalogue posts are
 * deliberately omitted from the display.
 *
 * @param int $user_id WordPress user id.
 * @return array<int,array<string,mixed>>
 */
function cas_account_dashboard_courses( $user_id ) {
	if ( ! $user_id || ! function_exists( 'tutor_utils' ) ) {
		return array();
	}

	$utils = tutor_utils();
	if ( ! is_object( $utils ) || ! method_exists( $utils, 'get_enrolled_courses_ids_by_user' ) ) {
		return array();
	}

	$ids = $utils->get_enrolled_courses_ids_by_user( $user_id );
	$ids = is_array( $ids ) ? array_unique( array_map( 'absint', $ids ) ) : array();
	$rows = array();

	foreach ( $ids as $course_id ) {
		if ( ! $course_id ) {
			continue;
		}

		$post = get_post( $course_id );
		if ( ! $post instanceof WP_Post || 'courses' !== $post->post_type || ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
			continue;
		}

		$stats = method_exists( $utils, 'get_course_completed_percent' )
			? $utils->get_course_completed_percent( $course_id, $user_id, true )
			: array();
		$stats = is_array( $stats ) ? $stats : array();
		$total = max( 0, (int) ( $stats['total_count'] ?? 0 ) );
		$done  = max( 0, (int) ( $stats['completed_count'] ?? 0 ) );
		$done  = $total ? min( $done, $total ) : $done;
		$percent = $total ? (int) round( ( $done / $total ) * 100 ) : 0;
		$percent = max( 0, min( 100, $percent ) );
		$first_lesson = method_exists( $utils, 'get_course_first_lesson' )
			? $utils->get_course_first_lesson( $course_id )
			: false;
		$course_url = get_permalink( $course_id );
		$next_url   = $first_lesson ? $first_lesson : $course_url;

		if ( ! $course_url || ! $next_url ) {
			continue;
		}

		$rows[] = array(
			'id'        => $course_id,
			'title'     => get_the_title( $course_id ),
			'url'       => $course_url,
			'next_url'  => $next_url,
			'image'     => get_the_post_thumbnail_url( $course_id, 'medium' ),
			'done'      => $done,
			'total'     => $total,
			'percent'   => $percent,
		);
	}

	return $rows;
}

/**
 * Aggregate real Tutor progress for the student's enrolled courses.
 *
 * @param array<int,array<string,mixed>> $courses Enrolled course rows.
 * @return array{done:int,total:int,percent:int}
 */
function cas_account_dashboard_progress( $courses ) {
	$done  = 0;
	$total = 0;

	foreach ( $courses as $course ) {
		$done  += max( 0, (int) ( $course['done'] ?? 0 ) );
		$total += max( 0, (int) ( $course['total'] ?? 0 ) );
	}

	$percent = $total ? (int) round( ( $done / $total ) * 100 ) : 0;

	return array(
		'done'    => $done,
		'total'   => $total,
		'percent' => max( 0, min( 100, $percent ) ),
	);
}

/**
 * Get catalogue recommendations without inventing course titles, prices or tiers.
 * The brief permits newest-first fallback because no approved focus field exists in
 * Tutor's account data.
 *
 * @param array<int,int> $enrolled_ids Enrolled course ids.
 * @return array<int,array<string,mixed>>
 */
function cas_account_dashboard_recommendations( $enrolled_ids ) {
	$query = new WP_Query(
		array(
			'post_type'      => 'courses',
			'post_status'    => 'publish',
			'posts_per_page' => 3,
			'post__not_in'   => array_map( 'absint', (array) $enrolled_ids ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);
	$rows = array();

	foreach ( $query->posts as $post ) {
		$url = get_permalink( $post->ID );
		if ( ! $url ) {
			continue;
		}

		$rows[] = array(
			'id'    => (int) $post->ID,
			'title' => get_the_title( $post->ID ),
			'url'   => $url,
			'image' => get_the_post_thumbnail_url( $post->ID, 'medium' ),
		);
	}

	return $rows;
}

/**
 * Use Tutor's own template loader for account internals. This keeps billing, profile
 * forms, settings, nonce handling and future Tutor updates in Tutor's ownership.
 *
 * @param string               $template  Tutor template key.
 * @param array<string,mixed>  $variables Variables inherited by the template.
 * @return string
 */
function cas_account_dashboard_native_template( $template, $variables = array() ) {
	if ( ! function_exists( 'tutor_load_template' ) ) {
		return '';
	}

	ob_start();
	tutor_load_template( $template, $variables );
	return (string) ob_get_clean();
}

/**
 * @return string
 */
function cas_account_dashboard_courses_url() {
	if ( function_exists( 'tutor_utils' ) && method_exists( tutor_utils(), 'course_archive_page_url' ) ) {
		$url = tutor_utils()->course_archive_page_url();
		if ( $url ) {
			return $url;
		}
	}

	$url = get_post_type_archive_link( 'courses' );
	return $url ? $url : home_url( '/' );
}

/**
 * Render one empty state with a real catalogue destination.
 *
 * @return string
 */
function cas_account_dashboard_empty_state() {
	return '<div class="dash-card dash-empty">'
		. '<div class="eyebrow">Your studio</div>'
		. '<h3>Your bench is ready.</h3>'
		. '<p>Explore the course catalogue when you are ready to begin.</p>'
		. '<a class="btn primary" href="' . esc_url( cas_account_dashboard_courses_url() ) . '">Explore courses <span class="arr" aria-hidden="true">&#8594;</span></a>'
		. '</div>';
}

/**
 * Build the approved dashboard body. The sixth tab is Profile, which uses Tutor's
 * native account form; the obsolete community card is intentionally not represented.
 *
 * @return string
 */
function cas_account_dashboard_body() {
	$user_id       = get_current_user_id();
	$courses       = cas_account_dashboard_courses( $user_id );
	$progress      = cas_account_dashboard_progress( $courses );
	$enrolled_ids  = array_map(
		function ( $course ) {
			return (int) $course['id'];
		},
		$courses
	);
	$recommendations = cas_account_dashboard_recommendations( $enrolled_ids );
	$dashboard_url   = function_exists( 'tutor_utils' ) ? tutor_utils()->tutor_dashboard_url() : home_url( '/' );
	$logout_url      = wp_logout_url( $dashboard_url );
	$profile_html    = cas_account_dashboard_native_template(
		'dashboard.account.settings.account',
		array( 'form_id' => 'cas-dashboard-profile-form' )
	);
	$security_html   = cas_account_dashboard_native_template( 'dashboard.account.settings.security' );
	$purchases_html  = cas_dashboard_purchases_html( $user_id );
	$settings_html   = cas_dashboard_settings_html( $user_id );
	$tabs            = array(
		'overview'  => 'Overview',
		'courses'   => 'My courses',
		'progress'  => 'Progress',
		'purchases' => 'Purchases',
		'account'   => 'Profile',
		'settings'  => 'Settings',
	);

	ob_start();
	?>
	<div class="dash-layout cas-studio-dashboard" data-cas-dashboard>
		<nav class="dash-rail" role="tablist" aria-label="Studio sections" aria-orientation="vertical">
			<div class="dash-rail-head">
				<span class="eyebrow">Your studio</span>
				<span class="hand-note">Make a little, often.</span>
			</div>
			<?php $first = true; ?>
			<?php foreach ( $tabs as $key => $label ) : ?>
				<button
					class="dash-tab<?php echo $first ? ' active' : ''; ?>"
					type="button"
					role="tab"
					id="tab-<?php echo esc_attr( $key ); ?>"
					data-panel="<?php echo esc_attr( $key ); ?>"
					aria-controls="panel-<?php echo esc_attr( $key ); ?>"
					aria-selected="<?php echo $first ? 'true' : 'false'; ?>"
					tabindex="<?php echo $first ? '0' : '-1'; ?>"
				>
					<span><?php echo esc_html( $label ); ?></span><span class="arr" aria-hidden="true">&#8594;</span>
				</button>
				<?php $first = false; ?>
			<?php endforeach; ?>
			<div class="dash-rail-foot">
				<a class="dash-logout" href="<?php echo esc_url( $logout_url ); ?>">Log out <span class="arr" aria-hidden="true">&#8594;</span></a>
			</div>
		</nav>
		<div class="dash-main" id="dash-main">
			<section class="dash-panel active" id="panel-overview" data-panel="overview" role="tabpanel" aria-labelledby="tab-overview" tabindex="0">
				<div class="dash-panel-head"><div><span class="eyebrow">Overview</span><h2>Continue learning</h2></div></div>
				<?php if ( ! empty( $courses ) ) : ?>
					<?php $resume = $courses[0]; ?>
					<article class="resume-card dash-card">
						<?php if ( $resume['image'] ) : ?><img src="<?php echo esc_url( $resume['image'] ); ?>" alt="" loading="lazy" decoding="async" width="480" height="270"><?php endif; ?>
						<div class="resume-body"><span class="eyebrow">Pick up where you left off</span><h3><?php echo esc_html( $resume['title'] ); ?></h3><div class="prog-row"><span><?php echo esc_html( (string) $resume['done'] ); ?> / <?php echo esc_html( (string) $resume['total'] ); ?> lessons</span><strong><?php echo esc_html( (string) $resume['percent'] ); ?>%</strong></div><div class="pbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $resume['percent'] ); ?>"><span class="pfill" style="width:<?php echo esc_attr( (string) $resume['percent'] ); ?>%"></span></div><a class="btn primary" href="<?php echo esc_url( $resume['next_url'] ); ?>">Continue <span class="arr" aria-hidden="true">&#8594;</span></a></div>
					</article>
				<?php else : ?>
					<?php echo cas_account_dashboard_empty_state(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
				<?php if ( ! empty( $recommendations ) ) : ?>
					<div class="dash-subhead"><span class="eyebrow">From the catalogue</span><h3>Recommended next</h3></div>
					<div class="mycourse-grid dash-recommendations">
						<?php foreach ( $recommendations as $recommendation ) : ?>
							<a class="mc-course dash-recommendation" href="<?php echo esc_url( $recommendation['url'] ); ?>">
								<?php if ( $recommendation['image'] ) : ?><img src="<?php echo esc_url( $recommendation['image'] ); ?>" alt="" loading="lazy" decoding="async" width="480" height="270"><?php endif; ?>
								<span class="mc-body"><span class="eyebrow">Course</span><strong><?php echo esc_html( $recommendation['title'] ); ?></strong><span class="arr" aria-hidden="true">&#8594;</span></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section class="dash-panel" id="panel-courses" data-panel="courses" role="tabpanel" aria-labelledby="tab-courses" tabindex="0" hidden>
				<div class="dash-panel-head"><div><span class="eyebrow">Studio shelf</span><h2>My courses</h2></div><span class="badge"><?php echo esc_html( (string) count( $courses ) ); ?></span></div>
				<?php if ( empty( $courses ) ) : ?>
					<?php echo cas_account_dashboard_empty_state(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<div class="mycourse-grid">
						<?php foreach ( $courses as $course ) : ?>
							<article class="mc-course dash-card">
								<?php if ( $course['image'] ) : ?><a href="<?php echo esc_url( $course['url'] ); ?>"><img src="<?php echo esc_url( $course['image'] ); ?>" alt="" loading="lazy" decoding="async" width="480" height="270"></a><?php endif; ?>
								<div class="mc-body"><span class="eyebrow">Course</span><h3><?php echo esc_html( $course['title'] ); ?></h3><div class="prog-row"><span><?php echo esc_html( (string) $course['done'] ); ?> / <?php echo esc_html( (string) $course['total'] ); ?> lessons</span><strong><?php echo esc_html( (string) $course['percent'] ); ?>%</strong></div><div class="pbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $course['percent'] ); ?>"><span class="pfill" style="width:<?php echo esc_attr( (string) $course['percent'] ); ?>%"></span></div><a class="btn small" href="<?php echo esc_url( $course['percent'] >= 100 ? $course['url'] : $course['next_url'] ); ?>"><?php echo $course['percent'] >= 100 ? 'Review' : 'Continue'; ?> <span class="arr" aria-hidden="true">&#8594;</span></a></div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>

			<section class="dash-panel" id="panel-progress" data-panel="progress" role="tabpanel" aria-labelledby="tab-progress" tabindex="0" hidden>
				<div class="dash-panel-head"><div><span class="eyebrow">The long view</span><h2>Progress</h2></div></div>
				<div class="dash-card progress-summary"><div class="prog-row"><strong>Overall progress</strong><strong><?php echo esc_html( (string) $progress['percent'] ); ?>%</strong></div><div class="pbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $progress['percent'] ); ?>"><span class="pfill" style="width:<?php echo esc_attr( (string) $progress['percent'] ); ?>%"></span></div><p><?php echo esc_html( (string) $progress['done'] ); ?> of <?php echo esc_html( (string) $progress['total'] ); ?> lessons completed.</p></div>
				<?php if ( ! empty( $courses ) ) : ?>
					<div class="dash-card prog-list">
						<?php foreach ( $courses as $course ) : ?><div class="prog-course"><div class="prog-row"><a href="<?php echo esc_url( $course['url'] ); ?>"><?php echo esc_html( $course['title'] ); ?></a><strong><?php echo esc_html( (string) $course['percent'] ); ?>%</strong></div><div class="pbar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $course['percent'] ); ?>"><span class="pfill" style="width:<?php echo esc_attr( (string) $course['percent'] ); ?>%"></span></div></div><?php endforeach; ?>
					</div>
				<?php else : ?><p class="hand-note">Progress appears here as you finish lessons.</p><?php endif; ?>
			</section>

			<section class="dash-panel" id="panel-purchases" data-panel="purchases" role="tabpanel" aria-labelledby="tab-purchases" tabindex="0" hidden>
				<div class="dash-panel-head"><div><span class="eyebrow">Your records</span><h2>Purchases</h2></div></div>
				<div class="dash-card" style="padding:6px 4px;"><?php echo $purchases_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</section>

			<section class="dash-panel" id="panel-account" data-panel="account" role="tabpanel" aria-labelledby="tab-account" tabindex="0" hidden>
				<div class="dash-panel-head"><div><span class="eyebrow">Your details</span><h2>Profile</h2></div></div>
				<div class="dash-card tutor-panel-host" x-data="tutorSettings()"><?php echo $profile_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<div style="margin-top:16px;"><button class="btn primary" type="submit" form="cas-dashboard-profile-form">Save changes</button></div>
			</section>

			<section class="dash-panel" id="panel-settings" data-panel="settings" role="tabpanel" aria-labelledby="tab-settings" tabindex="0" hidden>
				<div class="dash-panel-head"><div><span class="eyebrow">Studio preferences</span><h2>Settings</h2></div></div>
				<?php echo $settings_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<div class="dash-card tutor-panel-host" style="margin-top:16px;" x-data="tutorSettings()"><?php echo $security_html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</section>
		</div>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the shared shell around Tutor's standalone dashboard documents.
 *
 * @param bool $include_hero Whether the dashboard welcome band should be printed.
 * @return void
 */
function cas_account_render_dashboard_shell_before( $include_hero = true ) {
	if ( cas_account_rendered( 'dashboard-shell' ) ) {
		return;
	}

	cas_account_rendered( 'dashboard-shell', true );

	if ( ! did_action( 'get_header' ) ) {
		echo '<a class="skip-link" href="#content">Skip to content</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		if ( shortcode_exists( 'cas_header' ) ) {
			echo do_shortcode( '[cas_header]' ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}

	echo '<main id="content" class="site-main" tabindex="-1">'; // phpcs:ignore WordPress.Security.EscapeOutput

	if ( $include_hero ) {
		$hero = cas_account_dash_hero();
		if ( $hero ) {
			echo '<div class="container cas-account-dash">' . $hero . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}

/**
 * @return void
 */
function cas_account_render_dashboard_shell_after() {
	if ( ! cas_account_rendered( 'dashboard-shell' ) ) {
		return;
	}

	echo '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput

	if ( ! did_action( 'get_header' ) && shortcode_exists( 'cas_footer' ) ) {
		echo do_shortcode( '[cas_footer]' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * The welcome band above Tutor's dashboard: `.dash-hero` from the prototype.
 *
 * Every number in it is read, not invented. The prototype also carries a plan chip
 * (Wanderer / Apprentice / Fellow) and it is not reproduced — nothing in Tutor records
 * which tier a member is on, Apprentice and Fellow are indistinguishable in the data,
 * and a label that might be wrong is a fabricated one.
 *
 * @return string
 */
function cas_account_dash_hero() {
	if ( ! is_user_logged_in() ) {
		return '';
	}

	$user    = wp_get_current_user();
	$courses = 0;
	$lessons = 0;

	if ( function_exists( 'tutor_utils' ) ) {
		$ids     = tutor_utils()->get_enrolled_courses_ids_by_user( $user->ID );
		$ids     = is_array( $ids ) ? $ids : array();
		$courses = count( $ids );

		foreach ( $ids as $course_id ) {
			$lessons += (int) tutor_utils()->get_completed_lesson_count_by_course( (int) $course_id, $user->ID );
		}
	}

	$since = $user->user_registered ? date_i18n( 'M Y', strtotime( $user->user_registered ) ) : '';

	ob_start();
	?>
	<section class="dash-hero">
		<div class="dash-avatar"><img src="<?php echo esc_url( get_avatar_url( $user->ID, array( 'size' => 168 ) ) ); ?>" alt="" width="84" height="84" decoding="async"></div>
		<div class="dash-hi">
			<div class="eyebrow"><span class="star">&#10022;</span> Your studio</div>
			<h1><?php echo $user->display_name ? esc_html( 'Welcome back, ' . $user->display_name . '.' ) : 'Welcome back.'; ?></h1>
			<p>Pick up where you left off &#8212; your bench is just as you left it.</p>
		</div>
		<div class="dash-stats">
			<div class="dash-stat"><div class="v"><?php echo esc_html( (string) $courses ); ?></div><div class="k">Courses</div></div>
			<div class="dash-stat"><div class="v"><?php echo esc_html( (string) $lessons ); ?></div><div class="k">Lessons done</div></div>
			<?php if ( $since ) : ?>
				<div class="dash-stat"><div class="v" style="font-size:20px;"><?php echo esc_html( $since ); ?></div><div class="k">Member since</div></div>
			<?php endif; ?>
		</div>
	</section>
	<?php

	return ob_get_clean();
}

/**
 * The dashboard as a shortcode, for a page that is NOT Tutor's configured dashboard.
 *
 * On Tutor's own dashboard page this never runs — see the adoption block below — and
 * `[tutor_dashboard]` deliberately renders nothing on a dashboard sub-route, so this is
 * only useful as a landing panel.
 *
 * @return string
 */
function cas_render_dashboard() {
	cas_account_rendered( 'dashboard', true );

	if ( ! is_user_logged_in() ) {
		// Not our job to build a second login. Send them to the one that exists.
		$body = '<div class="auth-stage active" style="text-align:center;">'
			. '<p style="color:var(--muted);margin:0 0 20px;">Sign in to see your studio.</p>'
			. '<a class="btn primary big full" href="' . esc_url( cas_account_login_url() ) . '">Log in <span class="arr" aria-hidden="true">&#8594;</span></a>'
			. '</div>';

		return cas_account_card( 'Your studio', 'Dashboard', '', $body );
	}

	return '<div class="container cas-account-dash">' . cas_account_dash_hero() . cas_account_dashboard_body() . '</div>';
}

add_shortcode( 'cas_dashboard', 'cas_render_dashboard' );

/* -------------------------------------------------------------------------
 * Checkout consent
 * ---------------------------------------------------------------------- */

/**
 * Whether the current request is Tutor's native pay-now submission.
 *
 * Tutor verifies the checkout nonce before its order handler runs. This flag is
 * used only to gate the custom consent control and to attach consent metadata after
 * Tutor has placed the order; it never replaces Tutor's nonce, validation or payment
 * handling.
 *
 * @return bool
 */
function cas_account_checkout_submission() {
	if ( ! isset( $_POST['tutor_action'] ) || ! is_scalar( $_POST['tutor_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	return 'tutor_pay_now' === (string) wp_unslash( $_POST['tutor_action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Whether the checkout consent checkbox was submitted as checked.
 *
 * @return bool
 */
function cas_account_checkout_terms_checked() {
	if ( ! isset( $_POST['agree_to_terms'] ) || ! is_scalar( $_POST['agree_to_terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return false;
	}

	return '1' === (string) wp_unslash( $_POST['agree_to_terms'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}

/**
 * Require the custom consent before Tutor creates a single-course order.
 *
 * Tutor's native GDPR validator only checks configured consent records. The current
 * install has none, so this supported purchase filter closes that server-side gap for
 * the native single-course checkout path before order creation. Tutor still owns the
 * nonce, cart, billing, payment and order handlers.
 *
 * @param bool|WP_Error $can_buy Existing Tutor purchase result.
 *
 * @return bool|WP_Error
 */
function cas_account_require_checkout_terms( $can_buy ) {
	if ( ! cas_account_checkout_submission() || cas_account_checkout_terms_checked() || is_wp_error( $can_buy ) ) {
		return $can_buy;
	}

	// Tutor's own Terms / consent checkbox, when configured, posts `on`, not `1`, and Tutor
	// validates it itself; this control is not printed then, so it must not be demanded.
	if ( cas_account_checkout_has_native_consent() ) {
		return $can_buy;
	}

	return new WP_Error(
		'cas_checkout_terms_required',
		__( 'Please accept the checkout terms to continue.', 'hello-elementor-child' )
	);
}

add_filter( 'tutor_can_purchase_course', 'cas_account_require_checkout_terms' );

/**
 * Whether Tutor already owns a checkout consent control.
 *
 * Do not print a second checkbox when an administrator later configures Tutor's
 * native Terms page or GDPR checkout consent. Tutor remains the source of truth in
 * that case; the empty-copy mechanism below is only the safe current-install path.
 *
 * @return bool
 */
function cas_account_checkout_has_native_consent() {
	if ( function_exists( 'tutor_utils' ) && tutor_utils()->get_toc_page_link() ) {
		return true;
	}

	if ( class_exists( '\\Tutor\\GDPR\\Controllers\\LegalConsent' ) ) {
		$consents = \Tutor\GDPR\Controllers\LegalConsent::get_consent_by_display_key(
			\Tutor\GDPR\Controllers\LegalConsent::DISPLAY_ON_CHECKOUT
		);

		if ( is_array( $consents ) ) {
			foreach ( $consents as $consent ) {
				if ( is_object( $consent ) && ! empty( $consent->is_active ) ) {
					return true;
				}
			}
		}
	}

	return false;
}

/**
 * The checkout consent control mounted inside Tutor's checkout form.
 *
 * The copy span is intentionally empty until the client supplies the final legal
 * wording. The link destination is the existing About page's `#terms` section.
 *
 * @return string
 */
function cas_account_checkout_terms_markup() {
	$terms_url = esc_url( cas_page_url( 'about' ) . '#terms' );
	$checked   = checked( cas_account_checkout_terms_checked(), true, false );

	ob_start();
	?>
	<div class="tutor-form-row tutor-mt-20 cas-member-terms cas-checkout-terms">
		<div class="tutor-input-field">
			<div class="tutor-input-wrapper">
				<input
					id="cas-checkout-terms"
					type="checkbox"
					class="tutor-checkbox tutor-checkbox-md tutor-form-check-input"
					name="agree_to_terms"
					value="1"
					required
					aria-label="<?php esc_attr_e( 'Checkout terms consent', 'hello-elementor-child' ); ?>"
					aria-describedby="cas-checkout-terms-link"
					<?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput ?>
				>
				<label for="cas-checkout-terms">
					<span class="cas-checkout-terms-copy"></span>
					<a id="cas-checkout-terms-link" href="<?php echo $terms_url; ?>">Terms &amp; privacy</a>
				</label>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

/**
 * Print the checkout consent control in Tutor's own checkout form.
 *
 * Called from tutor/ecommerce/checkout.php, inside the order-summary card and outside the
 * element Tutor refreshes when a coupon or country changes, so the tick survives that
 * refresh. It used to hang on `tutor_before_checkout_order_details`, which sits inside the
 * refreshed element. The form, nonce and payment controls remain entirely Tutor-owned.
 *
 * @return void
 */
function cas_account_render_checkout_terms() {
	if ( cas_account_checkout_has_native_consent() ) {
		return;
	}

	echo cas_account_checkout_terms_markup(); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Store the accepted checkout consent after Tutor has created the order.
 *
 * Only the acceptance flag and UTC timestamp are stored. No legal wording or
 * fabricated receipt data is persisted. The action fires from Tutor's order
 * creation path, so this function never creates an order itself.
 *
 * It hangs on `tutor_order_placed` (a new order, free or paid) and `tutor_order_updated` (an unpaid
 * order paid again). Both fire inside the pay-now request, where the ticked box is still in $_POST.
 * It used to hang on `tutor_order_placement_success`, which Tutor fires inside that request only
 * for a single free item; for a paid order it fires when the success page is viewed, a GET with no
 * box, so the consent was never stored (live orders 1 and 2 have none). update_meta() writes one
 * row per key, so the two hooks cannot leave a second record.
 *
 * @param int|string|array|object $order Newly placed Tutor order id/data.
 *
 * @return void
 */
function cas_account_store_checkout_terms_consent( $order ) {
	if ( ! cas_account_checkout_submission() || ! cas_account_checkout_terms_checked() ) {
		return;
	}

	$order_id = is_scalar( $order )
		? absint( $order )
		: ( is_array( $order ) && isset( $order['id'] )
			? absint( $order['id'] )
			: ( is_object( $order ) && isset( $order->id ) ? absint( $order->id ) : 0 ) );

	if ( ! $order_id || ! class_exists( '\\Tutor\\Models\\OrderMetaModel' ) ) {
		return;
	}

	try {
		\Tutor\Models\OrderMetaModel::update_meta( $order_id, '_cas_checkout_terms_consent', '1' );
		\Tutor\Models\OrderMetaModel::update_meta( $order_id, '_cas_checkout_terms_consent_at', current_time( 'mysql', true ) );
	} catch ( \Throwable $exception ) {
		// Consent storage must not interrupt Tutor's already-created order.
		return;
	}
}

add_action( 'tutor_order_placed', 'cas_account_store_checkout_terms_consent' );
add_action( 'tutor_order_updated', 'cas_account_store_checkout_terms_consent' );

/* -------------------------------------------------------------------------
 * [cas_checkout]
 * ---------------------------------------------------------------------- */

/**
 * The purchase-history route used by Tutor's native order confirmation.
 *
 * @return string
 */
function cas_account_checkout_purchase_history_url() {
	if ( class_exists( '\\TUTOR\\Dashboard' ) && method_exists( '\\TUTOR\\Dashboard', 'get_account_page_url' ) ) {
		return (string) \TUTOR\Dashboard::get_account_page_url( 'billing' );
	}

	$dashboard_url = cas_account_dashboard_url();

	return $dashboard_url ? $dashboard_url . '#purchases' : '';
}

/**
 * Whether an order belongs to the currently authenticated student.
 *
 * The order id is supplied in the success URL, so never expose its total or item
 * count from an arbitrary guessed id. Guest/unauthenticated visitors still receive
 * the generic success card, but private order details are omitted until Tutor has
 * an authenticated owner context.
 *
 * @param mixed $order Tutor order data.
 *
 * @return bool
 */
function cas_account_checkout_order_belongs_to_current_user( $order ) {
	if ( ! is_object( $order ) || ! function_exists( 'get_current_user_id' ) ) {
		return false;
	}

	$current_user_id = absint( get_current_user_id() );
	$order_user_id   = isset( $order->user_id ) ? absint( $order->user_id ) : 0;

	if ( ! $order_user_id && isset( $order->student ) ) {
		if ( is_object( $order->student ) && isset( $order->student->id ) ) {
			$order_user_id = absint( $order->student->id );
		} elseif ( is_array( $order->student ) && isset( $order->student['id'] ) ) {
			$order_user_id = absint( $order->student['id'] );
		}
	}

	return $current_user_id > 0 && $order_user_id > 0 && $current_user_id === $order_user_id;
}

/**
 * Render Tutor's native order-placement success state inside the account shell.
 *
 * Tutor has already completed its native nonce, validation, cart and payment flow
 * before loading this template. The order summary is read from OrderModel only;
 * missing data is omitted rather than replaced with invented receipt values.
 *
 * @param int|string $order_id Order id supplied by Tutor.
 * @param string     $order_status Placement status supplied by Tutor.
 *
 * @return string
 */
function cas_account_render_checkout_success( $order_id, $order_status = 'success' ) {
	if ( 'success' !== (string) $order_status || ! absint( $order_id ) ) {
		return '';
	}

	$order = false;

	if ( class_exists( '\\Tutor\\Models\\OrderModel' ) ) {
		try {
			$order = ( new \Tutor\Models\OrderModel() )->get_order_by_id( absint( $order_id ) );
		} catch ( \Throwable $exception ) {
			$order = false;
		}
	}

	$has_receipt = $order
		&& is_object( $order )
		&& isset( $order->id, $order->items, $order->total_price )
		&& absint( $order->id ) === absint( $order_id )
		&& cas_account_checkout_order_belongs_to_current_user( $order )
		&& is_countable( $order->items )
		&& is_numeric( $order->total_price );

	$purchase_history_url = cas_account_checkout_purchase_history_url();
	$dashboard_url        = cas_account_dashboard_url();
	$courses_url          = cas_account_courses_url();
	$terms_url            = esc_url( cas_page_url( 'about' ) . '#terms' );

	ob_start();
	?>
	<section class="cas-checkout-confirmation" data-stage="3" tabindex="-1">
		<div class="finish-card show" tabindex="-1" aria-labelledby="cas-checkout-success-title" style="max-width:620px;margin:10px auto 80px;">
			<div class="fc-star" aria-hidden="true">&#10022;</div>
			<h2 id="cas-checkout-success-title">Order placed</h2>
			<?php if ( $has_receipt ) : ?>
				<p id="fc-line">
					Your receipt is saved to
					<?php if ( $purchase_history_url ) : ?>
						<a href="<?php echo esc_url( $purchase_history_url ); ?>">Purchase history</a>
					<?php else : ?>
						Purchase history
					<?php endif; ?>
					in your dashboard.
				</p>
			<?php endif; ?>
			<?php if ( $has_receipt ) : ?>
				<div class="cert" aria-label="Order summary">
					<div class="c1">Crystal's Art School</div>
					<div class="c2">Order <span id="order-no"><?php echo esc_html( (string) $order->id ); ?></span></div>
					<div class="c3">
						<span id="order-count"><?php echo esc_html( (string) count( $order->items ) ); ?></span>
						<span id="order-noun"><?php echo 1 === count( $order->items ) ? 'item' : 'items'; ?></span>
						&middot;
						<span id="order-total">
							<?php
							if ( function_exists( 'tutor_get_formatted_price' ) ) {
								echo wp_kses_post( tutor_get_formatted_price( (float) $order->total_price ) );
							}
							?>
						</span>
					</div>
				</div>
			<?php endif; ?>
			<div class="fc-btns">
				<?php if ( $dashboard_url ) : ?>
					<a class="btn primary big" href="<?php echo esc_url( $dashboard_url ); ?>">Go to my courses</a>
				<?php endif; ?>
				<?php if ( $courses_url ) : ?>
					<a class="btn teal" href="<?php echo esc_url( $courses_url ); ?>">Keep browsing</a>
				<?php endif; ?>
			</div>
			<p class="cas-checkout-terms-footer"><a href="<?php echo $terms_url; ?>">Terms &amp; privacy</a></p>
		</div>
	</section>
	<?php
	$success_markup = ob_get_clean();

	return cas_account_checkout_shell( $success_markup, 'success' );
}

/**
 * The basket and checkout shell.
 *
 * The banner is written out here rather than taken from `cas_page_hero()` because that
 * helper always emits Logo2 and DD §1.4.5 excludes these pages from it. Everything else
 * — the eyebrow, the title, its clamp, the lead sentence, the three-step strip — is the
 * prototype's `Checkout.dc.html` verbatim. The prototype keeps one banner across all
 * three of its stages, which is why Tutor's separate cart page gets the same one.
 *
 * @param string $inner Already-rendered markup for the stage (Tutor's).
 * @param string $stage 'cart', 'checkout' or 'success'.
 *
 * @return string
 */
function cas_account_checkout_shell( $inner, $stage ) {
	$is_cart    = ( 'cart' === $stage );
	$is_success = ( 'success' === $stage );

	// Each step renders aria-current="step" for the live stage and
	// aria-current="false" for the remaining stages.
	$step_one_state   = $is_cart ? 'active' : 'done';
	$step_two_state   = $is_cart ? '' : ( $is_success ? 'done' : 'active' );
	$step_three_state = $is_success ? 'active' : '';

	ob_start();
	?>
	<header class="page-hero cas-account-hero">
		<div class="container">
			<div class="eyebrow"><span class="star">&#10022;</span> Your basket</div>
			<h1 class="marker-title" style="font-size:clamp(32px,4vw,52px);color:var(--banner-ink);">Checkout</h1>
		</div>
	</header>
	<div class="container page-lead-wrap"><p class="page-lead">Review what&rsquo;s in your basket, then we&rsquo;ll take it from here.</p></div>
	<div class="container cas-account-body">
		<div class="steps">
			<div class="step <?php echo esc_attr( $step_one_state ); ?>" aria-current="<?php echo $is_cart ? 'step' : 'false'; ?>"><span class="sn">1</span> Basket</div>
			<span class="step-arrow" aria-hidden="true">&#8594;</span>
			<div class="step <?php echo esc_attr( $step_two_state ); ?>" aria-current="<?php echo ( ! $is_cart && ! $is_success ) ? 'step' : 'false'; ?>"><span class="sn">2</span> Details &amp; payment</div>
			<span class="step-arrow" aria-hidden="true">&#8594;</span>
			<div class="step <?php echo esc_attr( $step_three_state ); ?>" aria-current="<?php echo $is_success ? 'step' : 'false'; ?>"><span class="sn">3</span> Confirmation</div>
		</div>
		<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php

	return ob_get_clean();
}


/**
 * Basket / checkout as a shortcode.
 *
 * @param array $atts stage: cart|checkout|auto.
 *
 * @return string
 */
function cas_render_checkout( $atts ) {
	$atts  = shortcode_atts( array( 'stage' => 'auto' ), $atts, 'cas_checkout' );
	$stage = $atts['stage'];

	if ( 'auto' === $stage ) {
		$key   = cas_account_current_key();
		$stage = ( 'cart' === $key ) ? 'cart' : 'checkout';
	}

	$stage = ( 'cart' === $stage ) ? 'cart' : 'checkout';
	cas_account_rendered( $stage, true );

	$tag   = ( 'cart' === $stage ) ? 'tutor_cart' : 'tutor_checkout';
	$inner = shortcode_exists( $tag ) ? do_shortcode( '[' . $tag . ']' ) : '';

	if ( '' === trim( (string) $inner ) ) {
		return '';
	}

	return cas_account_checkout_shell( $inner, $stage );
}

add_shortcode( 'cas_checkout', 'cas_render_checkout' );

/* -------------------------------------------------------------------------
 * Adoption of Tutor's own pages
 *
 * Tutor replaces `the_content` on its cart, checkout and registration pages, so a
 * shortcode placed in the content is never reached. This runs after that replacement
 * and wraps what Tutor produced. It never edits Tutor's markup and never changes where
 * a form posts.
 * ---------------------------------------------------------------------- */

add_filter( 'the_content', 'cas_account_adopt_content', 20 );

/**
 * @param string $content Post content, already replaced by Tutor on its own pages.
 *
 * @return string
 */
function cas_account_adopt_content( $content ) {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$key = cas_account_current_key();

	if ( ! $key || cas_account_rendered( $key ) ) {
		return $content;
	}

	switch ( $key ) {
		case 'cart':
		case 'checkout':
			cas_account_rendered( $key, true );

			return cas_account_checkout_shell( $content, $key );

		case 'register':
			// cas_render_signup() decides for itself whether to use Tutor's form or
			// to replace the plugin's "Access Denied" page with the closed state.
			$card = cas_render_signup( $content );

			return $card ? $card : $content;
	}

	return $content;
}

/*
 * The logged-in dashboard is not a page render at all — Tutor prints its own document
 * — so the site header and footer have to be put back by hand. The native landing markup
 * is buffered between Tutor's hooks and replaced only on the logged-in home route; every
 * dashboard sub-route keeps Tutor's original markup and behavior.
 */
/**
 * A logged-out visitor who reaches the dashboard is sent to the site's own Log in page.
 *
 * Without this, Tutor prints its own stock sign-in form on that route - a second login,
 * in a different voice, inside this site's header and footer, while the designed one
 * sits at its own address. cas_render_dashboard() already states the rule for the
 * shortcode path: building a second login is not this theme's job. This applies the
 * same rule to Tutor's own template, which is what the dashboard page actually uses.
 *
 * Signing in lands on the dashboard, because that is already this site's default
 * destination after login.
 *
 * @return void
 */
add_action(
	'template_redirect',
	function () {
		// Tutor marks retrieve-password public for request and reset-token forms.
		if ( is_user_logged_in() || ! function_exists( 'tutor_utils' ) || cas_account_is_retrieve_password() ) {
			return;
		}

		$dashboard = (int) tutor_utils()->get_option( 'tutor_dashboard_page_id' );

		if ( ! $dashboard || ! is_page( $dashboard ) ) {
			return;
		}

		$login = cas_account_login_url();

		if ( ! $login ) {
			return;
		}

		wp_safe_redirect( $login );
		exit;
	}
);

add_action( 'tutor_dashboard/before/wrap', 'cas_account_dashboard_before' );
add_action( 'tutor_dashboard/after/wrap', 'cas_account_dashboard_after' );

/**
 * @return void
 */
function cas_account_dashboard_before() {
	if ( cas_account_rendered( 'dashboard' ) ) {
		return;
	}

	cas_account_rendered( 'dashboard', true );
	cas_account_render_dashboard_shell_before( true );

	if ( cas_account_dashboard_should_render_studio() ) {
		echo '<div class="container cas-account-dash">'; // phpcs:ignore WordPress.Security.EscapeOutput
		ob_start();
		cas_account_rendered( 'dashboard-native-body', true );
	}
}

/**
 * @return void
 */
function cas_account_dashboard_after() {
	$native = '';
	if ( cas_account_rendered( 'dashboard-native-body' ) && ob_get_level() > 0 ) {
		$native = (string) ob_get_clean();
	}

	if ( cas_account_dashboard_should_render_studio() && cas_account_rendered( 'dashboard-native-body' ) ) {
		echo cas_account_dashboard_body(); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	} else {
		echo $native; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	cas_account_render_dashboard_shell_after();
}

/* -------------------------------------------------------------------------
 * Body classes and assets
 * ---------------------------------------------------------------------- */

add_filter(
	'body_class',
	function ( $classes ) {
		$key = cas_account_current_key();

		if ( $key ) {
			$classes[] = 'cas-account-page';
			$classes[] = 'cas-account-' . $key;

			if ( 'checkout-success' === $key ) {
				// The success route is a home URL, but it still needs the checkout
				// full-bleed rules and the checkout footer variant.
				$classes[] = 'cas-account-checkout';
			}

			if ( cas_account_is_retrieve_password() ) {
				$classes[] = 'cas-account-retrieve-password';
			}
		} elseif ( cas_account_has_shortcode() ) {
			$classes[] = 'cas-account-page';
		}

		return $classes;
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! cas_account_current_key() && ! cas_account_has_shortcode() ) {
			return;
		}

		wp_register_style( 'cas-account', false, array( 'cas-industrial-3' ), null );
		wp_enqueue_style( 'cas-account' );
		wp_add_inline_style( 'cas-account', cas_account_css() );
	},
	27
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( 'dashboard' !== cas_account_current_key() && ! cas_account_has_shortcode() ) {
			return;
		}

		$script = get_stylesheet_directory() . '/js/cas-dashboard.js';
		if ( ! is_readable( $script ) ) {
			return;
		}

		wp_enqueue_script(
			'cas-dashboard',
			get_stylesheet_directory_uri() . '/js/cas-dashboard.js',
			array(),
			(string) filemtime( $script ),
			true
		);
	},
	28
);

/**
 * The dashboard is Tutor's own document: Elementor's Theme Builder templates never
 * render there, so Elementor's config never prints — but its frontend scripts are
 * still enqueued, and the first one to run throws
 * `ReferenceError: elementorFrontendConfig is not defined` on every dashboard visit.
 * Nothing Elementor renders appears on that page, so the scripts are dead weight;
 * drop them rather than feed them a config for templates that do not exist.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( 'dashboard' !== cas_account_current_key() ) {
			return;
		}

		$handles = array(
			'elementor-frontend',
			'elementor-frontend-modules',
			'elementor-common',
			'elementor-common-modules',
			'elementor-pro-frontend',
			'elementor-pro-webpack-runtime',
			'elementor-sticky',
		);

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
		}
	},
	100
);

/**
 * The stylesheet.
 *
 * Two jobs, and only two. First, take hello-elementor's 1140px box off `.site-main` so
 * the wood banner can go full bleed, and hide the parent theme's plain `<h1>Cart</h1>`
 * now that a real banner supplies the heading. Second, remap Tutor's own design tokens
 * onto the site's palette — that is a dozen custom properties rather than a rewrite of
 * Tutor's components, so it survives a plugin update in a way selector-by-selector
 * overrides would not.
 *
 * @return string
 */
function cas_account_css() {
	return <<<'CSS'
/* hello-elementor boxes .site-main at 1140px with
   `body:not([class*=elementor-page-]) .site-main`, which matches at the same specificity
   as anything written here and whose load order against this inline block is not
   guaranteed. That is what the !important is for, and it is the only one in the file. */
body.cas-account-page .site-main { max-width: none !important; width: 100%; }
/* only the two pages that carry a full-bleed banner lose the side padding. The auth
   card pages keep it: .auth-wrap has no horizontal padding of its own, so on a phone
   the card would otherwise sit flush against both screen edges. */
body.cas-account-cart .site-main,
body.cas-account-checkout .site-main { padding-inline: 0 !important; }
body.cas-account-page .site-main > .page-header { display: none; }
body.cas-account-page .page-content { margin: 0; }
/* the parent theme underlines every link inside .page-content */
body.cas-account-page .page-content a { text-decoration: none; }

.cas-account-shell { padding-top: 40px; }
.cas-account-body { padding-bottom: 80px; }
.cas-account-dash { padding-top: 26px; padding-bottom: 8px; }
/* NOTE, not a bug: `.page-hero .container` reserves 272px on the right for Logo2, and
   DD 1.4.5 excludes these pages from Logo2, so that gutter stays empty above 700px.
   The prototype's own Checkout.dc.html renders exactly the same way. Left alone
   deliberately rather than overridden, so the two stay identical. */

/* Tutor's tokens, wearing the site's palette. Set on <body> so they reach every
   Tutor control on the page without naming one of them. */
body.cas-account-page,
body.tutor-screen-frontend-dashboard {
  --tutor-surface-base: var(--cream);
  --tutor-surface-l1: var(--surface);
  --tutor-surface-l1-hover: var(--warm-white);
  --tutor-surface-l2: var(--warm-white);
  --tutor-surface-l2-hover: var(--paper-2);
  --tutor-surface-l3: var(--surface);
  --tutor-surface-sidebar-l1: var(--surface);
  --tutor-surface-brand-primary: var(--coral-fill);
  --tutor-surface-dark: var(--ink);
  --tutor-text-primary: var(--ink);
  --tutor-text-secondary: var(--muted);
  --tutor-text-subdued: var(--muted);
  --tutor-text-light: var(--muted);
  --tutor-body-color: var(--ink);
  --tutor-text-primary-inverse: var(--coral-fill-ink);
  --tutor-text-brand: var(--coral-ink);
  --tutor-text-brand-hover: var(--coral-deep);
  --tutor-text-critical: var(--danger);
  --tutor-border-idle: var(--line);
  --tutor-border-idle2: var(--line);
  --tutor-border-hover: var(--coral-ink);
  --tutor-border-brand: var(--coral-ink);
  --tutor-border-error: var(--danger);
  --tutor-icon-brand: var(--coral-ink);
  --tutor-icon-idle: var(--muted);
  --tutor-icon-subdued: var(--muted);
  --tutor-icon-hover: var(--ink);
  --tutor-button-primary: var(--coral-fill);
  --tutor-button-primary-hover: var(--coral-deep);
  --tutor-button-primary-focused: var(--coral-deep);
  --tutor-button-destructive: var(--danger);
  --tutor-color-primary: var(--coral-fill);
  --tutor-color-secondary: var(--marine);
  --tutor-color-danger: var(--danger);
  --tutor-color-muted: var(--muted);
  --tutor-color-subdued: var(--muted);
}

/* the primary button, in the site's brushed copper — the recipe is .btn.primary's */
body.cas-account-page .tutor-btn-primary,
body.tutor-screen-frontend-dashboard .tutor-btn-primary {
  background-color: #8a5528;
  background-image: linear-gradient(118deg, rgba(255,236,210,.14), transparent 42%), repeating-linear-gradient(95deg, rgba(0,0,0,.12) 0 1px, transparent 1px 3px), var(--copper-metal);
  color: #f8ecd8;
  -webkit-text-fill-color: #f8ecd8;
  border-color: transparent;
  border-radius: var(--radius-pill);
  font-family: var(--font-cond);
  font-weight: 500;
  letter-spacing: var(--tracking-label);
  text-transform: uppercase;
  box-shadow: var(--shadow-sm), inset 0 1px 0 rgba(255,236,210,.45), inset 0 -1px 0 rgba(60,30,10,.4);
}
body.cas-account-page .tutor-btn-primary:hover,
body.tutor-screen-frontend-dashboard .tutor-btn-primary:hover { filter: saturate(1.18); }

body.cas-account-page .tutor-btn,
body.tutor-screen-frontend-dashboard .tutor-btn {
  font-family: var(--font-cond);
  letter-spacing: var(--tracking-label);
  border-radius: var(--radius-pill);
}

/* form controls, matching .co-field input */
body.cas-account-page .tutor-form-control,
body.cas-account-page .tutor-input,
body.cas-account-page .tutor-form-select,
body.tutor-screen-frontend-dashboard .tutor-form-control,
body.tutor-screen-frontend-dashboard .tutor-input,
body.tutor-screen-frontend-dashboard .tutor-form-select {
  background: var(--cream);
  border: 1px solid var(--line);
  border-radius: var(--r-sharp);
  color: var(--ink);
  -webkit-text-fill-color: var(--ink);
  font-family: var(--font-body);
}
body.cas-account-page .tutor-form-control::placeholder,
body.tutor-screen-frontend-dashboard .tutor-form-control::placeholder { color: var(--muted); opacity: 1; }

body.cas-account-page .tutor-card,
body.tutor-screen-frontend-dashboard .tutor-card {
  background: var(--surface);
  border-color: var(--line);
  border-radius: var(--radius-card);
  box-shadow: var(--shadow-sm);
  color: var(--ink);
}

/* Tutor's own login card — the fallback a logged-out visitor meets on /dashboard/.
   It keeps Tutor's heading and its form; only the surface is ours. */
body.cas-account-page .tutor-login-wrap,
body.tutor-screen-frontend-dashboard .tutor-login-wrap {
  background: var(--surface);
  border: 0;
  border-radius: var(--radius-card);
  box-shadow: var(--shadow);
}

/* the auth card holds Tutor's form, which brings its own field widths */
.cas-account-form .tutor-form-control,
.cas-account-form .tutor-input { width: 100%; }
.cas-account-form .tutor-btn-block { width: 100%; }

/* The /login/ override cannot assume Tutor's Alpine runtime is present. Keep the
   fallback password native, then let its small vanilla enhancement use this control. */
body.cas-account-page .cas-password-field { position: relative; }
body.cas-account-page .cas-password-toggle {
  align-items: center;
  background: transparent;
  border: 0;
  color: var(--muted);
  cursor: pointer;
  display: inline-flex;
  height: 36px;
  justify-content: center;
  padding: 0;
  position: absolute;
  right: 9px;
  bottom: 8px;
  width: 36px;
}
body.cas-account-page .cas-password-toggle:hover { color: var(--ink); }
body.cas-account-page .cas-password-toggle:focus-visible {
  border-radius: var(--r-sharp);
  outline: var(--focus-ring-w) solid var(--focus-ring);
  outline-offset: 2px;
}
body.cas-account-page .cas-password-toggle[hidden] { display: none; }
body.cas-account-page .cas-password-toggle svg { display: block; }

/* Tutor's Alert component has no guaranteed theme stylesheet on the shortcode
   login page. Give its server-rendered errors one full-width, wrapping surface so
   long messages stay above the remember/forgot row. */
body.cas-account-page .cas-login-errors {
  display: grid;
  gap: 10px;
  margin: 0 0 18px;
  width: 100%;
}
body.cas-account-page .cas-login-errors .cas-login-error {
  background: var(--surface);
  border: 1px solid var(--danger);
  border-left: 4px solid var(--danger);
  border-radius: var(--r-sharp);
  box-sizing: border-box;
  color: var(--ink);
  margin: 0 !important;
  padding: 12px 14px;
  width: 100%;
}
body.cas-account-page .cas-login-errors .cas-login-error .tutor-alert-content {
  align-items: flex-start;
  display: flex;
  gap: 10px;
  min-width: 0;
}
body.cas-account-page .cas-login-errors .cas-login-error .tutor-alert-icon {
  color: var(--danger);
  flex: 0 0 auto;
}
body.cas-account-page .cas-login-errors .cas-login-error .tutor-alert-text {
  color: var(--ink);
  min-width: 0;
  overflow-wrap: anywhere;
}

/* Tutor's retrieve-password template supplies a card of its own. The auth card is
   now the surface, so flatten only that inner shell and keep Tutor's form intact. */
body.cas-account-page .cas-retrieve-password-form .tutor-card {
  background: transparent;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  margin: 0 !important;
  max-width: none !important;
}
body.cas-account-page .cas-retrieve-password-form .tutor-card > form { padding: 0 !important; }
.cas-account-back-link { margin-top: 12px; }

/* Tutor's registration template supplies a second card around its form. The account
   card is the only surface here, so flatten the plugin wrapper without changing its
   fields, handlers or nonce. */
body.cas-account-page .cas-account-form #tutor-registration-wrap.tutor-card {
  background: transparent;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  margin: 0 !important;
  max-width: none !important;
  padding: 0 !important;
}
body.cas-account-page .cas-account-form #tutor-registration-wrap.tutor-card > form { padding: 0 !important; }

/* Keep the site's single, /login/ registration link. Tutor's form repeats its own
   dashboard link immediately below the submit button. */
body.cas-account-page .cas-account-form #tutor-registration-form > div > .tutor-flex.tutor-items-center.tutor-justify-center { display: none; }

.cas-member-terms { margin-top: 4px; }
.cas-member-terms .tutor-input-wrapper { align-items: flex-start; }
.cas-member-terms .tutor-input-wrapper label { line-height: 1.5; }
.cas-member-terms a { color: var(--coral-ink); text-decoration: underline; }

/* Keep the site's studio layout and the native Tutor routes in the same material language. */
body.cas-account-page .tutor-dashboard-layout,
body.tutor-screen-frontend-dashboard .tutor-dashboard-layout {
  max-width: 1440px;
  margin: 0 auto;
  background: var(--cream);
  color: var(--ink);
}
body.cas-account-page .tutor-dashboard-sidebar,
body.tutor-screen-frontend-dashboard .tutor-dashboard-sidebar {
  background: var(--surface);
  border-right: 1px solid var(--line);
}
body.cas-account-page .tutor-dashboard-main,
body.tutor-screen-frontend-dashboard .tutor-dashboard-main {
  background: var(--cream);
}
body.cas-account-page .tutor-dashboard-header,
body.tutor-screen-frontend-dashboard .tutor-dashboard-header {
  background: var(--surface);
  border-bottom: 1px solid var(--line);
}
body.cas-account-page .tutor-dashboard-body,
body.tutor-screen-frontend-dashboard .tutor-dashboard-body {
  background: transparent;
  color: var(--ink);
}
body.cas-account-page .tutor-dashboard-page,
body.tutor-screen-frontend-dashboard .tutor-dashboard-page {
  max-width: 1180px;
  margin: 0 auto;
  padding: 30px clamp(18px, 4vw, 52px) 72px;
}
body.cas-account-page .tutor-dashboard-nav-mobile,
body.tutor-screen-frontend-dashboard .tutor-dashboard-nav-mobile {
  background: var(--surface);
  border-top-color: var(--line);
  border-bottom-color: var(--line);
}
body.cas-account-page .tutor-account-page-wrapper,
body.cas-account-page .tutor-dashboard-isolated-page-wrapper {
  max-width: 1180px;
  margin: 0 auto;
  padding: 30px clamp(18px, 4vw, 52px) 72px;
}
body.cas-account-page .tutor-account-page-wrapper .tutor-card,
body.cas-account-page .tutor-dashboard-isolated-page-wrapper .tutor-card {
  box-shadow: var(--shadow-sm);
}
.cas-studio-dashboard { padding-bottom: 72px; }
.cas-studio-dashboard .dash-panel[hidden] { display: none; }
.cas-studio-dashboard .tutor-panel-host { overflow: hidden; }
.cas-studio-dashboard .tutor-panel-host > .tutor-card {
  margin: 0;
  border: 0;
  box-shadow: none;
}
.cas-studio-dashboard .dash-panel-head {
  align-items: flex-end;
  display: flex;
  justify-content: space-between;
  margin-bottom: 22px;
}
.cas-studio-dashboard .dash-panel-head h2,
.cas-studio-dashboard .dash-subhead h3 { margin-bottom: 0; }
.cas-studio-dashboard .dash-subhead { margin: 34px 0 16px; }
.cas-studio-dashboard .dash-rail-foot { margin-top: auto; padding-top: 24px; }
.cas-studio-dashboard .dash-logout {
  color: var(--muted);
  display: inline-flex;
  font-family: var(--font-cond);
  gap: 8px;
  letter-spacing: var(--tracking-label);
  text-transform: uppercase;
}
.cas-studio-dashboard .dash-logout:hover { color: var(--coral-ink); }
.cas-studio-dashboard .dash-recommendation {
  color: inherit;
  text-decoration: none;
}
.cas-studio-dashboard .dash-recommendation .mc-body {
  align-items: center;
  display: grid;
  gap: 5px;
  grid-template-columns: 1fr auto;
}
.cas-studio-dashboard .dash-recommendation .mc-body .eyebrow { grid-column: 1 / -1; }
.cas-studio-dashboard .progress-summary p { color: var(--muted); margin: 14px 0 0; }
.cas-studio-dashboard .prog-list { display: grid; gap: 22px; margin-top: 18px; }
.cas-studio-dashboard .prog-course a { color: var(--ink); font-weight: 600; }
/* Profile (#125): Tutor's own form, without the public-page cover photo (a student has no
   public page) and without the "Account" heading the panel already says. */
.cas-studio-dashboard #panel-account .tutor-account-cover-photo { display: none; }
.cas-studio-dashboard #panel-account form > div:first-child > h5 { display: none; }
.cas-studio-dashboard #panel-account .tutor-account-avatar-wrapper { position: static; margin: 0 0 6px; transform: none; }
.cas-studio-dashboard #panel-account .tutor-h5 { font-family: var(--font-marker); font-weight: 600; color: var(--ink); }
/* Purchases (#125): the status badge and the receipt cell under the prototype's table. */
.cas-studio-dashboard .dash-table .ord-status { margin-top: 4px; }
.cas-studio-dashboard .dash-table td a { color: var(--coral-ink); font-weight: 600; }
.cas-studio-dashboard .dash-card .mycourse-grid { margin-top: 0; }
.cas-studio-dashboard .pbar .pfill { display: block; }

@media (max-width: 880px) {
  body.cas-account-page .tutor-dashboard-layout,
  body.tutor-screen-frontend-dashboard .tutor-dashboard-layout { display: block; }
  body.cas-account-page .tutor-dashboard-sidebar,
  body.tutor-screen-frontend-dashboard .tutor-dashboard-sidebar {
    border-bottom: 1px solid var(--line);
    border-right: 0;
  }
  body.cas-account-page .tutor-dashboard-page,
  body.tutor-screen-frontend-dashboard .tutor-dashboard-page,
  body.cas-account-page .tutor-account-page-wrapper,
  body.cas-account-page .tutor-dashboard-isolated-page-wrapper {
    padding-inline: 18px;
  }
}
CSS;
}

/**
 * No admin toolbar for anyone who cannot manage options (#140).
 *
 * WordPress gives every account `show_admin_bar_front = true` when it is created, and nothing
 * on this site filtered it, so a student saw "About WordPress", "Howdy, ..." and the search
 * box above the studio header. The user setting is per account; this rule is for everyone, so
 * a student never has to be edited. Administrators keep the toolbar, including their own
 * "show toolbar" preference.
 */
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return current_user_can( 'manage_options' ) ? $show : false;
	},
	20
);
