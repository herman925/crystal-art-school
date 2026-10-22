# Route A owner switch + access-matrix preparation — NOT EXECUTED

2026-10-23. This is SOURCE/DOCUMENT PREPARATION, not a runbook approval or a claim that production matches it. Preserve the original operator draft at `.claude/scratch/Dev-Team-Worker-1-switch-draft/SWITCH-DRAFT.md`; this addendum supplies the newly required provenance/native-builder gates without rewriting old evidence.

**No demotion, production metadata write, provenance stamp, deployment, payment change or migration is authorised here.** Final detailed money guide, actual Review sign-off on the complete combined release, a reviewed fresh manifest and the person's separate final OK are required before the controlled switch window.

## 1. Freeze the real release and inventory before mutation

The actual integrator/authorised operator, not this Dev continuation, must record:

- Exact merged release SHA and loaded/served hashes for role, Customizer allowlist, price/shared-writer, native builder, private files, Sales, Coupons, Enrol, early guards and audit logger. Price #180 `917a8d7` and builder #181 `c9aaa86` were frozen for Review; their review result cannot be silently applied to a changed head or an untested combination.
- Actual account IDs/logins/roles/direct caps/admin set; expected historic IDs are cas_owner **1** and cas_dev **2**. Verify fresh, do not infer identities from display names. cas_dev remains a working independent Administrator with SSH fallback.
- Raw `wp_user_roles`, `cas_content_role_version`, target user capability rows and session fallback. Reviewed limited role includes the approved own-course/lesson edit/publish caps, not `edit_others_*`, manage_options, edit_theme_options or WP instructor/admin roles. A mismatch blocks the switch.
- Exact content/template author manifest (the previous draft proposed 25 author-0 content rows → 1 and template IDs 5/29/30/205 → 2). These are old expected counts, **not a new inventory**. Record each real ID/type/status/author, compare immediately before guarded updates and stop on drift. Preserve developer-owned test courses/lessons/topics/posts; do not run a global author-0 rewrite.
- Current raw Tutor option bytes, signup=0/guard state, email/audit health and current `CAS_AUDIT_EXPECTED_ADMINS`/mu-plugin hash/mode. Do not alter publish settings, gateways/payment modes, addon toggles or signup to make tests pass.
- Raw cas_owner user-meta rows for `_is_tutor_instructor`, `_tutor_instructor_status`, `_tutor_instructor_course_id`. Head's last read found the first two ABSENT; recheck. Save meta IDs and raw values privately. Never call `approve_instructor()` because it adds a WP role. Do not use broad instructor/admin grants or metadata spoofing.

All raw capability/usermeta/manifest backups and file-path evidence belong outside public_html and git, directory 700/files 600. No customer data or credentials in chat/PRs.

## 2. Private-reference inventory — zero means NO migration

Head's last production inventory reported **0 real attachments**. The authorised operator must perform a FRESH read-only inventory of real course/lesson `_tutor_attachments` sources and exact referenced attachment IDs, their current parent chain/current course author, file identity/private flags and any `_cas_private_author_refs` rows. Distinguish exact developer test IDs from real course content; do not use a title wildcard to approve/delete/ignore records.

- **If still 0 real attachments:** record **“no migration required”** with the inventory evidence. No provenance bootstrap, no empty-list stamps, no sweep, no extra migration work. Proceed to the other gates only.
- **If legacy real references exist:** STOP the zero path. The operator reviews each exact entry: attachment ID, source ID/type (actual course or real lesson), current course ID, source/topic/course ownership and parent-chain rows, EXACT current source list, attachment/file identities, private paths, original + declared-size SHA-256, and raw binding/list/security-meta preimages. A current-but-untrusted reference alone is not authority. Do not auto-approve all inventory rows or use old growing course-map metadata, attachment author or instructor metadata as proof.

The bounded module helpers are for this explicit reviewed case, not an automatic migration framework:

```php
// READ ONLY, authorised operator captures each reviewed actual source ID separately.
$preimage = cas_private_reference_snapshot($reviewed_source_id);
// false => malformed/missing/non-private file or graph; investigate; never guess replacement IDs.
// Store the exact emitted snapshot privately, review against the intended actual resource.
```

