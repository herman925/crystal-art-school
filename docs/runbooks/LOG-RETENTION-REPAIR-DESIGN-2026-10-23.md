# #152 — bounded log-retention repair and regression design

Date: 2026-10-23. **Design only: no helper implementation, test execution, rotation, compression, deletion, production read/write, service change or deployment is authorised or performed by this document.**

## Decision summary

The smallest complete repair is **not** just replacing a retained-lines list with a generator. Use bounded non-PHP streaming and an explicitly coordinated writer boundary. The closest behavioural repair preserves the original prune → post-prune size rotation → archive-age purge order, but can replace the active path only while every appender is genuinely quiescent and the gate remains held. A native rename/create plus writer-reopen/drain approach is an alternative if the host supports it; pruning detached generations changes where/when the window is enforced and needs an explicit policy decision. Never publish a filtered snapshot over an actively appended path.

This plan is reviewable but **not implementation-ready** until the CEO chooses the writer-concurrency and exact-retention/storage policy below. The complete 91-line source has now been supplied by CEO through Dev-Team-Head and its transmitted bytes hash-verified in memory. There is no zero-loss claim, no permission to weaken retention and no production-read request.

## 1. Exact baseline and evidence limits

The known baseline is production `~/cas-logs/rotate.sh`, reported as **91 lines**, SHA-256:

`740362ceb846d2a68490ff4f9accd191d52dc1f18390171eb6b8fd9857d756e2`.

Tracked project evidence is `docs/runbooks/plugin-security-upgrade.md:1169–1212`, which names the full hash and quotes the audit block, source lines 59–91. The initially missing PHP portion was subsequently supplied **verbatim by CEO through Dev-Team-Head**, from a pinned read-only observation at **2026-10-23T08:19:47Z**. Reconstructing that supplied text solely as an inert in-memory string produced **91 lines / 3096 bytes** and the exact SHA-256 above. The script was not written to an executable file, executed or newly read from production.

The complete source establishes:

1. **PHP active prune, lines 24–47:** 180-day cutoff, retained-lines list, text decoding with `errors='replace'`, fixed adjacent `.tmp`, then `os.replace` over the active path. The leading grammar is bracketed `dd-Mon-yyyy HH:MM:SS`, optionally followed by literal ` UTC`; parsing uses `%d-%b-%Y %H:%M:%S` against a naive UTC cutoff.
2. **PHP size stage, lines 49–53:** only **after pruning**, if the active result is `>=1048576` bytes it is moved to a date-only archive name and the active path is recreated by shell redirection. This is an active-generation threshold, not an aggregate-storage limit or a pre-read safety guard.
3. **PHP archive stage, lines 55–56:** a `php-errors.log.*` name pattern with `find -mtime +180 -delete`. This is archive-file mtime policy, distinct from the active per-entry cutoff; its day rounding and file matching must not be silently changed.
4. **Audit prune, lines 59–91:** 90-day cutoff, anchored `yyyy-mm-dd HH:MM:SS UTC |` prefix without pipe splitting, the same retained-lines accumulation, fixed `.tmp`, explicit chmod 0600 and live-path replacement.
5. **Both parsers:** start with `keeping=True`; an unmatched trace/continuation inherits the preceding decision; a matching but invalid date changes the decision to keep; a valid timestamp is retained at or after the run's cutoff.
6. **Wrapper edge cases:** absence of the PHP log exits the entire script before audit handling; no explicit error-stop checks prevent later stages/final `exit 0` after a failed Python phase; a second size rotation on the same date can overwrite that date's archive. These are source findings, not reproduced production outcomes.

No complete script artifact is committed here. The runbook hash and CEO-observed full-source provenance are preserved; the remaining blockers are concurrency/policy choices, not a missing-source claim.

The historical flood was **36,239,397,831 bytes**. That is a regression threat model, not a fresh live-size measurement. Issue #152's later 2026-10-21 comment reports that the old flood archive was reduced and removed with owner approval, preserving 450 non-flood lines separately. The older incident page's archive-held status must not be mistaken for current verification. This plan neither repeats nor authorises that historical cleanup. [I152]

## 2. Confirmed failure mechanisms and non-solutions

### Unbounded retained input

