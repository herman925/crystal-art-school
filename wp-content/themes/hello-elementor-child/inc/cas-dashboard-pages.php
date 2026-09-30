<?php
/**
 * Student dashboard subpages (#125): the routes, and the panels the routes now open.
 *
 * The studio page (cas_account_dashboard_body) is the student's one surface, as in
 * Dashboard.dc.html. Tutor's own subpages (Courses, Billing, Profile, Settings) each print a
 * second top bar and sidebar of their own, so a student's request for one of them is sent to
 * the studio with the matching tab open. The old addresses keep working: a link in an email
 * or in Tutor's own screens lands on the right tab.
 *
 * Quiz attempts and reviews are not part of the launch (#124, owner decision 2026-09-30).
 * Their tab, their links and their addresses all lead back to the studio.
 *
 * Nothing here changes how Tutor stores or handles orders, wishlists, billing or passwords:
 * the purchases table only reads Tutor's orders, the wishlist only reads Tutor's wishlist,
 * the security card is Tutor's own template, and the country is Tutor's billing country.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The studio tab a Tutor dashboard request belongs to.
 *
 * @return string A panel id (`panel-courses`), `blocked`, or '' when the request is not one
 *                the studio replaces.
 */
function cas_dashboard_route_target() {
	$page = (string) get_query_var( 'tutor_dashboard_page' );
	$sub  = (string) get_query_var( 'tutor_dashboard_sub_page' );

	if ( 'quiz-attempts' === $page || 'my-quiz-attempts' === $page || 'my-quiz-attempts' === $sub ) {
		return 'blocked';
	}

	if ( 'account' === $page ) {
		$map = array(
			'reviews'  => 'blocked',
			'billing'  => 'panel-purchases',
			'settings' => 'panel-settings',
			'profile'  => 'panel-account',
			''         => 'panel-account',
		);

		return $map[ $sub ] ?? '';
	}

	if ( 'reviews' === $page ) {
		return 'blocked';
	}

	if ( 'courses' === $page ) {
		if ( 'wishlist' === $sub ) {
			return 'panel-wishlist';
		}

		return in_array( $sub, array( '', 'active-courses', 'completed-courses' ), true ) ? 'panel-courses' : '';
	}

	if ( 'my-courses' === $page ) {
		return 'panel-courses';
	}

	return '';
}

add_action(
	'template_redirect',
	function () {
		if ( ! is_user_logged_in() || wp_doing_ajax() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return;
		}

		if ( ! function_exists( 'tutor_utils' ) || ! class_exists( 'TUTOR\\User' ) || ! \TUTOR\User::is_student_view() ) {
			return;
		}

		$target = cas_dashboard_route_target();

		if ( '' === $target ) {
			return;
		}

		$dashboard = tutor_utils()->tutor_dashboard_url();

		wp_safe_redirect( 'blocked' === $target ? $dashboard : $dashboard . '#' . $target );
		exit;
	},
	5
);

/* Tutor's Courses page offers Quiz Attempts as a tab; the route above already blocks it for
   students, this keeps the tab off every other screen that prints the list. */
add_filter(
	'tutor_enrolled_courses_page_tabs',
	function ( $tabs ) {
		unset( $tabs['courses/my-quiz-attempts'] );

		return $tabs;
	}
);

/**
 * The student's Tutor orders as the prototype's purchases table.
 *
 * The receipt cell is Tutor's own: the pay button for an order still waiting for payment
 * and, where Tutor Pro's invoice add-on is on, its real invoice button.
 *
 * @param int $user_id User id.
 * @return string
 */
