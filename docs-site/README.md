# Website guide

The public `site/` source is Crystal's task guide. Administrator Markdown is maintained
outside this public repository. Do not add it to `site/admin/`; the build refuses that layout.

```sh
DOCS_ADMIN_SOURCE=/path/to/private-admin-source npm run build
```

The private source contains `index.md`, `handover.md`, `payments-and-access.md`,
`release-and-recovery.md`, `launch-checks.md`, `content-map.md` and `launch-call-agenda.md`.
Keep its private backup with the deployment records, outside the webroot.

Two separate builds are produced under `site/.vitepress/dist/`:

- `crystal/`: everyday editing and the approved price/order/access instructions.
- `admin/`: the same guide plus administrator material.

The PHP gate selects the edition from the authenticated guide account. Only `herman`
receives the administrator edition. This is separate from WordPress roles. Unknown/revoked
sessions are refused. Pages, chunks and search data all require the current session and
use no-store caching. Keep Sign out as a full `_self` navigation.

`deploy.sh --dry-run` builds without publishing. A real release preserves the existing
`users.php`, checks deployment preimages and backs up the old private tree. It installs the
account-aware gate before exposing the new tree. `install-release.py` also supports a
snapshot/install sequence for a guarded operator release. Never include credentials in a
release archive or use a guide publication to change CMS permissions.

The guide access tests require an explicitly enabled synthetic localhost HTTPS fixture;
they refuse production URLs. Installer tests use disposable files only.