Both complete-source parsers are O(retained input) in memory. A 36 GB flood of retained records can exhaust RAM before the later PHP size stage helps. A large number of ordinary lines is sufficient; no malicious input is required.

Changing to `for line in file` and streaming the output still does **not** bound a single newline-free physical line: the line reader may allocate the entire line. The replacement design must bound input chunks and timestamp-prefix state as well as output buffering. Stat-only checks are insufficient for a growing input unless a byte budget is also enforced during reading.

### Concurrent append followed by live replacement

A writer can append after the reader has passed that point but before `os.replace`. Publishing the older filtered snapshot over the live path drops those new entries from the active path. A writer that already opened the old inode can also complete later against the displaced inode. Retaining neither that generation nor a writer-drain protocol makes atomic pathname replacement **not** a zero-loss log operation.

The project audit writer uses `FILE_APPEND | LOCK_EX` (`wp-content/mu-plugins/cas-audit-log.php:44` in main and frozen 237). PHP documents acquisition of the file lock **between opening and writing**. A rotation-process lock alone does not coordinate every writer, and an inode lock does not automatically transfer to a newly created file at the same name. A writer may have opened the old inode before waiting for its lock. Native PHP error logging has no documented universal flock/reopen guarantee in the checked configuration manual. [PHP-WRITE] [PHP-ERROR]

**Do not claim the issue is solved by:** a generator alone; a lock held only by the pruner; checking file size only before reading; atomic rename alone; sleeping until size looks stable; or saying no warnings were seen after a parser error.

## 3. Native alternatives inspected before code

| Alternative | What it provides | Why it is not an automatic policy-equivalent repair |
|---|---|---|
| Native logrotate rename/create plus writer reopen/drain | Generation management, size/time triggers, mode/owner/group and post-rotation hooks | Requires actual platform availability and service/writer cooperation. Old descriptors can continue to append to the detached generation; do not prune/delete it before drain is established. |
| logrotate `copytruncate` | Supports a writer that keeps its file open | Upstream explicitly warns that data may be lost in the copy-to-truncate time slice. Not a zero-loss default or implicitly approved trade-off. |
| logrotate `maxage` / file-count retention | Age/count management of rotated files | The source already has a separate PHP archive-mtime policy, but native options must be checked against its `+180` rounding and cadence. They do not replace the active per-entry UTC/continuation policy; `maxage` is checked when rotation is due. |
| logrotate `maxsize` | Size-triggered rotation when the scheduler invokes it | Not a hard instantaneous or aggregate 1 MiB ceiling, and not proof that a flood cannot grow between checks. |
| PHP `error_log = syslog` with host-managed journal/syslog retention | A native logging service can own ingestion, generation and storage limits | A logging-destination/platform change, not the smallest drop-in script repair. Host access and audit-sink design are unverified and not authorised here. Journal file-age/storage controls are not arbitrary embedded PHP-entry timestamp parsing. |

Primary native source inspected: upstream `logrotate/logrotate` manual at recorded commit `1aba3764602a0a013ac3fb1ee57ed547dff03f88`, especially `create` lines 420–430, `maxage` 350–354, `maxsize` 368–376 and `copytruncate` 465–475. The checked upstream manual says: “there is a very small time slice between copying the file and truncating it, so some logging data might be lost.” This does not establish that logrotate is installed or usable on Hostinger. [LOGROTATE]

PHP's official manual describes file/syslog destinations and `error_log_mode` (default 0644 since PHP 8.2), but does not promise rotation, retention or a coordination protocol with this script. The journald manual describes native file/storage retention controls; this project has not been shown to have service-administration access. [PHP-ERROR] [JOURNAL]

## 4. Policy that must be preserved or explicitly decided

