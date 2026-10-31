# Route B unified editing — source candidate, 2026-10-31

**Scoped local checks passed; native WordPress workflows remain untested. Not installed,
merged or deployed.** The source checkpoint originally deferred all testing. The owner
subsequently requested testing, and the bounded checks below were executed on 2026-10-31.

This candidate is on `takeover/client-editing-20261031`, based on main
`2e8bc45225011af9f40c360b962e1b6cbc089aa3`. It includes the lesson correction and the source
reconciliation of the already-published guide Sign out fix in separate local commits.

The existing field controls, cleaners, save guard, media selector and deferred regression
sources were reused from PR #201's frozen `45996731a30c640b41ada90c9ecc62e260a4e92c`.
The original PR/branch/worktrees were not changed or merged. Its hub edits were applied to
current main so the later Gallery Size/Type pointers and other merged releases survive.
PR #203's guide proposal and #204's log runbook were not merged or copied.

| Editing page | Existing fields | Route |
|---|---:|---|
| Home | 24: carousel 12, text 9, photographs 3 | `cas-content-home-page` |
| About | 6: writing 5, portrait 1 | `cas-content-about-page` |
| Blog | 1: introduction | `cas-content-blog` |
| Free Videos | 4: opening line and introductions | `cas-content-free-videos` |
| Site details | 7: contact/social 4, brand images 3 | `cas-content-site-details` |

The five pages cover the same 42 fields; this is not a whole-site construction score.
The new combined forms reuse each original cleaner and control. Each form binds its nonce
and snapshot to its page and actor, validates every field before saving, and uses the existing
single whole-option conditional write with unrelated values/autoload preservation and cache
readback. Invalid fields cannot intentionally trigger a partial group save. Actual native
SQL/cache/concurrency behaviour remains unverified.

Public image restrictions and default/clear behaviour are retained. Media scripts now load on
the combined pages too. Old fixed-group routes remain registered with their original guards
for bookmarks but are hidden from the submenu. No account, role, post type, template or upload
capability is added. Related content lists reuse the hub's capability-filtered links; the
public-page link shows saved content, not an unsaved preview.

Nine existing client chapters now describe these routes and Save buttons, explicitly as
drafts awaiting installation, checks and handover. A local guide build and the browser checks
below completed after testing resumed. No guide publication occurred.

## Executed local checks — 2026-10-31

- Host PHP **8.3.6**, Python **3.12.3**, Node **22.23.2**; these are not a claim of production
  PHP 8.3.36 parity. PHP syntax checks passed for all 13 changed PHP files; both changed
  JavaScript/configuration files passed Node syntax checks.
- **46 Python source-contract tests passed**, plus the standalone hub, lesson and lesson
  material/access source contracts. These establish source expectations, not native access.
- **195 in-memory PHP cases passed**: this includes the existing save guards, combined
  all-or-nothing text/image saves, stale/concurrent/foreign-field/nonce/snapshot cases and
  rendered combined field bindings. The editor stub does not prove native TinyMCE.
- **9 mocked media JavaScript cases passed.** No actual upload/picker/browser acceptance.
- **4 lesson-fragment render tests passed after a real fix**. The first run found that an
  empty lesson description removed the page's only H1. The heading now renders independently
  of description content, with its existing styles. The description still uses lesson text
  once and the lesson's own cover, with no course-text/photo fallback. Native KSES/Tutor and
  the whole enrolled lesson screen are not covered by this controlled fragment test.
- VitePress **1.6.4** built successfully using existing dependencies, with output outside
  the repository. Ten guide pages rendered with expected titles/headings and no blank/error
  overlay or page-width overflow. Sidebar navigation and local search were exercised; the
  tested search returned 16 results. Desktop 1440×900 and mobile 390×844 screenshots were
  retained. Browser plugin was unavailable; existing Playwright 1.61.1 and installed Google
  Chrome ran headlessly with a fresh, owned browser context, without account credentials.
- Clicking local Sign out generated a **same-tab document navigation**, not fetch/XHR.
  The static test server has no PHP logout endpoint and returned the expected 404. This
  proves navigation only, not session destruction or live protected-page denial.
- A local `/favicon.ico` 404 was the only pre-logout console error; it was attributed by
  console URL and retained, not called a clean console pass. No app JavaScript errors or
  warnings were observed before logout. Earlier browser attempts failed on a missing
  bundled browser, a zero-width heading-anchor assertion, and favicon attribution; those
  failures and original artifacts remain retained.

Private logs, screenshots, build output and failed attempts are retained under
`~/cas-evidence/takeover-tests-20261101-r1/` (run on 2026-10-31; the directory name is an
identifier, not the test date). They are not GitHub attachments. Guide build launch r1
failed on an incorrect dependency-link path; corrected r2 built successfully. No historical
incident evidence, account, progress marker, provider or production state was changed.

The only running WordPress environment is the old review stack, with older mounted source
and held incident state. It was inspected for container/mount identity only, without
bootstrapping WordPress or changing its files/database. A designated approved staging target
or a separately chosen fresh local instance is required for the remaining native checks.

For native testing, include combined text/image saves, all-or-nothing validation, stale and
foreign inputs/nonces/snapshots, concurrent saves/cache readback, restricted-role routes and
native upload/pick/replace/default/clear, editor persistence and mobile/accessibility behaviour.
Carry forward the remaining #211 learner/payment/guide/human checks and incident holds.
Actual merge/deployment/publication/account handover remain separate gates. Choose the source
integration path deliberately; do not blindly merge both this candidate and the older PRs.
