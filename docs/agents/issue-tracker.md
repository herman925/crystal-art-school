# Issue tracker

Tickets for this repo live **in this file**, not in GitHub Issues. GitHub Issues are
closed and no longer used — see "History" at the bottom.

Work the **frontier**: any ticket whose blockers are all done. Tick the acceptance
criteria as they land; move the ticket to "Done" with its commit SHA when every box
is ticked.

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

## Ready

### 01 — Forms actually validate

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** every form on the site refuses to submit when a required field is
empty, and says why. Today an entirely empty login authenticates you as "Friend" and
redirects to the dashboard; an entirely empty checkout places an order.

The cause is the `.dc.html` runtime dropping valueless attributes, so `required` is
present in source and absent from the rendered DOM (`minlength="8"`, which carries a
value, survives). Restoring the attribute is necessary but not sufficient — native
validation messages are not announced, and `Checkout.dc.html` has no JS validation path
at all.

- [ ] `hasAttribute('required')` is true in the rendered DOM for all 7 checkout inputs, both auth pages, and the comment form
- [ ] `form.checkValidity()` returns false on an empty form on every one of those
- [ ] An invalid submit moves focus to the first invalid field and announces the reason
- [ ] Empty login does not write `cas-user`; empty checkout does not reach the confirmation step

### 02 — Typed text is visible in the dark theme

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** text you type into a field is legible. `.co-field input` sets
`color: #2b2823` **and** `-webkit-text-fill-color: #2b2823` over `var(--cream)`, which in
theme D is `#211e1a` — measured **1.13:1**, proven by pixel sampling (no pixel in a filled
field is darker than its background). Dark is the default theme. The same construct is
duplicated for `.field input, .field textarea`.

Affects the card number, address and password fields.

- [ ] Field text and placeholder both clear 4.5:1 in themes A and D, measured from painted pixels
- [ ] The dead first `color` declaration in that rule is removed rather than left shadowed
- [ ] Both copies of the construct are fixed, not just the first

### 03 — `motion.js` runs every time, or not at all

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** the motion layer behaves the same on every page load. It binds to
`DOMContentLoaded`, but the runtime injects it after that event has fired, so it is a
race: instrumented over 10 loads it ran **2/10** on About and **3/10** on Lessons.
Dispatching a synthetic `DOMContentLoaded` on a losing load immediately produced the
missing 42 ScrollTriggers and the headline word-split.

This is silent — the page looks finished either way — which is why it has survived.
`js/site-extras.js` already does this correctly with a `readyState` check; copy that.

- [ ] 20 consecutive loads of About and Lessons produce an identical ScrollTrigger count
- [ ] The fix is a `readyState` guard, matching `site-extras.js`
- [ ] Reduced-motion still bails out before any of it runs

**Note for whoever takes this:** `motion.js` also disables itself when
`navigator.webdriver` is true or the UA contains "Headless". Any Playwright check of
motion must spoof both, or it measures a static page and passes for the wrong reason.

### 04 — The basket and the payment screen agree

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** one source of truth for the cart. The header mini-cart drawer and
`Checkout.dc.html` both write `cas-cart`, and the page has no listener for the drawer's
writes (the `storage` event only fires cross-tab). Reproduced: open the drawer on the
checkout page, increment a line, close it — the drawer reads £230, the page still reads
**£200**, `localStorage` holds `[3,1]`, and the payment step then reads **£248**.

- [ ] Changing quantity in the drawer updates the checkout totals in the same tab, immediately
- [ ] The reviewed total and the charged total are provably the same value, not two computations
- [ ] `#ship-1` does not show £18 on an empty basket

### 05 — The checkout is honest about being a demo

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** nothing on the purchase flow should read as a real transaction.
The "Demo only — no real payment is taken" note lives on step 2 and is gone by step 3,
which renders an order number, a total, and *"A confirmation is on its way to your inbox"* —
there is no email field anywhere in the form.

Separately, `£18 Shipping (framed, insured)` and *"ships framed and insured within 5–7
days"* are applied to whatever is in `cas-cart`, which `site-extras.js` also fills with
video courses. Two courses currently total £182 + £18 postage.