| Requirement | Baseline / acceptance rule | Source/decision status |
|---|---|---|
| PHP active retention | 180-day rolling UTC cutoff captured once per pass; retain a valid timestamp at or after it; bracketed date with optional literal UTC | Complete pinned source |
| Audit active retention | 90-day rolling UTC cutoff captured once per pass; retain a matching timestamp at or after it | Complete pinned source / verbatim audit excerpt |
| PHP archive age | Preserve the source's separate mtime-based `+180` policy, matching and rounding unless a change is explicitly approved | Complete pinned source; not identical to active per-entry retention |
| Leading unparseable lines | Initial decision is keep | Both pinned parsers |
| Unmatched continuation/trace line | Inherit the preceding record's keep/drop decision | Both pinned parsers |
| Matched but invalid date | Keep decision becomes true | Both pinned parsers; do not silently classify as old |
| Escaped audit pipe | Never use pipe splitting to identify records | Runbook/source |
| Audit permissions | Temporary and final audit output private, 0600; preserve verified owner/group and restricted directory | Audit source; operator verifies actual metadata before any deployment |
| PHP permissions | Preserve verified owner/group and the approved active-file mode, historically 0644; original Python/shell creation relies on the ambient umask rather than explicit mode preservation | Complete source plus issue mode evidence; preserve approved metadata explicitly |
| PHP 1 MiB stage | After successful prune, rotate when the retained active result is `>=1048576`; add a separate pre-read resource guard without pretending the threshold is an input or total-storage ceiling | Complete source; pre-prune rotation would change stage semantics |
| Exact bytes / invalid UTF-8 / newlines | Proposed binary streaming preserves bytes; both original text readers replace decoding errors and normalise universal newlines | Explicit semantic difference to review, not a hidden equivalence claim |
| Retention when safety is uncertain | Stop without destroying input; report any over-retention/policy exception | CEO must approve handling, not silently redefine the deadline |

### Required CEO concurrency and retention decisions

1. **Writer cooperation:** can the operator establish a controlled brief quiescent/reopen/drain point for every PHP/audit appender, or does the host offer a native rotation mechanism with that guarantee? If neither is available, a pruner-only change cannot promise zero loss. Do not invent a signal, stop a shared process or add broad hook suppression.
2. **Exact stage policy versus non-blocking generations:** the source unambiguously uses 1 MiB as a **post-prune active-generation trigger**, not an aggregate cap. The closest repair preserves that stage order under a genuine writer-quiescence gate. Rotating/detaching before filtering avoids a live rewrite but changes the location/order of retention enforcement and potentially archive mtime behaviour; CEO must explicitly approve that alternative rather than call it a drop-in equivalent. Keeping every eligible record also cannot guarantee a 1 MiB total-storage cap during a flood. No byte dropping or trace truncation is selected here.
3. **Safety hold versus exact age:** keeping an uncertain generation longer prevents data loss but may exceed the retention deadline. That exception and alert/ownership need an explicit decision; “retain forever” is not the current policy.
4. **Byte/newline policy:** approve raw-byte preservation or the exact legacy UTF-8 replacement/universal-newline transformation. The line-header keep/drop semantics must remain fixed either way. Do not silently change retained trace bytes or label binary preservation legacy-equivalent.

Until these are resolved, this is a **design/source blocker**, not approval to deploy a live-log rewrite with a reassuring zero-loss label.

## 5. Smallest proposed repair shape

Eventually version **one small non-PHP streaming retention helper and a narrow wrapper change**, not a logging framework. Do not implement until the baseline and decisions above are fixed.

### Phase A — preflight, before any input read

- Preserve the complete original script's verified hash and stage mapping above. The repaired wrapper must explicitly check helper failures, avoid date-only archive overwrite and decide independent audit handling when PHP log is absent; no unrelated stage is replaced from memory.
- Validate explicit allowed paths and parent ownership. Reject symlinks, unexpected file types, identity changes, ambiguous ownership/permissions and output-name collisions. Preserve any existing ACL/security metadata or stop if that cannot be done.
- Record source device/inode, initial byte size, expected metadata, input-byte/time budget, fixed UTC cutoff and parser version. Hosting-account quota/headroom must be checked by the authorised operator; host-filesystem free space alone is not quota evidence.
- Add an independent resource preflight **before** reading payload bytes, plus a checked runtime byte/time budget. This is not the existing 1 MiB post-prune output check. Oversized/growing input must not start an unbounded whole-log read or be silently rotated/dropped merely to avoid parsing.
- Use an independent maintenance-run lock to prevent two maintenance jobs colliding. This lock is necessary but is **not** represented as writer coordination.

### Phase B — choose and certify one writer boundary

