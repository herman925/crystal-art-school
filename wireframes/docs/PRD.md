# Crystal Education Project — Product Requirements Document

**Author:** hkkchan
**Date:** 2026-04-17
**Project Level:** L2 (Brand + Online Course Platform)
**Budget:** HK$15,000 (development fee)
**Timeline:** 15 weeks (looser workload + domain migration buffer)
**Payment Terms:** 50% deposit (start), 50% (before acceptance)

---

## Goals and Background Context

### Business Goal

Build a professional online art education platform that establishes Crystal's brand presence while selling courses. Website serves as both personal art teacher profile (with Xanga-like personal touch) and commercial course platform. Primary focus on organic growth through quality content and community, not paid ads. Year 1 target: 0–50 enrolled students.

### Background Context

Crystal currently runs offline art classes and wants to expand online. Previous site (crystalauart.com) was static/dated. Goal is a platform that reflects the artistic quality of the teaching while being modern and accessible.

Reference sites:
- **Proko** — Modern UI, video-first, clean interface, strong course previews
- **Will Kemp Art School** — Classical art school feel, warm tones, text-heavy, established brand

**Design direction:** Tone the style *between* Proko and Will Kemp — professional but warm, modern but not sterile. Art school sensibility meets contemporary web design. Detailed look-and-feel to be finalized in design phase.

---

## Scope Management

### IN SCOPE (Phase 1 — Included in HK$15,000)

| Feature | Details |
|---------|---------|
| UI/UX Design | Pencil prototypes, 3 revision rounds |
| Responsive Frontend | Mobile, tablet, desktop |
| Blog System | Basic blog with article pages |
| Course Management | Tutor LMS (free tier) |
| Membership + Payment | Registration, login, WooCommerce, Stripe/PayPal |
| Video Protection | Bunny.net + HLS + domain lock |
| SEO | Basic Google search optimization |
| Content Upload | First 5 courses上架 + testing |
| Dashboard | Course progress, resume, purchase history, account settings |
| Documentation | Admin operation manual |
| Support | 1 month technical support after launch |
| Hero Banner | Layered DOM hero (stacked images + text, real DOM not flat image) + per-layer entrance animation + desktop mouse parallax. Built ONCE by Herman with client-chosen images. Mobile = simplified (no overlap, Crafti-style). All via Elementor Pro native motion effects — no custom code. Hero template is **locked post-build** (see §Hero Banner Specification). |

### OUT OF SCOPE (Phase 2 — Requires Additional Quote)

| Feature | Reason |
|---------|--------|
| Painting/equipment sales | Separate e-commerce flow, not core to launch |
| Monthly/annual subscription model | Not ready per client decision |
| Advanced DRM (wider than HLS+domain lock) | Budget/timeline constraint |
| Dedicated discussion forum | Discord covers community needs |
| Newsletter/mass email system | Explicitly not needed |
| Tutor LMS Pro features | Free tier sufficient for launch; dashboard inner UI styling requires custom CSS (not via Elementor) |
| Course bundles | Tutor LMS free tier doesn't support bundles; sell courses individually at launch |
| Automated Discord bot for Fellows | Manual invite code sufficient; automated bot is Phase 2 if wanted |
| Additional language support | English only at launch |
| Mobile apps | Web responsive covers mobile browsers |
| Advanced analytics/tracking | GA/Search Console out of scope |
| Tutor LMS native teacher comments | Discord used instead; dev note: if LMS supports natively, easy add-on |
| GDPR cookie consent popups | UK GDPR compliance required (UK is a target market). Free plugin. Added to scope. |
| Multi-currency automatic conversion | HKD base only; GBP/USD display depends on Stripe config (no extra cost) |
| Client-editable layered hero (CMS self-service swap of overlapping images) | Swapping any layered image via CMS requires per-breakpoint X/Y/scale re-tuning so the composition stays coherent on desktop/tablet/mobile. No auto-layout engine for overlap. **In-scope:** Herman builds hero once w/ client-chosen images. **Phase 2:** unlimited self-service image swaps without breaking responsive composition. See §Hero Banner Specification. |
| Site-wide broken-grid / overlapping editorial layouts (Crafti / Ingenioso style applied beyond hero) | New direction introduced 2026-05-25 post-quotation. Contracted baseline = Proko + Will Kemp (carousel + grid + real-DOM text overlays). Site-wide overlap is a directional scope change, not a refinement. |
| Entrance + exit animation system for arbitrary client-uploaded content | Per-element entrance animations on Herman-built layout = in-scope (Elementor native). A system where any new content auto-receives staggered entrance/exit animations = Phase 2. |

