# Desktop prototype parity audit — 2026-11-01

Owner confirmed audit, fixes and deployment. Desktop A/light and D/dark are in scope;
mobile/responsive adaptation is deferred to a second pass because the prototype itself
needs work there. Approved WordPress-editable content and settled product changes are
exceptions, not permission for template geometry, effects, colours or typography to drift.

Reference: the current published GitHub Pages prototype, with source checked against the
repository HTML/CSS/JS. Baseline source head: `5cf1ea5`. Evidence is recorded privately at
`~/cas-evidence/desktop-parity-audit-20261101/`. Animation comparisons use ordinary Chromium
UA and explicitly neutralise the prototype's headless/webdriver motion gate; reduced motion
will be checked separately. This is browser evidence, not human/device acceptance.

## Working findings ledger

| ID | Family | Finding | State |
|---|---|---|---|
| D01 | Shared header | Prototype Courses disclosure/category drawer absent in WordPress | Confirmed missing implementation; source fix in progress |
| D02 | Home | Desktop hero/section inner width 1392 instead of prototype 1168 at viewport 1440 | Confirmed geometry mismatch |
| D03 | Home | Hero misses prototype's first-section 32px top spacing | Confirmed geometry mismatch |
| D04 | Home motion | Working prototype title/testimonial entrances absent; generic rises differ | Source fix added; dormant prototype card scenes are a separate reference bug |
| D05 | Home gallery | Promo remains 300px square; prototype uses first course card × 0.66 (363px in baseline) | Confirmed missing implementation |
| D07 | Shared typography | Hello heading line-height 1.2 overrides prototype inherited 1.6 (release sign 60px vs 72px; footer -4.8px) | Desktop heading normalisation added |
| D06 | Catalogue navigation | Category deep-link application absent from course-filter script | Confirmed source gap; bind drawer to native catalogue controls |

| D08 | Login | Tutor ID/placeholder styles override the prototype field palette, type size and row spacing | Source adapter corrected; native handlers/nonces preserved |
| D09 | Shared controls | Parent button line-height and radius alter filter pills and Polaroid captions/corners | Desktop baseline corrected |
| D10 | FAQ | Extra contact section, different heading gap and missing final section padding | Removed template duplication; kept maintained answers and shared email values |
| D11 | Lesson | Prototype's known teacher fact omitted from metadata strip | Restored fixed brand attribution without a new field |
| D12 | Header cart | Shopping-cart icon points at payment instead of the native basket | Corrected through existing Tutor cart URL resolver |

| D13 | Basket | Empty native basket loses the prototype's two-column empty state/order summary | Desktop empty-state override added; native populated renderer and all pricing remain intact |
| D14 | Basket | Populated native basket typography/layout differ from the prototype; old review had left this out of scope | Desktop presentation adapter added; populated native state remains unverified |

| D15 | Signed-in header | Real session suppresses login but has no prototype logout/member chip | Native session-backed desktop chrome added; no fake localStorage auth |
| D16 | Dashboard rail | Missing prototype icons/course count; extra rail heading/arrows | Presentation restored; Profile remains the approved Discord replacement |
| D17 | Dashboard cards | Images lack fixed cover/resume frames; headings inherit body font | Restored prototype containers/type; real course metadata retained |
| D18 | Empty dashboard | Missing onboarding panel structure; fake universal preview/material promises must not ship | Matching component structure added with truthful existing-feature guidance |

| D19 | Testimonials | Backend explanation about member records appears inside public quote cards | Removed developer-facing sentence; quotes/member data untouched |

| D20 | Showcase | Resting desktop wall uses fixed hash pinning while the reference re-pins each visit | Restored reference jitter ranges/pair/pin choices in browser; SSR/mobile fallback retained |

| D21 | Dashboard empty recommendations | Header/empty message disappear when the reader owns the available catalogue | Restored explicit reference empty state without fabricating recommendations |

## Exceptions and boundaries

- Keep individual video-course purchases, Tutor native commerce, hosted payment, closed signup,
  no membership/Discord/Q&A/assignment features, removed Wishlist and already-accepted dashboard
  adaptations. No live payments or incident-held operations.
- Keep the owner-requested full-width course introduction; this is a later explicit visual
  direction. Main catalogue uses the same 1440px container as its prototype.
- Keep lesson-owned description/cover per the written design correction, not the obsolete
  prototype course-blurb comment. Actual content, supplied media, lists, prices and credits
  differ only within their approved authoring boundary.
- Do not invent missing content, Etsy destinations or legal text. Prototype sample-data warnings
  and membership/community CTAs are not shipped client content. Record their effects on total
  page height instead of padding with fake sections.
- Enrolled lesson GET can mutate completion through Tutor; do not open it in a real production
  learner session. Inaccessible protected states stay explicitly unverified. Do not restore/reset
  shared admin credentials, touch held fixture data or execute provider wrappers.
- Archives/search/404 have no dedicated prototype page; compare their shared design system and
  label the missing page-specific visual reference.

This is an evolving audit ledger, not a declaration of 1:1 acceptance. Last saved weighted
acceptance coverage stays **63/110**. No new percentage is inferred from this audit.

## Reference animation finding

The published prototype's Home script uses `#content > section` indexes as though the hero
were not a section. Its release/shop/share scenes therefore target the preceding section
and do not register. Normal-UA browser traces confirm those card targets have no tweens;
the title and testimonial scenes do run. This pass ports the working effects and removes
non-reference generic rises; it does not invent the dormant card choreography. Original
reference HTML and motion traces are retained privately.

## Isolated-fixture boundary

A separate local fixture attempt stopped at the existing course/lesson assignment guard.
The process returned exit 0 with WordPress error HTML; it is explicitly **not** recorded as
success. No guard bypass, credential reset or retry of the denied assignment was attempted.
The partial owned fixture ledger and original stdout/stderr are preserved. Public audit and
native read-only header rendering continued independently; populated protected states are
not inferred from this partial fixture.

Fresh read-only observation is kept separately from the incomplete local fixture ledger. A
native free-enrolment attempt returned an unconfirmed readback, not proof of no write. A
later browser observation found no recommendation rows; the subsequent bounded read records
the actual owned fixture rows/enrolment/course list. No enrolment retry or cleanup was used
to force an expected result.
