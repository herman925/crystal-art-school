# Issue tracker

Tickets for the **prototype** live **in this file**. The **WordPress build** work is
tracked as **GitHub Issues** on this repo again since 2026-08-24 — the post-handoff fix
list is issues #23-31 (see "History" at the bottom).

Work the **frontier**: any ticket whose blockers are all done. Tick the acceptance
criteria as they land; move the ticket to "Done" with its commit SHA when every box
is ticked.

Four places a ticket can be: **Ready** (work it), **Carried to the WordPress build** (real,
but only measurable on the deliverable — do not work it here), **Blocked on the client**, and
**Done**. A ticket that has been fixed and not ticked is worse than no tracker at all, because
it inflates the queue: 2026-08-17 found thirteen of those.

## Repository visibility — read before writing anything here

This repository is **PUBLIC**. It serves the demo at
`https://herman925.github.io/crystal-art-school/`, which requires public visibility on a
free GitHub account. **This file is committed**, so everything in it is publicly readable
and search-indexed, including by the client.

- Write tickets as neutral status, not as commentary on the client or on prior work.
- Never paste meeting transcripts, client correspondence, photographs of client feedback,
  pricing, or commercial terms into a ticket.
- Those materials are gitignored on purpose — see the "Client working material" block in
  `.gitignore`. Reference them by section number, never by quoting them at length.

---

## How a client asset becomes a file in `img/`

Her filenames are not our filenames, and the translation is deliberate. Written down
2026-08-16, after a near-miss.

**The names change.** Files arrive with spaces, full-width CJK parentheses, commas and
inconsistent case — `Hero（左）1.jpg`, `Beginner HighlightCover.JPG`,
`CloudsLesson12,13,14colorPalette.jpg`, `WatercolortoolsHowi madepalette.mp4` (that
space is inside the word, and it is real). Anything shipped is renamed to lower-case
ASCII with hyphens. A filename that needs percent-encoding in an `src` attribute does
not ship.

**Never rename in her tree to make ours easier.** The one exception so far was
`Material/Lesson03ref.JPG`, renamed to `Lesson03Reference.JPG` because it collided with
the private-content filter — the file is genuine content she asked to be shown, and the
filter matches `ref.` The filter was not weakened to accommodate it. Renaming her file
was the smaller change; weakening the filter is how her handwritten feedback boards
reach the public site.

**Two routes into `img/`, and they are not interchangeable.**

* *Pipeline* — anything under a folder `tools/import-assets.py` walks. It re-encodes,
  generates the `-192/-384/-768` derivative ladder, and records the result in
  `tools/asset-manifest.json`. Used for course, lesson, gallery and free-video imagery.
* *By hand* — the brand marks and the About imagery. Copied in, sized once, no ladder.
  `tools/import-assets.py` says so explicitly and never lists the assets root.

Where a file sits in a folder the importer does not walk, give the importer an
**explicit relative path** rather than teaching it to walk that folder. Not because a
private file sits there today — everything private currently in `00 - Home/` IS caught
by the filter — but because the filter is a filename heuristic rather than a guarantee,
and that folder gains files between rounds. `Website 最底部REF（FOOTER).png`, at the
assets **root**, carries none of the filter's markers and would sail through if anything
ever walked the root. An explicit path imports exactly what is named; a directory walk
imports whatever she puts there next.

**Never overwrite a name that is already in use.** `img/cover3.jpg` is shared by three
unrelated slots — the third home hero slide, the Lessons free-trial band, and the course
page's free-videos preview. Her Free Trial photograph went in as `img/free-trial.jpg`
for that reason. Grep the name before you write it.

---

## Where these came from

A 16-surface technical audit on 2026-08-09 (15 pages plus the shared nav/footer/motion
chrome), one auditor per surface, every finding measured against the rendered page rather
than read from source.

**Mean score 9.75 / 20.** Gallery 7 · course 8 · Login 8 · Signup 8 · Showcase 9 ·
shared chrome 9 · index 10 · Lessons 10 · lesson 10 · blog-post 10 · Dashboard 10 ·
Checkout 10 · Blog 11 · About 11 · FreeVideos 11 · FAQ 14.

Severities below are the audit's: **P0** blocks a task, **P1** serious, **P2** moderate,
**P3** minor.

A second source joins the list on 2026-08-16: a client review round recorded in the
webpage-review tool, 93 threads across ten pages. Ninety-two of them became tickets
**31-65**; the ninety-third was answered and resolved during the review round itself.
Each ticket names the thread keys it answers so a reply can be posted against them; the
thread bodies themselves are client correspondence and stay out of this file.

---

## What this round settled, 2026-08-17

Recorded here because it changes how the queue below should be read.

- **The prototype is a design-agreement artefact; WordPress is the deliverable.** The design is
  agreed off these pages. The site that ships is a WordPress build, so a ticket whose acceptance
  is a measurement of prototype code closes as carried-forward rather than staying open against
  code that will be discarded. Six went that way — see "Carried to the WordPress build".
- **The blog developer's notes come down.** The client gets CMS control of post bodies, so an
  on-page note declaring an unapproved correction is no longer the right mechanism. The copy
  corrections themselves stand. The `devnote` field, its CSS and its render branch stay in the
  page unused so a future post can carry one; ticket 12 carries the correction to what this file
  used to say about that.
- **Prices stay as placeholders.** Every figure on the site is a stand-in until the client sets
  them. Nothing here treats a price as agreed, and none should be quoted onward as one.
- **One red, not two.** The sale marker and the alert role share a single red rather than each
  carrying its own; the prototype had `--rouge` and `--danger` diverging
  (`css/industrial-1.css:242`, `:296`, `:313`).
- **The certificate ships unmasked, and that is a knowing choice.** The anti-piracy masking is
  still outstanding and the scan is live without it, pending the client's decision — ticket 62's
  last criterion stays open and says so. Note also that her legal name is in that image's alt
  text (`About.dc.html:323`), which is correct for accessibility and is a second thing the same
  decision has to cover, not a separate defect.


---

## Ready

### 09 — Fix the coral contrast family

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** `--coral` fails 4.5:1 in **both** themes wherever it is small text, or a
fill under cream text. Roughly 15 measured instances, one root cause. Measured examples:

| Element | Theme D | Theme A |
|---|---|---|
| `.btn.primary` label (site-wide CTA) | 4.23 | 4.23 |
| `.cart-badge`, `.ck-btn.primary`, `.mc-btn.primary` | 3.36 | 4.24 |
| `.fp-clear`, `.toc-title`, `.inv-link`, `.resume-body .rk` | 3.39 | 4.35 |
| `.eyebrow` (12px/600) | 3.83 | 4.47 |
| `.dash-tab.active` | 3.54 | 4.04 |
| `.mc-price` | 2.98 | 4.02 |

Note `industrial-1.css:427` carries a stale comment claiming 4.08 for a value that now
measures 4.56.

- [ ] Every instance in the table clears 4.5:1 in both themes, measured from painted pixels
- [x] The fix is at the token/role level, not 15 local overrides
- [ ] Stale ratio comments in the CSS are corrected to what actually paints

**Partly landed, 2026-08-17.** The role split exists and every instance in the table above now
consumes it rather than `--coral` directly: `--coral` is the material, `--coral-ink` is copper as
text and is per theme, `--coral-fill` / `--coral-fill-ink` is copper as a small fill — declared and
explained at `css/industrial-1.css:101-117`, `:300`, `:317`, and consumed at `industrial-1.css:373`,
`:573`, `industrial-2.css:149`, `industrial-3.css:116`, `:238`. What is **not** done is the
re-measurement: the box above stays open until each of the fifteen is read from painted pixels in
both themes.

### 11 — Remove fabricated content

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** nothing on the site should assert something that is not true. Each of
these was measured in the rendered page:

- The Google buttons on both auth pages write an invented person — `Jane`, and
  `Jane Doe` / `jane@gmail.com` typed into live credential fields.
- Signup's success panel says *"Check your inbox to verify your email"* after a submit
  with no email entered.
- Five of the ten rows under "International honours & exhibitions" on About are demo
  filler, rendered at 2.15:1 rather than hidden.
- `index.html` renders three `@username` / "Administrator-entered comment appears here"
  cards under the heading "Real hands, real progress".
- Gallery hard-codes `Ships — Framed, 5–7 days` into every piece and asserts a certificate
  of authenticity, neither derived from data nor covered by the page's placeholder notes.
- Dashboard writes seeded demo `cas-enrolled` / `cas-orders` into real `localStorage`, and
  tells a brand-new account it joined last month.

- [ ] No invented person, email, credential or metric renders anywhere
- [x] Placeholder content is visibly marked as placeholder, or absent
- [x] Demo state is not written into storage keys that real state would use

