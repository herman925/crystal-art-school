# Private-author files — separate source candidate / Review handoff

Original preparation: 2026-10-23. Qualified preservation: 2026-10-24. **Source/document preservation only; no fixture, test, runtime, browser, production, migration, provenance stamp or owner-switch action was executed in this preservation task.** Actual independent Review owns execution and acceptance. Historical price #180 `917a8d7196435246d23278b157c0deb50389730a` and builder #181 `c9aaa8609ef78eb29349851fb05cf3025d5bb4a7` are old component inputs, not the current combined release.

## Revision / scope

Original #182 source: **`813a0b747fd45f196de03a7dc3e595769f51ca3f`**, branch `feat/private-author-files`, base `ed29780654481b077e614abfc9dd5926b9bdf04b`. Its full original tree remains preserved. The module is already copied byte-for-byte into frozen #192 **`23723f4c5aef9abf5fe5839b89bed12d19c60ed7`** and backend-only #196 **`63ce60b63c35deb4ac4b64b7c9350f88de1ea9f6`**, blob **`692c4cb116ff413ff2bba42803d4d5cb4299a6d7`**, SHA-256 **`d6fd882e820e9928885d91269baef69be19e1accc996a1382512eff634024b1a`**. Exact file/tree mapping does not grant integrated acceptance, merge, deployment or account-switch approval; both frozen candidates remain unchanged.

This separate preservation candidate starts from approved main **`d3c2eb7d962208ae52aab7cd92b690547b7f96bd`** and adds only this qualified document, the qualified switch-preparation document and the EXACT original `tests/test_private_author_contract.py` blob. **No PHP, role, loader, guide, mutable fixture or runtime runner is imported.** Its own commit/PR identifies the preservation pin, not a new private-file implementation.

**Open contract dependency:** approved main at that base still has the older private module (blob `fbb594ec29006e99ab6f4018516ba5b3c0e4e945`), without `cas_private_author_can_download`. The preserved contract targets #182 / frozen `23723f4` / #196's module and is not expected to pass against the older main module. Do not weaken assertions, silently import PHP, run broad test discovery or treat preservation as test-green. Actual Review decides execution against an independently approved matching module/tree; #196 remains a separate review/merge gate.

The source-approved client guide from #194 is already on the preservation base (complete docs-site tree `565d50934fcfd7441f788e5d89b993b76233e8a5`); money instructions remain draft pending screen checks/handover and separate live-publication approval. The guide and #193 research are untouched.

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

- The historical operator inventory reported **0 real attachments**, not a current production fact. A fresh authorised read-only inventory is mandatory. If still 0, record **“no migration required”** and do **no bootstrap/stamping/sweep**.
- Only if real legacy references exist: an operator reviews the exact attachment/source/course/chain/list/file identities. `cas_private_reference_snapshot(source)` captures raw list/binding rows, actual graph/owners/status, attachment rows/meta, paths and SHA-256 of original + declared sizes. Store manifest privately, mode 600, not in git/chat.
- `cas_private_apply_reference_manifest(snapshot)` is operator/real WP-CLI only, requires a nonempty exact source and absent binding preimage, compares all current preimages, writes only the expected source/course/list binding, then rechecks graph/list/files and binding. Drift/tampering fails closed. Same manifest + exact existing postimage is a no-op. `(..., true)` removes only that exact stamp after matching the pinned resource preimages; it never overwrites an unrelated stamp.
- A current-but-untrusted reference alone does not qualify. The snapshot is evidence to review, not self-approval. Never auto-stamp every row returned by an inventory or run a blanket sweep as the migration.
- No migration or acceptance was executed. Stamping failure/drift blocks switch BEFORE demotion; no instructor/attachment-owner fallback.

## Six preserved inputs and their disposition

All source blobs below are pinned at original #182 **`813a0b747fd45f196de03a7dc3e595769f51ca3f`**, not newly accepted runtime evidence. The first five were absent from frozen237/#196; the existing legacy test has blob `cbf931f9a79747284e823fadd9f1b55774cd57d7` there and on this main base. Original refs/worktrees/evidence remain intact; no branch/PR closure is implied.

| Source path | Original blob | Current disposition |
|---|---|---|
| `docs/PRIVATE-AUTHOR-REVIEW-HANDOFF-2026-10-23.md` | `146d369afbc51f4fe0e2cd496070db78777e1ddb` | Preserve here with current/historical pins, six-path disposition, exclusions and open coverage; not a runtime handoff approval |
| `docs/ROUTE-A-OWNER-SWITCH-PREP-2026-10-23.md` | `fcedb7004bf687cc5ef5f7843914928a47c1d5e2` | Preserve qualified PREPARATION only; fresh ID/preimage/ownership manifests, zero-attachment shortcut, independent admin fallback, rollback and separate final human GO remain mandatory |
| `tests/fixtures/cas-private-author-fixture.php` | `c59cdc4ec5192083835ee11b489d1974cb744cc7` | **UNSAFE AS-IS; excluded.** Fixed ledger/usernames/marker lack a unique run identity; unchecked ledger creation; cleanup verifies only limited identity before deleting users and potentially non-ledger owned content. Localhost/admin guards do not cure these defects |
| `tests/test_private_author_contract.py` | `37dd9ba15eedb0185ddb31f2d9a65153d9aa73d4` | Preserve EXACT byte-for-byte as six pure static contracts, not executed. Current #182 / frozen `23723f4` / #196 APIs/literal boundaries match the intended target; older approved-main module does not satisfy this dependency |
| `tests/test_private_author_runtime.py` | `a05af30ca73d04c0f32b52aaf9aef041199df3a4` | **UNSAFE AS-IS; excluded.** External fixture JSON lacks protected-input/run/ownership attestation; PHP container and HTTP origin are not independently bound; fixed-name fixture dependency, unconditional finally-restores and unclosed login sessions. Existing Review access packet replaces only some read cases, not all mutations |
| `tests/test_private_course_files.py` change | `b55027827ae12aea0887ad537c9c0371113faa70` | **UNSAFE AS-IS; delta excluded.** Optional target/admin assignment does not remove old-stack defaults, plugin activation, existing-user role/password rewrites, root mkdir/chown or broad title/user/private-directory deletion. Existing `cbf931f…` is unsafe too and remains unchanged, not approved for execution |

