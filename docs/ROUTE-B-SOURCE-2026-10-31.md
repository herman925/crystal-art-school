# Route B unified editing — source candidate, 2026-10-31

**Merged and deployed on 2026-10-31. Source and scoped native fixed-editor checks passed.**
The source checkpoint originally deferred all testing. The owner
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

Before the round review, the only running WordPress environment was the old review stack, with older mounted source
and held incident state. It was inspected for container/mount identity only, without
bootstrapping WordPress or changing its files/database. A designated approved staging target
or a separately chosen fresh local instance was required for the native checks completed below.

## Native review and release preparation — 2026-10-31

The owner subsequently requested a round check and deployment. A **new**, independent Docker
project `cas-takeover-20261031-084505` was created with fresh synthetic data. The held old
stack, shared accounts and learner fixtures were not changed. This environment uses PHP
8.3.36, WordPress 7.1.3, Tutor 4.1.3 and Elementor 4.3.5; Tutor Pro is absent. It has an
internal-only Docker network, blocked external WordPress requests and disabled mail.
This is sufficient for the native fixed-editor checks below, not full production parity.

Twenty native browser checks passed across login/menu restrictions, the five forms and all
42 fields, Home SQL/cache readback, stale tabs, unknown fields, bad nonce, public selection,
private-marked-image rejection without partial text save, clear, About/Blog/Free Videos native
TinyMCE save/reload, shared contact save/reload, subscriber denial, mobile layout and browser
exceptions. Additional native file-chooser checks passed for PNG upload, replacement and
shipped-default restoration. Final DB checks confirmed saved values, preserved unrelated
settings, rejected foreign fields, retained media files and unchanged restricted capabilities.

Two real defects were found and fixed before release:

- About's `style_formats` must be JSON for the WordPress TinyMCE initializer; the original PHP
  array caused `Cannot create property 'name' on string 'A'` and prevented rich editors loading.
- WordPress does not observe uploads for a query containing the custom nonce filter. Completed
  uploads now trigger a scoped server-filtered library refresh before selection, preserving
  public/private validation instead of admitting the unfiltered upload queue.

The PHP synthetic suite remains 195 passing cases with a new TinyMCE settings assertion; the
media JavaScript suite now has 14 passing cases. Existing failed runs, including environment
setup and test assumptions, are retained privately under
`~/cas-evidence/overhaul-review-20261031T084505Z/`. No credentials or raw logs are uploaded.

Production preimages of all four replaced existing runtime files matched current main; ten
new files were absent as expected. Only those 14 theme files are in the runtime release.
The guide release preserves the current 22-page inventory and published navigation, excludes
the six unpublished drafts from pages/chunks/search, and replaces their links with handover
references. All 18 changed document bodies were reviewed; other document text is unchanged.
Authentication, reader configuration and server files are excluded and hash-guarded.

The normal guide configuration now excludes `*-draft.md` sources and their sidebar group.
The final exclusion check includes all six draft source files and requires exactly the same
22 published HTML paths with no draft routes/content in generated chunks or search. Draft
source files remain in the repository. The full `deploy.sh` is still not used because its
authentication/user-rewrite and replacement behaviour is outside this release.

GitHub's connector rejected creation of a new PR with HTTP 403. The repository's normal Git
transport can push the reviewed branch; release integration uses an ordinary fast-forward
of main, subject to GitHub branch protection, without retrying the denied PR-creation action.

Native full course/five-photo/material authoring, production record ownership, Tutor Pro,
enrolled learning/completion, provider payments, real-reader PHP logout, human assistive
technology and launch/account decisions retain their separate outstanding gates. No weighted
acceptance-score increment is claimed by these new checks.

## Production release receipt — 2026-10-31

- Release commit **`4116b5f3c8b15ff916f2b5aff7c204970186eb96`** was pushed to main by an ordinary
  fast-forward after the GitHub connector rejected PR creation with 403. No branch protection,
  permission or repository setting was changed; the denied PR creation was not retried.
- **14 theme files and 86 guide files were installed and read back by SHA256.** All 22 guide
  HTML paths match the pre-release inventory. Seven authentication/server-file hashes and
  retained old assets are unchanged. Thirteen unchanged runtime dependency files also match
  the reviewed repository source. Native PHP 8.3.36 syntax checks passed for all 12 deployed
  PHP files before installation. Hostinger disables PHP `exec`; syntax checks used the normal
  SSH shell instead, without changing that restriction.
- Preimages, metadata and a conditional owned-file rollback receipt were preserved privately
  at `/home/u202887698/backups/cas-overhaul-20261031T084505Z/` on the server. The staged release
  and verification/rollback helper remain at `/home/u202887698/deploy-cas-overhaul-20261031T084505Z/`.
  Those private files are not public repository artifacts.
- Public smoke: Home returned **200**, its main/heading and noindex were present; served editor
  JavaScript and lesson CSS exactly matched the release hashes. Known preview lesson **53**
  returned **200**, rendered the custom lesson template with exactly one H1, and used no
  authenticated cookies or completion POST. This proves one preview, not both preview flags.
- Signed-out guide root, editing and About pages all returned **302 to the existing login**.
  This is protected-page denial evidence, not real-reader PHP logout/session verification.
- Real account roles, ownership, course prices/orders, signup, live payments, indexing and
  incident-held operations were not changed by the release. The restricted production role
  remains unassigned. No production account login, purchase, refund or account cleanup ran.
- The original PR #201/#203/#204 branches and evidence remain preserved; this release already
  incorporates the fixed-field implementation with the native corrections. Do not merge an
  older candidate blindly over the released versions. The log/core operational work remains held.

Full course/five-photo/material authoring, production ownership handover, enrolled learning,
provider payments, real-reader guide logout, human AT/devices and the other #211 launch gates
remain open. **Last saved acceptance coverage stays 63/110; this release is not launch approval.**
