# 04 — Pages Reference

Per-page detail: what it shows, the notable markup, the data it reads, and the behaviour
in its `Component` class. Every page also carries the shared nav (two rows) + mobile sheet
+ footer, forces theme D, wires the burger, and renders the cart badge — those are not
repeated below.

Shared chrome recap:
- **Nav row 1** (`.nav-top`): brand logo (`img/LAULogoFinal.png`) + main links
  (Home, Courses, Gallery, Showcase, Blog, About, Free video, Tools, FAQ) + cart / Log in /
  Sign up / burger.
- **Nav row 2** (`.nav-cats`): category deep-links (Watercolour, Oils, Drawing, Ink,
  Beginners → `Lessons.dc.html?cat=…`).
- **Footer** (`.site-footer`): brand blurb + Learn / Explore / Contact columns.

---

## `index.html` — Home

The richest page and the only one with fully bespoke GSAP scene choreography (inline, not
from `motion.js`).

Sections:
1. **Hero** (`.hero--compact`) — a 3-slide Crafti-style left-text / right-image panel.
   6s autoplay, prev/next arrows, a `01 / 03` counter. GSAP does a clip-path image wipe +
   headline rise on load, and a depth parallax on scroll. Content is visible by default
   (animation is enhancement).
2. **New Release trio** — a 3-up: free-video tile (links to `lesson.dc.html`), a
   mini-carousel of the 3 newest courses (auto-rotating, with tier-picker "Add to cart"),
   and a twin-arch About portrait tile.
3. **Shop all courses** — a paged horizontal rail of 10 randomly-shuffled course cards
   (3 visible) + a tall gallery promo tile.
4. **Share with you** — a paged rail of blog/gallery cards + a circular "TOOLS" filter
   button (spins in) linking to `Blog.dc.html?tag=Tools`.
5. **Community band** — Fellow-tier Discord CTA (walnut band).
6. **Testimonials** — 3 student quotes (pop-in stagger).
7. **Social strip** — Instagram / YouTube / Facebook / email.

Data: `coursePool()` (12 courses) and `courseHref()` (title → slug map) in the Component.
Notable: the whole scroll choreography is a hand-rolled GSAP `scene()` system with a
`drive()` scroll driver; `motion.js` detects the hero (`.hero__slide`) and **bows out** so
it doesn't double-animate the home page.

---

## `Lessons.dc.html` — Course catalog

Sections:
1. **Page hero** (oak wood banner) — "Pick up a brush."
2. **Catalog** — a left filter rail + a `.lesson-grid` of 9 hard-coded course cards.
   Filters (each a `.fgroup`): Medium, Theme, Difficulty, Length, Offers, plus a Max-price
   slider and a Sort card (Featured / price ↑ / price ↓). Filtering is attribute-based
   (`data-cat`, `data-theme`, `data-difficulty`, `data-duration`, `data-sale`, `data-price`
   on each `.lesson-cell`). Cards deep-link to `course.dc.html?id=…` and "Add to cart" opens
   the tier picker.
3. **Membership** (`#membership`) — three tiers: Wanderer (free), Apprentice (per-course),
   Fellow (per-course + Discord).
4. **Free-intro band** — CTA to the free lesson.

Deep-link: `?cat=<medium|beginners>` from the nav category row auto-clicks the matching
filter on load. Price slider auto-computes a "nice" range from the cards' prices.

---

## `course.dc.html` — Course detail

**Fully data-driven by `?id=<slug>`.** The Component holds a `courses()` object keyed by
slug (9 courses: `materials-setting-up`, `light-shadow-head`, `blocking-large-shapes`,
`mixing-greens`, `soft-edges-clean-paint`, `loading-a-brush`, `warm-cool-greys`,
`erasing-drawing-tool`, `brush-care-habits`). Unknown/missing id falls back to
`materials-setting-up`.

Renders: breadcrumb, cover (with Free/Sale flag), title + blurb + meta chips, buy row
(tier-picker "Add to cart", or "Start the free course" if free), a "what you'll learn"
grid, a unit-by-unit **syllabus**, a teacher card, and 3 related courses (same-tag first).