---

## Priority Tiers

### P0 — Must Have (Launch blockers)

- User registration + login
- Email verification on signup (requires free WP plugin — not built-in)
- Course purchase flow (Stripe/PayPal)
- Video delivery + protection
- Course player with progress tracking
- Free course previews
- Mobile responsive design

### P1 — Should Have (Launch ok without, add ASAP)

- Blog system with comments and likes (registered users only)
- Discord integration for Studio Members (manual invite — automated bot is Phase 2)
- Course rating/review system
- Basic SEO

### P2 — Nice to Have (Post-launch)

- Advanced analytics
- Email notifications for new content
- Student community gallery
- Painting/equipment sales module

---

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| **Platform** | WordPress | Flexibility, client familiarity, easy to find developers later |
| **CMS** | Tutor LMS (free) | Purpose-built for courses, WooCommerce integration |
| **Payment** | Stripe + PayPal via WooCommerce | Widest coverage, automatic course unlock |
| **Video** | Bunny.net | Pay-as-you-go pricing, HLS + token auth, domain lock |
| **Page Builder** | Elementor Pro (One plan, $168/yr) | Industry standard for WordPress, fast development; verified 2026-04-17 |
| **Prototyping** | Pencil (MCP) | In-workflow design tool, Figma alternative |
| **Design System** | `design.md` | Structured spec before coding, reference for implementation |
| **Community** | Discord | Free, familiar, sufficient for teacher-student interaction |

### Design Revisions

- **3 rounds included** in HK$15,000
- Extra rounds: +HK$500/round
- Scope changes: quoted separately

---

## Brand Design System

### Color Palette

**Main Colors:**
| Name | Hex | Usage |
|------|-----|-------|
| Teal Mist | `#96D3D9` | Primary accent, CTAs, highlights |
| Forest Green | `#3D6150` | Headers, nav, secondary accent |
| Ink Black | `#15130A` | Body text, primary headings |
| Coral Rose | `#F59595` | Warm accent, badges, alerts |
| Sand Dune | `#D69C85` | Backgrounds, cards |
| Blush Cream | `#E5C1B3` | Soft backgrounds, dividers |

**Extended / Font Colors:**
| Name | Hex | Usage |
|------|-----|-------|
| Warm White | `#F5DDDA` | Light backgrounds, text on dark |
| Mint Foam | `#C5FAEE` | Highlights, hover states |
| Cream Paper | `#FCFAED` | Page backgrounds, cards |

### Typography

**Primary Font:** Quicksand (Google Fonts)
- Weights: 300 (Light), 400 (Regular), 500 (Medium), 600 (SemiBold), 700 (Bold)
- Usage: Body text, UI elements, navigation, subtitles

**Accent Font:** Permanent Marker (Google Fonts)
- Usage: Headings, brand moments, artistic callouts (sparingly)

### Visual Style

- Cover photos: 1:1 aspect ratio
- Artwork display: Gallery-style grid
- Reference hybrid: Proko's modern structure + Will Kemp's warm art school feel
- Tonal direction: Professional but approachable, artistic but not stuffy

---

## Membership & Access Model

### User Tiers (Confirmed: Wanderer / Apprentice / Fellow)

| Tier | Access | Price Model |
|------|--------|-------------|
| **Wanderer** | Site browsing, blog, free intro courses | Free |
| **Apprentice** | Buy-per-course, course materials, video access | Per-course purchase |
| **Fellow** | Buy-per-course-or-bundle + teacher interaction via Discord channel | Per-course purchase + Discord access |

**Key notes:**
- All users must sign up to access any content
- Email verification required on signup
- No monthly/annual subscription model (not under consideration yet)
- Multi-course cart enabled (students can buy multiple courses in one transaction)
- Course access duration: Client to decide (lifetime or time-limited)
- No refund policy
- Account deletion supported (GDPR compliance)

---

## Pricing & Revenue Model

### Course Pricing

- Per-course purchase (self-learn): TBD per course
- Per-course purchase (with Discord interaction): TBD + premium
- Bundles: TBD discount for multi-course packs

### Revenue Streams

1. Course sales (primary)
2. Painting/equipment sales (Phase 2)
3. Potential membership tiers in future

---

## Site Structure & Features

### Pages / Sections