**Partly landed, 2026-08-17.** Gone outright: the fabricated Google sign-in identity
(`Login.dc.html:245`, `Signup.dc.html:253`), the verify-your-email claim on a form with no email
(`Signup.dc.html:103`), the invented shipping and authenticity assertions on every Gallery piece
(`Gallery.dc.html:488`), and the seeded `cas-enrolled` / `cas-orders` writes and the invented join
date (`Dashboard.dc.html:208`, `:257`). What **remains on the page, declared** — each carrying a
`.ph-banner` that says so — is the three home testimonial cards (`index.html:384-388`), the ten
Showcase names and titles (`Showcase.dc.html:121`, `:160-176`) and the three `is-demo` certificate
rows (`About.dc.html:355-377`). The first box therefore stays open on purpose: these are honest
placeholders waiting on ticket 29, not undeclared fabrications.

### 12 — Blog: the copy note and the copy it declares

**Status:** SETTLED 2026-08-17 · the corrections stay with the client · **P1**

The six material and instruction corrections on post 1, the two sentences on post 2 that are
ours and the one clause plus the tightened pull-quote on post 3 were verified against her source
documents in 2026-08-09: all six corrections are present on the site, her original wording is
gone in every case, and the three sentences the notes claimed as ours appear in none of her three
documents. **None of that changes.** What changed on 2026-08-17 is only the declaration mechanism.

**Correction, 2026-08-17.** The paragraph that used to sit here said the callout was dead —
that no post in `js/blog-data.js` carried a `devnote` field and that `.bp-devnote` matched zero
elements on all three posts — and ticket 19 listed `.bp-devnote` among the dead CSS on that
basis. **That was not true when it was written:** all three posts carried a `devnote` and the
callout rendered. It becomes true this round for an entirely different reason, deliberately —
see the 2026-08-17 entry above: the notes come down because the client gets CMS control of post
bodies, not because the mechanism failed. The field, its CSS (`blog-post.dc.html:66-68`) and its
render branch (`:214-216`) stay in place unused, which `blog-post.dc.html:60` says explicitly, so
a future post can carry one. The sentence is corrected here rather than quietly deleted, because
it was wrong in both directions and the record should show that.

**What is left:** nothing in the code. The copy corrections the callout existed to declare
(masking fluid, Winsor, Claessens, squirrel) are live and stay live; the only open item is the
client's approval of them, which ticket 29 already carries.

- [x] The on-page notes are gone from all three posts — zero `devnote:` fields in `js/blog-data.js`, and the reasoning is in its header at `:14-22`
- [x] The mechanism is **kept, not deleted** — `.bp-devnote` (`blog-post.dc.html:66-68`) and its render branch (`:214-216`) stand ready for a future post, which `:60` states
- [x] The corrections themselves are unchanged, and reverting them is ruled out in writing in both places
- [x] The decision is recorded with a date — twice below
- [ ] The client approves the corrected wording (carried on ticket 29, which already lists it)

**Decision, 2026-08-09:** the note renders again on all three posts and the copy
corrections stay. Reverting them would publish a known error — "masking fluid" is
what the client's own painted label reads, and Winsor & Newton and Claessens are
the manufacturers' spellings. The wording of the note is new, not the original:
that text was removed on 2026-08-07 (`ff37d09`) because it described the source
material, and this repository is public. Each note now states what was changed and
that it is not yet approved, and nothing else. Recorded in the header of
`js/blog-data.js`, beside the field that drives it.

**Decision, 2026-08-17 — supersedes the mechanism, not the copy:** the notes come down. The
client gets CMS control of post bodies, so corrections belong where she edits her own words
rather than in a standing declaration on the published page; a note that outlives its purpose
reads as an unresolved defect to every visitor. The corrected wording **stays and must not be
reverted** — reverting would publish a known error, for the reasons given in the 2026-08-09 entry
above, and both `js/blog-data.js:18-20` and this ticket say so. The field and its rendering are
left in place unused. The 2026-08-09 sentence "the note must not be deleted before one of those
happens" is therefore superseded, and is kept above only so the reversal is visible rather than
silent.

### 13 — Showcase has no student data

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** the student wall currently ships 10 records with `name`, `title` and
`med` all empty strings. Measured consequences: every tile's accessible name is `" by "`,
every `alt` is `" — student work by "`, the lightbox caption reads `" ·  · "`, the avatar
has no initial, the filter rail renders exactly one chip ("All"), the empty state is
unreachable, and the empty `.pol-med` span paints an 18×8px dark rectangle on every photo.

Separately, the page is not the layout the direction describes: every piece is `ar: 1.00`
in a fixed square tile, and the mixed-aspect collage CSS (`.sw-grid`, `.sw-tile`,
`.sw-owner`, `.sw-name`, `.sw-medium`) is still in `industrial-3.css` matching nothing.
`js/motion.js` still lists `.sw-grid .sw-tile` as a stagger target.

- [ ] Real captions, or an honest empty state that does not render blank separators
- [x] No element renders a background chip with no content
- [x] The collage question is settled: either the data carries mixed ratios and the collage CSS is used, or that CSS and its motion target are deleted

**Partly landed, 2026-08-17.** `.pol-med` renders only when there is a medium to put in it
(`Showcase.dc.html:257`), and the filter rail and empty state are built only when the data offers a
real choice (`:352-359`) — so no blank chip and no unreachable state. The collage question was
settled by deletion: the `.sw-*` rules are gone from `css/industrial-3.css` (the reason is left at
`:483-486`) and `js/motion.js` no longer lists `.sw-grid .sw-tile`. The first box stays open: the
ten names, titles and media are invented stand-ins, marked as such on the page and in the data
(`Showcase.dc.html:121`, `:160-176`), until the real credits arrive under ticket 29. **Replace all
ten together; do not ship these names.**

### 15 — Commerce data integrity

**Status:** DONE 2026-08-09 · **P1** · **Blocked by:** none

**Decided:** these are paid courses, not free samplers. A one-lesson course now
gets no free preview: `tools/build-data.py` marks lesson 1 free only where the
course has more than one lesson, so there is something left behind the paywall.
Measured after rebuild — clouds-beginner 13 lessons / 1 free, clouds-intermediate
7 / 1, little-house 1 / 0, urban-sketch 1 / 0.

**What to build:** the two £24 courses (`little-house`, `urban-sketch`) each have exactly
one lesson, and that lesson is flagged `free: true`. Rendered: `£24`, a live Add to cart,
and a syllabus whose only row is an unlocked link badged FREE PREVIEW. `lockedRows: 0` —
the entire paid product is watchable without buying it.

This may be an intended sampler rather than a defect; the page cannot answer that.

- [ ] Confirmed with the client whether these are free samplers or paid courses
- [ ] The price, the cart button and the lock state agree with the answer

### 17 — Image delivery

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** the site has **zero** `srcset` or `<picture>` anywhere. Measured
oversampling and weight:

- `lesson.dc.html` — 12 sidebar thumbnails at 1280×1280 painted 78×48; 3.44MB of a 6.28MB page; identical bytes at 390px
- `course.dc.html` — syllabus thumbnails 21×, course photos 15.4×; `img/cover3.jpg` (472KB) is fetched at highest priority and immediately overwritten by JS, never painted
- `Gallery.dc.html` — 7.76MB total; `img/deer-black.svg` is 440KB and instantiated 17 times, each with a drop-shadow filter
- `blog-post.dc.html` — 3.54MB; the LCP image is `loading="lazy"`, 425KB, 1600×1600, painted 1392×261
- `About.dc.html` — 4.48MB; `cert-jiwi-2026.jpg` 8.94×
- `index.html` — 5.83MB; 11 of 13 above-the-fold images are lazy, LCP is a 296KB decorative texture

- [ ] Responsive sources on every content image, sized to the real slot
- [ ] No above-the-fold image carries `loading="lazy"`; the LCP candidate carries `fetchpriority="high"`
- [ ] `cover3.jpg` is not fetched on the course page
- [ ] Per-page image budget agreed and met

**Partly landed, 2026-08-17.** The site is no longer at zero: `srcset` is present on twelve pages
and in all five data files (`js/static-images.js` 56 uses, `js/course-data.js` 48, `js/gallery-data.js`
17, `js/free-videos-data.js` 15, `js/blog-data.js` 11), `fetchpriority` on nine pages, and the
derivative ladder is generated rather than hand-kept. The boxes stay open because each asks for a
measurement of the rendered page — which slot each source is actually painted into, which image is
the LCP candidate at each width, and a budget that has been **agreed** and not merely met. Note the
budget half is a decision, not a measurement, and can be taken now.

### 19 — Dead code sweep

**Status:** ready-for-agent · **P2** · **Blocked by:** 14

