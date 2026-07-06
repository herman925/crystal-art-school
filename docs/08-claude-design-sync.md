# 08 — Claude Design Sync

The pages are authored in two places: this git repo (canonical) and a **Claude Design**
project (claude.ai/design) where they're click-editable. This page documents how to sync
local files up to that project, and the one hard limit that shapes the whole process.

- **Project:** "Crystal's Art School (Industrial)"
- **project_id:** `8a38ae8e-c2de-412f-ae1a-354fd9f4c9b1`
- **Tools:** the `mcp__claude_design__*` MCP tools (`list_files`, `read_file`,
  `finalize_plan`, `write_files`, `render_preview`, etc.). Requires a one-time
  `/design consent`.

> This repo is the **authoritative source**. Sync direction is usually local → remote
> (local is newer). Always diff before pushing — check `git log` / file sizes / etags so
> you don't overwrite newer edits made directly in Claude Design.

---

## The one hard limit: 32,000 output tokens per write

`write_files` takes file content **inline** (its `local_path` param is not implemented
server-side). A single tool call's output is capped at **32,000 tokens**. This bites in two
places and dictates the whole strategy. If you exceed it, the write fails with
`"response exceeded the 32000 output token maximum"` — which can look like a mid-stream
stall.

### Consequence 1 — large CSS must be split
`css/industrial.css` is ~140 KB ≈ 45k tokens > cap. A single write **always** fails.

**Fix (already applied on the remote):** a master `css/industrial.css` that only holds
three `@import`s, plus three part-files:

```css
/* css/industrial.css on Claude Design */
@import url("industrial-1.css");
@import url("industrial-2.css");
@import url("industrial-3.css");
```

- `industrial-1.css` = local lines 1–499
- `industrial-2.css` = local lines 500–999
- `industrial-3.css` = local lines 1000–end

Split at **complete-rule boundaries** (never mid-rule). `@import` relative URLs resolve
against `css/`, so the cascade is identical to the single file, and the pages' `<link
rel="stylesheet" href="css/industrial.css">` is unchanged. Verified: computed styles from
all three parts apply.

**Important:** in **this repo** `industrial.css` is the single canonical 140 KB file. On
Claude Design it's the 4-file `@import` version. If you edit CSS locally, you must re-split
on the next sync (the remote master + 3 parts, not the single file).

### Consequence 2 — binary assets can't go inline
Images are written as inline **base64**, which tokenizes at ~2 tokens/char. So a 99 KB PNG
≈ 260k tokens; even downscaled to 35 KB ≈ 94k tokens — all far over the cap. Practical
inline ceiling is ~8 KB raw (visibly degraded for a logo/photo).

**Fix:** don't auto-upload real images. **Drag-drop the file into the project's `img/`
folder via the Claude Design web UI** — full quality, seconds. This is how
`img/LAULogoFinal.png` (the nav logo) is uploaded. Until it's there, the logo 404s on
Claude Design but the rest of the site renders.

---

## Sync recipe (local → remote)

1. **Diff first.** `list_files` the project; compare sizes / etags against local. Confirm
   local is the newer side before overwriting.
2. **Consent + prompt.** Ensure `/design consent` is granted; call
   `get_claude_design_prompt` once before any write.
3. **HTML + small JS** — batch 4–6 files per `write_files` call (each well under the token
   cap). Thread each file's `if_match` etag (from `finalize_plan` `base_etags` or
   `list_files`) so a concurrent edit is caught, not clobbered.
4. **CSS** — split into master + 3 parts as above; one `write_files` per part (a single
   part ≈ 12–17k tokens, safely under the cap).
5. **Images / binaries** — hand off to the user for UI drag-drop. Do not base64 inline.
6. **`design_base.html`** — skip; it's byte-identical local/remote and unchanged.
7. **Verify** — `render_preview` → open the `serve_url` with a headless browser and check
   **computed CSS values** (not a screenshot). The raw `?direct=1` serve does **not** mount
   the `<x-dc>` runtime headless, so screenshots come out blank even when styles loaded
   fine — checking `getComputedStyle` on a few selectors from each CSS part is the real
   test. Give the user the durable `open_url` (claude.ai/design link), never the
   short-lived `serve_url`.

## Gotchas learned the hard way

- **Delegating a huge write to a subagent doesn't help** — the 32k cap kills the subagent
  too. Split first, then write. (Cost us three stalled attempts.)
- **`copy_files`** only copies from another Claude Design project, not from local disk.
- **Small writes are instant.** The stalls are purely payload size, not the tool.
- Windows `convert` on this machine is NTFS `convert.exe`, not ImageMagick — no CLI image
  tools available; use PowerShell `System.Drawing` if you must resize, or just drag-drop.

## Current sync state (as of last sync)

- ✅ 15 HTML pages + `js/motion.js` — synced.
- ✅ `css/industrial.css` — synced as master + `industrial-1/2/3.css`.
- ⛔ `img/LAULogoFinal.png` — **manual drag-drop pending** (binary token limit).

Next: [09 — Deploy & Ops](09-deploy.md).
