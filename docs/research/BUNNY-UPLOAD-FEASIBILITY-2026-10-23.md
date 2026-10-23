# #149 — WordPress-to-Bunny upload feasibility

Date: 2026-10-23. **Source-only recommendation; no uploader implementation, security acceptance, provider-test approval or launch promise.**

## Recommendation

**Keep operator upload in Bunny plus the existing manual URL/ID/embed workflow at launch.** WordPress-led upload is technically feasible using a custom authenticated control plane and Bunny's resumable TUS transport, but the available evidence does not establish a safe, turnkey Tutor uploader. Treat a self-service uploader as a separately specified and tested feature, not a few upload-button settings. The missing production key does not prevent offline design/mock work; neither does offline work authorise a provider test. [I149] [B-TUS] [B-WP]

The official WordPress plugin really does offer upload/select inside a Stream Gutenberg block; it is not merely a static embed plugin. However, its documentation does not establish Tutor lesson metadata wiring, the limited content role's lesson ownership checks, this project's signing-host compatibility, or atomic cancel/finalize. Installing it is therefore not the recommendation under this ticket. [B-WP] [P-LESSON] [P-SIGN]

This recommendation completes a bounded feasibility assessment, **not approval to build, upload, delete, create keys/libraries, purchase anything or change live WordPress**. Real-provider tests remain separately gated by **OQ-14**. [I149]

## Evidence and scope

- Read the exact #149 goal, acceptance criteria and two 2026-10-19 operator-report sections. They explicitly say Worker 1's supporting contract/source references were not attached and that neither feasibility nor security had passed. Their proposed hub UI and browser-TUS flow are proposals, not shipped/approved contracts. [I149]
- Searched repository docs, relevant git history and retained Markdown/text/JSON notes for an October 19 upload contract, TUS, `storageSize` and 1 GiB. **No standalone October 19 uploader-contract artifact was located.** Dev-Team-Head also knew no exact saved path. Do not reconstruct an approved contract from the issue's operator report. This is an evidence gap, not a blocker to recommending the manual launch workflow.
- Project comparisons use approved main `ed29780654481b077e614abfc9dd5926b9bdf04b` and frozen combined candidate `23723f4c5aef9abf5fe5839b89bed12d19c60ed7`. No frozen source was changed.
- Cached Tutor Free source inspected at `.claude/scratch/Dev-Team-Worker-1-routeA/src/free/tutor/` declares **4.1.3** in `tutor.php:7,29`. The issue's reported 4.1.0 stack is older evidence. A cached source declaration is **not** verification of the presently deployed plugin inventory; freeze/reconfirm the target package before any future implementation.
- Only public documentation/schema GETs and local source reads were performed. No credentials were opened, no account/library endpoints were exercised, and no uploads, deletes, key/library creation, tests, installation, provider writes or production changes occurred. No primary-source fetch permission was denied. Official documentation redirects were followed to Bunny's own canonical site; no access-denial workaround was used.

## 1. What Bunny actually provides

### Create and HTTP upload

The public API documents these library-scoped operations, authenticated with an `AccessKey` header. The HTTP upload guide specifies the Stream **library API key**, not the account-wide API key. [B-API] [B-HTTP]

| Operation | Method and path | Important boundary |
|---|---|---|
| Create video object | `POST /library/{libraryId}/videos` | Creates a GUID before any file is ready |
| Send initial binary | `PUT /library/{libraryId}/videos/{videoId}` | Raw `application/octet-stream` body; not JSON/base64 |
| Inspect video | `GET /library/{libraryId}/videos/{videoId}` | Authoritative provider resource/state read, not a local success flag |
| Update metadata | `POST /library/{libraryId}/videos/{videoId}` | Metadata update; not evidence of binary replacement |
| Delete video | `DELETE /library/{libraryId}/videos/{videoId}` | Destructive provider operation; never implied by this research |

Direct HTTP PUT does not resume: a broken transfer starts again. Bunny recommends TUS for files over 2 GB; that recommendation is not a documented universal maximum. The public schema documents upload HTTP 400 as **“The requested video was already uploaded”**. Do not promise that PUT on an already-uploaded GUID provides safe in-place replacement or rollback. [B-HTTP] [B-API]

Delete documents 200 success, 404 not found, 401/403 authentication/authorisation failure and 500 server error. This is not an application-wide transaction, proof of immediate cache erasure, or a documented guarantee about repeated deletes and active TUS uploads. An ambiguous delete needs reconciliation; 401/403 must not be treated as successful cleanup. [B-API]

