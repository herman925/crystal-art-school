# Crystal's Art School

> Slow down. Draw with care.

A multi-page website for Crystal's Art School — a small, craft-focused drawing studio.
Static HTML/CSS/JS, no build step.

## Pages

| Page | File | Purpose |
|------|------|---------|
| Home | `index.html` | Hero, philosophy, featured work |
| Lessons | `lessons.html` | Classes, curriculum, membership |
| Gallery | `gallery.html` | Student & studio work (3D gallery via `js/gallery3d.js`) |
| Blog | `blog.html` | Article index |
| Blog post | `blog-post.html` | Single article layout |
| About | `about.html` | Bio, contact, terms |

## Structure

```
.
├── index.html, about.html, blog.html, blog-post.html, gallery.html, lessons.html
├── css/site.css          # all styles
├── js/site.js            # shared interactions
├── js/gallery3d.js       # 3D gallery
├── img/                  # site imagery (compressed)
├── serve.bat             # local dev server (Windows)
├── docs/                 # PRD, design notes, proposal (PDF/DOCX)
└── wireframes/           # earlier design wireframes (kept for reference)
```

## Run locally

JavaScript needs a real HTTP origin (not `file://`). On Windows, double-click **`serve.bat`** — it finds a free port in `8080–8084` and serves the folder. Then open the printed `http://localhost:<port>`.

Any static server works too, e.g.:

```bash
python -m http.server 8080
```

## License

All rights reserved unless stated otherwise.
