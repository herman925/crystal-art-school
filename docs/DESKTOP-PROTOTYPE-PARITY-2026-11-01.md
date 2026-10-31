# Desktop prototype parity audit — 2026-11-01

Owner confirmed audit, fixes and deployment. Desktop A/light and D/dark are in scope;
mobile/responsive adaptation is deferred to a second pass because the prototype itself
needs work there. Approved WordPress-editable content and settled product changes are
exceptions, not permission for template geometry, effects, colours or typography to drift.

Reference: the current published GitHub Pages prototype, with source checked against the
repository HTML/CSS/JS. Initial working source head: `5cf1ea5`; newer already-deployed course/CMS work was
reconciled from main `e07be48` before publication. The merge preserves that work and
deployment scope contains only this audit’s 21 theme files. Browser baselines describe
the actual observed deployment, not an assumption that its whole tree was the old head. Evidence is recorded privately at
`~/cas-evidence/desktop-parity-audit-20261101/`. Animation comparisons use ordinary Chromium
UA and explicitly neutralise the prototype's headless/webdriver motion gate; reduced motion
will be checked separately. This is browser evidence, not human/device acceptance.

## Delivered findings

“Public observed” means Chromium on the deployed site, A/light and D/dark. “Local only”
means the separate owned fixture; it does not certify a production account or purchase.
All rows below are implementation delivery, not additions to the acceptance score.

| ID | Surface | Correction | Verification boundary |
|---|---|---|---|
| D01 | Header | Courses disclosure restored with native category links | Public observed; keyboard follow-up D22 |
| D02 | Home | Inner desktop width restored from 1392px to 1168px at 1440 | Public/reference geometry matches at both desktop widths |
| D03 | Home | First-section 32px top spacing restored | Public/reference hero position matches |
| D04 | Home motion | Working title/testimonial entrances ported; non-reference generic rises removed | Titles observed; testimonial scene remains source-only while no quotes are published |
| D05 | Home Gallery card | Height follows first course card × 0.66 instead of fixed square | Public rendered; same reference sizing rule |
| D06 | Catalogue | Drawer category query applies to existing filter controls | Public Watercolour deep link and Clear observed |
| D07 | Typography | Desktop Hello heading line-height leak removed | Public heading/footer values match reference |
| D08 | Login | Native Tutor field palette/type/spacing corrected | Public field/focus rendering; no credentials submitted |
| D09 | Controls | Parent button line-height/radius leak removed | Public controls/dialogs observed; previous site-wide colour repair retained |
| D10 | FAQ | Duplicate contact section removed; reference heading gap and padding restored | Public accordion open/animated close observed |
| D11 | Lesson | Known teacher attribution restored | Public preview observed; enrolled production state unverified |
| D12 | Header cart | Icon uses native basket resolver | Resolved basket response observed; plain query permalink is valid |
| D13 | Empty basket | Reference two-column empty state and zero summary restored | Public both modes; Continue disabled |
| D14 | Populated basket | Presentation adapter added around native renderer | Built; native populated production state unverified |
| D15 | Signed-in header | Native-session logout/member chip restored | Local only; no production session opened |
| D16 | Dashboard rail | Icons/course count restored; extra rail furniture removed | Six panels, both modes, local only |
| D17 | Dashboard cards | Cover/resume frames, headings, native metadata and recommendation styling restored | Local observed enrolled fixture, 0/1 complete |
| D18 | Empty studio | Onboarding structure restored with truthful course guidance | Built; initial empty local shell and later enrolled state are separately recorded |
| D19 | Testimonials | Member-record implementation explanation removed from public quote markup | Source checked; no quotes currently available for production browser acceptance |
| D20 | Showcase | Per-visit desktop pinning/jitter ranges match prototype | Public pinning, scroll response, filters and dialog observed |
| D21 | Recommendations | Explicit empty heading/message retained when catalogue already owned | Local observed; no recommendations fabricated |
| D22 | Header keyboard | Opening visibility is immediate before arrow-key focus | Browser candidate and subsequent live A/D checks; closing/fade timings preserved |

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

This is a delivered desktop audit/fix record, not a declaration that every protected state is 1:1. Last saved weighted
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

## Current source reconciliation

While the audit ran, main acquired course/template/CMS follow-ups and restricted-file fixes.
The audit candidate was committed independently and merged with `e07be48`; the only shared
JavaScript change (multi-medium Showcase matching) merged cleanly and was retained alongside
the new desktop pinning. No other worker's files were reset or replaced. The deployment
manifest excludes their unrelated files, including private-file/builder follow-ups.