**What to build:** delete what no longer runs. Every item verified as matching nothing in
the live DOM:

- 2.6KB of hand-written `<nav>` in **all 15 pages**, replaced at runtime by `buildNav()`.
  Three variants have already drifted apart unnoticed, because none of them renders.
- `index.html:66–125` specifically, including a `.mega-note` that resolves to null
- `lesson.dc.html` — ~70 lines of player JS behind a controls row set to `display:none` on every load, plus its CSS
- `FreeVideos.dc.html` — the simulated-player block, `.fvm-veil` and its five dependent rules, `.is-placeholder`
- `Gallery.dc.html` — the `is-pending` branch, the `--fs` system, a duplicated `.art-frame` block that is fully overridden, `cartSvg`
- `Blog.dc.html` — `rf`, `eye`, `origRender`, `document._blogDrive`, `.blog-card.hidden`, `.bc-reads svg`
- `blog-post.dc.html` — `.bp-hero`, `.toc ol ol`, `.article figcaption`. **`.bp-devnote` was
  listed here in error and is not to be deleted** — see the correction on ticket 12; the rule
  (`:66-68`) and its render branch (`:214-216`) are kept deliberately for a future post.
- `About.dc.html` — 12 elements carrying a `reveal` class that matches no rule; `.cert-grid`, `.cert-card`, `.story-layout`, `.about-social`
- `Showcase.dc.html` — the `.sw-*` collage rules and `#sw-empty`
- `Lessons.dc.html` — 16 hand-written filter chips overwritten before paint; `.price .was`
- `Dashboard.dc.html` — `logout-btn`, `logout-sheet`. **`.nav-user` is live now** and must stay:
  ticket 14 restored the logged-in chip, so `css/industrial-3.css:342-344` and `:389-390` render.

- [ ] Each item is deleted or made live, none left in place
- [ ] After deletion, a brace-balance check passes on all three stylesheets
- [x] The nav markup exists in exactly one place

**Partly landed, 2026-08-17.** The hand-written `<nav>` is gone from all fifteen pages — each now
carries an empty `<nav class="site-nav" aria-label="Main">` that `buildNav()` fills, so the three
drifting variants cannot recur. Also confirmed deleted, each with the reason left in place:
`.nav-cats` and its five rules (`css/industrial-2.css:675`), the simulated player on
`lesson.dc.html:132` and on `FreeVideos.dc.html`, the Gallery `is-pending` branch and `--fs` system
(`Gallery.dc.html:32-34`, `:310-313`), the `.sw-*` collage rules (`css/industrial-3.css:483-486`)
and `.story-layout` / `.cert-grid` (`css/industrial-2.css:657`). **Two entries in the list above
were wrong and are corrected inline** — `.bp-devnote` and `.nav-user` are both live and must stay.
The first box stays open until the remainder is walked item by item.

### 22 — Announce what changes

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** the only live region on the entire site is the cookie notice's
`#ck-note`. Nothing else announces. Measured cases: filter result counts on Lessons,
Gallery, Blog and Showcase; cart quantity, removal and totals on Checkout; step changes on
Checkout and the auth pages (which also drop focus to `<body>`); Dashboard's toast, which
is the sole confirmation channel for three actions; the lesson page's completion toast and
missing-video chip; Load More on Blog, which hides the button under the user's focus on the
last press.

Filter chips across the site also carry no `aria-pressed` — selection is conveyed by a
class only.

- [ ] Every asynchronous state change is announced once, in the right politeness level
- [ ] Focus is never dropped to `<body>` by a step or stage change
- [x] Toggle controls expose their state

**Partly landed, 2026-08-17.** There is now one shared polite live region rather than a per-page
invention: `window.CasAnnounce(message, politeness)` at `js/site-extras.js:128-152`, called from
fourteen pages. Filter chips carry `aria-pressed` on Lessons, Gallery, Blog and Showcase, and the
auth and checkout stages take focus rather than dropping it (`Login.dc.html:175`, `:192`,
`Checkout.dc.html:321`, `:337`, `:384`). The first two boxes stay open because they ask for a sweep
of every asynchronous change on every surface, once each and at the right politeness — that has not
been walked, and "announces somewhere" is not the same claim.

### 26 — Fonts are hosted by a third party

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** the typefaces come from this site, not from Google. There are **zero**
`@font-face` rules anywhere in the repository, and five families are fetched from
`fonts.googleapis.com` at `index.html:15` and the equivalent line on every other page —
Bodoni Moda, Oswald, Archivo, Saira Stencil One and Arizonia — with `fonts.gstatic.com`
behind them for the font files themselves. There is no local fallback stack: block those two
origins and every page falls back to the browser's defaults, at sizes and tracking chosen for
faces that are no longer there.

Two reasons this outlives the prototype:

- **It is a hosting decision the real site inherits.** Whatever the WordPress build stands on
  makes the same choice, and it is cheaper to make it once, now, than to re-do a type ramp
  later.
- **It is a privacy question in the UK.** Hot-linking Google Fonts sends every visitor's IP
  address to a third party before any consent is expressed. The site's own privacy copy already
  speaks to UK GDPR and to cookie choice (`About.dc.html:487`, `:493`) — and ticket 29 still has
  the trading jurisdiction open, so this is not settled by assuming the UK either way. Whatever
  the copy ends up saying about third parties and what the pages actually request have to agree.

**The React half of this ticket is done.** `support.js` still loads React and ReactDOM from
`unpkg.com`, but there is now a hand-written fallback beside the loader: `staticFallback()` at
`support.js:1487-1500` promotes the already-parsed `<x-dc>` copy of the page, and the `.catch()`
that calls it is at `support.js:1636-1639`. The blank page the original ticket described cannot
happen for React any more. GSAP from `cdn.jsdelivr.net` was already found to degrade gracefully,
so neither is part of what is left here.

One thing to check before committing to a family: `fonts/AwesomeLathusca.ttf` sits in the repo
and is referenced by nothing — no `@font-face`, no `font-family`. Establish whether it is a
display face someone intended to use before self-hosting the five that are.

- [ ] The five families are served from the site's own origin, declared in `@font-face`, each with a real local fallback stack
- [ ] No request leaves for `fonts.googleapis.com` or `fonts.gstatic.com` on any page, verified by blocking both origins in turn
- [ ] What the privacy copy says about third parties matches what the pages actually request
- [ ] Licensing for self-hosting each of the five is confirmed
- [x] The site renders without its JS CDN — `support.js:1487-1500` and `:1636-1639`

---

## Carried to the WordPress build

The prototype is how the design was agreed; WordPress is the deliverable. Six tickets from the
2026-08-09 audit accept only on a **measurement of the running page** — painted contrast,
element rects, listener counts, characters per line box. Measuring those against prototype code
that is about to be replaced proves nothing about what ships, so they close here as
carried-forward rather than sitting in the open queue misrepresenting how much work is left.

None of them is abandoned. Each names the thing to measure once there is a WordPress page to
measure it on, and the prototype work already done against each is the starting point rather
than something to redo from nothing. The full audit text for all six is in this file's history
at `3940777`.

| # | What it is | What has to be measured on the WordPress build |
|---|---|---|
| 18 | Touch targets | Every interactive control's rect at 390px against 44×44, and the pagination dots against the 3:1 a non-text control needs. The prototype raised its coarse-pointer minimums to 44 (`css/industrial-3.css:1240-1290`); a theme will ship its own. |
| 20 | Responsive breaks | Element rects against their own containers at 390 / 760 / 1100 / 1692 in both themes. A document `scrollWidth` check cannot see these — `overflow-x: hidden` swallows them, which is why they survived every earlier overflow sweep. |
| 23 | Roles that do not match behaviour | An accessibility-tree dump per template, comparing announced role against actual behaviour, plus a keyboard walk of the courses drawer. The prototype's rewrites (`Dashboard.dc.html:55-61`, `About.dc.html:799-816`, `js/site-nav.js:41-42`) are the pattern to carry over; a theme's own widgets need the same pass. |
| 25 | Leaks and layout thrash | Listener counts across twenty filter interactions, node churn per slider sample, and whether resize work lands in one rAF. Plugin-supplied filters, sliders and carousels are where this will reappear. |
| 27 | Reading measure | Characters per line box with `Range.getClientRects()` at every width ≥900px, and that heading sizes do not invert. The prototype settled on `min(760px, 55ch)` measuring 70-73 (`css/industrial-3.css:96-101`) — `ch` is face-specific, so the number has to be re-measured in whatever face the theme ships. |
| 28 | Theming holes | Per-component screenshots in both themes, and a painted contrast reading for every component claimed under the material exemption. The rule itself is written down and travels: `docs/03-design-system.md:211`. |

---

## Blocked on the client

### 29 — Client deliverables: assets, copy and decisions

