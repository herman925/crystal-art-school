<?php
/**
 * Crystal Art School login form override.
 *
 * Tutor still owns authentication, validation, redirects and error handling. This
 * override keeps the password field native and adds a dependency-free progressive
 * enhancement for visibility. Registration remains in cas_account_card()'s foot so
 * the P6 registration gate has one source of truth and the link is not duplicated.
 *
 * @package Crystal_Art_School
 */

use TUTOR\Ajax;
use Tutor\Components\Alert;
use Tutor\Components\SvgIcon;
use TUTOR\Icon;

defined( 'ABSPATH' ) || exit;

$lost_pass = apply_filters( 'tutor_lostpassword_url', wp_lostpassword_url() );
$login_errors = get_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY )
	? get_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY )
	: array();

if ( ! empty( $login_errors ) ) :
	?>
	<div class="cas-login-errors" role="alert" aria-live="assertive" aria-atomic="true">
		<?php foreach ( $login_errors as $login_error ) : ?>
			<?php
			Alert::make()
				->text( $login_error )
				->variant( Alert::ERROR )
				->icon( Icon::WARNING )
				->attr( 'class', 'cas-login-error' )
				->render();
			?>
		<?php endforeach; ?>
	</div>
	<?php
endif;

do_action( 'tutor_before_login_form' );
?>
<form id="tutor-login-form" method="post">
	<?php if ( is_single_course() ) : ?>
		<input type="hidden" name="tutor_course_enroll_attempt" value="<?php echo esc_attr( get_the_ID() ); ?>">
	<?php endif; ?>
	<?php tutor_nonce_field(); ?>
	<input type="hidden" name="tutor_action" value="tutor_user_login" />
	<input type="hidden" name="redirect_to" value="<?php echo esc_url( apply_filters( 'tutor_after_login_redirect_url', tutor()->current_url ) ); ?>" />

	<div class="tutor-input-field tutor-mb-7 co-field">
		<label for="cas-login-email"><?php esc_html_e( 'Email', 'hello-elementor-child' ); ?></label>
		<input id="cas-login-email" type="text" class="tutor-form-control tutor-input" placeholder="<?php esc_attr_e( 'you@example.com', 'hello-elementor-child' ); ?>" name="log" value="" size="20" autocomplete="username" required />
	</div>

	<div class="tutor-input-field tutor-mb-8 co-field cas-password-field">
		<label for="cas-login-password"><?php esc_html_e( 'Password', 'hello-elementor-child' ); ?></label>
		<button
			type="button"
			class="cas-password-toggle"
			data-cas-password-toggle
			aria-controls="cas-login-password"
			aria-pressed="false"
			aria-label="<?php esc_attr_e( 'Show password', 'hello-elementor-child' ); ?>"
			data-show-label="<?php esc_attr_e( 'Show password', 'hello-elementor-child' ); ?>"
			data-hide-label="<?php esc_attr_e( 'Hide password', 'hello-elementor-child' ); ?>"
			hidden
		>
			<span class="cas-password-icon" data-cas-password-icon="show" aria-hidden="true">
				<?php SvgIcon::make()->name( Icon::EYE )->size( 20 )->render(); ?>
			</span>
			<span class="cas-password-icon" data-cas-password-icon="hide" aria-hidden="true" hidden>
				<?php SvgIcon::make()->name( Icon::EYE_OFF )->size( 20 )->render(); ?>
			</span>
		</button>
		<input id="cas-login-password" type="password" class="tutor-form-control tutor-input" placeholder="<?php esc_attr_e( 'Your password', 'hello-elementor-child' ); ?>" name="pwd" size="20" required style="padding-right: 45px;" autocomplete="current-password" />
	</div>

	<div class="tutor-login-error" role="alert" aria-live="polite"></div>
	<?php
		do_action( 'tutor_login_form_middle' );
		do_action( 'login_form' );
		apply_filters( 'login_form_middle', '', '' );
	?>
	<div class="tutor-flex tutor-justify-between tutor-items-center tutor-mt-9">
		<div class="tutor-input-field tutor-w-auto">
			<div class="tutor-input-wrapper">
				<input id="tutor-login-agmnt-1" type="checkbox" class="tutor-checkbox tutor-checkbox-md" name="rememberme" value="forever" />
				<label for="tutor-login-agmnt-1" class="tutor-label">
					<?php esc_html_e( 'Remember me', 'hello-elementor-child' ); ?>
				</label>
			</div>
		</div>
		<a href="<?php echo esc_url( $lost_pass ); ?>" class="tutor-btn tutor-btn-link-gray tutor-btn-small">
			<?php esc_html_e( 'Forgot password?', 'hello-elementor-child' ); ?>
		</a>
	</div>

	<?php do_action( 'tutor_login_form_end' ); ?>
	<button type="submit" class="tutor-btn tutor-btn-primary tutor-btn-block tutor-mt-10">
		<?php esc_html_e( 'Log in', 'hello-elementor-child' ); ?> <span aria-hidden="true">&#8594;</span>
	</button>

	<?php do_action( 'tutor_after_sign_in_button' ); ?>
</form>
<script>
( function () {
	var field  = document.getElementById( 'cas-login-password' );
	var toggle = document.querySelector( '[data-cas-password-toggle]' );

	if ( ! field || ! toggle ) {
		return;
	}

	var show_icon = toggle.querySelector( '[data-cas-password-icon="show"]' );
	var hide_icon = toggle.querySelector( '[data-cas-password-icon="hide"]' );

	if ( ! show_icon || ! hide_icon ) {
		return;
	}

	function sync() {
		var visible = 'text' === field.type;

		toggle.hidden = 0 === field.value.length;
		toggle.setAttribute( 'aria-pressed', visible ? 'true' : 'false' );
		toggle.setAttribute(
			'aria-label',
			visible ? toggle.getAttribute( 'data-hide-label' ) : toggle.getAttribute( 'data-show-label' )
		);
		show_icon.hidden = visible;
		hide_icon.hidden = ! visible;
	}

	field.addEventListener( 'input', sync );
	field.addEventListener( 'change', sync );
	toggle.addEventListener( 'click', function () {
		field.type = 'text' === field.type ? 'password' : 'text';
		sync();
		field.focus();
	} );
	sync();
}() );
</script>
<?php
do_action( 'tutor_after_login_form' );
if ( ! tutor_utils()->is_tutor_frontend_dashboard() ) :
	?>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		var loginModal = document.querySelector('.tutor-modal.tutor-login-modal');
		var errors = <?php echo wp_json_encode( $login_errors ); ?>;
		if (loginModal && errors.length) {
			loginModal.classList.add('tutor-is-active');
		}
	});
</script>
	<?php
endif;
delete_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY );
