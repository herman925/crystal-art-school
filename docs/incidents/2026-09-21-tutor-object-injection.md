# Incident 2026-09-21 — Tutor LMS object injection, attacker admin on crystalau.com

| Field | Value |
|---|---|
| Status | **Closed 2026-09-30.** Monitoring live (`cas-audit-log.php`). Owner items remain in section 7. |
| Found | 2026-09-30, during a status check (Operator session) |
| Attack date | 2026-09-21 18:53 UTC |
| Attacker access window | 2026-09-21 18:53 UTC to 2026-09-30 (about 9 days of admin access) |
| Root cause | CVE-2026-78175, Tutor LMS <= 4.0.7, PHP object injection in `tutor_save_withdraw_account` |
| Blast radius | Small. 0 orders, 0 enrolments, 0 real students. Two admins (cas_owner, cas_dev). Attacker could read wp-config.php and the DB |
| Evidence folder (server) | `~/incident-2026-09-21/` (0700), outside public_html |

Rule for this file: record measurements, label inference as INFERRED, never write a secret value.

---

## 1. Timeline (UTC)

| Time | Event | Source |
|---|---|---|
| 2026-08-22 | WordPress, Tutor 4.0.7, Tutor Pro 4.0.7 installed | file mtimes |
| 2026-09-10 | Tutor 4.0.8 released with a silent fix | vendor code diff |
| 2026-09-12 | CVE-2026-78175 published (assigner Wordfence) | opencve.io |
| 2026-09-17 | Wordfence public disclosure | gbhackers.com |
| 2026-09-21 18:53:13 | `alex67099` registers as subscriber through open signup (`users_can_register=1`), email `@example.com`, IP 129.227.46.171 (Zenlayer, Jakarta) | wp_users, session_tokens |
| 2026-09-21 18:53:18 | POST to `admin-ajax.php` action `tutor_save_withdraw_account` stores a serialized Guzzle `FileCookieJar` in usermeta `_tutor_withdraw_method_data_bank_transfer_withdraw`. On unserialize the jar writes `624bf1c4c4.php` into `wp-content/mu-plugins/` and `wp-content/uploads/` | php-errors.log stack, file mtimes .058/.059 |
| 2026-09-21 18:53:23 | Admin `alexaeebc4` created, same IP. Name matches the dropper's `?a` scheme (`alex` + 6 hex) | wp_users, payload.txt |
| 2026-09-21 18:53:31 | `alexaeebc4` logs in. Session valid to 2026-10-05. Visits wp-admin (quick-press auto-draft post 268, later deleted) | session_tokens, usermeta |
| 2026-09-21 19:06:37 | Third copy `wp-content/624bf1c4c4.php` written. No log line. Request unknown | file mtime |
| 2026-09-22 15:36 | WordPress core update to 7.1.2 (routine) | file mtimes |
| 2026-09-29 13:20 | plugins/ and themes/ directory mtimes change. INFERRED: automatic-updater cron run (same second as its log warning) | php-errors.log |
| 2026-09-30 | Found and contained (section 3) | this session |

## 2. How the attack worked

1. Open signup let anyone create a subscriber.
2. Tutor 4.0.7's `tutor_save_withdraw_account` checked only a nonce, not a role. A subscriber could reach it.
3. The `withdraw_method_field` input went through `esc_sql()`, which changed string lengths inside a serialized value. That broke out of the stored string and planted a real serialized object.
4. The object is `GuzzleHttp\Cookie\FileCookieJar` from the PayPal library bundled in `tutor/ecommerce/PaymentGateways/Paypal/vendor`. Its destructor writes its cookies as JSON to a file path the attacker chose.
5. The cookie value held about 600 bytes of PHP. JSON does not escape `<?php`, so the file ran as PHP. In `mu-plugins/` it ran on every request.
6. The dropper code (read from payload.txt as text):
   - `?v` printed `TUTOR_SHELL_OK` and the PHP version.
   - `?c=CMD` ran `shell_exec`. INFERRED: this failed, because `shell_exec` is in `disable_functions`.
   - `?a` loaded WordPress, created user `alex` + 6 hex chars with a random password and role administrator, and printed the credentials.

