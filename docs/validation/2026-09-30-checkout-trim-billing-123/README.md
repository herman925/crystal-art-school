# Checkout billing form without State and Phone (Refs #123)

Owner decision: keep Tutor's structure, the Sale discount row, the coupon and the thumbnails; do not ask for State or Phone.

## What Tutor 4.1.0 requires

| Layer | State | Phone |
|---|---|---|
| Checkout form HTML | `<select>`, no `required` | `<input required>` (HTML only) |
| `CheckoutController::pay_now()` | no check on billing fields | no check |
| Customers table (`tutor_customers`) | `VARCHAR(100) NOT NULL`, no default | `VARCHAR(20) NOT NULL`, no default |
| `BillingModel::$required_fields` | listed | listed |
| Dashboard Billing form (`BillingController::validate`) | `required` when the key is posted | same |
| Gateways, order record | read with `?? ''` | read with `?? ''` |
| Tutor JS | country change fills `[name=billing_state]`; no error when it is a hidden input (console clean) | none |

No setting or filter exists for billing fields. Checkout therefore sends `billing_state` and `billing_phone` as empty hidden inputs, so the NOT NULL columns get `''` and do not depend on the server's sql_mode. The dashboard Billing page is not changed and still shows Tutor's full form.

`tutor/ecommerce/cas-checkout-billing-fields.php` is a copy of Tutor's `checkout-billing-form-fields.php` without the two visible fields. Diff it against Tutor when Tutor updates.

## Verified locally

Test order through the manual test gateway (`card_test`), two courses, £150.00:

- order 1 saved: `order_status=incomplete`, `payment_status=unpaid`, `payment_method=card_test`, `total_price=150.00`, two `tutor_order_items` rows;
- `tutor_customers` row saved: country, city, postcode, address filled; `billing_state` and `billing_phone` are empty strings.

Screenshot: `after-desktop.png` (City and Postcode share a row).

## Not verified

- Stripe and PayPal live gateways (no local sandbox): Stripe's customer details read state and phone with `?? ''` in `CheckoutController`, so empty values are expected to pass.
- Tax by state: tax is not configured here. `Tax.php` reads state with `?? ''`.
