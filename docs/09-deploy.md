# 09 — Deploy & Ops

## The repo

- **Remote:** `github.com/herman925/crystal-art-school` (`origin`), branch `main`.
- It's a static folder — the "app" is just files served over HTTP.

## Ship changes

```bash
git add -A
git commit -m "…"
git push origin main
```

There is **no CI** and **no host config** committed (no `.github/workflows`, no
`netlify.toml`, no Pages config). Pushing to GitHub stores the code; it does not deploy a
site by itself.

## Hosting options

Because it's static with no build, any static host works. Point the host at the **repo
root** (where `index.html` lives) and it's live. Options, easiest first:

- **GitHub Pages** — Settings → Pages → deploy from `main` / root. Zero config. Note: the
  case-sensitive `.dc.html` filenames and relative links must match exactly (Pages is
  case-sensitive; Windows local dev is not — see caveat below).
- **Netlify / Cloudflare Pages / Vercel** — connect the repo, set build command = none,
  publish directory = repo root.
- **Any bucket/CDN** (S3 + CloudFront, etc.) — upload the folder.

No environment variables, no secrets, no server runtime needed.

## Run locally

The JS needs a real HTTP origin (not `file://`):

```bash
python -m http.server 8080      # then open http://localhost:8080/index.html
```

Or `serve.bat` (Windows, if present — finds a free port 8080–8084), or any static server
(`npx serve`, VS Code Live Server).

## `.gitignore`

Ignores editor/design metadata (`*.artifact.json`), OS scratch (`.DS_Store`, `Thumbs.db`,
`*.tmp`), and `/uploads/` (local source uploads, not part of the site).

## Caveats & gotchas

- **Case sensitivity.** Filenames mix cases (`Gallery.dc.html`, `index.html`,
  `blog-post.dc.html`). Links reference them exactly. Windows/macOS local dev is
  case-insensitive and will forgive a wrong case; Linux hosts (GitHub Pages, most CDNs)
  will 404. Match the case exactly in any new link.
- **`.dc.html` on a plain host.** These are ordinary HTML files as far as a browser cares —
  they serve and run fine anywhere (they just load `support.js`). No special MIME handling
  needed. Ensure the host serves `.html`-family files as `text/html` (all do by default).
- **GSAP + fonts are external.** They load from jsDelivr and Google Fonts. Offline/blocked
  networks → the site still renders (motion degrades to static, fonts fall back to system).
- **Two source-of-truth surfaces.** Code lives in git *and* in the Claude Design project.
  Keep them in sync deliberately (see [08](08-claude-design-sync.md)); don't assume an edit
  in one appears in the other.
- **Logo pending on Claude Design.** `img/LAULogoFinal.png` still needs a manual drag-drop
  into the Claude Design `img/` folder (binary token limit). It's fine in git and on any
  real host — only the Claude Design preview 404s it.

## Deploy checklist

1. `git status` clean? Commit any stray work.
2. `git push origin main`.
3. If you edited CSS and also maintain the Claude Design project → re-split + re-sync
   `industrial.css` there ([08](08-claude-design-sync.md)).
4. If you changed course/gallery/blog data → check the duplicated copies across pages are
   in sync ([06](06-data-and-state.md)).
5. Hard-refresh the deployed URL; sanity-check the home hero, a course page (`?id=`),
   the gallery filters, and the cart drawer.

---

Back to the [wiki index](README.md).

## GitHub Pages does not always build on push

A push to `main` does **not** reliably trigger a build on this repo. Always:

```bash
gh api -X POST repos/herman925/crystal-art-school/pages/builds
gh api repos/herman925/crystal-art-school/pages/builds/latest   # expect "built"
```

**Then verify by md5, not by the status.** A run has reported success against a
stale commit, and this check has caught a commit whose CSS was missing entirely:

```python
live  = urlopen(Request(url + '?cb=' + str(int(time.time())),
                        headers={'Cache-Control': 'no-cache'})).read()
local = open(path, 'rb').read()
assert md5(live).digest() == md5(local).digest()
```

## ⚠️ The repo lives on Google Drive, and that breaks git

`G:\My Drive\…` is a virtual filesystem that reports stat metadata — and
sometimes content — that disagrees with the real file. Four failures, all seen:

- **Git reads stale bytes and commits them.** The worst one. `git status` reports
  clean, the committed blob is missing your edits, and the deployed page has the
  markup with none of its styling. Hashing the file's own bytes and asking git to
  hash the same path returned **different SHA-1s**. `git status` cannot detect
  this — it compares stale bytes against a blob built from the same stale bytes.
  Rewriting the file in place does not clear it; writing to a new name and
  renaming over the original does, because that replaces the directory entry.
- **Stale index stat-cache.** Files CRLF on disk while git holds LF, reported
  clean, because the index cached the on-disk size against the other blob's hash.
  `git update-index --really-refresh` did not clear it.
- **Phantom modified files.** ` M` in `git status` with an empty
  `git diff --numstat`. `git checkout --` on them discards nothing.
- **Stale zero-byte `.git/index.lock`.** Zero bytes means git never wrote a new
  index, so deleting it cannot corrupt anything. Check the size first.

Detect the first one before committing anything you care about:

```python
mine   = sha1(b'blob %d\0' % len(data) + data).hexdigest()
theirs = subprocess.run(['git','hash-object','--no-filters',path],
                        capture_output=True, text=True).stdout.strip()
assert mine == theirs
```

Working from a local clone and syncing to Drive separately would remove the whole
class of problem.

## Line endings

`.gitattributes` sets `* text=auto eol=lf`, with `*.bat` pinned CRLF. Before it
existed, Python's `open()` in text mode on Windows rewrote whole files from LF to
CRLF as a side effect, so a one-line edit produced a diff thousands of lines long
— which is how an orphaned CSS brace stayed invisible for two commits. Generators
write with an explicit LF newline argument.

## After deleting a CSS rule

Deleting a rule by matching its opening line leaves the tail behind, and the
orphaned `}` silently kills every rule after it until the parser recovers. This
happened three times in one session; the worst instance took out a grid and three
cards rendered full-width in a column for two commits.

```python
import re
d = bad = 0
for n, l in enumerate(open(f, encoding='utf-8').read().split('
'), 1):
    for ch in re.sub(r'/\*.*?\*/', '', l):
        if ch == '{': d += 1
        elif ch == '}':
            d -= 1
            if d < 0: print('stray close at', n); bad += 1; d = 0
print('depth %+d, stray %d' % (d, bad))
```

Related: after removing a wrapper element, grep for selectors and custom
properties scoped to it. Custom properties declared on a wrapper become
**undefined** when it goes, which makes every `calc()` using them invalid and the
rule silently stops applying — a five-row cap and a hide-on-mobile rule both died
that way in one edit.
