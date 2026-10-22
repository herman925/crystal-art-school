# Private-author files — separate source candidate / Review handoff

2026-10-23. **Implementation + test/fixture preparation only. No runtime, browser, Docker/stack, production, migration, merge, or owner-switch action was executed by Development.** Actual Review designs/runs acceptance on its one isolated synthetic stack. Frozen price #180 `917a8d7196435246d23278b157c0deb50389730a` and builder #181 `c9aaa8609ef78eb29349851fb05cf3025d5bb4a7` are untouched.

## Revision / scope

Independent branch `feat/private-author-files`, base main **ed29780**. Exact candidate SHA is the commit containing this file / its PR head. Module source SHA-256: **`d6fd882e820e9928885d91269baef69be19e1accc996a1382512eff634024b1a`**; loaded/served parity is UNVERIFIED until actual Review records it. Only `inc/cas-private-files.php` and its focused tests/fixtures plus these new handoff/switch-prep documents change. No loader/role/price/builder/Sales/Coupons/Enrol/guide file is changed. The already loaded private-file module needs no wiring. Runtime role/course-capability integration uses reviewed #180; the isolated main-base branch deliberately does not invent those caps itself.

Last touching private-file SHA before this fix: **8f4f058280c32f7c59ff2a21628dc982f134fb7c**. Existing route remains **`/?cas_private_file=ID`**, including its existing `size` parameter for generated image sizes.

## Exact authority, including the assignment attack

New author path requires all of:

1. Actual `cas_content` user, `manage_cas_content`, actual current course `post_author=current user`, and `edit_post` permission on that real course.
2. A real current source of type `courses` or a real `lesson → topics → courses` chain, with no trashed node.
3. The attachment appears in that source's CURRENT `_tutor_attachments` list, freshly read from DB, and the source has a protected `_cas_private_author_refs` binding matching **source ID + current course ID + exact canonical attachment IDs**. Duplicate/corrupt list/binding rows fail closed.

Attachment authorship, internal instructor metadata, a request course ID, and the old growing `_cas_private_courses` metadata are **not author-download authority**. The old enrolled/admin branch continues to use its existing rules, deliberately unchanged.

The protected binding is minted after a legitimate assignment, not from arbitrary current references:
- Before WordPress adds/updates/deletes `_tutor_attachments`, a limited author must own the real target course. An already PRIVATE file needs an already trusted CURRENT author reference before the write. Merely guessing another course's ID and assigning it to one's own course is refused with 403 before the list/mapping/binding mutation.
- A new PUBLIC upload uses the normal attachment edit permission before first assignment. It is already public, so this is an upload/edit check, **not attachment-owner-based private download permission**. The native private-file move runs inside a narrow internal metadata window; the post-write callback binds only the request's preflight-approved exact list and current own course.
- Admin writes are trusted operator assignments. Non-admin/non-WP-CLI writers without the actual limited-role/course authority (including ordinary editor/instructor/guest or missing custom cap) cannot change material lists; they cannot use the old growing enrolled map as a second-account laundering route. Imports must run as an explicit administrator or genuine WP-CLI, not an anonymous HTTP writer. Existing untrusted current lists are **not lazily promoted** on download or by a limited-role re-save. A separate exact reviewed manifest is needed for legacy lists.
- Detach immediately removes author authority; deleted/empty lists remove the binding. Same-course topic moves remain possible; role-driven cross-course lesson/topic grafts and course ownership takeovers are refused before core post writes. Direct SQL parent grafts also invalidate the stored course binding rather than creating authority.
- Metadata-ID writes/key renames are guarded too. Core whole-object deletion has a scoped delete-only exception for exactly the object already in WordPress's deletion lifecycle, so cleanup is not blocked halfway through removing its own security metadata. The binding, growing student map, private flag/origin, and private file-path/image-size metadata cannot be rewritten by a limited author. Public upload paths must resolve inside public uploads; image-size filenames cannot traverse out of their folder. This closes laundering a guessed private file through a new editable attachment.

No new HTTP write endpoint, WP role, broad capability, plugin, table or global instructor setting exists.

## Bounded legacy migration — preparation only

See `ROUTE-A-OWNER-SWITCH-PREP-2026-10-23.md` for ordering and exact zero-inventory shortcut.

