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
