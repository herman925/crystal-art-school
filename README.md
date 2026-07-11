# Crystal's Art School

> Slow down. Draw with care.

A multi-page website for Crystal's Art School — a small, craft-focused drawing studio.
Static HTML/CSS/JS, no build step. Industrial design system (v3.1) with four
swappable themes (A: Oak Atelier · B: Walnut · C: Concrete & Iron · D: Moody Loft)
and photographic wood / cork / marble textures on the large surfaces.

Live: https://herman925.github.io/crystal-art-school/

## Pages

| Page | File | Purpose |
|------|------|---------|
| Home | `index.html` | Hero carousel, new release, course shop, journal, community band, testimonials |
| Courses | `Lessons.dc.html` | Curriculum grid, filters, membership tiers |
| Course (single) | `course.dc.html` | Course overview / syllabus |
| Lesson player | `lesson.dc.html` | Single lesson / video page |
| Gallery shop | `Gallery.dc.html` | Framed originals & prints, filter + Etsy links |
| Student showcase | `Showcase.dc.html` | Cork pin-board of student work (physics on scroll) |
| Blog | `Blog.dc.html` | Article index |
| Blog post | `blog-post.dc.html` | Single article layout |
| About | `About.dc.html` | Bio, contact, terms |
| Free videos | `FreeVideos.dc.html` | Free tutorial index |
| FAQ | `FAQ.dc.html` | Accordion FAQ |
| Login / Signup | `Login.dc.html` / `Signup.dc.html` | Auth pages |
| Dashboard | `Dashboard.dc.html` | Member home (enrolled, recommendations) |
| Checkout | `Checkout.dc.html` | Cart → details → confirmation |

## Structure

```
.
├── index.html + *.dc.html      # pages (Claude Design runtime via support.js)
├── css/industrial.css          # entry — imports industrial-1/2/3.css
│   ├── industrial-1.css        # tokens, themes A–D, base components, nav
│   ├── industrial-2.css        # page components, footer, theme D photo overrides
│   └── industrial-3.css        # dashboard, showcase, course page, 2026-07 additions
├── js/site-nav.js, motion.js   # shared nav + GSAP scroll motion
├── js/<page>.js                # per-page init
├── img/                        # site imagery + texture photos (wood-*, corkboard, marble-wall)
├── DESIGN.md                   # design system v3.1 (source of visual truth)
└── serve.bat                   # local dev server (Windows)
```

## Run locally

JavaScript needs a real HTTP origin (not `file://`). On Windows, double-click
**`serve.bat`** — it finds a free port in `8080–8084` and serves the folder.
Then open the printed `http://localhost:<port>`.

Any static server works too, e.g.:

```bash
python -m http.server 8080
```

## Themes & textures

The nav theme switch sets `data-theme` on `.cas-root`; choice persists across
pages via `localStorage('cas-theme')`. All visual surfaces reskin from token
overrides in `css/industrial-1.css`.

Since v3.1 the large surfaces use real photos (see `DESIGN.md` §12): creamy
wood headers + white-marble gallery wall in light mode, rustic/black wood in
dark mode, a maple-framed cork board on the showcase, and metal button
finishes (copper primaries, olive metal, brushed steel, iron outlines).

## License

All rights reserved unless stated otherwise.