**A. Closest behavioural repair:** under an explicitly approved maintenance gate that really stops/drains **every** appender and remains held, stream-filter the canonical PHP/audit input to private output, verify it, then replace the canonical file while it is not an active append target. Check the PHP result's size only after successful pruning; apply the source's `>=1048576` rotation decision and approved archive-age stage. Create collision-free archives rather than overwriting a same-day file. Reopen/resume writers only after the new active path and metadata are verified. This preserves the intended phase order but requires a production writer-quiescence mechanism; no such mechanism is assumed or authorised here.

**B. Native-generation alternative:** if supported and approved, native rename/create plus a verified writer reopen/drain protocol detaches a generation for later bounded filtering. Keep every generation that can still receive writes. This may change active-file contents, rotation timing and archive mtime retention compared with source A; do not claim exact equivalence without the CEO's explicit policy decision. Preserve the final original generation mtime if publishing a filtered archive so a rewrite does not silently restart its age window.

**C. Neither boundary can be certified:** refuse the destructive prune/replace and report the resulting retention/storage exception. A maintenance-only flock is not a substitute for unavailable writer cooperation, and `copytruncate` is not an authorised fallback.

For either permitted design, account for previously opened and lock-waiting descriptors before pruning a generation. A stable mtime, successful rename or one empty writer check is not by itself a drain certificate. Actual Review must exercise the exact chosen writer profile; CEO/operator separately owns any production service or maintenance action.

### Phase C — bounded filtering only inside the certified writer boundary

- Read binary fixed-size chunks, for example 64 KiB. Buffer only the fixed timestamp prefix (the pinned complete prefixes are at most 26 bytes for PHP and 25 for audit) and keep/drop state; stream the rest of each physical line directly to a private output or discard it according to the current record decision. Do not buffer an entire line, trace, retained list or whole file. If legacy text conversion is chosen, use bounded incremental decoding rather than an unbounded text-line reader.
- Implement the **two pinned timestamp grammars** exactly. PHP recognises a bracketed day/three-letter month/year/time with optional literal UTC; audit recognises its fixed UTC/pipe prefix. Preserve their respective cutoffs and unparseable/continuation decisions; record the month-parsing locale rather than silently adding timezone/date formats. Compile fixed patterns before opening the input; parser errors go to private maintenance diagnostics, not through PHP into the log being processed.
- Freeze each log pass's cutoff once, as its original parser does. Do not change it mid-pass, split audit fields on `|`, drop continuation lines independently, or silently turn future/invalid dates into a different policy.
- Use unique exclusive temporary output in the approved private directory with restrictive creation permissions. Enforce byte/time/output-space budgets while streaming. An enormous line or exhausted budget must yield a clear partial/failed outcome, never a “clean log” claim.
- On parser/I/O/space/budget/identity failure, leave the input generation and active path untouched. Do not publish a partial output. Retain or remove only this run's own partial artifact under the separately approved policy; never broad temporary cleanup.

### Phase D — publish only inside the approved gate and preserve archive policy

Freshly verify the source identity/size and the selected writer boundary. Require complete input coverage, successful parser status, verified output metadata and checked accounting. Use the required filesystem durability steps; an in-memory success flag is insufficient.

In exact-behaviour path A, replacement of the canonical target is allowed **only while it is quiescent and the writer gate remains held**, then the post-prune PHP size decision is made. In generation path B, publish only to the approved detached-generation/archive location, never over the current active append target. If any boundary becomes uncertain, preserve the original and report failure rather than claiming cleanup or reclamation.

The wrapper must fail nonzero on parser/publish errors and must not continue into destructive PHP archive purge after an unsuccessful PHP phase. Audit handling must not disappear merely because PHP input is absent. Retain the explicit 180/90-day semantics and source archive-mtime ageing; prevent same-day archive overwrite, define compatible unique names and exclude maintenance temporary/lock/receipt artifacts from the purge family. Any changed archive selection/mtime behaviour or safety hold requires explicit CEO approval rather than a silent retention exception.

Replacing/deleting a generation is destructive and remains gated by the exact approved retention/evidence policy and operator action. The receipt must distinguish bytes admitted/read/unread, records kept/dropped/unknown, continuation handling, original/output identities, metadata, budgets, writer/drain evidence, complete/partial status and failed operations. A bounded sample establishes facts only about that sample.

## 6. Regression design — actual Review executes, Dev does not