The native free-enrolment verification returned an unconfirmed cached readback, but later
fresh observation found enrolment 61, lesson 60 and course 58 in the independent fixture:
reader 3 is enrolled at 0/1 complete. The later six-panel browser pass therefore observed
that state. It is not relabelled as an empty-studio run. No assignment/enrolment retry,
provider call, cleanup or progress/completion action was used to force the result.

## Desktop route coverage

Production and the published reference were inspected at **1440×1000 and 1920×1000**,
in both A/light and D/dark. Baseline: 60 paired captures at 1440, plus 16 additional
public captures and separate lesson/essay/dashboard/checkout-reference observations.
Final: 136 page captures and 28 supplemental captures. Four final essay captures used
an incorrect assumed permalink and returned 404; they are retained as failed route
selection, **not passes**. The real existing post was then checked at its observed
`/website-test-post-placeholder/` permalink in all four desktop/mode combinations.
The lesson's correct reference query is `?c=clouds-beginner&u=1`; supplementary captures
replace the earlier incorrectly named query parameters. Raw originals remain intact.

| Family | Observed production surface | Result / limit |
|---|---|---|
| Shared header/footer | All inspected public routes | Wood/palette/type/controls reviewed; desktop drawer and mode toggle exercised |
| Home | `/` | Hero/image/section geometry matches reference; content-fed rails legitimately differ |
| Catalogue | `/courses/` and Watercolour deep link | Public rendering, filtering and Clear observed; full-width introduction is the later owner direction |
| Course detail | Beginner, Intermediate, Urban Sketching, Little House, published Demo studio | Public source/reference comparison; earlier course/CMS work retained; no purchase action |
| Lesson | Beginner first public preview | Public page and teacher metadata reviewed; private/enrolled/completion states not certified |
| Gallery | `/gallery/`, 12 artworks | Layout, prices, filter/sort controls and quick-view reviewed; native artwork content differs |
| Showcase | `/showcase/`, 10 labelled samples | Board, two media on first sample, filters, scroll physics and lightbox observed |
| Blog | `/blog/`, native topic chips and posts | Featured/grid layout and topic switching observed; sample authoring/content retained |
| Post | Demo Tools article and existing placeholder post | Shared article/byline/related components reviewed; short supplied content has no multi-section TOC |
| About | `/about/`, Terms/privacy anchors | Photo-carousel navigation and certificate lightbox observed; legal/content authoring boundaries retained |
| Free Videos | `/free-videos/` | Rails/cards/player dialog open/close observed; player iframe clears on close; no new upload/provider operation |
| FAQ | `/faq/` | Reference furniture and maintained answers; accordion exercised through animation completion |
| Login | `/login/` | Native fields/focus and closed-signup direction reviewed; no real sign-in/reset request |
| Recovery | Core lost-password URL → Tutor recovery page | Request form renders; token/expired/reused/delivery outcomes remain unverified |
| Registration | `/student-registration/` | Closed registration state reviewed; reference signup form deliberately not enabled |
| Dashboard gate | `/dashboard/` signed out | Login gate observed; this is not a production six-panel studio pass |
| Basket | `/cart/` and native page-ID URL | Empty summary observed; populated native state remains unverified |
| Checkout | `/checkout/` signed out/empty | Actual gate/redirect observed; prototype Details stage captured separately using prototype-only sample data |
| Archives | Existing category, tag, author, October 2026, search and no-result search | Shared design components render; no dedicated prototype page exists for exact page-level comparison |
| Not found | Deliberately nonexistent route | Styled page with expected HTTP 404 in both modes/widths |

The existing six-panel studio was also checked in both modes on the separate local
WordPress fixture, including native input styles, real-session member chrome and the
observed 0/1 enrolled course. It lacks Tutor Pro fidelity and is not production acceptance.

## Verification and failures retained

The target flow is public page → existing navigation/filter/disclosure/dialog control →
correct rendered state, preserving native account/commerce handlers. Browser plugin was
not available; the already installed Playwright/Chrome setup was reused without installing
dependencies. Screenshots, DOM geometry, status/identity and uncaught-page-error observations
are private. No uncaught page errors were recorded in the final page and supplemental
captures. The final console observation did record `favicon.ico` 404 and cart-count AJAX 503 responses.
The latter returns `success:false, known:false`, matching the existing server guard when a
native guest cart is unavailable. The header hides the unknown badge rather than displaying
an invented zero. This is not a clean-console claim or a populated-cart pass; no hosting,
commerce setting or provider configuration was changed. The direct actionless AJAX GET
returned the normal 400/`0`, so the 503 observations are not labelled a general server outage.
A comprehensive network/console or multi-browser audit is not claimed.

