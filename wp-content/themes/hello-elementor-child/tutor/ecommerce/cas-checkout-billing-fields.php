<?php
/**
 * Billing fields for the checkout step, without State and Phone.
 *
 * COPIED from Tutor LMS 4.1.0 `templates/ecommerce/checkout-billing-form-fields.php`, for the
 * checkout step only (the account Billing page keeps Tutor's full form). The owner chose to
 * not ask for a state or a phone number.
 *
 * Tutor 4.1.0 does not check either field on the server when an order is placed: its `required`
 * is an HTML attribute only. Its customers table has no default for them, so they are sent as
 * EMPTY hidden inputs. A missing key would rely on MySQL's sql_mode to fill the column.
 *
 * When Tutor is updated, diff this file against the plugin's and carry the changes across.
 * tests/test_checkout_parity_contract.py names the pieces that must stay.
 *
 * @package Tutor\Templates
 */

use Tutor\Ecommerce\BillingController;

$billing_controller = new BillingController( false );
$billing_info       = $billing_controller->get_billing_info();

$billing_first_name = $billing_info->billing_first_name ?? tutor_utils()->input_old( 'billing_first_name', '' );
$billing_last_name  = $billing_info->billing_last_name ?? tutor_utils()->input_old( 'billing_last_name', '' );
$billing_email      = $billing_info->billing_email ?? tutor_utils()->input_old( 'billing_email', '' );
$billing_zip_code   = $billing_info->billing_zip_code ?? tutor_utils()->input_old( 'billing_zip_code', '' );
$billing_address    = $billing_info->billing_address ?? tutor_utils()->input_old( 'billing_address', '' );
$billing_country    = $billing_info->billing_country ?? tutor_utils()->input_old( 'billing_country', '' );
$billing_city       = $billing_info->billing_city ?? tutor_utils()->input_old( 'billing_city', '' );
?>

<div class="tutor-row <?php echo isset( $is_checkout_page ) ? 'tutor-g-0' : ''; ?>">
	<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'First Name', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="text" name="billing_first_name" placeholder="<?php esc_attr_e( 'First Name', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_first_name ); ?>" required>
		</div>
	</div>

	<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'Last Name', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="text" name="billing_last_name" placeholder="<?php esc_attr_e( 'Last Name', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_last_name ); ?>" required>
		</div>
	</div>

	<div class="tutor-col-12">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'Email Address', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="email" name="billing_email" placeholder="<?php esc_attr_e( 'Email Address', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_email ); ?>" required>
		</div>
	</div>

	<div class="tutor-col-12 tutor-position-relative">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'Country', 'tutor' ); ?>
			</label>
			<select name="billing_country" class="tutor-form-control" required>
				<option value=""><?php esc_html_e( 'Select Country', 'tutor' ); ?></option>
				<?php
				$countries = array_column( tutor_get_country_list(), 'name' );
				foreach ( $countries as $name ) :
					?>
					<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $billing_country, $name ); ?>>
						<?php echo esc_html( $name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'City', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="text" name="billing_city" placeholder="<?php esc_attr_e( 'City', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_city ); ?>" required>
		</div>
	</div>

	<div class="tutor-col-12 tutor-col-sm-6 tutor-col-md-12 tutor-col-lg-6">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'Postcode / ZIP', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="text" name="billing_zip_code" placeholder="<?php esc_attr_e( 'Postcode / ZIP', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_zip_code ); ?>" required>
		</div>
	</div>

	<div class="tutor-col-12">
		<div class="tutor-mb-16">
			<label class="tutor-form-label tutor-color-secondary">
				<?php esc_html_e( 'Address', 'tutor' ); ?>
			</label>
			<input class="tutor-form-control" type="text" name="billing_address" placeholder="<?php esc_attr_e( 'Address', 'tutor' ); ?>" value="<?php echo esc_attr( $billing_address ); ?>" required>
		</div>
	</div>

	<input type="hidden" name="billing_state" value="">
	<input type="hidden" name="billing_phone" value="">
</div>
