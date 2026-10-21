# Crystal Education Project

## Gstack

Available skills:
- /office-hours
- /plan-ceo-review
- /plan-eng-review
- /plan-design-review
- /design-consultation
- /design-shotgun
- /design-html
- /review
- /ship
- /land-and-deploy
- /canary
- /benchmark
- /connect-chrome
- /qa
- /qa-only
- /design-review
- /setup-browser-cookies
- /setup-deploy
- /retro
- /investigate
- /document-release
- /codex
- /cso
- /autoplan
- /plan-devex-review
- /devex-review
- /careful
- /freeze
- /guard
- /unfreeze
- /gstack-upgrade
- /learn

## Skill routing

When the user's request matches an available skill, ALWAYS invoke it using the Skill
tool as your FIRST action. Do NOT answer directly, do NOT use other tools first.
The skill has specialized workflows that produce better results than ad-hoc answers.

Key routing rules:
- Product ideas, "is this worth building", brainstorming → invoke office-hours
- Bugs, errors, "why is this broken", 500 errors → invoke investigate
- Ship, deploy, push, create PR → invoke ship
- QA, test the site, find bugs → invoke qa
- Code review, check my diff → invoke review
- Update docs after shipping → invoke document-release
- Weekly retro → invoke retro
- Design system, brand → invoke design-consultation
- Visual audit, design polish → invoke design-review
- Architecture review → invoke plan-eng-review
- Save progress, checkpoint, resume → invoke checkpoint
- Code quality, health check → invoke health

## Design Direction Decision Log

Timestamped record of client-driven design-direction changes. Purpose: maintain a
clear, dated history of what was agreed when, so directional drift is traceable and
the agreed scope is documented. This file is internal working memory — the
authoritative external record is the WhatsApp thread, the dated proposal doc, and any
written confirmation sent to the client.

### Agreed references (baseline)
- **Round 1 (prior):** Client-agreed reference sites were **Proko** + **Will Kemp Art
  School**. Per PRD, design direction = "between Proko and Will Kemp": professional but
  warm, modern but not sterile. This is the contracted look-and-feel direction.

### 2026-05-25 — Client introduced new editorial/broken-grid references
- **What changed:** Client sent two new reference templates this round:
  - Crafti — https://crafti.axiomthemes.com/
  - Ingenioso — https://ingenioso.themerex.net/
- **What client wants:** Less rigid grid; more "alive" feel — overlapping artwork,
  layered text over/between images, background texture, moving/animated text, a
  "theme board" vibe, and a banner with multiple overlapping artworks + layered text.
- **Assessment:** This is a **"Creative Editorial" / "Broken Grid"** direction, which
  is different from the agreed Proko + Will Kemp direction. As such it is a
  **directional scope change** from what was scoped, not part of baseline.
- **Dev position communicated to client (via WhatsApp, 2026-05-25):**
  - The real cost driver is NOT "adding an animation." It is building a
    **client-editable, content-agnostic, responsive overlapping layout** — i.e. a
    layout that does not fall apart when Crystal swaps in images of different aspect
    ratios via the CMS, and that holds up on mobile. That robustness is what blows the
    budget, not static positioning per se.
  - A full-site overlapping + entrance/exit animation system, editable by the client,
    is effectively a small project of its own → **Phase 2, quoted separately**.
  - Static single image / simple carousel banner: doable in scope.
  - Simple fade-in on 1–2 specific spots: doable in scope.
- **Resolution / next action:** Dev (Herman) to **explore Elementor Pro's native
  overlap + motion-effect capabilities**. If a comparable effect is achievable with
  **minimal setup** (no bespoke layout engine required), it will be treated as
  **in-scope** and absorbed at no extra cost. If it requires a custom editable layout
  engine, it remains **Phase 2 (separate quote)**. Herman to report findings back to
  client.
- **Note for defensibility:** The proposal/PRD lists Elementor Pro as in-scope and
  sells it as fast custom-layout creation. Therefore avoid arguing the overlap is
  "impossible / a different system" — the defensible line is specifically that a
  *client-editable, responsive* overlapping layout is the cost driver, and that the
  agreed direction was Proko + Will Kemp (so this is a scope change).

### 2026-05-25 (update) — Crafti hero teardown + revised scope assessment

Investigated the Crafti hero/banner DOM + Revolution Slider config directly.

- **How Crafti's "layered photos" effect is actually built:**
  - It is NOT one composite image and NOT CSS blend modes. NOT a single pre-made
    image. It is **3 separate real image files** stacked with absolute positioning +
    z-index inside a Revolution Slider (`rs-module`) slide. Each is an independent
    `<rs-layer data-type="image">` element inside the active `<rs-slide>`, positioned
    via Revolution Slider's coordinate system (`data-xy`, `data-dim`):

    | Layer | File                       | Role                                        | z-index |
    |-------|----------------------------|---------------------------------------------|---------|
    | 1     | `slider-bg.png`            | Cream/linen texture background, fills slide | 12      |
    | 2     | `new-slide-2-img-2.jpg`    | Large pottery photo (right, behind)         | 13      |
    | 3     | `new-slide-2-img-1.jpg`    | Smaller tools photo (left, overlapping in front) | 14 |

  - **Positioning detail:**
    - Layer 2 (large): pinned to right edge (`x:r`) with `xo:70px` inward offset.
    - Layer 3 (small): also right-anchored but pushed further left (`xo:680px`) and
      down (`yo:80px`) → creates the diagonal "peeking from behind" overlap.
  - Overlap illusion = pure z-index stacking + offset X/Y coordinates. Nothing
    composited.
  - Both photos enter from right (entrance animation `data-frame_0: x:100%`) with
    staggered timing (`st:310` vs `st:610`).
- **Mobile behaviour (the key finding):** Both photo layers carry
  `data-vbility="t,t,t,f"` (4 values map to desktop / notebook / tablet / mobile).
  Trailing **`f` means both photos are HIDDEN on mobile (≤480px)** — only linen
  texture background + text (heading, subtitle, CTA button) remain. The right half
  of the hero is essentially empty on phones. Photos scale at fixed per-breakpoint
  sizes (not fluid reflow):

  | Asset                       | Desktop (1240px+) | Notebook (~1024px) | Tablet (~778px) | Mobile (≤480px) |
  |-----------------------------|-------------------|--------------------|-----------------|-----------------|
  | Large (`...img-2.jpg`)      | 800 × 822         | 560 × 575          | 281 × 289       | hidden          |
  | Small (`...img-1.jpg`)      | 382 × 452         | 280 × 331          | 134 × 159       | hidden          |

  Sensible trade-off for small screens, but the layered visual richness is completely
  absent on phones.
- **Why this matters for scope:** Crafti did NOT solve responsive overlap — it
  **dodged it** by hiding the images on mobile. The expensive problem (overlapping
  images staying coherent across breakpoints when a different-aspect-ratio image is
  swapped in) is avoided, not engineered. We can use the same dodge.

- **REVISED SCOPE DECISION (supersedes the cautious framing above):**
  - **IN-SCOPE / achievable:** A hero with 2–3 stacked images at fixed desktop/tablet
    positions, staggered fade/slide-in entrance animation, hidden or simplified on
    mobile — set up ONCE by Herman using client-chosen images at agreed sizes. This is
    Elementor Pro widget configuration (absolute positioning + motion effects /
    slider), NOT a custom layout engine. Equivalent to what Revolution Slider does for
    Crafti.
  - **STILL PHASE 2 / still the real cost driver:** Making the hero **client-editable**
    — i.e. Crystal swapping these images herself via the CMS and having the
    composition auto-arrange without breaking (especially on the breakpoints where
    images ARE shown). That robustness is the cost, not the visual effect.
  - **Line to communicate to client:** "I can build this exact layered hero. In-scope =
    I set it up with your chosen images at fixed positions, hidden on mobile (same as
    the reference). It becomes Phase 2 only if you need to freely swap these images
    yourself later and have them auto-arrange."
- **Confidence caveat:** Assessment is from stack reasoning (Elementor Pro has absolute
  positioning, motion effects, entrance animations), NOT from testing the exact widget
  workflow. Verify Elementor Pro's current positioning/motion capabilities before
  committing a specific look to the client.
- **Reference source:** Crafti theme — https://crafti.axiomthemes.com/

### 2026-05-25 — Ingenioso hero teardown (client's other reference)

Inspected the Ingenioso hero/banner DOM directly (https://ingenioso.themerex.net/).
Same vendor family as Crafti (ThemeREX), same slider engine (Slider Revolution).

- **How Ingenioso's hero is actually built:**
  - NOT one composite image. It's a Slider Revolution module (`<rs-module>` →
    `<rs-slide>` → `<rs-layer>`) made up of **7 separately positioned layers** stacked
    via absolute positioning + per-layer z-index. Concretely:

    | # | Type  | Asset / content                              | Approx. size (desktop) | z-index |
    |---|-------|----------------------------------------------|------------------------|---------|
    | 0 | image | `image-132.jpg` (big center portrait)        | 569 × 815              | 8       |
    | 1 | image | `image-133.jpg` (top-left, dark interior)    | 533 × 357              | 10      |
    | 2 | text  | "We enjoy building incredible things."       | 1325 × 300             | 14      |
    | 3 | image | `image-134.jpg` (small girl, bottom-left)    | 213 × 264              | 11      |
    | 4 | image | `image-135.jpg` (small square, behind)       | 213 × 264              | 13      |
    | 5 | image | `image-136.jpg` (orange flowers, right)      | 533 × 357              | 12      |
    | 6 | text  | "creative for tomorrow" (outline word)       | 3455 × 398             | 9       |

  - Each image is its own JPG file — not a flattened composite. Foreground photo sits
    above side photos; headline text sits above all photos; the giant outline word
    sits behind everything as a backdrop.
- **Animation / parallax:**
  - Mouse-driven parallax via Slider Revolution config:
    `parallax: { levels:[1,2,3,4,5,6,7,8,9,10,12,15,17,20,25,30], type:"mouse",
    origo:"slidercenter" }` — each layer drifts a different amount on cursor move,
    producing depth-on-hover. No 3D model — just per-layer parallax levels.
  - Each layer also has its own entrance animation (e.g.
    `data-frame_0="x:110%;o:1;skX:3px;"`) — they slide in from the right with a slight
    skew on page load.
- **Mobile behaviour (the key finding — differs from Crafti):**
  - Ingenioso does NOT hide layers on mobile. It **rescales the whole stage** and uses
    per-breakpoint coordinates. Slider Revolution declares
    `responsiveLevels: "1240, 1460, 785, 500"` → 4 breakpoints
    (desktop / laptop / tablet / phone).
  - Every layer carries 4 comma-separated values for X/Y offsets, font size, etc. e.g.
    layer 1: `data-xy="xo:199px,186px,-1px,-78px; yo:95px,46px,39px,35px;"` — at the
    smallest breakpoint the photo's X becomes -78px (shifts off-screen left) and Y
    becomes 35px. Headline carries `s:130,90,60,40` (font shrinks 130 → 40 px) and
    `l:150,110,90,50` (line-height shrinks).
  - Stage height itself shrinks: `editorheight:"900,600,480,480"` → 480 px on phones.
  - Result: on mobile the photos REMAIN LAYERED/OVERLAPPING (just smaller and
    repositioned), and the giant "creative for tomorrow" outline word is intentionally
    clipped by the stage edges. No reflow into a stacked column.
- **Why this matters vs Crafti:**
  - Crafti **dodged** responsive overlap by hiding photos on mobile (`data-vbility`
    trailing `f`). Ingenioso **engineered** it by hand-tuning per-breakpoint
    coordinates for every layer.
  - The expensive problem (overlap staying coherent across breakpoints) is solved by
    **manually authored breakpoint values per layer**, not by an auto-layout engine.
    Slider Revolution provides the inputs; a human has to fill them in for every
    image at every breakpoint.
- **REVISED SCOPE DECISION for Ingenioso-style hero:**
  - **IN-SCOPE / achievable:** A 5–7 layer stacked hero with mouse parallax, staggered
    entrance animations, hand-tuned at 3–4 breakpoints — set up ONCE by Herman with
    client-chosen images at fixed positions. Elementor Pro can do absolute positioning
    + motion effects per breakpoint, similar (though less ergonomic) to Slider
    Revolution. Acceptable as a one-time hero build.
  - **STILL PHASE 2 / cost driver remains the same:** Making it **client-editable** —
    Crystal swapping any of the 7 layers herself and having the overlap stay coherent
    at all 4 breakpoints. With Ingenioso's pattern this is even worse than Crafti,
    because every swapped image needs 4 sets of X/Y/scale values re-tuned by hand.
  - **Line to communicate to client:** "I can build this exact layered/parallax hero
    in-scope as a one-time setup with your chosen images. If you want to swap any of
    the 7 layers yourself later and keep the composition coherent across mobile/
    tablet/desktop, that's Phase 2 — each swap needs per-breakpoint repositioning,
    which Slider Revolution does by hand, not automatically."
- **Confidence caveat:** Layer count, z-indexes, and breakpoint values were read from
  the live `<rs-module>` markup. Elementor Pro's ability to match Slider Revolution's
  per-breakpoint coordinate authoring should be verified before committing.
- **Reference source:** Ingenioso theme — https://ingenioso.themerex.net/

#### Ingenioso — under-hero photo carousel (`elementor-element-67357e8`)

The horizontally-scrolling photo row directly below the hero (hands holding fruit,
person w/ hat & flowers, woman in red against louvred wall, dot indicator below).
**Different engine from the hero** — standard Elementor section + ThemeREX Swiper
widget, NOT Slider Revolution.

- **Class string decoded:**
  - `elementor-section elementor-top-section elementor-element` → top-level Elementor
    section wrapper.
  - `elementor-element-67357e8` → Elementor's unique per-element ID hash (scopes
    inline CSS).
  - `elementor-section-boxed` → inner container constrained to theme boxed width
    (1320 px); section itself spans full viewport.
  - `elementor-section-height-default` (×2, Elementor sometimes duplicates) → height
    from content, no forced min-height.
  - `sc_fly_static` → ThemeREX ShortCode flag = block is NOT in scroll-parallax
    "fly" mode (stays put while scrolling). `sc_fly_left/right/up/down` would
    translate on scroll; `_static` opts out.
  - `trx_addons_parallax_layers_inited` + `trx_addons_parallax_blocks_inited` →
    "I've been processed" markers added by ThemeREX Addons JS after parallax engine
    inits. No actual `data-parallax` attrs inside this section → plugin attaches but
    has nothing to animate. **Effectively no-ops on this block.**
- **Internal structure:** section → 1 full-width column (`elementor-col-100`) → 2
  widgets:
  1. Spacer widget (`elementor-widget-spacer`, 100 px tall) — gap above carousel.
  2. ThemeREX slider widget (`elementor-widget-trx_widget_slider`) — the carousel.
- **Slider wrapper class flags (`slider_outer slider_swiper_outer ...`):**
  - `slider_style_default` — default visual style.
  - `slider_source_custom` — slides manually picked, not from post category.
  - `slider_outer_direction_horizontal` — horizontal scroll.
  - `slider_outer_multi` — multi-slide view (>1 visible at once).
  - `slider_outer_nopagination`, `slider_outer_nocontrols` — no bullet pagination,
    no prev/next arrows. Single dot = only indicator. Navigation by swipe/drag only.
  - `slider_outer_nocentered` — slides left-aligned, not centered.
  - **`slider_outer_overflow_visible`** — crucial. Overflow visible so partial
    slides bleed off left/right viewport edges (banana-leaf + dark photo clipped on
    left edge).
  - `slider_outer_notitles` — no captions/titles overlaid.
  - `slider_outer_height_auto` — height adapts to image height.
- **Slides:** 7 total. Each = single `<img>` inside `.slider-slide.swiper-slide`
  div. Varying intrinsic heights (~350 px to ~501 px) → staggered-height look.
  Files: `image-10.jpg`, `image-23.jpg`, `image-12.jpg`, `image-148.jpg`,
  `image-13.jpg`, `image-76.jpg`, `image-130.jpg`. Each rendered slide ~390 px wide
  desktop. Swiper.js transforms `.swiper-wrapper` for left/right translation;
  individual slides stay `matrix(1,0,0,1,0,0)`.
- **Mobile / responsive:** Swiper.js inherently responsive — recomputes slide width
  + translation on resize. With `multi` mode + `overflow_visible`, mobile typically
  shows one full slide centered + peek of neighbors. User swipes horizontally.
  Spacer widget height adjustable per-breakpoint via Elementor editor (100 px
  desktop usually drops to ~40–60 px on mobile in this theme).
- **TL;DR vs the hero:** Hero = Slider Revolution stage, absolutely-positioned
  separately-animated layers, mouse parallax. This carousel = Elementor section →
  column → Spacer + Swiper. `trx_addons_parallax_*` + `sc_fly_static` look exotic
  but are no-ops on this block — just ThemeREX framework tagging every section so
  parallax JS can decide later. Visual effect (partial-bleed gallery row) comes
  entirely from Swiper multi-slide mode + `overflow: visible`.