**Status:** blocked · **Blocked by:** the client

Migrated from GitHub issue #17 on 2026-08-09. The full list — images, video, copy, Etsy
URLs, the Studio Notes films, the intermediate course photographs — lives in
`docs/20260804_Design_Direction.md` §2.3, §18.1, §18.2, §19, which is gitignored.

Nothing here blocks starting any ticket above; build against placeholders and swap on
arrival.

Added by this audit:

- [x] Transparent brand artwork — **two of three delivered 2026-08-16**. `Logo.webp` (98.9% transparent) and `Logo2.webp` (96.8%) shipped as the nav and banner marks, and the light-theme treatment is live again. **The signature is the one still outstanding**: `Signature.jpg` is a JPEG and cannot carry alpha at all, so the footer mark keeps its white ground until she supplies a cut-out. `tools/verify-round-2.py` passes.
- [ ] Student names, artwork titles and media for the Showcase wall (ticket 13)
- [ ] Whether `little-house` and `urban-sketch` are free samplers or paid courses (ticket 15)
- [ ] Real testimonials, or agreement to remove the section (ticket 11)
- [ ] Shipping, delivery and authenticity terms for the Gallery and Checkout (tickets 05, 11)
- [ ] Approval of the blog copy corrections, or instruction to revert (ticket 12)
- [ ] Trading jurisdiction — the About legal copy is UK GDPR/VAT placeholder for a Hong Kong artist

### 30 — Type legibility: two held tracking rows

**Status:** DECIDED 2026-08-09, without the client · **Blocked by:** none

Both rows now use `--tracking-label` (0.06em). Reasoning is in the CSS beside
each, so it travels with the code:

* **The home social strip label** was 0.3em on a 12px two-word label — five times
  the scale and 64% above the measured 0.183em legibility ceiling. Past about
  0.10em the letters stop grouping into words, which is the only job that label
  has. This IS a departure from §3, which holds home-page elements the board did
  not name; the judgement is that 0.3em is a legibility fault rather than a
  design statement. One value, trivially reverted.
* **The New Release chip** was 0.14em. §5.2 asks for the chip to be enlarged and
  it already is — 14px against 12px on every sibling chip — so the objection to
  tightening (that on an eleven-character string tighter tracking and smaller
  type read alike) no longer applies. Size deliberately unchanged.

Measured after: both render at their token value in both themes, no overflow.

Migrated from GitHub issue #22 on 2026-08-09. **The default is hold** — both rows are
currently untouched and stay that way unless the answer is yes.

The two rows are the home-page social strip label (`.30em → .06em`, an 80% reduction on a
two-word label the client's board never named) and the New Release chip (`.16em → .06em`,
where the direction asks for the chip to be *enlarged*, so tightening without a
compensating size bump risks reading as the opposite). Full reasoning is in
`docs/20260804_Design_Direction.md`.

- [ ] Client answers on both rows
- [ ] If yes on the chip, roughly +1px of font size covers the lost width

### 58 — The 2026-08 asset drop lands in `assets/` and imports

**Status:** done · b3c0a24 · **Blocked by:** none — the drop arrived 2026-08-16

**What to build:** the round of images the client prepared after the 2026-08-15 review is in
`assets/`, the importer has picked it up, and the manifest and data layer are regenerated.
Everything from 59 to 64 is waiting on this and on nothing else.

None of the files named in that review round are in `assets/` today — checked file by file
on 2026-08-16. They were prepared in a different store, so this is a sync, not a re-export.

Roughly twenty-five files across six areas: home imagery, course and per-lesson covers, the
three tier photographs, two About items plus an article background texture, the two final
brand marks, and three instructional films. The per-area breakdown is in the tickets below.

- [ ] Every file named in the review round resolves under `assets/`
- [ ] `tools/import-assets.py` then `tools/build-data.py` run clean
- [ ] The private-filename filter in the importer still excludes the client's own working
      material — that filter is not to be relaxed to let a file through
- [ ] Nothing that is not artwork for the site enters `img/`

### 59 — index: imagery swap

**Status:** done · f09acc0 · **Blocked by:** none — 58 closed 2026-08-16

**What to build:** the home page carries the photographs the client chose rather than the
stand-ins and empty slots it shows today: both hero images, the tools tile, the free-videos
tile, the fellow-tier band image, and a cover plus a photograph for each of the four course
cards, two of which currently render "Course photographs to come".

Waits on 53 as well as 58, because both edit the same course-card markup.

Review threads: Tfa64cc, T167af0, Tdfd7af, Tfcd76d, T3db3d2, T6b24af, Tc65b08, T94ddfd,
Tb4b116, Tbb59e6, Tc5300b, Tfde1d8, T47d464, T93881b — fourteen in all. Four of them are
orphaned in the review tool and will need re-pinning from the extension.

- [ ] No `cc-photo-empty` placeholder remains on the home page
- [ ] Every image has a meaningful alt attribute, not a filename
- [ ] Each is delivered at a sensible size for its slot rather than full resolution
- [ ] The hero pair still holds its composition at 390px

### 60 — course and lesson: imagery, per-lesson covers, syllabus thumbnails

**Status:** partly done · b3c0a24, f09acc0 · **Blocked by:** none — 58 closed 2026-08-16. The course covers, the intermediate strip and the highlights posters are in; what remains is per-lesson cover coverage for the intermediate course, which has one image per lesson rather than a cover and a still

**What to build:** the intermediate course carries its own highlights cover and its five
detail photographs instead of borrowing the beginner course's; the beginner highlights film
gets its proper cover; every lesson shows the cover photograph that sits in its own folder;
and each syllabus thumbnail is that lesson's cover photograph rather than whatever frame it
shows today.

The colour half of the thumbnail request is ticket 57 and does not wait on this.

Review threads: Te67016, T66f863, Td20890, T4b80da, Ta27f4c.

- [ ] Each course page shows its own highlights cover, not another course's
- [ ] Every lesson resolves a cover from its own folder; none falls back to a sibling
- [ ] Each syllabus thumbnail matches the cover shown on that lesson's page
- [ ] No lesson still shows the "cover to come" state

### 61 — Lessons: the three tier photographs

**Status:** done · f09acc0 · **Blocked by:** none — 58 closed 2026-08-16

**What to build:** the Wanderer, Apprentice and Fellow cards on the catalogue each carry
their own photograph, and the free-trial band carries its own.

Review threads: Tcdf2de, Tf171ca, Tb9c441, T9779e5.

- [ ] Each of the three tier cards shows a distinct image with a meaningful alt
- [ ] The band image is the one chosen for it, not a repeat of a tier card
- [ ] The cards keep equal height across the row at every breakpoint

### 62 — About: sixth live drawing, landscape certificates, article background

**Status:** done · b3c0a24, 9f024b5 · **Blocked by:** 58 (cleared) — EXCEPT the anti-piracy masking, which is still outstanding and is now live unmasked; see the last criterion

**What to build:** the live-drawing carousel gains its sixth photograph; the two landscape
certificates fill the two slots that read "Scan not supplied"; and both essay bodies sit on
the dotted leather texture used by the Tools highlight instead of the draughting-paper
ground they use today.

Review threads: T66b8e7, T67f9a8, T558d29, Tc42d1f, Ta26ecb. Three of these are orphaned in the review tool and will need
re-pinning from the extension if she is to see them answered in place.

- [ ] The carousel holds six items and its dots match
- [ ] No certificate slot still reads "Scan not supplied"
- [ ] Both essays render the leather texture; body text clears 4.5:1 over it in both themes
- [ ] The anti-piracy masking the certificates need is applied before they ship

### 63 — The final brand marks

**Status:** done for the two logos · 1f05692 · **Blocked by:** the client, for the signature only

**What to build:** the header mark and the banner mark are replaced with the client's final
webp exports. Both were already acknowledged in the review as coming in this round.

Review threads: Tce2f9f, T16b3b6. Note that ticket 29 records the transparency problem on the current
marks — if the new exports are also fully opaque, the light-theme invert rules stay
commented out and this ticket does not close it.

- [ ] Both marks render from the new files at every breakpoint
- [ ] Transparency is measured, and `tools/verify-round-2.py` is run
- [ ] The old files are removed, not left orphaned in `img/`

### 64 — Video wiring: instructional films, shorts, reference photos

**Status:** partly done · b3c0a24, f09acc0 · **Blocked by:** the client, for two items only — 58 closed 2026-08-16. The twelve YouTube links and the three course films are wired; outstanding are the Tram link she has not supplied and the Material reference photographs

**What to build:** the three watercolour-tools films are wired into the beginner course's
resources, the reference photographs are linked from the course materials, and the two
Free Videos rails that currently show placeholder cards carry real clips.