- [ ] The demo marking is present on every step including the confirmation
- [ ] No claim about email, delivery or fulfilment appears unless it is true of what was bought
- [ ] Shipping is not charged on digital items
- [ ] A terms/privacy link is reachable from the checkout (the `checkout` footer variant drops the column that carries it)

### 06 — The catalogue works on a touch device

**Status:** ready-for-agent · **P0** · **Blocked by:** none

**What to build:** on `Lessons.dc.html`, tapping a course card opens the course. Today
`.cover-card .actions` is `opacity: 0; pointer-events: none` until `:hover`, and the only
link into a course lives inside it. At 390px with a coarse pointer, `elementFromPoint` at
the centre of both buttons returns the title scrim and a click times out.

Four other components in the same stylesheets already ship an `@media (hover: none)`
unhide (`industrial-1.css:625, 714`; `industrial-2.css:54, 260`).

- [ ] At 390px with `hover: none`, both card buttons are hit-testable and a click navigates
- [ ] The same check passes on every component that hides an action behind `:hover`
- [ ] Desktop hover behaviour is unchanged

### 07 — Nothing invisible is in the tab order

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** hidden UI is hidden from the keyboard too. Measured on every page: 12
tabbable controls inside `.ck-modal` (`opacity: 0; pointer-events: none`, full-viewport
rect), `.ck-bar` (translated off-screen) and the closed `.mc-drawer`. None carries `inert`,
`aria-hidden`, `visibility: hidden` or `display: none`.

Also here: inactive carousel slides on `index.html` keep 6 tabbable controls, and the
Gallery, Showcase, FreeVideos and About lightboxes are all `role="dialog" aria-modal="true"`
with no accessible name, no focus move-in, no trap, and no focus restore on close.

- [ ] A live tab walk from the top of each page never lands on a control whose computed opacity chain is 0 or that is outside the viewport
- [ ] Every lightbox takes focus on open, traps it, restores it on close, and has an accessible name
- [ ] The cart drawer traps focus (it already moves and restores it correctly)

### 08 — A real focus indicator, and stop clipping it

**Status:** ready-for-agent · **P1** · **Blocked by:** none

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

- [ ] A tokened focus style, ≥3:1 against every surface it lands on, in both themes
- [ ] Pixel-diff proves a ring paints on `a.syl-row` and on `summary.acc-item`
- [ ] `color-scheme` is declared per theme

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
- [ ] The fix is at the token/role level, not 15 local overrides
- [ ] Stale ratio comments in the CSS are corrected to what actually paints

### 10 — Page identity: title, lang, skip link

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** `document.title` is `""` and `documentElement.lang` is `null` on **all
15 pages**; no page has a `meta description`; no page has a skip link, though every one
has `<main id="content">`. First focusable element is the brand, then 8 nav links — 10 tab
stops to reach content, on every page.

- [ ] Every page has a unique, descriptive `<title>` and a `lang`
- [ ] A skip link is the first tab stop on every page and moves focus, not just the viewport
- [ ] `blog-post.dc.html` sets its title from the post data

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
- [ ] Placeholder content is visibly marked as placeholder, or absent
- [ ] Demo state is not written into storage keys that real state would use

### 12 — Blog: the copy note and the copy it declares

**Status:** MECHANISM FIXED 2026-08-09 · decision still with the client · **P1**

The note renders again, and it now names every change rather than gesturing at
them: the six material and instruction corrections on post 1, the two sentences
on post 2 that are ours, and the one clause plus the tightened pull-quote on
post 3. Each note ends "This note stays until she signs it off."

Verified against her source documents: all six corrections are present on the
site and her original wording is gone in every case, and the three sentences the
notes claim as ours appear in none of her three documents.

**Still with the client:** approve the corrections, or the copy is restored to
her wording. The note must not be deleted before one of those happens.

**What to build:** a decision, then the code to match. The "Developer's note" callout is
dead — no post in `js/blog-data.js` carries a `devnote` field, and `.bp-devnote` matches
zero elements on all three posts — while the copy corrections it existed to declare
(masking fluid, Winsor, Claessens, squirrel) **are live**.

