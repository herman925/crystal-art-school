# Bunny lesson uploads: client journey and delivery plan

Planning/research date: 2026-11-02. This is a source-backed plan, not a delivered uploader or approval to purchase/install a plugin or write to Bunny. Public vendor documentation, local source and a read-only WordPress version/role inventory were checked. No Bunny account/API operation, upload, deletion, installation, purchase or live configuration change was performed.

## Decision and recommendation

**Owner decision, 2026-11-02: do not use WP Video Flow or add a paid uploader licence/subscription.** The earlier plugin-first recommendation is superseded. Do not buy, install or run a Video Flow qualification trial under this plan.

Use a focused site integration with **Bunny Stream's resumable TUS API** and the existing Tutor lesson editor/storage/signing path. Start by proving the supported editor/save integration offline. Use a standard TUS client; keep the provider API key server-side and associate each upload with the authorised course/lesson and job. [Bunny TUS](https://bunny.net/docs/stream/tus-resumable-uploads), [existing feasibility assessment](BUNNY-UPLOAD-FEASIBILITY-2026-10-23.md)

The target remains **Content Hub → own course → lesson → Upload video → progress/processing → Ready preview → Update lesson**. Free public videos stay on YouTube. There is no additional paid uploader licence in this route; Bunny's existing storage/delivery charges and the integration's development and maintenance work remain.

This is a plan, not a delivered feature. Preserve the current manual operator workflow until the integration passes the stated checks.

## Proposed client journey

This section defines the target to qualify or build. It is not a walkthrough of controls already installed.

**One WordPress login, one lesson editor, and one normal lesson save.** Crystal should not need the Bunny dashboard, an iframe, a video GUID, an API key, or plugin/settings access.

### Upload a new lesson film

1. **Open Content Hub → Manage Courses → course → Curriculum → lesson.** For a new lesson, enter its name first. The upload should use that name automatically instead of asking for it again.
2. **Choose Upload video and select the file.** Start the authorised transfer directly from the browser to Bunny Stream. Display the filename and upload percentage in the lesson's Video area. Do not route the large file through ordinary WordPress Media/Hostinger storage.
3. **Show Processing after upload reaches 100%.** Bunny is preparing playback; 100% transferred does not mean ready. Crystal can continue writing the lesson. Do not label the video attached or saved at this stage.
4. **Show a thumbnail, duration and Preview when ready.** Fill Tutor's playback duration from a valid provider value where supported, keeping the combined lesson-count field and lesson cover separate. Keep the iframe/token work out of the client's daily interface.
5. **Crystal uses Update lesson once.** This is the explicit commitment to use the chosen film. The integration validates the ready video and saves the stable reference through the verified lesson-save path. It does not independently publish a course or change preview/access/price settings.

For a brand-new unsaved lesson, the evaluation must establish safe course-scoped upload-job binding and assignment when the native lesson is first saved. Prefer no extra save-and-reopen step. If the native API requires an explicit draft checkpoint, document that result before accepting the route; do not fake success or auto-publish an incomplete lesson to obtain an ID.

### Reuse or replace a film

- **Choose existing** opens a small approved-video picker with title, thumbnail, duration and readiness. Seed its index from a reviewed inventory of the legitimate existing course references, so Crystal can use films previously uploaded by an administrator. Do not expose the whole remote library or grant management permission merely because someone pastes a GUID into an embed.
- **Replace video** starts the same upload journey while the currently saved film stays live. New bytes get a new Bunny video ID. Only the selected lesson changes after Ready and Update; another lesson using the old clip keeps working. Do not delete the old/shared film automatically.
- **Remove from this lesson** detaches a reference with an explicit consequence; it does not delete the remote original. Permanent deletion and unused-upload cleanup stay in the administrator workflow for the first release.

### The visible states