- Last production inventory supplied by Head reported **0 real attachments**. A fresh authorised read-only inventory is mandatory. If still 0, record **“no migration required”** and do **no bootstrap/stamping/sweep**.
- Only if real legacy references exist: an operator reviews the exact attachment/source/course/chain/list/file identities. `cas_private_reference_snapshot(source)` captures raw list/binding rows, actual graph/owners/status, attachment rows/meta, paths and SHA-256 of original + declared sizes. Store manifest privately, mode 600, not in git/chat.
- `cas_private_apply_reference_manifest(snapshot)` is operator/real WP-CLI only, requires a nonempty exact source and absent binding preimage, compares all current preimages, writes only the expected source/course/list binding, then rechecks graph/list/files and binding. Drift/tampering fails closed. Same manifest + exact existing postimage is a no-op. `(..., true)` removes only that exact stamp after matching the pinned resource preimages; it never overwrites an unrelated stamp.
- A current-but-untrusted reference alone does not qualify. The snapshot is evidence to review, not self-approval. Never auto-stamp every row returned by an inventory or run a blanket sweep as the migration.
- No migration or acceptance was executed. Stamping failure/drift blocks switch BEFORE demotion; no instructor/attachment-owner fallback.

## Prepared fixtures and tests (NOT run)

- `tests/fixtures/cas-private-author-fixture.php`: setup/cleanup with WP loaded, an existing LOCAL admin, `CAS_RUNTIME_TESTS=1`, `CAS_PRIVATE_FIXTURE_HOME` exactly equal to localhost `home_url()`, and `$args[0]=setup|cleanup`. No old-stack default or production URL accepted. Requires the reviewed own-course role cap already present; it does not grant extra caps.
- Fresh users: w1privateowner/other (`cas_content`), w1privatestudent/outsider/instructor (`subscriber`), w1privateeditor (`editor`). Only the instructor negative gets REAL internal Tutor instructor metadata. Owner intentionally has none.
- Draft own/other/legacy courses; real topic/lesson chain; own course notes and lesson reference PNG; foreign private files including one whose **attachment author is the owner but course author is someone else**; detached private file with stale growing map; fresh unassigned public upload; current-but-untrusted legacy list; one enrolled student.
- IDs/passwords and file/manifest/hash evidence are generated only when Review executes setup. **No fixture IDs were created in this Development continuation.** Keep captured JSON mode 600. A ledger records exact synthetic records/users/directory; cleanup refuses identity drift and deletes only those records. Review owns execution/cleanup, no broad reset.
- `tests/test_private_author_runtime.py`: prepared real WP metadata/core-write probes plus real HTTP download bytes/status checks; requires explicit `CAS_REVIEW_CONTAINER` and the captured `CAS_PRIVATE_FIXTURE_JSON`. It never selects/starts an old stack. Covers current-author course/lesson downloads; guest/student/editor/instructor/other-author denial; ordinary enrolled access; forged assignments and metadata-ID writes; detach; takeover/reparent/security-meta tampering; stale mapping; legacy manifest tamper/file drift/no-op/rollback; legitimate first upload.
- `tests/test_private_author_contract.py`: prepared source contracts only. Also rerun existing private-file/paid-material/preview contracts and runtime tests in Review, not as a substitute for the new cases.

Review additionally owns adversarial mixed-reference/duplicate-row tests, core/REST/Tutor assignment routes, raw parent graft/stale bindings, ownership/capability removal, valid same-course reorder, original/size/direct URL checks, real native media-picker/reference rendering and UI error handling. The synthetic runtime probe's 403 exceptions are not a substitute for HTTP/E2E error-path checks.

## Limits / release gates

- Development performed only PHP lint, Python AST syntax parsing and diff whitespace checks. No claim of functional acceptance, source-to-served parity, UI/media-picker usability or migration success is made. Review must hash the loaded PHP/served route against its selected commit and capture the fixture module hash.
- Private image binary/path/size edits by the limited author are refused; upload a replacement public file and assign it legitimately instead. Caption/alt text are not newly locked. Native media-picker filtering may still limit which existing files are visible; verify it in the actual UI rather than widening general media permissions.
- Legacy sources without reviewed provenance deliberately remain denied to authors until the bounded migration is approved/applied. Empty production inventory requires no migration.
- The final migration window must exclude competing course/material/file changes. Filesystem/DB writes by a privileged operator outside WordPress APIs are not claimed serialised; before/after drift checks fail closed on observed changes. Raw SQL assignment cannot mint the protected binding.
- Enrolled access to a detached file through the old growing student map is intentionally preserved; only NEW AUTHOR authority is current-reference-based. Do not silently change student access policy in this fix.
- Actual Review sign-off, full Route A integration, detailed money guide and final owner OK still gate any merge/deploy/switch. Source concurrency is allowed; runtime concurrency is not.
