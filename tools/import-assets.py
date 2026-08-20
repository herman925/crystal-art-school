#!/usr/bin/env python3
"""
import-assets.py — one-way asset pipeline for the Crystal's Art School prototype.

Walks the client's Google-Drive `assets/` tree, and for every image it needs:
  * auto-rotates per EXIF, strips EXIF, resizes, re-encodes as progressive JPEG
  * writes it into img/ under a lowercase ASCII, space-free name
  * writes the responsive derivatives beside it (`name-320.jpg` and friends)
    at the widths the page actually paints, and records the ready-made
    srcset/sizes/w/h for build-data.py to hand to the pages
and for every video it needs:
  * reads the duration with ffprobe (header only — the files are 0.6-2.4 GB and
    are NEVER copied into the site)

Everything it learns is written to tools/asset-manifest.json, which
tools/build-data.py turns into js/course-data.js + js/gallery-data.js.

Re-runnable: safe to run again when the client sends more photos.

Usage:  python tools/import-assets.py [--force]
        --force   re-encode images even if the output already exists
"""

import fnmatch
import json
import os
import re
import subprocess
import sys
import unicodedata

from PIL import Image, ImageOps

# --------------------------------------------------------------------------
# paths
# --------------------------------------------------------------------------

SITE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PROJECT_DIR = os.path.dirname(os.path.dirname(SITE_DIR))
ASSETS_DIR = os.path.join(PROJECT_DIR, "assets")
IMG_DIR = os.path.join(SITE_DIR, "img")
TOOLS_DIR = os.path.join(SITE_DIR, "tools")
MANIFEST = os.path.join(TOOLS_DIR, "asset-manifest.json")

# --------------------------------------------------------------------------
# encoding rules (spec 5.2)
# --------------------------------------------------------------------------

MAX_EDGE_GALLERY = 1600   # gallery art + course covers
MAX_EDGE_POSTER = 1280    # lesson posters
JPEG_QUALITY = 82
SIZE_WARN_BYTES = 600 * 1024

IMAGE_EXTS = {".jpg", ".jpeg", ".png", ".webp"}
VIDEO_EXTS = {".mp4", ".mov", ".m4v"}

# --------------------------------------------------------------------------
# responsive derivatives
#
# The site shipped one file per image at 1280-1600px and painted it at 78px.
# Every width below comes from MEASURING the rendered page (Playwright,
# 2026-08-09, viewports 390 / 768 / 1280 / 1600, lazy images scrolled in and
# the states you only reach by opening something -- the gallery quick-view,
# the free-video modal, the lesson stage). The CSS rule that fixes each slot
# is named beside it. None of these numbers is a guess; if the layout moves,
# re-measure and change them here rather than in a page.
#
#   widths -- the derivative ladder. A rung is skipped when the master is not
#             meaningfully bigger than it, so a 540px cover does not gain a
#             896px "derivative" that is really an upscale.
#   sizes  -- the `sizes` attribute for the slot this image is MOSTLY painted
#             in. A page that puts the same file in a second, much larger slot
#             (the lesson stage, a lightbox) must override `sizes` there; the
#             master is always the last srcset candidate, so an override that
#             is forgotten costs bytes, never sharpness.
# --------------------------------------------------------------------------

SLOTS = {
    # course.dc.html `.syl-thumb` is 76px (56px under 600px, industrial-3.css
    # :542,:544); lesson.dc.html `.uthumb` paints 78x48. The same file also
    # fills `#stage-poster` (.stage measures 366 / 828 / 938) and index's
    # `.cc-photo` at 269 -- hence the two big rungs.
    "course-poster": {
        "widths": [96, 160, 384, 768],
        "sizes": "(max-width: 600px) 56px, 78px",
    },
    # `.cd-cover` 320, `.rel-slide` 373, `.cc-cover` 269; 420 at 768, 366 at 390.
    "course-cover": {
        "widths": [224, 448, 896],
        "sizes": "(max-width: 600px) 366px, (max-width: 1000px) 420px, 373px",
    },
    # `#co-cover` (photos[0]) paints 446 at 1280; 358 at 390, 351 at 768.
    "course-photo-lead": {
        "widths": [128, 256, 448, 896],
        "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 351px, 446px",
    },
    # `.co-thumb` (photos[1..]) paints 104; 82 at 390, 173 at 768. Index reuses
    # the same files in `.cc-photo` at 269.
    "course-photo-thumb": {
        "widths": [128, 256, 448, 896],
        "sizes": "(max-width: 600px) 82px, (max-width: 1000px) 173px, 269px",
    },
    # `.art-img` paints 241-306 across all four viewports. The quick-view
    # `#am-img` is SMALLER, not larger: 175 / 186 / 237 / 237 -- the modal frame
    # is only 78% of a narrow column. Nothing on this page ever needs 1600px.
    "gallery": {
        "widths": [320, 640, 960],
        "sizes": "306px",
    },
    # `.fv-thumb` paints 301 / 348 / 395 / 448. `#fvm-poster` in the modal is
    # 346 at 390 and 880 from 1280 up, which is what the 896 rung is for.
    "free-poster": {
        "widths": [320, 480, 896],
        "sizes": "(max-width: 600px) 346px, (max-width: 1000px) 395px, 448px",
    },
    # The same tile in the 9:16 rail (`.fv-grid.is-vertical .fv-thumb`), which is
    # a different width entirely because more of them fit across. Measured in
    # Chromium: 186 at 390, 233 at 480, 287 at 600, 221 at 760, 171 at 1000,
    # 227 at 1280, 259 from 1440 up. The bands are not monotonic with viewport
    # width -- the rail changes how many tiles it shows -- so each band takes the
    # WIDEST value inside it, not the value at its edge.
    # Only tools/fetch-youtube-thumbs.py uses this slot: every 9:16 film Crystal
    # has supplied is a YouTube link, so the importer never sees one.
    "free-poster-vertical": {
        "widths": [320, 640],
        "sizes": "(max-width: 600px) 290px, (max-width: 1000px) 225px, 260px",
    },
}

