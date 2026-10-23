# First price/native-builder candidate — Review handoff (2026-10-23)

**SOURCE CANDIDATE ONLY. No merge, deployment, owner-role switch, production reads/writes, or runtime acceptance was performed in this continuation.** Development prepares source and fixtures; the actual Review team designs and runs runtime/E2E/acceptance and records its own evidence.

## Revisions and dependencies

- Price/shared-writer: existing PR **#180**, branch `feat/price-gate-routea`, **917a8d7196435246d23278b157c0deb50389730a**; main base **ed29780** (#178).
- Builder: separate stacked PR, branch `feat/content-native-builder`, base `feat/price-gate-routea` at the SHA above. The PR head is the commit containing this document (read `git rev-parse HEAD` / the PR head, not an invented SHA in a self-referencing document).
- Both candidates must be present for native draft creation/first-price publication. Price includes protected initialisation/publication hooks; these require the builder's fresh-request/own-author/nonce predicates and are dormant without it. Builder refuses to run without the price-publication guard.
- **Original #180 evidence applies only to 795ca5c4080759d77b58aafa11e13f0aec98b077.** It is retained unchanged in the PR comment and `.claude/scratch/Dev-Team-Worker-1-switch-draft/PRICE-795ca5c-EVIDENCE.md`. The newer paid-only/native-writer/builder changes have NOT inherited those runtime results.
- Original price recovery worktree, `*-W1-3-ready`, builder-adapter and builder-proof worktrees, original WIP commits 2301606/5c8f082, and operator `SWITCH-DRAFT.md` are intentionally retained. Do not reset/clean them as disposable test output.

## Changed entrypoints

Theme-relative paths below are in `wp-content/themes/hello-elementor-child/`.

| Entry | Responsibility |
|---|---|
| `inc/cas-content-role.php` | Own-course/lesson editing, `publish_tutor_courses`, role version 3; no admin/instructor role or `edit_others_*` grant. |
| `inc/cas-price-gate.php` | `admin_post_cas_price_request` / `admin_post_cas_price_confirm`; regular/sale/type metadata filters; native `wp_insert_post_data` and `save_post_courses` shared locks; protected fresh-draft paid initialisation; Tutor, REST and core publication checks. |
| `inc/cas-content-builder.php` | Exact native `create-course` menu callback/allowlist, own-author+capability gate; hub Add course form; native AJAX preflight for create/read/update and topic/lesson/sorting; request-only publishing overlay and anti-persistence filter; payout/settings/refund/application refusals. |
| `inc/cas-content-editor.php` | Loads builder after price gate. |
| `js/cas-content-builder.js` | Posts the native draft action with its nonce; follows only a same-origin native builder URL; displays a failure rather than a false success. |

The adapter calls Tutor's `Course(false)->load_course_builder_view()` and its existing AJAX handlers. It does not replace the authoring UI. The old alternate `tutor_create_course` action is explicitly denied; the approved flow uses `tutor_create_new_draft_course` then `tutor_update_course`.

## Existing local stack and source-to-served verification

- Existing project: **cas-local**, `tests/local-runtime/docker-compose.yml`, private env `/home/herman925/cas-local-runtime/cas-local.env` (do not print it).
- WordPress local URL `http://127.0.0.1:18089`; container `cas-local-wordpress-1`; Mailpit bound to `127.0.0.1:18026`. Named DB/WordPress volumes remain the existing ones. Do not start W2's separate stack, pull/build images or create another stack for this handoff.
- Compose maps `${CAS_CHILD_THEME_SRC:-../../wp-content/themes/hello-elementor-child}` read-only to `/var/www/html/wp-content/themes/hello-elementor-child` for WordPress and CLI. Default is main, **not this candidate**.
- For the combined candidate, Review should use the existing override mechanism with the absolute theme directory in `.claude/scratch/Dev-Team-Worker-1-builder-adapter/`. The price-only worktree is `.claude/scratch/Dev-Team-Worker-1-W1-3-ready/`.
- **Development did not inspect/recreate/start/stop the stack in this continuation. Served hashes are UNVERIFIED.** Review must record the effective mount and compare loaded files in a fresh PHP request to the source hashes below, plus check the served JS bytes (not just the checkout).