Each course record: `{ title, tag, level, len, units, img, price, was?, free?, blurb,
learn[], syllabus[[title, sub, len], …] }`.

---

## `lesson.dc.html` — Free lesson player

A **simulated** 6-unit watercolour intro course ("Materials & setting up"). There is no
real video — a scripted timer advances a progress bar at 12× speed over a poster image
with a Ken-Burns + sheen effect.

- **Player**: play/pause, seek bar (clickable), timecode, unit flag, "Unit complete"
  toast. Poster + Ken-Burns while "playing".
- **Units** (`UNITS` array): 6 units with title/duration/image. A right-rail unit list
  lets you jump between them; each has expandable notes + a "Crystal's note" tip + a
  downloadable resource (a `data:text/plain` file, so downloads work with no backend).
- **Progress** persists in `localStorage('cas-free-lesson-progress')` as
  `{ unit, t, done[6] }`. "Mark complete & continue" advances; finishing all 6 shows a
  completion certificate card. "Restart course" and "Watch again" reset it.

This page's real progress feeds the Dashboard's free-course completion count.

---

## `Gallery.dc.html` — Artwork store (Etsy-style)

A framed-artwork shop. Component holds `pieces()` — 12 artworks, each
`{ title, img, tags[], price, date, orig (original vs print), size, desc }`.

- **Left rail**: a Sort card (Newest / Oldest / Price ↑↓) + a Filter card (Medium
  multi-select, Subject multi-select, Max-price slider). Active tags show as removable
  chips. `?tag=<medium|subject>` deep-links a filter.
- **Grid**: each artwork hangs in a **real CSS frame** (mat + 4-tone bevel) on a textured
  wall; in theme D an ornate carved deer-frame SVG overlay replaces the bevel and adapts to
  the picture's aspect ratio (`--r` custom property per card).
- **Actions**: "Buy on Etsy" → opens an Etsy search for the piece in a new tab (the
  Phase-1 commerce bridge — no native artwork checkout). "View details" → a quick-view
  modal (large framed image, description, spec list, price, Etsy buy + Add to cart).
- Add-to-cart here pushes `{ title, price, img, kind }` into the shared cart.

---

## `Showcase.dc.html` — Student showcase

A justified, Google-Photos-style wall of student work. Component holds `pieces()` — 18
entries `{ img, ar (aspect ratio), name, med, title }`.

- The wall is a flexbox where each tile's `flex-grow`/`flex-basis` is driven by its aspect
  ratio (`--ar`), so mixed portrait/landscape/square tiles pack into justified rows of
  equal height (a trailing `::after { flex-grow: 1000000 }` stops the last row stretching).
- **Medium filter** (All / Watercolour / Oils / Drawing / Acrylic).
- **Hover** reveals the student's name + a colour-hashed avatar initial; **click** opens a
  lightbox. Student work is community, not for sale — no Buy button (contrast with Gallery).

---

## `Blog.dc.html` — Journal index

Component holds `posts()` — 12 posts `{ title, excerpt, img, tag, date, d (short date) }`.
Shows the **newest 10**, sorted by date.

- A tag filter row (All + each tag). `?tag=Tools` deep-links (used by the home "TOOLS"
  button and the nav "Tools" link).
- A **featured** card (the latest) + a grid of the rest. GSAP rolls each card in on scroll
  (alternating left/right drift). Empty-state message when a tag has no posts.
- All posts currently link to the single `blog-post.dc.html` article (demo).

---

## `blog-post.dc.html` — Article

A single long-form article ("How to block in a portrait — value first"). Static content.

- **Banner** (`.post-banner`) — wide photo with a wood chip holding the H1.
- **Byline**, then a **hierarchical table of contents** (`.toc`) with smooth-scroll to
  section anchors.
- Body: H2/H3 sections, a figure, a **pull-quote**, an author bio card, a **comments**
  section (2 sample comments + a post form that shows a "waiting for review" note), and
  prev/next + related-rail.

