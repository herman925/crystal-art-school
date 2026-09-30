# Site emails: local test (issue #133)

Runs `inc/cas-mail.php` against a throwaway WordPress with Tutor 4.1.0 (free) and the manual gateway.
Mail goes to Mailpit (web view on http://localhost:8026). Needs Docker. Nothing touches the live site.

```sh
docker compose up -d db mailpit wp
docker compose run --rm cli sh -c 'cd /var/www/html && wp core install --url=http://localhost:8089 --title=T --admin_user=a --admin_password=p --admin_email=a@example.test --skip-email && wp plugin install https://downloads.wordpress.org/plugin/tutor.4.1.0.zip --activate'
MSYS_NO_PATHCONV=1 docker compose run --rm cli sh /tests/run.sh   # resets the site, prints PASS/FAIL lines
docker compose down -v
```

Covered (36 checks): receipt and enrolment on a paid order (buyer only, HTML and plain part, escaping), no repeat
on a repeated event, refund through Tutor's own handler (partial, then full; staff reason not sent; "enrolment cancelled" line only when the enrolment of that order is cancelled, not when the order is),
no mail to an administrator, no receipt for a free order, new-course queue (opted-in only, private
course sends nothing, no second announcement), opt-out while queued, promo opt-ins, the daily cap
reserve (bulk waits, receipt still goes out).
Not covered: Stripe (the live payment is marked paid by the `charge.succeeded` webhook and reaches the
same `tutor_order_payment_status_changed` action), the real SMTP hook, the theme's own pages.