| File | Source SHA-256 |
|---|---|
| `inc/cas-content-builder.php` | `5b6c4575d217eac03f7095eaf85d75339e64d213877f74ffba36e887b5848e2d` |
| `js/cas-content-builder.js` | `b741270c95fddf7441171c7cd2bc29134d8ca5a528f1479ec7157aeeb4dc66c9` |
| `inc/cas-price-gate.php` | `b313288ed9924b5908f6136a898a6ec320ae82260c59f2455bc6292f38d6e10e` |
| `inc/cas-content-role.php` | `e86e3a9fdf3d1b7aac4ee5842edbcaa254d1642044b68e896a49fbe5f3638c1d` |
| `inc/cas-content-editor.php` | `4ee8cfec51659a904e42badcdfc61fa16389c2ba55bc18cc46af659893ae0c8c` |
| unchanged `inc/cas-private-files.php` | `4b23ad3a52f1a3d3826cff31d8bb18339561e33a002f1641809c3aed7a0aeb29` |

## Synthetic fixtures — prepared, NOT executed

`tests/fixtures/cas-w1-builder.php` is an explicit local-only setup/cleanup script. Run it with WP loaded, `CAS_RUNTIME_TESTS=1`, no limited `--user`, and `$args[0]` equal to `setup` or `cleanup` (for example `wp eval-file <copied-fixture-path> setup`). The script requires a localhost home URL on **port 18089**, refuses existing fixture usernames/ledger, and never changes an existing account. Review owns execution, capture and cleanup.

- Synthetic logins: **w1builderowner**, **w1builderother** (`cas_content`), **w1buildersub** (`subscriber`). Fresh random passwords are output once; protect captured JSON as mode 600, do not commit it.
- The first two get REAL internal Tutor `_is_tutor_instructor=tutor_time()` and `_tutor_instructor_status=approved`, as explicitly approved for synthetic proof. No WP instructor/admin role is added. These match the fields read by Tutor 4.1.3 `User::is_instructor()` / `Utils::is_instructor()`.
- Course::save_course_meta() (Course.php:2024–2053) natively adds `_tutor_instructor_course_id` to the actual `post_author`. The fixture checks this native link exists; it does not spoof or manually create it. New courses created by the native draft action use the same save hook.
- Draft courses: own paid (£50, legacy zero sale), other-author paid, own free. Own topic → lesson, own course PDF notes and own lesson private reference PNG. **No enrolment** is created; output reports the actual enrolment lookup and private flags/URLs.
- IDs are assigned by WordPress and emitted with exact authors/parents. **No fixture IDs exist from this continuation**; Review must record the emitted IDs, plus each NEW draft/token created by its HTTP/E2E tests. Never substitute production IDs.
- The ledger `cas_w1_builder_fixture` stores only synthetic record IDs and the exact owned upload directory. Cleanup verifies per-record ownership markers, deletes only ledger records/users and their private attachment files, then removes that exact empty upload directory. It deliberately does not delete unrecorded native-created drafts/tokens; Review must record and clean those separately. It does not change signup or the global Tutor publish option.
- Private-file migration needs the existing local `/var/www/cas-private` writable by the web/CLI user, as in `tests/test_private_course_files.py`. Check actual private flags after setup; a printed `?cas_private_file=ID` route alone does not prove migration.

Prepared regression scripts: `tests/test_price_gate_contract.py`, `tests/test_price_gate_runtime.py` (now includes paid-only/zero/stale-type and contended native row/meta writers), `tests/test_content_builder_contract.py`, existing role/page contracts and `tests/fixtures/cas-w1-price-fault.php`. These have only been syntax-checked in this continuation. The price runtime harness copies/removes audit/fault plugins and touches a synthetic log: Review must inspect its setup/cleanup and confirm exclusive local ownership before running it.

## Invariants for independent acceptance

These describe the intended contract, not claimed test results.