1. **Home** — Brand intro, featured courses, testimonials
2. **Courses** — Browse all courses, filter by category (oils, acrylics, drawing, etc.)
3. **Course Detail** — Syllabus, preview videos, enrollment CTA
4. **Blog** — Art tips, student showcases, news (NO newsletter function)
5. **About** — Crystal's background, teaching philosophy
6. **Contact / FAQ** — Common questions, support
7. **Dashboard (post-login)** — Course progress, resume watching, purchase history, account settings
8. **Gallery — Teacher's Work Showcase** — Google-Photos-style grid of Crystal's artwork. Hover reveals **View** (lightbox) + **Buy** (external link to corresponding Etsy listing — see §Etsy Bridge below). Wireframe code: C3.
9. **Gallery — Student Work Showcase** — Mixed-orientation collage (portrait / landscape / A4) with stable row height. Hover reveals student username + avatar. Lightbox on click. Wireframe code: C4.

### Etsy Bridge (Phase 1 commerce for teacher artwork)

Painting/equipment sales remain Phase 2 OUT-OF-SCOPE for native commerce on Crystal's WordPress site. To still allow visitors to purchase teacher artwork at launch, each Teacher's Work gallery item's **Buy** button opens the corresponding **Etsy** listing in a new tab. Etsy handles checkout, fulfilment, and inventory. No WooCommerce integration for artwork at launch. Crystal maintains the Etsy listings and the wireframe Buy links resolve to those URLs (link list provided by client at content-upload phase).

### Excluded

- Newsletter/mass email (explicitly not needed)
- Discussion forum (Discord channel covers community needs)
- Monthly/annual subscription (not under consideration)

---

## Hero Banner Specification

Added 2026-05-25 after client reviewed Crafti + Ingenioso reference templates and
asked for a more "alive" / layered banner. Codified here to lock in-scope deliverable
vs Phase 2 boundary at the spec level.

### What gets built (IN SCOPE)

- **Layered DOM hero on Home page.** Stacked images (2–7 layers depending on final
  composition) + text + CTA, all real DOM elements (NOT a flat baked image).
- **Per-layer entrance animation** on page load (staggered fade/slide-in).
- **Desktop mouse parallax** via Elementor Pro Motion Effects (each layer drifts a
  different amount on cursor move).
- **One-time setup by Herman** using client-supplied images at agreed dimensions and
  positions.
- **Reference for visual feel:** Crafti / Ingenioso layered hero style is the look
  being approximated (within Elementor Pro native capability).

### Mobile / responsive

- Hero is **simplified on mobile** (Crafti pattern): overlapping image layers either
  hidden or reduced to background + text + CTA only.
- Mouse parallax is desktop-only (Elementor Pro Motion Effects does not run on
  touch devices — confirmed by Elementor docs).
- Tablet may show a reduced-layer version (final call during build).
- Rationale: full multi-layer overlap on small screens obscures text and requires
  per-image per-breakpoint hand-tuning, which is the Phase 2 cost driver.

### Implementation guardrail — hero template is LOCKED post-build

After build + client sign-off, the hero section is **locked** in WordPress
(template-locked / restricted editing in Elementor). Reason: the layered composition
relies on hand-tuned positioning per image. If a layer is swapped via the standard
editor without re-tuning, the composition WILL break on at least one breakpoint
(images of different aspect ratios + overlap = broken layout).

- **Image swaps after launch:** require Herman (covered by 1-month post-launch
  support window for the first request; subsequent swaps quoted separately).
- **Headline / CTA text edits:** remain client-editable (text layers are
  independent of image positioning).
- **Locking method:** WordPress role/capability restriction OR Elementor
  template-lock OR custom-CSS-driven layout that disallows freeform repositioning.
  Final mechanism chosen during build.

### What stays Phase 2 (NOT in HK$15,000)

- **Client-editable layered hero.** A system where Crystal swaps any of the
  overlapping images herself via the CMS and the composition auto-arranges across
  all breakpoints without breaking. Requires either a custom layout engine OR
  ongoing per-swap manual repositioning by dev.
- **Site-wide broken-grid / overlapping layouts** beyond the single Home hero.
- **Entrance/exit animation system for arbitrary new content** (any newly-uploaded
  image automatically receiving staggered motion).

### Reference / chronology (load-bearing)

- Contracted design direction = **Proko + Will Kemp** (per §Goals and Background
  Context). Both use carousel/grid layouts with real-DOM text overlays, NOT
  broken-grid or absolute-positioned overlapping artwork.