**Resolved (2026-09-30):** all three dropper files were 0 bytes when found. A successful write is about 650 bytes, and the code has no truncate. Hostinger's malware scanner (Monarx) emptied them: hPanel Malware Scanner lists `wp-content/624bf1c4c4.php` as Removed, discovered 2026-09-21 19:17 UTC. INFERRED: the `mu-plugins` and `uploads` copies were emptied the same way (they are not listed).

## 3. Containment and hardening actions (2026-09-30)

| # | Action | Who | Read-back |
|---|---|---|---|
| 1 | Read-only discovery: unknown admin, zero-byte files, open signup | Operator | `wp user list`, `ls` |
| 2 | Attempted `wp user delete 6 7`. It unserialized the payload and re-fired the gadget. The write failed (relative path resolved outside the docroot). Partial result: user 6 lost its role | Operator | fatal in php-errors.log 22:20:40; no new files |
| 3 | Killed user 7 sessions, set capabilities `a:0:{}` and user_level 0 for users 6 and 7 (raw SQL) | Worker 1 | 0 session rows |
| 4 | Saved payload to `~/incident-2026-09-21/payload.txt` (3,481 B, md5 27c830b0…), then deleted usermeta row 132 | Worker 1 | 0 rows; DB scan found no other Guzzle object |
| 5 | Deleted users 6 and 7 and their 17 usermeta rows (raw SQL). They owned no posts, comments or Tutor rows | Worker 1 | only IDs 1, 2 remain |
| 6 | `users_can_register` set to 0 | Worker 1 | reads 0 |
| 7 | Text search of 173 columns for attacker markers | Worker 1 | 0 hits (1 WordPress default) |
| 8 | Recorded, then deleted, the three 0-byte `624bf1c4c4.php` files | Worker 1 | `files.txt`; `find` returns 0 |
| 9 | `wp-content/uploads/.htaccess` denies php/phtml/phar | Worker 1 | test PHP 403, image 200 |
| 10 | `chmod 555 wp-content/mu-plugins` | Worker 1 | `touch` denied; cas-hero-lock still loads |
| 11 | `DISALLOW_FILE_EDIT` | Worker 1 | already true in wp-config.php line 94 |
| 12 | Backed up wp-config.php and root .htaccess to the incident folder | Worker 1 | md5 match |

## 4. Investigation results (Worker 2, read-only)

| Check | Result |
|---|---|
| New or changed PHP since 09-20 | Only the 3 dropper files and the 09-22 core update files |
| Backdoor grep (eval, base64, shell functions + request input, and others) | 0 hits |
| Core checksums | Only `wp-includes/php-ai-client` filename truncation from install day. INFERRED benign |
| Plugin checksums | elementor, rank-math and tutor OK. elementor-pro and tutor-pro unverifiable (no public checksums) |
| Child theme vs git | No attacker change. 7 server files are ahead of main. All 7 match PR #98 branch `fix/v1-ui-validation-86` |
| DB | No code-like values, no unknown cron hooks, no extra admins. 1 app password (legit) |
| Access logs | Not on the account. Needs hPanel download |
| Backups | Hostinger API offers no shared-hosting backup list. No DB dump existed on the server |
| `~/cas-backups/` | Holds old wp-config copies with credentials |

## 5. Credential rotation (approved: rotate all, tell Crystal)

Status: **done 2026-09-30** (Worker 1, batch B). The user confirmed the scope.

Backups in `~/incident-2026-09-21/` (0600):
- `db-2026-09-30.sql.gz` (162,232 B, 45 of 45 tables, gzip -t OK)
- `plugins-tutor-4.0.7.tgz` (13.6 MB)
- wp-config copies from before each change (these hold OLD credentials)

Local: `.secrets/.env.bak-2026-09-30` (the pre-rotation copy).
A second backup, `.secrets/.env.bak-H-2026-09-30`, also exists next to the original `.env`.

