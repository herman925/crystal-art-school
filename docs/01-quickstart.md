# 01 — Quickstart & Onboarding

Everything a new developer (or a curious owner) needs to understand what this site is,
what it does, and how to work on it. Read this top to bottom once; the other pages are
reference you dip into.

---

## What is this?

A **marketing + demo website** for Crystal's Art School — a small, craft-focused online
drawing/painting studio run by one teacher (Crystal Lau). It sells video courses and
original artwork, hosts a blog, and shows off student work.

It is a **front-end prototype**: it looks and behaves like a real product, but there is
**no backend**. Accounts, carts, purchases, and course progress are all simulated in the
browser's `localStorage`. Video playback in the lesson player is a scripted simulation,
not a real video. This makes it a perfect clickable demo and a faithful design reference,
with no server to run.

- **Tech:** plain HTML, one big CSS file, two shared JS files, plus GSAP (loaded from a CDN).
- **No build step.** No npm install, no bundler, no compile. Edit a file, refresh, done.
- **Authoring home:** the pages are also editable in **Claude Design** (claude.ai/design).
  The `.dc.html` extension is what makes them click-editable there. See [08](08-claude-design-sync.md).

---

## Feature tour

What a visitor can actually do:

- **Browse & switch themes** — the whole site is tokenized into four industrial looks
  (Oak / Walnut / Concrete / Moody Loft). Currently the site is **locked to theme D**
  (Moody Loft, dark). The switcher UI has been removed; the themes still exist in CSS.
- **Watch a free course** — the lesson player (`lesson.dc.html`) simulates a 6-unit
  watercolour intro course. Progress bar, unit list, "mark complete", downloadable notes
  (data-URI text files). Progress persists across reloads.
- **Browse the course catalog** — `Lessons.dc.html`: a filterable grid (medium / theme /
  difficulty / length / price / on-sale) plus a membership-tier section.
- **View a course page** — `course.dc.html?id=<slug>`: hero, "what you'll learn",
  unit-by-unit syllabus, related courses. Data-driven by a `?id=` query param.
- **Buy a course** — every course "Add to cart" opens a **tier picker** (Apprentice =
  course only; Fellow = course + private Discord, priced 1.8×). Adds to a cart drawer.
- **Shop artwork** — `Gallery.dc.html`: an Etsy-style framed store with a filter rail
  (medium / subject / max-price / sort) and a quick-view modal. "Buy on Etsy" links out;
  "Add to cart" adds locally.
- **See student work** — `Showcase.dc.html`: a justified, Google-Photos-style wall of
  student pieces of mixed aspect ratios, filter by medium, click to lightbox.
- **Read the blog** — `Blog.dc.html` (index, newest 10, tag filter) and
  `blog-post.dc.html` (a full article with a table of contents and comments).
- **Check out** — `Checkout.dc.html`: 3-step basket → details/payment → confirmation
  (demo, no real payment).
- **Create an account / log in** — `Signup.dc.html` (3-step wizard) and `Login.dc.html`
  (with Google button + forgot-password flow). Creates a fake user in `localStorage`.
- **Use a dashboard** — `Dashboard.dc.html`: member home, gated behind "login". Shows
  enrolled courses, progress, purchases, a Fellow-only Discord panel, and settings.
- **Cart, cookie consent, contact** — a slide-down cart drawer on every page, a UK-GDPR
  cookie banner + data-rights modal, and a contact form on `About.dc.html`.

---

## Page map

Every page shares the same top nav (two rows: main links + category links), a mobile
burger sheet, and a walnut footer. All pages link `css/industrial.css`,
`js/site-extras.js`, and `js/motion.js`.

| File | Page | One-liner |
|------|------|-----------|
| `index.html` | Home | Hero carousel, New Release trio, course shop rail, journal rail, community band, testimonials |
| `Lessons.dc.html` | Course catalog | Filterable course grid + membership tiers + free-intro band |
| `course.dc.html` | Course detail | `?id=` driven: syllabus, learn-list, related courses |
| `lesson.dc.html` | Free lesson player | Simulated 6-unit course player, progress persists |
| `Gallery.dc.html` | Artwork store | Etsy-style framed grid, filters, quick-view modal, Etsy buy links |
| `Showcase.dc.html` | Student showcase | Justified mixed-ratio wall + lightbox |
| `Blog.dc.html` | Journal index | Featured + grid, tag filter, newest 10 |
| `blog-post.dc.html` | Article | Banner, TOC, body, pull-quote, comments, related |
| `About.dc.html` | About | Bio, credentials, contact form, terms accordion |
| `FAQ.dc.html` | FAQ | Accordion of common questions |
| `Login.dc.html` | Log in | Email/Google login, forgot-password, guest |
| `Signup.dc.html` | Sign up | 3-step wizard (account → focus → plan) |
| `Dashboard.dc.html` | Member dashboard | Login-gated; courses, progress, purchases, Discord, settings |
| `Checkout.dc.html` | Checkout | 3-step basket → payment → confirmation |
| `deer-preview.html` | Asset preview | Dev-only: renders the ornate deer frame SVGs |
| `design_base.html` | Design board | The direction board (palette, materials, themes). Reference, not a site page. |

See [04 — Pages Reference](04-pages.md) for the deep dive on each.

---

## How to run it locally

The JS needs a real HTTP origin (it reads `localStorage` and fetches sibling files);
opening a file as `file://` will misbehave. Serve the folder:

```bash
# from the repo root
python -m http.server 8080
# then open http://localhost:8080/index.html
```

On Windows there's also **`serve.bat`** (double-click) if present — it finds a free port
in 8080–8084 and serves the folder.

Any static file server works (`npx serve`, VS Code Live Server, etc.). There is nothing
to install or build.

To reset all demo state (cart, login, progress, cookie consent), clear the site's
`localStorage` in DevTools → Application → Local Storage, or run in the console:

```js
localStorage.clear(); location.reload();
```

## How to edit it

Two workflows, same files:

1. **Directly in code** — open the `.dc.html` file, edit the markup inside `<x-dc>…</x-dc>`
   or the logic in the trailing `<script type="text/x-dc">` Component class. Style lives
   in `css/industrial.css` (use existing tokens; don't hard-code hex — see [03](03-design-system.md)).
   Refresh the browser.
2. **In Claude Design** — the `.dc.html` format is click-editable there. Sync local
   changes up (or pull edits down) per [08 — Claude Design Sync](08-claude-design-sync.md).

Common edits and where they live:
- **Add/change a course** → the `courses()` data in `course.dc.html` (+ the catalog cards
  in `Lessons.dc.html` and the pools in `index.html`/`Dashboard.dc.html`). See [06](06-data-and-state.md).
- **Add a gallery artwork** → the `pieces()` array in `Gallery.dc.html`.
- **Add a blog post** → the `posts()` array in `Blog.dc.html`.
- **Change a theme colour** → the `[data-theme="…"]` block in `css/industrial.css`.
- **Change cart / cookie / tier behaviour** → `js/site-extras.js`.
- **Change scroll/hover motion** → `js/motion.js`.

## How to ship it

```bash
git add -A
git commit -m "…"
git push origin main       # → github.com/herman925/crystal-art-school
```

There is no CI and no configured host in the repo. It's a static folder — deploy it to
any static host (GitHub Pages, Netlify, Cloudflare Pages, S3) by serving the folder root.
See [09 — Deploy & Ops](09-deploy.md).

---

Next: [02 — Architecture](02-architecture.md).