### Browser TUS transport

Bunny documents `https://video.bunnycdn.com/tusupload`, with a video object created first and its GUID bound to these headers: [B-TUS]

- `AuthorizationSignature` = SHA-256 of `library_id + api_key + expiration_time + video_id`, in that order, without separators.
- `AuthorizationExpire`: Unix timestamp in seconds.
- `LibraryId` and `VideoId`.
- Documented upload metadata includes required `filetype` and `title`; MIME metadata is not a proof of file contents.

The server computes the signature; the browser receives the scoped upload authorisation, **not the library API key**. This signature is nevertheless a bearer capability for the selected video, not a harmless public identifier. Application authentication, ownership checks and protected status/re-signing routes remain necessary; CORS is not authorisation. [B-TUS]

TUS uses HEAD/offset discovery and PATCH continuation. The base protocol rejects an incorrect offset with 409 without modifying the upload. Bunny's example retries network/5xx and 409/423, while stopping most other 4xx responses. That example is a strategy to evaluate, not a promise that every error is retryable or that retrying creates no orphan resource. Preserve the same job/video/upload-URL association when resuming; do not create a fresh video for every retry. [TUS] [B-TUS]

Bunny's documented expiry details matter:

- Expiry is checked at the start of every POST, HEAD and PATCH, **not again during an in-flight PATCH**.
- Re-signing the same GUID can permit a resume through the existing upload URL, but does **not** extend that upload resource's original expiry.
- The documented incomplete-resource window ends at its original authorisation expiry or roughly 48 hours of inactivity, whichever comes first; the documented expired-resource response is 404.

Thus a local Cancel button, token expiry, or refusal to issue another token is **not proof of instant interruption/revocation**. Bunny's TUS guide does not establish an individually revocable token or a supported termination endpoint. The TUS specification's `termination` extension is optional; do not assume Bunny advertises or implements it without evidence. [B-TUS] [TUS]

### Size, type and completion are different checks

A **1 GiB browser preflight is advisory only**. The documented TUS signature inputs do not include an application-specific maximum byte count or MIME constraint. TUS `Upload-Length`/metadata are client-supplied; the standard's optional `Tus-Max-Size` is a server limit, not evidence of a per-job 1 GiB policy on Bunny. No checked Bunny source proves that this project can enforce that hard ceiling through its direct-browser signature. [B-TUS] [TUS]

`GET Video.storageSize` is **total storage occupied by the video**, not original-file upload size or a file-type assertion. Encoded resolutions, codecs, MP4 fallback, originals and ancillary files affect storage. The checked schema has `hasOriginal` and `originalHash`, but no dedicated original input-byte-count/type field establishing the proposed policy. A hash is not a file-type proof. [B-GET] [B-API] [B-ENCODE]

If a hard original-byte/type limit is a requirement, it needs a separately proven architecture: for example, an authoritative ingest proxy that counts actual streamed bytes and validates content, or a documented provider constraint with a negative test. A proxy adds host limits, timeouts, temporary-storage/egress and maintenance costs. Post-upload rejection or deletion may limit publication but does not undo incurred storage/traffic or establish preventive enforcement. **Do not promise a hard 1 GiB limit in this feature proposal.**

## 2. Transcoding states: do not interchange REST and webhook enums

The **Get Video `status`** enum is documented as follows. Upload success is not encoded-video readiness. [B-GET] [B-API]

| Get Video status | Meaning |
|---:|---|
| 0 | Created |
| 1 | Uploaded |
| 2 | Processing |
| **3** | **Transcoding — not Finished** |
| **4** | **Finished** |
| 5 | Error |
| 6 | UploadFailed |
| 7 | JitSegmenting |
| 8 | JitPlaylistsCreated |

For a proposed ordinary, non-JIT workflow, require a fresh server-side GET showing the expected library/GUID and **status 4**, plus the intended playable output, before offering final attachment. `availableResolutions` describes encoded resolutions available for playback. Unknown/JIT states need their own supported pipeline contract, not an assumption that any numeric value above 3 is ready. No encoding-time SLA was verified. [B-GET]