| Secret | Rotated | Where on the server | .env key | Verification |
|---|---|---|---|---|
| cas_dev password | yes | user 2 | WORDPRESS_ADMIN_DEV_PASSWORD | wp_authenticate OK, wrong password rejected |
| cas_owner password (Crystal) | yes | user 1 | WORDPRESS_ADMIN_OWNER_PASSWORD | same |
| App password `claude-code-tooling` | yes | user 2 | WORDPRESS_APP_PASSWORD | new 200, old 401 |
| Tutor API key + secret | yes | usermeta `tutor-api-key-secret` | TUTOR_API_KEY, TUTOR_API_SECRET | new 200, old 401 |
| Bunny library API key | yes | Bunny resetApiKey | BUNNY_LIBRARY_KEY | new 200, old 401 |
| Bunny pull-zone security key | yes | zone 6397141; wp-config `CAS_BUNNY_TOKEN_KEY` | BUNNY_TOKEN_KEY (new) | embed 200, 48,926 B |
| DB password | yes | Hostinger API + wp-config DB_PASSWORD | WORDPRESS_DB_PASSWORD | new login OK, old errno 1045 |
| WP salts (8) | yes | `wp config shuffle-salts` | n/a | site 200, logins work |
| WORDPRESS_PASSWORD | no | separate account (different value) | unchanged | Herman to handle |

Findings from the rotation:
1. `CAS_BUNNY_TOKEN_KEY` held the library API key (write access to the videos), not a token-auth key. It now holds the real pull-zone security key.
2. The old Bunny library key and the new DB password appear in Worker 1's session transcript. The old key is dead. The DB accepts only local connections.
3. The GitHub Actions secret copies are now stale.
4. `~/cas-backups/*wp-config*` set to 0600 (they hold old values).

