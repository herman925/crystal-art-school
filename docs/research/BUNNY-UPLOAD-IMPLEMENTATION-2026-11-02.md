# Tutor lesson uploader implementation and acceptance

Status: deployed and enabled on WordPress on 2026-11-02 following the owner’s explicit “please make it live” request. That authorisation superseded waiting for a separate test library: a bounded live check used one 3,151-byte, four-second synthetic clip and a temporary private draft, both removed after verification. Local evidence and observed live results are distinguished below. The separate real-account handover in #150 is unchanged.

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

Set the constant privately in `wp-config.php` to the actual outside-webroot path. Provision only the chosen library key; keep the account-wide key off the site. Existing `CAS_BUNNY_TOKEN_KEY` playback signing remains separate and must be configured before this uploader enables. PHP must be able to read the file; the web server must not serve it or its backups. The live private file and parent are outside the webroot; mode 0600, PHP readability, plugin hashes and public-path key exclusion were verified. A guessed public path returned 404 and guest API access returned 403. Full PHP compromise can read a secret PHP needs; the private developer handover note records that accepted architecture boundary and the later isolation discussion.

The browser receives only library/video IDs and a video-specific upload signature valid for at most two hours (and no later than the job expiry). A job/context lasts 24 hours. Per-user controls allow two active jobs and 20 creations per day; these are application job limits, not a hard provider byte/type limit. Upload creation uses an idempotency key and records ambiguous provider results as `unknown`; do not blindly create a second film. Cancel stops the local transfer and prevents application attachment. It does not revoke an already-issued Bunny capability or delete a remote object.

Readiness uses the current **REST** status model: only Finished (4), positive duration and encoded resolution/codec metadata qualify. Created/Uploaded/Processing/Transcoding/JIT states do not qualify; Error (5) and UploadFailed (6) fail. JIT early playback is intentionally unsupported pending real-library qualification. A new or reused film is refreshed before selection and again before save. [Bunny video schema](https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json)

The native save uses a course mutex, InnoDB transaction, current parent/ownership checks, locked context revision and raw stored video identity. Token refresh does not produce a false conflict. Tutor's post, metadata and context commit together; an ordinary interrupted save rolls back. Duplicate video metadata, stale selection, moved lessons and unsupported records are refused. Existing lesson authorship is preserved. An unexpected database reconnection is an uncertain outcome requiring readback/reconciliation; this is not a claim of guaranteed rollback across a server/connection failover.

The stored video remains Tutor's `embedded` source with a stable iframe reference and provider duration. Expiring playback tokens are generated on read, never committed to the lesson. Replacing/detaching a film does not delete it or alter other lessons. A pending transfer for a new lesson follows that lesson's first native save. Free Videos retain their YouTube workflow. Course highlights are a separate future integration, since they are public previews.

## Local evidence

The isolated fixture uses WordPress 7.1.3/PHP 8.3, Tutor/Tutor Pro 4.1.3, the project theme, synthetic users and dummy keys. The connected check additionally verified the real WordPress 7.1.4/PHP 8.3.36/Tutor and Pro 4.1.3 stack. Execution uses read-only source/toolchain mounts, isolated loopback networking with no external connection, an empty allowlisted environment and bounded CPU, memory, processes, disk and time. Existing incident-held containers are not used.

- `tests/bunny-uploader/runtime.php`: 47 assertions covering permissions/nonces, cross-owner refusal, idempotent creation, key exclusion from responses, upload expiry, REST readiness/failure states, cancellation, selection, revoked capability, catalogue and stable signing identity.
- `tests/bunny-uploader/native-save.php`: 22 assertions through Tutor's native handler, covering kept/replaced/removed video, correct duration, stable stored reference, refused direct embed, stale/expired contexts, concurrent video changes, native Discard, injected transaction rollback and new-lesson/pending-upload binding.
- `tests/bunny-uploader/browser.cjs`: actual registered panel → synthetic TUS transfer → Ready → native save → fresh page/reopen with video and duration; no unhandled page errors. Desktop 1440 × 1100. Browser plugin unavailable; cached Playwright/Chromium used within the isolated fixture.

The synthetic transport file is not a decodable film. These tests prove application integration and local protocol handling, not Bunny encoding, provider policy enforcement, actual seek/playback or real-library upload recovery. The 888 × 745 browser recovery check also passed: interrupted transfer → same original file → same job/TUS resource → Ready, then cancellation and the approved picker. Five standalone configuration checks passed for private storage, public-path refusal, loose-permission refusal, symlink refusal and missing-signing-key refusal. Do not present a local fixture screenshot as deployed WordPress or put unshipped controls into Crystal's guide.

## Copied-link and download acceptance — requested by the owner