**The webhook guide publishes a different `Status` table. Treat this as a documentation/namespace mismatch requiring reconciliation, not a provider event mapping proven by this research.** That page lists 0 Queued, 1 Processing, 2 Encoding, **3 Finished**, **4 Resolution finished**, 5 Failed, 6 PresignedUploadStarted, 7 PresignedUploadFinished, 8 PresignedUploadFailed, 9 CaptionsGenerated and 10 TitleOrDescriptionGenerated. Do not copy those labels into the Get Video enum, or treat upload-finished/event-7 as transcode readiness. The safe proposed control plane uses callbacks as notifications and reconciles with the GET resource and its own job state. Verify actual callback payloads only under separately approved provider testing. [B-WEBHOOK]

The current webhook documentation specifies `X-BunnyStream-Signature-Version`, `X-BunnyStream-Signature-Algorithm` and `X-BunnyStream-Signature`: version `v1`, HMAC-SHA256 over the **exact raw body**, using the library's **Read-Only API key**. That secret is not the playback signing key. The checked page does not promise retry ordering, exactly-once delivery or built-in replay protection; repeated/out-of-order callbacks must not reopen cancelled/finalized jobs. [B-WEBHOOK]

## 3. Fit with this project's existing source

### Existing manual paths

- Free Videos stores `_cas_free_video_url` on `cas_free_video` (`inc/cas-free-videos.php:32–41`). It accepts a Bunny player URL or a bare UUID; bare UUIDs use the fixed existing library `734616`, whereas a full supported URL carries its library ID (`:182–198,216–257`). This is URL/ID authoring, not file upload. [P-FREE]
- Tutor lesson films use `_video`, with `source = embedded` and `source_embedded` containing an iframe/embed. The project extracts that iframe URL at `inc/cas-lesson.php:317–350`. It does not establish a `source_bunny` or other new Tutor video-source field. [P-LESSON]
- Tutor Free 4.1.3 `classes/Lesson.php:335–349` handles `video.source`, sanitizes `source_embedded` and writes `_video` through `filter_video_meta()`. A future uploader must preserve the actual installed metadata contract and other valid fields, not invent a schema from an example.

**Keep-manual launch procedure:** an authorised operator uploads in Bunny's dashboard and waits for readiness; course lessons continue to use Tutor's supported embedded-video editing, while Free Videos can use its existing URL/ID field. Use a stable canonical source URL/GUID, not a copied expiring token URL. No extra library/key/plugin purchase is required merely to continue that existing process. The process still needs normal content/ownership review; it is not a new in-WordPress uploader. [P-FREE] [P-LESSON] [P-SIGN]

A future nonproduction uploader must have an **explicit nonproduction library allowlist and secrets**; a missing test setting must fail closed. Never fall back to production library `734616`, the account-wide API key, or the Free Videos bare-ID default. Test data using bare IDs could otherwise target the wrong existing library. [P-FREE] [I149]

### Playback signing is not upload authorisation

`inc/cas-video-token.php` reads server-side `CAS_BUNNY_TOKEN_KEY` (`:44–45`), uses a 3600-second TTL (`:33`) and computes `SHA256(key + guid + expires)` (`:73–85`). This is playback signing, not the Stream library upload API key and not the TUS signing input order. Its `_video` read filter signs `source_embedded` (`:121–148`); empty signing configuration leaves URLs unchanged (`:60–68`). Do not copy that playback fallback as permission for an uploader to fall back to production. [P-SIGN]

Current official signed-player documentation uses `player.mediadelivery.net`, while this project's signer explicitly matches only `iframe.mediadelivery.net` or `video.mediadelivery.net` (`:67,103`). Therefore a new plugin/host/embed format must not be assumed to traverse the existing signer. Reuse the current accepted URL format or separately approve/test a compatibility change; no such change is implemented here. The playback authorisation/enforcement work in #87 is separate and was not retested by this research. [B-PLAYER] [P-SIGN]

**Prior-video hash trap:** the project's `_video` accessor returns freshly signed markup, not necessarily the persisted raw preimage. Hashing `get_post_meta(..., '_video', true)` for future optimistic concurrency can therefore hash changing expiry/signature data. WordPress `get_metadata_raw()` also applies `get_post_metadata`; its name does not make it a signing-filter bypass. A finalize design needs a server-held fingerprint of the actual stable stored record, obtained through a narrowly reviewed raw-storage/preimage path, not a client-supplied “old hash” or a dynamically signed accessor. [P-SIGN] [WP-META]

### Nonces and roles must remain distinct

