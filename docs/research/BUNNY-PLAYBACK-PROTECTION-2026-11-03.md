# Bunny playback protection: MP4 blocking and the remaining DRM decision

The owner asked to prioritise theft/download resistance after delivery of the restricted CMS account and public Highlights uploader. This is a focused provider-configuration check, not a full-site audit or a claim that streaming can be made impossible to copy.

## Current deployed policy

- Player token authentication and CDN token authentication remain enabled; keys were preserved.
- Standalone play, MP4 fallback, exposed originals and Early-Play are disabled. Original source files remain retained privately.
- The `cas_block_video_mp4_downloads` CDN edge rule blocks the `mp4` extension even with a valid signed media path. Turning off fallback alone did not stop retrieval of an already-generated MP4.
- Existing playlist caching, caption and original-file rules were preserved. HLS playback, thumbnails and captions remain separate from MP4 blocking.
- Basic DRM and provider IP verification are off for the concrete compatibility reasons below. No Enterprise DRM or premium processing was enabled; WordPress roles, content, prices and enrolments were not changed.

## Rejected Basic DRM trial

Bunny documents Basic MediaCage as free dynamic, per-play-session encryption, with embedded-player-only delivery and no MP4 fallback. A four-second synthetic film confirmed an encrypted HLS variant and successful decoded playback after activation. The provider also automatically turned CDN token authentication off.

Attempting to retain both protections returned HTTP 400, `VideoLibrary.TokenAuthAndDrmConflict`, with the message “Cannot have Token Authentication and Basic DRM enabled at the same time”. In Basic mode, requests to the synthetic film's variant, encrypted segment and 16-byte key endpoint succeeded without a signed media URL when an allowed-site/player referrer was supplied. No-referrer and foreign-referrer requests were denied. Referrers are forgeable; this is not an identity check. No real film was downloaded or decrypted.

Basic was therefore reverted rather than presented as a security improvement over the existing paid-media token gate. The provider IP-verification trial separately caused a CDN 403 and prevented player initialisation in the controlled browser. Reverting IP verification restored decoded playback. Do not report an IP-bound link or treat a saved checkbox as a passing playback test.

The edge-rule API interprets a supplied GUID as an update: a new random GUID and an all-zero GUID both returned `pullZone.edge_rule_not_found`. A null GUID created the intended rule; readback verified exactly one enabled rule and preserved the three existing rules and signing key. No broad zone settings replacement or deletion of media/originals was used.

## Connected checks

One 3,151-byte, four-second synthetic clip was uploaded using the existing standard encoding tier. Encoding took longer than the initial two-minute readiness bound; the same fixture was reused when it finished, with no duplicate upload or re-encoding.

- Before changes, valid signed media paths returned plain HLS segments and an MP4, with allowed/player referrers. The unsigned routes were refused.
- After the final edge block, all 24 bounded cases were recorded: unsigned master, segment, MP4 and original routes refused with no/allowed/player referrers; the previously valid signed MP4 and original routes also refused. Valid signed HLS still delivered the clip. Each response read was limited to 1,024 bytes.
- Unsigned, correctly expired and tampered player URLs and standalone play produced denial documents without a player. The legacy host can return HTTP 200 with body `403`; status alone is not a pass. A valid player with no/foreign referrer was also denied in this run; the same signed player with an allowed referrer worked without WordPress authentication. Earlier no-referrer success is historical and not a permanent provider guarantee.
- Actual WordPress browser checks after the MP4 rule: both guest free previews and the existing enrolled subscriber's paid lesson played with decoded dimensions. Paid playback resumed after a seek. Logged-out and logged-in unentitled paid pages exposed no player capability. The synthetic clip also played after the final rule, with no media failures or page errors.
- The tiny unshared provider fixture was deleted after checks; no WordPress test course or lesson was needed for this pass. Final original-video/content/account/configuration readback is retained privately.

Browser checks used the existing cached Playwright/Chromium, in an owned container with read-only tools/control mounts, explicit credentials only, empty allowlisted environment, bounded CPU/memory/processes/disk/time, and the network access needed for the owner-authorised connected check. No incident-held Review service was used. Provider keys remained parent-side; the browser received only short-lived playback URLs and the existing test-student login. Private receipts, signed capabilities and credential-bearing configuration preimages stay outside public Git. These short samples do not prove Safari/iOS, long-film expiry/network recovery or whole-film endurance.

## What is still not prevented

A valid signed URL is a bearer capability, not a WordPress-session credential. The iframe signature lasts one hour; the previously observed media signature lasted about ten hours. A forged referrer can defeat a referrer-only restriction. An authorised browser receives usable HLS media, so disabling MP4 does not prevent all download tools. Screen recording or filming a display cannot be universally prevented.

Keep #87 open. This release closes the MP4-specific gap, not the broader anti-copying goal.

## Concrete next step for approval: Enterprise DRM

Bunny's published stronger tier uses Widevine/FairPlay and protected key exchange. Current listed pricing is US$99/month plus licence fees, with potentially multiple billable licences per playback. Activation requires coordination with Bunny; Safari/iOS support requires an Apple FairPlay deployment package. No charges, support messages, certificate registration or new production library were initiated.

Proposed rollout after approval:

1. Confirm pricing and activation/migration requirements with Bunny; establish who supplies the FairPlay package and verify which existing originals are available. Preserve private copies and all current metadata preimages.
2. Prepare a protected paid-lesson library on the existing Bunny account; public Course Highlights can remain in the current library. Keep credentials in the existing private Hostinger boundary. Crystal still uses WordPress; no new client-facing platform or iframe-copy workflow.
3. Qualify one synthetic protected clip and a representative long film: entitlement, copied URLs, licence expiry/replay, media/key extraction resistance, Safari/iOS and Widevine browsers, seeking, network changes and upload/replacement. A valid embed signature still needs an explicit sharing policy; Enterprise DRM is not automatically WordPress-session binding.
4. Add the smallest necessary uploader/library-routing and playback changes, then migrate selected lesson references only after acceptance. Preserve existing references until protected replacements pass, with exact-preimage updates and conditional rollback. Do not bulk re-encode/delete or change public previews, course prices or enrolments under an evaluation approval.
5. Update the private briefing with the observed device limits and costs. Only then obtain the separate production activation/migration approval if the initial approval covered preparation alone.

## Questions prepared for Bunny support (not sent)

- Is there a supported way to retain signed CDN entitlement with Basic DRM, given the observed TokenAuthAndDrmConflict and tokenless allowed-referrer key/variant responses?
- Can the embed player's approximately ten-hour media capability be shortened or revoked without breaking long playback? Which setting/API and renewal behaviour apply?
- What player, proxy/IP-family or network constraints apply to the observed IP-verification CDN refusal?
- For Enterprise, confirm current billing, existing-video migration/original requirements, FairPlay prerequisites, signed-licence/expiry controls and a reversible synthetic pilot.

Sources: [security options](https://bunny.net/docs/stream/security-options), [Basic setup and limitations](https://bunny.net/docs/stream/quickstart-mediacage-basic), [DRM tiers/pricing](https://bunny.net/docs/stream/drm), [Enterprise prerequisites](https://bunny.net/docs/stream/quickstart-mediacage-enterprise), [library settings API](https://docs.bunny.net/reference/videolibrarypublic_update), [edge-rule API](https://bunny.net/docs/api-reference/core/pull-zone/addupdate-edge-rule).