| Case | Required proof |
|---|---|
| Retention boundaries | Fixed clock, each pinned grammar's cutoff minus one / equal / plus one; both use `>=`; PHP optional UTC/no-UTC and month-locale behaviour remain explicit |
| Trace/unparseable semantics | Leading unknown retained; old/new record continuations follow the header; matched invalid dates keep; escaped pipes do not split records |
| Huge retained flood | Synthetic multi-GB or sparse input under an explicit test budget; RSS remains within an agreed cap rather than growing with retained data; no actual production log copied |
| One huge physical line | Chunk/prefix memory remains bounded; budget exhaustion reports partial/failed and never publishes |
| Invalid bytes and CRLF | Preserve the explicitly approved byte policy and line structure; no silent replacement/truncation |
| Read guard before work | Over-budget initial stat selects a safe refusal/strategy before the first payload read; growing input cannot bypass runtime budget; no silent pre-prune rotation policy change |
| Post-prune size threshold | Retained output just below / equal to / above 1048576 exercises the original stage-2 decision; mostly-expired large input does not automatically imply retained output exceeds the threshold |
| Wrapper/archive edge cases | PHP-absent/audit-present handling is explicit; parser failure cannot be masked by final success or followed by destructive PHP purge; two same-day rotations do not overwrite an archive; mtime `+180` boundary and approved archive family remain tested |
| Writer appends at old replacement boundary | Unique sequence markers appended after the reader's last point cannot disappear; naive snapshot/live replace must fail the regression |
| Pre-opened and lock-waiting descriptors | A writer opening the old inode before rotation or waiting on its flock is included in the drain proof; no loss hidden in an abandoned inode |
| Non-cooperating writer | Rotation/pruning refuses an unsupported profile instead of falsely claiming zero loss |
| Concurrent maintenance runs | One run cannot overwrite another's output/receipt or delete its files |
| Path/identity swap | Symlink, inode, ownership or file-type changes stop the operation without touching unrelated paths |
| Output failure / disk quota | Short write, ENOSPC, permission error, interrupted process and failed publish preserve the source and produce non-success evidence |
| Permissions and metadata | Audit output remains 0600; PHP active mode/owner/group match approved baseline; no broad chmod or public diagnostics |
| Durability and replay | Interrupted/repeated work reconciles the exact owned artifacts and original identity, not a guessed `.tmp` or a stale receipt |
| Scope | No PHP/WP-CLI reader, no production-path access, no unrelated log deletion, compression, service change or release acceptance inferred |

For concurrency, compare independently acknowledged **in-window** synthetic sequence markers against the explicitly listed published retained generations plus new active generation, requiring each marker exactly once. Raw evidence copies are not counted as a second published copy. Certify distinct old/new inode identities and actual descriptor targets before the phase; do not accidentally query one target twice. Checking only active-file size is insufficient. A lossless result applies only to the certified writer/reopen profile, not arbitrary host behaviour.

No tests have been prepared as executable helpers or run under this assignment; this is the first design/acceptance plan. Fixture implementation follows source/policy approval and belongs in a separate candidate; actual Review runs it. CEO/operator owns any later production verification and rollout.

## 7. Review gates / ownership

- **CEO/operator:** retain the supplied full-source provenance/hash; before any later deployment, attest that the intended baseline still matches. Confirm host tooling/scheduler/writer cooperation, quota and policy choices. No fresh production read by Dev is authorised.
- **Source implementer, after approval:** smallest helper/wrapper diff, exact source provenance, no unrelated cleanup or framework.
- **Actual Review:** independent source review and bounded synthetic retention/memory/concurrency/failure regressions on the exact candidate; no old failed-parser health result is accepted.
- **CEO/operator:** separately authorise backup, deployment, controlled rotation/service coordination and production evidence. No automatic rollback/reclamation/deletion promise.

## 8. 2026-10-24 implementation: safety-first policy selected