- New REST-cookie endpoints would use the WordPress **`wp_rest` nonce**, supplied as `X-WP-Nonce` or `_wpnonce`, with real `permission_callback`/capability and target-ownership checks. A nonce alone is not authorisation. Application Passwords are a separate server/tool authentication method, not a secret to put into upload-page JavaScript. [WP-REST]
- Cached Tutor 4.1.3 defines `_tutor_nonce` and action `tutor_nonce_action` (`classes/Config.php:82–83`), checked in `Utils.php:1091–1098,1124–1127`. Frozen `inc/cas-content-builder.php:17–19,384–422` validates that **Tutor** nonce and current own-course authority before the existing native lesson/topic handlers. A REST `wp_rest` nonce is not interchangeable with it. [P-BUILDER]
- Existing `cas_content`/`manage_cas_content` and own-course editing are not blanket permission to create/delete remote videos. Any later feature must explicitly decide the upload capability and recheck the current actor, editable lesson, owning course, library and job on create, re-sign, status, finalize and cancel. Anonymous users/students must not obtain upload credentials; role downgrade, author transfer, course trash or lesson move must invalidate further control-plane authority. No role or capability is expanded by this note. [P-BUILDER]

## 4. What a custom implementation would have to own

This is a **design checklist for a separately approved feature**, not a shipped or already-proven implementation.

| Area | Required bounded behaviour | What must not be claimed |
|---|---|---|
| Secrets | Library upload API key server-only; account-wide key unnecessary for ordinary video ingest; playback token key separate; webhook read-only key separate where used | Putting the library key in a block, browser bundle, HTML, logs or issue |
| Job identity | Server-owned actor/lesson/course/library/GUID mapping; selected configured library cannot be changed by client input | Arbitrary GUID re-signing, production fallback or CORS-only authorisation |
| Upload/retry | One created object per logical job, stored resume URL, classified retry, expiry/reconciliation and orphan accounting | Every retry is free/idempotent, or re-signing extends the original resource forever |
| Ready state | Fresh provider GET/library/GUID plus supported ready output | TUS success, webhook upload event, or Get Video status 3 means ready |
| Finalize | One coordinated atomic job transition and metadata preimage-checked write; recheck authorisation; preserve old working embed until new video is ready | A prior hash comparison followed by `update_post_meta()` is automatically atomic |
| Cancel | Same state-transition gate as finalize; do not attach a cancelled job; stop issuing authorisations; reconcile remote resource separately | Local cancel instantly revokes an issued TUS authorisation or interrupts an in-flight PATCH |
| Replace | New GUID, encode/verify, conditional attach; preserve previous usable lesson on failure | Metadata POST or repeated PUT provides safe same-GUID binary replacement |
| Delete/cleanup | Only specifically owned, eligible GUIDs; check current/shared references and reconcile provider results; explicit cleanup budget/retention decision | Delete the prior/shared video or a whole library automatically after any successful lesson save |

For local concurrency, a design could use a verified database transaction/locking or an equivalent conditional state machine that coordinates the job's cancel/finalize claim with the exact `_video` preimage update. It must account for duplicate metadata rows and concurrent native lesson edits. A “check state, then save metadata” sequence on separate requests is not an atomic finalize. **No such atomic implementation has been delivered or tested under #149.** Even a correct local transaction cannot roll back Bunny's remote bytes or make WordPress and Bunny one distributed transaction.

Cancel/finalize must have defined outcomes: cancellation before attachment prevents attachment; a cancel arriving after a committed finalize reports that fact rather than pretending to undo the lesson. Lost responses/repeated finalize must reconcile the committed job/record and not create a second upload or overwrite a later edit. A remote cleanup failure must leave a visible, owned orphan record for operator review, not a false “everything removed” result.

The old video may be referenced by another lesson or Free Video. Never infer exclusive ownership from the fact that one lesson previously used its GUID. Deletion/replacement of published/shared media needs an explicit policy and separate authorisation; the manual workflow does not require the new uploader to solve unlimited content replacement automatically.

## 5. Costs and maintenance

Transport changes do not make the content free. Stored outputs, retained originals, overlapping old/new videos, abandoned jobs and resumed/repeated transfers can incur cost. Standard encoding is documented as free; Premium Encoding, transcription and DRM are optional separately charged features, not prerequisites silently approved by this ticket. [B-PRICE] [B-ENCODE]

Official documentation currently lists:

