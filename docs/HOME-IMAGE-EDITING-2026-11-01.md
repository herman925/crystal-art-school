# Home image editing — 2026-11-01

Owner scope: remove automatic Gallery Image popup, restore the prototype TOOLS image, and
address review comments A001–A006. Existing testing/deployment authorisation remains in force.

| Request | Delivered behaviour |
|---|---|
| Gallery chooser | Following its section link scrolls to it; only Choose Artwork opens the modal. |
| TOOLS image | Prototype `img/A6.jpeg` and its two responsive derivatives copied byte-for-byte into the theme. Explicit Clear Slot remains supported. |
| A001 | Three accessible slide tabs, keyboard arrows/Home/End, one visible panel. Save Home includes inactive tabs. |
| A002 | Large/small image panes sit side by side when the editing column permits, and stack at smaller widths. |
| A003 | Reposition opens a masked crop preview for the nine Home image fields. Drag, horizontal/vertical controls and 1–3× zoom; Use This Crop stages it, Cancel/Escape leaves the field alone. Save Home publishes it. |
| A004 | Recommended image sizes appear below each image/action row. |
| A005–A006 | Choose/Replace, Reposition and reset/clear controls sit beside the preview, using aligned equal-sized native buttons. |

Home About uses the same twin-arch SVG mask as the public card. Free Video uses the square
promo shape, TOOLS the rounded square, and hero previews use desktop slot ratios 580/442
and 334/280 with the existing asymmetric corners. Hero layout changes on phones; saved
normalised framing adapts to those slots rather than changing their public dimensions.

## Storage and preservation

Framing uses a reserved `#cas-crop=x,y,zoom` fragment on the existing image URL. It travels
with the selected picture, so replacement discards its old framing and the existing
actor/page snapshot and whole-option conditional save remain atomic. Bounds and syntax
are validated server-side. It adds no new field or separate live-writing crop endpoint.
The current fixed-control count remains 44; the acceptance score remains **63/110**.

The fragment is stripped before resolving attachments or fetching image bytes. Public
renderers apply bounded object-position/scale/origin values and retain native responsive
image ladders, alt text, loading attributes and the original image files. No upload is
edited, duplicated, removed, or recropped on disk. No cropping library was installed.

The original local worktree, historical incident evidence, real user/role/session state,
course data, providers, signup/indexing and guide authentication are outside this change.
The explicit TOOLS restore is limited to its one theme setting, using a guarded option
write with a private exact preimage and unrelated settings/autoload preservation.

## Checks

247 in-memory PHP cases and 14 mocked media JS cases passed. Python source contracts:
45 passed, four pre-existing DB-runtime tests skipped. These are synthetic/source checks.
Native checks used the existing independent takeover WordPress/PHP instance; the held
review stack was not run or changed. No browser infrastructure was installed.

Native browser checks passed: anchor does not auto-open chooser; tab click/keyboard changes
only the visible panel; wide image panes align; equal-size buttons and recommendations
have the requested positions; twin-arch crop modal; drag/sliders/zoom; Cancel preserves
input; crop survives Save Home/reload; inactive-slide fields save; mobile layout fits the
viewport; no browser JavaScript exceptions in the tested flow. Four native public-render
checks cover hero, About, Free Video and TOOLS framing; responsive derivatives are retained
for the shipped hero/About/TOOLS assets. This is not real-device or client acceptance.

Release applied and hash-verified: **15 theme files**, including eight PHP files linted
on the production PHP runtime, plus **31 changed guide files**. The guide retains the same
22 protected pages; six draft chapters remain excluded. Authentication hashes and untouched
assets were preserved. The one TOOLS setting was restored to A6 through its guarded write
and verified readback; unrelated values/autoload were unchanged.

The first live check found A6 present but its responsive ladder absent: the CLI import saved
an HTTP URL while the HTTPS renderer compared theme URLs byte-for-byte. The renderer now
recognises the same owned theme asset across HTTP/HTTPS, preserving its canonical served
URL and responsive ladder. The failed original check is preserved privately. Follow-up
publication is verified: one follow-up PHP file passed production lint and hash readback.
The HTTP-alias renderer passed a native check under HTTPS context, and the owned TOOLS
setting was normalised to HTTPS with a separate exact preimage and conditional write.
Live Home shows the requested photo and responsive ladder; its screenshot was inspected.
Four served CSS/JS hashes match. All three served JPEG sizes decode at 192×220, 384×441
and 624×716, and guide login protection remains in place.

A second wire assertion expected JPEG response bytes to equal the origin file. The served
JPEG encoding differs (progressive JFIF at origin versus baseline Exif in the response),
while origin file hashes, decoded dimensions and visible photo are correct. That failure
and response bytes are preserved; it is not reported as a successful byte comparison.
The final image checks establish delivery/decoding/appearance, not identical wire bytes. Production administrator login was not used. Scoped editing checks were local;
full production/client authoring acceptance remains pending.

Private artifacts: `~/cas-evidence/home-image-editing-20261101/` and the new frame-render
check in the independent takeover evidence directory. Do not upload private option
preimages, raw logs, credentials or session state to GitHub.

The GitHub #105 connector's existing write denial remains; HANDOFF.md and this file hold
the pending local Wayfinder update. Full course/payment, enrolled-progress, guide-reader,
human/device checks and incident-held work remain as recorded in the dated handoff.

The implementation is recorded in commit `13be7c6` plus its HTTP/HTTPS follow-up. Own test
containers were stopped, preserving volumes and original evidence. No unrelated WIP was
cleaned, stashed, reset or overwritten.