So the client's text is altered on the page with the on-page declaration removed. That is
the opposite of the arrangement recorded when the corrections were made.

- [ ] Either the note renders again on the posts it describes, or the corrections are reverted to her wording
- [ ] If the note stays dead, the CSS and the render branch are deleted rather than left
- [ ] The decision is recorded with a date

**Decision, 2026-08-09:** the note renders again on all three posts and the copy
corrections stay. Reverting them would publish a known error — "masking fluid" is
what the client's own painted label reads, and Winsor & Newton and Claessens are
the manufacturers' spellings. The wording of the note is new, not the original:
that text was removed on 2026-08-07 (`ff37d09`) because it described the source
material, and this repository is public. Each note now states what was changed and
that it is not yet approved, and nothing else. Recorded in the header of
`js/blog-data.js`, beside the field that drives it.

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
- [ ] No element renders a background chip with no content
- [ ] The collage question is settled: either the data carries mixed ratios and the collage CSS is used, or that CSS and its motion target are deleted

### 14 — Stated behaviours that do not happen

**Status:** ready-for-agent · **P1** · **Blocked by:** none

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

- [ ] Each item above is either made to work or removed, with the comment corrected
- [ ] Logging out is possible from every page
- [ ] No JS references an element id that does not exist

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

### 16 — Locked lessons: contrast and announcement

**Status:** ready-for-agent · **P1** · **Blocked by:** 08

**What to build:** `course.dc.html` emits `style="opacity:.72"` inline on every locked
syllabus row — 12 of 13 rows in the default not-purchased view. Painted contrast **2.55:1**
(theme A) / 3.00:1 (theme D) against 4.80 / 6.13 for the unlocked row. Being inline, it
outranks every stylesheet remedy.

The padlock is an `aria-hidden` SVG with no text alternative, so the accessible text of a
locked row contains no indication that it is locked — while the free row does say
"FREE PREVIEW".

- [ ] Locked rows clear 4.5:1 in both themes, measured from painted pixels
- [ ] "Locked" is in the accessible name or an adjacent text node
- [ ] The dimming is not an inline style

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

### 18 — Touch targets

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** measured at 390px, essentially every control on the site is under
44×44. Worst: `.rel-dots button` 8×8, `.phc-dot` 9×9, `.li-remove` 21px tall,
`.qty button` 28×28, `.social-btn` 30–36px, `.cart-btn` and `.mode-toggle` 38×38,
`.nav-burger` 40×40, `.fopt` 39px, `.cover-btn` 36px. `industrial-3.css:365-368` sets
`min-height: 40px/42px` under a coarse pointer — deliberately below 44.

The carousel dots also fail as indicators: measured 1.56:1 (A) / 2.08:1 (D) against the
page, under the 3:1 needed for a non-text control.

- [ ] Every interactive control is ≥44×44 at 390px, or has a ≥44×44 hit area
- [ ] The coarse-pointer minimums are raised to 44
- [ ] Pagination dots clear 3:1 in both themes

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
- `blog-post.dc.html` — `.bp-devnote`, `.bp-hero`, `.toc ol ol`, `.article figcaption`
- `About.dc.html` — 12 elements carrying a `reveal` class that matches no rule; `.cert-grid`, `.cert-card`, `.story-layout`, `.about-social`
- `Showcase.dc.html` — the `.sw-*` collage rules and `#sw-empty`
- `Lessons.dc.html` — 16 hand-written filter chips overwritten before paint; `.price .was`
- `Dashboard.dc.html` — `.nav-user` rules, `logout-btn`, `logout-sheet`

- [ ] Each item is deleted or made live, none left in place
- [ ] After deletion, a brace-balance check passes on all three stylesheets
- [ ] The nav markup exists in exactly one place

### 20 — Responsive breaks

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** four measured layout failures that no overflow check catches, because
`.cas-root { overflow-x: hidden }` swallows them.

- **`blog-post.dc.html` banner is 39% clipped between 701 and 1221px.** `aspect-ratio: 48/9`
  plus `min-height: 220px` makes Chrome resolve width *from* the min-height — a fixed
  1173px. At 760px the container is 712px.