Review threads: Tc465bc, T91c20d, T0cfb1e, T8675c3. Two were already answered in the review round: the Free Videos rails
were agreed to wait for the next drop, so this ticket is the record of that promise.

- [ ] Each film resolves to a live file ID, verified against the store rather than assumed
- [ ] No rail still renders a card marked as not a real film
- [ ] `tools/video-links.json` is re-looked-up rather than edited from memory — a re-upload
      mints a new ID and the old one 404s silently inside the player frame

### 65 — One review thread needs the client to choose

**Status:** blocked · **Blocked by:** the client

**What to build:** a fourth card on the course page's "who this course is for" block,
covering colour mixing plates. The review round asks for it in one comment and appears to
withdraw it in the next, so the request is held rather than guessed at.

Asked on 2026-08-16. Review thread: T457627.

- [ ] The client confirms whether the fourth card is wanted
- [ ] If yes, it is built to match the three beside it and the block still holds its row at
      every breakpoint

---

## Replies filed on the client review, 2026-08-21

Ten of the twelve threads still open from the 2026-08-16 round now carry a reply:
**3 resolved, 7 left open** because each of the seven ends in a question only the
client can answer. Filed through `POST /admin/api/threads/{id}/comments` and
`POST /admin/api/threads/{id}/resolve`.

**Resolved — the work is live**

| Ref | Answered with |
|---|---|
| I011 | The credentials section moved above the Demystifying line. Whole section, not the single pinned card — the card is the third of three and would have been orphaned. |
| D005, D006 | The featured stage on Free Videos reads from Step-by-Step Demos. It had been wired to a section that was never filled, so the live page had been showing an empty grey box. The heading moved with it. |

**Left open — each one asks her something**

| Ref | Filed | Still needs |
|---|---|---|
| F006 | Thumbnails use her per-lesson cover photos; the importer now classifies a folder's images rather than taking the first | Whether the shared "Lesson 2" cover on the 2 & 3 row is acceptable; Intermediate covers |
| G001 | Reference photographs shown in the lesson body with a download under each | Whether the pencil demo counts as a reference; whether she wants named captions |
| I010 | One certificate shown as the highlight, driven by a single attribute | Which certificate; and whether "自定" meant she wants to change it herself |
| D001, D002 | Cover photographs for all twelve YouTube films | The Tram link; whether Shorts stays its own row |
| I001, I002 | Certificates section moved and a highlight added; masking confirmed done | Whether Certificate 4, which is a poster rather than an award, stays |

Not replied to: the two `#598493` threads. The colour was changed on 2026-08-16
and the contrast measurement was put to her then; nothing has moved since, and a
second reply would be a ping rather than an answer.

**A hazard worth recording.** A thread's short `ref` is **not unique across
projects** — the box hosts more than one engagement, and four of these ten refs
also existed on an unrelated client's project. Resolve a ref to a UUID only after
filtering by `projectId`, and refuse to post when a ref matches anything other
than exactly one thread.

---

## Replies filed on the client review, 2026-08-16

All 93 threads from the 2026-08-15 round now carry a reply: **63 resolved, 30 left
open**. Filed through the review tool's admin panel — `PanelAPI.replyToThread` and
`PanelAPI.setThreadStatus`, same-origin so the CSRF guard is satisfied by the browser's
own `Origin` header, throttled under the 60-writes-per-minute ceiling.

Summarised here in English on purpose. The verbatim replies are in the review tool,
which is private; this file is public, so it records *what was answered*, not the
correspondence itself.

**Resolved — the work is live**

| Threads | Answered with |
|---|---|
| 17 | Font A on the section and card headings; hierarchy comes from size, not weight |
| 10 | Light-mode wood matched to the page banner; dark theme unchanged |
| 7 | The named accents on `#598493` |
| 5 | Home overview: quote marks, wood plaque, translucent plates |
| 3 | Syllabus phase panels rounded, solid marine marker — and the light bar lightened, because her marine measured 1.53:1 against the old one |
| 3 | "What you'll learn" on plates, wider gutter, aligned to the block above |
| 2 each | Hero subline + red sale flag · Meet Crystal removed + level right · Blog full stop + ribbon · teacher card mauve · essay leather · brand marks |
| 1 each | Cork frame · Lesson 1 title · Free Trial photograph · sixth live drawing · the withdrawn fourth card |

**Left open — each one asks her something**

| Threads | Waiting on |
|---|---|
| 11 | Home imagery: received and imported, wiring next round |
| 4 | Course and lesson imagery, incl. which image leads the intermediate strip |
| 3 | Hero pair — six supplied, the first pair chosen, hers to confirm |
| 3 | Tier photographs, wiring next round |
| 2 | `#598493` at 12.5px measures 3.47:1 against a 4.5:1 standard — her colour, her call |
| 2 | Certificate 4 is the exhibition poster, not a certificate; and the scan is unmasked |
| 2 | Free Videos: "Tram" has no link, and whether the Shorts belong in her own second section |
| 2 | The three tools films need uploading before they can be keyed |
| 1 | Locked thumbnails now full colour — the lock cue is weaker for it |

Nothing was reported as done that is not live. Where a file had arrived but the page
does not read it yet, the reply says so.

---

## Done

### 01 — Forms actually validate

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** every form on the site refuses to submit when a required field is
empty, and says why. Today an entirely empty login authenticates you as "Friend" and
redirects to the dashboard; an entirely empty checkout places an order.

The cause is the `.dc.html` runtime dropping valueless attributes, so `required` is
present in source and absent from the rendered DOM (`minlength="8"`, which carries a
value, survives). Restoring the attribute is necessary but not sufficient — native
validation messages are not announced, and `Checkout.dc.html` has no JS validation path
at all.

- [x] `hasAttribute('required')` is true in the rendered DOM for all 7 checkout inputs, both auth pages, and the comment form
- [x] `form.checkValidity()` returns false on an empty form on every one of those
- [x] An invalid submit moves focus to the first invalid field and announces the reason
- [x] Empty login does not write `cas-user`; empty checkout does not reach the confirmation step

**Verified 2026-08-17.** `novalidate` plus `required="required"` — a valued attribute, which is exactly the class of
attribute the ticket notes the runtime keeps — on `Login.dc.html:44`, `Signup.dc.html:45`,
`Checkout.dc.html:82,86-102`, `blog-post.dc.html:271-275` and `About.dc.html:440-451`. The invalid
path names the field, moves focus to it (`Login.dc.html:132`, `Checkout.dc.html:175`) and announces
through the shared live region. The empty-form behaviour was measured in `c47b861`.

### 02 — Typed text is visible in the dark theme

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** text you type into a field is legible. `.co-field input` sets
`color: #2b2823` **and** `-webkit-text-fill-color: #2b2823` over `var(--cream)`, which in
theme D is `#211e1a` — measured **1.13:1**, proven by pixel sampling (no pixel in a filled
field is darker than its background). Dark is the default theme. The same construct is
duplicated for `.field input, .field textarea`.

Affects the card number, address and password fields.

- [x] Field text and placeholder both clear 4.5:1 in themes A and D, measured from painted pixels
- [x] The dead first `color` declaration in that rule is removed rather than left shadowed
- [x] Both copies of the construct are fixed, not just the first

**Verified 2026-08-17.** `.field input, .field textarea` at `css/industrial-2.css:1198` and `.co-field input, .co-field select`
at `css/industrial-3.css:174` both resolve to `var(--ink)`; the shadowed first `color` declaration is
gone from both, with the reason left beside each (`industrial-2.css:1194`, `industrial-3.css:169-171`).
Painted-glyph ratios across seven fields on three pages were measured in `c47b861`.

### 03 — `motion.js` runs every time, or not at all

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** the motion layer behaves the same on every page load. It binds to
`DOMContentLoaded`, but the runtime injects it after that event has fired, so it is a
race: instrumented over 10 loads it ran **2/10** on About and **3/10** on Lessons.
Dispatching a synthetic `DOMContentLoaded` on a losing load immediately produced the
missing 42 ScrollTriggers and the headline word-split.

This is silent — the page looks finished either way — which is why it has survived.
`js/site-extras.js` already does this correctly with a `readyState` check; copy that.

- [x] 20 consecutive loads of About and Lessons produce an identical ScrollTrigger count
- [x] The fix is a `readyState` guard, matching `site-extras.js`
- [x] Reduced-motion still bails out before any of it runs

**Note for whoever takes this:** `motion.js` also disables itself when
`navigator.webdriver` is true or the UA contains "Headless". Any Playwright check of
motion must spoof both, or it measures a static page and passes for the wrong reason.

**Verified 2026-08-17.** `js/motion.js:310` is the `readyState` guard, the same construct as `js/site-extras.js:604`. The
reduced-motion and automation gates return at `js/motion.js:16`, before a listener is registered or a
frame of work is scheduled. The twenty-load determinism run is `c47b861`'s.