# Images that are already checked into img/ and are written into the pages by
# hand (or by js/blog-data.js) rather than produced from assets/. The pipeline
# does not own their content -- it only adds the derivative ladder beside them,
# which is a pure function of the file, so this pass stays idempotent.
#
# Brand marks are deliberately absent: img/lau-logo.png, img/logo2.png and
# img/signature.png ship exactly as the client uploaded them.
# CSS background textures (wood-*.jpg, marble-wall.jpg, corkboard.jpg,
# Sketchbook.jpg, Postercolor.jpg) are absent too -- a background cannot carry
# srcset, so shrinking those is a stylesheet decision, not a data one.

STATIC_SLOTS = [
    # blog-post.dc.html `.post-banner`: 358 / 1173 / 1232 / 1392. Also Blog's
    # `.bf-img` at 647 and index's `.sc-img` at 150.
    {"glob": "img/blog-kit-all.jpg",
     "widths": [192, 384, 768, 1440],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 1173px, 1392px"},
    # blog-post.dc.html numbered-section figures: 358 / 720 / 760 / 760.
    {"glob": "img/blog-kit-[0-9]*.jpg",
     "widths": [384, 768],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px"},
    # Blog `.bc-img` 393, blog-post `.cover-card` 399, index `.sc-img` 150;
    # 358 at 390 and 720 at 768.
    {"glob": "img/blog-*.jpg",
     "widths": [192, 384, 768],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 720px, 452px"},
    # About `.arch-duo` portrait 515 / 584; the byline and teacher-card avatars
    # reuse the same file at 36-74, which is what the 96 rung is for.
    {"glob": "img/ProfilePic.jpg",
     "widths": [96, 160, 384, 640],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 380px, 584px"},
    # About `.phc-cell`, exhibition strip: 352 / 725 / 622 / 708.
    {"glob": "img/about/exhibition-*.jpg",
     "widths": [384, 768, 1440],
     "sizes": "(max-width: 600px) 352px, (max-width: 1000px) 725px, 708px"},
    # About `.phc-cell`, artful-living strip: 352 / 725 / 364 / 415.
    {"glob": "img/about/artful-living-*.jpg",
     "widths": [384, 768, 1440],
     "sizes": "(max-width: 600px) 352px, (max-width: 1000px) 725px, 415px"},
    # About `.phc-cell`, live drawing. Re-measured 2026-08-20 when the strip
    # became two stacked one-up carousels: the cell was 241px wide at two
    # photographs to a page and is 484px at one. The bands follow the LAYOUT
    # breakpoint (industrial-2.css:629, max-width 900px, where .bio-layout drops
    # to a single column) rather than the 1000px used elsewhere, because that is
    # where this cell's width actually jumps -- 285px at 901px, 836px at 900px.
    # Painted widths measured in Chromium: 536 at 600, 836 at 900, 484 from
    # 1440 (the --site-max cap) upwards.
    {"glob": "img/about/livedrawing-*.jpg",
     "widths": [256, 384, 768],
     "sizes": "(max-width: 600px) 540px, (max-width: 900px) 840px, 484px"},
    # About `.cert-mat` paints 100 at 1280 and 120 at 1600, and is not rendered
    # at all below 1000. cert-jiwi-2026.jpg was the worst oversample on the
    # site at 8.9x.
    {"glob": "img/about/cert-*.jpg",
     "widths": [128, 256],
     "sizes": "120px"},
    # Showcase `.pol-photo` 194-298; `#swm-img` in the modal 330 / 445.
    {"glob": "img/showcase/sw*.jpg",
     "widths": [224, 448],
     "sizes": "(max-width: 600px) 298px, 215px"},
    # index `.hero__img--large`: 251 / 504 / 487 / 487.
    # The client's own hero photographs, 2026-08-16 — one pair per carousel
    # slide. --large is the right-hand figure (右), --small the one overlapping
    # in front of it (左); the CSS at industrial-1.css:731-732 is what makes
    # that so, not the filenames. One glob covers all three of each, because the
    # painted size is identical on every slide.
    {"glob": "img/hero-[123]-large.jpg", "widths": [256, 512, 1024],
     "sizes": "(max-width: 600px) 251px, (max-width: 1000px) 504px, 487px"},
    {"glob": "img/hero-[123]-small.jpg", "widths": [160, 288, 576],
     "sizes": "(max-width: 600px) 136px, (max-width: 1000px) 274px, 280px"},
    # The three photographs these replaced are kept: cover3.jpg in particular is
    # still the Lessons free-trial band and the course free-videos preview.
    {"glob": "img/A1.jpeg", "widths": [256, 512, 1024],
     "sizes": "(max-width: 600px) 251px, (max-width: 1000px) 504px, 487px"},
    {"glob": "img/cover2.jpg", "widths": [256, 512, 1024],
     "sizes": "(max-width: 600px) 251px, (max-width: 1000px) 504px, 487px"},
    {"glob": "img/cover3.jpg", "widths": [256, 512, 1024],
     "sizes": "(max-width: 600px) 251px, (max-width: 1000px) 504px, 487px"},
    # index `.hero__img--small`: 136 / 274 / 280 / 280. BrushA.jpg is also a
    # decorative background at 452, which the 576 rung covers.
    {"glob": "img/BrushA.jpg", "widths": [160, 288, 576],
     "sizes": "(max-width: 600px) 136px, (max-width: 1000px) 274px, 280px"},
    {"glob": "img/BrushB.jpg", "widths": [160, 288, 576],
     "sizes": "(max-width: 600px) 136px, (max-width: 1000px) 274px, 280px"},
    {"glob": "img/Liquidwatercolor.jpg", "widths": [160, 288, 576],
     "sizes": "(max-width: 600px) 136px, (max-width: 1000px) 274px, 280px"},
    # index `.free-video` / `.arch-duo` square tiles: 358 / 420 / 373 / 373.
    {"glob": "img/A2.jpeg", "widths": [192, 384, 768],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px"},
    {"glob": "img/A3.jpeg", "widths": [192, 384, 768],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 420px, 373px"},
    # index `.gallery-tile`: 358 / 720 / 300 / 300.
    {"glob": "img/A4.jpeg", "widths": [384, 768],
     "sizes": "(max-width: 600px) 358px, (max-width: 1000px) 720px, 300px"},
    # index `.tb-photo` is 184 at every viewport.
    {"glob": "img/A6.jpeg", "widths": [192, 384], "sizes": "184px"},
    # index and lesson `.thumb`: 200 at every viewport. cover4.jpg was 722 KB
    # for a 200px square.
    {"glob": "img/Rubber.jpg", "widths": [224, 448], "sizes": "200px"},
    {"glob": "img/cover4.jpg", "widths": [224, 448], "sizes": "200px"},
]

DERIVATIVE_RE = re.compile(r"^(?P<stem>.+)-(?P<w>\d{2,4})(?P<ext>\.jpe?g)$", re.I)

# --------------------------------------------------------------------------
# course map — folder on disk -> slug used everywhere in the site.
#
# Deliberately explicit rather than auto-discovered, because the folder names
# do NOT map cleanly to what the user sees:
#   * Course 01-Beginner has empty legacy folders Lesson 13..24
#   * Course 01-Intermediate folders are still numbered Lesson 13..19 but must
#     be displayed as lessons 1..7
# --------------------------------------------------------------------------

COURSES = [
    {
        "slug": "clouds-beginner",
        "folder": "01 - Courses/Course 01-Beginner",
        # 2026-08-07: the client restructured this course. The folders are no
        # longer "Lesson 01".."Lesson 12" — they are "Lesson01" (no space),
        # a merged "Lesson02&03", then Lesson04..Lesson14. Lesson01 holds the
        # opening film that was missing before; Lesson13 and Lesson14 are new.
        # "Lesson02&03" is ONE folder holding ONE film that covers TWO of her
        # numbered lessons. That is why 13 folders make 14 lessons — the count
        # the client expects. A ("folder", n) pair means the folder SPANS n
        # lesson numbers: it stays one card, labelled "2 & 3", and the course
        # total counts n. A bare string spans one.
        "lesson_folders": ["Lesson01", ("Lesson02&03", 2)] + ["Lesson%02d" % n for n in range(4, 15)],
        # 7.1: the course preview reel, and the one-large-four-small photo set
        # (naming convention per 1.4.10), both at the course root.
        "highlights": "Beginner Highlights.mp4",
        "cover": "01 - Courses/Course 01-Beginner/Beginner HighlightCover.JPG",
        "photos": ["CloudsLesson12.jpg", "CloudsLesson12A.jpg", "CloudsLesson12B.jpg",
                   "CloudsLesson12C.jpg", "CloudsLesson12D.jpg"],
        "card_cover": "00 - Home/CloudsLesson14.jpg",
        "card_photo": "00 - Home/Beginnertools.jpg",
        "highlight_poster": "01 - Courses/Course 01-Beginner/Beginner HighlightCover.JPG",
    },
    {
        "slug": "clouds-intermediate",
        "folder": "01 - Courses/Course 01-Intermediate",
        # source folders keep the old global numbering; display order is 1..7
        "lesson_folders": ["Lesson %d" % n for n in range(13, 20)],
        "highlights": "Intermediate Hightlights.mp4",   # client's spelling
        "cover": "01 - Courses/Course 01-Intermediate/CloudsPaintingForIntermediates.JPG",
        # The client supplied five 小圖 for this course and no 大圖. The beginner
        # course has a named large image (1.4.10 records cloudslesson12.jpg as its
        # 大圖); nothing in her material names one here, so the order below is OURS
        # — her file numbering, taken as given. photos[0] becomes the lead at
        # 446px, so if she wanted a different one leading, this is the line.
        # Her fifth file's stem is 小圖書5 rather than 小圖5 — verbatim, not normalised.
        # Her fifth file leads. Looked at side by side, 小圖1..4 are in-progress
        # stills of one painting shot from the same angle — hand, brush, half-laid
        # wash — and 小圖書5 is the finished piece styled with the brushes and paint
        # pots. photos[0] is painted at 446px and is excluded from the small strip,
        # so the composed shot belongs there and a mid-stroke still does not.
        "photos": ["Intermediate小圖書5.JPG", "Intermediate小圖1.jpg", "Intermediate小圖2.jpg",
                   "Intermediate小圖3.jpg", "Intermediate小圖4.jpg"],
        "card_cover": "00 - Home/CloudsLesson20.jpg",
        "card_photo": "00 - Home/Intermediatetools.jpg",
        "highlight_poster":
            "01 - Courses/Course 01-Intermediate/CloudsPaintingForIntermediates.JPG",
    },
    {
        "slug": "little-house",
        "folder": "01 - Courses/Course 02",
        "lesson_folders": [""],          # flat folder, single lesson
        "highlights": None,
        "photos": [],
        # no card_cover: this course owns a single image, which is already the
        # cover, so the home card would show the same picture twice.
        "card_photo": "00 - Home/LittleHousetools.jpg",
    },
    {
        "slug": "urban-sketch",
        "folder": "01 - Courses/Course 03",
        "lesson_folders": [""],
        "highlights": None,
        "photos": [],
        "card_photo": "00 - Home/UrbanSketchingtools.jpg",
    },
]

# --------------------------------------------------------------------------
# 2026-08-16 asset drop: images the client named for a specific slot, which do
# NOT live under the course folder (three of the four sit in "00 - Home").
#
# Each COURSES value is a path RELATIVE TO THE ASSETS ROOT and is imported
# literally — the pipeline never walks "00 - Home". The reason is not that a
# private file sits there today: every private file currently in that folder IS
# caught by PRIVATE_PATTERNS. It is that the folder is one the client adds to
# between rounds, and the filter is a filename heuristic, not a guarantee —
# "Website 最底部REF（FOOTER).png" at the assets ROOT carries none of its markers
# and would sail straight through if anything ever walked the root. Naming one
# file per slot imports exactly what was asked for and nothing else.
#
# Every key is OPTIONAL: a course that omits one imports exactly as before and
# gets nulls in the manifest. A named file that is not on disk is a warning,
# never an exception.
#
#   course key         img/courses/<slug>/ stem   SLOTS row            manifest key
#   ---------------------------------------------------------------------------
#   card_cover         card-cover                 course-cover         cardCover
#   card_photo         card-photo                 course-photo-thumb   cardPhoto
#   highlight_poster   highlight-poster           free-poster          highlightPoster
#
# Slot choices are the closest EXISTING measured rows, not new numbers:
#   * card_cover fills index's `.cc-cover`, which is one of the three widths
#     "course-cover" was measured for (269 desktop).
#   * card_photo fills index's `.cc-photo` at 269 — named in the
#     "course-photo-thumb" comment as the slot that already reuses those files.
#   * highlight_poster is a video poster frame; "free-poster" is the site's
#     video-poster row (448 card / 896 modal), and the course preview column is
#     the same size as the free-video card.
# --------------------------------------------------------------------------

NAMED_COURSE_IMAGES = [
    ("card_cover", "card-cover", "course-cover", "cardCover"),
    ("card_photo", "card-photo", "course-photo-thumb", "cardPhoto"),
    ("highlight_poster", "highlight-poster", "free-poster", "highlightPoster"),
]

GALLERY_FOLDER = "02 - Gallery"
FREE_FOLDER = "05 - Free Videos"

# Free-video pairing is explicit so a missing .mov (Drive sync gaps) is a
# reported hole rather than a silently dropped card.
FREE_VIDEOS = [
    {"slug": "mountain", "poster": "Mountain.JPG", "video": "Mountain.MOV"},
    {"slug": "pink-floral", "poster": "PinkFloral.JPG", "video": "PinkFloral.MOV"},
    {
        "slug": "watercolor-airport",
        "poster": "Watercolor Airport.JPG",
        # client's Drive screenshot shows this file; it may not have synced yet
        "video": "Watercolor Airport 2.mov",
    },
]

warnings = []


def warn(msg):
    warnings.append(msg)
    print("  ! " + msg)


def slugify(name):
    """Lowercase ASCII, no spaces — safe to put in a URL without escaping."""
    stem = os.path.splitext(name)[0]
    stem = unicodedata.normalize("NFKD", stem).encode("ascii", "ignore").decode()
    stem = re.sub(r"[^a-zA-Z0-9]+", "-", stem).strip("-").lower()
    return re.sub(r"-{2,}", "-", stem) or "asset"


def natural_key(s):
    return [int(t) if t.isdigit() else t.lower() for t in re.split(r"(\d+)", s)]


# The client's working material lives in the same folders as her artwork: the
# six handwritten comment boards (*頁面*.jpg, *解說*.jpg) and the design
# reference shots she sent (*ref*.jpg). These are NEVER site content — one of
# them shipped to the public gallery as a saleable painting. Excluded here so
# it cannot happen again, and mirrored by the .gitignore patterns.
PRIVATE_PATTERNS = ("頁面", "解說", "ref.", " ref", "ref.jpg")


def is_private(name):
    low = name.lower()
    return any(pat.lower() in low for pat in PRIVATE_PATTERNS)


def list_dir(path):
    if not os.path.isdir(path):
        return []
    out = []
    for f in os.listdir(path):
        if f.startswith(".") or f.lower() == "desktop.ini":
            continue
        if is_private(f):
            print("   (skipping client working material: %s)" % f)
            continue
        out.append(f)
    return out


def pick(files, exts):
    return sorted([f for f in files if os.path.splitext(f)[1].lower() in exts], key=natural_key)


# every master this run has produced or measured — sweep_derivatives must never
# delete one of these, whatever it looks like next to another master's stem
MASTERS = set()


def site_rel(path):
    """img/gallery/g01.jpg — the form a page writes in an attribute."""
    return os.path.relpath(path, SITE_DIR).replace(os.sep, "/")


def derivative_path(master, width):
    stem, ext = os.path.splitext(master)
    return "%s-%d%s" % (stem, width, ext)


def sweep_derivatives(master, keep):
    """Delete `<stem>-<n>.jpg` files beside `master` that the current ladder no
    longer lists, so shrinking a ladder does not leave orphans in the repo.
    Only ever touches names that match the derivative pattern for THIS master,
    and never a file that is itself a master."""
    stem, ext = os.path.splitext(os.path.basename(master))
    folder = os.path.dirname(master)
    pattern = re.compile(r"^%s-(\d{2,4})%s$" % (re.escape(stem), re.escape(ext)), re.I)
    for name in os.listdir(folder) if os.path.isdir(folder) else []:
        m = pattern.match(name)
        if not m:
            continue
        p = os.path.join(folder, name)
        if p in MASTERS or site_rel(p) in keep:
            continue
        os.remove(p)
        print("   (removed stale derivative %s)" % site_rel(p))


def make_derivatives(master, widths, sizes, force=False):
    """Write `<stem>-<w>.jpg` beside the master for every ladder rung the master
    is actually bigger than, and return the srcset/sizes/w/h a page needs.

    Idempotent: an existing derivative is measured, not rewritten, unless
    --force. The master is always the last candidate, so a slot that is bigger
    than the page agent told us still gets a sharp image."""
    MASTERS.add(master)
    with Image.open(master) as im:
        mw, mh = im.size
        base = im.copy()

    cands = []
    for w in sorted(set(widths)):
        # A rung has to be materially smaller than the master to be worth a
        # file. At 0.95 the gallery gained seventeen 960px copies of ~1160px
        # masters — 2.7 MB in the repo to save a fraction of one fetch at
        # DPR 3 — and every 1600px photograph gained a 1440px twin. 0.80 keeps
        # the rungs that halve a fetch and drops the ones that shave it.
        if w >= mw * 0.80:
            continue
        p = derivative_path(master, w)
        if force or not os.path.exists(p):
            h = max(1, int(round(mh * w / float(mw))))
            base.resize((w, h), Image.LANCZOS).save(
                p, "JPEG", quality=JPEG_QUALITY, optimize=True, progressive=True)
        with Image.open(p) as im2:
            dw, dh = im2.size
        cands.append({"w": dw, "h": dh, "src": site_rel(p), "bytes": os.path.getsize(p)})
    cands.append({"w": mw, "h": mh, "src": site_rel(master),
                  "bytes": os.path.getsize(master)})

    sweep_derivatives(master, {c["src"] for c in cands})
    return {
        "w": mw, "h": mh,
        "srcset": ", ".join("%s %dw" % (c["src"], c["w"]) for c in cands),
        "sizes": sizes,
        "widths": [c["w"] for c in cands],
        "derivativeBytes": sum(c["bytes"] for c in cands[:-1]),
    }


def encode_image(src, dst, max_edge, force=False, slot=None):
    """Resize + re-encode. Returns {w, h, ratio, bytes} of the OUTPUT, plus
    {srcset, sizes, widths} when `slot` names a row in SLOTS."""
    if os.path.exists(dst) and not force:
        with Image.open(dst) as im:
            w, h = im.size
        meta = {"w": w, "h": h, "ratio": round(h / w, 4), "bytes": os.path.getsize(dst)}
        return with_derivatives(dst, meta, slot, force)

    os.makedirs(os.path.dirname(dst), exist_ok=True)
    with Image.open(src) as im:
        im = ImageOps.exif_transpose(im)          # honour camera rotation...
        im = im.convert("RGB")                     # ...then drop all EXIF
        if max(im.size) > max_edge:
            scale = max_edge / float(max(im.size))
            im = im.resize(
                (max(1, round(im.width * scale)), max(1, round(im.height * scale))),
                Image.LANCZOS,
            )
        im.save(dst, "JPEG", quality=JPEG_QUALITY, optimize=True, progressive=True)
        w, h = im.size

    n = os.path.getsize(dst)
    if n > SIZE_WARN_BYTES:
        warn("%s is %d KB (over the 600 KB ceiling)" % (os.path.relpath(dst, SITE_DIR), n // 1024))
    meta = {"w": w, "h": h, "ratio": round(h / w, 4), "bytes": n}
    return with_derivatives(dst, meta, slot, force)


def img_meta(meta):
    """The four fields a page reads, and nothing else. `None` for an image that
    does not exist (a lesson with no cover photo), so a page can tell the
    difference between "no ladder" and "ladder of one"."""
    if not meta or "srcset" not in meta:
        return None
    return {"srcset": meta["srcset"], "sizes": meta["sizes"],
            "w": meta["w"], "h": meta["h"]}


def with_derivatives(dst, meta, slot, force):
    if not slot:
        MASTERS.add(dst)
        return meta
    spec = SLOTS[slot]
    meta.update(make_derivatives(dst, spec["widths"], spec["sizes"], force))
    meta["slot"] = slot
    return meta


def probe_duration(path):
    """Seconds (int) via ffprobe, or None. Reads the header only."""
    if not os.path.exists(path):
        return None
    try:
        out = subprocess.run(
            ["ffprobe", "-v", "error", "-show_entries", "format=duration",
             "-of", "csv=p=0", path],
            capture_output=True, text=True, timeout=120,
        )
        return int(round(float(out.stdout.strip())))
    except Exception as exc:                                   # noqa: BLE001
        warn("ffprobe failed on %s (%s)" % (os.path.basename(path), exc))
        return None


def probe_portrait(path):
    """True when the video is taller than it is wide, else False, or None if it
    cannot be read. 1.4.12 makes a portrait lesson a normal case rather than an
    exception, so the ratio has to come from the file rather than a guess — the
    display matrix matters, since a rotated 1920x1080 file is portrait on screen."""
    if not os.path.exists(path):
        return None
    try:
        out = subprocess.run(
            ["ffprobe", "-v", "error", "-select_streams", "v:0",
             "-show_entries", "stream=width,height:stream_side_data=rotation",
             "-of", "default=noprint_wrappers=1:nokey=0", path],
            capture_output=True, text=True, timeout=120,
        )
        vals = dict(
            line.split("=", 1) for line in out.stdout.strip().splitlines() if "=" in line
        )
        w, h = int(vals.get("width", 0)), int(vals.get("height", 0))
        if not w or not h:
            return None
        rot = abs(int(float(vals.get("rotation", 0) or 0))) % 180
        if rot == 90:
            w, h = h, w
        return h > w
    except Exception as exc:                                   # noqa: BLE001
        warn("could not read dimensions of %s (%s)" % (os.path.basename(path), exc))
        return None


# --------------------------------------------------------------------------
# importers
# --------------------------------------------------------------------------

def import_courses(force):
    out = []
    for course in COURSES:
        root = os.path.join(ASSETS_DIR, course["folder"])
        if not os.path.isdir(root):
            warn("missing course folder: %s" % course["folder"])
            continue

        print("\n== %s (%s)" % (course["slug"], course["folder"]))
        lessons = []
        # `seq` numbers the poster files by position and must stay stable, or a
        # folder gaining a span would rename every image after it. `number` is
        # what the reader sees and advances by the span.
        seq, number = 0, 1
        for idx, entry in enumerate(course["lesson_folders"]):
            # ("Lesson02&03", 2) — one folder, one film, two of her lesson numbers
            sub, spans = entry if isinstance(entry, tuple) else (entry, 1)
            folder = os.path.join(root, sub) if sub else root
            files = list_dir(folder)
            images, videos = pick(files, IMAGE_EXTS), pick(files, VIDEO_EXTS)

            if not images and not videos:
                warn("%s/%s is empty — skipped" % (course["slug"], sub or "."))
                continue

            seq += 1
            rel, meta = None, None
            if images:
                rel = "img/courses/%s/l%02d.jpg" % (course["slug"], seq)
                meta = encode_image(os.path.join(folder, images[0]),
                                    os.path.join(SITE_DIR, rel), MAX_EDGE_POSTER, force,
                                    slot="course-poster")
            else:
                # 8.1 / 8.3: a lesson with no cover photo runs full width and
                # shows nothing in its place. It is not a reason to drop the
                # lesson — Lesson01 of the beginner course is video-only.
                warn("%s/%s has a video but no cover photo — lesson kept, poster left empty"
                     % (course["slug"], sub or "."))

            # A lesson folder may hold several videos: they are PARTS of one
            # lesson, played in natural filename order (client confirmed the
            # folder placement is authoritative, 2026-07-23 — e.g. Lesson 01 =
            # the short CloudsLesson2 intro then CloudsLesson3; Lesson 03 =
            # 5A then 5B). Lesson duration = sum of parts.
            parts = [{"file": v,
                      "dur": probe_duration(os.path.join(folder, v)),
                      "portrait": probe_portrait(os.path.join(folder, v))}
                     for v in videos]
            if any(pt["dur"] is None for pt in parts):
                warn("no duration for %s/%s" % (course["slug"], sub or "."))
            if len(parts) > 1:
                warn("%s/%s is multi-part: %s"
                     % (course["slug"], sub or ".",
                        ", ".join("%s=%ss" % (pt["file"], pt["dur"]) for pt in parts)))
            total = sum(pt["dur"] or 0 for pt in parts) or None

            lessons.append({
                "n": number,
                # spans > 1 means this one film carries more than one of her
                # numbered lessons: the card is labelled "2 & 3" and the course
                # total counts both.
                "spans": spans,
                "srcFolder": sub or os.path.basename(root),
                "poster": rel,
                "posterW": meta["w"] if meta else None,
                "posterH": meta["h"] if meta else None,
                # ticket 17: the responsive ladder for this poster. Every page
                # that paints it reads srcset/sizes/w/h from here via
                # build-data.py rather than re-deriving the widths.
                "posterMeta": img_meta(meta),
                "dur": total,
                "videos": parts,
                "srcVideo": parts[0]["file"] if parts else None,
            })
            label = str(number) if spans == 1 else "%d & %d" % (number, number + spans - 1)
            number += spans
            print("   %-7s %-22s %s%s" % ("l" + str(seq).zfill(2) + " (" + label + ")",
                                          images[0] if images else "(no cover)",
                                          ("%ds" % total) if total else "no video",
                                          " (%d parts)" % len(parts) if len(parts) > 1 else ""))

        if not lessons:
            warn("%s produced no lessons" % course["slug"])
            continue

        # course cover = first lesson poster that exists, at gallery size
        cover_rel = "img/courses/%s/cover.jpg" % course["slug"]
        # The cover is normally derived — the first image, in natural order, of
        # the first lesson folder that has one. That is a fallback, not a
        # decision: it picks whatever happens to sort first. Where the client
        # has supplied a cover on purpose, "cover" names it and wins. Her
        # HighlightCover files are exactly that (2026-08-16).
        cover_src = None
        named = course.get("cover")
        if named:
            cand = os.path.join(ASSETS_DIR, named)
            if os.path.exists(cand):
                cover_src = cand
            else:
                warn("%s: named cover %s not on disk, falling back to the first lesson image"
                     % (course["slug"], named))
        if not cover_src:
            for entry in course["lesson_folders"]:
                # entries may be ("folder", spans) — see the note on lesson_folders
                sub = entry[0] if isinstance(entry, tuple) else entry
                d = os.path.join(root, sub) if sub else root
                imgs = pick(list_dir(d), IMAGE_EXTS)
                if imgs:
                    cover_src = os.path.join(d, imgs[0])
                    break
        cover_meta = None
        if cover_src:
            cover_meta = img_meta(encode_image(
                cover_src, os.path.join(SITE_DIR, cover_rel), MAX_EDGE_GALLERY, force,
                slot="course-cover"))
        else:
            warn("%s has no photo to use as a cover" % course["slug"])
            cover_rel = None

        # 7.1: the five course photographs — one large, four small — live at the
        # course root under the naming convention in 1.4.10.
        photos = []
        for i, name in enumerate(course.get("photos") or []):
            src = os.path.join(root, name)
            if not os.path.exists(src):
                warn("%s: course photo %s not on disk" % (course["slug"], name))
                continue
            prel = "img/courses/%s/p%02d.jpg" % (course["slug"], i + 1)
            # photos[0] is the lead shot in `#co-cover` at 446px; the rest are
            # the 104px `.co-thumb` strip below it. Same ladder, different
            # `sizes`, because one `sizes` for both would cost the thumbs 4x.
            pm = encode_image(src, os.path.join(SITE_DIR, prel), MAX_EDGE_GALLERY, force,
                              slot="course-photo-lead" if i == 0 else "course-photo-thumb")
            photos.append({"src": name, "img": prel, "w": pm["w"], "h": pm["h"],
                           "meta": img_meta(pm)})
        if course.get("photos") and len(photos) < 5:
            warn("%s has %d of the 5 course photos" % (course["slug"], len(photos)))

        # 7.1: the course preview reel for the right-hand column.
        hl = course.get("highlights")
        highlights = None
        if hl:
            hp = os.path.join(root, hl)
            if os.path.exists(hp):
                highlights = {"file": hl, "dur": probe_duration(hp)}
                print("   preview  %-20s %ss" % (hl, highlights["dur"]))
            else:
                warn("%s: preview reel %s not on disk" % (course["slug"], hl))

        # The client-named per-slot images (see NAMED_COURSE_IMAGES). Optional
        # to the last detail: a course that names none of them contributes the
        # same six null fields and nothing else changes.
        named = {}
        for key, stem, slot, out_key in NAMED_COURSE_IMAGES:
            named[out_key], named[out_key + "Meta"] = None, None
            rel_src = course.get(key)
            if not rel_src:
                continue
            src = os.path.join(ASSETS_DIR, rel_src.replace("/", os.sep))
            if not os.path.isfile(src):
                warn("%s: %s %s not on disk" % (course["slug"], key, rel_src))
                continue
            drel = "img/courses/%s/%s.jpg" % (course["slug"], stem)
            nm = encode_image(src, os.path.join(SITE_DIR, drel), MAX_EDGE_GALLERY,
                              force, slot=slot)
            named[out_key], named[out_key + "Meta"] = drel, img_meta(nm)
            print("   %-16s %-38s %dx%d" % (stem, os.path.basename(rel_src),
                                            nm["w"], nm["h"]))

        record = {"slug": course["slug"], "folder": course["folder"],
                  "cover": cover_rel, "coverMeta": cover_meta, "photos": photos,
                  "highlights": highlights, "lessons": lessons}
        record.update(named)
        out.append(record)
    return out


def import_gallery(force):
    root = os.path.join(ASSETS_DIR, GALLERY_FOLDER)
    files = pick(list_dir(root), IMAGE_EXTS)
    if not files:
        warn("gallery folder is empty")
        return []

    print("\n== gallery (%d files)" % len(files))
    out = []
    for i, name in enumerate(files, 1):
        rel = "img/gallery/g%02d.jpg" % i
        meta = encode_image(os.path.join(root, name), os.path.join(SITE_DIR, rel),
                            MAX_EDGE_GALLERY, force, slot="gallery")
        # ratio drives the gallery frame; a missing value silently squashes art
        out.append({"img": "g%02d.jpg" % i, "src": name, "path": rel,
                    "w": meta["w"], "h": meta["h"], "ratio": meta["ratio"],
                    "meta": img_meta(meta)})
        print("   g%02d  %-24s %dx%d  r=%.2f" % (i, name, meta["w"], meta["h"], meta["ratio"]))
    return out


def import_free_videos(force):
    root = os.path.join(ASSETS_DIR, FREE_FOLDER)
    print("\n== free videos")
    out = []
    for item in FREE_VIDEOS:
        poster_src = os.path.join(root, item["poster"])
        if not os.path.exists(poster_src):
            warn("missing free-video poster: %s" % item["poster"])
            continue

        rel = "img/free/%s.jpg" % item["slug"]
        meta = encode_image(poster_src, os.path.join(SITE_DIR, rel), MAX_EDGE_GALLERY,
                            force, slot="free-poster")

        video_src = os.path.join(root, item["video"])
        present = os.path.exists(video_src)
        if not present:
            warn("free video not on disk (Drive sync?): %s" % item["video"])
        dur = probe_duration(video_src) if present else None

        out.append({"slug": item["slug"], "poster": rel, "posterW": meta["w"],
                    "posterH": meta["h"], "posterMeta": img_meta(meta),
                    "srcVideo": item["video"],
                    "videoPresent": present, "dur": dur})
        print("   %-20s %s" % (item["slug"], ("%ds" % dur) if dur else "no duration"))
    return out


def derive_static(force):
    """Second pass: the images that were already in img/ before this tool
    existed, and that the pages name directly (About's photo strips, the blog
    photographs, the home-page collage). Their CONTENT is not this tool's —
    nothing is re-encoded, nothing is moved. All this adds is the derivative
    ladder beside each one, plus the srcset/sizes/w/h that go with it, so a
    hand-written <img> on About.dc.html can be as responsive as a generated one.

    Runs after the asset passes so MASTERS already holds everything produced
    from assets/ and the sweep cannot mistake one of those for an orphan."""
    print("\n== static images")
    out = {}
    for spec in STATIC_SLOTS:
        folder = os.path.join(SITE_DIR, os.path.dirname(spec["glob"]))
        if not os.path.isdir(folder):
            warn("static slot folder missing: %s" % spec["glob"])
            continue
        for name in sorted(os.listdir(folder), key=natural_key):
            rel = "%s/%s" % (os.path.dirname(spec["glob"]), name)
            if rel in out:                       # first matching rule wins
                continue
            if not fnmatch.fnmatch(rel, spec["glob"]):
                continue
            path = os.path.join(SITE_DIR, rel.replace("/", os.sep))
            if DERIVATIVE_RE.match(name) and derivative_master(path):
                continue                          # it is a derivative, not a master
            try:
                meta = make_derivatives(path, spec["widths"], spec["sizes"], force)
            except Exception as exc:              # noqa: BLE001
                warn("could not derive %s (%s)" % (rel, exc))
                continue
            out[rel] = {"srcset": meta["srcset"], "sizes": meta["sizes"],
                        "w": meta["w"], "h": meta["h"]}
            print("   %-34s %4dx%-4d -> %s" % (name, meta["w"], meta["h"],
                                               " ".join(str(w) for w in meta["widths"][:-1]) or "(none)"))
    return out


def derivative_master(path):
    """`img/x-384.jpg` -> `img/x.jpg` when that file exists, else None. Stops
    the static pass treating its own output as a new master on the second run —
    and `blog-kit-1-4.jpg` is NOT a derivative of `blog-kit-1.jpg`, because no
    such master exists."""
    m = DERIVATIVE_RE.match(os.path.basename(path))
    if not m:
        return None
    cand = os.path.join(os.path.dirname(path), m.group("stem") + m.group("ext"))
    return cand if os.path.exists(cand) else None


def main():
    force = "--force" in sys.argv
    if not os.path.isdir(ASSETS_DIR):
        sys.exit("assets folder not found: %s" % ASSETS_DIR)

    manifest = {
        "generatedFrom": os.path.relpath(ASSETS_DIR, PROJECT_DIR).replace("\\", "/"),
        "courses": import_courses(force),
        "gallery": import_gallery(force),
        "freeVideos": import_free_videos(force),
    }
    # ticket 17: path -> {srcset, sizes, w, h} for the images the pages name
    # directly. Nothing reads this at runtime; it is here so a page agent can
    # look up the exact attribute text without re-measuring the layout.
    manifest["static"] = derive_static(force)
    manifest["warnings"] = warnings

    os.makedirs(TOOLS_DIR, exist_ok=True)
    with open(MANIFEST, "w", encoding="utf-8", newline="\n") as fh:
        json.dump(manifest, fh, indent=2, ensure_ascii=False)

    print("\nwrote %s" % os.path.relpath(MANIFEST, SITE_DIR))
    print("courses=%d  gallery=%d  free=%d  static=%d  warnings=%d" % (
        len(manifest["courses"]), len(manifest["gallery"]),
        len(manifest["freeVideos"]), len(manifest["static"]), len(warnings)))
    if warnings:
        print("\nWARNINGS")
        for w in warnings:
            print("  - " + w)


if __name__ == "__main__":
    main()