| Item | Published reference rate / condition |
|---|---|
| Minimum monthly usage | US$1 |
| Default storage region | US$0.01 per GB stored |
| Second storage region | Additional US$0.01/GB; third and later additional US$0.005/GB each |
| Standard delivery | Europe/North America US$0.010/GB; Asia/Oceania US$0.030/GB; other regional rates differ |
| Volume delivery | Starts at US$0.005/GB for the documented first monthly tier; different network/tier |
| Standard encoding | US$0 |
| Premium encoding | US$0.025/0.050/0.150 per output minute by resolution tier, **per output codec** |
| Optional transcription | US$0.10 per language per video minute |
| Optional enterprise DRM | US$99/month per enabled library plus licence charges |

**Starting price is not a regional quote:** the marketing Stream page advertises delivery “from” US$0.005/GB, while the detailed pricing documentation lists the regional Standard rates above, including Europe/North America at US$0.010/GB. The generic starting price does not establish a Europe/North America rate or a direct contradiction between the sources. This research does not determine the applicable account/tier/promotion or silently substitute one rate. Confirm the actual account plan, storage billing period/replicas and delivery tier when approving a test budget. The account minimum is not proof that additional test storage/traffic is free. [B-PRICE] [B-MARKETING] [I149]

No new paid plugin is proven necessary for the existing manual path. No recurring uploader service or exact development quote is authorised here. A custom uploader adds WordPress/Tutor compatibility work, secret handling, queue/poll/webhook reliability, resumable-client updates, abuse limits, audit/orphan cleanup and operator support. Proxy-based size enforcement additionally depends on host upload limits, PHP/web-server timeouts, temporary disk and bandwidth; these were not inspected or configured. That maintenance is the main reason not to promise this self-service feature for launch.

## 6. Bounded verification plan, not execution

### A. Offline/source/mock phase — no provider access needed

1. Freeze the intended WordPress/Tutor/package versions and decide one request family. Test REST `wp_rest` versus native Tutor nonce mismatches, missing nonce, student/anonymous actors, role downgrade, other author's lesson, deleted/moved lesson and client-swapped library/GUID/job IDs.
2. Model REST video statuses separately from webhook event statuses. Test upload-done-but-transcoding, REST status 3, ready status 4, failures, unknown/JIT states and malformed/replayed/out-of-order notifications.
3. Model retry expiry, lost responses, duplicate create/finalize, missing resume URL, GUID mismatch, local cancel while PATCH is active and re-sign refusal. Assert cancellation is local state, not invented remote token revocation.
4. Prove cancel-versus-finalize and concurrent native `_video` edits using deterministic races on the actual local storage design; test raw preimage handling under the existing signing filter, duplicate metadata, partial local writes and response loss. The old playable record must survive failed replacement.
5. Verify that logs/markup/JSON never expose API keys or playback keys, and that a declared MIME/size or `storageSize` is not accepted as preventive type/byte enforcement. No uploader exists yet, so these are future acceptance cases, not tests claimed passed here.

### B. Optional real-provider phase — requires separate OQ-14 approval first

Approval must explicitly identify a **nonproduction** library, secure scoped access, allowed operations/GUID ownership, storage/traffic/replication budget, retained residual-cost owner and cleanup policy. No production-library fallback, account-key creation, purchases or library provisioning is implied. If access is denied or ambiguous, stop; do not switch providers/credentials or use production. [I149]

With that approval, actual Review could run one small known video through create → TUS interruption/resume → transcode → GET-ready → conditional test-lesson attach; then test one controlled cancellation/expiry and owned-resource cleanup. Record provider statuses, resource identity and costs without logging secrets. Verify the exact supported embed host/signing and buyer/author access, not just upload HTTP success.

Negative tests for a claimed hard size/type restriction, deleting during an active upload, token reuse after local cancellation and same-GUID reupload are **unproven** and require explicit budget/safety scope; do not represent them as guarantees beforehand. If these cannot be safely bounded, retain manual ingest rather than promise the restriction. Poll GET after ambiguous operations and preserve evidence/orphan ownership; do not broaden deletion to other media.

### C. Decision gates / owners

- **Owner/CEO:** accept manual-at-launch recommendation; separately decide whether to fund and bound the custom uploader, hard limits and replacement/deletion policy. This document does not make that commitment.
- **Dev:** only if separately asked, write the explicit control-plane contract and offline implementation/fixtures; no undocumented React injection or security shortcut.
- **Actual Review:** verify any future exact implementation's role/nonce/storage/concurrency/provider behaviour; a plugin's existence or this research is not its security pass.
- **Owner/OQ-14:** separately approve real-provider resources, keys, operations and residual cost. No provider test is required to accept this source-only recommendation.