A valid legacy entry requires an ABSENT prior author binding and a nonempty real attachment list. A pre-existing different binding needs separate review; it is never overwritten by the helper. Store the approved manifest byte-for-byte (including array order) with its SHA-256 and exactly which source IDs are approved.

## 3. Backup and controlled switch window — only after final OK

Take a verified full DB backup plus exact raw per-ID author/capability/Tutor/provenance preimages, audited release files/hashes, current audit expectation file and permissions. Verify backups are readable/nonempty. Establish a short maintenance window with no competing course/material/file edits, ahead of the daily admin-count check. Keep the independent cas_dev session and SSH fallback open.

Order inside that approved window:

1. Re-read EVERY manifest precondition immediately before writes: release/hash, account identity/caps/admin set, exact author partitions, Tutor option bytes, Tutor user-meta and all legacy source/graph/list/file preimages. Drift means stop, not a widened predicate.
2. Only if the reviewed nonzero legacy manifest exists, apply each approved entry with `cas_private_apply_reference_manifest($approved_snapshot)`. It compares exact graph/owner/parent/list/file/security-meta preimages, writes only the pinned source/course/list binding and rechecks the result. Any false/stale/failed/mismatched stamp **BLOCKS SWITCH BEFORE DEMOTION**. Do not stamp a changed resource, use a growing map as fallback, or grant instructor/attachment-owner download access. If zero inventory, skip this entire step and retain “no migration required”.
3. Provision ONLY the reviewed internal Tutor metadata for actual cas_owner if its exact approved preimage still matches: `_is_tutor_instructor` uses the native `tutor_time()` format; `_tutor_instructor_status` is exactly `approved`. No WP tutor_instructor role, no `_tutor_instructor_approved` field unless separately justified, no global instructor-publish toggle. Pin/read back the exact new values and raw row IDs for rollback. Actual mutation remains operator work after final approval.
4. Inventory native `_tutor_instructor_course_id` links against exact REAL current courses owned by cas_owner. Preserve all existing rows; do not attach developer-owned courses or trust stale links for access. If links are already correct, do nothing. Missing links must use Tutor's verified native own-author save path (Course::save_course_meta) for only those reviewed IDs, with empty/controlled POST and demonstrated no unrelated meta/price effects in Review. Do not call the broad instructor-approval API or blindly fire every save_post/addon hook. If that narrow operation has not been reviewed/proven, it is a switch blocker, not permission to invent raw linkage.
5. Perform the separately reviewed exact author-0/template ownership updates from the original manifest, using ID+type+old-author predicates and exact affected counts, then invalidate only affected caches. No change to deliberately unchanged course/lesson/topic/test ownership.
6. Set ONLY cas_owner to the reviewed cas_content role and change ONLY the reviewed audit expectation from 2 admins to 1 in the same controlled window, preserving all other audit/SMTP code. Handle the mu-plugin directory 555 mode only for that exact replacement and restore it. No half-state may remain: on failure restore both role and audit preimages immediately.
7. Fresh readback: cas_owner exactly cas_content with no direct/broad caps/extra roles; cas_dev remains Administrator; admin set exactly `{cas_dev}`; audit expectation=1 and alerts/logging healthy. Confirm internal Tutor rows/linkage are only the intended ones, raw global Tutor option bytes unchanged, provenance manifest exact/read back (or zero/no migration), signup=0 and all unchanged ownership partitions preserved.
8. Run the bounded REAL-role acceptance matrix below with the actual Review-approved procedures. No release sign-off solely from a menu disappearing or a capability probe. Any forbidden access or failed authorised task blocks handover and requires rollback or a separately reviewed correction.

## 4. Access matrix to execute — all statuses currently NOT RUN here

Use the actual switched user's own courses, a deliberately different-author synthetic control and safe synthetic order/student/coupon fixtures. Never manufacture a real charge/refund to test permissions. Evidence must record exact release, actor, object ownership, route/method, response/observable result and before/after persisted state; never fetch unknown wp-admin links by crawling.

