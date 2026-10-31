# Public interaction colours — 2026-11-01

Owner request: replicate the prototype's hover/focus effects across the website in both
light and dark modes, correcting the pink Showcase frame visible on hover.

## Cause and correction

Hello Elementor's `reset.css` supplied `#c36` button text/borders and hover/focus fills,
and `#336` active/hover links. Its state selectors outranked normal prototype component
selectors. This was shared frontend behaviour, not a Showcase data or colour-field issue.

The parent reset now loads through its existing WordPress handle in a low-priority CSS
layer. Browser normalisation remains; its decorative button/link defaults are neutralised
inside that layer. Unlayered prototype component styles own the visible colours and states.
The existing industrial stylesheet depends on that handle, retaining deterministic loading
and dependencies used by Elementor. No extra stylesheet network request or library was added.

Tutor's more-specific default focus selectors also overrode the prototype keyboard ring.
Its enabled `.tutor-btn` controls now join the existing shared focus rule with matching
specificity. The same light outline/dark outer band is used, without blanket `!important`
overrides or removing focus indication. Existing component animations/materials are retained.

Scope: `functions.php` and one selector in `css/industrial-3.css`. Public site-wide styling;
WordPress admin, guide, content, images, user roles/sessions and provider settings are unchanged.
No new fields or data writes. Acceptance coverage remains **63/110**, not a new build score.

## Verification and publication

- Actual prototype and live browser preview compared using valid **A** and **D** modes:
  Showcase paper background remains `rgb(246, 241, 230)` at rest, hover and keyboard focus;
  the focus outline matches the prototype's 3px light ring.
- Native independent WordPress run confirmed the parent reset is imported once in its
  layer, Elementor frontend CSS remains loaded, and card/filter hover/focus work in A/D.
- Tutor login focus mismatch found during scoped checking and corrected before release.
- Two-file guarded deployment passed PHP lint, exact preimage checks, private backups and
  postimage readback. Original file ownership/modes were preserved. No WordPress bootstrap
  was used for the file preflight/publication.
- Published **canonical URLs**, without bypass query strings: Showcase, Home, Courses,
  Gallery, Blog and Login. Hover/keyboard-focus checks passed in both modes; Showcase mobile
  viewport colour also passed. Screenshots captured in both modes. No login was submitted.
- Guide was neither built nor published; all authentication/draft constraints remain intact.

Private original inspections, failures, final browser results and deployment receipts are
in `~/cas-evidence/site-highlights-20261101/`. Animation timing is not treated as an exact
shadow-pixel comparison. Full authenticated/payment/lesson journeys and human/device acceptance
were not rerun; incident-held operations remain stopped. Own isolated test containers were
stopped with their volumes retained. GitHub #105's earlier connector write denial remains;
this file and HANDOFF.md hold the local Wayfinder checkpoint.