- **Scope implication:** This carousel pattern is **trivially in-scope**. Elementor
  Pro has a native Image Carousel widget + the Posts Carousel widget; either can
  produce horizontal multi-slide w/ overflow-visible peek-of-neighbors via simple
  config. Client-editable from CMS (add/remove/reorder slides) without per-
  breakpoint hand-tuning. Unlike the hero, **no Phase 2 caveat** for this block.

### 2026-05-25 — Will Kemp Art School teardown (agreed baseline reference)

Inspected willkempartschool.com page structure + hero carousel directly. This is one
of the **Round 1 agreed baseline references** (Proko + Will Kemp). Documenting how
it's actually built so we can match the look without over/under-scoping.

#### Overall page layout

Classic content + sidebar WordPress stack:
- **Header** (banner, `ref_2`): centered logo "Will Kemp Art School — professional
  secrets for aspiring artists", "QEST Craft Scholar" badge top-left. Horizontal nav
  below: Home, New? Start Here, Acrylics, Oils, Drawing, Portraits, Shop All
  Courses, FAQ's & Contact Will, Free Videos, About Will, 7 Painting Principles,
  Student Success Stories, cart icon (0), search icon. Header duplicated into 2
  regions: `ref_7` desktop, `ref_60` mobile/sticky — each w/ own search box +
  mobile-menu toggle.
- **Main content area** (`ref_117`): hidden H1 ("painting tutorials, courses, tips
  & techniques"), carousel slider, then stream of 14 article previews (`ref_173` →
  `ref_804`) = blog/tutorial post cards. Pagination list (`ref_823`) w/ 7 page items.
- **Primary sidebar** (complementary, `ref_839`): right side. "hello…" intro w/
  B&W photo of Will Kemp + short bio. "Get Free Updates" email signup module
  promoting free 50-page PDF + 10 free drawing printouts + 10 free reference images.
- **Footer** (`ref_887`): main footer area (`ref_888`) + copyright bar (`ref_942`)
  "Copyright © 2012-2026 Will Kemp Art School" + Privacy / Terms / Contact links.
- **Utilities:** scroll-to-top (`ref_946`), 2 off-canvas mobile menus (`ref_950`,
  `ref_979`) each duplicating main nav, cart drawer (`ref_976`), notifications
  generic (`ref_1004`).

#### Hero carousel — what it actually is

**Smart Slider 3** (WordPress plugin — every element prefixed `n2-ss-`). Root:
`<div id="n2-ss-2" class="n2-ss-slider n2-ow n2-has-hover n2-ss-desktopPortrait
n2-ss-loaded" data-creator="Smart Slider 3" data-device-mode="desktopPortrait"
data-responsive="auto">`.

- **6 rotating slides**, each w/ same overlay structure:
  - Small uppercase top-line tag: "will kemp art school"
  - Large serif centered headline (slide theme)
  - Yellow rounded pill CTA → different destination per slide

  | # | Headline               | CTA                       | Destination                                          |
  |---|------------------------|---------------------------|------------------------------------------------------|
  | 1 | Learn to Paint         | Shop All Courses          | `/shop/`                                             |
  | 2 | Classical Techniques   | Beginner Drawing Course   | `absolute-beginners-drawing-course`                  |
  | 3 | Simple Approach        | Free Videos               | `how-to-paint-with-acrylic-paint-videos`             |
  | 4 | Online Courses         | Bestseller Acrylic        | `absolute-beginners-acrylic-course`                  |
  | 5 | Acrylics & Oil         | Student Success           | `student-success-stories`                            |
  | 6 | Easy to Follow         | Latest Acrylic Course     | `market-day-peaches-acrylic-still-life-course`       |

- **Controls:** previous arrow (`ref_162`) left edge, next arrow (`ref_164`) right
  edge, dot pager (`ref_166`) bottom center w/ 6 dots (`ref_167`–`ref_172`). Alt
  text duplicated across dots ("Slide 1 learn to paint" repeated) — accessibility nit.

#### Each slide = layered composition (NOT a flat banner)

Most interesting finding: each slide is **5 stacked layers + separate `<img>`**, NOT
a flat baked image. Structure for active slide (at current viewport):

1. **Bottom `<img class="skip-lazy">`** = photographic background. Each slide loads
   own pre-cached WebP at 809×486 from `/wp-content/uploads/slider/cache/`. Files:
   `Screen-Shot-2023-04-03-at-16.15.36.webp` (Learn to Paint),
   `slider-paint-pots.webp` (Simple Approach), `silder-peaches.webp` (peaches), etc.
   WebP for compression. `skip-lazy` keeps them eager-loaded so hero never blank.
2. **Stacked `.n2-ss-layer` divs** above photo (all `z-index: 2; position: relative`,
   vertically flowed inside flex container — **NOT absolute positioning**, unusual
   for slider, this is why layers can re-stack cleanly on mobile):
   - Outer wrapper layer (809×486) holds everything
   - Inner content wrapper (679×386) w/ padding to keep text off edges
   - Small uppercase tagline "WILL KEMP ART SCHOOL" (659×21, tracking-spaced label)
   - Large serif headline (659×240, dominant element)
   - Yellow pill CTA button (246×60) wrapping `<a href>` to per-slide destination
- **No runtime dark gradient overlay** — photos pre-darkened in source files so
  white text reads. Dimming is NOT a CSS effect.
- Arrows + dot pager (`.n2-ss-slider-controls-absolute-center-bottom`) are siblings
  of slide track, absolutely positioned on top via
  `n2-ss-slider-controls-absolute-left-center` utility classes.

#### Mobile / responsive strategy — two cooperating systems

**1. Smart Slider's own device-mode swap.** `data-responsive="auto"` → Smart Slider
listens to viewport width, swaps slider class between 6 modes: `desktopPortrait`,
`desktopLandscape`, `tabletPortrait`, `tabletLandscape`, `mobilePortrait`,
`mobileLandscape`. Each mode ships own font-size + padding + layer-visibility rules
as inline CSS (configured server-side).
- Layers use relative positioning inside flex column → naturally re-flow vertically
  when container shrinks. Tagline, headline, button stack tighter.
- Photo `<img>` scale-to-container w/ `overflow:hidden` → 16:10 desktop hero
  becomes shorter narrower hero on phone w/ same subject centered.
- 32×32 SVG arrows remain tappable at any width.

**2. OceanWP theme breakpoints around the slider.** Body carries
`oceanwp-theme default-breakpoint`. Stylesheet defines 60 distinct media query
blocks. Critical thresholds:
- `max-width: 959px` — OceanWP "mobile" cutover. Entire desktop nav collapses:
  `#top-bar-nav`, `#site-navigation-wrap`, `.oceanwp-social-menu` →
  `display: none`. `.oceanwp-mobile-menu-icon` → `display: block`. Page has 2
  duplicate nav `<list>` structures (`ref_950` + `ref_979`) — one is desktop bar,
  other is hidden off-canvas drawer using **Sidr library** (everything prefixed
  `sidr-class-*`). "Close mobile menu" at `#sidr-menu-close` is dismiss target.
- `max-width: 767px` — tablet/phone breakpoint. Blog entry cards switch from
  horizontal `flex-direction: row` (image left, text right) → vertical
  `flex-direction: column` (image top, text below). Header columns other than logo
  hidden: `.top-col:not(.logo-col) { display: none }`. Utility classes
  `.hide-tablet-mobile` + `.hide-tablet` kick in.
- `max-width: 600px` + `max-width: 480px` — fine-tuning for small phones (font
  sizes + padding shrink further).

**Mobile-specific logo swap:** `#site-logo.has-responsive-logo .custom-logo-link`
hidden under 959px, `.responsive-logo-link` shown instead → smaller/simpler logo
served on phones (no scaling wide horizontal one). Sticky variant
`.is-sticky .sticky-logo-link` for shrinking header on scroll.

`<meta name="viewport" content="width=device-width, initial-scale=1">` present →
real device width, no iOS 980px fallback.

#### Sidebar on mobile

Right-side complementary sidebar (`ref_839` — Will's photo + bio + "Get Free
Updates" signup) does NOT disappear on mobile. Body class **`sidebar-mobile`**
tells OceanWP to drop sidebar BELOW main content rather than hide. Mobile order:
header → hero slider → article list → sidebar (bio + email signup) → footer.
**Preserves email-capture conversion path on small screens.**

#### Stack summary

Typical WordPress stack:
- **OceanWP theme** → header / nav / sidebar / footer responsiveness via max-width
  media queries + Sidr off-canvas drawer
- **Smart Slider 3** → hero w/ internal device-mode system + layered
  relatively-positioned text/CTA over pre-darkened WebP photos
- **Elementor** → used elsewhere on page (per `elementorFrontendConfig` script)

**Clever trick:** hero text + buttons = real DOM layers, NOT baked into image.
Headlines stay crisp at any density. CTA = real `<a>` link (SEO + a11y win). Slide
recomposes on mobile without tiny illegible text. **This is the model to copy** —
it's the agreed baseline direction and it's achievable in Elementor Pro w/o the
broken-grid / per-breakpoint hand-tuning cost of Crafti / Ingenioso heroes.

#### Scope implication vs Crafti / Ingenioso

- Will Kemp hero pattern = **inherently in-scope + inherently client-editable**.
  Text + CTA are DOM, not coordinates → swapping a slide = upload new WebP + edit
  headline string + edit CTA URL in CMS. No per-breakpoint repositioning.
- This is the **defensible baseline**: it matches PRD direction (Proko + Will
  Kemp), it's achievable in Elementor Pro w/ native slider widgets, and it does NOT
  trigger Phase 2 cost driver.
- Reinforces 2026-05-25 dev position: Crafti/Ingenioso style overlapping/parallax
  hero is a directional scope change from this baseline, not a refinement of it.
- **Reference source:** Will Kemp Art School — https://willkempartschool.com/

### 2026-05-25 — Proko homepage teardown (other agreed baseline reference)

Inspected proko.com homepage directly. This is the **other Round 1 agreed baseline
reference** (Proko + Will Kemp per PRD §Goals). Documenting how it's actually built
so we have the full baseline picture before responding to client's new direction asks.

#### Stack

- **Angular** (`_ngcontent-proko-web-*` attrs on every element).
- **Tailwind CSS** utility classes layered on top of BEM-style component classes
  (`RecentStudentWork`, `ProductCardM`, `SSPoster`, etc.).
- Responsiveness via **two cooperating systems**:
  1. **Tailwind breakpoint prefixes** (`sm:`, `md:`, `lg:`) on individual
     elements. e.g. product card overlay uses `translate-y-[88px]
     md:translate-y-[96px]`; small-card variant rendered w/ `sm:hidden`, desktop
     counterpart w/ `hidden sm:block`.
  2. **Component-scoped CSS media queries** at ~1024px, 1192px, 1280px, 1600px →
     progressively enlarge dimensions per breakpoint.
- **Strategy:** "render different component for small screens, then progressively
  enlarge spacing/dimensions as viewport grows." Horizontal rails (Trending, Deals,
  etc.) = generic `Slider` component w/ `display: flex; gap: 24px;` + CSS
  `scroll-snap-align: start` → mobile = swipeable carousels, NOT fit-everything.

#### Hero / banner section

Visually looks like one wide banner. **Structurally = horizontal slider of
independent layered "poster" cards**, NOT a single image.

- Outer: `div.Slider` → child `div.Slider-container` w/ `display: flex;
  flex-direction: row; gap: 24px; overflow: auto;` + scroll-snap. **13
  `Slider-item` children**: first = "Summer Sale" promo, rest = course posters
  (Practical Perspective, Drawing Basics, etc.).
- Each slide = own Angular component (`proko-summer-sale-poster`,
  `proko-course-poster`). **Course poster = multi-layered**, NOT flat image:
  - `<picture>` w/ 3 `<source>` tags swapping artwork by viewport:
    - `(min-width: 1280px)` → 480×640
    - `(min-width: 1024px)` → 384×512
    - `(min-width: 0)` → 272×362
    - Browser downloads only the needed size.
  - Absolutely-positioned `<img>` (`position: absolute; object-fit: cover`) =
    artwork layer behind text.
  - Separate "PRESALE" / "SALE" diagonal ribbon element on top.
  - Gradient overlay (`ProductCardM-overlay`:
    `linear-gradient(180deg, transparent 0%, black 100%)`) over bottom of image for
    text readability.
  - Title + instructor + duration/lesson stats + START LEARNING / DETAILS buttons
    on top of overlay.
- Summer Sale slide = even more layered. Inline SVG of ice-cube logo as own DOM
  element (NOT baked image) + separate text blocks + BROWSE button.
- **Mobile behavior:** slides resize via CSS, NOT re-crop a single image.
  `.SSPoster`: `width: 272px; height: 362px` default → 384×512 @ ≥1024px →
  480×640 @ ≥1280px. Container = flex row + scroll-snap + `overflow: auto` → on
  phone see ~1.x posters + swipe horizontally. Dot indicators below reflect slide
  groups. **Small-screen story: smaller cards + swipe-to-browse, NOT stack/hide.**

#### "Recent Student Work" section (genuinely clever pattern worth copying)

Looks like masonry grid but is **2-row flex layout where each tile preserves own
image aspect ratio while keeping row height equal**.

- `div.RecentStudentWork`: `display: flex; flex-direction: column;
  max-width: 1216px; margin: auto;`. Holds header + `RecentStudentWork-items`
  block.
- Inside: 2× `RecentStudentWork-row` divs. Each row: `display: flex;
  flex-direction: row; justify-content: center; gap: 16px;` (gap bumps to 24px
  @ ≥1192px and 32px @ ≥1600px; 10px margin between rows).
- Each row = 4× `RecentStudentWork-item` tiles. **The trick:**
  - Every tile in row gets **same fixed height** (~281px current viewport).
  - Each tile width = whatever image aspect ratio dictates (wide sketchbook
    spread → ~461px; narrow portrait sketch → ~167px).
  - Row = equal height + uneven widths → visually tasteful collage, NOT rigid grid.
- Tile CSS: `position: relative; overflow: hidden; border-radius: 16px;
  box-shadow: 0 8px 40px rgba(0,0,0,0.08); flex-shrink: 0; flex-grow: 0;`
  — **`flex-grow: 0` is the key** — stops items stretching to fill row, preserves
  artwork's natural proportions.

**Image + avatar embedding inside each tile:**
- Artwork `<img>`: `position: absolute; inset: 0; object-fit: cover;` fills tile.
- Avatar on top: `RecentStudentWork-avatar --sizing-image-24`,
  `position: absolute; bottom: 8px; left: 8px; z-index: 20;`, 24×24 circular
  badge. Each tile contains `span.avatar_bg --top` (colored circle behind initial
  for users w/o profile photo — e.g. "H" letter bubble) + 2 `<img>` tags (primary
  + `.avatar_img --hidden` swapped in when real photo loads).
- Usernames NOT shown as text in tile. `<a>` tag's accessible label carries name
  (`@justjen`, `Benjamin Gustafsson`, `Lea`, `Patryk Makosz`, `Hodiyah Green`)
  → screen readers + link tooltips reveal. Visually only avatar identifies.
- Hover: `RecentStudentWork-previewOverlay` (translucent black layer
  `z-index: 10` + 30px icon) fades in via opacity transition.

**Mobile adaptiveness here (intentionally minimal):**
- Items sized by **intrinsic image aspect ratios** → no separate mobile-stacked
  variant needed.
- Only breakpoint-driven changes: gaps (16 → 24 → 32px) + decorative side sketch
  (`RecentStudentWork-sketch`) `display: none` default, only appears @ ≥1600px.
- Narrower screens → less horizontal space → each tile (still respecting aspect
  ratio) ends up shorter. Equal-height property automatic from flexbox
  `align-items: stretch` / equal-row-height behavior.
- Row stays horizontal + scales down. **No reflow to vertical stack.**

#### Other patterns noted in passing

- Course product cards (`ProductCardM`) have height tiers: 364px default →
  424px @ ≥1192px → 468px @ ≥1600px.
- Hover affordance = transform-based reveal: stats block translated down by
  88/96px and slid up on `group-hover`. **Touch devices (no hover) just see
  title + badges → intentional**, no "stuck mid-state" issue.

#### Stack summary + comparison vs Will Kemp

- Proko = bespoke Angular app w/ Tailwind + component-scoped CSS. Much more
  engineered than Will Kemp's WordPress + OceanWP + Smart Slider 3 stack.
- Both reference sites' heroes = **multi-layered DOM components, NOT flat baked
  images, NOT broken-grid, NOT absolute-positioned overlapping artwork.** Both
  use horizontal-scroll multi-card carousels at the top (Will Kemp = single-slide
  rotator, Proko = horizontal-scroll deck), w/ text/CTA as real DOM layers.
