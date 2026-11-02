# Restricted account handover and public course highlights

Owner request: complete the agreed support-permission/account handover and extend the Bunny uploader to Course Highlights before payment-launch work. No payment/signup/indexing switch or real financial action is part of this release.

## Account scope

Crystal (owner user 1) uses only `cas_content`; developer user 2 remains Administrator. Crystal can manage assigned content, own courses/lessons and media, certificates, confirmed course prices, coupons, Sales and course-access corrections. The existing limited pending-order transitions retain their warnings and confirmation. User administration, refunds/disputes, provider/tax settings, plugins, Elementor/templates and general technical maintenance stay with the administrator. No broad edit-others, user or theme capabilities were added.

The refreshed inventory matched the reviewed plan: 39 FAQ/Free Video/overview/About Photo entries and 62 related public media records needed ownership transfer. No private media was transferred. The four real course links and existing course/lesson ownership were preserved. Eighteen published demo/test records stay published and outside the transfer. Certificates/scans already owned by Crystal needed no transfer.

A private database snapshot, exact row/capability/profile preimages and audit-source backup preceded the operation. The migration uses InnoDB row locks, exact preimage checks, targeted author changes, native Tutor profile markers and the normal role setter. Synthetic success and stale-preimage refusal cases passed, including no partial ownership changes. The first live attempt rolled back when it encountered the protected MU directory; the guarded SSH deployment path then paired the audit expectation 2→1 with the successful role/ownership migration, preserving directory mode 0555 and file mode 0644. No hardening permissions were left relaxed. The administrator-count alert remains enabled.

Live owner login reaches Content Hub. Approved price/Sales/coupon/access screens and the native own-course/Bunny lesson editor open. Plugins, Users, Settings, Themes and Elementor-template screens are denied/redirected. A temporary owned draft was created/edited and a benign image uploaded through normal authenticated WordPress APIs; both were removed. Real content/status, media metadata, demos and course links were checked unchanged apart from the planned authors. These are targeted workflow checks, not a full-site security audit or live transaction proof.

## Course Highlights design

The existing uploader is reused through Tutor 4.1.3's supported `CourseBuilder.Basic` slots and a registered `_cas_bunny_course_edit` field. Files still go directly to Bunny via the existing server-only credential and expiring TUS handoff. The normal course save commits the selection; the old highlight remains until then. The native raw intro-video field is hidden while this control is enabled.

Schema version 2 adds a `target` column, defaulting old records to `lesson`. Highlights use `course`, with lesson ID zero. Upload jobs, contexts, catalogue queries, selection and resume binding carry this destination. The public picker contains only approved highlights; a paid lesson job cannot be selected into it. An explicit public-preview acknowledgement is required and bound to the exact selected film before save. Existing public highlight references are imported only from reviewed course metadata preimages. Reusing the same remote GUID in different approved catalogues does not make client-supplied GUIDs authoritative.

Native course writes use the same course mutex and InnoDB video/context transaction. Completion uses `tutor_after_prepare_update_post_meta` after the native course post and metadata preparation; Tutor's final thumbnail handling follows that hook. Do not describe this as a guarantee covering arbitrary later plugin failures, database reconnection or lost responses: those need readback. Stale video identity/revision, unready or wrong-destination films, missing public approval and raw reference promotion are refused. Total course duration and lesson videos are separate from highlight duration.

The Basic panel refreshes after save, supports replacement/removal/reuse, and coexists with the lesson modal without sharing its registered field. A saved highlight on a draft is not published by uploading; publication stays with the ordinary course workflow. No paid film is used as a fallback when a highlight is empty.

## Qualification

- Existing 47 runtime and 22 native lesson-save assertions remain passing.
- 22 native course-highlight assertions cover public approval, public/lesson isolation, preserved runtime/lesson media, stable save, removal, stale edits, raw-reference rejection and interrupted-save rollback.
- Browser: public approval → synthetic TUS transfer → Ready → native Save as Draft → same-page saved state → reopen → public-only picker; the separate lesson picker remains available.
- Actual local public course rendering contains the approved highlight and excludes the private lesson film.
- Connected rollout, actual restricted-owner screenshots and final receipts are recorded in #249; no synthetic test is represented as a provider encoding or client long-upload pass.

Keep long client-upload/network recovery, final client training/content/payment acceptance and the existing signed-link lifetime/DRM decisions separate. Do not reopen delivered lesson-upload #241 for this extension or claim a new protection change under #87.

## New-course draft correction

The actual restricted-owner workflow exposed Tutor's client-side paid-price requirement on a newly created unpriced draft. The client must be able to prepare draft content before using the separate confirmed first-price workflow. Pending own drafts therefore receive a form-only non-monetary type placeholder; on a draft save the native guard normalizes it back to the unchanged stored paid/unpriced state. No amount is established and the pending-price marker remains. Unconfirmed amount changes and publication are still refused. Eleven native AJAX checks and a real browser draft-save/publication-refusal case passed, alongside the fourteen existing price contracts. The opt-in older broad runtime harness was not enabled.

Tutor Pro also overwrites early visibility data with its stored setting (an empty string when unset). Uploader visibility now runs after Pro, normalizes that empty setting, and hides the redundant native video controls. The restricted editor's native price controls are hidden because confirmed Course Prices remains the only intended price workflow. This is a content-authoring correction, not payment activation or a change to any real course amount.

## Connected completion

Plugin 1.1.2/schema 2 is enabled on the existing WordPress 7.1.4/PHP 8.3.36/Tutor and Pro 4.1.3 site. The actual restricted owner created a native draft, uploaded a 3,151-byte four-second public-preview fixture directly to Bunny, waited for encoding, selected the Ready result, saved through the normal course draft action and reopened at 0:04. Decoded preview playback advanced. A temporary lesson reusing that fixture retained its film during an ordinary title save with the redundant native video field hidden. A separate tagged paid-state draft (£10/£8) confirmed non-price content saves preserve the existing price pair while native pricing is hidden. These are targeted CMS tests, not a live payment trial.

Temporary course/topic/lesson/jobs, the separate paid-state draft and the unshared remote fixture clip were removed. The four original course links, 18 published demos, original course/highlight identities and related public-media metadata matched their preimages. Existing lesson/video preservation, role/administrator count and exact code hashes are retained in private operator receipts. The two pre-existing public highlight films were indexed with exact metadata preimages; paid lesson catalogue entries remained separate. Public/lesson picker titles are decoded for plain-text React labels.

The client guide now describes the applied editing account and actual Highlights workflow, using restricted-account Content Hub/upload/picker screenshots from the live backend. Private sources and originals remain outside public Git; reader credentials are preserved. The old broader opt-in runtime harness was not enabled and held Review services were not resumed. Payment/sign-up/indexing switches, real-content/client training, long-file/network endurance and #87's signed-link/DRM decisions retain their own gates.