---

## `About.dc.html` — About

- **Hero**: handwritten signature (`.sig-script`, Mrs Saint Delafield), twin-arch portrait
  (masked), intro copy, CTAs.
- **Biography** (`#biography`): two framed photos + bio copy + facts list + a
  "Training & credentials" polaroid row.
- **Contact** (`#contact`): a two-column contact panel — channels (email, Discord,
  Instagram, Etsy originals) + a contact form (shows a "sent" note, no backend).
- **Terms & privacy** (`#terms`): an accordion (`details.acc-item`) covering purchases &
  refunds, using materials, data, cookies.

Anchors `#contact` and `#terms` are linked from the footer across the site.

---

## `FAQ.dc.html` — FAQ

A single accordion of 6 common questions (how courses work, where to start,
Apprentice vs Fellow, materials, refunds, buying originals) + a "still curious? write to
the studio" band. GSAP animates the accordion open/close (from `motion.js`).

---

## `Login.dc.html` — Log in

A single auth card with staged views:
- **Login**: Continue-with-Google button, email + password, remember-me, forgot-password
  link, "Log in", and "Continue as guest".
- **Forgot password** → email entry → "check your inbox" success stage.
- **Success** → "logged in" stage with a link to the dashboard.

Demo auth: submitting sets `localStorage('cas-user')` (the email prefix becomes the name;
Google sets name "Jane"). `?next=<page>.html` redirects there after login (used by the
Dashboard gate).

---

## `Signup.dc.html` — Sign up

A 3-step wizard in one card:
1. **Account** — Google button (skips to step 2) or name/email/password.
2. **Focus** — pick a medium (Watercolour / Oils / Drawing) → tailors dashboard recs.
3. **Plan** — pick a tier (Wanderer / Apprentice / Fellow).
Then a **success** stage. On submit writes `localStorage('cas-user')` as
`{ name, plan, focus, newUser: true, since: 'Jul 2026' }`. A progress bar tracks the steps.

---

## `Dashboard.dc.html` — Member dashboard

**Login-gated**: if there's no `cas-user`, it `window.location.replace('Login.dc.html?next=Dashboard.dc.html')`.

A left tab rail + panels:
- **Overview** — a "continue learning" resume card (or an onboarding empty-state for brand-
  new accounts) + focus-matched recommended courses.
- **My courses** — enrolled course cards with progress.
- **Progress** — overall % + per-course bars.
- **Purchases** — an order-history table with (fake) invoice download.
- **Discord** — Fellow-gated: a join panel if the user has any Fellow-tier course/plan,
  else a locked upsell.
- **Settings** — editable name/email/focus, notification toggles, a Privacy button (opens
  the cookie/data-rights modal via `window.casOpenPrivacy`), and a Delete-account danger
  zone.

Demo data seeding: brand-new accounts (`newUser`) start empty (onboarding); returning demo
users get seeded `cas-enrolled` + `cas-orders` so the design shows populated. Free-course
progress is read from `cas-free-lesson-progress`. A toast confirms actions. Active tab
persists in `cas-dash-tab`.

### WordPress port (theme `hello-elementor-child`)