- **`index.html` New Release title overlaps its own metadata by 31px on touch**, at 390,
  760 and 1100. Both sit at `bottom: 58px`; the lift is `:hover`/`:focus-within` only,
  while `@media (hover: none)` forces the facts visible.
- **`About.dc.html` portrait carousel ignores its mobile height.**
  `.ph-carousel.is-portrait .phc-cell img { height: 560px }` (0,3,2) outranks the 900px
  media query (0,2,2) — 560px in a 358px cell at 390px.
- **`course.dc.html` syllabus collapses at laptop width** — 560px of content with 270px of
  empty gutter each side at 1100px, because the left column is pinned until the 900px
  breakpoint.

Also: the nav collides with itself at 1100px (`Free videos` × cart 24px, `FAQ` × cart 12px,
`FAQ` × `Log in` 17px), and the Dashboard tab rail scrolls off-screen with no affordance.

- [ ] Each of the above measures correctly at 390 / 760 / 1100 / 1692 in both themes
- [ ] A width sweep checks element rects against their containers, not just document scrollWidth

### 21 — Autoplay needs a stop

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** WCAG 2.2.2 — moving content over 5 seconds shown alongside other
content needs a user-operable pause. Three auto-advancing carousels on About (measured
5.3s / 6.7s / 8.1s), two on `index.html` (6s each). They correctly pause on hover, focus
and hidden tab, and correctly do not run under reduced motion — but there is no persistent
control.

- [ ] A visible, keyboard-operable pause/play on every auto-advancing component
- [ ] State is exposed (`aria-pressed`), and the dots expose `aria-current`

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
- [ ] Toggle controls expose their state

### 23 — Tabs, lists and dialogs that lie about what they are

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** correct roles. Measured:

- **Dashboard** — `role="tablist"` wraps six plain buttons: no `role="tab"`, no
  `aria-selected`, no `role="tabpanel"`, arrow keys dead, and the active tab differs from
  the inactive ones by colour alone. Putting `role="tablist"` on the `<aside>` also
  destroys its `complementary` landmark.
- **About** — `role="listitem"` on `<button>` overwrites the implicit button role on all 10
  certificate rows, so the two working controls announce as list items.
- **About** — `.cert-info` is the mobile affordance but ships as
  `<span role="presentation" aria-hidden="true">` styled with `cursor: pointer` and a hover
  state, while the real control gets `cursor: default` at the same breakpoint. It also
  shows a pointer on the 8 disabled rows.
- **Shared nav** — `role="menu"` + 6 × `role="menuitem"` on plain links, with no
  `aria-haspopup`, no `aria-expanded`, and no arrow-key handling. The drawer opens on
  `mouseover`/touch only, so it is unreachable by keyboard above 860px.
- **Showcase** — `aria-label` on a bare `<div>` with no role, so the name is discarded.
- **Gallery** — active-filter removal chips are `<span>` with a click handler,
  `tabIndex: -1`.

- [ ] Roles match behaviour, or are removed
- [ ] The Courses drawer is keyboard-reachable
- [ ] Every control with a pointer cursor is a control

### 24 — Escape and outside-click close the burger sheet

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** at 390px the mobile sheet stays open after `Escape` and after a real
bubbling click on `<main>` — verified on three pages in both themes. `aria-expanded` stays
`"true"`. Only a second burger tap closes it. There is no keydown handler and no
outside-click branch.

- [ ] Escape closes the sheet and returns focus to the burger
- [ ] A click outside closes it
- [ ] `aria-expanded` tracks the real state (it already does for the button itself)

### 25 — Leaks and layout thrash

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** three measured runtime problems.

- **Blog leaks a listener per filter click.** `document._blogDrive` is assigned nowhere, so
  the removal never fires, and the resize binding has no removal at all. Instrumented: 6
  chip clicks took document-capture scroll listeners from 3 to 9 and resize from 3 to 9,
  each calling `getBoundingClientRect()` on every card. `drive()` already runs twice per
  scroll at baseline from double-binding.
