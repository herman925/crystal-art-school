# Checkout consent: local test (issue #138)

Runs `inc/cas-checkout.php` (the no-refund consent) against a throwaway WordPress with Tutor 4.1.0 (free).
It calls Tutor's own `CheckoutController::pay_now()` with a crafted POST, exactly as the checkout form sends
it, with a manual payment method. Needs Docker. Nothing touches the live site.

```sh
docker compose up -d db mailpit wp
docker compose run --rm cli sh -c 'cd /var/www/html && wp core install --url=http://localhost:8089 --title=T --admin_user=a --admin_password=p --admin_email=a@example.test --skip-email && wp plugin install https://downloads.wordpress.org/plugin/tutor.4.1.0.zip --activate'
MSYS_NO_PATHCONV=1 docker compose run --rm cli sh /tests/run.sh   # resets the site, prints PASS/FAIL lines and a pass count
docker compose down -v
```

Covered: the box (a required checkbox with the approved wording, never pre-ticked, inside the form above the
place-order button); no box or a wrong value = no order, no billing record, a clear error; the site terms box is
still required; box ticked = order created with `_cas_refund_consent_at` (UTC) and `_cas_refund_consent_text`
(exact wording); an unpaid order paid again; other requests untouched; the second payment entry point
(`tutor_pay_incomplete_order`). Mutation check: with the server filter removed, 11 checks fail.
Not covered: the served page in a browser and Stripe test mode (needs a deploy).