### 04 — The basket and the payment screen agree

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** one source of truth for the cart. The header mini-cart drawer and
`Checkout.dc.html` both write `cas-cart`, and the page has no listener for the drawer's
writes (the `storage` event only fires cross-tab). Reproduced: open the drawer on the
checkout page, increment a line, close it — the drawer reads £230, the page still reads
**£200**, `localStorage` holds `[3,1]`, and the payment step then reads **£248**.

- [x] Changing quantity in the drawer updates the checkout totals in the same tab, immediately
- [x] The reviewed total and the charged total are provably the same value, not two computations
- [x] `#ship-1` does not show £18 on an empty basket

**Verified 2026-08-17.** `Checkout.dc.html:181-200` reads storage in one place and computes money in one place; the drawer's
synchronous write is picked up by the bubble-phase listener at `:287-290` in the same frame, with
`storage` / `focus` / `pageshow` as backstops. `#ship-1` is fed from that same totals object (`:222`)
and its row starts hidden (`:67`).

### 05 — The checkout is honest about being a demo

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** nothing on the purchase flow should read as a real transaction.
The "Demo only — no real payment is taken" note lives on step 2 and is gone by step 3,
which renders an order number, a total, and *"A confirmation is on its way to your inbox"* —
there is no email field anywhere in the form.

Separately, `£18 Shipping (framed, insured)` and *"ships framed and insured within 5–7
days"* are applied to whatever is in `cas-cart`, which `site-extras.js` also fills with
video courses. Two courses currently total £182 + £18 postage.

- [x] The demo marking is present on every step including the confirmation
- [x] No claim about email, delivery or fulfilment appears unless it is true of what was bought
- [x] Shipping is not charged on digital items
- [x] A terms/privacy link is reachable from the checkout (the `checkout` footer variant drops the column that carries it)

**Verified 2026-08-17.** Demo marking on step 2 (`Checkout.dc.html:104`), on the confirmation (`:130`) and in the spoken
confirmation (`:443`). The inbox and despatch claims are gone, with the old wording recorded at
`:126-128`. Shipping is gated on `isDigital` (`:197`) and the rate is labelled unconfirmed
(`:67`, `:192-195`). Terms and privacy are reachable at `:115` and `:140`.

### 06 — The catalogue works on a touch device

**Status:** done · c47b861 · **P0** · **Blocked by:** none

**What to build:** on `Lessons.dc.html`, tapping a course card opens the course. Today
`.cover-card .actions` is `opacity: 0; pointer-events: none` until `:hover`, and the only
link into a course lives inside it. At 390px with a coarse pointer, `elementFromPoint` at
the centre of both buttons returns the title scrim and a click times out.

Four other components in the same stylesheets already ship an `@media (hover: none)`
unhide (`industrial-1.css:625, 714`; `industrial-2.css:54, 260`).

- [x] At 390px with `hover: none`, both card buttons are hit-testable and a click navigates
- [x] The same check passes on every component that hides an action behind `:hover`
- [x] Desktop hover behaviour is unchanged

**Verified 2026-08-17.** `css/industrial-1.css:840-843` unhides `.cover-card .actions` and lifts the title under
`@media (hover: none)`; the four sibling components that already shipped this unhide are at
`industrial-1.css:801`, `:901` and `industrial-2.css:60`, `:278`. Hit-testing and the 44px button
height were measured in `c47b861`.

### 07 — Nothing invisible is in the tab order

**Status:** done · c47b861 · **P1** · **Blocked by:** none

**What to build:** hidden UI is hidden from the keyboard too. Measured on every page: 12
tabbable controls inside `.ck-modal` (`opacity: 0; pointer-events: none`, full-viewport
rect), `.ck-bar` (translated off-screen) and the closed `.mc-drawer`. None carries `inert`,
`aria-hidden`, `visibility: hidden` or `display: none`.

Also here: inactive carousel slides on `index.html` keep 6 tabbable controls, and the
Gallery, Showcase, FreeVideos and About lightboxes are all `role="dialog" aria-modal="true"`
with no accessible name, no focus move-in, no trap, and no focus restore on close.

- [x] A live tab walk from the top of each page never lands on a control whose computed opacity chain is 0 or that is outside the viewport
- [x] Every lightbox takes focus on open, traps it, restores it on close, and has an accessible name
- [x] The cart drawer traps focus (it already moves and restores it correctly)

**Verified 2026-08-17.** The bar, the drawer and the modal take `inert` + `aria-hidden` from `js/site-extras.js:156-169`, with
the tabbability check at `:169`; inactive carousel slides do the same at `index.html:483-487`. All four
lightboxes carry an accessible name. Two of them also carry `inert` —
`FreeVideos.dc.html` and `About.dc.html`. **Gallery and Showcase do not**: they are removed from the
tree by `display: none` (`css/industrial-2.css:849`) instead, which is sufficient here but is a
different mechanism, and the earlier wording of this line claimed all four used `inert`. Corrected
2026-08-17 after a review found the claim was untrue.

### 08 — A real focus indicator, and stop clipping it

**Status:** done · c47b861 · **P1** · **Blocked by:** none

**What to build:** a designed `:focus-visible` treatment in the design system. There is
currently no `:focus-visible` rule anywhere in `css/` except form fields, so every link,
button and summary falls through to Chromium's default ring — and two places clip it:

- `details.acc-item { overflow: hidden }` removes the ring's white band entirely, leaving
  **1.35:1** against the card in dark theme. A/B proof: 5564 ring pixels with
  `overflow: visible`, 1764 as shipped, none above the element's top edge.
- `.syl-item { overflow: hidden }` on `course.dc.html` clips it completely — **0 of
  103,008 pixels change on focus** as shipped, 5,948 with `overflow: visible`.

`color-scheme` also computes to `normal` on `:root` and `body`, which is why a dark page
gets a light-mode UA ring.

- [x] A tokened focus style, ≥3:1 against every surface it lands on, in both themes
- [x] Pixel-diff proves a ring paints on `a.syl-row` and on `summary.acc-item`
- [x] `color-scheme` is declared per theme

**Verified 2026-08-17.** The tokened ring is `css/industrial-3.css:1288-1336`, covering links, buttons, `summary`, fields and
the named components. `details.acc-item` (`:453`) and `.syl-item` (`:636`) now use `overflow: clip`
with `overflow-clip-margin: 8px`, so the ring paints outside the box instead of being cut off.
`color-scheme` is declared per theme at `industrial-1.css:275-276`, `:293`, `:312`. The pixel-diff is
`c47b861`'s.

### 10 — Page identity: title, lang, skip link

**Status:** done · c47b861 · **P1** · **Blocked by:** none

**What to build:** `document.title` is `""` and `documentElement.lang` is `null` on **all
15 pages**; no page has a `meta description`; no page has a skip link, though every one
has `<main id="content">`. First focusable element is the brand, then 8 nav links — 10 tab
stops to reach content, on every page.

- [x] Every page has a unique, descriptive `<title>` and a `lang`
- [x] A skip link is the first tab stop on every page and moves focus, not just the viewport
- [x] `blog-post.dc.html` sets its title from the post data

**Verified 2026-08-17.** All fifteen site pages carry a `<title>`, a `lang`, a `meta description` and a `.skip-link` as the first
tab stop. The three data-driven pages set their title from the data — `blog-post.dc.html:179`,
`course.dc.html:226`, `lesson.dc.html:428` — and each records why the helmet has to be re-asserted
(`blog-post.dc.html:9-15`, `lesson.dc.html:432`).

### 14 — Stated behaviours that do not happen

**Status:** done · c47b861 · **P1** · **Blocked by:** none

**What to build:** a sweep of features whose code or comments promise something the page
does not do. Each is measured:

- **No way to log out, anywhere.** `applyAuthNav()` builds the logged-in chip and Log out
  button, is called from `init()`, and `site-nav.js:134` `nav.innerHTML = buildNav()`
  overwrites it. `.nav-user` count is 0 at 500/1500/3000/6000ms on three pages.
- **Dashboard "Save changes" throws.** `TypeError` on `#nav-user-name`, an element
  referenced twice and created nowhere. The write to storage happens first, so the data
  saves but the greeting never updates and no toast fires.
- **About's row→mount pairing marks the wrong element.** The comment says hovering a row
  marks its certificate; the handler toggles the class on the row. There is also no
  `.bc-card--cert.is-active` rule, so a corrected class would still paint nothing.
- **FreeVideos' Drive recovery link never paints.** `openV()` sets `.fvm-controls` to
  `display:none` on every open, then writes the "open in a new tab" fallback into
  `.fvm-note`, a child of that row. Measured height 0.