function cas_dashboard_purchases_html( $user_id ) {
	$orders = array();

	if ( function_exists( 'tutor_utils' ) && 'free' !== tutor_utils()->get_option( 'monetize_by' ) ) {
		$response = tutor_utils()->get_orders_by_user_id(
			$user_id,
			array(
				'status' => 'all',
				'limit'  => 50,
				'offset' => 0,
				'order'  => 'DESC',
			)
		);
		$orders   = $response->results ?? array();
	}

	if ( empty( $orders ) ) {
		return '<p class="hand-note">Purchases appear here after your first order.</p>';
	}

	ob_start();
	?>
	<table class="dash-table">
		<caption class="screen-reader-text">Orders, newest first.</caption>
		<thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Item</th><th scope="col">Amount</th><th scope="col">Receipt</th></tr></thead>
		<tbody>
		<?php foreach ( $orders as $order ) : ?>
			<?php
			ob_start();
			\Tutor\Models\OrderModel::render_pay_button( $order );
			do_action( 'tutor_dashboard_invoice_button', $order ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
			$receipt = trim( (string) ob_get_clean() );
			?>
			<tr>
				<td>#<?php echo esc_html( (string) $order->id ); ?></td>
				<td><?php echo esc_html( \Tutor\Helpers\DateTimeHelper::get_gmt_to_user_timezone_date( $order->created_at_gmt ) ); ?></td>
				<td>
					<?php foreach ( \Tutor\Models\OrderModel::get_order_history_titles( $order ) as $title ) : ?>
						<div class="ord-item"><span class="t"><?php echo esc_html( $title ); ?></span></div>
					<?php endforeach; ?>
				</td>
				<td>
					<span class="amt"><?php echo wp_kses( tutor_get_formatted_price( $order->total_price ), tutor_price_allowed_html() ); ?></span>
					<div class="ord-status"><?php \Tutor\Helpers\ComponentHelper::render_status_badge( $order->order_status ); ?></div>
				</td>
				<td><?php echo '' !== $receipt ? $receipt : '&mdash;'; // phpcs:ignore WordPress.Security.EscapeOutput -- Tutor's own buttons. ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php

	return (string) ob_get_clean();
}

/**
 * The student's wishlist as course cards.
 *
 * @param int $user_id User id.
 * @return string
 */
function cas_dashboard_wishlist_html( $user_id ) {
	$courses = function_exists( 'tutor_utils' ) ? tutor_utils()->get_wishlist( $user_id ) : array();

	if ( empty( $courses ) ) {
		return '<p class="hand-note">Courses you save appear here.</p>';
	}

	ob_start();
	?>
	<div class="mycourse-grid">
		<?php foreach ( $courses as $course ) : ?>
			<?php
			$image = get_the_post_thumbnail_url( $course->ID, 'large' );
			$url   = get_permalink( $course->ID );
			?>
			<article class="mc-course dash-card">
				<?php if ( $image ) : ?><a href="<?php echo esc_url( $url ); ?>"><img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy" decoding="async" width="480" height="270"></a><?php endif; ?>
				<div class="mc-body"><span class="eyebrow">Course</span><h3><?php echo esc_html( get_the_title( $course->ID ) ); ?></h3><a class="btn small" href="<?php echo esc_url( $url ); ?>">View course <span class="arr" aria-hidden="true">&#8594;</span></a></div>
			</article>
		<?php endforeach; ?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Main focus options, as in the prototype.
 *
 * @return string[]
 */
function cas_dashboard_focus_options() {
	return array( 'Watercolour', 'Oils', 'Drawing', 'Acrylic', 'Ink' );
}

/**
 * Settings: Main focus (a small user field) and Country (Tutor's billing country). One form,
 * one Save button.
 *
 * @param int $user_id User id.
 * @return string
 */
function cas_dashboard_settings_html( $user_id ) {
	$focus   = (string) get_user_meta( $user_id, 'cas_main_focus', true );
	$billing = function_exists( 'tutor_utils' ) ? ( new \Tutor\Ecommerce\BillingController( false ) )->get_billing_info( $user_id ) : null;
	$country = $billing->billing_country ?? '';
	$saved   = isset( $_GET['cas_saved'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
	$names   = function_exists( 'tutor_get_country_list' ) ? array_column( tutor_get_country_list(), 'name' ) : array();

	ob_start();
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-cas-settings-form>
		<input type="hidden" name="action" value="cas_dashboard_save_settings">
		<?php wp_nonce_field( 'cas_dashboard_save_settings', 'cas_settings_nonce' ); ?>
		<div class="dash-card">
			<div class="set-grid">
				<div class="set-field"><label for="set-focus">Main focus</label>
					<select id="set-focus" name="cas_main_focus">
						<option value=""></option>
						<?php foreach ( cas_dashboard_focus_options() as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>" <?php selected( $focus, $option ); ?>><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="set-field"><label for="set-country">Country</label>
					<select id="set-country" name="cas_country" autocomplete="country-name">
						<option value=""></option>
						<?php foreach ( $names as $name ) : ?>
							<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $country, $name ); ?>><?php echo esc_html( $name ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</div>
		<div style="margin-top:16px;display:flex;align-items:center;gap:12px;"><button class="btn primary" type="submit">Save changes</button><?php if ( $saved ) : ?> <span class="hand-note" role="status">Saved.</span><?php endif; ?></div>
	</form>
	<?php

	return (string) ob_get_clean();
}

add_action(
	'admin_post_cas_dashboard_save_settings',
	function () {
		$user_id = get_current_user_id();

		if ( ! $user_id || ! isset( $_POST['cas_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cas_settings_nonce'] ) ), 'cas_dashboard_save_settings' ) ) {
			wp_die( esc_html__( 'The link has expired. Go back and try again.', 'hello-elementor-child' ), '', array( 'response' => 403 ) );
		}

		$focus = isset( $_POST['cas_main_focus'] ) ? sanitize_text_field( wp_unslash( $_POST['cas_main_focus'] ) ) : '';

		if ( '' === $focus || in_array( $focus, cas_dashboard_focus_options(), true ) ) {
			update_user_meta( $user_id, 'cas_main_focus', $focus );
		}

		$country = isset( $_POST['cas_country'] ) ? sanitize_text_field( wp_unslash( $_POST['cas_country'] ) ) : '';
		$names   = function_exists( 'tutor_get_country_list' ) ? array_column( tutor_get_country_list(), 'name' ) : array();

		if ( '' !== $country && in_array( $country, $names, true ) ) {
			cas_dashboard_save_billing_country( $user_id, $country );
		}

		wp_safe_redirect( add_query_arg( 'cas_saved', '1', tutor_utils()->tutor_dashboard_url() ) . '#panel-settings' );
		exit;
	}
);

/**
 * Write the billing country Tutor's checkout prefills from.
 *
 * Tutor keeps one billing row for each student. When there is none yet, the row is created
 * with the student's own name and email and the country; checkout asks for the rest.
 *
 * @param int    $user_id User id.
 * @param string $country Country name from Tutor's list.
 * @return void
 */
function cas_dashboard_save_billing_country( $user_id, $country ) {
	$model = new \Tutor\Models\BillingModel();

	if ( $model->get_info( $user_id ) ) {
		$model->update( array( 'billing_country' => $country ), array( 'user_id' => $user_id ) );

		return;
	}

	$user = get_userdata( $user_id );

	$model->insert(
		array(
			'user_id'            => $user_id,
			'billing_first_name' => (string) get_user_meta( $user_id, 'first_name', true ),
			'billing_last_name'  => (string) get_user_meta( $user_id, 'last_name', true ),
			'billing_email'      => $user ? $user->user_email : '',
			'billing_phone'      => '',
			'billing_zip_code'   => '',
			'billing_address'    => '',
			'billing_country'    => $country,
			'billing_state'      => '',
			'billing_city'       => '',
		)
	);
}

/**
 * Tutor's dashboard script registers a page's Alpine components by looking at the address:
 * `/account/settings` registers the profile and password components, `/dashboard/` registers
 * only the home ones. The studio shows Profile and Settings on `/dashboard/`, so their forms
 * (name, photo, password) had no component behind them and did nothing.
 *
 * Tutor reads `?dashboard-page=` before the path. The address carries that one parameter
 * until the page has loaded, and is put back then, so the
 * student never sees it and a bookmark of the studio stays clean.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'cas_account_dashboard_should_render_studio' ) || ! cas_account_dashboard_should_render_studio() || ! wp_script_is( 'tutor-dashboard', 'enqueued' ) ) {
			return;
		}

		wp_add_inline_script(
			'tutor-dashboard',
			"(function(){var u=new URL(window.location.href);if(u.searchParams.has('dashboard-page')){return;}window.__casStudioUrl=window.location.href;u.searchParams.set('dashboard-page','settings');window.history.replaceState(window.history.state,'',u.pathname+u.search+u.hash);}());",
			'before'
		);
		wp_add_inline_script(
			'tutor-dashboard',
			"(function(){function restore(){if(window.__casStudioUrl){window.history.replaceState(window.history.state,'',window.__casStudioUrl);window.__casStudioUrl='';}}window.addEventListener('load',restore);}());",
			'after'
		);
	},
	30
);
