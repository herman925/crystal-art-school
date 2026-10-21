# Operational incident — PHP error-log growth, 2026-10-19

**Status:** Containment accepted by Operator at **14:11:25Z**. Active log remains **0 bytes for >2 minutes**; raw original inode and **36,239,397,831 bytes** remain unchanged; post-rotation health **3/3**. Archive/space recovery is **not complete**; account quota is unknown. Only reviewed small code/content work may resume with fresh backups, lint/hash checks, and bounded non-PHP validation. Large uploads, payments, signup, compression execution, and raw deletion remain gated. Reports below come from Operator/Worker 1; tracker maintenance made no production changes.

**Tracking:** [#152](https://github.com/herman925/crystal-art-school/issues/152). This is a separate operational event from the September security incident. No evidence here establishes compromise or attributes the cause to WordPress.

## Retraction

All earlier “zero recent warnings” or equivalent log-health conclusions from the failed WP-CLI parser are **invalid and retracted**. They do not establish a clean log or server health. This includes the earlier 15-minute statement in the [PR #147 release note](https://github.com/herman925/crystal-art-school/pull/147#issuecomment-6267915268) and #152's initial body. Independently reported file hashes, lint, and page behavior are separate evidence; they do not prove log health.

## Reported timeline

| Time (UTC) | Observation or action | Evidence limit |
|---|---|---|
| 2026-10-19 12:51:02 | Earlier PHP log measurement: 376,722,193 bytes; recorded mtime 12:51:02Z. Daily 05:00 rotation job exists. | Rotation coverage and free disk remain unknown. |
| 2026-10-19 13:49:22 | Worker 1 reports PR #146 deployed: raw hash/lint, public GET 4/4, user 9 six-panel dashboard, legacy route 302. | Reported deployment behavior only; log health is not verified. |
| Later initial report; exact measurement time pending | Worker 1 initially reports PHP log at **35,839,174,815 bytes**. | Superseded by the stable larger measurements below. |
| 2026-10-19 13:51:19 | Tail's final warning timestamp in a bounded **64 KiB** sample. Sample contains **254 `preg_match` Compilation failed warnings**, all from WP-CLI `Eval_Command` source. | Operator/Worker 1 confirms the faulty owned parser caused amplification. |
| 2026-10-19 13:51:26, 13:51:49, 13:52:42 | Log remains **36,239,397,831 bytes** at all three measurements. No matching owned payload process remains; no kill performed. | Short observation window only; longer stability and account quota remain pending. |
| After growth report | Operator pauses further production deployments/content writes. | Pause remains until longer stability, account-capacity, and site-health checks are accepted. |

## Attribution supported by bounded evidence

Worker 1 confirms their own WP-CLI `eval` parser caused warning amplification while reading the growing PHP log. A bounded 64 KiB sample contains 254 `preg_match` Compilation failed warnings, all from WP-CLI `Eval_Command` source. Tail stops at 13:51:19Z. No matching owned payload remains; no kill was performed. This supports an operational diagnostic failure, not a malicious-attack or WordPress-cause claim.

## Containment and preservation

1. No matching owned payload remains. No kill was performed. If it recurs, identify the exact owned process before any authorized stop.
2. Worker 1 completed separately approved stages 1–2 with raw preservation; no broad kill, compression execution, raw deletion, purge, or full-log download is approved.
3. Preserve raw and relevant process/command evidence. Exclude secrets and private customer data from reports.
4. Operator accepted containment and limited resumption at 14:11:25Z. Large uploads/payment/signup/compression execution/raw deletion stay gated; host-filesystem free space is not hosting-account quota.

## Approved lossless handling — stages 1–2 only

Operator approved stages 1–2; Worker 1 reports completion at **14:09:19.375901Z**. Raw archive `/home/u202887698/cas-logs/php-errors-incident-20261019T140919Z-58b6cc012300.raw` retains the original device/inode and **36,239,397,831 bytes**. Active path is **0 bytes**, new inode, same owner, **0644**; zero writers/no uncertainty reported. Private receipt is retained and not reproduced. Rotation did not read/hash/compress/delete raw content. [Completion record](https://github.com/herman925/crystal-art-school/issues/152#issuecomment-6269311206). No data was discarded, per Worker 1. Metadata preservation is not a content-hash check; no disk-space reclamation claimed.

Operator accepted post-rotation evidence at **14:11:25Z**: active 0 bytes stable >2 minutes, raw unchanged, health 3/3. Only reviewed small code/content work may resume with fresh backups, lint/hash checks, and bounded non-PHP validation. No broad production authorization.

### Compression design remains gated

**Review hold:** Operator reports 10 synthetic helper tests passed independently. Review still found that the live `STARTED` PID/UID/startticks receipt persists only after `finally`, while stderr `read(4096)` buffers the start line. Worker 1 must fix this and add an active-stream receipt-persistence regression. [Review blocker](https://github.com/herman925/crystal-art-school/issues/152#issuecomment-6269713622). No production compression ran; raw retention, active log0, accepted containment, and limited small-write approval are unchanged.

Local streamed-compression design with a **256 MiB cap** is approved as design only. Helper synthetic checks, Worker 4 independent review, and local privacy/free-space checks must pass before execution. Retain raw; no compression execution, raw deletion, or purge approved. Account quota remains unknown. Archive/space recovery is not complete.

## Pre-rotation bounded check (historical)

Operator reports the same **36,239,397,831-byte** log remained stable for **at least 3 minutes 42 seconds**, with mtime **13:51:19Z**. No matching parser runs; no kill was performed. Three public HEAD health endpoints returned **200/DYNAMIC**. The bounded tail contains **254/254** regex-compilation warnings attributed to the owned WP-CLI parser. Host-filesystem free space is reported as **1.52 TB**, which is **not** hosting-account quota evidence; account quota remains unknown. Worker 1 prepares a lossless handling plan for Operator review. Production writes remain held; no deletion, truncation, or compression occurred under this record.

## Required next evidence

| Check | Status |
|---|---|
| Exact owned payload identity and stop result | No matching owned payload remains; no kill performed. |
| Repeated bounded file-size measurements after containment | At 14:11:25Z acceptance: active 0 bytes stable >2 minutes; raw original inode/36,239,397,831 bytes unchanged. |
| Hosting-account quota and immediate capacity impact | Unknown; host-filesystem free space is insufficient evidence. |
| Bounded public site behavior after containment | Post-rotation health 3/3 accepted by Operator at 14:11:25Z; not a complete site audit. |
| Safe log sampling without the failed parser | 64 KiB sample, 254 matching warnings from `Eval_Command`; reported by Worker 1. |
| Root cause and rotation-script coverage | Parser amplification attributed; rotation coverage still unverified. |

## Future validation checklist

1. Never parse the live growing PHP error log with PHP/WP-CLI; parser errors can append to the same input.
2. Take an immutable snapshot or read an explicit bounded byte range with a non-PHP reader.
3. Test the pattern on a fixed small sample before any production validation.
4. Record input byte limit, parser errors, sample timestamps, and skipped/unread bytes. Do not infer “zero warnings” from parser failure.
5. Measure stable log size and hosting-account quota separately from page responses and source hashes.

No log cleanup, production rollback, or new deployment is authorized by this document. No website-compromise, WordPress-cause, or clean-log claim is made.