**Do not execute/import the three excluded mutable inputs or run broad legacy test discovery.** Their fixture descriptions in the original #182 document were intended coverage, not a certification of safe ownership/cleanup. No old fixture IDs/passwords were created or read by this preservation task. Any future mutable fixture must independently bind the explicit Review-LOCAL stack, unique run marker and exact owned ID/preimage ledger; no existing-user reuse, blanket deletion, unguarded restore or implicit stack selection. Actual Review owns approval/execution/cleanup; this document authorises none of it.

## Independent evidence and OPEN current coverage

The following are retained independent Review reports, not new Development runs, all below `/home/herman925/cas-evidence/review-sorting/20261022t170820z-98d8e35b6b61dae8/`:

- `COMPOSED-RETEST-VERDICT.md`: scoped own-author/private-file/provenance and other cases on an OLDER composed #186 tree plus exact #182 module. It is not `23723f4`/#196 whole-tree acceptance and does not certify execution of the excluded old fixture/runtime files.
- `ACCESS-237-HERMES-v18/MANIFEST.md` and `EXEC-RESULT-v18-r1.md`: actual limited `23723f4` HTTP/native-preview/private-PDF evidence, including owner/admin byte equality and foreign/guest private-file refusals. This partially replaces read coverage only. Do not transplant its local IDs, credentials, fixed run paths or results into a fresh fixture or call it #196 runtime execution.
- `PREVIEW-237-REVIEWED-v7/EXEC-RESULT-v7-r1.md`: the native server-generated draft-preview route worked while a hand-built root-query route did not. This is a bounded route diagnostic, not a reason to rerun, alter product URLs or reuse its synthetic IDs.
- `PREVIEW-RETAINED-31-SOURCE-AUDIT.md`, section **Independent retained ACCESS-v18 execution-evidence signoff — source/raw only, 2026-10-24**: independent retained-evidence signoff for the limited v18 cases. It relies on the retained execution summary for external profile/network/capability assertions, not a second runtime run. The report is append-only and its whole-file hash changes; no obsolete whole-file hash is asserted here. Lesson-negative claims remain sentinel absence, not UI denial or general no-leak; failed v12/v17 history remains preserved.

**OPEN:** a fresh independently reviewed mutable packet for any still-required forged assignment/by-mid/security-meta/takeover/detach/current-graph/legacy manifest drift/no-op/rollback cases on the selected exact tree; duplicate/mixed references, role/cap removal and same-course moves; original/generated-size/direct-URL/deletion isolation where not covered by current evidence; native media-picker/reference rendering and UI errors, including #148 native-editor enrolled replace/remove; final real-role integration and all #150 UI/money gates, migration/zero-inventory evidence and switch/rollback verification. Existing reports must be mapped case-by-case, not counted as blanket coverage. The preserved static contract cannot clear these gates. **#182 remains OPEN until explicit disposition.**

## Limits / release gates

- The original Development handoff recorded PHP lint, Python AST syntax and whitespace checks only. This preservation task performs only source/blob/scope/whitespace and Python AST checks, with no contract import/execution. No new functional acceptance, source-to-served parity, UI/media-picker usability or migration result is claimed. Review must certify its own selected source, target and evidence.
- Private image binary/path/size edits by the limited author are refused; upload a replacement public file and assign it legitimately instead. Caption/alt text are not newly locked. Native media-picker filtering may still limit which existing files are visible; verify it in the actual UI rather than widening general media permissions.
- Legacy sources without reviewed provenance deliberately remain denied to authors until the bounded migration is approved/applied. Empty production inventory requires no migration.
- The final migration window must exclude competing course/material/file changes. Filesystem/DB writes by a privileged operator outside WordPress APIs are not claimed serialised; before/after drift checks fail closed on observed changes. Raw SQL assignment cannot mint the protected binding.
- Enrolled access to a detached file through the old growing student map is intentionally preserved; only NEW AUTHOR authority is current-reference-based. Do not silently change student access policy in this fix.
- Merge of this three-path preservation requires **BOTH** explicit independent Review approval of this exact source/scope **AND** the matching accepted private-author backend **ALREADY ON MAIN** (module blob `692c4cb116ff413ff2bba42803d4d5cb4299a6d7`, SHA-256 `d6fd882e820e9928885d91269baef69be19e1accc996a1382512eff634024b1a`). CEO coordinates only after both gates hold.
  The matching backend reached main through #199 (`7e24cb47060fd53ff830e3d9389573b51a2b9efe`); that satisfies only the backend prerequisite, not independent approval of this preservation PR or closure of #182.
  Actual Review sign-off on full Route A, a current operator manifest, the detailed money guide's tested/handover state, separate publication approval and final human switch OK still gate any runtime deployment/account switch. No concurrency or execution permission is created here.
