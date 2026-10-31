# Route B unified editing — source candidate, 2026-10-31

**Untested, not installed, not merged or deployed.** The owner authorised source work and
deferred all testing to a later combined pass. No application, build, lint or test was run.

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
drafts awaiting installation, checks and handover. No guide build or publication occurred.
The existing regression sources were extended/adjusted for later execution; no result is claimed.

When testing resumes, include combined text/image saves, all-or-nothing validation, stale and
foreign inputs/nonces/snapshots, concurrent saves/cache readback, restricted-role routes and
native upload/pick/replace/default/clear, editor persistence and mobile/accessibility behaviour.
Carry forward the remaining #211 learner/payment/guide/human checks and incident holds.
Actual merge/deployment/publication/account handover remain separate gates. Choose the source
integration path deliberately; do not blindly merge both this candidate and the older PRs.
