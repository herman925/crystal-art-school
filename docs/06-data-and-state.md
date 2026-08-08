# 06 — Data & State

There is **no backend**. Two things stand in for one:

1. **Content data** is hard-coded inside each page's `Component` class (arrays/objects).
2. **User state** (cart, login, progress, consent) is faked in `localStorage`.

This page catalogs both so you know where to change content and what state exists.

---

## Content data models

Each is a method on the relevant page's `Component` class returning plain JS data. To add
or change content, edit these arrays (and keep the parallel copies in sync — see the
warning at the bottom).

### Courses — `course.dc.html` → `courses()`
An object keyed by **slug**. Each record:

```js
'light-shadow-head': {
  title, tag, level, len, units, img, price, was?, free?,
  blurb,
  learn: [ '…', … ],                       // "what you'll learn" bullets
  syllabus: [ [unitTitle, unitSub, len], … ]
}
```

Slugs in use: `materials-setting-up` (free), `light-shadow-head`, `blocking-large-shapes`,
`mixing-greens`, `soft-edges-clean-paint`, `loading-a-brush`, `warm-cool-greys`,
`erasing-drawing-tool`, `brush-care-habits`. `course.dc.html?id=<slug>` selects one;
unknown falls back to the free course.

### Course catalog cards — `Lessons.dc.html`
Not data-driven — 9 hard-coded `.lesson-cell` blocks with filter attributes
(`data-cat`, `data-theme`, `data-difficulty`, `data-duration`, `data-sale`, `data-price`).
Their "View course" links carry the matching `course.dc.html?id=…` slug.

### Home course pool — `index.html` → `coursePool()`
12 course summaries `{ img, title, tag, date, price }` for the New Release carousel and the
"Shop all courses" rail. Plus `courseHref(title)` mapping titles → course-page slugs.

### Gallery artworks — `Gallery.dc.html` → `pieces()`
12 records `{ title, img, tags[], price, date, orig, size, desc }`. `orig: true` = original,
else limited print. "Buy on Etsy" builds an Etsy **search** URL from the title (no per-piece
listing id).

### Student showcase — `Showcase.dc.html` → `pieces()`
18 records `{ img, ar, name, med, title }`. `ar` is the presented aspect ratio that drives
the justified-wall layout. Avatar colours are hashed from the name.

### Blog posts — `Blog.dc.html` → `posts()`
12 records `{ title, excerpt, img, tag, date, d }`. Newest 10 shown. Tags become the filter
row; `?tag=` deep-links. All currently point at the one `blog-post.dc.html`.

### Lesson player units — `lesson.dc.html` → `UNITS`
6 records `{ title, dur, img }` for the simulated player. `SPEED = 12` (12× playback).

### Dashboard pools — `Dashboard.dc.html`
`pool()` = recommendable courses; seed arrays for `cas-enrolled` and `cas-orders` used to
populate returning demo accounts. `courseHref()` maps titles → slugs.

---

## `localStorage` keys (the "database")

All keys are namespaced `cas-`. Clearing them resets the demo.

| Key | Written by | Shape | Purpose |
|-----|-----------|-------|---------|
| `cas-cart` | site-extras, Gallery, Lessons, index, Checkout | `[{ title, price, qty, img, kind, tier? }]` | The shopping cart. Drawer + checkout read/write it; badge counts it. Checkout **clears** it on confirmation. |
| `cas-theme` | theme switch (currently dormant) | `"A"\|"B"\|"C"\|"D"` | Saved theme. Read but overridden by forced `applyTheme('D')`. |
| `cas-user` | Login, Signup, site-extras (logout) | `{ name, email?, plan?, focus?, via?, newUser?, since? }` | The fake logged-in user. Presence = "logged in". Dashboard gates on it. |
| `cas-enrolled` | Dashboard | `[{ img, title, tier, units, done?, free? }]` | Enrolled courses. New accounts start empty; returning demo users get seeded. |
| `cas-orders` | Dashboard | `[{ id, date, title, tier, img, amount }]` | Purchase history for the Purchases tab. Seeded for returning demo users. |
| `cas-free-lesson-progress` | lesson.dc.html | `{ unit, t, done[6] }` | Progress in the free lesson player. Feeds the Dashboard's free-course completion count. |
| `cas-dash-tab` | Dashboard | tab name | Remembers the last-open dashboard tab. |
| `cas-cookie-consent` | site-extras | `{ analytics, marketing, ts }` | GDPR consent. Absence → banner shows after 700ms. |

