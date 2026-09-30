# Checkout step parity (#123)

Spec: `Checkout.dc.html`, step 2 (details and payment). Port: Tutor 4.1.0 native checkout at `/checkout/`, tested on a local Docker stack (two paid courses in the basket, two manual test gateways, student account).

## Measured differences and result

| # | Prototype | Live before | Result |
|---|---|---|---|
| 1 | Wide billing column left, summary card right | Columns reversed, container narrowed to 1032px | Fixed |
| 2 | Billing address and Payment are two `.co-panel` cards | One squared table of fields, Tutor labels hidden | Fixed, visible uppercase labels, dark fields |
| 3 | Dark summary card, marker-font total | White `#fafafa` card, pale text | Fixed |
| 4 | Heading "Order summary", row "Total" | "Order Details", "Grand Total" | Fixed (gettext, also after AJAX refresh) |
| 5 | Place order button, full width, inside the summary | Small "Pay Now" under the billing form | Fixed, "Place order →" |
| 6 | One terms consent in the summary | Checkbox floating above the summary | Fixed, exactly one consent |
| 7 | Gateway list as rows | Tutor radio list | Fixed, styled rows |
| 8 | Coupon as a quiet link | Pink pill | Fixed |
| 9 | Back to basket link | Under the billing form | Fixed, inside the summary |

## Accepted differences (owner to confirm)

1. Billing field set is Tutor's (first/last name, email, country, state, city, postcode, phone, address). The prototype has fewer fields.
2. "Sale discount" row stays. Tutor prints Subtotal, discount and Total. Only the style changed.
3. The card form is not on the site. Payment is on Stripe's hosted page. Branding of that page is a Stripe setting and is separate from this step.
4. Gateway labels and icons come from Tutor settings, not from this PR.

## Demo-copy cases

No copy was invented. Words used: "Billing address", "Payment", "Order summary", "Total", "Place order", "Back to basket". Terms consent text is the site's existing consent.

## Code changes

- `tutor/ecommerce/checkout.php`: fork of Tutor 4.1.0, all Tutor ids, names and hooks kept.
- `css/cas-checkout.css`, `inc/cas-checkout.php`: style, enqueue, wording.
- `inc/cas-account.php`: removed the second consent hook and the old back link. Fixed a latent bug: `cas_account_require_checkout_terms()` demanded `agree_to_terms=1`, but Tutor's own checkbox posts `on`.
- `tests/test_checkout_parity_contract.py`.

## Verified

- Coupon and country change refresh the summary over AJAX: heading wording, dark ground, ticked consent and single button survive.
- Mobile, no horizontal overflow.
- `php -l` clean on four PHP files. All 8 contract tests pass. Sabotage (button inside refreshed element) fails the new test.

## Not verified

- A real payment and the success screen.
- Live Stripe and PayPal gateways (only manual test gateways locally).
- `?course_id=` Buy Now path.
- Cart step, "Beginner" sub-label is dark on dark there. Noted, out of scope.

Screenshots: `prototype-desktop.png`, `before-desktop.png`, `after-desktop.png`, `after-mobile.png`.