The owner selected safety-first generations, raw-byte preservation and explicit temporary over-retention exceptions (issue #152, owner-policy comment dated 2026-10-24). This authorises the source candidate below, **not execution or deployment**. Earlier sections record the original design and unanswered operational questions; they are not a claim that this implementation was already approved or run.

### Actual source candidate

- `tools/log-retention.py`: bounded binary parser and detached-generation capture, standard library only.
- `tools/rotate-logs.sh`: narrow explicit-directory/verified-run-space wrapper, using the known Hostinger Python path with isolated mode. No default production directory, account access, service manipulation or scheduler installer.
- `tests/test_log_retention.py`: prepared synthetic parser/generation/failure regressions; Dev performs syntax checks only. Actual Review executes them in its authorised lane, with `REVIEW_LOCAL=1` for unique temporary-directory filesystem cases. No existing stack, user, role, log or fixture cleanup is selected.

**This candidate does NOT certify a writer-drain profile or retire raw sources.** No such profile was supplied. There is deliberately no force/closed checkbox that can turn an assertion, stable size, maintenance flock or an empty descriptor observation into proof. A nonempty capture always exits **3 / RETENTION_EXCEPTION**, even if a filtered candidate was produced successfully. This is a bounded safe-generation implementation with an explicit cleanup hold, not a completed automatic retention/reclamation system or deployment-ready replacement for the scheduled job. Keep #152 open.

### Exact stage and byte differences

1. The helper validates the explicit private directory (same user, 0700, no extended metadata), takes a maintenance-only lock and inventories at most 256 entries. Existing managed-generation names and the old `php-errors.log.*` family become visible holds. It does not read, rewrite or purge those prior archives. The incident `.log.gz` is outside both families and is never selected.
2. Before payload reads or capture, a file must be regular, single-linked, owned by the current user, carry the required mode (PHP 0644 / audit 0600), and have no unhandled extended metadata. Unexpected ACL/xattr/identity/mode/owner/link state is a scope exception, not grounds to chmod somebody else's file. An initial byte/space admission check occurs before detaching it.
3. The helper renames a nonempty canonical log into a NEW uniquely named private generation directory, preserving its original inode and all already-open descriptors. It then creates the new active log exclusively; if an append/open-create writer created it during the gap, it verifies metadata and identity rather than truncating/replacing it. New active files receive the previous owner/group and explicit mode. Both directories and newly created files are fsynced. Missing PHP input does not skip audit.
4. This handoff supports **append/open-create writers that do not mutate the namespace or truncate logs**; it is not a universal host guarantee. The rename/create interval may expose a missing canonical path to a writer that does not open-create. A namespace-mutating writer, different account, unsupported metadata or failure after rename requires operator reconciliation. No downtime/service control/reopen protocol is implemented. Production applicability must be independently established; the helper does not assert that PHP's deployed writer satisfies this profile.
5. A fixed 64 KiB reader admits only the initial byte count. A prefix of at most 104 raw bytes covers the original maximum 26-character UTF-8 header, including Unicode decimal digits recognised by the original text regex. Only that prefix is decoded for the original regex/date parser. **Output bytes are never decoded/re-encoded**. LF, CRLF, bare CR, invalid UTF-8 and retained traces remain byte-exact. Leading unknown text is kept; nonmatching continuations inherit the prior decision; matching invalid dates reset it to keep. Locale is explicitly C for the original English month grammar. Each log gets its original 180/90-day cutoff at a single run-start UTC clock, rather than recapturing the clock per file.
6. Detached raw sources may still grow from pre-opened or lock-waiting descriptors. The helper never follows that growth into an unbounded read, deletes/replaces the raw source, or publishes a partial snapshot as its replacement. It records source changes and keeps the raw inode regardless. The `.candidate` is **not** a published retained generation: do not count it twice alongside raw/active, serve it, or infer completeness/closure from its existence.
7. The original prune → post-prune 1 MiB rotation → broad mtime purge sequence is intentionally **not reproduced**. Capture precedes filtering; `>=1048576` retained bytes is recorded as the PHP retained-generation trigger, not an input or aggregate cap. Inputs over the independent byte budget are refused before capture. Prior PHP archives would originally age with `find -mtime +180`; this candidate holds them because closure/safe retirement is unproven. No broadened purge, restarted archive mtime, retention-equivalence or zero-loss-outside-profile claim is made.
8. The only implemented purge is the exact current invocation's still-open private **partial candidate**, conditionally identified against its open descriptor on failure. Raw originals, previous generations, legacy archives, receipts and incident archives are never deleted. Automatic retirement of a raw generation is not silently enabled after a quiet interval; it remains a separately certified operational boundary and source change.

### Budgets, failures and recovery

Default aggregate admitted input is 64 MiB per run; explicit byte/space budgets are capped at 256 MiB and time at 120 seconds (default 30). The wrapper requires the operator's verified **account-level allocation for this run**, with 1 MiB reserved for metadata; filesystem available bytes are only a second check, never account-quota proof. The bounds are per invocation, not aggregate storage/retention limits. A 36 GB file is refused without payload read or capture, rather than exhausting RAM or pretending that the 1 MiB trigger has capped it. Fixed chunks and prefix/state bound memory even on one huge newline-free line. Deadline checks plus an alarm cover the work; kernel-stalled filesystem operations still require operator handling, not a universal wall-clock completion promise.

Failures retain originals and produce nonzero status. Private exclusive, fsynced receipts are written before each handoff and after each log result; an interrupted run may leave only a pre-handoff receipt or raw source, never a deletion success claim. PHP failure is recorded and audit is still attempted within the remaining shared budgets. Receipt-write failure is itself fatal; stderr is not a retained-health proof. A later invocation reports existing generations as holds and never overwrites them. Run/receipt names are collision-resistant exclusive creations, not date-only overwrites.

Recovery is **inspect the exact private generation and canonical active identity under separately authorised operator action**. Do not rerun to reclaim space, automatically concatenate candidates, copy a candidate over the active path, remove a held raw file or assume an interrupted invocation changed nothing. The raw source may continue receiving records. Repeated invocations can accumulate held generations and eventually hit the inventory bound; that is reported as a retention/storage exception, not indefinite-retention success. No production invocation, new quota/schedule check or cleanup has occurred.

### Prepared versus outstanding verification

Prepared cases cover timestamp boundaries, optional UTC, Unicode dates, leading/invalid text, CR/LF/CRLF and invalid bytes, huge lines with bounded reader/memory, short read/write, retained-size trigger boundaries, prior/open/lock-waiting descriptors and distinct raw/new inode markers exactly once, PHP absent with audit present, over-budget refusal with independent audit processing, same-day unique generations, maintenance collision, symlink refusal, failure-only partial cleanup and incident/archive exclusions. These are **not executed results**.

Actual Review must additionally inject the selected writer races during handoff/filter, active creation/metadata/fsync/receipt failures, growth and namespace/identity swaps, unsupported process/permission visibility, quota/space/time exhaustion and interruption/restart. A zero-loss result is scoped to the exact independently certified append/open-create profile; the current unproven production profile remains a hold. Separate operator approval is required for any production read/write/deploy/rotation/deletion. No compression or old incident cleanup is reopened.

## Sources

- [#152 issue and dated operator records][I152], especially the 2026-10-19 containment and 2026-10-21 archive-disposition comments; historical evidence is not a fresh runtime measurement.
- `docs/runbooks/plugin-security-upgrade.md:1169–1212` at approved main `ed29780654481b077e614abfc9dd5926b9bdf04b`: exact full-script hash and verbatim audit excerpt. CEO's complete 2026-10-23T08:19:47Z source observation was supplied through Dev-Team-Head; inert in-memory reconstruction independently matched 91 lines, 3096 bytes and the declared hash.
- `wp-content/mu-plugins/cas-audit-log.php:30–45` at main/frozen 237: strict append result and `FILE_APPEND | LOCK_EX` writer.
- [Upstream logrotate manual][LOGROTATE]: native alternatives and documented copy/truncate loss window, not evidence of installed host capabilities.
- [PHP file_put_contents manual][PHP-WRITE]: append/flock semantics.
- [PHP error logging configuration][PHP-ERROR]: file/syslog destinations and file mode; no rotation/cooperation guarantee.
- [journald configuration manual][JOURNAL]: native storage/retention controls, not this project's embedded per-entry policy.

[I152]: https://github.com/herman925/crystal-art-school/issues/152
[LOGROTATE]: https://github.com/logrotate/logrotate/blob/1aba3764602a0a013ac3fb1ee57ed547dff03f88/logrotate.8.in#L465
[PHP-WRITE]: https://www.php.net/manual/en/function.file-put-contents.php
[PHP-ERROR]: https://www.php.net/manual/en/errorfunc.configuration.php
[JOURNAL]: https://www.freedesktop.org/software/systemd/man/latest/journald.conf.html