Interaction evidence covers mode switching; Courses hover, arrow navigation and Escape;
Home previous/next, single active/inert slides, pause and six-second autoplay; category
filter/Clear; Gallery dialog/focus return/ascending prices; Showcase medium matching,
lightbox and scroll response; Blog topics; About photo dots/certificate dialog; Free Videos
dialog cleanup; FAQ open/close; empty basket/disabled Continue; login focus. Reduced-motion
Home keeps content visible and disables autoplay. No form, profile, password, progress,
completion, order or payment was submitted in production.

Several first interaction assertions were wrong: the native basket uses a valid page-ID
permalink, the hero height is fractional (489.265625px), a single-medium catalogue has no
redundant medium group, the article wrapper is `.article`, and FAQ closing takes 280ms.
Those original failures were kept, corrected narrowly, and re-observed. The menu focus
failure was real: transitioning visibility could reject the first focus call. Its CSS
opening transition was fixed and separately verified. A screenshot's apparently missing
course-footer image was lazy loading; a later direct observation found no broken images
on that course. It did not justify replacing the client's image or changing source.

Source checks: the Home/login/checkout/catalogue/lesson contracts, lesson-material guard,
course-introduction behaviour, four lesson renderer cases, eight cart-count cases, content
contracts (45 pass / 4 skipped), PHP/JS syntax and whitespace checks passed. The new empty-cart
fixture passes four synthetic cases (empty/nonempty/unknown/count mismatch), rendering the
vendor template once; **native populated checkout acceptance remains false**. Earlier
unittest discovery of script-only checks ran zero tests; their direct script executions,
not that zero-test discovery, supply the contract evidence.

## Release record

The candidate `5553192` was merged with main `e07be48` in `774443d` before the release.
All **21 scoped runtime files** matched their expected server preimages. Remote CLI lint
passed all 10 PHP files without bootstrapping WordPress; every preimage was backed up before
the first replacement. All 21 deployed bytes were read back successfully. D22 then changed
only `css/cas-nav.css`, with its own exact preimage/backup/readback receipt. Its opening
visibility is immediate; the visual opacity/transform transition remains 200ms.

Private server backup: `~/backups/desktop-parity-20261101/`. Deployment inputs/receipts:
`~/deploy-cas-desktop-parity-20261101/`, including `keyboard-followup/` for D22. Local originals:
`remote-preflight-merged.*`, `remote-apply-merged.*`, `keyboard-followup.*`, `postimages.json`,
`keyboard-followup/hashes.json`. Do not publish raw logs or reuse a completed apply helper.
No guide build, guide authentication change, database/content migration, broad upload,
provider execution, production account operation or held-runtime execution formed this release.

## Remaining work and decisions

| Classification | Item | Smallest next step |
|---|---|---|
| Deferred by owner | Mobile/tablet adaptation and prototype defects there | Separate responsive pass after desktop review |
| Built but unverified | Populated basket, checkout details, order receipt/success, enrolled lesson/progress/completion and production studio | One separately bounded native journey using an approved account/payment fixture |
| Built but content-blocked | Testimonial entrance, multi-heading post TOC, missing course photographs/materials | Review once actual eligible content is supplied; do not invent filler |
| Owner/content cleanup | Published placeholder post and clearly labelled demo records | Decide what to retain/replace before launch; this audit did not remove them |
| Reference defect | Dormant Home card animation scenes | Decide whether to repair the prototype and port that new behaviour later |
| Not an approved visual exception yet | Checkout discount/hosted gateway field differences historically marked “owner to confirm”; remaining finish/restart lesson scenes | Inspect the real gated state and record the exact decision; do not infer acceptance from old receipts |
| Existing operational gates | Recovery outcomes, human AT, client devices, email delivery, legal/prices, safe retention/core repair, role handover and launch switches | Keep their original Wayfinder children open; this visual release does not close them |

WordPress-owned copy, photos, item order/count, prices, media and topic values are allowed
to differ. Absent membership/Discord/guest login/Wishlist and closed signup follow later
product decisions. No invented Etsy fallback/listing or legal consent text is added merely
to imitate sample content. These exceptions do not excuse structural/design drift.