**A copied signed player URL is not bound to a WordPress login.** The current signer covers the secret, video ID and expiry and uses a 3600-second TTL. A valid copied URL can be reused during that window where Bunny's other controls permit it. A no-referrer/foreign-origin refusal would demonstrate that additional restriction, not WordPress-session binding. Expiry may stop a new player request without stopping playback already started.

Bunny distinguishes player/embed authentication from underlying CDN protection. Player HTTP 200 is not proof of playable media, and player HTTP 403 is not proof that HLS/MP4/original files cannot be accessed. [Bunny security layers](https://bunny.net/docs/stream/security), [embed token authentication](https://bunny.net/docs/stream/token-authentication), [security options](https://bunny.net/docs/stream/security-options)

The owner requested live activation on 2026-11-02. The connected check therefore used a known four-second synthetic clip in the existing library with a temporary private draft, rather than claiming a separate pilot occurred. No complete paid film was exported. The operator backed up database/configuration first, checked the existing references, and preserved real content/account state.

| Check | Observed live result |
|---|---|
| Bare GUID / unsigned player | Legacy iframe host returned a denial document (body text `403`, no video or media capability), despite HTTP 200; modern host returned HTTP 403 |
| Correctly expired / altered token | Refused; no video or signed media capability in the legacy response; modern expired URL returned 403 |
| Copied valid URL without WordPress login | Played the test clip in a fresh browser with zero WordPress cookies and no supplied referrer; foreign-referrer response had no player/capability |
| Underlying media token lifetime | Bunny issued a separate signed path with about 36,000 seconds remaining; do not equate this with the one-hour iframe TTL |
| Unsigned HLS master, segment, MP4 and original | After the correction below, all returned 403 with no referrer, an allowed-site referrer and a player referrer; bounded reads only |
| Signed delivery | Signed master/variant/segment returned 200 and the test film advanced with decoded video dimensions |
| Guest/student pages | Logged-out paid page emitted no Bunny player capability; a guest free preview and existing enrolled student’s paid lesson both advanced with decoded video dimensions (short samples, not entire films) |
| Started playback beyond iframe expiry | Not measured by waiting an hour; no immediate revocation claim |
| Uploader disabled | Not toggled on production for a test; stable stored reference and independent theme signer were read back |

The release found `ZoneSecurityEnabled=false` on the linked CDN zone even though `PlayerTokenAuthenticationEnabled=true` was set on the library. Before correction the known test clip’s unsigned playlist/MP4/original returned media with an allowed/player referrer. On 2026-11-02 the operator set the library’s `EnableTokenAuthentication=true` and read back `ZoneSecurityEnabled=true`. The existing security key already matched the WordPress signer and was preserved. Signed playback still worked; the tested unsigned routes then returned 403. Other library controls—including direct play, MP4 fallback and DRM—were preserved; DRM remains off and no paid feature was enabled. [Bunny update API](https://docs.bunny.net/reference/videolibrarypublic_update)

The live upload journey passed: upload directly from the actual panel, real Bunny encoding to Ready, native Tutor save, reopen with duration 0:04 and the approved picker. Twenty-two legitimate pre-existing Bunny references were indexed; four legacy demo HTML5 references were kept. The test film had no other references before deletion. Its course/topic/lesson/jobs and remote clip were removed, and all 26 original video fingerprints, course status/ownership and account roles matched their preimages. The live target stack is WordPress 7.1.4/PHP 8.3.36/Tutor and Pro 4.1.3. The separate role switch remains #150.

A private database/wp-config/active-plugin snapshot and hash-checked plugin staging preceded activation. Only the library upload key was provisioned. Prefer narrow plugin deactivation for rollback; a full database restore can undo new orders/student activity and needs reconciliation. Existing references use the theme’s signer independently of the uploader. Treat the CDN correction separately from an uploader rollback, and preserve working protected playback.

Historical #87 closure explicitly recorded the one-hour sharing limit and successful HLS playback, but did not establish all of this matrix. Do not describe that closure as a fresh anti-download pass. Streaming necessarily sends media to an authorised device; referer/token controls and DRM can raise the barrier, but cannot guarantee prevention of copying or screen recording. Changing the credential host to Cloudflare would not by itself change this playback model.

## Handover and remaining checks

The client guide is updated with the live upload/reuse/replacement/recovery workflow and actual backend captures. The protected developer edition documents the Hostinger boundary, bounded live check, CDN correction and copied-link/media-lifetime limits. Keep the real-account restriction/ownership handover and Crystal’s own usability trial in #150. A representative long client upload, real-network interruption/reload, provider capability reuse after Ready/cancel, cross-device codec checks and any signed-link/DRM policy changes remain follow-up qualification; do not mark these passed by the tiny clip or local mocks. The remaining playback/security decisions stay in #87.