- **Crafti** (https://crafti.axiomthemes.com/) and **Ingenioso**
  (https://ingenioso.themerex.net/) were introduced by client on **2026-05-25,
  AFTER quotation**. Treated as feature-level visual inspiration where achievable
  in Elementor Pro native, NOT as a new contracted direction.
- Elementor Pro native support for layered positioning + entrance animations +
  mouse parallax verified via elementor.com/help and elementor.com/academy
  (2026-05-25) — see CLAUDE.md design direction decision log for full audit trail.

---

## Technical Requirements

### Platform

- WordPress-based
- Tutor LMS for course management (free tier)
- WooCommerce for payments
- Stripe/PayPal for checkout

### Video

- Bunny.net for video hosting (pay-as-you-go)
- HLS + token authentication + domain lock
- Free preview clips per course

### Dashboard Requirements (post-login)

- Course progress tracking
- Resume video from last position
- Purchase history
- Account settings

---

## Development Fee Breakdown

| Item | Cost |
|------|------|
| UI/UX Design (3 revisions) | HK$2,000 |
| Interface + Responsive | HK$2,500 |
| LMS Backend (Tutor LMS) | HK$4,000 |
| Membership + Payment | HK$4,000 |
| Video Security + Basic SEO | HK$1,000 |
| Content Upload + Manual | HK$1,500 |
| **Total** | **HK$15,000** |

**Support:** 1 month technical support included

---

## Detailed Timeline (15 Weeks)

### Phase 1: Foundation (Week 1–3)

| Week | Deliverables |
|------|-------------|
| **Week 1** | Design kickoff, collect assets from Drive, domain + hosting setup, Pencil prototypes (home, courses, about) |
| **Week 2** | Design review (rev 1), design.md drafted, revisions 2-3, final design approval |
| **Week 3** | Domain migration planning, staging environment setup, WordPress installation on staging |

**Client Homework:** Provide logo or brief, cover photos (1:1), course descriptions, test videos (1-2)

### Phase 2: Frontend Development (Week 4–6)

| Week | Deliverables |
|------|-------------|
| **Week 4** | WordPress setup, theme development, home + courses page, responsive layouts |
| **Week 5** | Blog system, about/contact pages, Elementor fine-tuning, cross-browser testing |
| **Week 6** | Continued frontend polish, mobile testing, client review checkpoint |

### Phase 3: Core Systems (Week 7–8)

| Week | Deliverables |
|------|-------------|
| **Week 7** | Tutor LMS install, course structure setup, WooCommerce integration |
| **Week 8** | Stripe/PayPal checkout, automatic course unlock, registration/login flow, test purchases, payment flow testing |
| **Week 9** | Payment flow testing, user registration testing, dashboard integration, final checks |

### Phase 4: Content + Protection (Week 10–12)

| Week | Deliverables |
|------|-------------|
| **Week 10** | Bunny.net setup, HLS encoding, domain lock configuration, token auth; first 5 courses uploaded; video protection verification |
| **Week 11** | First 5 courses uploaded, course player testing, video protection verification (VPN tests) |
| **Week 12** | Content upload completion, course bundle configuration, Discord integration |

### Phase 5: Polish + Launch (Week 13–15)

| Week | Deliverables |
|------|-------------|
| **Week 13** | QA testing, bug fixes, basic SEO setup, mobile final check |
| **Week 14** | UAT with client, final revisions, domain cutover planning |
| **Week 15** | **FULL PUBLIC LAUNCH** — DNS cutover, announce site, admin manual handover, post-launch support begins |

### Post-Launch

- 1 month technical support included
- Discord channel setup guidance for Studio Members

---

## Third-Party Services Budget (Year 1 — Client Pays Directly)

| Service | Recommended | Est. Cost (Year 1) |
|---------|-------------|-------------------|
| Domain + Hosting | Namecheap + Hostinger | HK$600-900 |
| Course Management | Tutor LMS Pro (Individual) | ~HK$1,550/yr (verified 2026-04-17) |
| Video Delivery | Bunny.net Stream | ~HK$25-55/yr (**corrected: was HK$300-600/yr**) |
| Page Builder | Elementor Pro (One plan, launch price) | ~HK$1,300/yr (**corrected: was ~HK$460/yr**) |
| Payment Processing | Stripe/PayPal | ~2-3% per transaction |
| Discord (community) | Free tier | HK$0 |
| Email Verification Plugin | WP Email Verification (free) | HK$0 |
| GDPR Plugin | GDPR Cookie Consent (free) | HK$0 |
| **Total** | | **~HK$2,500-4,500/yr** |

> **Corrections verified via live crawls (2026-04-17):** Bunny.net Stream ~$3-7/mo (not $15-40). Elementor One plan $168/yr (~$1,300 HKD) — Essential is $60/yr but lacks Theme Builder needed for this project.

---

## Migration Plan

### Domain Strategy

**Approach:** Build on staging, full public launch at Week 15.

1. **Staging Setup (Week 1-3):** Build site on temporary URL (password-protected), client reviews via staging URL
2. **Development Period (Weeks 1-14):** All development on staging, client reviews via staging URL
3. **Full Public Launch (Week 15):** DNS cutover to new WordPress server, announce site, begin marketing push

### Domain Handover Process

| Step | Action | Owner |
|------|--------|-------|
| 1 | Client provides domain registrar login credentials | Client |
| 2 | Developer reviews DNS settings, creates backup of current site | Developer |
| 3 | DNS change planned (TTL lowered 48hrs before cutover) | Developer |
| 4 | Final UAT sign-off from client | Client |
| 5 | DNS updated to point to new server | Client/Dev |
| 6 | DNS propagation verified (24-48hrs) | Developer |
| 7 | Old hosting kept live for 1 month as backup | Developer |

### Risk Mitigation

- **Risk:** Domain registrar access difficult to obtain from client
- **Mitigation:** Document exact steps, provide screenshots for client to follow; if all else fails, can launch on new domain temporarily
- **Risk:** DNS propagation delays affecting launch
- **Mitigation:** Stagger cutover with buffer day, notify users of potential brief downtime

---

## Content Collaboration

### Google Sheet for Client Input

To streamline content gathering, developer will create a shared Google Sheet with tabs for:

| Tab | Purpose |
|-----|---------|
| **Course Catalog** | Course name, description, category, price, free/paid, number of lessons |
| **Tier Names** | Confirmed: Wanderer/Apprentice/Fellow (client to use or adjust) |
| **Cover Photos** | List of photos to use, aspect ratio notes, upload links |
| **Test Videos** | Video links (720p MP4), which course each belongs to |
| **Blog Topics** | Initial blog article ideas, preferred posting frequency |
| **FAQ** | Common questions to include in FAQ page |
| **Wix Content** | Old site content worth migrating (client to review) |

**Action:** Developer to create and share Google Sheet in Week 1

---

## Open Questions / Client Input Required

| # | Question | Priority | Status |
|---|----------|----------|--------|
| 1 | Tier names | P0 | **Resolved:** Wanderer/Apprentice/Fellow |
| 2 | Logo — provide existing logo or design brief | P0 | Client to provide |
| 3 | Course catalog — exact courses, pricing per course | P0 | Via Google Sheet |
| 4 | Free course selection — which courses are free intro | P0 | Via Google Sheet |
| 5 | Discord structure — who manages, how teacher interaction | P1 | Client to decide |
| 6 | Domain registrar access — for DNS changes | P0 | Client to facilitate. TTL lowered Week 13 (2 weeks before Week 15 launch). |
| 7 | Course categories — art mediums for filter | P1 | Via Google Sheet |
| 8 | Course access duration — lifetime or time-limited | P1 | Client to decide |
| 9 | Video resolution — 720p or 1080p | P1 | Client to decide |
| 10 | Old site content — which Wix content worth migrating | P1 | Client to review |
| 11 | LMS choice | P0 | **Resolved:** Tutor LMS (stay — dashboard gap applies to all LMS equally) |
| 12 | Launch strategy | P0 | **Resolved:** Full launch Week 15 (staging until launch, no soft launch) |
| 13 | Course bundles | P1 | **Resolved:** Sell individually at launch. No bundles. |
| 14 | Fellow Discord access | P1 | **Resolved:** Manual invite code shown on dashboard after Fellow purchase. No automated bot. |
| 15 | Branded dashboard inner UI | P2 | **Resolved:** Wrapper only for now. Custom CSS only if client specifically needs it. |

---

## References

- Previous site: https://www.crystalauart.com

### Contracted baseline design references (pre-quotation)

- Proko: https://www.proko.com (modern, clean course platform)
- Will Kemp Art School: https://willkempartschool.com (classical art school feel)

### Feature-level visual inspiration (post-quotation, 2026-05-25)

- Crafti: https://crafti.axiomthemes.com/
- Ingenioso: https://ingenioso.themerex.net/

Received from client 2026-05-25 (AFTER quotation). Used as feature-level inspiration
for the Home hero layered-banner look where achievable via Elementor Pro native
capabilities. **NOT the contracted design direction** — baseline remains Proko +
Will Kemp. See §Hero Banner Specification for in-scope vs Phase 2 boundary.

### Design system references

- Color palette: https://www.figma.com/color-palette-generator/
- Fonts: Quicksand, Permanent Marker (Google Fonts)
