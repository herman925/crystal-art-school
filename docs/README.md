# Crystal's Art School — Developer Wiki

> Slow down. Draw with care.

Full reference for the `crystal-art-school` repo: a static, no-build, multi-page
marketing + demo site for a small craft drawing studio. Industrial design system,
four themes (currently locked to the dark "Moody Loft" theme D), vanilla JS, and a
Claude Design authoring workflow.

**New here? Start with [01 — Quickstart](01-quickstart.md).**

---

## Contents

| # | Page | What's in it |
|---|------|--------------|
| 01 | [Quickstart](01-quickstart.md) | What the site is, the feature tour, every page explained, how to run + edit + ship |
| 02 | [Architecture](02-architecture.md) | No-build stack, the `.dc.html` format, `support.js` runtime, file layout, legacy files |
| 03 | [Design System](03-design-system.md) | Tokens, the 4 themes, fonts, materials/grains, shapes, theme-D safety rules |
| 04 | [Pages Reference](04-pages.md) | Per-page deep dive: sections, markup, behaviours |
| 05 | [JavaScript](05-javascript.md) | `motion.js`, `site-extras.js`, the per-page Component pattern, theme/cart/auth wiring |
| 06 | [Data & State](06-data-and-state.md) | Content data models + every `localStorage` key + demo-account behaviour |
| 07 | [Commerce](07-commerce.md) | Cart drawer, tier picker, checkout flow, membership tiers, Etsy bridge |
| 08 | [Claude Design Sync](08-claude-design-sync.md) | Syncing local files up to the Claude Design project (the 32K-token wall) |
| 09 | [Deploy & Ops](09-deploy.md) | Git, hosting, local dev server |

---

## 30-second orientation

- **Stack:** Static HTML + one CSS file + two shared JS files. No framework, no build,
  no server. Open a page over HTTP and it runs.
- **Pages:** 15 content pages, all sharing the same nav + footer chrome. Filenames end
  in `.dc.html` (a Claude Design "Design Component" format — see [02](02-architecture.md)).
- **Design:** Everything is driven by CSS custom properties in `css/industrial.css`.
  Four themes (A/B/C/D) re-point the same token names; the site currently forces theme **D**.
- **Behaviour:** Each page has an inline `Component` class (theme, nav, page logic).
  `js/site-extras.js` adds the cart drawer, cookie consent, and course tier picker to
  every page. `js/motion.js` adds GSAP scroll/hover motion.
- **State:** All "backend" is faked in `localStorage` (cart, logged-in user, course
  progress, cookie consent). No real server or database.
- **Commerce:** Courses use a tier picker (Apprentice / Fellow) → cart → demo checkout.
  Original artwork "Buy" buttons link out to Etsy.

## A note on the older docs

`README.md` and `DESIGN.md` at the repo root predate the current `.dc.html` structure.
They still describe lowercase `about.html` / per-page `js/about.js` files and a visible
A/B/C/D theme switcher. Those are **stale**. This wiki reflects the current repo:
`.dc.html` pages, inline Component logic, `motion.js` + `site-extras.js`, and a
theme locked to D. Where they conflict, trust the wiki.