## Source register

Primary sources checked on 2026-10-23; links describe public documentation or immutable project source, not credentials/account endpoints.

[B-TUS]: https://bunny.net/docs/stream/tus-resumable-uploads.md "Bunny Stream TUS resumable uploads"
[B-HTTP]: https://bunny.net/docs/stream/http-api.md "Bunny Stream HTTP upload"
[B-API]: https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json "Bunny public video API OpenAPI schema; read-only document"
[B-GET]: https://bunny.net/docs/reference/video_getvideo "Bunny Get Video reference and VideoModel"
[B-WEBHOOK]: https://bunny.net/docs/stream/webhooks.md "Bunny Stream webhook event enum and signatures"
[B-WP]: https://bunny.net/docs/integrations/wordpress/stream.md "Official Bunny WordPress Stream block and shortcode"
[B-PLAYER]: https://bunny.net/docs/stream/player/signed-embeds.md "Official server-side Player embed signing"
[B-ENCODE]: https://bunny.net/docs/stream/encoding.md "Bunny encoding and storage implications"
[B-PRICE]: https://bunny.net/docs/stream/pricing.md "Detailed Stream pricing reference"
[B-MARKETING]: https://bunny.net/pricing/stream/ "Stream marketing pricing; from-price distinction retained"
[TUS]: https://tus.io/protocols/resumable-upload.html "tus resumable-upload protocol and optional extensions"
[WP-REST]: https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/ "WordPress REST authentication and wp_rest nonce"
[WP-META]: https://developer.wordpress.org/reference/functions/get_metadata_raw/ "WordPress metadata raw accessor still applies metadata filters"
[I149]: https://github.com/herman925/crystal-art-school/issues/149 "Exact feasibility scope and October 19 operator-reported findings"
[P-FREE]: https://github.com/herman925/crystal-art-school/blob/23723f4c5aef9abf5fe5839b89bed12d19c60ed7/wp-content/themes/hello-elementor-child/inc/cas-free-videos.php#L32 "Frozen manual Free Videos URL/ID handling"
[P-LESSON]: https://github.com/herman925/crystal-art-school/blob/23723f4c5aef9abf5fe5839b89bed12d19c60ed7/wp-content/themes/hello-elementor-child/inc/cas-lesson.php#L317 "Frozen Tutor lesson embedded-video renderer"
[P-SIGN]: https://github.com/herman925/crystal-art-school/blob/23723f4c5aef9abf5fe5839b89bed12d19c60ed7/wp-content/themes/hello-elementor-child/inc/cas-video-token.php#L33 "Frozen signing TTL, key, host matcher and metadata filter"
[P-BUILDER]: https://github.com/herman925/crystal-art-school/blob/23723f4c5aef9abf5fe5839b89bed12d19c60ed7/wp-content/themes/hello-elementor-child/inc/cas-content-builder.php#L17 "Frozen Tutor nonce and own-course handler checks"

The three project files `cas-video-token.php`, `cas-free-videos.php` and `cas-editable.php` have no diff between the cited approved main and frozen candidate; lesson/author/builder changes are evaluated at the explicit candidate pin, not assumed deployed.

Cached Tutor Free 4.1.3 evidence (source inspection only, not vendor code added to this commit):

| Cached file | Relevant lines | SHA-256 |
|---|---|---|
| `tutor.php` | 7,29 version declaration | `685cac9e241d8691d83ddb18c2b18248cfb681f7fa3f1fc63b812da0d4a13fcc` |
| `classes/Lesson.php` | 335–349 `_video` input/sanitization/write | `f66afd2ad2898a13e161ecb1bdb12a2585ac1999027097f15761d0146bc1c606` |
| `classes/Utils.php` | 1091–1098,1124–1127 nonce verification | `b5fbf74b3747b742f5f75abb3ce9043ca566670aead26fa5908087f4cacf4cf9` |
| `classes/Config.php` | 82–83 Tutor nonce names | `1633fc7f7d680faa4730f1b0ec79019c46cf58ad32bd83cda7dd605a3382fcb5` |

No standalone October 19 contract or its security sign-off is asserted by these sources. No real-provider test result or live-version/configuration attestation is inferred from preparing this note.