Notes:
- A `Component` never assumes a key exists — every read is wrapped in try/catch with a
  default. Follow that pattern.
- `cas-cart` is the one truly shared key: written from many pages and the drawer, and kept
  in sync across tabs via a `storage` event listener in `site-extras.js`.

---

## Demo-account behaviour (how "realistic" state appears)

- **Brand-new account** (`Signup` sets `newUser: true`) → Dashboard shows the **onboarding
  empty-state** (nothing enrolled, no orders, 0 progress), nudging the free course.
- **Returning demo user** (logged in via `Login`, no `newUser`) → Dashboard **seeds**
  sample `cas-enrolled` + `cas-orders` on first visit so the populated design is visible.
- **Fellow gating** → the Discord panel unlocks if any enrolled course is Fellow-tier or
  the user's plan is Fellow.
- **Free-course progress** is *real* (whatever you did in the lesson player) and flows into
  the Dashboard stats.

To demo the full logged-in experience quickly: sign up (or log in with any email), then
visit the lesson player and complete a unit or two, then open the Dashboard.

---

## ⚠️ Data duplication (keep in sync)

Course data is intentionally duplicated across pages because there's no shared module:
- Full course records live in `course.dc.html` (`courses()`).
- Card copies live in `Lessons.dc.html` (hard-coded cells), `index.html` (`coursePool()`),
  and `Dashboard.dc.html` (`pool()` + seeds).
- The title→slug map (`courseHref`) is repeated in `index.html`, `Dashboard.dc.html`, and
  `course.dc.html`.

If you add or rename a course, update **all** of these, or links/prices will drift. A
future refactor could hoist this into a shared `js/data.js`, but today it's copied.

Next: [07 — Commerce](07-commerce.md).

## The asset pipeline

Content comes from the client's Drive tree, not from hand-written JavaScript.

```
assets/                     the client's own folders (outside the repo)
  └─ tools/import-assets.py     resize, strip EXIF, probe video durations
       └─ tools/asset-manifest.json
            └─ tools/build-data.py
                 └─ js/course-data.js, js/gallery-data.js, js/free-videos-data.js
```

**Never hand-edit `js/*-data.js`.** They are generated and the next run
overwrites them. The only hand-written copy is `tools/content-meta.json`; video
IDs live in `tools/video-links.json`.

### Traps in here, all of which have cost time

- **`tools/import-assets.py` filters the client's working material out by
  filename** (her handwritten comment boards and reference shots sit in the same
  folders as her artwork). One of those boards once shipped to the public gallery
  as a saleable painting. **Do not remove that filter.**
- **A folder is not a lesson number.** One folder can span more than one of the
  client's numbered lessons — the beginner course is 14 lessons across 13 films,
  because one folder holds a single film covering two of them. The `spans` field
  carries this; `lessonCount` is what the reader is shown and `lessons.length` is
  what indexing and progress use. Conflating them is what produced "I thought
  there were 14 lessons but I can only see 13".
- **Poster filenames are numbered by position, not by lesson number**, precisely
  so a folder gaining a span does not rename every image after it.
- **Her folder numbers are not her lesson numbers.** Before remapping any title,
  check it against the artwork — the covers are the evidence. A remap that looked
  obviously right would have shifted every title by one.
- **Her own documents disagree with each other** on counts. Where they do, no
  number goes in the copy, so the page cannot contradict itself, and the conflict
  is recorded in `tools/content-meta.json` `_source` for the client to resolve.

### Provenance

`_source` on each record says where the copy came from and what was changed.
Anything developer-written that describes the client's work is flagged in the
data (`fromArtwork`, `pending`) rather than presented as hers. Do not write
biography, credentials or syllabus that did not come from the client — an
earlier build invented credentials and they had to be removed.
