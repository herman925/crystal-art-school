# Tutor lesson uploader implementation and acceptance

Status: implementation in `feat/bunny-lesson-upload`; not enabled on the live site. No production upload key has been provisioned. No Bunny upload, deletion, configuration change or connected playback test is implied by the local results below. #241 remains open. The separate real-account handover in #150 is unchanged.

## Implemented design

`wp-content/plugins/cas-bunny-uploader` adds a lesson-video panel to Tutor 4.1.3's supported `bottom_of_sidebar` content slot. A registered text field carries an opaque context/revision through Tutor's actual lesson-save payload. It is hidden by its stable name, with no hashed class selectors, React internals or changes to Tutor. The supported extension API has no custom hidden field/form context; the named-input bridge therefore requires a browser compatibility check after Tutor updates. [Hook inspection](TUTOR-BUNNY-HOOKS-2026-11-02.md)

The client selects Upload video, sees transfer/processing status, previews the ready candidate, then uses Tutor's ordinary save. The same panel offers approved existing films, removal from the lesson and recovery of an interrupted transfer with the same original file. Files travel directly from the browser to Bunny using vendored `tus-js-client` 4.3.1. Crystal needs no Bunny login, key, GUID or iframe.

WordPress stores server-owned contexts/jobs in its prefixed `cas_video_jobs` table. Authenticated REST requests require a WordPress REST nonce, content/upload capabilities and current course ownership; status, selection, resume and cancellation recheck the principal/resource relationship. An opaque job ID alone grants no access. Ready catalogue films from a reviewed administrator import can be reused within the editor's currently authorised courses. No public API accepts an arbitrary video GUID as an approved film. Remote permanent deletion is deliberately absent.

The permanent library key is loaded from a mode-0600 PHP configuration file outside the resolved WordPress webroot through `CAS_BUNNY_UPLOAD_CONFIG`. The example below contains no credential:

```php
<?php
return [
    'enabled' => false,
    'library_id' => 'REPLACE_WITH_APPROVED_LIBRARY_ID',
    'library_key' => 'PROVISION_PRIVATELY',
];
```

Set the constant privately in `wp-config.php` to the actual outside-webroot path. Provision only the chosen library key; keep the account-wide key off the site. Existing `CAS_BUNNY_TOKEN_KEY` playback signing remains separate and must be configured before this uploader enables. PHP must be able to read the file; the web server must not serve it or its backups. Actual Hostinger path/permissions and HTTP exclusion are still release checks. Full PHP compromise can read a secret PHP needs; the private developer handover note records that accepted architecture boundary and the later isolation discussion.

The browser receives only library/video IDs and a video-specific upload signature valid for at most two hours (and no later than the job expiry). A job/context lasts 24 hours. Per-user controls allow two active jobs and 20 creations per day; these are application job limits, not a hard provider byte/type limit. Upload creation uses an idempotency key and records ambiguous provider results as `unknown`; do not blindly create a second film. Cancel stops the local transfer and prevents application attachment. It does not revoke an already-issued Bunny capability or delete a remote object.

Readiness uses the current **REST** status model: only Finished (4), positive duration and encoded resolution/codec metadata qualify. Created/Uploaded/Processing/Transcoding/JIT states do not qualify; Error (5) and UploadFailed (6) fail. JIT early playback is intentionally unsupported pending real-library qualification. A new or reused film is refreshed before selection and again before save. [Bunny video schema](https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json)

The native save uses a course mutex, InnoDB transaction, current parent/ownership checks, locked context revision and raw stored video identity. Token refresh does not produce a false conflict. Tutor's post, metadata and context commit together; an ordinary interrupted save rolls back. Duplicate video metadata, stale selection, moved lessons and unsupported records are refused. Existing lesson authorship is preserved. An unexpected database reconnection is an uncertain outcome requiring readback/reconciliation; this is not a claim of guaranteed rollback across a server/connection failover.

The stored video remains Tutor's `embedded` source with a stable iframe reference and provider duration. Expiring playback tokens are generated on read, never committed to the lesson. Replacing/detaching a film does not delete it or alter other lessons. A pending transfer for a new lesson follows that lesson's first native save. Free Videos retain their YouTube workflow. Course highlights are a separate future integration, since they are public previews.

## Local evidence

The isolated fixture uses WordPress 7.1.3/PHP 8.3, Tutor/Tutor Pro 4.1.3, the project theme, synthetic users and dummy keys. The live inventory reports WordPress 7.1.4, so exact live-version compatibility is still a release check. Execution uses read-only source/toolchain mounts, isolated loopback networking with no external connection, an empty allowlisted environment and bounded CPU, memory, processes, disk and time. Existing incident-held containers are not used.

