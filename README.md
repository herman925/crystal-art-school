# Crystal's Art School

Production prototype (v2.x) — multi-page static site, no build step.
Live: https://herman925.github.io/crystal-art-school/

## Pages

| File | Purpose |
|---|---|
| `index.html` | Front page — hero, new releases, teacher intro |
| `lessons.html` | Catalog + membership tiers |
| `lesson.html` | Lesson detail |
| `gallery.html` | three.js 3D museum — "Art Showcase" (Crystal) + "Fresh Eyes" (students), 2D collage fallback |
| `blog.html` / `blog-post.html` | Blog index + post detail |
| `about.html` | About + contact + T&C |

## Stack

- Vanilla HTML/CSS/JS, GSAP 3 + ScrollTrigger (CDN) for scroll motion, three.js (CDN) for the gallery
- Design system: `DESIGN.md` (pastel sketchbook — Quicksand · Permanent Marker · Italiana · Caveat)
- `PRD.md` — production addendum; authoritative Phase-1 PRD in `docs/PRD-phase1.md`
- Wireframe exploration phase lives in this repo's git history (pre-v2 commits); local `wireframes/` clone is not tracked

## Local dev

Run `serve.bat` (or any static server) and open `index.html`. All paths are relative, so the site also works from a subpath (GitHub Pages project site).
