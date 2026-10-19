# Tutor 4.1.0 password-recovery route regression

Tutor marks `retrieve-password` as public (`classes/Utils.php`, `tutor_dashboard_permalinks()`), sends the Forgot password link there (`classes/FormHandler.php::lostpassword_url()`), and may rewrite reset email links to that same route with `reset_key` and `user_id` (`classes/User.php::maybe_update_password_reset_link()`). That route renders either the request form or reset form (`templates/template-part/retrieve-password.php`). The theme's guest-dashboard redirect must preserve it.

Pinned Tutor 4.1.0 source:

- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/Utils.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/Template.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/FormHandler.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/classes/User.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/templates/template-part/retrieve-password.php
- https://plugins.svn.wordpress.org/tutor/tags/4.1.0/templates/template-part/form-retrieve-password.php

## Tests

Run static and loopback GET contracts with:

```sh
python3 tests/test_login_form_contract.py
CAS_RECOVERY_TEST_BASE_URL=http://127.0.0.1:18089 python3 tests/test_password_recovery_http.py
```

Run offline harness-safety checks with:

```sh
python3 tests/password-recovery/test_reset_integration.py TestRecoveryHarnessSafety
```

Run the full isolated reset matrix only after the PR146 fixture owner releases the stack:

```sh
CAS_RECOVERY_TEST_BASE_URL=http://127.0.0.1:18089 \
CAS_RECOVERY_MAILPIT_URL=http://127.0.0.1:18026 \
CAS_RECOVERY_WP_CONTAINER=cas-pr146-w1-20261019-wp-1 CAS_RECOVERY_MAILPIT_CONTAINER=cas-pr146-w1-20261019-mailpit-1 \
python3 tests/password-recovery/test_reset_integration.py
```

Before `wp_insert_user`, the harness checks exact Compose project/service labels, running containers, the project bridge network, loopback-only WordPress and Mailpit ports, unpublished database and SMTP ports, project-scoped data volumes, matching synthetic database settings, WordPress 7.1.2 or 7.1.3 with exact `home`/`siteurl`, Tutor 4.1.0, active and mounted theme hashes, and the Mailpit-only PHPMailer plus `/bin/false` sendmail setting. It refuses all other WordPress or Mailpit origins. It checks every request, redirect, reset link and Mailpit GET against its exact origin. Mailpit access uses GET only. The approved bridge does not block general egress; the harness itself makes only loopback HTTP requests, and PHP mail routing must stay on Mailpit with sendmail denied. PHP payloads, including synthetic credentials, go through stdin with `docker exec -i`; no payload appears in process arguments. The harness never resets volumes or deletes users or messages.

Offline tests reject wrong container/project/database/mail targets, cross-port redirects/reset links and command-line secrets. Do not run the integration matrix while another worker owns the stack.

## Historical runtime result (2026-10-19; before harness guard repair)

The runtime matrix below used the earlier harness revision. The safety repair has not run against a runtime yet.

- Runtime: WordPress 7.1.2, Tutor 4.1.0, Hello Elementor Child active; `enable_tutor_native_login` was off, so reset emails used WordPress's `wp-login.php?action=rp` link. Worker1 verified the active theme mirror against PR146's source export.
- The earlier full reset matrix passed three runs; the loopback GET smoke test passed 3/3 cases.
| Case | HTTP / final route | Visible result | Mail/credential result |
|---|---|---|---|
| Bad Tutor nonce | 200, `/dashboard/retrieve-password/` | No visible alert text | No reset email |
| Empty username | 200, `/dashboard/retrieve-password/` | `Enter a username or email address.` | No reset email |
| Known synthetic email | 200, `/dashboard/retrieve-password/` | `We've sent an email to this account's email address...` | Reset email captured in Mailpit |
| Unknown synthetic email | 200, `/dashboard/retrieve-password/` | `Invalid username or email.` | No email; message differs from known account (account-enumeration finding) |
| Invalid reset key | 200, `/wp-login.php` | Reset link is invalid | Password unchanged |
| Mismatched confirmation | 200, `/wp-login.php` | `The passwords do not match.` | Old password remains valid |
| Valid reset | 200, `/wp-login.php` | `Your password has been reset.` | New credential authenticates; old one fails |
| Reused reset key | 200, `/wp-login.php` | Reset link is invalid | New password remains valid |
| Expired reset key | 200, `/wp-login.php` | Reset link has expired | New password remains valid |

Known/unknown account enumeration is reproduced. A separate recovery-hardening change is required; PR146 does not hide the distinction.

After the runs, the isolated stack contained 5 users (4 reset-test-prefix users) and 12 Mailpit messages. Some pre-existing synthetic artifacts could not be attributed. We left all records/messages in place; no volume reset or cleanup ran. Runtime WordPress is 7.1.2; live site is 7.1.3.

## Repaired runtime result (2026-10-19; local only)

- Runtime: WordPress 7.1.3, Tutor 4.1.0, Hello Elementor Child. Local `home` and `siteurl` were `http://127.0.0.1:18089`.
- Active and mounted PR146 theme source matched SHA-256 `efacf9ca6ec4795848a65080d2dce76f1d594cf939a679d353ab5d0f68ed436f`.
- The original Tutor native-login option was absent/default off. Matrix passed with `enable_tutor_native_login=0` and `=1`; option was restored to its original state.
- Both configurations passed all nine matrix checks: nonce, empty input, known/unknown requests, local reset link, invalid token, mismatch, valid reset, reused token, expired token, and credential checks. Known/unknown responses differed in both configurations.
- In the simulated native Tutor mode, invalid-key, mismatch, reused-key and expired-key responses returned to `/dashboard/retrieve-password/` without visible alert text. Tutor 4.1.0 sets danger flashes, but its native reset template does not render them. Invalid-token checks confirmed unchanged password hash, old credential valid, new credential invalid, no WordPress login cookie, and dashboard redirect to `/login/`. These HTTP checks do not prove browser feedback. A separate local browser check confirmed no visible alert after invalid-token submission; expired-token browser feedback remains unverified. Do not edit Tutor files.
- Existing user IDs 2–6 and user 6's wishlist sentinel stayed unchanged. User count rose from 11 to 13. Mailpit count rose from 19 to 25. No user, message or volume cleanup ran.

## Not verified

- SMTP delivery or mail-failure logging outside local Mailpit.
- Production password recovery or live user credentials.