- **Gallery rebuilds the entire grid on every slider `input`.** 20 synthetic events =
  316 nodes removed, 101.5ms, 5.08ms each, all 34 images destroyed and recreated per
  pointer sample. The source comment says "filter only on release"; the handler is wired to
  `input` as well as `change`.
- **About's resize handlers interleave reads and writes** — `measureEngraved` loops
  `getBoundingClientRect()` then `style.setProperty`, and `relayout` is registered three
  times, all unthrottled.

Also: the nav guard leaves a permanent 400ms interval, after 300 ticks of `querySelector`
in the first 15 seconds.

- [ ] Listener counts are stable across 20 filter interactions
- [ ] The price slider filters on release, as its comment says
- [ ] Resize work is batched into one rAF

### 26 — The site survives a CDN outage

**Status:** ready-for-agent · **P2** · **Blocked by:** none

**What to build:** `support.js` loads React and ReactDOM from `unpkg.com` and mounts the
entire `<x-dc>` body. With `unpkg.com` blocked, `document.body.scrollHeight === 0` and the
page contains only the cookie banner. By contrast, blocking `cdn.jsdelivr.net` (GSAP)
degrades gracefully — everything renders at opacity 1.

Five external origins on a fully static site: unpkg, jsdelivr, and two Google Fonts hosts.

- [ ] The pages render their content without a third-party CDN, or the dependency is vendored
- [ ] Verified by blocking each origin in turn

### 27 — Reading measure on the article pages

**Status:** ready-for-agent · **P3** · **Blocked by:** none

**What to build:** `blog-post.dc.html` body copy measures **104 characters per line** at
1692px, counted with `Range.getClientRects()` rather than estimated. `.article`'s 760px at
16.5px is ~46em, roughly 40% above the 65–75 ceiling for sustained reading. FAQ answers
measure 118 characters. Mobile is fine (45–48).

`.page-lead` on the same FAQ page is correctly capped at `74ch`, so the target already
exists in the same file.

- [ ] Body copy on the two Read-mode surfaces measures 65–75 characters at every width ≥900px
- [ ] Heading sizes do not invert (`Comments` at 34px currently outranks every content section heading at 26px)

### 28 — Theming holes

**Status:** ready-for-agent · **P3** · **Blocked by:** 09

**What to build:** components that do not respond to the theme toggle. Each measured
byte-identical in A and D, or measured painting far from its own token:

- **Showcase's entire wall** — 14 literal colours, 0 token references in 40 lines
- **The Courses mega drawer** — hard-coded `#26221e` / `#cf9457` / `#ede1d0` in
  `site-nav.js`; a near-black panel on a cream nav bar
- **FreeVideos' lightbox** — 35 raw literals against 44 token uses; A and D screenshots
  differ only in the corner antialias
- **Dashboard's settings fields** — `#f3ede1` / `#2b2823`, cream in both themes, with a
  border at ~1.03:1 against their own fill; and the danger zone at **2.40:1** in dark
- **`course.dc.html`'s `.co-highlight`** — `#1b1814`, a 900×506 black rectangle above the
  fold on the cream theme
- **Blog's leather** — only `background-color` is themed; the light token `#d2cec7`
  (L 0.620) paints at `#a39787` (L 0.317), so the card sits at 2.52:1 against the light page
- **Lessons** — 6 component families frozen, including hard-coded `-webkit-text-fill-color`
- **blog-post's author card and pull-quote** — `#cab389` / `#5e4a3d`, light tan on the dark page

Some of these are the documented "material exemption" (copper, oak, leather hold their
colour). That rule needs restating so it is clear which of the above are exempt and which
are drift — and the exempt ones still have to pass contrast.

- [ ] Every component above is either tokened or listed as a material exemption with a measured contrast pass
- [ ] The material-exemption rule is written down in `docs/03-design-system.md`

---

### 57 — course: syllabus thumbnails render in colour

**Status:** ready-for-agent · **Blocked by:** 16

**What to build:** every syllabus thumbnail renders in full colour, whether or not the lesson
has been bought. Locked rows currently desaturate their thumbnail.

