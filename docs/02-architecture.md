# 02 — Architecture

## The stack, in one breath

Static HTML pages, one shared stylesheet, two shared vanilla-JS files, and GSAP from a
CDN. No framework, no bundler, no build step, no server-side code, no database. Each page
is self-contained: open it over HTTP and it runs.

```
Browser
 ├── <page>.dc.html           the page (markup + inline logic)
 │     └── support.js         the "Design Component" runtime that mounts <x-dc>
 ├── css/industrial.css       all styles (tokens + 4 themes + every component)
 ├── js/site-extras.js        shared: cart drawer, cookie consent, tier picker, auth nav
 ├── js/motion.js             shared: GSAP scroll + hover choreography
 └── GSAP (jsDelivr CDN)      gsap.min.js + ScrollTrigger.min.js
```

Everything that looks like a backend (accounts, cart, purchases, progress) is simulated
in `localStorage`. See [06 — Data & State](06-data-and-state.md).

---

## The `.dc.html` format (important)

Most pages end in `.dc.html`, not `.html`. This is the **Claude Design "Design Component"**
format. It is still just an HTML file the browser can open, but it has a specific shape so
the Claude Design editor can click-edit the markup. Anatomy:

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="./support.js"></script>   <!-- the dc runtime -->
</head>
<body>
  <x-dc>                                  <!-- the template the editor can edit -->
    <helmet>                              <!-- children mount into <head> -->
      <link rel="preconnect" ...>
      <link href="https://fonts.googleapis.com/css2?..." rel="stylesheet">
      <link rel="stylesheet" href="css/industrial.css">
      <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
      <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
      <script src="js/site-extras.js"></script>
      <script src="js/motion.js"></script>
    </helmet>

    <div class="cas-root" data-theme="D">
      ... all the page markup: nav, main, footer ...
    </div>
  </x-dc>

  <script type="text/x-dc" data-dc-script data-props='{"defaultTheme": {...}}'>
    class Component extends DCLogic {
      componentDidMount() { /* page logic runs here */ }
    }
  </script>
</body>
</html>
```

Key rules:
- **`support.js`** is the dc runtime bundle. It reads `<x-dc>`, mounts the template, moves
  `<helmet>` children into `<head>`, and instantiates the `Component` class. It is
  machine-generated — never hand-edit it (`// GENERATED from dc-runtime/src/*.ts`).
- **`<helmet>`** is where fonts, the stylesheet, GSAP, and the two shared scripts are
  loaded. Scripts are only legal inside `<helmet>`; for post-render JS use
  `componentDidMount`.
- **The `Component` class** (in `<script type="text/x-dc">`) is plain classic JavaScript
  extending an injected `DCLogic` base — like a React class component minus `render()`.
  It gets `componentDidMount`, `this.props`, etc. All of a page's own logic lives here.
- **`data-props`** on the script tag declares editor-tweakable props (e.g. `defaultTheme`).
- **`.dc.html` vs `.html`:** `index.html` is plain-`.html` but internally uses the exact
  same `<x-dc>` + `Component` structure. It works identically in the browser; the only
  difference is the extension (and thus how Claude Design treats it).

For the full format spec (control-flow tags, child components, styling atomics), see the
Claude Design system prompt — but you rarely need more than the above to work on this site.

---

## How a page boots

1. Browser loads `<page>.dc.html`, runs `support.js` from `<head>`.
2. `support.js` mounts the `<x-dc>` template into the DOM and moves `<helmet>` children
   into `<head>` → fonts, `industrial.css`, GSAP, `site-extras.js`, `motion.js` all load.
3. The page's `Component.componentDidMount()` runs: sets `data-theme` (forced to `D`),
   wires the mobile burger, renders any data-driven content (course cards, gallery grid,
   blog list…), and sets up page-specific interactions.
4. `site-extras.js` (an IIFE) self-initialises on `DOMContentLoaded`: injects its styles,
   builds the cart drawer, the cookie banner + data-rights modal, the course tier picker,
   and applies logged-in nav state.
5. `motion.js` (an IIFE) polls briefly for the mounted `.cas-root`, then layers GSAP
   entrance/scroll/hover motion (skipped under `prefers-reduced-motion` or headless).

Order matters only loosely: `site-extras.js` uses event delegation on `document`, so it
works regardless of when the template finished mounting.

---

## File & folder layout

```
crystal-art-school/
├── index.html                Home (plain .html, same x-dc structure)
├── Lessons.dc.html            Course catalog
├── course.dc.html             Course detail (?id=)
├── lesson.dc.html             Free lesson player
├── Gallery.dc.html            Artwork store
├── Showcase.dc.html           Student showcase
├── Blog.dc.html               Journal index
├── blog-post.dc.html          Article
├── About.dc.html              About
├── FAQ.dc.html                FAQ
├── Login.dc.html              Log in
├── Signup.dc.html             Sign up
├── Dashboard.dc.html          Member dashboard
├── Checkout.dc.html           Checkout
├── deer-preview.html          Dev-only asset preview
├── design_base.html           Design direction board (reference)
├── support.js                 dc runtime (generated — do not edit)
├── css/
│   └── industrial.css         the entire design system (tokens, 4 themes, components, motion)
├── js/
│   ├── site-extras.js         shared: cart, cookie consent, tier picker, auth nav, chalk cursor
│   ├── motion.js              shared: GSAP choreography
│   └── about.js, blog.js, …   LEGACY per-page files — NOT loaded by the .dc.html pages
├── img/                       studio photography, logo, deer-frame SVGs, textures
├── DESIGN.md                  design spec (partly stale — see below)
├── README.md                  repo readme (partly stale — see below)
├── proposal_text.txt          client proposal text (reference)
└── docs/                      this wiki
```

### Legacy / unused files (don't be fooled)

- **`js/about.js`, `js/blog.js`, `js/checkout.js`, `js/dashboard.js`, `js/gallery.js`,
  `js/index.js`, `js/lesson.js`, `js/lessons.js`, `js/login.js`, `js/signup.js`,
  `js/blog-post.js`** — these are from an alternate "plain HTML" port. The current
  `.dc.html` pages do **not** load them (each page's logic is inline in its `Component`
  class). Editing them has no effect on the live pages. They're kept for reference / a
  possible future non-dc build.
- **`README.md` / `DESIGN.md`** — predate the current `.dc.html` structure. They mention
  lowercase `about.html`, per-page `js/*.js`, and a visible A/B/C/D theme switcher. All
  stale. `DESIGN.md` is still an excellent source for the *design intent* (palette,
  materials, theme philosophy) — just not for the file structure.
- **`design_base.html`** — the design direction board (palette swatches, material studies,
  the four themes side by side). A reference artifact, not a navigable site page. It's
  byte-identical between local and the Claude Design project, so it's skipped on sync.
- **`deer-preview.html`** — a tiny dev harness that renders the two ornate deer-frame SVGs
  (`img/deer-black.svg`, `img/deer-silhouette.svg`) light and dark, to eyeball the gallery
  frame art. Not linked from the site.

---

## External dependencies

- **GSAP 3.12.5** + **ScrollTrigger** — from `cdn.jsdelivr.net`. Drives the hero, scroll
  choreography, hover tilt, accordion animation. If it fails to load, everything degrades
  gracefully to visible-by-default (no motion). See [05](05-javascript.md).
- **Google Fonts** — Bodoni Moda, Oswald, Archivo, Saira Stencil One, and (on some pages)
  Mrs Saint Delafield / Sacramento for the handwritten signature. Loaded per page in
  `<helmet>`.
- No other runtime dependencies.

Next: [03 — Design System](03-design-system.md).