- Neither hero uses parallax, mouse-driven layers, or hand-tuned per-breakpoint
  coords. Both rely on the layout engine (flexbox + scroll-snap, or relatively
  positioned flex columns) to re-flow naturally.

#### Scope implication — completes the baseline picture

- **Both PRD baseline references confirm: the contracted direction is
  carousel/grid + real-DOM text overlays, NOT broken-grid + absolute overlap.**
- Proko's "Recent Student Work" section = a **strong candidate pattern to copy**
  for Crystal's student gallery / portfolio area: equal-row-height flex w/
  aspect-ratio-preserving tiles is implementable in Elementor Pro using its
  native Gallery widget set to `flex` layout w/ `flex-grow: 0` on items, OR via a
  simple custom CSS snippet. **In-scope, no Phase 2 caveat.**
- Reinforces 2026-05-25 dev position: any client-pushed broken-grid/parallax
  direction is **not present in EITHER baseline reference** → directional scope
  change, not a refinement of baseline.
- **Reference source:** Proko — https://www.proko.com/

### 2026-05-25 — WhatsApp exchange + reaffirmed dev position (logged verbatim)

Logging this exchange because the new references (Crafti, Ingenioso) are materially
more demanding than the agreed PRD baseline (Proko + Will Kemp). Future drift checks
should refer back to this record.

#### Client message (16:41 + 17:07, +852 6184 6003)

- Client: "不如我選template 給你參考好嗎？現在看如果一整版也是這個設計太工整 ...
  我找不到什麼網站 不如我上網找template好似直接些"
- Then sent:
  - https://crafti.axiomthemes.com/ — "像這個不要一排排的圖片很工整的感覺 生動一些
    例如一些重疊，可以加啲背景 文字有些郁動 或者有些像theme board"
  - https://ingenioso.themerex.net/ — "Banner改為這個不同畫作重疊 表面及夾著有不同
    layers 的文字"

#### Dev (Herman) replies (17:17–17:26)

- 17:17 "如果banner要多樣重疊係完全另一個系統，係一1個component tree，會當一個
  additional feature 🙏 只係做呢個可能已經要1個月。Static 一張相或者幾張跑馬燈
  係可以做的"
- 17:22 "動畫的話（包括入場及離開的動畫）時間上都困難的。好似好簡單加個animation
  但係其實涉及的部分包括component 分割，更多的反應性測試，甚至如果個animation
  嘅文字圖片係會修改（等於要一套可以用於任何文字，任何圖片的動態系統）都抱歉
  現階段時間係做唔到住🙏🙇‍♀️"
- 17:25 (a) "我睇完之後，大概明白你想追求的感覺，這種 'Creative Editorial' (雜誌風)
  的設計確實比傳統網站更生動。但這兩款 Template 採用的其實是 'Broken Grid'
  (打破網格) 的架構，這跟目前合約規劃的標準 CMS 系統是完全不同的邏輯"
- 17:25 (b) "呢類重疊設計如果要在 CMS 讓你自己換圖換字，我們需要開發一套專門的
  佈局引擎，否則你換一張不同比例的圖，整個重疊效果就會散晒，係無法簡單處理。
  另外圖片重疊在電腦看很美，但在手機細螢幕很容易遮擋到文字，這需要額外多一倍
  的時間去做跨裝置測試（做到但係如上，測試時間會大增）"
- 17:26 (a) "如我剛才提到，單是處理 Banner 的多層重疊與入場動畫，就已經等同做
  一個小型專案的工程量，這在現有時程和預算內確實無法負荷🙇‍♀️🙇‍♀️"
- 17:26 (b) "如果想在不增加成本的情況下提升生動感，我們可以在現有框架下，針對 1-2
  個特定位置加入簡單的淡入 (Fade-in) 效果。但如果你需要像範本那樣全站重疊和
  多層次動畫，這需要當作 Phase 2 (第二階段) 的額外功能來報價和規劃時程🙏🙇‍♀️"

#### Reaffirmed dev position (commitment + line in the sand)

- **Commitment to client:** Herman will explore Elementor Pro's native widgets for
  overlap + motion. If a comparable effect is achievable w/ **minimal setup** (a few
  clicks, no custom layout engine), absorb into current scope at no extra cost. If
  it requires building a client-editable responsive overlap engine, it is **Phase 2,
  quoted separately**.
- **Line in the sand:** Contracted design direction = **Proko + Will Kemp** ("between
  Proko and Will Kemp: professional but warm, modern but not sterile" — PRD §Goals
  and Background Context). Both reference sites use standard grid + carousel layouts
  (Will Kemp teardown confirms: Smart Slider 3, layered DOM, no parallax, no
  multi-image overlap, no broken-grid).
- **Therefore:** Crafti + Ingenioso style overlap/parallax/broken-grid is a
  **direction change, NOT a refinement**, of the contracted baseline. Treating it as
  free scope would be **unpaid extra work** on a fixed HK$15,000 fee.
- **Concession Herman is willing to make in-scope** (matches WhatsApp 17:26):
  - 1–2 specific fade-in motion accents on existing layout.
  - Static single image OR simple carousel banner.
  - Elementor Pro native overlap IF achievable in minimal-setup mode.
- **What stays Phase 2:**
  - Site-wide broken-grid / overlapping layouts.
  - Entrance + exit animation system that works across arbitrary client-uploaded
    content.
  - Client-editable responsive overlap engine (per-breakpoint hand-tuning per
    swap = unbounded ongoing cost).

#### Why this exchange matters for future sessions

- The 2 new reference sites are objectively more demanding than the contracted
  baseline. Any future "but the client likes Crafti/Ingenioso" pressure should be
  met w/ the Will Kemp teardown above (proves baseline = simpler architecture) and
  this WhatsApp record (proves the line was drawn on 2026-05-25 in writing).
- Herman is **doing the Elementor exploration as a goodwill gesture**, not as a
  scope obligation. If exploration shows minimal-setup native support exists, it
  becomes a free upgrade. If not, the Phase 2 framing already communicated stands.
- Avoid scope creep through accumulation: small "can you also just..." additions
  in this direction each individually feel small but together = the unscoped Phase
  2 project. Push back early, push back in writing.

### 2026-05-25 (later) — Elementor capabilities VERIFIED + corrected position + report-back sent

Did the Elementor exploration that was promised to client (twice) in earlier messages.
Findings are from Elementor's own docs/academy, not assumption. **This supersedes the
earlier "parallax = needs custom code" framing.**

#### What was verified (sources: elementor.com/help, elementor.com/academy)

- **Absolute / relative / fixed positioning, per device** — native Elementor Pro, no
  code. Per-breakpoint position controls exist in-editor.
- **Entrance animations** — 37 built-in, selectable PER DEVICE (incl. "None" per
  device). Native, no code.
- **Mouse-track / parallax** — native Elementor Pro Motion Effects. "No external
  libraries or custom code required." Elementor's own academy has a tutorial doing
  exactly the Crafti/Ingenioso move: layering images with position:absolute + mouse
  track on a hero.
- **Mouse effects are DESKTOP-ONLY** — confirmed by Elementor directly ("Mouse effects
  will only affect desktop devices" / "do not work on mobile or tablet"). So parallax
  contributing nothing on touch is a fact, not a guess.

#### CORRECTION to earlier record (important)

- Earlier WhatsApp msg (and earlier log entries) framed Ingenioso parallax as
  "要另外寫程式 / bespoke JS / discrete add-on." **This is WRONG.** Parallax is native
  Elementor Pro, a few clicks, no code. Do NOT bill it as "custom development."
- Consequence: parallax is now treated as **IN-SCOPE** (it's near-zero effort in the
  contracted toolset), NOT a paid extra. Reason logged explicitly so the in-scope call
  is grounded in *the tool does it natively*, NOT a general willingness to absorb
  Crafti/Ingenioso features.

#### Corrected scope position (current, authoritative)

- **IN-SCOPE (contracted delivery, NOT a favour — do not call it "free"):** Layered
  DOM banner (images + text stacked, real DOM not flat image) + staggered entrance
  animation + mouse parallax. Built ONCE by Herman with client-chosen images. Grounds:
  this matches the **pre-quotation** agreed references (Will Kemp + Proko), both of
  which use layered-DOM heroes — so it is delivering the agreed direction, and
  Elementor does all of it natively.
- **Mobile:** simplified (Crafti-style). Mouse parallax does nothing on touch anyway;
  multi-layer overlap obscures text on small screens. Client told mobile = simplified
  version. (Sets expectation in writing that full mobile overlap is NOT promised.)
- **PHASE 2 (the ONE true paid extra — narrowed after fact-check):**
  **Client-editable** layered hero — Crystal swapping the overlapping images herself
  via CMS and having the composition stay coherent across desktop/tablet/mobile. No
  auto-layout for overlap → every swapped image needs per-breakpoint manual
  repositioning. "I set it up once = in-scope. A system where you swap images yourself
  unlimited times without it breaking = Phase 2."

#### Contract chronology (the load-bearing fact)

- **Will Kemp + Proko were provided BEFORE the HK$15,000 quotation** → baked into the
  price → anything matching them is contracted, already paid for.
- **Crafti + Ingenioso were provided AFTER quotation** → features that appear ONLY in
  them (not in the baseline pair) are new. The defensible line is chronological, not a
  matter of opinion: in-baseline = in-scope; only-in-new-templates = scope change.

#### Report-back message — DRAFTED but NOT SENT (correction)

- **CORRECTION:** The "Elementor checked → parallax in-scope, 我照做" report-back was
  DRAFTED but **NOT actually sent to the client.** Earlier wording in this section
  implying it was sent is wrong. It was never delivered — which is fortunate, because
  the deep Ingenioso teardown (added later, see below) showed the "parallax is native /
  in-scope" claim was over-simplified. **No promise to retract.**
- (Note: an earlier draft that called the banner "out of scope 免費服務" was also
  DELETED before sending — that framing would have conceded the baseline argument.
  Avoid it.)

#### What was ACTUALLY last sent to the client (the true current state)

- The genuinely-sent thread ends at the message describing the Phase 2 cost driver:
  *"如果你想自己喺後台換嗰幾層重疊嘅相、又要喺電腦/平板/手機都唔散——呢個先係真正貴
  嗰part。因為呢種重疊係冇得自動排，每換一次相都要人手喺幾個螢幕尺寸逐個重新調位
  （Crafti 自己更加係索性喺手機 cap 咗啲相唔顯示嚟避開呢個問題，Ingenioso 就係硬係用
  人手逐個 breakpoint 調返）"*
- **This is the strongest, most defensible point in the whole thread, and it is fully
  consistent with the teardown.** The client has NOT been told parallax is free/native.
  The ball is in the client's court. **No correction message is required — nothing
  inaccurate is outstanding.** Sending an unprompted walk-back would manufacture a
  problem and look wishy-washy.
- Items already sent and holding: (1) Crafti/Ingenioso = editorial direction change vs
  agreed baseline; (2) the real cost driver = self-editable cross-device overlap;
  (3) the Crafti-hides-on-mobile vs Ingenioso-hand-tunes contrast.
- **For the NEXT message** (whenever client replies), keep this distinction ready:
  "similar animated layered banner (stacked images + individual entrance + light mouse
  drift) = in-scope, will build" vs "pixel-exact Ingenioso (unified 3D parallax + mask
  wipes + per-letter type animation + 4s choreography) = needs Slider Revolution /
  custom GSAP = Phase 2 / small-project piece." Do NOT volunteer the word "parallax"
  as a blanket in-scope promise; if it comes up, scope it to *light mouse drift*, not
  the full Ingenioso system.

#### Process note (so the over-promise mistake doesn't recur)

- A web search confirmed Elementor *has* a mouse-track motion effect, and that was
  over-generalised in the earlier entry to "so the whole Ingenioso parallax is native."
  The search verified the feature EXISTS, not that it MATCHES the reference's
  complexity. **Lesson:** "tool has feature X" ≠ "tool reproduces this specific
  implementation of X." Verify depth, not just existence.

#### IMPLEMENTATION GUARDRAIL (for when build happens — protects the Phase 2 line)

- The Phase 2 boundary only holds if the build reflects it. When building the hero,
  **lock the layered hero section** (template / locked / non-trivial to edit) so that
  swapping the overlap images genuinely requires Herman. If the hero is left freely
  editable in the standard Elementor editor, client WILL swap an image, it WILL break
  on mobile, and "but you built it editable" becomes a fair complaint. Decide
  deliberately: lock it, or accept image-swaps as light ongoing support.

#### Open caveats / time-cost honesty

- "Extra testing time" has been mentioned to client ~twice without attaching to a
  concrete number. If the layered banner genuinely threatens the 15-week timeline,
  state it concretely (pushes X by N days) when relevant; otherwise drop the vague
  "this'll take longer" notes — they erode credibility if they never materialise.
- **Reference sources (verified):** elementor.com/help/motion-effects/,
  elementor.com/help/entrance-animations/, elementor.com/academy/motion-effects-collection/

### 2026-05-25 — Site-wide no-border + classic-waterfall 18px curve (visual baseline)

Client-driven directive codified after multiple feedback rounds. **Applies site-wide
unless explicitly overridden.**

- **No black borders anywhere.** All cards, photo frames, banner frames, container
  strips, and panel chrome render borderless. Previous selective border use is
  superseded. New rule in `styles-v5.css`: `.card.no-border { border-color: transparent }`
  + `.card.no-border .photo { border-color: transparent; border-radius: var(--radius-card) }`.
  Apply by default; opt back into bordered variant only when client explicitly asks.
- **Card corner curve = 18px** (the classic-waterfall layout's curve). Single source
  of truth. All cards, photo frames, banner frames, hover overlays adopt this radius.
  Previous per-page curve values (varied 4px–16px) are deprecated.
- **Rationale:** Client wants visual consistency across F1/L*/A*/C* pages so the
  brand reads as one site, not stitched-together templates. Borderless + 18px
  matches Will Kemp's softer warm feel rather than Crafti's bordered editorial look.
- **Future agents:** Do NOT re-introduce black borders or per-page curves "for
  visual interest." If a section feels flat, fix with typography / spacing / accent
  color — not chrome.

### 2026-05-25 — Gallery split: C3 Teacher's Work / C4 Student Work

Client-introduced scope, not in original PRD §Pages. Confirmed in subsequent client
feedback round; PRD §Site Structure now lists both as Pages #8 and #9.

- **C3 Teacher's Work Showcase** — Google-Photos-style grid of Crystal's artwork.
  Hover overlay = View (lightbox) + Buy (Etsy outbound). Reuses existing gallery
  CSS pattern.
- **C4 Student Work Showcase** — collage layout supporting Portrait / Landscape / A4
  ratios on the same row. **Stable row height** is the load-bearing constraint: a
  landscape tile in row N must have the SAME pixel height as the portrait tile
  beside it. Widths vary by aspect ratio; heights are locked per row. Hover overlay
  surfaces student username + avatar (NOT "View" / "Buy" — student artwork is not
  for sale).
- **Both** have lightbox on click + black-gradient hover overlay for legibility.
- **Implementation note for future agents:** Don't merge C3 and C4 back into a
  single Gallery page; client explicitly wants the split (Teacher = sales-driven,
  Student = community-driven, different overlays).

### 2026-05-25 — Shopping cart in header + Etsy bridge (Phase-1 commerce plan)

Two related decisions captured together because they form the launch commerce
posture.

- **Shopping cart visible in F1 site header** (badge w/ item count). Drives the
  per-course / per-lesson WooCommerce purchase flow defined in PRD §Membership and
  §Pricing. Implemented in F1 nav; cart drawer/page pattern reused from A5/A7.
- **Etsy bridge for teacher artwork.** Native painting/equipment commerce is
  Phase 2 OUT. To still let visitors purchase Crystal's paintings at launch, the
  C3 Teacher Gallery's **Buy** button is an outbound link to the matching Etsy
  listing (`target="_blank"`). Etsy handles checkout, fulfilment, payment, returns.
  No WooCommerce SKU for artwork at launch.
- **Why this is defensible vs §OUT "painting/equipment sales":** the §OUT line
  refers to *native* commerce on Crystal's own WordPress site (which would require
  a second WooCommerce product flow, separate fulfilment plumbing, shipping
  integration, etc.). Linking out to an existing Etsy listing is content-link
  authoring, not a new commerce subsystem. Documented under PRD §Site Structure
  §Etsy Bridge.
- **Future agents:** if client asks to "move sales back onto the site," that IS
  Phase 2 (separate quote). Etsy outbound is the launch-day answer.

### 2026-05-25 — Ingenioso banner DEEP teardown (parallax engine + entrance choreography)

Supersedes the earlier surface-level Ingenioso teardown. Inspected the live
`<rs-module id="rev_slider_7_1">` config, GSAP timeline, per-layer keyframes, and
mouse-parallax engine. **Important: this changes the in-scope/Phase 2 line for the
hero — see §Scope correction at the bottom.**