The prototype's demo storage does not exist on the site; the studio page is
`cas_account_dashboard_body()` in `inc/cas-account.php`, and the subpage work is in
`inc/cas-dashboard-pages.php` (#125).

- **One shell.** A student's GET request for Tutor subpages redirects to the studio page; mapped
  pages open the matching tab, while the removed Wishlist route goes to studio home. Instructors
  and admins keep Tutor's pages:

  | Tutor address | Studio tab |
  |---|---|
  | `/dashboard/courses/` (also `active-courses`, `completed-courses`, `my-courses`) | My courses |
  | `/dashboard/courses/wishlist/` | studio home; legacy route, saved Tutor data retained, no Wishlist panel |
  | `/dashboard/account/billing/` | Purchases |
  | `/dashboard/account/profile/`, `/dashboard/account/` | Profile |
  | `/dashboard/account/settings/` | Settings |
  | quiz attempts (`quiz-attempts`, `my-quiz-attempts`), reviews | studio home; not reachable (no quizzes at launch, #124) |

- **Purchases** is the `dash-table` built from Tutor's orders. The Receipt cell holds Tutor's
  own pay button and, with Tutor Pro's invoice add-on, its invoice button
  (`tutor_dashboard_invoice_button`). Newest 50 orders, no paging.
- **Settings** is Main focus (usermeta `cas_main_focus`) and Country (Tutor's billing row),
  plus Tutor's own Login Info / Reset Password card. Notification switches, Full name, Email,
  Privacy and Delete account are not built (owner decisions 2026-09-30; the site sends
  transactional mail only).
- **Profile and Settings need Tutor's settings component.** Tutor's dashboard script registers
  a page's Alpine components from the address, and `/dashboard/` only registers the home
  ones. The studio page therefore carries `?dashboard-page=settings` until the page has
  loaded, then restores the address (inline scripts added in `inc/cas-dashboard-pages.php`).
  Without this the profile save, photo upload and password reset do nothing.
- Toolbar: nobody without `manage_options` gets the WordPress admin toolbar (#140).

---

## `Checkout.dc.html` — Checkout

A 3-step flow driven by the shared cart:
1. **Basket** — line items (qty +/−, remove), order summary (subtotal + £18 shipping).
   Empty state if the cart is empty; the continue button disables.
2. **Details & payment** — shipping address + card fields (demo only, no real payment) +
   a mini order summary.
3. **Confirmation** — an order certificate with a random `#CAS-####` number; **clears the
   cart** (`cas-cart`) on completion.

Reads/writes the same `cas-cart` array as the drawer. Money is shown in GBP.

### WordPress port (theme `hello-elementor-child`)

The checkout step is Tutor's native eCommerce page, not the prototype's demo flow (#123).

- `tutor/ecommerce/checkout.php` is a **fork of Tutor 4.1.0's own `checkout.php`**. All Tutor
  ids, field names, nonce and hooks are kept; the wrapping is the prototype's `.checkout-grid`
  (billing and payment panels on the left, the order summary card on the right). The consent,
  the "Place order" button and "Back to basket" sit outside `[tutor-checkout-details]`, the
  element Tutor refreshes over AJAX when a coupon or country changes, so they survive it.
- `tutor/ecommerce/cas-checkout-billing-fields.php` is a copy of Tutor's billing fields
  **without State and Phone**. Tutor does not check them on the server, but its customers
  table has no default for them, so they are posted as empty hidden inputs.
- `inc/cas-checkout.php` adds the stylesheet (`css/cas-checkout.css`), the prototype's words
  ("Order summary", "Total", "Place order") and the AJAX-refresh coverage.
- **When Tutor is updated, diff both template files against the plugin's** and carry the
  changes across. `tests/test_checkout_parity_contract.py` names the pieces that must stay.
- There are no card fields on the site: Stripe hosts the card form, and its page branding is a
  Stripe setting.
- The footer on the checkout pages shows the Account column (Log in, Basket; Sign up only while
  registration is open) to a signed-out visitor and the normal Contact column to a signed-in
  one (#141).
- **Consent:** a required no-refund checkbox (owner-approved wording, #138) and the site-terms
  box. The server refuses pay-now without the no-refund box (filter `tutor_can_purchase_course`,
  `inc/cas-checkout.php`), and it also guards the pay button for an unpaid order. Both consents
  are stored as order meta on `tutor_order_placed` and `tutor_order_updated`:
  `_cas_refund_consent_at`, `_cas_refund_consent_text` and `_cas_checkout_terms_consent`.

---

## `deer-preview.html` / `design_base.html`

Not site pages. `deer-preview.html` renders the deer-frame SVGs for eyeballing; it's the
only plain-`.html` file **not** using the `<x-dc>` structure. `design_base.html` is the
design direction board (palette, materials, four themes) — a reference artifact.

Next: [05 — JavaScript](05-javascript.md).