That desaturation is one of the cues that a row is locked, so this ticket depends on 16
having made the locked state textual first — otherwise removing it leaves the padlock and
the price as the only indication, and the padlock is currently `aria-hidden`.

Review thread: Ta27f4c (colour half; the "these should be the lesson cover photos" half is
ticket 60).

- [ ] No thumbnail has a saturation filter applied in either state
- [ ] "Locked" is still conveyed in text, not by the image treatment alone
- [ ] The change is verified in the default not-purchased view, not only when signed in

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

- [ ] Transparent brand artwork (SVG → transparent PNG → AI). All three marks are currently 0.0% transparent, which is why the light-theme invert rules are commented out and why the signature draws a white box. `tools/verify-round-2.py` fails automatically when transparency arrives, so this cannot be forgotten.
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

**Status:** blocked · **Blocked by:** the client

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

**Status:** blocked · **Blocked by:** 58, 53

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

**Status:** blocked · **Blocked by:** 58

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

**Status:** blocked · **Blocked by:** 58

**What to build:** the Wanderer, Apprentice and Fellow cards on the catalogue each carry
their own photograph, and the free-trial band carries its own.

Review threads: Tcdf2de, Tf171ca, Tb9c441, T9779e5.

- [ ] Each of the three tier cards shows a distinct image with a meaningful alt
- [ ] The band image is the one chosen for it, not a repeat of a tier card
- [ ] The cards keep equal height across the row at every breakpoint

### 62 — About: sixth live drawing, landscape certificates, article background

**Status:** blocked · **Blocked by:** 58

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

**Status:** blocked · **Blocked by:** 58

**What to build:** the header mark and the banner mark are replaced with the client's final
webp exports. Both were already acknowledged in the review as coming in this round.

Review threads: Tce2f9f, T16b3b6. Note that ticket 29 records the transparency problem on the current
marks — if the new exports are also fully opaque, the light-theme invert rules stay
commented out and this ticket does not close it.

- [ ] Both marks render from the new files at every breakpoint
- [ ] Transparency is measured, and `tools/verify-round-2.py` is run
- [ ] The old files are removed, not left orphaned in `img/`

### 64 — Video wiring: instructional films, shorts, reference photos

**Status:** blocked · **Blocked by:** 58

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
- [x] In theme D both are byte-identical to before the change
- [x] Band ink, eyebrow and CTA all clear 4.5:1 against the new lighter ground

### 32 — course: the wood band and the footer go pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the free-trial band and the footer on the course page follow the pale
light-mode wood established in 31.

Review threads: Tc3e09f, T7bcb32.

- [x] Both surfaces render the pale wood in theme A, dark theme unchanged
- [x] Every text colour inside them clears 4.5:1 on the new ground

### 33 — Blog: the footer goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the Blog footer follows the pale light-mode wood established in 31.

Review thread: Tc18755.

- [x] The footer renders the pale wood in theme A, dark theme unchanged
- [x] Footer text and links clear 4.5:1 on the new ground

### 34 — Showcase: the wood band and the footer go pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the submissions band and the footer on Showcase follow the pale
light-mode wood established in 31.

Review threads: Tbc990f, Tbc97d4.

- [x] Both surfaces render the pale wood in theme A, dark theme unchanged
- [x] Every text colour inside them clears 4.5:1 on the new ground

### 35 — About: the footer goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the About footer follows the pale light-mode wood established in 31.

Review thread: T175475.

- [x] The footer renders the pale wood in theme A, dark theme unchanged
- [x] Footer text and links clear 4.5:1 on the new ground

### 36 — FAQ: the wood band goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the "ask me directly" band on FAQ follows the pale light-mode wood
established in 31.

Review thread: T85e252.

- [x] The band renders the pale wood in theme A, dark theme unchanged
- [x] Band ink, eyebrow and CTA clear 4.5:1 on the new ground

### 37 — Free Videos: the wood band goes pale in light mode

**Status:** done · 3f89bd3 · **Blocked by:** 31

**What to build:** the "full courses go deeper" band on Free Videos follows the pale
light-mode wood established in 31.

Review thread: T18a98d.

- [x] The band renders the pale wood in theme A, dark theme unchanged
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