1. Existing course gate accepts only type **paid**, a positive regular price, and sale blank or positive below regular. Submitted zero sale is invalid; a stored legacy zero sale is displayed/typed as no sale. Free/unknown types cannot be converted. Snapshot includes type as well as both amounts; a later type/amount change rejects confirmation.
2. A user-bound, expiring one-use token is claimed atomically, then the shared course lock is acquired before stale reads/writes. Same-token replay and different-token contention must not produce a second write or success audit. Compare-and-delete expiry/release must not remove a successor's lock.
3. Gate and normal native administrator/Tutor/core metadata saves of regular/sale/type use the same lock. A contended native row save returns a clear retry **before the row or price changes**, not a successful silent discard. Direct metadata mutations similarly refuse before their mutation. Native price pairs stay under one request lock, released at shutdown.
4. Attempt audit must be durable before price mutation; verified price audit follows paired fresh readback. First/second-write faults, audit failure, rollback failure and third-party values retain the protections recorded at 795ca5c. Orders/items/history never change. The lease is 600 seconds; raw SQL bypasses the WordPress metadata API and is outside this coordination, not claimed safe.
5. Hub Add course is authenticated POST+Tutor nonce, no mutating GET. Exact native page accepts only a real non-trashed course currently authored by this limited user with edit permission. Subscriber, other author, invalid nonce, malformed IDs and crafted author overrides fail closed.
6. New draft initialisation is allowed only for an ID created in the same native request, still own draft/auto-draft, with no price or prior pending marker. It becomes paid + protected pending-first-price; it does not convert an existing free course. Vendor default free metadata cannot overwrite it.
7. First price uses the same two-step confirmation/audit path. Publication through native Tutor, REST and core is refused before mutation until positive valid paid pricing and no pending marker. If marker deletion fails **after** verified price/audit, report `initial_pending`: price is saved, publication remains blocked; do not falsely claim rollback or write failure.
8. Native own-course topic/lesson/sorting uses current parent chains and actual course author, not just Tutor's possibly stale instructor link. Mixed-course topic/lesson requests and stale links must not grant access. Tutor 4.1.3 validates every sorting topic/content against the resolved course before its direct SQL move; retain and verify that vendor behaviour.
9. Publishing permission exists only in the authenticated own-course native update context and real `publish_tutor_courses` cap. The request-only option overlay handles absent options/keys and false defaults. Raw stored `tutor_option` bytes must remain unchanged across render, create, ordinary save, publish and an overlay read-modify-write attempt; no globally enabled instructor publish setting.
10. Own content edits still save in the same native UI. Changed regular/sale/type values outside Course prices receive 409 with an actionable message. Paid-course public-access flags are refused rather than opening an alternate no-payment route. Review must check real UI payloads, toast text, Add course redirect and price-link visibility; Dev has not browser-tested them.
11. No settings, refunds, payout, public instructor application, other-author editing, users/plugins/templates powers or instructor/admin WP role are granted. Test crafted GET/POST/action mismatches as well as ordinary screens; merely hiding menus is not evidence.

## Explicit private-file author blocker — separate next PR

Unchanged module's last touching SHA: **8f4f058280c32f7c59ff2a21628dc982f134fb7c**. Its current download route is **`/?cas_private_file=ID`** (optional size suffix handled by that module). `cas_private_can_download()` currently admits `manage_options` or an enrolled student. A non-enrolled `cas_content` author can therefore be denied their OWN course materials/private reference image even with valid course ownership and editing permission. The fixture exposes exactly this case; this candidate does not claim to solve it.

CEO approved a **separate focused follow-up**, not bundled into #180/builder: current actual cas_content+manage_cas_content+edit permission+actual current course author, plus genuine current attachment-to-course/lesson references. Stale accumulated course-ID metadata is not authority. Guard forged assignment writes too: guessing another course's private attachment ID and assigning it to one's own course must not create download authority. Admin/enrolled behaviour remains unchanged. Review should check the present mismatch and media picker/lesson/file routes now; the fix waits for its next sole-worker slot after this handoff.

## Release and effort boundaries

This is the first coherent build-ready candidate, not completion of Route A. Sales/coupons/enrol, the detailed money guide and final owner OK remain separate gates. Actual cas_owner internal Tutor metadata is currently absent (Head's read); any future provisioning requires the final switch manifest, exact raw preimage/postimage/rollback, and only reviewed ACTUAL own-course linkage IDs. The old operator draft is retained, not executable approval and not yet updated to a release-ready manifest.

Development source/fixture preparation for this candidate is complete. Runtime discoveries may require follow-up fixes; none are estimated away. Review, the private-file author fix, full Route A integration/guide, merge/deploy and role switch remain unperformed. Worker 1 pauses and releases the sole worker/runtime slot to the actual Review team after reporting the exact PR heads.
