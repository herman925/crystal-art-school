# Guide audit for UAT — 5 November 2026

Three sequential passes reviewed Crystal's working guide and the separately gated administrator edition against the deployed WordPress 7.1.4/Tutor 4.1.3 workflows and current project decisions. This is guide acceptance, not Crystal's UAT sign-off or permission to open payments, registration or indexing.

## Pass 1: coverage, accuracy and the client journey

Reviewed the existing 29 client pages and seven private administrator runbooks. Added three client pages: a practical website-review/UAT checklist and result template, picture/file handling, and privacy/student permissions. The client edition now has 32 content pages; the administrator edition adds seven runbooks. Each build also contains its 404 page.

Corrected the obsolete card-only decision: cards/Stripe plus PayPal are the chosen launch methods (#113). Both scoped provider sandbox journeys and test-inbox delivery are complete (#253/#120), while live activation, settlement/KYC, legal/VAT, real-device and human acceptance remain separate. Updated the private refund/billing/native-order-ID patch notes (#254/#256/#258), recovery status (#151/#158), core-integrity disposition (#200), handover and launch agenda.

Expanded ordinary edit/save/check/withdraw instructions for articles, free films, FAQ, student comments, About photographs and Showcase. Clarified Curriculum's location and lesson edit pencils, new-course draft/first-price flow, separate lesson/course/gallery-image saves, YouTube versus Bunny destinations, public/private file separation, certificate pin/order/no-scan rules, staff-versus-learner review accounts and the administrator's update/refund/user-management responsibilities. Corrected the obsolete student-credit instruction to preserve the linked student and respect their Showcase preference.

Kept deferred invisible watermarking and worker hosting in the private notes as a future client decision, with HLS survival and artwork fidelity explicitly unverified. No worker, model, DRM subscription or new website feature was introduced by this audit.

## Pass 2: actual screenshots and instruction usability

Captured/refreshed 30 distinct screenshots from the real restricted owner account, separate developer Administrator and relevant public website controls. Retained accurate earlier original crops where still applicable. The private source catalogue has 66 originals; the guide uses 63 unique PNGs: 61 in Crystal's edition and two additional administrator-only images.

Added context views of the course list, Basics/Curriculum, lesson editor, blog/artwork/Showcase editors, FAQ, privacy choices, filters and introduction editors, rather than relying only on isolated buttons. Refreshed certificate, video-picker and support-form captures. Waited for the writing editors and video previews to load; captured actual hover-revealed lesson controls. No controls were reconstructed, renamed or drawn. Incomplete initial captures were replaced before the accepted build.

Screenshot originals, capture metadata and private runbook sources remain outside public Git. New `admin-` screenshots are collected only by the administrator build; the client build refuses references to that prefix. Shared sign-in and access-denied messages now say “site administrator” instead of naming the developer.

Editor visits created six blank auto-drafts, including intermediate capture attempts. Exact ID/type/owner/status/empty-content guards preceded their removal; pre-existing auto-drafts were preserved. No existing content was saved or published and no price, payment, coupon, enrolment or privacy-default action was submitted. Readback preserved the original five courses, 26 lesson-video records, 18 published demo entries, account roles, course links and private-file ownership.

## Pass 3: rendered guide, links, images and access

- Both editions build: 33 client HTML pages and 40 administrator HTML pages, including each 404 page.
- 3,414 internal link/anchor checks: 1,402 client and 2,012 administrator; zero missing links or anchors.
- 132 screenshot uses checked for source PNG byte hashes, declared/natural dimensions and nonempty alternative text. All 63 referenced originals match.
- 142 rendered page/viewport/theme combinations: all 32 client pages at 1440×1000 and 390×844 in light/dark, plus all seven administrator pages at desktop/light and phone/dark. No blank pages, framework overlays, relevant page-script errors or document horizontal overflow. Four final plain-language edits received 16 targeted reruns.
- Browser interactions passed for client search, administrator-search exclusion, full-size image viewing, task shortcut anchors, native theme switching, mobile sign-out, resource revocation and same-browser administrator→client switching. Administrator pages/images are available only to the intended edition.
- Eleven synthetic HTTPS audience/access tests pass, covering signed-out resources, wrong credentials/external redirects, private chunks/images, traversal, no-store caching, logout/account switching, original screenshot bytes and generic shared-message wording. Updated one obsolete test marker to assert the current handover content.
- The changed PHP templates pass syntax checks. Authentication rules, existing guide usernames/passwords and WordPress permissions are not changed.

The in-app Browser runtime/skill was unavailable. Cached Playwright/Chromium ran in an owned bounded container; the guide fixture had no external network, read-only source/toolchain/private-input mounts, an empty allowlisted environment and isolated localhost HTTPS. Real backend capture used a separate bounded browser with only explicit WordPress credentials and the network required for the authorised capture. Held Review services were not used. Headless viewport checks do not constitute physical-device or screen-reader acceptance.

Private evidence includes static/browser/interaction summaries, original-capture metadata, guide render samples, cleanup and site-preservation receipts. Early capture/selector and stale-marker harness failures are retained as intermediate attempts, not application defects or passes.

## Publication reconciliation

The first guarded publication stopped before replacing live content because #261 had concurrently published the client-only website/service links update. Compared all changed page main text: only the approved homepage link section differed; all ten updated live PNGs matched the reviewed originals. Preserved the old release and verified current live baseline, merged #261 into this revision, and retained all eight website/service destinations. Rebased the exact-preimage guard only after the source/content comparison and a fresh unchanged snapshot.

## Publication and remaining UAT

Published the reviewed build from `d536aa5` through the exact-preimage installer: 392 private build files, private source/screenshot backups and existing reader credentials preserved. Actual Crystal and Herman guide logins passed new-page/image checks, private page/image denial, no-store behaviour, sign-out/resource revocation and same-browser account switching. Direct raw private HTML/PNG paths returned 403. Source originals matched the installed files; Hostinger CDN losslessly re-encodes PNG delivery, so three live image samples were decoded offline and matched the originals pixel-for-pixel. Delivery evidence is retained privately and recorded in #93. This final report-only update does not change the published guide build.

Crystal's actual review, final real-content/learner checks, representative long-upload/network recovery, physical devices, human screen-reader listening and remaining business/legal/live-activation decisions stay open in their existing tasks. Optional watermark/DRM work does not become a new promised UAT feature. The guide update does not remove the retained demos or send a client message.