| Crystal sees | What it means / what she can do |
|---|---|
| Uploading — 56% | File is transferring. Keep this tab open. Show cancel and an honest connection state. |
| Connection interrupted | Offer a verified retry/resume path. A retry must reuse the same upload job rather than make duplicate films. |
| Processing | Transfer is complete; Bunny is preparing playback. Keep the old saved film for a replacement. Processing continues remotely if she leaves. Preserve job status for her return. |
| Ready — preview available | She can preview the candidate and commit it with Update. |
| Saved to this lesson | The lesson save was read back and the selected film is now in use. |
| Could not process / save | Preserve the previous working video, explain whether to retry or reload, and retain a useful support reference without exposing credentials. |

Do not promise uploads continue after closing/reloading the browser. Bunny's resumable API can support recovery with the same file/job within its valid resource window; that behaviour must be implemented and tested. A local cancel stops the application's proposed attachment and browser transfer; it is not proof that an issued provider capability was instantly revoked.

### Scope and access

- **First release:** paid lesson videos, including new upload, approved existing-video selection, preview, replacement and clear status. Preserve existing lessons, free-preview flags, film counts, covers, reference photos, materials, prices and enrolments.
- **Next extension after the lesson path passes:** reuse the uploader in Course Highlights Video. Clearly label public-preview use and test guest access separately; do not silently expose a paid lesson film as a course highlight.
- **Free Videos remain YouTube.** Crystal uploads there and pastes the YouTube share link into the existing Free Videos entry. No YouTube migration or YouTube upload integration is included.
- **Crystal uses her normal WordPress account.** Test the intended `cas_content` role against actual course/lesson ownership. Administrator-only success is insufficient. No Bunny login, billing, security, plugin updates or Elementor powers are required for daily uploads.
- **You configure and maintain the connection.** Keep upload credentials server-side and separate from playback signing. Readiness, authorisation, replacement and cleanup must be enforced by the server, not just hidden buttons.

## Best course of action and delivery sequence

The architecture and client journey are fixed: direct-to-Bunny video transfer from the lesson workflow, automatic stable reference generation, protected playback and one deliberate lesson save. The chosen route is the focused site integration. The first stage proves the supported editor and save hooks before implementing the upload transport; paid-plugin evaluation is removed.

1. **Prove the Tutor editor bridge offline.** Use an isolated WordPress copy, synthetic content editors and mocked Bunny responses. Verify exact versions, supported editor extension/save hooks, main-video placement, signed playback and new/existing lesson behaviour. Avoid unsupported core/React patching or manual iframe steps. This replaces the paid-plugin qualification stage.
2. **Build the focused upload control.** Provide Upload video, approved existing-video selection, preview and replacement in the lesson workflow. Use direct browser-to-Bunny resumable transfer with server-issued per-video authorisation, owned job state and honest Uploading/Processing/Ready messages. Keep the existing protected player and native lesson-save action; do not add unrelated image offloading, media migration or another paid service.
3. **Implement/prove the save and ownership contract offline.** The server owns the actor/course/lesson-or-draft/library/video/job mapping. Every create/status/resume/selection/attach/cancel request rechecks current authority. Verify that a cancelled or stale job cannot attach, that duplicate responses are safe, and that a simultaneous native Tutor edit cannot be overwritten. Use a stable raw stored `_video` fingerprint; the normal accessor returns fresh signatures. Detect/reconcile duplicate metadata rows rather than assuming the first value is authoritative. Prove one coordinated conditional commit for the job and lesson reference, not a hash check followed by an unconditional write. Freeze the supported hooks/storage mechanism after inspection; do not improvise DOM targeting of hashed React classes.
4. **Run a separately bounded Bunny pilot.** Use an explicitly chosen nonproduction library with scoped access and agreed allowed operations, storage/traffic/retention/cleanup budget and owner. Start with a small known test clip, then one representative client file, interrupted upload, processing failure and safe replacement. There is no production-library fallback. A browser size/MIME check is useful feedback, not proof of a hard provider-enforced byte/type limit; do not advertise a hard ceiling until proved. Use fresh provider readiness and playable-output checks before attachment, not upload completion alone. Confirm the installed library’s encoding/JIT settings and current REST status model; webhook event codes must not be substituted for REST statuses.
5. **Have Crystal exercise the actual simple journey.** In the test environment, ask her to add one film, reuse one, replace one, recover from an interruption and find the result after returning. If she has to understand HTML, GUIDs, vendor settings or ask the developer which save button commits the film, revise the workflow before release.
6. **Release a narrow scope, then update the guide.** Back up source and current stable video references, deploy with readback/rollback, preserve existing clips, and verify author/enrolled-student/guest/preview behaviour plus expired-token handling. Keep the real account role switch under its separate #150 handover. Replace the current operator-upload instructions only when the new flow is delivered, using captures of the actual WordPress controls. Put credentials, configuration, updates and orphan cleanup in the Herman-only documentation.

