"""Compress img/*.jpg in place: max 1600px wide, JPEG quality 82, progressive."""
from PIL import Image, ImageOps
from pathlib import Path
import sys

MAX_W = 1600
QUALITY = 82
src = Path("img")

total_before = 0
total_after = 0
for p in sorted(src.glob("*.jpg")):
    before = p.stat().st_size
    total_before += before
    im = Image.open(p)
    im = ImageOps.exif_transpose(im)
    if im.mode != "RGB":
        im = im.convert("RGB")
    if im.width > MAX_W:
        new_h = round(im.height * MAX_W / im.width)
        im = im.resize((MAX_W, new_h), Image.LANCZOS)
    im.save(p, "JPEG", quality=QUALITY, optimize=True, progressive=True)
    after = p.stat().st_size
    total_after += after
    print(f"{p.name:30s} {before/1024:7.0f}K -> {after/1024:7.0f}K  ({100*after/before:.0f}%)")

print(f"\nTotal: {total_before/1024/1024:.2f}MB -> {total_after/1024/1024:.2f}MB  ({100*total_after/total_before:.0f}%)")
