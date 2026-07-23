#!/usr/bin/env python3
"""
import-assets.py — one-way asset pipeline for the Crystal's Art School prototype.

Walks the client's Google-Drive `assets/` tree, and for every image it needs:
  * auto-rotates per EXIF, strips EXIF, resizes, re-encodes as progressive JPEG
  * writes it into img/ under a lowercase ASCII, space-free name
and for every video it needs:
  * reads the duration with ffprobe (header only — the files are 0.6-2.4 GB and
    are NEVER copied into the site)

Everything it learns is written to tools/asset-manifest.json, which
tools/build-data.py turns into js/course-data.js + js/gallery-data.js.

Re-runnable: safe to run again when the client sends more photos.

Usage:  python tools/import-assets.py [--force]
        --force   re-encode images even if the output already exists
"""

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
        # explicit whitelist: folders Lesson 13..24 exist but are EMPTY legacy
        "lesson_folders": ["Lesson %02d" % n for n in range(1, 13)],
    },
    {
        "slug": "clouds-intermediate",
        "folder": "01 - Courses/Course 01-Intermediate",
        # source folders keep the old global numbering; display order is 1..7
        "lesson_folders": ["Lesson %d" % n for n in range(13, 20)],
    },
    {
        "slug": "little-house",
        "folder": "01 - Courses/Course 02",
        "lesson_folders": [""],          # flat folder, single lesson
    },
    {
        "slug": "urban-sketch",
        "folder": "01 - Courses/Course 03",
        "lesson_folders": [""],
    },
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


def list_dir(path):
    if not os.path.isdir(path):
        return []
    return [f for f in os.listdir(path) if not f.startswith(".") and f.lower() != "desktop.ini"]


def pick(files, exts):
    return sorted([f for f in files if os.path.splitext(f)[1].lower() in exts], key=natural_key)


def encode_image(src, dst, max_edge, force=False):
    """Resize + re-encode. Returns {w, h, ratio, bytes} of the OUTPUT."""
    if os.path.exists(dst) and not force:
        with Image.open(dst) as im:
            w, h = im.size
        return {"w": w, "h": h, "ratio": round(h / w, 4), "bytes": os.path.getsize(dst)}

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
    return {"w": w, "h": h, "ratio": round(h / w, 4), "bytes": n}


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
        for idx, sub in enumerate(course["lesson_folders"]):
            folder = os.path.join(root, sub) if sub else root
            files = list_dir(folder)
            images, videos = pick(files, IMAGE_EXTS), pick(files, VIDEO_EXTS)

            if not images and not videos:
                warn("%s/%s is empty — skipped" % (course["slug"], sub or "."))
                continue
            if not images:
                warn("%s/%s has a video but no cover photo" % (course["slug"], sub or "."))
                continue

            n = len(lessons) + 1
            rel = "img/courses/%s/l%02d.jpg" % (course["slug"], n)
            meta = encode_image(os.path.join(folder, images[0]),
                                os.path.join(SITE_DIR, rel), MAX_EDGE_POSTER, force)

            # A lesson folder may hold several videos: they are PARTS of one
            # lesson, played in natural filename order (client confirmed the
            # folder placement is authoritative, 2026-07-23 — e.g. Lesson 01 =
            # the short CloudsLesson2 intro then CloudsLesson3; Lesson 03 =
            # 5A then 5B). Lesson duration = sum of parts.
            parts = [{"file": v, "dur": probe_duration(os.path.join(folder, v))}
                     for v in videos]
            if any(pt["dur"] is None for pt in parts):
                warn("no duration for %s/%s" % (course["slug"], sub or "."))
            if len(parts) > 1:
                warn("%s/%s is multi-part: %s"
                     % (course["slug"], sub or ".",
                        ", ".join("%s=%ss" % (pt["file"], pt["dur"]) for pt in parts)))
            total = sum(pt["dur"] or 0 for pt in parts) or None

            lessons.append({
                "n": n,
                "srcFolder": sub or os.path.basename(root),
                "poster": rel,
                "posterW": meta["w"], "posterH": meta["h"],
                "dur": total,
                "videos": parts,
                "srcVideo": parts[0]["file"] if parts else None,
            })
            print("   l%02d  %-22s %s%s" % (n, images[0], ("%ds" % total) if total else "no video", " (%d parts)" % len(parts) if len(parts) > 1 else ""))

        if not lessons:
            warn("%s produced no lessons" % course["slug"])
            continue

        # course cover = first lesson poster, re-encoded at gallery size
        cover_rel = "img/courses/%s/cover.jpg" % course["slug"]
        first_folder = course["lesson_folders"][0]
        first_dir = os.path.join(root, first_folder) if first_folder else root
        first_img = pick(list_dir(first_dir), IMAGE_EXTS)[0]
        encode_image(os.path.join(first_dir, first_img),
                     os.path.join(SITE_DIR, cover_rel), MAX_EDGE_GALLERY, force)

        out.append({"slug": course["slug"], "folder": course["folder"],
                    "cover": cover_rel, "lessons": lessons})
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
                            MAX_EDGE_GALLERY, force)
        # ratio drives the gallery frame; a missing value silently squashes art
        out.append({"img": "g%02d.jpg" % i, "src": name, "path": rel,
                    "w": meta["w"], "h": meta["h"], "ratio": meta["ratio"]})
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
        meta = encode_image(poster_src, os.path.join(SITE_DIR, rel), MAX_EDGE_GALLERY, force)

        video_src = os.path.join(root, item["video"])
        present = os.path.exists(video_src)
        if not present:
            warn("free video not on disk (Drive sync?): %s" % item["video"])
        dur = probe_duration(video_src) if present else None

        out.append({"slug": item["slug"], "poster": rel, "posterW": meta["w"],
                    "posterH": meta["h"], "srcVideo": item["video"],
                    "videoPresent": present, "dur": dur})
        print("   %-20s %s" % (item["slug"], ("%ds" % dur) if dur else "no duration"))
    return out


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
    manifest["warnings"] = warnings

    os.makedirs(TOOLS_DIR, exist_ok=True)
    with open(MANIFEST, "w", encoding="utf-8") as fh:
        json.dump(manifest, fh, indent=2, ensure_ascii=False)

    print("\nwrote %s" % os.path.relpath(MANIFEST, SITE_DIR))
    print("courses=%d  gallery=%d  free=%d  warnings=%d" % (
        len(manifest["courses"]), len(manifest["gallery"]),
        len(manifest["freeVideos"]), len(warnings)))
    if warnings:
        print("\nWARNINGS")
        for w in warnings:
            print("  - " + w)


if __name__ == "__main__":
    main()
