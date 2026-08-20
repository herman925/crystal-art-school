"""Fetch a local cover photo for every YouTube film on the Free Videos page.

    python tools/fetch-youtube-thumbs.py [--force]

WHY THIS EXISTS
---------------
Crystal supplied twelve of her films as YouTube links rather than files. A link
has no local file to cut a frame from, so tools/import-assets.py never sees them
and tools/build-data.py emitted `"poster": null` for all twelve. The reasoning
written into build-data.py was that "the embed brings its own thumbnail" -- which
is true of the player once it opens, and not true of the CARD in the rail. The
result on the live page was twelve empty dark rectangles with a play button, and
the whole Shorts row reading as broken. (client feedback 2026-08-21)

WHAT IT DOES
------------
For each `host: "youtube"` record in tools/content-meta.json it downloads the
best cover YouTube publishes for that id, writes it into img/free/ through the
same encode_image() the importer uses -- same slot table, same derivative ladder,
same progressive JPEG, same EXIF strip -- and records the result in
tools/youtube-thumbs.json for build-data.py to read.

WHICH THUMBNAIL
---------------
YouTube publishes several, and WHICH ONE IS RIGHT DEPENDS ON THE SHAPE OF THE
TILE the cover has to fill. Every candidate is downloaded and MEASURED -- an
absent one answers 200 with a 120x90 grey placeholder, so the HTTP status cannot
be trusted, and the published names differ from video to video for no reason
this script can predict.

For a 9:16 record (the Shorts rail) the shape matters more than the resolution.
A 1280x720 cover in a 9:16 tile is cropped to a narrow vertical strip through the
middle of a horizontal frame, which is worse than a small picture. So:

    pass 1, anything NOT wider than square (h >= w), best pixel area wins:
        oardefault.jpg   the original aspect. 1080x1920 for most of her Shorts.
        oar2.jpg         a second original-aspect variant. Present on some films
                         where oardefault is not -- there is no telling which
                         from the id, hence both.
        frame0.jpg       270x480, the frame at t=0. Small, and correct shape.
                         Two of her Shorts publish nothing else portrait, and
                         this beats a 16:9 crop -- but a film that fades in from
                         black publishes a black rectangle here, so a candidate
                         with no tonal variation at all is rejected as blank.
    pass 2, landscape masters, only if pass 1 found nothing:
        maxresdefault.jpg, hq720.jpg, sddefault.jpg, hqdefault.jpg

For a 16:9 record the order is simply largest-first:
    maxresdefault.jpg, hq720.jpg, sddefault.jpg, then the original-aspect names,
    then hqdefault.jpg.

hqdefault.jpg is 480x360 -- 4:3, with black bars top and bottom. It is the last
resort in both lists and lands in WARNINGS when it is used, because shipping a
letterboxed cover as though it were a frame is the kind of thing that should be
said out loud rather than discovered on the page.

A file smaller than 200px on its long edge is treated as a placeholder.

WHY A SEPARATE FILE RATHER THAN asset-manifest.json
---------------------------------------------------
asset-manifest.json is import-assets.py's output and it rewrites `freeVideos`
wholesale from the files on disk. Anything written there by this script would
disappear the next time the importer ran, silently, and the twelve cards would
go back to being empty. tools/youtube-thumbs.json is this script's own output and
survives an importer run. build-data.py reads both.

RE-RUN ORDER
------------
    python tools/import-assets.py         # only when the source assets change
    python tools/fetch-youtube-thumbs.py  # needs the network; safe to re-run
    python tools/build-data.py

This script needs the network and nothing else. It does NOT read the client's
assets folder.

WHAT IT DOES NOT DO
-------------------
It does not read a duration. YouTube does not publish one on a thumbnail URL,
and a running time guessed from anything else would be an invented number of
exactly the kind this project has had to strip out before. The cards stay
without a duration badge.
"""
import argparse
import json
import os
import sys
import tempfile
import urllib.error
import urllib.request

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import importlib.util

_spec = importlib.util.spec_from_file_location(
    "import_assets", os.path.join(os.path.dirname(os.path.abspath(__file__)), "import-assets.py"))
_ia = importlib.util.module_from_spec(_spec)
_spec.loader.exec_module(_ia)          # guarded by __main__; importing runs nothing

from PIL import Image, ImageStat

TOOLS_DIR = os.path.dirname(os.path.abspath(__file__))
SITE_DIR = os.path.dirname(TOOLS_DIR)
META = os.path.join(TOOLS_DIR, "content-meta.json")
OUT = os.path.join(TOOLS_DIR, "youtube-thumbs.json")

# Original-aspect names first for a vertical tile, largest-first for a wide one.
# See the module docstring for why the order differs and why both `oardefault`
# and `oar2` are tried.
TALL_FIRST = ["oardefault", "oar2", "frame0"]
WIDE_FIRST = ["maxresdefault", "hq720", "sddefault"]
LAST_RESORT = ["hqdefault"]
PLACEHOLDER_EDGE = 200
UA = "Mozilla/5.0 (compatible; crystal-art-school asset pipeline)"