Scope rule: the attacker could read only what was stored on the server (files and DB).
- **Not exposed** (never on the server): the Hostinger, Namecheap, Cloudways, Bunny and Tutor/Themeum account passwords, BUNNY_API, HOSTINGER_API_TOKEN. No rotation is needed for this incident, unless a password is reused.
- **Exposed, not rotated, low risk** (optional, needs Herman's vendor login): the Tutor Pro licence key (`tutor_license_info`), the Elementor Pro licence key (`elementor_pro_license_key`), and the Elementor connect secret (`elementor_one_client_secret`). Worst case: licence misuse.

The Tutor API key is in scope for a second reason: CVE-2026-85569 (Tutor < 4.0.8) lets a read-only API key perform admin-level actions.

## 6. Tutor update plan

| Item | Finding |
|---|---|
| Fix present | 4.0.8 and 4.1.0 (`classes/Withdraw.php` identical). Adds an instructor check and a field whitelist, and removes `esc_sql()` |
| Child overrides | login-form.php and order-placement-success.php are unchanged in 4.1.0. account.php and dashboard-isolated.php changed upstream, but the child copies still replace them |
| Hooks | All 12 used by the theme still exist |
| Risk | Visual only: un-overridden dashboard sidebar, registration and settings templates changed markup |
| Tutor Pro 4.1.0 | Covered by the 2026-09-30 logged-in handler audit (#101, done). Ships its own Guzzle 7.10.6. Update it too |
| Patchstack via Hostinger API | tutor 4.0.7 has 7 entries. 4.0.8 fixes 4, including CVE-2026-78175. Three more (reflected XSS, arbitrary post deletion via lesson_id by a Subscriber+, and student data exposure) are fixed only in 4.0.9. Target 4.1.0, not 4.0.8 |
| Verification | Worker 2 compared the code. Worker 3 runs a benign local probe against 4.0.7 and 4.1.0. An earlier attempt was blocked by a model safeguard; feedback was filed |

**Done 2026-09-30 (Worker 1, batch C):**
- tutor and tutor-pro updated to 4.1.0.
- The installed `classes/Withdraw.php` has `is_instructor` checks at lines 201 and 275, 0 `esc_sql(` calls in the function, and sha256 b385aafa…d1d.
- Six public pages: same HTTP codes and titles, +165 B each, 0 critical errors.
- `wp-config.php` set to 640.
- The wp-cli update output printed the Tutor licence key into Worker 1's transcript. Treat that licence key as exposed.

Remaining after the update: the `DISALLOW_FILE_MODS` decision, and reopening signup (map #99). The update and the visual check are done (above).

**Post-update verification (Worker 2, read-only):**
- Patchstack via the Hostinger API reports 0 vulnerabilities for tutor 4.1.0 and tutor-pro 4.1.0.
- The installed `Withdraw.php` sha256 matches the wordpress.org 4.1.0 file. `wp plugin verify-checksums tutor` passes.
- 13 public URLs checked: all 200 or the expected redirect, with 0 PHP error strings.
- Browser check of /student-registration/ ("New accounts are closed"), /login/ and /cart/: correct, 0 console errors.
- **Not verified:** signed-in student dashboard views, because no student account exists.

**Local regression test (Worker 3, Docker project `cas-cve-test`, since removed):**
- Benign probe: a real login, the real nonce, then POST `tutor_save_withdraw_account` with a plain marker string.
- 4.0.7: the subscriber is accepted (`"Withdrawal information saved!"`), and attacker-controlled field data is stored. The access-control flaw reproduced.
- 4.1.0: the subscriber is rejected ("not authorzied"), and 0 rows are stored.
- 4.1.0 also adds `WithdrawModel::safe_unserialize_array()`. It rejects any serialized object and calls `unserialize(..., ['allowed_classes' => false])`.
- Not reproduced: the object break-out itself. A model safeguard blocked that step, and Herman chose access-control plus code-diff proof instead.
- Gap: the legitimate instructor save path is unproven on 4.1.0. The site takes no instructor payouts, so the impact is low.

**Live regression test (Worker 3, 2026-09-29 23:01–23:03 UTC):**
- Temporary subscriber `cas-probe-fd8601` (id 8), real login, real nonce, benign marker only.
- Response: `{"success":false,"data":{"msg":"You are not authorzied to perform this action"}}`. 0 withdraw rows stored.
- User removed with raw SQL (17 usermeta rows and 1 users row). Admin count still 2, total users 2.
- audit.log captured `user_register`, `wp_login` (from 37.19.205.234) and the ajax hit. The ajax line was logged twice, because the specific withdraw hook duplicated the admin_init hook. The duplicate hook was removed and redeployed on 2026-09-30 (see the deploy note below).
- Tutor Pro 4.1.0: no `unserialize`/`maybe_unserialize` fed by request data. All 17 logged-out (nopriv) ajax actions check the nonce first. Capability audit of the logged-in handlers: Audit done 2026-09-30: 293 logged-in handlers read. No subscriber path to code execution, role changes, or orders/payments. Some access-control gaps exist; they are handled by a guard mu-plugin (PR to follow), by switching off Q&A, lesson comments and notes, and by private disclosure to the vendor. Details are kept off the tracker (#101).
- Note: server timestamps are UTC. Local time is HKT (UTC+8), so 2026-09-29 23:00 UTC is 2026-09-30 07:00 HKT. The clock is not wrong.

**Bug-class audit of tutor 4.1.0 (Hermes "Tester", relayed by Herman 2026-09-30):**
- Scope: tutor 4.1.0 non-vendor code only. tutor-pro was not in this audit.
- 11 `esc_sql()` sites, all building SQL strings. None reaches a meta or option write.
- The only place where request data becomes option keys, `Addons::addon_enable_disable()`, is admin-gated (manage_options) and has no `esc_sql`, so no length desync.
- Dynamic re-test on a fresh 4.1.0 with the exact 4.0.7 payload as a subscriber: rejected, 0 rows, no object stored.
- Not a finding, only a note: `QueryHelper::prepare_set_clause()` interpolates field names into raw SQL. Callers were not traced.
- Limits of the claim: this audit covers the esc_sql-then-unserialize class only. The logged-in handlers were audited separately, see the note above (#101, done). Also, 4.0.7 had other subscriber-reachable bugs per Patchstack (post deletion via lesson_id, student data exposure, reflected XSS), all fixed by 4.0.9 and so by 4.1.0.

## 6a. Monitoring (approved by Herman)

The mu-plugin `wp-content/mu-plugins/cas-audit-log.php` merges Worker 2's audit log with the admin alert:
- Audit log `~/cas-logs/audit.log`: user and role changes, logins, failed logins, password resets, app passwords, plugin and theme changes, key option changes, and every admin-ajax action name.
- Instant email to Herman when any user gains the administrator role.
- Daily WP-cron check: raw-SQL admin count, with an email if it is not 2.
- Deployed 2026-09-30 (Worker 1, batch D): server sha256 42de1dd9…6849 matches, and mu-plugins was restored to 555.
- Checks: the site returns 200, the test audit line was written, and the daily check logged `admin_count | 2 cas_owner,cas_dev`.
- The audit.log retention (90 days) was added to `~/cas-logs/rotate.sh`. What schedules rotate.sh is unknown (no crontab on the account); check hPanel Cron Jobs.
- **Email did not arrive before SMTP went live (see the two entries below)** (as first measured). wp_mail returned true, but mail.log gained no line. CORRECTION: the Gmail searches used a connector bound to hermanchan925@gmail.com, not the recipient herchan925@gmail.com, so "nothing in Gmail" was never a valid test of the pre-SMTP mail() path. Until SMTP went live the alerts did not reach Herman, and the same fault blocked signup email verification and order receipts. The SMTP change below fixes it.
- WP-Cron runs only on page visits, so the daily check depends on traffic.
- **Email fix (2026-09-30):** Herman started the Hostinger "Starter Business Email Free Trial" (order OR888e86…, expires 2027-09-29, auto-renews as paid) and created `hello@crystalau.com` (SMTP out on). The trial send limit is **100 per day** (not 1,000), with 100 recipients per message. DNS needed no change. The mailbox credentials are in `.env` as HOSTINGER_MAIL and HOSTINGER_MAILPASSWORD, and in wp-config as CAS_SMTP_USER and CAS_SMTP_PASS. Crystal's approval is pending (message in `docs/client-messages/2026-09-30-site-email-and-password.md`).
- **SMTP live and verified (2026-09-30):** Worker 1 added CAS_SMTP_USER/CAS_SMTP_PASS to wp-config (mode 640), deployed the phpmailer_init hook (server sha256 94a0f44…711e), and `wp_mail` returned true. Server SMTP login to smtp.hostinger.com:465 returned 235. The test email arrived in the herchan925 inbox (not spam). OPEN: the header auth (SPF/DKIM/DMARC) check is not measured yet. Worker 2's Gmail connector is hermanchan925@gmail.com, so it can read only a test that lands there; a first test to herchan925 could not be read.
- The duplicate withdraw-ajax audit hook is removed in this deploy.

**Closed by Herman 2026-09-30:**
- The Tutor Pro licence (Individual, 19A1…5889) is active on crystalau.com only.
- The Elementor Pro licence (Advanced Solo) is connected to crystalau.com only.
- No foreign activations, so no licence reset was needed.
- GitHub Actions secrets WORDPRESS_ADMIN_DEV_PASSWORD, WORDPRESS_ADMIN_OWNER_PASSWORD and WORDPRESS_DB_PASSWORD were updated from `.env` (2026-09-29 23:43 UTC).
- Crystal has been told her new password.

**hPanel checks (Worker 2, browser, read-only, 2026-09-30). hPanel shows HKT = UTC+8:**
- Malware Scanner: 1 malicious file, "Removed". `wp-content/624bf1c4c4.php` was discovered at 2026-09-21 19:17 UTC, 11 minutes after it was written. This confirms the scanner emptied that copy. The mu-plugins and uploads copies (18:53 UTC) are not listed. INFERRED: they were emptied the same way.
- Backups: daily website and DB backups exist. **2026-09-20 12:09 UTC is a certain pre-attack restore point.** 09-21 12:10 UTC is probably clean too. From 09-22 on, backups contain attacker state. No restore was done or recommended: the cleanup is verified, and a restore would undo the Tutor update and the rotation.
- Access logs: not offered on this plan. The hPanel Activity Log has no entries between 08-30 and 09-30, so the attacker never used hPanel. The raw web logs would need a Hostinger support ticket.

**Git brought in line with the server (2026-09-30):**
- PR #98 squash-merged (44d395a). It carries the video-signing code and the V1 fixes that were already live.
- PR #103 squash-merged (fc26e70). It adds `cas-audit-log.php`. The file sha256 in main equals the server copy (94a0f44e…711e).
- 13 merged GitHub branches and 21 local branches deleted.

**Sign-up reopening is deferred to a wayfinder map (2026-09-30):**
- Map: herman925/crystal-art-school #99, "reopen student sign-up safely".
- Tickets:
  - #100 (grilling): when and how to reopen.
  - #101 (research): audit of logged-in Tutor actions. Done 2026-09-30, closed.
  - #126 (task): deploy the Tutor ajax guard mu-plugin.
  - #102 (task): check the signed-in student pages on 4.1.0.
- #100 is blocked by #101 (done), #102 and #126.

**Payments work started (2026-09-30, outside incident scope, noted for continuity):**
- The base currency is GBP (Herman's decision). PRD line 66 ("HKD base") is stale. The Stripe account settles in HKD, so a 2% conversion fee applies unless a GBP settlement currency is added.
- tutor-stripe 1.0.9 installed. It uses Stripe Checkout Sessions, so Adaptive Pricing is possible.
- Private test course 269 (topic 270, lesson 271) created. Delete it after testing.
- The Stripe keys in .env are LIVE and are not used for tests. Test keys: STRIPE_TEST_PUBLISHABLE and STRIPE_TEST_SECRET. The PayPal keys are sandbox.
- Backups: `~/incident-2026-09-21/db-pre-payments.sql.gz` and `tutor_option.json`.
- Keys added to `.secrets/.env` on 2026-09-30: STRIPE_TEST_* (including STRIPE_TEST_WEBHOOK_SECRET) and CAS_PAYTEST_STUDENT_*. The CAS_PAYTEST_STUDENT_* keys are temporary. Remove them, and the test student, at cleanup.
- `CAS_BUNNY_TOKEN_KEY` in wp-config held the Bunny library API key (write access to the videos) until the 2026-09-30 rotation. It now holds the pull-zone security key, stored in `.env` as BUNNY_TOKEN_KEY.
- Test purchase result (2026-09-30): the order was paid by the Stripe webhook event `charge.succeeded`. A refund made in Tutor's admin order modal works (order 1's enrolment removed, order 2's kept, per the 'remove enrolment' checkbox) (production rows read by Dev, 2026-10-20); the Stripe-dashboard path was never run and, per code, leaves access in place for a completed, paid order (Stripe.php:194-200). UNVERIFIED: if Stripe sends charge.updated with status 'succeeded' after a refund made in Tutor's admin, Tutor would mark the order paid/completed again and re-enrol the student. Check this in Stripe test mode before live payments. Corrected 2026-10-20 (Review, refund item A); previous wording: "A refund works." Refund effect on enrolment depends on the path (Tutor 4.1.0, HooksHandler.php:329-400): the admin order modal honours the 'remove enrolment' checkbox (ticked removes access, unticked keeps it); a refund made in Stripe's dashboard does not change the Tutor order or enrolment: tutor-stripe 1.0.9 ignores `charge.refunded` and returns nothing to Tutor (payments/Stripe.php:194-200; ecommerce/PaymentHandler.php:86-88 in Tutor 4.1.0), so, for a completed, paid order, access stays until an administrator changes the order in Tutor's admin (verified by code reading of tutor-stripe 1.0.9 and Tutor 4.1.0, 2026-10-20; not tested against live Stripe); and a Tutor bug (enrolment lookup with no status filter) can leave access after a second refund of a re-purchase. Owner decisions pending: refund policy, and whether to fix the bug in theme code (#114). All Tutor order emails are off, and admin emails would reach every administrator, so turn them on only after choosing the recipients.
  - Corrected 2026-10-20 by code reading (verified by Review 2026-10-20 (code reading, Tutor 4.1.0 hashes b2723b4d…/44645502…); tutor-stripe file hashes Dev-reported; post-refund charge.updated UNVERIFIED): a refund made in Stripe's dashboard does not change the Tutor order or enrolment (see the HANDOFF.md note; trace and excerpts held outside the repo (vendor code; not committed)). **Previous wording:** "a refund in Stripe's dashboard removes access automatically; and a Tutor bug".
  - Corrected 2026-10-20 (cite only): `payments/Stripe.php:194-199` in the test-purchase result above is now `payments/Stripe.php:194-200` (the early return for `charge.refunded`). **Previous wording:** "payments/Stripe.php:194-199".
- Test artefacts left in place (cleanup is tracked in issue #118): test student user 9 (`cas-paytest-6188`), private course 269 with topic 270 and lesson 271, order 1, enrolments 272 and 273, Stripe test webhook `we_1ULBXPPSdaQ9165By3LF0X3Q`, Stripe test customer `cus_VLtVpQLJgEiPQu`.

## 7. Open items

| Item | Owner |
|---|---|
| DONE 2026-09-30: hPanel Malware Scanner history. One entry: `wp-content/624bf1c4c4.php`, Removed, 2026-09-21 19:17 UTC | Herman |
| DONE 2026-09-30: hPanel backup list. Daily backups; 2026-09-20 12:09 UTC is a certain pre-attack restore point | Herman |
| DONE 2026-09-30: hPanel access log. Not offered on this plan. Raw logs need a Hostinger support ticket (optional) | Herman |
| DONE 2026-09-30: Crystal has been told her new cas_owner password | Herman |
| DONE 2026-09-30: outbound email. Hostinger Email mailbox `hello@crystalau.com` exists (trial to 2027-09-29). The client message does not describe the compromise; disclosure to Crystal is Herman's decision | Herman, Crystal |
| SMTP through the phpmailer_init hook is live (done). Still open: DMARC rua, the SPF/DKIM/DMARC header check, then reopen signup with enable_email_verification (map #99) | Operator |
| Bunny account password and Hostinger hPanel password (human logins) | Herman |
| DONE 2026-09-30: Tutor and Tutor Pro to 4.1.0, and the visual check | Worker 1, Worker 2 |
| `DISALLOW_FILE_MODS`, reopen signup with email verification | Operator decision |
| DONE 2026-09-30: PR #98 merged (44d395a), git matches the server | Herman |
| Firewall rule to block `O:<digits>:"` in POST bodies (optional) | not decided |
| Remove ~/cas-backups wp-config copies once the rotation is confirmed | not decided |
| DONE 2026-09-30: `DOCS_AUTH_*` logins for docs.crystalau.com were rotated and deployed (they were not in the first rotation; bcrypt hashes in `public_html/docs_site/server/users.php`, same hosting account) | Worker 1 |

Tutor feature switches set 2026-09-30 (owner decision; each had an access gap): `enable_q_and_a_on_course`, `enable_comment_for_lesson` and `enable_lesson_notes` are all off. An unset `enable_lesson_notes` means ON in Tutor Pro, so it must be set explicitly. Gotcha: an in-code render of a Tutor 4.1.0 lesson cannot show these features, so verify in a browser.

## 8. Lessons

1. A plugin security release shipped with no changelog note. Watch CVE feeds (Wordfence, WPScan) for installed plugins, not only the changelog.
2. Open signup plus a subscriber-reachable plugin endpoint equals the whole site. Keep signup closed until email verification, the signed-in student page check and the logged-in action audit (#101, done) and the Tutor ajax guard deploy (#126) are done (map #99).
3. Do not delete a compromised user with `wp user delete`. It unserializes that user's meta. Remove payload rows with raw SQL first.
4. The site had no DB backup. Take a dump before any incident or recovery write.
5. The server had no access logs, which left one step of the attack unproven.
6. `wp plugin update tutor-pro` prints the licence key inside the download URL in its output. Never paste that output anywhere.

## 9. Logged-in handler audit and guard (2026-09-30)

- **Audit (#101, done):** 293 logged-in Tutor and Tutor Pro handlers were read. Result: 1 live HIGH (`delete_tutor_license`) and some dormant HIGHs. None reaches code execution, role changes, or orders and payments. Details of the gaps are kept off the tracker.
- **Guard:** the mu-plugin `cas-ajax-guard.php` denies non-admin access to the listed handlers (PR #127). PR #134 extends it to instructor applications and instructor sign-up posts, and to 2 dormant notification actions. The site takes no instructors.
- **Addons:** Tutor's Email and Notifications addons stay off for good (owner decision 2026-09-30). Q&A, lesson comments and lesson notes are also off. Site emails are our own theme code (#133, PR #136).
- **Disclosure:** the private disclosure draft to the vendor is held by the owner and is not in the repo.
