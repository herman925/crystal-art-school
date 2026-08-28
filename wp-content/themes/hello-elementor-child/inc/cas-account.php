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
 * Which of Tutor's pages the request is on, or '' for anything else.
 *
 * @return string
 */
function cas_account_current_key() {
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

/* -------------------------------------------------------------------------
 * [cas_signup]
 * ---------------------------------------------------------------------- */

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

	$inner = shortcode_exists( 'tutor_dashboard' ) ? do_shortcode( '[tutor_dashboard]' ) : '';

	return '<div class="container cas-account-dash">' . cas_account_dash_hero() . $inner . '</div>';
}

add_shortcode( 'cas_dashboard', 'cas_render_dashboard' );

/* -------------------------------------------------------------------------
 * [cas_checkout]
 * ---------------------------------------------------------------------- */

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
 * @param string $stage 'cart' or 'checkout'.
 *
 * @return string
 */
function cas_account_checkout_shell( $inner, $stage ) {
	$is_cart = ( 'cart' === $stage );

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
			<div class="step <?php echo $is_cart ? 'active' : 'done'; ?>"><span class="sn">1</span> Basket</div>
			<span class="step-arrow" aria-hidden="true">&#8594;</span>
			<div class="step <?php echo $is_cart ? '' : 'active'; ?>"><span class="sn">2</span> Details &amp; payment</div>
			<span class="step-arrow" aria-hidden="true">&#8594;</span>
			<div class="step"><span class="sn">3</span> Confirmation</div>
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
 * — so the site header and footer have to be put back by hand. `did_action('get_header')`
 * distinguishes that case from every other: Tutor's logged-out login template calls
 * get_header() (hello-elementor is not a block theme), and the shortcode path is inside
 * a normal template, so in both of those the header is already on the page.
 */
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

	if ( ! did_action( 'get_header' ) && shortcode_exists( 'cas_header' ) ) {
		echo do_shortcode( '[cas_header]' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	echo '<main id="main" class="site-main">'; // phpcs:ignore WordPress.Security.EscapeOutput

	$hero = cas_account_dash_hero();

	if ( $hero ) {
		echo '<div class="container cas-account-dash">' . $hero . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}

/**
 * @return void
 */
function cas_account_dashboard_after() {
	echo '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput

	if ( ! did_action( 'get_header' ) && shortcode_exists( 'cas_footer' ) ) {
		echo do_shortcode( '[cas_footer]' ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
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

/* Tutor's retrieve-password template supplies a card of its own. The auth card is
   now the surface, so flatten only that inner shell and keep Tutor's form intact. */
.cas-retrieve-password-form > .tutor-card {
  background: transparent;
  border: 0;
  border-radius: 0;
  box-shadow: none;
  margin: 0 !important;
  max-width: none !important;
}
.cas-retrieve-password-form > .tutor-card > form { padding: 0 !important; }
.cas-account-back-link { margin-top: 12px; }
CSS;
}