| Surface | Required allowed result | Required denied / unchanged result |
|---|---|---|
| Login/hub | cas_content lands at the Content hub; safe approved content subpages accessible | Student login still follows its own dashboard route; closed subpages do not become accessible |
| Native builder | Hub Add course uses POST+nonce, NEW own paid draft, content editing, real topic/lesson/reorder; valid first-price confirmation then publish | GET create, bad/missing nonce, malformed ID, other author/stale link, forged author, legacy alternate creation, free-course conversion and pre-price publication refused |
| Price gate | Current paid type/old-new values shown; positive regular, blank-or-positive-below-regular sale; valid confirmation updates future prices only | Zero/invalid/type change, stale/replay/concurrent gate/native writer, audit/write/rollback failure fail as reviewed; orders/history unchanged; no silent discarded admin save |
| Author course files | Non-enrolled actual cas_content current course author+cap reads current trusted course notes/lesson reference image, actual bytes and image sizes; native picker/reference renders | Guest/unenrolled student/editor/instructor/other-course author denied; guessed assignment, stale map, detached list, forged parent/course transfer, marker/path/bymid tamper never mint access |
| Legacy references | Exact reviewed nonzero manifest stamps/readbacks; no-op repeat and exact rollback proved; zero case does no work | Stale/tampered source/owner/parent/list/file preimage blocks BEFORE demotion; no broad bootstrap or fallback |
| Enrolled students/previews | Existing enrolled download/progress/video and preview policies stay as reviewed | Public preview never grants file downloads to a guest/non-enrolled student; this author fix is not an enrolment grant |
| Content/media | Approved own posts/FAQ/videos/gallery/About editable fields + legitimate new public media uploads work | Other author content, pages, Elementor templates/kit/layout, unapproved Customizer fields remain closed; attachment ownership alone grants no private read |
| Sales | Reviewed limited Sales screen/filter/detail works against synthetic orders, historical totals preserved | Refund, order amount/state/gateway edits and Tutor native order/admin endpoints closed |
| Coupons | Only the reviewed scoped create/read/edit actions, clear validation/audit and guide match | Unsupported scope/type/amount abuse, other protected administration and crafted endpoints refused; no unrelated order/history mutation |
| Enrol | Reviewed manual access operation and readbacks/audit against synthetic student work | No user-role/admin grant, arbitrary account/password/payment edit or unapproved revoke/refund semantics |
| Admin/instructor | No extra WP role; only internal metadata/context required by reviewed native builder | Users/plugins/themes/tools/options/menus/Tutor settings, payout/withdraw/refund/public instructor applications remain closed even with valid Tutor nonce/crafted GET/POST |
| Guards/audit/mail | Audit reports correct admin count/security outcomes; approved email path and signup lock stay intact | No signup enable link bypass; no global publish option persistence; no security/smtp suppression or secret logging |
| Guide/handover | Detailed money guide is reviewed/published and matches actual tested screens/outcomes | No claim of complete Route A from this partial source candidate; final owner OK remains separate |

A source-level pass does not clear any row. If a native picker remains filtered despite a valid file download, record that specific UI blocker; do not widen general media powers to hide it.

## 5. Exact rollback — no global restore-by-guessing

On any failed switch gate, keep/restore cas_owner Administrator FIRST so they are not stranded; restore the exact audit file/expectation/mode in the same controlled window and verify both before closing fallback sessions. Then use the saved per-row manifests to undo only this operation:

- Restore target capability/role rows and exact changed authors using recorded postimages as compare predicates; preserve deliberate old/test ownership.
- For newly added internal Tutor metadata/linkage, remove only exact new raw row IDs/values if still matching the switch postimage. Restore any authorised changed preimage exactly; never remove existing links broadly. Any unexpected row/value is drift and blocks automated cleanup.
- For each newly stamped legacy entry, call `cas_private_apply_reference_manifest($approved_snapshot, true)` only while the pinned graph/list/file identity and exact stamp still match. It removes only that binding and verifies absence. A no-op original/rollback is explicit; any changed resource or different binding is left untouched for operator review. If original inventory was zero, **there are no provenance mutations to roll back**.
- Verify restored admin set/expectation, raw role/Tutor option bytes, provenance/author partitions, signup and fallback access. Do not delete shared logs, evidence, backups or unrelated files. Capture what actually rolled back and any unresolved drift.

No commands in this document were executed by Development. Final manifest values, actual source-to-served hashes, Review matrix results, detailed guide and final person approval are still missing and cannot be filled in from old screenshots or assumed counts.