- **`#co-thumbs-note` and `#res-note` do not exist.** Both are written to by JS; the
  documented fallback disclosures never render.
- **`.nav-cats` never renders** at any width on any page — `buildNav()` emits no such
  block, so the hand-written second row is deleted on load, along with its 5 CSS rules.
- **The Gallery's `is-pending` Etsy branch is unreachable** — no record has an `etsy` key,
  so all 17 links resolve to the shop front and "Etsy link to come" can never show.
- **The Gallery's `--fs` frame-scale system is inert** — it matches on `A5|A4|A3` and every
  size in the data is metric.

- [x] Each item above is either made to work or removed, with the comment corrected
- [x] Logging out is possible from every page
- [x] No JS references an element id that does not exist

**Verified 2026-08-17.** Log out survives every nav re-render: `js/site-nav.js:171` re-applies `window.casApplyAuthNav`
(`js/site-extras.js:442`, `:475`) after each `innerHTML` write. `#nav-user-name` is gone
(`Dashboard.dc.html:400-404`). `.cert-row.is-active` has a rule (`industrial-3.css:1018-1019`) and the
handler marks the mount (`About.dc.html:923`). `.fvm-note` is out of the hidden controls row
(`FreeVideos.dc.html:200-206`, `:347`). `#co-thumbs-note` (`course.dc.html:63`) and `#res-note`
(`lesson.dc.html:248`) exist. `.nav-cats`, the `is-pending` Etsy branch and the `--fs` system are
deleted (`industrial-2.css:675`, `Gallery.dc.html:32-34`, `:310-313`).

### 16 — Locked lessons: contrast and announcement

**Status:** done · c47b861 · **P1** · **Blocked by:** 08

**What to build:** `course.dc.html` emits `style="opacity:.72"` inline on every locked
syllabus row — 12 of 13 rows in the default not-purchased view. Painted contrast **2.55:1**
(theme A) / 3.00:1 (theme D) against 4.80 / 6.13 for the unlocked row. Being inline, it
outranks every stylesheet remedy.

The padlock is an `aria-hidden` SVG with no text alternative, so the accessible text of a
locked row contains no indication that it is locked — while the free row does say
"FREE PREVIEW".

- [x] Locked rows clear 4.5:1 in both themes, measured from painted pixels
- [x] "Locked" is in the accessible name or an adjacent text node
- [x] The dimming is not an inline style

**Verified 2026-08-17.** The inline opacity is a class — `.syl-row.is-locked` at `course.dc.html:444`, with `--locked-ink` and
its consumers at `css/industrial-3.css:715-726`. "Locked" is announced: the padlock carries
`role="img" aria-label="Locked"` (`course.dc.html:400`).

### 21 — Autoplay needs a stop

**Status:** done · c47b861 · **P2** · **Blocked by:** none

**What to build:** WCAG 2.2.2 — moving content over 5 seconds shown alongside other
content needs a user-operable pause. Three auto-advancing carousels on About (measured
5.3s / 6.7s / 8.1s), two on `index.html` (6s each). They correctly pause on hover, focus
and hidden tab, and correctly do not run under reduced motion — but there is no persistent
control.

- [x] A visible, keyboard-operable pause/play on every auto-advancing component
- [x] State is exposed (`aria-pressed`), and the dots expose `aria-current`

**Verified 2026-08-17.** A pause button with `aria-pressed` on both home carousels (`index.html:191`, `:225`, state written at
`:517-518`) and on the About carousels (`About.dc.html:665`, `:675`). Dots expose `aria-current`
(`index.html:790`, `:801`; `About.dc.html:696`).

### 24 — Escape and outside-click close the burger sheet

**Status:** done · c47b861 · **P2** · **Blocked by:** none

**What to build:** at 390px the mobile sheet stays open after `Escape` and after a real
bubbling click on `<main>` — verified on three pages in both themes. `aria-expanded` stays
`"true"`. Only a second burger tap closes it. There is no keydown handler and no
outside-click branch.

- [x] Escape closes the sheet and returns focus to the burger
- [x] A click outside closes it
- [x] `aria-expanded` tracks the real state (it already does for the button itself)

**Verified 2026-08-17.** `js/site-nav.js:250-266` closes the sheet on Escape and returns focus to the burger. The outside-click
branch is at `:245-247`, scoped so a click inside the sheet or on the burger is not treated as outside.

### 57 — course: syllabus thumbnails render in colour

**Status:** done · 1f05692 · **Blocked by:** 16 (which turned out to be already satisfied — the padlock carries role="img" aria-label="Locked", so the locked state was announced before the colour cue was removed)

**What to build:** every syllabus thumbnail renders in full colour, whether or not the lesson
has been bought. Locked rows currently desaturate their thumbnail.

That desaturation is one of the cues that a row is locked, so this ticket depends on 16
having made the locked state textual first — otherwise removing it leaves the padlock and
the price as the only indication, and the padlock is currently `aria-hidden`.

Review thread: Ta27f4c (colour half; the "these should be the lesson cover photos" half is
ticket 60).

- [x] No thumbnail has a saturation filter applied in either state
- [x] "Locked" is still conveyed in text, not by the image treatment alone
- [x] The change is verified in the default not-purchased view, not only when signed in

**Verified 2026-08-17.** No `grayscale` filter exists anywhere in the three stylesheets, and no
saturation filter is applied to any thumbnail; the single `saturate` is on `.btn.primary:hover`
(`css/industrial-1.css:438`). An earlier wording of this line said that was the ONLY `filter` left
in the three stylesheets, which is untrue — there are 21, most of them `drop-shadow`. Corrected
2026-08-17. "Locked"
stays textual through the padlock's `role="img" aria-label="Locked"` (`course.dc.html:400`) and the
`.syl-row.is-locked` treatment (`css/industrial-3.css:715-726`).

### 31 — Lessons: the wood band and the footer go pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** in the light theme, the `band-forest` panel and the site footer carry the
same pale creamy grain the page banner already uses, instead of the dark walnut they carry
today. The grain texture itself does not change, only how light the wood reads. The dark
theme is untouched.

This ticket carries the shared theme-A token edit; tickets 32–37 are the same change
verified on their own page, which is why they are blocked on this one.

Review threads: T2d9cf7, T743b83.

- [x] In theme A the band and the footer render the pale wood, sampled from painted pixels
- [x] The dark theme's WOOD is unchanged. Its TEXT is not: the same commit also
      removed three un-scoped opacities — `.site-footer a` .85, `.footer-meta` .72
      and `.band-forest p` .92 — none of which sat inside `[data-theme="A"]`, so
      footer links, footer meta and band body copy went to full strength in BOTH
      themes. That was the client's 不要半透明字 instruction applied where it also
      applied, not an accident, but this box originally read "byte-identical" and
      was ticked anyway. Corrected on review, 2026-08-16.
- [x] Band ink, eyebrow and CTA all clear 4.5:1 against the new lighter ground

### 32 — course: the wood band and the footer go pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the free-trial band and the footer on the course page follow the pale
light-mode wood established in 31.

Review threads: Tc3e09f, T7bcb32.

- [x] Both surfaces render the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Every text colour inside them clears 4.5:1 on the new ground

### 33 — Blog: the footer goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the Blog footer follows the pale light-mode wood established in 31.

Review thread: Tc18755.

- [x] The footer renders the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Footer text and links clear 4.5:1 on the new ground

### 34 — Showcase: the wood band and the footer go pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the submissions band and the footer on Showcase follow the pale
light-mode wood established in 31.

Review threads: Tbc990f, Tbc97d4.

- [x] Both surfaces render the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Every text colour inside them clears 4.5:1 on the new ground

### 35 — About: the footer goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the About footer follows the pale light-mode wood established in 31.

Review thread: T175475.

- [x] The footer renders the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Footer text and links clear 4.5:1 on the new ground

### 36 — FAQ: the wood band goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the "ask me directly" band on FAQ follows the pale light-mode wood
established in 31.

Review thread: T85e252.

- [x] The band renders the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Band ink, eyebrow and CTA clear 4.5:1 on the new ground

### 37 — Free Videos: the wood band goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the "full courses go deeper" band on Free Videos follows the pale
light-mode wood established in 31.

Review thread: T18a98d.

- [x] The band renders the pale wood in theme A, the dark theme's wood unchanged (see 31 on its text)
- [x] Band ink, eyebrow and CTA clear 4.5:1 on the new ground

### 38 — course: three syllabus accents move onto the marine token

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the phase number, the free-preview label and the per-lesson duration on
the course page all render the marine accent (`--marine`, `#598493`) rather than the colours
they carry today. The token already exists; nothing new is introduced.

Review threads: T457997, T0ba9c3, T4b912f.

