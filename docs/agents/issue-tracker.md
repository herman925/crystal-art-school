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

## Where these came from

A 16-surface technical audit on 2026-08-09 (15 pages plus the shared nav/footer/motion
chrome), one auditor per surface, every finding measured against the rendered page rather
than read from source.

**Mean score 9.75 / 20.** Gallery 7 · course 8 · Login 8 · Signup 8 · Showcase 9 ·
shared chrome 9 · index 10 · Lessons 10 · lesson 10 · blog-post 10 · Dashboard 10 ·
Checkout 10 · Blog 11 · About 11 · FreeVideos 11 · FAQ 14.

Severities below are the audit's: **P0** blocks a task, **P1** serious, **P2** moderate,
**P3** minor.

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

**Status:** ready-for-agent · **P1** · **Blocked by:** none

**What to build:** a decision, then the code to match. The "Developer's note" callout is
dead — no post in `js/blog-data.js` carries a `devnote` field, and `.bp-devnote` matches
zero elements on all three posts — while the copy corrections it existed to declare
(masking fluid, Winsor, Claessens, squirrel) **are live**.

So the client's text is altered on the page with the on-page declaration removed. That is
the opposite of the arrangement recorded when the corrections were made.

- [ ] Either the note renders again on the posts it describes, or the corrections are reverted to her wording
- [ ] If the note stays dead, the CSS and the render branch are deleted rather than left
- [ ] The decision is recorded with a date

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

**Status:** ready-for-agent · **P1** · **Blocked by:** none

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

**Status:** blocked · **Blocked by:** the client

Migrated from GitHub issue #22 on 2026-08-09. **The default is hold** — both rows are
currently untouched and stay that way unless the answer is yes.

The two rows are the home-page social strip label (`.30em → .06em`, an 80% reduction on a
two-word label the client's board never named) and the New Release chip (`.16em → .06em`,
where the direction asks for the chip to be *enlarged*, so tightening without a
compensating size bump risks reading as the opposite). Full reasoning is in
`docs/20260804_Design_Direction.md`.

- [ ] Client answers on both rows
- [ ] If yes on the chip, roughly +1px of font size covers the lost width

---

## Done

_Nothing yet from this audit._

---

## History

Issues were tracked in **GitHub Issues** on `herman925/crystal-art-school` until
2026-08-09. All issues were closed on that date and tracking moved into this file at the
client's request; #17 and #22 were still open and are carried forward above as tickets 29
and 30. The closed issues remain readable on GitHub for their history.