- `tests/bunny-uploader/runtime.php`: 47 assertions covering permissions/nonces, cross-owner refusal, idempotent creation, key exclusion from responses, upload expiry, REST readiness/failure states, cancellation, selection, revoked capability, catalogue and stable signing identity.
- `tests/bunny-uploader/native-save.php`: 22 assertions through Tutor's native handler, covering kept/replaced/removed video, correct duration, stable stored reference, refused direct embed, stale/expired contexts, concurrent video changes, native Discard, injected transaction rollback and new-lesson/pending-upload binding.
- `tests/bunny-uploader/browser.cjs`: actual registered panel → synthetic TUS transfer → Ready → native save → fresh page/reopen with video and duration; no unhandled page errors. Desktop 1440 × 1100. Browser plugin unavailable; cached Playwright/Chromium used within the isolated fixture.

The synthetic transport file is not a decodable film. These tests prove application integration and local protocol handling, not Bunny encoding, provider policy enforcement, actual seek/playback or real-library upload recovery. The 888 × 745 browser recovery check also passed: interrupted transfer → same original file → same job/TUS resource → Ready, then cancellation and the approved picker. Five standalone configuration checks passed for private storage, public-path refusal, loose-permission refusal, symlink refusal and missing-signing-key refusal. Do not present a local fixture screenshot as deployed WordPress or put unshipped controls into Crystal's guide.

## Copied-link and download acceptance — requested by the owner

**A copied signed player URL is not bound to a WordPress login.** The current signer covers the secret, video ID and expiry and uses a 3600-second TTL. A valid copied URL can be reused during that window where Bunny's other controls permit it. A no-referrer/foreign-origin refusal would demonstrate that additional restriction, not WordPress-session binding. Expiry may stop a new player request without stopping playback already started.

Bunny distinguishes player/embed authentication from underlying CDN protection. Player HTTP 200 is not proof of playable media, and player HTTP 403 is not proof that HLS/MP4/original files cannot be accessed. [Bunny security layers](https://bunny.net/docs/stream/security), [embed token authentication](https://bunny.net/docs/stream/token-authentication), [security options](https://bunny.net/docs/stream/security-options)

Run the following matrix on the owner-selected **nonproduction** library and a known test clip before enabling uploads. Record the library's effective settings and provider readback first; do not change production security settings or assume DRM is enabled. Use a separate browser context with no WordPress cookies and bounded media reads. Also test provider-side reuse/expiry of an already-issued upload capability after Ready/cancel; server refusal to issue another signature is not instant revocation of the old one. Never export a complete paid film to establish a download result.

| Check | Required observation | Current status |
|---|---|---|
| Bare GUID / unsigned player | Refused when embed enforcement is on | New test pending; historical production check passed Oct 21 |
| Expired signature | Refused even when the signature was correct for that expiry | Pending |
| Altered token, expiry or GUID | Refused | Pending |
| Copied valid signed URL, no WordPress session | Measure playback from permitted origin and no/foreign referrer separately; any permitted replay is a documented bearer-link limit | Pending |
| Signed player and paid student page | Authorised student actually plays/seeks; logged-out/unentitled paid page emits no player capability | New test pending |
| Free preview | Intended guest playback remains available | New test pending |
| Unsigned/copied HLS manifest and a segment | Check enforcement independently of the player; distinguish encrypted data from a playable copy | Pending |
| MP4 fallback, direct-play and original-file route | Disabled or access-controlled as intended; verify current configured routes with HEAD/bounded range requests, not a full download | Pending |
| Started playback beyond embed expiry | Record actual behaviour; do not advertise immediate revocation | Pending |
| Uploader disabled | Stored references still play through the existing signer/access gate | Connected check pending |

Historical #87 closure explicitly recorded the one-hour sharing limit and successful HLS playback, but did not establish all of this matrix. Do not describe that closure as a fresh anti-download pass. Streaming necessarily sends media to an authorised device; referer/token controls and DRM can raise the barrier, but cannot guarantee prevention of copying or screen recording. Changing the credential host to Cloudflare would not by itself change this playback model.

## Remaining release work

Agree the separate test library, permitted operations, clip/usage limits and cleanup owner; run the connected upload/recovery/encoding and copied-link matrix. Seed a reviewed existing-reference catalogue using exact raw preimages. Verify live PHP/WP compatibility, private configuration permissions/HTTP exclusion, role/access gates and disabled-plugin playback; then let Crystal exercise the actual workflow. Back up and deploy only after those checks. Replace manual-upload instructions with actual deployed screenshots; publish implementation/security/maintenance notes only in Herman's guide edition. Keep #241 open until its acceptance is met.