#### Module-level setup

- **Slider Revolution 6.7.34** "Hero" module (single slide, no rotation — that's
  why there's never a transition).
- Config:
  - `sliderType: "hero"`, `sliderLayout: "fullwidth"`
  - `gridwidth: 1920, 1440, 778, 480` + `gridheight: 900, 600, 480, 480` →
    virtual canvas all `data-xy` / `data-dim` coords are relative to. 4 numbers =
    SR scales whole scene proportionally per breakpoint.
  - `responsiveLevels: 1240, 1460, 785, 500`
  - `perspective: 600` + `perspectiveType: "global"` → **shared 3D camera** for
    whole module. Depth-based parallax doesn't pop layers into their own 3D
    contexts. (Caveat: `getComputedStyle` shows `perspective: none` at rest — SR
    injects it dynamically on mouse interaction.)
  - `lazyType: "smart"`, `progressBar: disabled`

#### The parallax engine (the heart of the effect)

```js
parallax: {
  type:   "mouse",           // cursor-driven, NOT scroll
  origo:  "slidercenter",    // (0,0) = geometric center of slider
  speed:  0,                 // instantaneous response (no easing/lag on follow)
  levels: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 15, 17, 20, 25, 30]  // 16 depth tiers
}
```

- **Mouse-driven, NOT scroll-driven.** Scroll page = nothing parallaxes. Move
  mouse = every layer reacts.
- **`origo: slidercenter`** → layers translate OPPOSITE to cursor offset from
  center → classic "look around the scene" feel.
- **16 depth tiers (1–30).** Each `<rs-layer>` gets a level. Low (1, 2) = shifts
  few pixels. High (25–30) = shifts dramatically. Foreground/background
  separation. Orange headline + outline word sit on different levels from the
  5 photos → type appears to float in front of imagery.
- **Global perspective** means high-level layers also pick up subtle Z-axis skew
  relative to low-level layers, not just translation.

#### The 4-second cascading entrance (GSAP timeline, 7 layers)

Each layer has `data-frame_0` (start state), `data-frame_1` (entrance), and
`data-frame_999` (exit). Timing in ms from slide start (`st` = start, `sp` =
duration, `e` = easing).

| Order | Layer        | Type  | Start  | Duration | Easing      | Technique                                                                  |
|-------|--------------|-------|--------|----------|-------------|----------------------------------------------------------------------------|
| 1     | layer-0      | image | 670ms  | 1500ms   | power3.out  | Slide in from `x:110%` + `skX:3px`, revealed through mask wiping `x:-100%` |
| 2     | layer-8 (headline)  | text  | 780ms  | 1500ms   | power3.out  | Per-WORD reveal (`d:8` = 8ms word stagger), each word rises from `y:100%` behind mask, `power2.out` |
| 3     | layer-16 (outline "creative for tomorrow") | text | 1380ms | 2000ms | power3.out | Per-CHARACTER reveal (`d:12` = 12ms char stagger), each char rises from `y:100%` + rotates `-35deg → 0deg`, `power4.out` |
| 4     | layer-9      | image | 2120ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 5     | layer-1      | image | 2380ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 6     | layer-15     | image | 2590ms | 1500ms   | power3.out  | Same masked slide-in                                                       |
| 7     | layer-10     | image | ~2800ms| 1500ms   | power3.out  | Same masked slide-in                                                       |

**Design intent baked into the timing:**
- Composition does NOT assemble in reading order. First photo at 670ms, then
  headline at 780ms (intentional 110ms gap) — text appears to "answer" the image
  rather than land on top.
- Outline word starts 600ms after headline (1380 vs 780ms) + takes longer
  (2000ms vs 1500ms) — per-character rotation needs more time to feel graceful.
- All 4 later photos use IDENTICAL motion vocabulary (`x:110%, skX:3px`, masked
  wipe, `power3.out`, 1500ms) → read as one cascading "deal-the-cards" move, not
  4 separate gestures. 210–260ms staggers between them = single beat, distinct.
- Exits in `data-frame_999` have `st:w` ("wait for slide change"). Hero mode = no
  next slide = **exits never fire**. Mouse parallax takes over indefinitely.

#### Mask + skew: the signature wipe (every photo layer)

```
data-frame_0:       x:110%;  o:1;  skX:3px;
data-frame_0_mask:  u:t;     x:-100%;
data-frame_1:       e:power3.out; sp:1500;
data-frame_1_mask:  u:t;
```

- Layer starts 110% right + 3px horizontal skew. Mask starts 100% left.
- During 1500ms `power3.out`: layer slides left into place; mask slides right
  into place; they meet → image "revealed through a slit."
- 3px skew (eased out) = tiny perceived momentum a pure translate would lack.
- `power3.out` (cubic ease-out) decelerates quickly at end → photo "settles"
  rather than "stops."

#### Why text uses two different techniques

- **Headline (layer-8, orange)** = split into WORDS (`data-frame_0_words` /
  `data-frame_1_words` with `d:8`). Each word = mini-clip translating up from
  below clip-mask. Fast + crisp — for a sentence reader absorbs quickly.
- **Outline word (layer-16, "creative for tomorrow")** = split into CHARACTERS
  (`data-frame_0_chars` / `data-frame_1_chars` with `d:12`, `rZ:-35deg → 0deg`).
  Each glyph tumbles up + rotates from -35° to upright with `power4.out`
  (stronger ease-out than `power3`). Decorative typography (transparent fill +
  2px white stroke via `data-tst`) → per-char flourish reads as ornament, not
  copy.

#### Responsive scaling of motion

- Headline's `data-text` attr encodes 4 font sizes + line-heights + letter
  spacings:
  ```
  s:130,90,60,40       (font-size px at 4 breakpoints)
  l:150,110,90,50      (line-height)
  ls:-8,-8,-7,-3       (letter-spacing)
  ```
- SR interpolates these alongside positional `xo`/`yo` arrays.
- **Timings + easings + parallax levels stay identical across devices.**
  Choreography preserved; only dimensions scale.

#### Mobile = different module entirely (NOT same animation scaled down)

- Slider is **hidden on tablet/mobile** via `sc_layouts_hide_on_tablet
  sc_layouts_hide_on_mobile` classes on the parent section.
- A **static image-widget variant** is rendered on those breakpoints instead.
- Why: avoid running 7-layer GSAP timeline on low-power devices.

#### Total perceived duration

- 670ms first start + ~2800ms last start + 1500ms last duration ≈ 4.3s ceiling.
- With overlaps, **perceived completion ≈ 4.0s**. Mouse parallax engine takes
  over after.

#### SCOPE CORRECTION — supersedes earlier "Elementor does this natively"

Earlier note (2026-05-25 later, "Elementor capabilities VERIFIED") confirmed
Elementor Pro has native motion effects + entrance animations + mouse track. That
is true — but the FULL Ingenioso choreography requires capabilities Elementor
native **does NOT provide:**

| Ingenioso feature                                       | Elementor Pro native? | Status            |
|---------------------------------------------------------|-----------------------|-------------------|
| Mouse-track parallax (single-layer)                     | YES                   | In-scope          |
| Per-layer absolute positioning + per-device coords      | YES                   | In-scope          |
| Built-in entrance animations (37 presets)               | YES                   | In-scope          |
| **Shared 600px global 3D perspective** across layers    | NO                    | Custom CSS needed |
| **16-tier depth-based parallax** (different multipliers per layer) | PARTIAL — single-layer only | Workaround per-layer |
| **Masked-wipe reveal** (layer + counter-mask sliding to meet) | NO — Elementor entrance presets don't include masked reveals | Custom CSS / GSAP |
| **Per-word text stagger** entrance                      | NO (single block animates as one) | 3rd-party addon (e.g. Premium Addons, Crocoblock) or custom |
| **Per-character text stagger** + rotation (`rZ:-35→0`)  | NO                    | 3rd-party addon or GSAP |
| Mobile-swap to entirely different static widget         | YES (Elementor responsive controls) | In-scope          |

**Net assessment of full Ingenioso clone:**
- Visually "70% there" achievable in Elementor native: stacked images +
  entrance fade/slide + single mouse-track parallax + responsive image sizes.
- "100% there" (masked wipes + per-char text choreography + 16-tier global
  perspective) requires either a 3rd-party Elementor addon (Premium Addons
  Pro, Crocoblock JetTricks, Slider Revolution-as-WordPress-plugin) OR custom
  CSS/GSAP work.
- **Slider Revolution itself IS available as a WordPress plugin** (~$30/yr).
  Installing it and importing an Ingenioso-style template = closest-to-1:1
  match without writing code. NOT currently in PRD §Third-Party Services
  Budget.

#### Revised scope position for the hero (current authoritative)

- **IN-SCOPE (delivers contracted Will Kemp/Proko-baseline direction +
  Elementor-native enhancements):** Layered DOM hero, single-direction entrance
  animations, single-layer mouse-track parallax, per-device positioning, mobile
  static-image swap. All Elementor Pro native. No new plugin cost.
- **STILL IN-SCOPE (small extra effort, defensible):** Build in Elementor +
  custom CSS for the masked-wipe reveal on 1–2 hero images. Bounded task.
- **STILL PHASE 2 (or "Slider Revolution license add-on"):**
  - Full 16-tier global-perspective parallax across 7 layers.
  - Per-word + per-character text reveal choreography.
  - Client-editable layered hero (the ORIGINAL Phase 2 line — unchanged).
- **Client decision needed if they want the FULL Ingenioso look:** add Slider
  Revolution plugin (~$30/yr — Crystal pays, like Elementor Pro) → Herman
  imports Ingenioso template, swaps images. Becomes one-time setup, mostly
  in-scope, **but Phase 2 image-swap line still applies** because SR templates
  are still per-breakpoint hand-tuned.

#### Future agents — read this before quoting hero work

- DO NOT promise full Ingenioso-equivalent in Elementor native alone. Earlier
  log entry was correct that motion effects exist; this entry corrects that
  the FULL choreography requires either 3rd-party addons, custom code, or
  Slider Revolution itself.
- DO offer the "70% in-scope, 100% via SR plugin (client pays $30/yr)" framing
  if client asks for exact Ingenioso match.
- Phase 2 image-swap line (per CLAUDE.md WhatsApp 2026-05-25 + PRD §Hero
  Banner Specification) is UNCHANGED regardless of which path is chosen.

### 2026-05-25 (final) — PRD §Hero Banner Spec expanded with formal Phase 2 boundary table + concession rule

Client requested PRD §Hero Banner Spec be updated "in detail" so the in-scope vs
Phase 2 boundary is defensible in writing (not just in CLAUDE.md decision log) for
the Ingenioso deep-teardown primitives. Done.

#### What changed in PRD §Hero Banner Specification

1. **New "Reference teardown" subsection** at the top — lists Ingenioso's actual
   stack (Slider Revolution 6.7.34 + GSAP) with the exact primitives (16-tier
   levels array, 600px global perspective, masked-wipe + skew, per-word + per-char
   split-text, power2/3/4-out easings, 4s timeline w/ named staggers). Marked
   **informational, NOT a build spec** — so the document records what was asked
   for without committing to it.

2. **"What gets built (IN SCOPE)" table** — maps each Elementor-native primitive
   to its Ingenioso parity rating (✅ matches / ⚠️ partial). Targets ~80%
   perceptual parity at a glance. No GSAP, no SR, no SplitText.

3. **"What stays Phase 2" table** — every Ingenioso primitive that Elementor
   native cannot deliver gets its own row with:
   - Why excluded (stack mismatch)
   - What it would require (custom JS / GSAP / SplitText paid lib / SR license)
   - Indicative effort (0.5–3 dev days each)

   Items individually named: 16-tier shared-perspective parallax, masked-wipe-w/-skew,
   per-word split-text, per-character split-text w/ rotation, GSAP timeline
   choreography, 4s cascading entrance w/ overlapping staggers, per-char glyph
   rotation, Slider Revolution module itself, client-editable layered hero,
   site-wide broken-grid, entrance system for arbitrary content.

4. **NEW "Concession path" rule** — within HK$15,000, client may pick **ONE**
   Phase 2 flourish to absorb under the 1-month post-launch support buffer,
   provided:
   - Hand-coded as a single GSAP/Motion One snippet (no SplitText paid lib, no SR)
   - Applies to ONE specific element only (headline OR portrait OR ≤3-tier depth)
   - Hero stays locked post-build (snippet is part of locked template)
   - Second flourish OR scope extension = re-quote

   This is **goodwill within scope, not a precedent**. Written default = the
   5–7-element Elementor-native build.

#### Why this matters for future agents

The earlier in-line PRD spec was short and could be read as "we'll match
Ingenioso." After client supplied the GSAP-level teardown the answer became
"~80% match in scope, the rest is Phase 2." This expansion makes that answer
explicit at the spec level rather than relying on this CLAUDE.md log.

- If client asks "can we have the masked-wipe?" → point to PRD §Hero Phase 2
  table row "Masked-wipe-with-skew" + offer Concession path (one flourish only).
- If client asks "can we have split-text on every heading site-wide?" → Phase 2,
  re-quote (SplitText is paid lib + site-wide application is not one flourish).
- If client asks "can we install Slider Revolution and import the Ingenioso
  template?" → PRD §Hero Phase 2 table row "Slider Revolution module itself" +
  CLAUDE.md earlier entry (2026-05-25 SCOPE CORRECTION) framing: ~$30/yr client
  cost, 2–3 dev days, Phase 2 image-swap line still applies.

### 2026-07-22 — Prototype shipped to GitHub Pages: course split, real assets, Drive video

Full record: `docs/DECISIONS-2026-07-22-prototype-build.md`. Compact version:

- **Live demo:** https://herman925.github.io/crystal-art-school/ — repo
  `herman925/crystal-art-school` IS the `Sample Website/crystal-art-school/`
  folder (its own git repo; the project root is NOT one).
- **Clouds split** into `clouds-beginner` (12 lessons) + `clouds-intermediate`
  (7, source folders still named Lesson 13–19 but displayed 1–7), plus
  `little-house` / `urban-sketch` as 1-lesson layout tests. Slugs are load-bearing.
- **Data layer:** content generated from `assets/` by
  `tools/import-assets.py` + `tools/build-data.py` into `js/*-data.js`
  (never hand-edit those). Hand-edited copy lives ONLY in
  `tools/content-meta.json`; Drive video IDs in `tools/video-links.json`.
- **Video = Google Drive `/preview` iframes.** No video files in the repo.
  KNOWN LIMITS: file IDs are public JS (no real paywall), Drive quota throttling,
  and the files are currently shared "anyone with link: WRITER" — must be
  downgraded to Viewer. Final site = YouTube/paid hosting (Phase 2).
- **Missing videos** show the poster + "Video coming soon" chip (simulated demo
  player retired). Currently only Watercolor Airport (its `.mov` was never
  uploaded to Drive).
- **Socials canonicalised** in `js/site-footer.js`: IG `crystalauart`,
  FB `crystal430`, `crystalau430@gmail.com`. YouTube removed — old
  `@crystalsartschool` handles were fabricated demo copy.
- **UNAPPROVED PLACEHOLDERS — flag when presenting:** Course 02/03 titles, all
  lesson titles, all prices; gallery tags cycle Original/Print/Pet over
  mostly-pet photos per client's explicit「隨意放」instruction.
- **Open with client:** is `CloudsLesson5B.mp4` (distinct 22-min video, unwired)
  a Lesson 3 part 2 → Beginner becomes 13? Upload of `Watercolor Airport 2.mov`.
  Real titles/prices. Etsy shop URL (Buy buttons currently hit Etsy search).
- **PM rulings that stand:** `#lesson-count` says "4 courses" (AC amended, not
  code); card radius stays 16px — CLAUDE.md's 18px baseline applies to the
  Claude Design `styles-v5.css` bundle, NOT this prototype.

### 2026-07-26 — Crystal's first real blog posts + WordPress authoring model settled

Full record: `docs/blog-drafts/README.md` (+ one draft file per post, each ending
in a "Queries for Crystal" list). Compact version:

- **Crystal delivered 3 real posts** (`assets/04 - Blog/`, 2026-07-23) as raw text
  + photos only — she declined to format them. Blog1 = 21-item watercolour
  materials guide with 7 photos of **hand-painted, cut-out watercolour
  illustrations of every tool**, hand-numbered 1–21 (strongest brand asset in the
  project so far). Blog2/Blog3 = short personal essays, one cover photo each.
- **All 16 placeholder posts DELETED** from the prototype, plus the fake article
  that was hardcoded into `blog-post.dc.html` as the default, the 4 hardcoded
  homepage cards, and the dead `js/blog.js`. **The fabricated `reads` counters are
  gone site-wide** (they appeared in 4 places) — replaced with reading time. No
  invented numbers remain anywhere on the site.
- **New `guide` article layout** in `blog-post.dc.html`: lede → hero photo → TOC →
  numbered sections each with their own photo. Retired `list`/`steps`/`photo`/`qa`
  layouts and their CSS. Verified in Edge/Playwright: no console errors, images
  load, TOC anchors resolve, numbering runs 1→21.
- **Copy corrections applied without Crystal's sign-off**, and declared on-page in
  an amber "Developer's note" callout driven by a `devnote` field in
  `js/blog-data.js` (delete field = callout gone). Biggest: 留白膠 was
  machine-translated as "blank residue glue" → **masking fluid** (her own painting
  reads `MASKING FLUID`); Windsor → **Winsor** & Newton; Classens → **Claessens**;
  "chinchilla hair" → **squirrel** (灰鼠毛); "still dry" → "still damp"; "before
  use" → "after use". Blog2/Blog3 rewritten from passive voice into first person.
  **These must not go live with the note still showing** — either she approves and
  the field is deleted, or the posts stay internal.

#### WordPress authoring model — DECIDED (affects the whole build)

- **Post content = Gutenberg block editor. Post templates = Elementor Theme
  Builder.** Do NOT enable "Edit with Elementor" on individual blog posts — every
  post then becomes a layout project and Crystal will never publish alone.
- **No Custom HTML blocks in post bodies.** HTML pasted into a post becomes an
  opaque block she cannot edit visually or restyle. HTML stays *available* to her,
  never *required*.
- **Block Patterns: declined by Herman.** He does not want to supply templates.
  Consequence deliberately accepted: the authoring constraint gets *tighter*, not
  looser — every construct must be reproducible from the plain toolbar. This is
  why post 1 puts each photo **above** its list rather than beside it (image +
  list = two obvious clicks; Media & Text is a block she'd have to hunt for, and
  it collapses to stacked on mobile anyway).
- **`theme.json`: leave colour/typography controls OPEN.** I recommended locking
  the palette so she picks structure and the theme picks appearance; Herman
  declined — the editor is acceptable *because* it is unconstrained. With both
  patterns and palette limits gone, nothing prevents visual drift between posts.
  Chosen knowingly in favour of her freedom. If drift shows up later,
  re-introducing palette limits is a small change, not a rebuild.
- **What Herman told Crystal is accurate, but scope it when repeating it:** blog
  and article content is genuinely unconstrained. The single-post template,
  header/footer, and **the front-page layered hero are NOT** — that last one is
  the existing Phase 2 client-editable-overlap line and must not be eroded by
  "she isn't constrained" bleeding across from the blog.

#### Still open with Crystal

Real publish dates · the new `Journal` tag (existing taxonomy Practice/Tools/
Colour/Studio fits neither essay) · whether ~15 named tool brands get shop links
(same outbound pattern as the C3 Etsy Buy buttons) · permission to lift the
exposure on the underexposed stage-fright photo · Chinese versions (source
filename `WindsorEnglish.docx` implies a Chinese original exists).

### 2026-08-19 — Client's platform/plugin package reviewed; commerce engine switched to Tutor LMS native

Client (Crystal + Summy) returned `PluginChoices.pdf` — screenshots of each platform's
pricing page with their intended tier circled — in response to Herman's account-signup
request. Reviewed against Proposal §9 (Ongoing Costs) and PRD §Third-Party Services Budget.

#### Verdict per item

| Item | Their pick | Verdict |
|------|-----------|---------|
| Hostinger | Premium $2.99/mo, 48-month term | Runs, but **weekly backups only** (20 GB SSD, 2 GB RAM, 1 core, 40 PHP workers). Recommended **Unlimited** (50 GB NVMe, 3 GB RAM, 2 cores, 60 workers, **daily backups**) — +US$48 over 4 years. A site taking money and tracking student progress should not restore from a week-old snapshot. |
| Tutor LMS Pro Individual | Annual $199 **and** Lifetime $499 both circled | Annual matches Proposal §9 (~HK$1,550) exactly. Lifetime ≈ HK$3,890, breaks even at 2.5 years. **Client must pick one.** |
| Bunny.net Stream | "Europe & North America $0.01/GB" circled | **Misread — region is not a choice.** Billing follows viewer location: HK students bill at Asia & Oceania $0.03/GB, UK at $0.01/GB. Both zones must stay enabled or HK students get degraded delivery. Also: bunny bills a **US$1/month minimum** on accounts with active zones, so the "HK$25-55/yr" estimate has a ~HK$95/yr floor. |
| Elementor | **Essential $5/mo** | **FAIL.** Essential excludes **Custom code & CSS**, eCommerce features and Popup Builder, and ships 57 widgets not 85. Minimum acceptable = **Advanced Solo** (US$7/mo, US$84/yr ≈ HK$655) — which is *cheaper* than the One plan the proposal budgeted at HK$1,300. |
| Stripe | Standard | Correct, but the fee was understated in the signed proposal — see below. |
| PayPal | Business | Correct (Personal cannot serve as a checkout gateway). |
| Discord | Free tier | Correct. **Nitro is irrelevant** — it is a personal cosmetic subscription; the server-side product is Server Boost, and nothing in scope needs it. Do not let the client buy it. |
| Domain (crystalau.com) | **absent from the package** | Hostinger bundles a free domain for year 1 on 12-month+ terms. Must be registered now — PRD §Open Questions flags DNS access as URGENT for Week 1-2. |

#### Stripe fee understatement (needs proactive disclosure)

Proposal §9 and PRD both say "~2-3% per transaction". Stripe Hong Kong Standard is
actually **3.4% + HK$2.35** domestic, **+0.5%** international card, **+2%** currency
conversion. A UK student paying in GBP costs roughly **5.9% + fixed**. Target audience is
explicitly HK **and UK**, so this will surface on an early sale. Tell the client before it
does; it is a disclosure problem, not a scope problem, and it costs nothing to fix now.

#### Elementor — a pre-existing PRD error corrected

PRD previously justified the One plan with "Essential is $60/yr but lacks Theme Builder
needed for this project." **That was wrong.** Essential includes Theme Builder and Form
Builder. It lacks Custom code & CSS, eCommerce and Popup Builder. The correct and binding
reason to reject Essential is **Custom code & CSS** — needed to style Tutor LMS templates
and to hold the site-wide no-border / 18px-radius baseline. Corrected in PRD
§Third-Party Services Budget.

#### THE BIG ONE — WooCommerce dropped, Tutor LMS native eCommerce adopted

Herman asked whether Tutor LMS handles payment itself. **It does, since Tutor LMS 3.0** —
native cart, checkout, order management, coupons, tax, with PayPal / Stripe / Paddle /
Mollie / Klarna / Alipay / Razorpay / Authorize.net / Paystack / 2Checkout built in. The
signed proposal specified WooCommerce because that was the only option when it was drafted.

**Decision: switch to Native.** Full rationale, both user-journey diagrams, consequences
and accepted risks are in **PRD §Commerce Engine Decision (2026-08-19)** — that section is
authoritative, this entry is the pointer.

Load-bearing points a future agent must not lose:

- The WooCommerce path splits the student account across two plugins (courses in the Tutor
  dashboard, order history in Woo's "My Account"). That conflicts with the **in-scope**
  Dashboard row, which promises courses + progress + **purchase history** + settings in one
  place. Native satisfies the contracted promise by default; Woo needs extra work to.
- **Tutor LMS Pro is now mandatory** — the Stripe gateway requires an active Pro licence.
  The proposal FAQ line "Tutor LMS free version can be used initially" is no longer true for
  anything that takes cards.
- **Only one engine can be active at a time**, and switching after real orders exist requires
  migration. Decide before the first live sale.
- **Elementor's eCommerce widgets stopped being a reason to buy a higher tier** the moment
  Native was chosen — cart/checkout are Tutor-rendered. Advanced Solo is still required, but
  on **Custom code & CSS** grounds. (Herman was told the eCommerce reason first; it was
  corrected in the same session. Do not re-cite the eCommerce argument to the client.)
- **Fee unchanged.** §Development Fee Breakdown "Membership + Payment — HK$4,000" is priced
  on the delivered outcome, not on plugin count. Relabelled, not repriced. If the client
  asks why it is not cheaper: the fee buys a working payment + membership + unlock flow, and
  the time saved goes into dashboard work and testing. Herman's call, not the agent's.

#### Scope pressure this creates — do NOT silently absorb

PRD §OUT OF SCOPE justified excluding **Course Bundles** with "Tutor LMS free tier doesn't
support bundles". Pro is now being bought, and Course Bundles is a Pro add-on, so that
stated reason has evaporated. The PRD row is marked **DECISION NEEDED**. Bundles stay OUT
until Herman explicitly rules otherwise: the contracted deliverable is individual course
sales, and bundle packaging/pricing is client content work that was never scoped. The same
logic applies to Subscriptions (Pro-native, still §OUT, "not requested at this stage").
This is exactly the accumulation pattern the 2026-05-25 entry warns about — each item feels
small, together they are an unscoped project.

#### Unverified — do not repeat to the client yet

Proposal §12 promises "The platform will allow for submission of refund request". Native
order management covers **admin-side** refunds and cancellations; a **student-facing refund
request** form is **not verified**. Confirm it exists, or plan a small form workaround,
before restating that sentence.

#### Revised Year 1 platform cost (all client-paid)

Hostinger Unlimited 48-month prepay US$191.52 ≈ HK$1,495 (covers 4 years) · domain free
year 1 · Tutor LMS Individual annual US$199 ≈ HK$1,550 · Elementor Advanced Solo US$84 ≈
HK$655 · Bunny ~HK$95 floor · Discord HK$0 → **≈ HK$3,795**, still inside the proposal's
HK$3,500-4,500 band.

**Sources (verified 2026-08-19):** hostinger.com/pricing · tutorlms.com/pricing ·
tutorlms.com/blog/tutor-lms-3-0/ · tutorlms.com/docs/native-ecommerce/native-ecommerce-overview/ ·
tutorlms.com/docs/tutor-lms-settings-monetization/ · tutorlms.com/docs/tutor-payment-gateway-stripe/ ·
bunny.net/pricing/stream/ · support.bunny.net (credit & billing) · elementor.com/pricing ·
stripe.com/en-hk/pricing

### 2026-08-19 (addendum) — Domain already at Namecheap; Hostinger free-domain perk is not redeemable

Herman flagged that Crystal had already bought the domain through **Namecheap**, which
makes the earlier "Hostinger bundles a free domain for year 1" note in the same-day review
inapplicable.

- **Confirmed:** Hostinger's free domain is **new registrations only**. It cannot be
  applied to a domain already registered elsewhere, and it cannot be applied to an inbound
  transfer.
- **Nothing was wasted in money terms.** It is a bundled perk, not a discount or account
  credit — the plan price is identical whether or not it is redeemed. The loss is ~US$10-15
  of unredeemed value, not a double payment. Do not let this be framed to the client as a
  mistake.
- **Split registrar/host is the better arrangement.** Domain not hostage to the hosting
  account; Namecheap `.com` renewals are typically cheaper than a host's bundled-domain
  renewal rate. Consolidating would trade that away for one year of savings.
- **Do not transfer to Hostinger to chase the perk.** Transfers are excluded from the offer,
  and ICANN's **60-day transfer lock** from registration date cannot be waived by any
  registrar.
- **Redeeming it against a different new domain is possible but rarely worth it.** Eligible
  TLDs exclude **`.co.uk` and `.hk`** — the two most useful defensive registrations for a
  HK + UK audience. Year 2 renews at Hostinger's standard rate, so an unused defensive
  domain is a recurring cost, not a saving.

**Still open:** *which* domain she actually registered — `crystalau.com`, `crystalauart.com`
or something else — is **not confirmed**. This matters: Proposal §12 and PRD both assume
`crystalau.com` is the new build and `crystalauart.com` stays on Wix. Ask before planning
the Week 13-15 cutover. Also still needed: Namecheap login or delegated access.

**New decision surfaced:** nameservers → Hostinger (simplest; auto SSL, staging subdomain,
email) vs DNS records kept at Namecheap (host-independent, all records manual).
Recommendation is nameservers → Hostinger. Comparison table is in PRD §Domain Strategy.

**Sources (verified 2026-08-19):** hostinger.com/support (free domain eligibility + TLD
list) · icann.org/resources/pages/name-holder-faqs-2017-10-10-en (60-day transfer lock)

### 2026-08-19 (addendum 2) — Client asked whether BR / UK tax number are mandatory, or personal accounts suffice

Client message: 「無論係香港或者英國我哋必須要有 BR (HK) 同 valid 嘅英國稅務識別號碼先？」
Full write-up: **PRD §Merchant Account & Tax Prerequisites**. Compact version:

**Hard boundary — Herman is not their tax adviser and must not answer as one.** State the
platform facts, flag the legal obligations, hand the rest to their HK accountant and, if UK
sales matter, a UK VAT adviser. Do not opine on liability or VAT treatment.

- **The question conflates two things:** what the processor accepts vs what the law requires.
  Different answers.
- **PayPal Personal is ruled out** — a Business account is required to receive payments
  through an online-store checkout. Upgrade is free. PayPal HK asks for a BRN in business
  KYC. The proposal's in-scope "Course Purchase — Stripe or PayPal" cannot be delivered on a
  Personal account.
- **Stripe is ambiguous — do NOT promise either way.** Stripe HK directs sole proprietors to
  "Sole Proprietorship" with a BRN, but separately references "merchants signing up as
  individuals" who need a **HKID** (non-HK IDs rejected for individuals). Verify with Stripe
  before telling the client.
- **BR is HK law, not a Stripe/PayPal quirk.** Business Registration Ordinance **Cap. 310**:
  register with the IRD within **1 month of commencing business**; failure carries a fine and
  possible imprisonment. **Using a personal account does not remove the duty** — it just means
  trading unregistered. This is the actual answer to "is BR mandated".
- **BR fee is a real unbudgeted cost.** IRD publishes 1-year and 3-year rates that change with
  budget announcements and waivers — **do not quote a figure from memory; confirm with IRD or
  their accountant.** Deliberately kept out of §Third-Party Services Budget (statutory fee,
  not a platform cost) but the client should know it exists.

**The UK part is not about a "tax ID" at all — it is UK VAT, and it is the bigger risk:**

- Opening a HK Stripe/PayPal account needs no UK tax number.
- But a seller with **no UK establishment has no VAT registration threshold** — VAT is due
  from the **first** B2C sale to a UK consumer at **20%**.
- **Classification is decisive:** pre-recorded video + PDFs with **no human interaction** =
  a digital service (rules above bite). Pre-recorded video + PDFs **plus live tutor support**
  = **not** a digital service.
- **This collides with the tier design.** Apprentice is pre-recorded self-study; **Fellow
  includes Discord access to the teacher** = live human interaction. Whether that shifts the
  classification is **for a UK VAT adviser** — raise it, never rule on it.

**Unscoped work this could create — quote separately, do not absorb:** VAT-inclusive vs
exclusive price display, Stripe Tax configuration, checkout flagging or blocking UK
addresses. None of it is in the HK$15,000. A legitimate alternative the client may prefer:
**restrict sales to HK at launch** and open UK later — but that is their business decision and
it contradicts the PRD's stated HK + UK target audience, so do not choose it for them.

**Timeline — de-escalate this, it is not an emergency:**
- **Development is NOT blocked.** Weeks 7-8 payment work builds and tests entirely in
  **Stripe test mode / PayPal sandbox**. No live merchant account needed.
- **Launch IS blocked.** KYC takes days to weeks and BR must exist first on the Business
  route. Target verified live accounts by **Week 12** for buffer.
- Tracked as PRD §Open Questions **#16-#20**. Client homework — flag it, do not chase it.

**Sources (verified 2026-08-19):** support.stripe.com (HK business requirements) ·
paypal.com/hk/business · ird.gov.hk (Cap. 310 business registration) · HMRC guidance on VAT
for B2C digital services supplied by non-established businesses

### 2026-08-21 — Two deliberate overrides of the 2026-08-04 Design Direction

Both changes were made on the client's own later instruction and both contradict a
line in `docs/20260804_Design_Direction.md`. Logged here because the direction
document is the spec: an override recorded only in a code comment will eventually
be "corrected" back by someone reading the spec and not the comment.

**The rule to apply, stated once:** the Design Direction records what was agreed on
**2026-08-04**. A later, dated, written instruction from Crystal supersedes it for
the specific thing she named — and nothing else. It does not reopen the surrounding
freeze, and it is not a licence to re-litigate the board. Log each one here.

#### Override 1 — About: the credentials section moved above the freeze line

- **Direction says:** the About board draws a line at `Demystifying the process` and
  marks everything below it 「以下全部不修改」, naming `A glimpse into the journey`
  explicitly as 「在界線以下，不動」.
- **She asked (2026-08-16, review ticket I011):** 「放在Demystifying the process上面」,
  pinned to the *International honours & exhibitions* card.
- **Built:** the whole `#highlights` section moved above `#philosophy`, not the one
  card. The card is the third of three in that section; lifting it alone would leave
  it with no heading and split the honours from the two cards that introduce them.
- **Reading order now:** biography → community → instructor → credentials → teaching
  philosophy → welcome.
- **If she meant the single card only,** it is one block move to change back.

#### Override 2 — Free Videos: the featured stage carries step-by-step demos

- **Direction says:** the featured row is `Studio Notes from Crystal Lau`, 「放最
  latest」, 16:9, with carousel dots, and Crystal's portrait beside the title.
- **She and Herman both asked (2026-08-16, tickets D005 and D006):** 「只放Step by
  Step demos影片」 / "Only for step-by-step demo section".
- **Built:** the stage reads the same section string the rail below reads. A Studio
  Notes section was never filled and nothing else on the site referred to it, so the
  live page had been showing an empty grey box reading "The latest free video will
  play here" — for weeks.
- **The heading moved with the content.** "Studio notes from Crystal Lau" over a
  stage of demos states something untrue. Everything else the board specifies for
  that row — latest first, 16:9, dots, her portrait — is unchanged.
- **Consequence deliberately not raised with her (Herman's call, 2026-08-21):** the
  featured film is also the first card in the rail below, so it appears twice. That
  is inherent to featuring a film from a row that also lists it. If she notices, the
  fix is a filter on the rail.

#### Two related changes that are NOT overrides

Recorded here only so a later reader does not mistake them for one.

- **About's essay panels and the pull-quote lost their drawn lines** (2026-08-20).
  The rules either side of each heading and the three-sided frame around each body
  were inherited from the ruled-notepaper sheet these panels used to be. The blog
  feature card — same leather, same saddle stitch — has neither. Herman's own design
  note; the dashed saddle stitch stays, and so do the two quotation marks.
- **The live-drawing strip became two stacked carousels** (2026-08-20). The board
  specifies a photo carousel for that block and still gets one. There are only six
  photographs, so the lower carousel repeats the same six, shuffled and rotated by
  half so the two never show the same picture at once. **Scaffolding: replace the
  lower deck when she supplies a second set.**

### 2026-08-21 — Defects found by inspection, not by the client

Two shipped faults that nobody had reported, both found while acting on her review.
Noted because each has a general lesson, not because the fix is interesting.

- **Twelve YouTube films had no cover photo.** `build-data.py` emitted
  `poster: null` for every film Crystal supplied as a link, on the reasoning that
  "the embed brings its own thumbnail". True of the player once it opens; not true
  of the card in the rail, which is what a visitor sees. The whole Shorts row was
  empty dark rectangles. Fixed by `tools/fetch-youtube-thumbs.py`, which needs the
  network and must run **after** `import-assets.py` and **before** `build-data.py`.
  Its output lives in `tools/youtube-thumbs.json`, deliberately NOT in
  `asset-manifest.json`, because the importer rewrites that file's `freeVideos`
  wholesale and would silently drop it.
  **Lesson:** "the third party will supply it" is an assumption about a different
  surface than the one being built.
- **The asset pipeline was write-once, not incremental.** `encode_image` skipped on
  the EXISTENCE of its output, so a slot fed by a different source file — or a
  source the client replaced — kept the old picture forever and said nothing. This
  is why Crystal's lesson cover photos never appeared after she supplied them on
  2026-08-16, and why she had to raise it (ticket F006). It now compares
  timestamps, and a rewritten master forces its derivative ladder.
  **Lesson:** when the client says "you are showing the wrong picture", suspect the
  pipeline's skip logic before suspecting the data.

**Standing instruction for the importer:** a full `import-assets.py` run rewrites
the gallery and static sections too. Diff `tools/asset-manifest.json` before and
after every run and confirm `gallery`, `static` and `freeVideos` are byte-identical
unless you meant to change them — this is the pass that once pulled one of the
client's private feedback boards into the gallery as a £320 painting.

### 2026-08-22 — Hero lock decided: locked build now, fixed slots become the quotable Phase 2

Settled while standing up the WordPress install. Herman initially recalled the agreed
position as "shape locked, images swappable by Crystal". The written record says the
opposite, consistently: PRD.md line 67 ("**In-scope:** Herman builds hero once w/
client-chosen images. **Phase 2:** unlimited self-service image swaps without breaking
responsive composition"), PRD.md lines 260-262 (image swaps after launch require Herman;
first one under the support window, subsequent ones quoted), three separate entries in
this log dated 2026-05-25, and the WhatsApp line actually sent to the client. Recording
the correction so the recollection does not resurface as fact.

**Decision:** build the hero locked, and keep the fixed-slot approach as the defined
deliverable if the client later pays for self-editing.

- **Locked build:** hero lives in an Elementor Theme Builder template. Swapping an
  overlap image requires Herman. Headline and CTA text stay client-editable, per
  PRD line 262.
- **Phase 2 now has concrete scope:** aspect-ratio-locked containers with
  `object-fit: cover`, so any uploaded image crops to its slot instead of displacing
  the composition. Absolute positions bind to the slot, not the image, which removes
  the per-breakpoint re-tuning the PRD names as the cost driver. Roughly half a day of
  CSS. This turns "client-editable hero" from an open-ended promise into something
  quotable.

**Why not simply build the slots now:** it would grant for free the single item that
the PRD, this log, and a client message all name as the paid extra. Rejected knowingly,
not overlooked.

**The nuance that caused the confusion, stated once:** nothing prevents Crystal from
swapping a hero image. What was never sold is the guarantee that the composition still
works afterwards across breakpoints. That distinction is clean on paper and useless in
practice, which is exactly why the guardrail says to lock it — if the editor permits
the action, the distinction collapses the first time she takes it.

**Consequence a future agent must not miss:** Elementor's Role Manager cannot restrict
Administrators. A locked hero therefore requires that Crystal's account is NOT an
Administrator. See the open question logged with this entry.

### 2026-08-22 (correction) — The hero lock entry above rests on a superseded premise

The entry immediately above reasons about a layered, overlapping hero and the Phase 2
line around client image swaps. That fight belongs to May 2026. It is not the hero this
project is building, and the entry should not be cited as if it were.

**What the hero actually is.** `docs/20260804_Design_Direction.md` §3.2 specifies a
three-slide carousel: 「Hero 保留三張 Slides」(:462), each slide carrying a main title,
a description, one large image and one small image (:471-475). Verified in the built
prototype's own markup — `index.html` uses `hero__slide`, `hero__img--large`,
`hero__img--small`, with arrows and a slide counter. No stacked layers, no parallax, no
entrance choreography. The Will Kemp baseline won; Crafti/Ingenioso did not.

**The document opens hero images for editing.** §3.2 lists title, description, large
image and small image as per-slide editable fields. Locking them contradicts the agreed
document.

**Crop-to-slot is already in scope, not a Phase 2 concession.** :477
「圖片由系統按版面比例顯示及裁切」 — the system crops uploads to the layout ratio — and
:476 requires Herman to publish recommended dimensions per slot in the admin. The
per-swap breakage risk that justified locking largely does not arise for a carousel with
fixed slots.

**Standing decisions, restated:** build the carousel with editable slide fields, native
Elementor widgets, crop-to-slot sizing, and published per-slot dimensions. The
`cas-hero-lock` mu-plugin stays installed with an empty `cas_locked_template_ids` list —
it costs nothing and is the mechanism if a genuinely locked template is ever needed.

**Lesson for future agents:** CLAUDE.md's May 2026 entries are a record of a scope
argument, not a build spec. `docs/20260804_Design_Direction.md` and the built prototype
are newer and authoritative. Check the prototype's markup before reasoning from this log
— one grep of `index.html` would have caught this before a decision was recorded.

### 2026-08-23 — Course video moved to Bunny Stream; 4 courses imported as drafts

Video hosting and the course catalogue were both stood up on the live WordPress site.

**Bunny Stream library:** `Crystal Art School`, ID **734616**, replicated to **UK + SG**
(origin near the Hostinger UK server, and near HK students on a cache miss). Credentials
are in `.secrets/.env` as `BUNNY_LIBRARY_ID` and `BUNNY_LIBRARY_KEY`; the account key
Herman added is `BUNNY_API`. Storage is ~5GB, which at $0.01/GB/month is pennies — the
$1/month account minimum dominates, so the second region is effectively free.

**How the 22 films got there:** Bunny's fetch-from-URL endpoint pulled each one directly
from Google Drive server-side, so nothing was downloaded locally or staged on Hostinger.
Drive serves files over 100MB through `drive.usercontent.google.com/download?...&confirm=t`
without the virus-scan interstitial; the plain `uc?export=download` form returns an HTML
page instead and would have silently stored 22 web pages as videos. All 22 encoded, none
failed.

**Security posture — read this before claiming the videos are protected.** The pull zone
(6397141) allows referers `crystalau.com` and `*.crystalau.com`, with `BlockNoneReferrer`
on. Measured: `crystalau.com` 200, `iframe.mediadelivery.net` 200, `example.com` 403, no
referer 403. So direct stream URLs cannot be shared and foreign sites cannot hotlink the
stream — but Bunny permits its own iframe origin, so **anyone holding a video GUID can
embed the Bunny player on their own page and it will play**. What actually protects the
films is that GUIDs are only rendered on lesson pages, which Tutor gates behind
enrolment; a non-buyer never sees one except on the two free preview lessons. An enrolled
student could still redistribute. **Token authentication is the real fix and is scheduled
with the Weeks 7-8 payment work.** Do not describe the current state as "protected" to
the client without that caveat.

**Update 2026-09-30:** the signing code is merged (PR #98) and deployed, and the key was rotated (see the 2026-09-30 entry at the end). Enforcement at the video host is still UNVERIFIED, so keep the caveat above until issue #87 is verified.

**Correction to the 2026-07-22 entry:** it records the Drive masters as shared
"anyone with link: WRITER". That was fixed on 2026-08-09 — they now return
`{"role":"reader","type":"anyone"}`. The write exposure is gone; public read remains,
which is why the move mattered.

**Courses imported as DRAFT** — clouds-beginner (13 lessons, £72 → £58),
clouds-intermediate (7, £68), little-house (1, £24), urban-sketch (1, £24). Draft because
CLAUDE.md records course titles and all prices as unapproved placeholders, and the
direction document puts the content freeze at this migration. Lessons sit in a single
`Lessons` topic per course; video is Tutor's `embedded` source carrying a Bunny iframe.
The two opening Clouds lessons are Tutor preview lessons, matching the prototype's `free`
flag. The importer is idempotent — it matches on slug and skips.

**Store currency set to GBP/£** at Herman's instruction. It was USD by default while the
whole design prices in pounds.

**Open content question, unchanged since 2026-07-22:** `clouds-beginner` numbers its
lessons L01, L02, L04…L14 — thirteen lessons across fourteen slots, with **no Lesson 3**.
Either a film is missing or the numbering wants resequencing. This is the same
`CloudsLesson5B` question the earlier entry logged. Ask Crystal before publishing.

### 2026-08-23 — Handoff document

`docs/HANDOFF.md` is the current-state snapshot: what is built and live, where the
credentials are, the architecture decisions that constrain future changes, the traps that
have already cost time, the seven things that must not reach launch, and what is still open
with the client. This log stays the authoritative record of decisions; the handoff is what
to read first when picking the work up.

The 2026-07-22 file keeps its name deliberately — this log references it by exact path, and
renaming it would break that pointer silently.

### 2026-08-22/23 — WordPress build stood up: platform, architecture, and the decisions that constrain future work

The site went from nothing to a live WordPress install at https://crystalau.com over two
days. `docs/HANDOFF.md` carries the current-state snapshot; this entry records the decisions,
because a snapshot gets rewritten and a decision log should not.

**Platform, as actually purchased and configured** (supersedes the 2026-08-19 projections):

| Item | Actual |
|---|---|
| Hosting | Hostinger **Business**, order `1009902478`, **US$203.88/year recurring** |
| Datacenter | **UK** (`server660`), chosen by Herman over the recommended Singapore |
| WordPress | 7.1, PHP 8.3.30, table prefix changed off `wp_`, `DISALLOW_FILE_EDIT` on |
| Currency | **GBP/£** — Tutor defaulted to USD and the whole design prices in pounds |
| Tutor cart | Native eCommerce, `enable_tutor_native_cart` switched **on** (it ships off) |

The hosting line is materially more expensive than budgeted. §Third-Party Services Budget
assumed Unlimited on a 48-month prepay at US$191.52 covering four years; the actual is
US$203.88 *per year*. Roughly 4x the amortised figure. The tier is better and the renewal
price does not spike, but the PRD table is wrong and should be corrected.

**Datacenter note for the record:** Singapore was recommended because there is no Hong Kong
datacenter and Crystal plus the majority of expected students are in HK. Herman chose UK.
Measured consequence, not speculation: an Asian CDN edge shows `x-hcdn-upstream-rt: 0.257`,
so ~257ms on uncached dynamic requests — the LMS dashboard and checkout. Static assets are
edge-cached near HK regardless.

**Four architecture decisions that will constrain anyone changing this site:**

1. **Template parts, not Elementor widgets.** Header, footer, hero and every page are PHP in
   `inc/cas-*.php` exposed as shortcodes, placed via Theme Builder templates containing a
   single shortcode widget. Chosen because the prototype's CSS is written against specific
   markup — a three-column grid with an absolutely-positioned brand, a two-stage nav collapse
   at 1080 and 860, solid-fill pills that deliberately break the site's metal-finish rule.
   Reproducing that with Elementor's native widgets meant rewriting working, breakpoint-tested
   CSS and would have ended as "native widget plus a pile of custom CSS" — more moving parts
   than either pure approach. Menu items stay editable in Appearance → Menus either way.
   Herman's stated basis: he maintains the styling, in CSS, and is not handing it to a
   non-technical maintainer. **If that changes, this decision should be revisited.**

2. **Tutor owns `/courses/`.** Its course post type slug is literally `courses`, so a
   WordPress page of that slug is unreachable behind the archive — and Tutor further swaps the
   template through `template_include`, which runs *after* the theme hierarchy, so an
   `archive-courses.php` in the theme is never reached. Both the archive and the single-course
   templates are claimed back with a `template_include` filter at priority 999. A course page
   that suddenly looks like stock Tutor means that filter stopped winning.

3. **Elementor Pro caches Theme Builder conditions.** A template inserted over WP-CLI exists
   and never displays until
   `\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate()`
   is called. Both header and footer hit this. Any future template built this way needs it.

4. **`.cas-root` sits on `<body>`.** The prototype scoped its entire design system to
   `.cas-root[data-theme]` on a wrapper div; rewriting ~40 selectors was the worse option.
   Consequence, and it is not theoretical: any rule written `body, .cas-root { ... }` now
   matches the body at *class* specificity. `industrial-3.css:356` sets `overflow-x: hidden`
   that way, which forces `overflow-y` to compute as `auto` and made the body its own scroll
   container. The page still scrolled, so nothing looked wrong — but `window.scrollY` stayed
   pinned at 0, silently disabling every scroll-driven effect on the site. Fixed with
   `overflow-x: clip`.

**Content imported:** 4 courses / 22 lessons as drafts, video on Bunny Stream, lesson covers
attached, 2 free preview lessons. Draft because titles and prices are unapproved placeholders
— which now blocks three homepage sections that need published courses, so the sign-off is on
a longer critical path than it looks.

### 2026-08-23 — "Default-closed" was read as "hardcode everything", and it produced a real defect

Worth its own entry because the misreading is easy to repeat and the correction changes how
every remaining page gets built.

**What §1.4.14 actually says** (:309): 「規則：預設不開放；只有各章明文列出的欄位可以修改。」 —
default not open; only fields each chapter explicitly lists may be modified. It then tables
what is **generally** open — 標題文字、內文、圖片、連結、價格、標籤的值 — against what is
**generally** locked — 按鈕文字及目的地、區塊標題、圖示、版面結構、顏色、字體. The column
headers say 一般 ("general"). Those are defaults describing the shape of the rule, not a grant
and not a prohibition.

**The mistake:** reading "default-closed" as licence to hardcode anything not explicitly
named. That is wrong in the other direction — it treats the absence of a chapter grant as a
decision to lock, when body text and images are named as generally open and each chapter
supplies specifics.

**The defect it caused:** the FAQ was built as a hardcoded PHP array. §16 (:1264) states
plainly 「版面沿用現有 FAQ 結構，**問答內容可編輯**。」 — the question-and-answer content is
editable. An explicit chapter-level grant, so default-closed never applied. Fixed by moving
FAQ entries to a `cas_faq` post type. The same error had produced hardcoded social links and
contact email, both since moved to the Customizer.

**Consequence for the build:** an **editability register** now exists as the lookup, one row
per field — page, field, editable, where it is edited, and which line of the direction
document decided it. Three statuses: `OK`, `DEFECT` (document opens it, code hardcodes it),
and `UNDECIDED` (document is silent, so it is closed by default rather than by anyone's
decision). That last distinction is the point: "locked because we decided" and "locked because
nobody considered it" are identical in code and completely different in fact. The document is
silent on **Showcase, Free Videos, blog post pages and course detail** — those are open
questions, not settled policy.

**Customizer reorganised by page, at Herman's instruction.** It grouped fields by content
category — a developer's mental model. Now two panels: **Site-wide** (logo and signature,
contact and social) and **Pages** (one section per page). Post types cannot move into the
Customizer — a growing list has no sane UI there — so each page section carries a pointer
saying where its editing actually happens. The Customizer is the map even where it is not the
editor.

#### Three risk-bearing content decisions, recorded because they were made knowingly

1. **FAQ re-seeded verbatim from `assets/07 - FAQ/FAQ.docx`, at Herman's explicit
   instruction**, after being told what it would publish. §16 (:1258) requires the questions to
   come from that file. Her raw text carries two things the document elsewhere forbids: the
   phrase **"lifetime access"** in the Apprentice/Fellow answer (§7 :829-831 requires her
   confirmation before it appears anywhere), and a full **PayPal guest-checkout claim** which
   §16 (:1266-1268) describes as 「向開發方提出的核對要求，不是文案⋯⋯須由 Herman 逐點核對後回覆，
   不能原文照登」 — a checking request addressed to Herman, not copy to publish. Both are live.
   The prototype's version had already neutralised both; re-seeding deliberately reverted that.
   The PayPal claim promises card payment without a PayPal account, which varies by country
   and needs verifying for HK and UK before launch.

2. **Three legal panels withheld from About `#terms`.** The prototype ships four accordion
   panels; three are invented copy and the prototype's own comment says so — "Placeholder legal
   copy, NOT client-supplied: GBP pricing, VAT, UK GDPR and a UK cookie banner, for a Hong
   Kong-based artist." Concretely they publish "Under UK GDPR you can request... **Deletion
   requests are completed within 30 days**", a licensing/household-sharing limit, and a Cookies
   panel describing a banner this build does not have. Only *Purchases & refunds*, which
   commits to nothing, ships. **No replacement wording was written** — that would just be a
   second invented version by a different author. The `#terms` anchor stays because
   `cas-footer.php` links to `about#terms` from every page.

3. **The About contact form routes to `mailto:`.** The prototype's form showed "Sent — thank
   you!" while sending nothing. An inert form is not better than a dishonest one — the visitor
   types an enquiry, presses Send, and their message is lost either way. Mailto is the only
   option where her words actually arrive. The address comes from `cas_social()['email']`, not
   hardcoded, so it follows the Customizer. **Elementor Pro is licensed and includes Form
   Builder** — this should become a real server-side form that captures submissions before
   launch, because mailto fails silently for anyone without a configured mail client.

#### Two prototype defects found while porting, neither reported by the client

- **The highlighted certificate on About has no table row and no lightbox handler**, so the
  2026 Accademia certificate cannot be opened full size. The prototype's comment claims it
  "gets its own row back at the top"; the prototype's *code* moves the figure out of the rail
  before building the table from the rail. The port reproduces the code, not the comment.
- **The portrait `srcset` probed the wrong ladder.** `cas_about_img()` looked for
  `-128/-256/-384/-768` while `ProfilePic` ships `-96/-160/-384/-640`. Only one rung matched,
  so phones at 1.5–2 DPR fetched the 283KB master instead of the 79KB `-640` — on the LCP
  element, marked `fetchpriority="high"`. Widened the probe. Worth checking any other
  `srcset` helper against the files that actually exist rather than an assumed ladder.

### 2026-08-23 — Editability register built; DEFECT turns out to run in both directions

`docs/EDITABILITY-REGISTER.md` (and `docs/editability-register.tsv`) is now the field-level
lookup the previous entry called for: 161 rows, **83 OK, 24 DEFECT, 54 UNDECIDED**, each
citing the line of `docs/20260804_Design_Direction.md` that decided it and the code line
that implements it. This entry records only what the register changed about our
understanding; the register itself is authoritative for any individual field.

**The `DEFECT` definition had to widen.** It was defined as "the document opens it, the code
hardcodes it". The audit found that direction is the *smaller* half:

- **Class A** (document opens it, she cannot edit it) is 3 entries — and all three are
  sections that were never built, not fields wrongly hardcoded. Homepage About Crystal
  image, Courses tier description and card photo, two of four per-lesson fields. So the
  promises are unmet, but nothing is wrongly locked.
- **Class B** (code opens what §1.4.14 closes by default) is 19 fields, dominated by the
  **entire Tutor course surface** — title, cover, regular and sale price, currency, level,
  category, catalogue order, description, benefits, syllabus. Also the three brand images,
  the primary nav, and FAQ ordering.

Class B is almost certainly intended and mostly benign. It is recorded anyway because it is
unwritten: there is no baseline for "she changed it and the page broke", and two document
rules already assume auto-derivation she can defeat (DD:686, DD:694). Do not "fix" Class B
by locking it — the register's point is that it was never decided, and locking it would be
the same unconsidered move in the other direction.

**Two findings that are not editability**, caught while comparing document to code:

- DD:775-777 requires the course CTA to read `Add to Cart` before purchase and `View Course`
  after. `cas-course-single.php:94` emits "Add to cart" unconditionally; no purchase-state
  branch exists.
- DD:634-635 requires a member-terms clause stating that username and avatar may appear in
  student sharing. The testimonial cards do publish both. That clause sits in one of the
  three About terms panels withheld on 2026-08-23 for inventing UK GDPR wording — so the
  withholding and this requirement need resolving together, from her adviser, not from us.

**16 decision briefs** close the register, ordered by ongoing developer burden, each with
change frequency, cost of opening, cost of leaving closed, and a recommendation. The
recommendations are recommendations; the calls are Herman's. Three worth knowing without
opening the file:

1. **Course detail images** — DD:750 specifies five per course, one renders. Highest value
   per pound because the frequency is tied to revenue: every new course otherwise needs a
   developer to finish its page. Build with crop-to-slot containers.
2. **The About page** — 1,151 lines of hardcoded PHP including her own biography. Split it:
   open the prose and photos (cheap, high traffic, low risk), leave the honours table
   closed. That table is where an earlier build invented credentials, and where an unmasked
   certificate scan could be uploaded over a deliberately pixelated one. Opening any of it
   is a **new request** — chapter 13 never asked for a backend — not a defect being repaired.
3. **Seven missing homepage sections** — not a field to open but the homepage to build, and
   three of the seven are blocked on course titles and prices, not on code. The sign-off is
   the critical-path item.

### 2026-08-23 — Tooling: when to use the WordPress REST API, and why the wordpress.com plugin does not fit

Two related questions came up while building. Recording both so neither is re-derived.

#### The REST API is worth using, for content only

Everything built so far went through WP-CLI over SSH. That was not a workaround — it is the
only path for most of it. Splitting the two honestly:

| Task | REST API | WP-CLI over SSH |
|---|---|---|
| Create or update posts, courses, meta | yes | yes |
| Upload media **from the local machine** | yes, one authenticated POST | no — scp first, then `wp media import` |
| Write theme PHP | no | no (scp; `DISALLOW_FILE_EDIT` is on) |
| Install or activate plugins | partial | yes |
| Run arbitrary PHP | no | yes, `wp eval` |
| Flush rewrites, regenerate Elementor's conditions cache | no | yes |
| Options and DB work with no endpoint | no | yes |

**The structural work needs `wp eval`.** Reclaiming Tutor's templates, the Elementor
`Conditions_Cache::regenerate()` call, the table-prefix change — none has a REST endpoint. Do
not go looking for one.

**Media is the real win, and it recurs.** Uploads currently take two hops; REST is a single
POST to `/wp-json/wp/v2/media`. Five images per course, the gallery and the blog photos are
all still to come, so this is worth setting up rather than a one-off saving.

Auth is **Application Passwords** (WordPress core since 5.6), minted with
`wp user application-password create`. Two constraints:

- An Application Password carries the **full capabilities of its user**, so mint it against
  `cas_dev`, never against Crystal's `cas_owner` account.
- Hostinger sits behind a CDN. If REST calls are intercepted or rate-limited, SSH is the
  fallback — REST supplements WP-CLI here, it does not replace it.

#### The `build-with-wordpress` plugin is not usable on this project

Checked at version 0.0.2 rather than assumed. It does **not** use the REST API — zero
`wp-json` references; `CONNECTORS.md` says "No MCP server is needed" and drives everything
through `studio wp` (WP-CLI) plus direct file writes. Four independent mismatches:

1. Requires **WordPress Studio**, a local Automattic environment, with Claude started from
   the Studio sites folder. Ours is a live Hostinger server.
2. Generates a **block theme** — `theme.json` and block markup. Ours is a classic child
   theme of `hello-elementor` built from PHP template parts, for the reasons in the
   architecture entry above.
3. Deploys to WordPress.com or Pressable. Not Hostinger.
4. Its core value is **inventing a design** from a plain-English brief and offering three
   directions to choose from. The design here is contracted and the prototype is the spec —
   generating fresh directions would be actively harmful, not merely redundant.

Do not reach for it when a WordPress task appears. It solves a different problem.

### 2026-08-23 — Course photo strip built; course ownership and capabilities settled

`docs/EDITABILITY-REGISTER.md` ranked the course-detail images as the highest value per pound
of the 16 undecided areas. Built. The register row moves from `UNDECIDED` to a real editable
field.

**What the direction document actually specifies** (DD:745-760, §7 left column): one large
image above four small ones, five uploaded per course, then **CTA and price below them in the
same column**. Line 749 explicitly strikes out the original auto-carousel —
「改為點擊切換的縮圖列，不自動輪播」 — on the reasoning that a purchase-decision screen must not
have self-moving content, consistent with §3.5.2. **The strip does not rotate, and that is the
spec, not an unfinished feature.** Do not "improve" it with autoplay.

**Built as:** five attachment-ID meta keys (`_cas_course_photo_large`, `_cas_course_photo_1..4`)
registered with `register_post_meta`, so the sanitiser runs for WP-CLI and importers too, not
only the form. A *Course photographs* box on the course edit screen gives five media-library
pickers. `cas_course_photos()` checks `wp_attachment_is_image()` before trusting an ID, so an
attachment deleted from the library does not leave a thumbnail that clicks to nothing. Below
two pictures the strip element is not emitted at all and its script is not enqueued.

**No new CSS was written**, and this is the point of the template-parts decision: matching the
prototype's markup meant `industrial-3.css` already carried `.co-thumbs`, `.co-thumb`, the
active ring and `:focus-visible`. Crop-to-slot came free too — `.course-cover` is already
`aspect-ratio: 3/2` and `.co-thumb` `4/3`, both `object-fit: cover`.

**Five pickers, not four, deliberately.** The featured image is the catalogue card cover
(`cas-courses.php:83`) and both real courses ship a different file for card and for detail
hero. Welding them together would stop Crystal changing one without the other.

**A stale code comment had parked this work.** `cas-course-single.php` said the strip needed
"data the import does not carry". The source images were in `assets/01 - Courses/` under
exactly the naming DD:753 specifies; the importer simply never picked them up, and nobody
re-read the comment. Same shape as the `poster: null` bug on the YouTube covers — an assumption
about a different surface than the one being built. Comment now corrected.

**Correction to a brief I wrote:** I read `Course 01-Intermediate` as having "no A-D files, but
5 candidate large images". **小圖 means small image.** She supplied 小圖1-4 plus 小圖書5 — one
large and four small in a different naming convention. Both Clouds courses have full sets;
only Course 02 and Course 03 lack images, which is client content, not a bug.

#### Course ownership and capabilities — the blocker nobody had noticed

**No course on this site could be edited by anyone, including an administrator.** All four had
`post_author = 0`, and Tutor does not grant the `administrator` role
`edit_others_tutor_courses`. So `edit_post` was false for every course and every user: the
Courses list offered only "Trash | Duplicate". It also made the four courses **invisible to the
REST API**. Proven with a three-run controlled test (baseline 0 courses → with the two caps
4 → baseline again 0), not inferred.

**Fix applied, both halves, at Herman's decision:**

1. `post_author` set to `cas_owner` (1) on all four. Crystal owns her own courses.
2. `administrator` granted `edit_others_tutor_courses` + `read_private_tutor_courses`.

Authorship alone was tried first and is *sufficient for Crystal* but locks the developer
account out of courses in wp-admin **and** REST — WP-CLI over SSH is unaffected because it
bypasses capability checks. Herman chose to widen the role as well so REST course tooling stays
possible. Verified after: `user_can('edit_post')` yes for both users on all four courses,
`GET /wp/v2/courses?status=draft` as `cas_dev` returns 4, all four still draft.

**Caveat that will bite later:** the grant lives in the `wp_user_roles` option, not in Tutor's
code. A Tutor update that re-runs its role installer can wipe it, and the symptom is exactly
the original bug — courses vanish from REST and lose their Edit link. Check this before
diagnosing it as something new.

#### Traps and findings from this pass

- **The primary button on a draft course reads *Publish*, not Update.** Clicking it to test a
  save published Little House; reverted to draft within the minute, Tutor data confirmed
  intact. Use **Save Draft**, or save over WP-CLI / REST instead of the form.
- **Prefer `user_can($id, $cap, $post)` over `wp_set_current_user()` in `wp eval`** — it needs
  no session mutation and is harder to get wrong. One `wp eval` calling `wp_set_current_user`
  fatalled during this pass, with a trace ending at `pluggable.php:27`, which is the
  declaration line of `wp_set_current_user` itself. **Cause not confirmed**, and two wrong
  explanations were recorded before this one:
    1. "A plugin redeclared the pluggable function" — wrong. That would break
       `current_user_can` site-wide, and it does not.
    2. "Something on the `set_current_user` path misbehaved while the Tutor capabilities were
       absent" — also wrong. Capabilities are evaluated in `map_meta_cap`, nowhere near a
       function declaration, and no capability state can decide whether a function is declared
       twice. Tying it to caps was a milder version of the same mistake.
  A one-off double-load of `pluggable.php` inside a single `wp eval` fits the trace and leaves
  the site healthy, but that is inference, not evidence. **The evidence was destroyed by
  piping the command through `tail -5`**, which cut off the error message that would have named
  the cause — the exact trap this file already warns about. It does not reproduce now. Leave it
  at "unexplained, once"; do not attach it to plugins or capabilities.

- **`cas_dev`'s display name is "Herman"**, so a browser session signed in as the developer
  account looks like Crystal's. This hid the ownership blocker. `cas_owner`'s display name is
  literally `cas_owner`.
- **Application Password** `claude-code-tooling` minted on `cas_dev`, stored in `.secrets/.env`
  as `WORDPRESS_APP_PASSWORD*`. Resolves the caveat flagged in the tooling entry above: **REST
  auth passes the Hostinger CDN cleanly**, measured.

#### Left unfixed, deliberately

- **`tools/asset-manifest.json` mislabels the clouds-intermediate photos, rotated by one.**
  `p01.jpg` is 1486×1105 but is labelled `Intermediate小圖書5.JPG`, which is really 768×768.
  Labels come from config order, files from a sorted glob; the two coincide for
  `clouds-beginner`, which is why it went unseen. Effect: the **prototype's** Intermediate page
  leads with a mid-stroke still, the outcome `import-assets.py:293-297` reasoned itself out of.
  The WordPress side is correct because it was wired from the images, not the labels. Not fixed
  because touching that importer is how one of Crystal's private feedback boards was once
  published as a £320 painting.
- **No `aria-live` announcement on photo swap.** `CasAnnounce` / `.cas-sr-live` from
  `js/site-extras.js` were never ported. `aria-pressed` is what a screen reader announces
  today; a live region is shared plumbing and a separate piece of work.
- **`show_in_rest` stays `false`** on the five photo meta keys, copying `cas-gallery.php`.
  Arguably now the wrong default given REST media uploads, but it widens the public REST shape
  of the course object and that is a storage-contract decision, not an implementation detail.

#### Still unverified

Nobody has loaded the Courses list in a browser signed in as `cas_owner`. The capability checks
say the Edit link is there, and the photo box, media picker and save round-trip were verified
in a browser during an earlier pass. What lacks direct evidence is that the ownership change
restores the admin screen.

### 2026-08-23 (later) — Tutor LMS Pro has its own REST API, and it closes the topics gap

Herman found the Tutor API credentials. This amends the tooling entry above, which said media
upload was REST's only real win here. It is not.

**Stored** in `.secrets/.env` as `TUTOR_API_KEY` / `TUTOR_API_SECRET`. Basic auth, key as
username and secret as password, against `https://crystalau.com/wp-json/tutor/v1/`. Issued and
revoked in **Tutor LMS → Settings → API**.

**Different from the Application Password in a way that matters.** The Application Password
belongs to `cas_dev` and inherits that user's capabilities — which is why the tooling entry says
never to mint one on `cas_owner`. The Tutor key pair is issued by the plugin and is not tied to a
WordPress account at all. Separate revocation path; rotating one does nothing to the other.

**Measured, not assumed:** no credentials → `401`; with them → `200`; 42 routes.

| Route | Methods |
|---|---|
| `/topics` | GET (by `course_id`), POST |
| `/topics/{id}` | PUT, PATCH, POST, DELETE |
| `/lessons` | GET (by `topic_id`), POST |
| `/lessons/{id}` | PUT, PATCH, POST, DELETE |
| `/courses`, `/courses/{id}` | full CRUD |
| `/course-contents/{id}` | GET — the whole curriculum tree in one call |

**Why this is more than a convenience.** The `topics` post type is registered with
`show_ui => false` — it has no admin screen anywhere in WordPress. That, not the course edit
screen, was the one thing genuinely forcing anyone into Tutor's Course Builder UI, and it is why
the earlier answer to "is the Course Builder forced on us" was "only for the curriculum". Topics
now have full CRUD over HTTP, so creating, renaming, reordering and deleting them is scriptable.
The course edit screen was never forced: `courses` supports `editor` and the classic screen
carries title, description, price, benefits, audience and materials. `lesson` has
`show_ui => true, show_in_menu => false`, so its screen exists and is simply unlinked.

**Two asymmetries that will read as bugs at 2am:**

1. **`GET /lessons/{id}` returns 404.** The singular lesson route accepts writes only. Read a
   lesson by listing `/lessons?topic_id=N`, or take `/course-contents/{id}` for the whole tree.
   `/courses/{id}` and `/quizzes/{id}` DO accept GET — lessons and topics are the exceptions.
2. **`GET /courses` returned "Course not found"** while `GET /courses/34` returned that course
   fine. All four courses are drafts, so the collection endpoint is almost certainly filtering to
   published. Confirm that before writing anything that iterates the catalogue — a script that
   silently sees zero courses is worse than one that errors.

**What still needs `wp eval` over SSH**, unchanged: Elementor's `Conditions_Cache::regenerate()`,
`template_include` work, options with no endpoint, and anything touching theme PHP. REST — both
the core one and this one — is for content.

**No writes have been made with this key.** The routes above were read from the API's own
discovery document; the read endpoints were exercised, the write endpoints were not.

### 2026-08-25 — Working rules learned the hard way this session

Short, durable rules. Each cost real time or produced a false finding before it was written down.

**The theme lives on `origin/main`, and local `main` runs behind it.** `git fetch && git pull`
before reasoning about theme files locally. On a stale checkout `wp-content/` is an empty
directory shell, so `git ls-files -- wp-content` returns 0 and appears to prove the theme is
unversioned. It is not — 132 files since `97a355f`. This exact mistake produced a false finding
in the 2026-08-25 parity audit, which then reached the issue tracker and the user.

**Multi-agent: give each agent one brand-new file that no other agent owns.** Never assign two
agents to one existing file. Both collisions on 2026-08-23 were orchestration errors, not agent
errors: one task brief named `cas-about.php` for two agents, and separately, *replying to a peer
agent's message resumes it* — which spawned a twin alongside a workflow's own copy of the same
agent. They fought over one file until a duplicate-function fatal reached the live site. Tell
agents to make the safe choice and report, never to ask and wait. A single integrator wires
shared files afterwards.

**Parity audits: classify every gap as unbuilt / content-gated / dropped-on-purpose.** A
class-name diff between prototype and live is a smoke test, not a diagnosis — it cannot tell the
three apart and it matches `class=` inside JavaScript strings. The 2026-08-25 audit found 63
unbuilt against 67 content-gated; filing the second group as missing work would have been worse
than filing nothing. To see what a content-gated block *would* render without publishing
anything: `wp eval-file` with a temporary in-process `pre_get_posts` that admits drafts, plus
`do_shortcode()`. Writes nothing, needs no login.

**PHP error logging.** `WP_DEBUG_LOG` only takes effect when `WP_DEBUG` is true, and that flips
`error_reporting` to `E_ALL` — a deprecation flood on PHP 8.3 with Tutor and Elementor. Set
`log_errors` and `error_log` directly in `wp-config.php` instead: `wp_debug_mode()` does not
touch them on the false branch, so you get fatals and warnings only. The log is
`~/cas-logs/php-errors.log`, deliberately outside `public_html` because a log in the web root is
served as readable text. 180-day window pruned by entry timestamp, 1MB rotation, cron 05:00
(uid `2aWgVOiQub`). **Until 2026-08-23 `log_errors` was off entirely** — three multi-agent builds
ran with every fatal emailed to the owner and written nowhere.

**Hostinger environment.** No `crontab` binary — use the Hostinger cron API. No `python3` on
PATH; the interpreter is `/opt/alt/python311/bin/python3`. Bash calls on this box have been
observed executing twice — once inserting a PHP block into a theme file twice, once creating six
test posts twice. Read every mutation back; an exit code is not a result.

**Python writing files for the server re-adds CRLF.** `.env` and several prototype files are
CRLF. `pathlib.write_text` without `newline="\n"` puts carriage returns back, and a bcrypt hash
of `password\r` fails to verify while looking exactly like a wrong password.

### 2026-08-28 — Fellow tier moves from Discord to Tutor Q&A + Assignments

**This varies the signed proposal, and the reason it is defensible is chronological — record it
before anyone reads the diff and concludes a developer rewrote a contracted tier.**

`proposal_text.txt:96-99` defines Fellow *as* the Discord tier, with the mechanism explained to
the client at `:81-82` and `:230-231`. `PRD.md:169`, `:88` and `:686` say the same. The direction
document does **not** define what Fellow contains — its only tier passage (`:174-192`) governs
the cards' appearance and CMS editability — so nothing there conflicts.

**Why this is a return to the client's own preference, not a downgrade imposed on her**
(Herman's account, 2026-08-28): **Discord was Herman's counter-proposal.** On-site teacher
interaction is what Crystal asked for first. She raised hesitations about Discord — that
customers would need a separate channel to reach the teacher, and that it probably was not the
best fit. Discord was accepted on the explicit understanding that the LMS route would be
explored during the Tutor stage. It has now been explored, and Tutor supports it. So the switch
delivers the requirement rather than the promise, which is the standard this project works to.

**What Tutor actually provides** (measured from installed code, not vendor docs): Q&A is core
and already ON, per-course and threaded, and `Q_And_A::has_qna_access()` grants reply rights to
**any enrolled student** — so it is a course-scoped discussion board, not only student-to-teacher.
Assignments is a Pro addon, already licensed, enabled 2026-08-28. **What is genuinely lost:** the
community half. No cross-course space, no channels, no presence, no real-time. If the tier's
selling point were belonging rather than access to the teacher, this would be the wrong call.

**Where Discord is promised — 7 live, across 5 pages.** Developer copy: `inc/cas-home.php:30`,
`inc/cas-courses.php:485` and `:488`, `inc/cas-about.php:1105`, `inc/cas-showcase.php:387`. **Two
are Crystal's own words**, live FAQ posts ID 82 and ID 84, verbatim from `FAQ.docx` — those
cannot be rewritten without her. Twelve more sit in unported prototype files. The client guide at
`docs-site/site/` has **zero** mentions, so her manual needs no change.

**Traps for whoever does the edit:**
- `cas-courses.php:488` is the tier description, which direction `:186-190` explicitly opens to
  Crystal in the CMS. A stored Customizer value will override the PHP default — check it first or
  the edit will appear to do nothing.
- FAQ post **ID 84** is the loosest promise on the site: it says *any* course purchase grants
  Discord, contradicting every other surface, all of which gate it to Fellow. It also carries the
  unconfirmed "lifetime access" phrase that direction `:829-831` requires her to confirm.
- **Assignments does not inherit an existing promise.** Its only mention anywhere is that same
  FAQ answer, which names **Discord** as where assignments are submitted. Moving them into Tutor
  replaces her wording rather than fulfilling it.
- **No Discord invite URL or code exists anywhere in the codebase.** Nothing behind these
  promises was deliverable today either — `Dashboard.dc.html:376` already says so on-page.

**Open and NOT closed by this:** `PRD.md:455` and `:690` record that the Fellow tier's live human
interaction is the pivot for the **UK VAT digital-services classification**, still awaiting a UK
VAT adviser. Moving interaction on-site does not remove it, and graded assignments may read as
*more* interactive than an off-site chat server, not less. Do not treat this entry as having
resolved that.

### 2026-08-28 — All 28 Tutor Pro addons were disabled, including a contracted P0

`tutor_addons_config` did not exist in the database at all, so every one of the 28 Pro addons
returned disabled. Two consequences were live defects, not preferences:

1. **Free lesson previews — P0, contracted, publicly promised, broken.** `PRD.md:82`, `:204`,
   `:307`; direction `:193-197`. Crystal's own live FAQ (post ID 85) tells visitors *"Each course
   page features a video preview"*. Two lessons carry `_is_preview=1` (IDs 37 and 53) — the
   importer set them — but the addon that makes that meta do anything was off. **Enabled
   2026-08-28.** NOT verified end to end: both lessons sit in draft courses a logged-out visitor
   cannot reach, so re-check the moment courses are published.
2. **Course materials with bundled and per-item download — specified, never built.** Direction
   `:214`, `:891`; `PRD.md:168`; her live FAQ promises "downloadable notes". Built in the
   prototype at `lesson.dc.html:328-360`. **There is no lesson template in WordPress at all** —
   27 files in `inc/`, no `cas-lesson.php`. `cas-course-single.php:143` reads Tutor's plain-text
   "materials included" list, which is not files. `tutor-course-attachments` enabled 2026-08-28.

Also enabled: `tutor-assignments` (for the tier change above). Prior state backed up to
`~/cas-backups/tutor_addons_config.before.json`; reverting is one option write.

**Deliberately NOT enabled — `tutor-certificate`.** Zero support anywhere in the spec. Direction
§14 (`:1186-1201`) 證書 refers to **Crystal's own competition and exhibition certificates** on the
About page, and `FAQ.docx` Q8's "certificate of authenticity" is an Etsy artwork claim. No course
completion certificate is promised. Enabling it would be new scope, not a contracted feature.

**Also found, unresolved:** no purchase receipt or enrolment email is specified anywhere —
`receipt` and `confirmation` return nothing across the proposal, PRD and direction document. That
is a real hole in a site that takes money, and nobody has ruled on it.

### 2026-08-28 (evening) — Client removes the membership tiers entirely. Supersedes the entry above.

**The Fellow-tier entry earlier today is now history, not policy.** It recorded a switch from
Discord to Tutor Q&A + Assignments. Crystal answered on WhatsApp the same evening:

> 我決定簡單些，只提供觀看課程，不設批改及Q & A

No Q&A, no assignment marking. Herman then proposed dropping tier language altogether — paid
means the course videos, unpaid means the public pages — and she confirmed. **There is no
membership tier model any more.**

**Applied immediately** (2026-08-28, verified by read-back): `tutor-assignments` addon disabled;
`enable_q_and_a_on_course` set to 0. `tutor-course-preview` and `tutor-course-attachments` stay
enabled — both are separately contracted and unaffected.

**What this obsoletes.** Do not build any of it:

- The three-tier model itself — Wanderer / Apprentice / Fellow — including the `THREE WAYS TO
  STUDY` block on the Courses page and its three CTAs, and direction §1.4.8's tier table.
- Discord, in all seven live promises: `inc/cas-home.php:30`, `inc/cas-courses.php:485` and
  `:488`, `inc/cas-about.php:1105`, `inc/cas-showcase.php:387`, and FAQ posts **82** and **84**
  (her own words — the FAQ answers still need rewriting, now to describe a video-only product).
  Twelve more sit in unported prototype files.
- Q&A and Assignments as a tier benefit — the thing that replaced Discord for about two hours.

**This is a second, larger variation of the signed proposal.** `proposal_text.txt:96-99` defines
three tiers with Fellow as the Discord tier; `PRD.md:169` the same. Both are now superseded by
the client's own instruction. Record it as her decision, because it is: Herman proposed the
simplification and she confirmed it, and it reduces her ongoing workload rather than the
delivery.

**The consequence nobody has told her, deliberately** (Herman's call, 2026-08-28): removing human
interaction makes the **UK VAT digital-services classification clear-cut** rather than arguable.
`PRD.md:455` and `:690` record that live interaction was the pivot. Pre-recorded video with no
interaction is the textbook case *for* the classification: no registration threshold, VAT due
from the first UK B2C sale. Herman chose to let her adviser raise it rather than raise it
himself. **The client-message appendix was still corrected to describe the product accurately** —
that is not the same as raising the issue, and sending an adviser a description of a product that
no longer exists would be worse than saying nothing.

**Lesson worth keeping.** Two hours passed between deciding the Discord→Q&A switch and the client
removing the feature altogether. Nothing was built in between, which is the only reason this cost
nothing. The tier work was correctly sequenced behind a client reply that had not arrived.

### 2026-08-30 — Source-only membership cleanup delivered

The authorized source-only portion of issue **#65** landed in PR **#84**, merge commit
`58de80edf661f02dc7dc054095d0099ee591d842`, and was deployed and verified on the rendered site.
It removes the obsolete developer-owned tier/Discord/membership surfaces from Home, Courses,
About, Showcase and the footer. It does **not** rewrite Crystal-authored FAQ posts 82 and 84 or
invent replacement membership wording. #65 remains open for that authored-copy decision.

### 2026-08-30 — Course attachment shelf merged, deployment pending

The repository-only portion of issue **#58** landed in PR **#85**, merge commit
`0082497809f6572027c8c9f7a06011883bce53bd`. `cas-course-single.php` now resolves attachments
for the requested course through Tutor's verified `get_attachments()` API and reuses Tutor's
official `global.attachments` template. Existing attachment records are rendered by Tutor, and
the native empty state is preserved when none exist. No files, captions, prices or client course
content were created, uploaded or seeded.

The PR is merged into `main` but has **not** been deployed or live-verified. Keep issue #58 open
for Crystal-supplied notes/reference photographs and the separate integrator deployment and
served-output check. The implementation was verified with the focused contract, RED/GREEN
sabotage proof, existing parity contracts, Python compilation, PHP 8.3 lint (35/35 files), JS
syntax checks (15/15 files), `git diff --check`, and a zero-finding added-line security scan;
pytest was unavailable in the environment.

### 2026-08-31 — Completed login PR reconciled

PR **#79** was complete but had been left open after its deployment and live verification. It was
merged into `main` at `8ac76eaaaa188341d649f2abd94a960dcb8c5080`, the remote feature branch was
deleted, and the open-PR count is now zero. Issue **#43** was already completed; this cleanup made
no new live WordPress changes.

### 2026-09-30 — Incident, hardening, email and payments decisions

On 2026-09-21 an attacker used open signup and Tutor 4.0.7's `tutor_save_withdraw_account` (CVE-2026-78175) to plant PHP files and create an administrator. It was found and contained on 2026-09-30. Full record: `docs/incidents/2026-09-21-tutor-object-injection.md`. Decisions, so they are not re-argued:

- Tutor and Tutor Pro are on 4.1.0 (4.0.8 carried a silent fix; three further subscriber-reachable bugs were fixed only in 4.0.9). Check Patchstack through the Hostinger connector (`wordpress_plugins_list-installed`) before recommending any plugin version.
- Every secret held on the server was rotated. The Bunny value in wp-config `CAS_BUNNY_TOKEN_KEY` was the library API key; it now holds the pull-zone security key (`.env` BUNNY_TOKEN_KEY).
- Signup stays closed until map #99 is done: email verification (Tutor Pro `enable_email_verification`), the signed-in student page check, and the audit of logged-in Tutor actions.
- Never `wp user delete` or `get_user_meta` a suspect user: it unserializes their meta and fires the payload. Use raw SQL.
- A mu-plugin, `cas-audit-log.php`, logs security events to `~/cas-logs/audit.log`, emails Herman when any user gains the administrator role, and checks the admin count daily. `mu-plugins` stays at chmod 555.
- Email: Hostinger Email mailbox `hello@crystalau.com` (Starter Business trial to 2027-09-29, 100 sends a day), sent through SMTP with a `phpmailer_init` hook. Before this, WordPress `mail()` left from the web server address, which the domain's SPF record does not authorize.
- Base currency is GBP (Herman's decision). Tutor is single-currency. The Stripe account settles in HKD, so charging GBP adds a 2% conversion fee unless a GBP settlement currency is added. PRD line 66 was updated.
- Payments are built in test mode first: `tutor-stripe` 1.0.9 (Stripe Checkout Sessions), Stripe test keys, PayPal sandbox, a private test course 269. The Stripe keys named STRIPE_SECRET and STRIPE_PUBLISHABLE_KEY in `.env` are LIVE and are not used for tests. Live switch needs Crystal: HK Business Registration, PayPal Business, the UK VAT answer.
- `wp plugin update tutor-pro` prints the licence key in its output. Never paste that output.