def fetch(video_id, name):
    """Return the bytes of one candidate, or None if it is absent or a placeholder."""
    url = "https://i.ytimg.com/vi/%s/%s.jpg" % (video_id, name)
    req = urllib.request.Request(url, headers={"User-Agent": UA})
    try:
        with urllib.request.urlopen(req, timeout=20) as fh:
            blob = fh.read()
    except (urllib.error.HTTPError, urllib.error.URLError, OSError):
        return None
    if not blob:
        return None
    fd, tmp = tempfile.mkstemp(suffix=".jpg")
    os.close(fd)
    with open(tmp, "wb") as fh:
        fh.write(blob)
    try:
        with Image.open(tmp) as im:
            w, h = im.size
    except Exception:
        os.remove(tmp)
        return None
    if max(w, h) < PLACEHOLDER_EDGE:
        os.remove(tmp)
        return None                     # the grey 120x90 "no such thumbnail" tile
    # frame0.jpg is literally the frame at t=0, and a film that fades in from
    # black publishes a black rectangle there. One of Crystal's Shorts did, and
    # it shipped as an all-black tile that read as a broken image. A picture with
    # no variation at all is not a cover.
    with Image.open(tmp) as im:
        spread = ImageStat.Stat(im.convert("L")).stddev[0]
    if spread < 3.0:
        os.remove(tmp)
        return None
    return tmp, w, h


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--force", action="store_true",
                    help="re-encode even when the derivative is newer than the master")
    args = ap.parse_args()

    with open(META, encoding="utf-8") as fh:
        meta = json.load(fh)

    records = {s: c for s, c in meta["freeVideos"].items()
               if not s.startswith("_") and c.get("host") == "youtube"}
    if not records:
        sys.exit("no host:youtube records in content-meta.json")

    print("== youtube covers (%d films)" % len(records))
    out, warnings = {}, []
    for slug, copy in records.items():
        vid = copy["youtubeId"]
        tall = copy.get("ratio") == "9:16"
        # The RECORD decides, not the file. `sizes` describes the box the cover
        # is painted in, and which rail a film lands in is decided by its ratio
        # string -- so a portrait file in a 16:9 record still needs 16:9 `sizes`.
        slot = "free-poster-vertical" if tall else "free-poster"

        order = (TALL_FIRST + WIDE_FIRST if tall else WIDE_FIRST + TALL_FIRST) + LAST_RESORT
        got = name = None
        for candidate in order:
            hit = fetch(vid, candidate)
            if not hit:
                continue
            # On a vertical tile, skip a landscape candidate while an
            # original-aspect one might still be coming. Square counts as
            # acceptable: cropping a square to 9:16 loses the sides, cropping a
            # 16:9 to 9:16 loses almost everything.
            if tall and candidate in TALL_FIRST and hit[2] < hit[1]:
                os.remove(hit[0])
                continue
            got, name = hit, candidate
            break
        if not got:
            warnings.append("%s (%s): no usable thumbnail at any of %s"
                            % (slug, vid, ", ".join(order)))
            print("   !  %-24s no thumbnail" % slug)
            continue
        tmp, w, h = got
        if name == "hqdefault":
            warnings.append("%s (%s): fell back to hqdefault, which is 480x360 and "
                            "carries black bars top and bottom" % (slug, vid))
        if tall and h < w:
            warnings.append("%s (%s): the only cover published is %dx%d, wider than "
                            "it is tall, and this film sits in the 9:16 rail -- it "
                            "will be cropped hard" % (slug, vid, w, h))
        if max(w, h) < 600:
            warnings.append("%s (%s): best cover is only %dx%d; sharp at 1x, soft on "
                            "a 2x screen" % (slug, vid, w, h))

        rel = "img/free/%s.jpg" % slug
        m = _ia.encode_image(tmp, os.path.join(SITE_DIR, rel),
                             _ia.MAX_EDGE_GALLERY, args.force, slot=slot)
        os.remove(tmp)

        out[slug] = {
            "poster": rel,
            "posterW": m["w"],
            "posterH": m["h"],
            "posterMeta": _ia.img_meta(m),
            "source": "https://i.ytimg.com/vi/%s/%s.jpg" % (vid, name),
            "youtubeId": vid,
        }
        print("   %-24s %s  %dx%d  (%s)" % (slug, name, m["w"], m["h"], slot))

    with open(OUT, "w", encoding="utf-8", newline="\n") as fh:
        json.dump({"_note": "GENERATED by tools/fetch-youtube-thumbs.py. Cover "
                            "photos for the films Crystal supplied as YouTube "
                            "links. Kept out of asset-manifest.json because the "
                            "importer rewrites that file wholesale and would "
                            "drop these. Re-run after import-assets.py, before "
                            "build-data.py.",
                   "thumbs": out}, fh, indent=2, ensure_ascii=False)
    print("\nwrote %s  (%d covers)" % (os.path.relpath(OUT, SITE_DIR), len(out)))
    if warnings:
        print("\nWARNINGS")
        for w in warnings:
            print("  - " + w)


if __name__ == "__main__":
    main()
