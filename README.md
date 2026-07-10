# Crystal's Art School

> Slow down. Draw with care.

A multi-page website for Crystal's Art School — a small, craft-focused drawing studio.
Static HTML/CSS/JS, no build step. Industrial design system (v3.0) with four
swappable themes (A: Oak Atelier · B: Walnut · C: Concrete & Iron · D: Moody Loft).

## Pages

| Page | File | Purpose |
|------|------|---------|
| Home | `index.html` | Hero carousel, new release, course shop, journal, community band, testimonials |
| Lessons | `lessons.html` | Curriculum grid, filters, membership tiers |
| Lesson (single) | `lesson.html` | Single course / video page |
| Gallery | `gallery.html` | Studio and student work |
| Blog | `blog.html` | Article index |
| Blog post | `blog-post.html` | Single article layout |
| About | `about.html` | Bio, contact, terms |
| Login | `login.html` | Sign-in + forgot password |
| Signup | `signup.html` | Account creation |
| Dashboard | `dashboard.html` | Member home (enrolled, recommendations) |
| Checkout | `checkout.html` | Cart → details → confirmation |

## Structure

```
.
├── index.html, about.html, blog.html, blog-post.html,
│   gallery.html, lessons.html, lesson.html,
│   login.html, signup.html, dashboard.html, checkout.html
├── css/industrial.css     # all styles (tokenized, theme A/B/C/D)
├── js/site-extras.js      # shared: cart drawer, cookie consent, tier picker, auth nav
├── js/<page>.js           # per-page init (was embedded Component class)
├── img/                   # site imagery
├── serve.bat              # local dev server (Windows)
└── transform.py           # one-shot script used to port from the
                            Claude Design handoff bundle (kept for reference)
```

## Run locally

JavaScript needs a real HTTP origin (not `file://`). On Windows, double-click
**`serve.bat`** — it finds a free port in `8080–8084` and serves the folder.
Then open the printed `http://localhost:<port>`.

Any static server works too, e.g.:

```bash
python -m http.server 8080
```

## Themes

Click the **A · B · C · D** buttons in the nav. Choice persists across pages
via `localStorage`. All visual surfaces (banner, gallery tile, panels,
buttons, footer) reskin from token overrides in `css/industrial.css`.

## License

All rights reserved unless stated otherwise.