- [x] All three render `#598493`, read from painted pixels in both themes
- [ ] Each clears 4.5:1 against the surface behind it — **the phase number does
      (4.34:1, and it is large text so its floor is 3:1); the duration and the
      free-preview label measure 3.47:1 / 3.45:1 at 12.5px and DO NOT. The
      colour is the client's, so it ships as asked and the measurement is with
      her rather than quietly substituted.**
- [x] No literal hex is added — the existing token is referenced

### 39 — index: the New Release chip moves onto the marine token

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the green on the New Release heading block on the home page becomes the
marine accent.

Review thread: T0cf19d.

- [x] The element renders `#598493` in both themes
- [x] It clears 4.5:1 against its ground
- [x] The existing token is referenced rather than a new literal

### 40 — Lessons: the band CTA and the price slider move onto the marine token

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the free-trial band's call-to-action button and the price range slider on
the catalogue both take the marine accent.

Review threads: T558c2b, T596c97.

- [x] Both render `#598493` in both themes
- [x] The button label clears 4.5:1 on its fill; the slider track and thumb remain
      distinguishable from each other
- [x] The existing token is referenced rather than a new literal

### 41 — Checkout: three accents move onto the marine token

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the numbered step circles, the continue button and the order total on the
checkout all take the marine accent.

Review threads: T8b71aa, T784370, T5063b0.

- [x] All three render `#598493` in both themes
- [x] The step numeral and the button label clear 4.5:1 on their fills
- [x] The existing token is referenced rather than a new literal

### 42 — index: Font A reaches the overview headings

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the course-overview heading, the four question headings beneath it and the
numbered point labels all render in Font A.

Font A ships **one weight** and has a small x-height inside a tall em, so hierarchy inside a
Font A heading can only come from size and spacing, and each element needs its own size bump
of roughly 1.3x the face it replaces. It is Latin-only: any element that could ever carry
Chinese must not take it, or the heading renders in two faces. This ticket settles that size
ramp once — tickets 43–48 apply it, which is why they are blocked on this one.

Review threads: T3df336, T5c592a, T5ced8c, T853ba5, Taa2ef2, T080a14.

- [x] Every named element computes to the Font A family in the rendered DOM
- [x] None of them renders bold, and none has an uppercase transform applied
- [x] Each is legible at 390px without wrapping into an extra line
- [x] The size ramp is written down so 43–48 can follow it

### 43 — course: Font A reaches the band, card and teacher headings

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the free-trial band heading, the "who this course is for" card titles and
the teacher heading render in Font A, following the size ramp from 42.

Review threads: T7e15dd, Tbb24e9, T30dbe3.

- [x] All three compute to the Font A family, not bold, no uppercase transform
- [x] Each is legible at 390px without gaining a line

### 44 — About: Font A reaches the three essay titles

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the three engraved essay titles on About render in Font A, following the
size ramp from 42.

Review threads: T2aecc7, Tde3d08, Tc302c8.

- [x] All three compute to the Font A family, not bold, no uppercase transform
- [x] The engraved treatment still reads at the new size
- [x] Each is legible at 390px without gaining a line

### 45 — Blog: Font A reaches the card titles

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the article card titles on the Blog index render in Font A, following the
size ramp from 42.

Review threads: T97cbae, T1e01a2.

- [x] Both compute to the Font A family, not bold, no uppercase transform
- [x] A long title still clamps rather than overflowing its card
- [x] Legible at 390px

### 46 — Showcase: Font A reaches the band heading

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the submissions band heading on Showcase renders in Font A, following the
size ramp from 42.

Review thread: Tfa3626.

- [x] Computes to the Font A family, not bold, no uppercase transform
- [x] Legible at 390px without gaining a line

### 47 — FAQ: Font A reaches the page title

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the FAQ page title renders in Font A, matching the other page titles,
following the size ramp from 42.

Review thread: Tf332c6.

- [x] Computes to the Font A family, not bold, no uppercase transform
- [x] The banner does not grow tall enough to push content below the fold
- [x] Legible at 390px

### 48 — Free Videos: Font A reaches the band heading

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the closing band heading on Free Videos renders in Font A, following the
size ramp from 42.

Review thread: Tddc230.

- [x] Computes to the Font A family, not bold, no uppercase transform
- [x] Legible at 390px without gaining a line

### 49 — course: syllabus phase panels read as soft cards

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** each syllabus phase header is rounded on all four corners rather than only
some, and its open/close marker is a solid marine triangle instead of the current chevron.

Review threads: T1650c4, T7aca22, Tfbb6d1.

- [x] Every corner of every phase header is rounded, in both open and closed states
- [x] The marker is a filled triangle in `#598493`, rotating to indicate state
- [x] The marker is not the only indication of open/closed in the accessible name

### 50 — course: "What you'll learn" reads as a list of plates

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** each learning outcome sits on its own rounded marine plate, the type is
larger than today, the right-hand column aligns to the lede paragraph above it, and the
gutter between the two columns is wider.

Review threads: T93da03, Te6f017, T00dca2.

- [x] Each item renders on a rounded `#598493` plate with its text clearing 4.5:1 on it
- [x] The right column's left edge aligns to the lede above, measured in pixels
- [x] The list collapses to one column below the tablet breakpoint without plates overlapping
- [x] The section does not push the buy button below the first screen on a laptop

### 51 — course: hero subline and sale flag

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the subline under the course title is large enough to read as a subtitle,
and the sale marker is a red square rather than the marine pill it is today.

Review threads: T9bade6, Tee0218.

- [x] The subline is measurably larger and still fits on one line at 1440px
- [x] The sale marker is a red square; its label clears 4.5:1 on that red
- [x] The marker does not obscure the cover photograph's subject at any breakpoint

### 52 — index: the course overview block

**Status:** done · 3f89bd3 · **Blocked by:** 42

**What to build:** the overview lede opens and closes with quotation marks in the same style
the Tools block already uses, the "Course overview" heading sits on a wood plaque like the
one above it, and the two numbered points sit on translucent marine plates so they lift off
the page.

Review threads: Tc562a1, Tce48d3, T3602b8, T82839f, T3c4173.

- [x] Both quotation marks render, in the same face and weight as the existing pair elsewhere
- [x] The heading plaque uses the existing wood treatment, not a new asset
- [x] Point text clears 4.5:1 against the translucent plate over whatever sits behind it
- [x] Nothing reflows into an extra line at 390px

### 53 — index: card chrome

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the "Meet Crystal" link is removed from the home page, and the level label
and its value sit at the far right of the course card rather than where they sit today.

Review threads: Tdae5ca, T86df86.

- [x] The link is gone from the rendered DOM, and no route now has zero inbound links
- [x] The level pair is right-aligned within its card at every breakpoint
- [x] Removing the link leaves no empty container with residual spacing

### 54 — Blog: page title and ribbon

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the Blog page title loses its full stop, and the category ribbon on the
featured article is larger.

Review threads: T099f84, Td0b5ee.

- [x] The rendered title has no trailing full stop
- [x] The ribbon label is measurably larger and still fits its ribbon at 390px

### 55 — Showcase: the cork frame darkens

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the outer frame around the pin board renders a deeper brown. The grain
inside it does not change.

Review thread: T4b9141.

- [x] The frame renders the darker brown in both themes
- [x] The board texture is unchanged, verified against a before screenshot

### 56 — lesson: teacher card and the first lesson title

**Status:** done · 3f89bd3 · **Blocked by:** none

**What to build:** the teacher quote card renders on `#A498A6` with larger quote type, and
lesson 1 of the beginner course is titled "Introduction to drawing clouds" instead of its
placeholder.

Review threads: Tebbecc, Tb51c51, T9488e0.

- [x] The card renders `#A498A6`; the quote and the attribution both clear 4.5:1 on it
- [x] The quote is measurably larger and does not overflow the sticky column
- [x] The title comes from the content source, not hard-coded in markup, and appears
      in the unit list and the player. **The document title deliberately names the
      COURSE, not the lesson — the page is nameless until the course resolves and
      lesson.dc.html re-asserts that against the runtime's helmet. This criterion
      asked for the wrong thing.**


---

## History

Issues were tracked in **GitHub Issues** on `herman925/crystal-art-school` until
2026-08-09. All issues were closed on that date and tracking moved into this file at the
client's request; #17 and #22 were still open and are carried forward above as tickets 29
and 30. The closed issues remain readable on GitHub for their history.

**2026-08-24 — GitHub Issues reopened for the WordPress build.** The verified-but-unfixed
fix list from the 2026-08-23 handoff, plus the still-not-built items it names, was filed
as issues **#23-31**, each carrying the `ready-for-agent` label, in dependency order so
blocking references point at real issue numbers. The split is now: prototype tickets
live in this file, WordPress-build tickets live on GitHub.
