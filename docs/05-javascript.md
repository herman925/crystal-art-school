# 05 — JavaScript

Three kinds of JS run on every page:

1. **`support.js`** — the Design Component runtime (generated, don't edit). Mounts the
   `<x-dc>` template and instantiates the page's `Component` class. Covered in [02](02-architecture.md).
2. **The page's inline `Component` class** — that page's own logic.
3. **`js/site-extras.js`** and **`js/motion.js`** — shared behaviour + motion, loaded on
   every page via `<helmet>`.

All of it is vanilla ES5-ish JavaScript. No modules, no framework. GSAP is the only
runtime library.

> Reminder: the `js/<page>.js` files (about.js, blog.js, …) are **legacy and unloaded** by
> the `.dc.html` pages. Per-page logic lives inline in the `Component` class. See [02](02-architecture.md).

---

## The per-page `Component` pattern

Every page ends with:

```html
<script type="text/x-dc" data-dc-script data-props='{"defaultTheme": {...}}'>
  class Component extends DCLogic {
    // optional data methods, e.g. courses(), pieces(), posts(), coursePool()
    componentDidMount() {
      const root = document.querySelector('.cas-root');
      if (!root) return;
      // 1. theme  2. burger  3. cart badge  4. page-specific logic
    }
  }
</script>
```

`componentDidMount` runs once after the template mounts. The first ~30 lines of every
page's `componentDidMount` are near-identical boilerplate:

```js
// theme (currently forced to D)
const applyTheme = (t) => { root.setAttribute('data-theme', t); /* toggle switch btns */ };
let saved = this.props.defaultTheme || 'A';
try { const s = localStorage.getItem('cas-theme'); if (s && valid.includes(s)) saved = s; } catch {}
applyTheme('D');            // ← forced; change to applyTheme(saved) to unlock the switcher

// mobile burger sheet
const burger = document.querySelector('.nav-burger'), sheet = document.querySelector('.nav-sheet');
if (burger && sheet) burger.addEventListener('click', () => sheet.classList.toggle('open'));

// cart badge from the shared cart array
const renderCart = () => { const n = read('cas-cart').reduce((a,i)=>a+i.qty,0); /* set .cart-badge */ };
```

After that, each page does its own thing: render course cards, wire filters, run the
lesson player, gate the dashboard, etc. Data lives in methods like `courses()` /
`pieces()` / `posts()` that return arrays/objects; the mount method renders them into
placeholder containers with `innerHTML`.

**Escaping:** pages define a local `esc()` and always escape interpolated strings before
`innerHTML` (guards against broken markup from data). Keep that up if you add rendering.

---

## `js/site-extras.js` — shared behaviour

A single self-injecting IIFE (guarded by `window.__casExtras`). It injects its own
`<style>` and builds several global widgets, all wired with **event delegation on
`document`** so they work no matter when the template mounted. Initialised on
`DOMContentLoaded` (or immediately if already loaded).

What it provides:

### 1. Cart drawer (mini-cart)
A slide-down drawer built once and appended to `<body>`. Every `.cart-btn` click anywhere
on the site toggles it (delegated + `stopPropagation`, so it intercepts even the header
cart link). Renders the `cas-cart` array with per-line qty +/−, remove, subtotal, "Keep
shopping" and "Go to checkout →". Empty-state links to the gallery. Keeps every
`.cart-badge` on the page in sync via `updateBadges()`. Locks body scroll while open;
Escape closes; focus is restored on close.

### 2. Course tier picker
Intercepts any `[data-add-to-cart][data-tiered]` button (course cards, the home mini-
carousel, course pages) **before** the page's own handlers. Opens a small popover under the
button offering **Apprentice** (course only, base price) and **Fellow** (course + private
Discord, `price × 1.8`, `FELLOW_MULT = 1.8`). Choosing a tier pushes
`{ title, price, tier, kind: 'Course · <tier>', img, qty }` into `cas-cart`, flashes
"Added ✓", and nudges the cart button. Price/title/img are resolved from the button's
`data-*` or its containing `.lesson-cell` / `.rel-slide`. Popover closes on outside-click,
scroll, or Escape.

### 3. GDPR cookie consent + data-rights
A bottom banner (Essential only / Manage / Accept all) shown after 700ms if no prior
consent, plus a **preferences modal** (Essential locked-on, Analytics + Marketing toggles)
and a **UK-GDPR data-rights** section (Request / Correct / Delete my data → each shows a
confirmation note). Consent persists in `localStorage('cas-cookie-consent')` as
`{ analytics, marketing, ts }`. `window.casOpenPrivacy()` re-opens it (the Dashboard's
Privacy button and the About cookies accordion call this).

### 4. Logged-in nav state
`applyAuthNav()` reads `cas-user`; if present, it swaps the header "Log in / Sign up"
buttons for a user chip (avatar + first name → Dashboard) and a "Log out" button, and
rewrites the mobile sheet's "Log in" link to "My dashboard". So every page reflects the
fake auth state.

### 5. Chalk cursor + dust trail (theme D only)
In theme D, the CSS swaps the cursor for a chalk dot, and a `pointermove` handler drops a
short-lived pool of fading "chalk dust" particles behind the cursor. No-op in other themes.

### 6. Social button fallback
Delegated handler: clicking a `.social-btn` with `href="#"` opens Instagram/Facebook based
on its `aria-label`.

Shared helpers in this file: `read()`/`write()`/`count()` for the cart, `money()`,
`esc()`, `imgsrc()`, `curTheme()`.

---

## `js/motion.js` — shared GSAP choreography

A self-injecting IIFE (guarded by `window.__casMotion`). It **degrades to fully static** if
GSAP is missing, `prefers-reduced-motion` is set, the browser is headless/automation
(`navigator.webdriver` or a `Headless` UA), or the URL has `?nomotion=1`. It polls briefly
for the mounted `.cas-root` + content before running.

It layers motion on **every page except the home page** — it detects `.hero__slide` and, if
present (home), only adds the shared micro-interactions and then returns, letting the home
page's own inline choreography own the rest.

What it does:
- **Nav**: drops in on load; links + brand cascade.
- **Micro-interactions** (fine-pointer only): magnetic primary buttons (`quickTo` x/y
  follow the cursor), and a card-tilt (`rotationX/Y` follow the cursor) on cards, tiers,
  course/blog/gallery cards, etc.
- **Ambient**: the `✦` brand stars gently twinkle.
- **Hero (non-home pages)**: signature ink-wipe on `.sig-script`, split-word headline
  reveal, hero content cascade, portrait slide + parallax, course-cover masked wipe,
  page-hero wood parallax.
- **Section titles**: "hang the workshop sign" — tilt + drop + elastic settle on scroll.
- **Image frames + card groups**: masked wipes for framed photos; grouped scroll staggers
  with alternating drift directions (a `GROUPS` list of selectors → `ScrollTrigger.batch`).
- **Bands + footer**: sweep/ease in on scroll.
- **Accordions**: animates `details.acc-item` open/close height + rotates the `+` icon.
- **Smooth-scroll**: intercepts same-page `#anchor` links (and cross-page hash arrival).

Uses `ScrollTrigger` when available; falls back to a `getBoundingClientRect` "in view"
check otherwise. The `✦` ornament is the brand mark throughout.

---

## The `✦` brand ornament

Not a font glyph you should swap casually — it's used as the brand stamp in eyebrows,
section titles, cookie/cart/tier headers, and is individually animated (twinkle) by
`motion.js`. Search `✦` before restyling it.

Next: [06 — Data & State](06-data-and-state.md).