### What constitutes success

Crystal can open an owned lesson, choose a file, understand progress/readiness, preview it and use the existing save action without copying any code or visiting Bunny. The resulting film appears in the intended main player once, with correct duration, current signing and unchanged access rules. A normal description edit while processing must preserve that edit and the working film; token refresh alone must not create a false video-change conflict. Disabling the uploader must leave saved references playable through the project player, verified independently of the upload interface. Interrupted/failed replacements do not break the working lesson. Existing legitimate films are available without re-uploading. No privileged vendor settings or unrelated course records become editable.

The reference is stored without a permanently copied expiry/token; the existing signer generates fresh playback authorisation. The current signer accepts only the iframe/video mediadelivery hosts, so any new player host or embed format needs explicit compatibility proof. Do not infer that a signed iframe alone enforces enrolment; preserve and test the WordPress lesson access gate as well. [Current signing source](../../wp-content/themes/hello-elementor-child/inc/cas-video-token.php), [current lesson player](../../wp-content/themes/hello-elementor-child/inc/cas-lesson.php), [Bunny signing](https://bunny.net/docs/stream/token-authentication)

## Historical vendor evidence — paid uploader rejected


| Option | Verified first-party workflow | Fit for this request |
|---|---|---|
| Themeum's Tutor LMS BunnyNet Integration | Enable BunnyNet source; open video in Bunny; copy Direct Play URL; paste into lesson or course intro field. Listing currently reports v1.0.2 and WordPress tested through 6.9.11. | Removes iframe typing but still requires an operator to upload in Bunny and copy a URL. Does not document an in-lesson file uploader. Its source type also differs from this project's existing `embedded` path. [Themeum-maintained listing](https://wordpress.org/plugins/tutor-lms-bunnynet-integration/) |
| Official bunny.net WordPress plugin | A Gutenberg Stream block uploads/selects videos and exposes playback settings. A shortcode supports places without blocks. Bunny advertises TUS for resumable block uploads. | Real uploader, not embed-only. Public docs establish a block editor workflow, not automatic Tutor `_video` assignment or Content Hub/custom-role integration. An adapter would still need proof. [Block documentation](https://bunny.net/docs/integrations/wordpress/stream), [Bunny announcement](https://bunny.net/blog/bunny-stream-now-available-in-our-wordpress-plugin-for-instant-video-embedding/) |
| Video Flow for Tutor LMS | Adds upload/library controls to Tutor lesson editing; vendor claims automatic naming/collections, reuse, usage warnings and Video Manager. | Rejected by the owner because it adds a paid licence. No qualification, purchase or installation is planned. Earlier compatibility findings are retained for history only; public source/package was not inspected. [Product](https://wpvideoflow.com/video-flow-for-tutor-lms/) |
| Focused project integration | Server creates/owns a video job; browser uploads to Bunny; server verifies readiness and conditionally attaches it to the lesson. | Most direct control over existing UI, ownership and signing, with more development/maintenance. Bunny supplies the upload protocol, not the application state machine. [TUS documentation](https://bunny.net/docs/stream/tus-resumable-uploads), [project feasibility](BUNNY-UPLOAD-FEASIBILITY-2026-10-23.md) |

The official Bunny listing currently reports **3.1.1, WordPress tested through 7.1.4, minimum WordPress 6.7/PHP 8.1**. Its changelog says v3.0 changed player, v3.1 added shortcode `tokenAuth=true`, and editor preview cannot show libraries with direct URL file access blocked. These are vendor claims about that plugin, not proof of Tutor or project-signer compatibility. [Official plugin listing/changelog](https://wordpress.org/plugins/bunnycdn/)

## Historical Video Flow findings — not an implementation recommendation

Its first-upload guide describes:

1. Open a course and lesson in WordPress admin.
2. In lesson content, choose **Add Video** or **Select from library**. A separate Intro/Featured/Video URL placement also exists.
3. Pick a file and optional thumbnail; confirm; browser transfers directly to Bunny.
4. Keep the upload tab open. Leaving stops transfer and requires starting again.
5. Wait through Uploading → Processing → Ready; save/update the lesson and test playback.

**Placement matters:** the content-editor action inserts into lesson content; it is independent of the featured video field. Using both can display two videos. The earlier plugin evaluation would have needed proof that its separate featured field preserved `_video` and signing; that evaluation is no longer planned.

The guide says instructors see only their own videos. Replacement is Remove Video, then upload another; the old video stays in Bunny. This is not a documented atomic replacement guarantee. Nor does this guide promise browser-restart resume. [Upload guide, updated 2026-08-23](https://wpvideoflow.com/docs/tutor-video-flow/uploading-your-first-video-lesson/)

The troubleshooting page reinforces keeping the tab open/computer awake and retrying after network interruption. Therefore do not advertise persistent pause/resume for Video Flow based only on Bunny's transport capabilities. [Troubleshooting](https://wpvideoflow.com/docs/troubleshooting/common-issues-and-how-to-fix-them/)

For a future custom TUS flow, Bunny documents retry within a page session and resume after reload using the **same video ID and stored upload URL**, with server-side ownership checks before re-signing. Re-signing does not extend the original upload resource expiry; the documented window ends at original expiry or roughly 48 hours inactivity, whichever comes first. A generic “resume anytime” promise would be wrong. [Bunny TUS](https://bunny.net/docs/stream/tus-resumable-uploads)

## Current compatibility evidence and inconsistencies

A fresh read-only inventory on 2026-11-02 reports WordPress **7.1.4**, Tutor **4.1.3** and Tutor Pro **4.1.3**. Plugin-file modifications are disabled. The same Tutor 4.1.3 source is available locally for inspection. The planned client role is **`cas_content`**, editing its own content without Administrator or `manage_options`; it is not Tutor's instructor role. The owner remains Administrator pending a separate role change. The live inventory confirms the Administrator role remains assigned; it does not prove a new uploader works under the intended role. PHP/runtime/package compatibility must also be frozen before evaluation.

Video Flow's public docs index says Tutor Free/Pro **3.7+**, **tested through 4.0.1**, WordPress 6.0+ and PHP 7.4+. “We test latest” is a general assurance, not an explicit 4.1.3/7.1.4 result. [Docs FAQ](https://wpvideoflow.com/docs/)

Its latest published changelog is **1.5.0, 2026-10-21**. That release declares actual requirements **PHP 8.0 and WordPress 6.5**, contradicting older install/docs minimums. The changelog also records restricted library loading in 1.5.0, a custom-role instructor course-filter fix in 1.2, and earlier duplicate-upload/embed-deletion fixes in 1.3. These indicate relevant maintenance but do not establish arbitrary custom-role support or concurrency correctness. [Changelog](https://wpvideoflow.com/docs/tutor-video-flow/video-flow-for-tutor-lms-changelog/), [older installation guide](https://wpvideoflow.com/docs/tutor-video-flow/installing-tutor-video-flow/)

The setup guide requires an administrator to connect the Library ID, library API key and separate Read-Only API key, and configure a generated webhook URL. It documents central configuration, not which exact capabilities its upload/select/delete routes require. Clients should not receive these credentials. [Setup guide](https://wpvideoflow.com/docs/tutor-video-flow/first-time-setup/)

Themeum's documentation endpoint timed out/returned HTTP 522 during this research; its own WordPress.org listing supplied the workflow evidence. Older search-indexed Themeum GitHub headers show 1.0.0, while the maintained directory lists 1.0.2; the older header must not be treated as current package inventory. [Maintained listing](https://wordpress.org/plugins/tutor-lms-bunnynet-integration/)

## Historical paid-plugin pricing — rejected option

| Video Flow plan | Published introductory price | Published regular price | Production sites |
|---|---:|---:|---:|
| Single | US$99/year | US$149/year | 1 |
| Pro | US$199/year | US$349/year | 5 |
| Agency | US$399/year | US$749/year | 25 |

Core direct uploads and video management are in Single; migration features are not needed for this request. The vendor says staging/local installs do not count, prices exclude applicable taxes, and a 14-day refund is offered. Price lock depends on the applicable renewal conditions; it is not a lifetime purchase. Confirm price/terms at any later purchase. [Vendor pricing](https://wpvideoflow.com/pricing/)

Bunny hosting is separate. Its own pricing page advertises storage from **US$0.01/GB**, delivery from **US$0.005/GB**, and a **US$1 monthly minimum**; actual region/settings/usage matter. Video Flow's docs FAQ says “no minimum spend,” which conflicts with Bunny's own page: use Bunny's pricing for Bunny charges. This research does not estimate a monthly bill. [Bunny pricing](https://bunny.net/pricing/stream/), [conflicting vendor FAQ](https://wpvideoflow.com/docs/)

## Acceptance checks for the chosen integration

These are requirements for the site integration, not executed tests or permission to change live services. Begin with offline fixtures; use the separately bounded provider pilot only after its scope is agreed.

1. **Exact versions and entry route:** explicit support for Tutor 4.1.3, WordPress 7.1.4 and the actual PHP version; controls/assets work in the lesson editor opened from Content Hub through the supported native builder route.
2. **Restricted role:** upload, library search, progress, replacement and save work for `cas_content` without granting the WordPress Tutor instructor/Administrator roles, `manage_options`, broad cross-course rights or access to settings. The already-planned internal Tutor profile markers are separate from granting an instructor role and remain subject to #150’s handover checks. Verify server checks for lesson/course ownership, arbitrary GUID access, changed authorship and role removal. An Administrator demonstration cannot prove this.
3. **Storage contract:** show what the featured field persists. Preserve `_video.source = embedded`, `source_embedded`, and other valid fields for the current renderer. Confirm how pre-existing manual videos are detected and whether “own videos” hides assets originally uploaded by an administrator.
4. **Signed playback:** show the emitted iframe/shortcode host and signing mechanism. The project's current signer accepts `iframe.mediadelivery.net`/`video.mediadelivery.net`; current Bunny examples use `player.mediadelivery.net`. Content insertion or a new player host does not automatically pass through the existing `_video` signing filter. Check authenticated student playback and fresh tokens after expiry/cache, with the existing protection enabled. [Project signing assessment](BUNNY-UPLOAD-FEASIBILITY-2026-10-23.md), [current Bunny signed embed guide](https://bunny.net/docs/stream/player/signed-embeds.md)
5. **Failure and replacement:** distinguish upload completion from playable readiness; retain the working old video until a new one is ready and attached. Verify failed processing, reload, duplicate clicks, concurrent lesson edits and cancel/finalize races. Keep permanent remote deletion in the administrator workflow; detaching a lesson reference must not delete shared media.
6. **Stable concurrency:** inspect the persisted raw `_video` preimage, not an ordinary metadata read that returns refreshed signed markup. Coordinate any conditional attach with native Tutor edits; a separate hash-check followed by an unconditional write is insufficient. This is a project requirement from the prior source review, not a capability already delivered. [Prior concurrency findings](BUNNY-UPLOAD-FEASIBILITY-2026-10-23.md)

**Delivery rule:** implement and qualify the focused Bunny integration. The paid Video Flow route is excluded. Any reuse of a free provider component still needs proof of Tutor placement, ownership and signing compatibility; it is not permission to install a broad offloader or change hosting/DNS.
