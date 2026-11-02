# Synthetic uploader checks

Never run these against a live WordPress database or real Bunny credentials. The PHP scripts refuse non-local/non-synthetic configurations. `fixture/` contains only dummy accounts, keys and media metadata. The synthetic `.mp4` bytes test transport only; they are not a decodable film.

The checked fixture uses cached WordPress 7.1.3/PHP 8.3, MariaDB 11, Tutor/Tutor Pro 4.1.3, Elementor 4.3.5/Pro 4.3.2, Hello Elementor and this project's child theme. No dependency download/install hooks run during tests. Browser plugin was unavailable, so cached Playwright/Chromium were used.

Run in a new OS/container sandbox with no external network or host ports. PHP, MariaDB and the browser share only an isolated loopback network namespace. Use an empty explicit environment, read-only source/toolchain mounts, a private scratch HOME/temp/cache, no capabilities, no-new-privileges and bounded CPU/memory/process/file/disk/time limits. Do not mount credentials, Docker sockets, the host home or existing incident/review state into the target processes.

Fixture layout inside the PHP sandbox:

- `/target/bunny`: this plugin, read-only; `/target/theme`: child theme, read-only. Put cached vendor plugins/themes in equivalent read-only mounts and symlink them into a disposable WordPress copy under `/scratch/runtime/domain/public_html`; `/scratch/site` points to that copy.
- `/control`: these test/fixture files, read-only. Copy `wp3-config.php` to the scratch site's `wp-config.php`. Copy `bunny-config.php` to `/scratch/config/bunny.php` and set mode 0600. Install `mock.php` as a scratch MU plugin. The private config and fixture provider JSON stay outside the scratch webroot.
- MariaDB listens only on shared loopback port 3306 with dummy schema `cas_access`, prefix `ca_`, no grant tables and no other data. Use `setup.php`, `schema.php`, `configure.php`, `finish-native-install.php`, then `seed.php` once, in that order. Copy `/scratch/fixture.json` into the read-only browser control input before starting its sandbox.
- Serve the scratch site with `php -S 127.0.0.1:18089 -t /scratch/site /control/router.php`. Set `memory_limit=256M`, disable mail and use process timeouts.

Run PHP tests sequentially in the PHP sandbox:

```text
php runtime.php                         # 47 assertions
php native-save.php                     # 22 assertions
php config.php private                  # repeat: public, permissions, symlink, no-signing-key
```

`config.php` expects the plugin at `/target/bunny`. The other PHP tests expect the isolated WordPress copy and generated `/scratch/fixture.json`. Run from a read-only control mount; they write only assigned scratch/database fixtures. Native JSON termination is intercepted only by the test harness. Normal rollback is tested by injecting an exception after Tutor writes metadata.

In the browser sandbox, mount cached Playwright dependencies read-only at `/modules`, cached Chromium at `/chromium`, the generated fixture JSON at `/control/fixture.json`, and these browser scripts under `/control`. Run with `NODE_PATH=/modules`, scratch HOME/TMPDIR and a 120–140 second timeout:

```text
node browser.cjs    # upload → Ready → native Tutor save → fresh page/reopen
node recovery.cjs   # interrupted PATCH → same-file resume, no duplicate resource; cancel; approved picker
```

The browser refuses non-localhost requests. Screenshots/result JSON stay in its disposable scratch, never the public repository. Capture synthetic output only; screenshots of this fixture must not be labelled as live WordPress or real Bunny playback. Each full fixture should be disposable; repeated manual runs can intentionally reach the application's job limits. Do not disable those controls to make production tests pass.

The run used PHP .5 CPU/768 MiB/80 processes/256 MiB scratch, MariaDB .2 CPU/256 MiB/80 processes/256 MiB scratch, and browser .5 CPU/1 GiB/192 processes/192 MiB scratch; containers had 5400-second lifetime limits and individual checks shorter timeouts. Source and vendor/toolchain mounts were read-only. No production/provider operation occurred.

See the [implementation record](../../docs/research/BUNNY-UPLOAD-IMPLEMENTATION-2026-11-02.md) for the connected-provider and copied-link acceptance matrix still required before release.

Course Highlights extension: `highlights.php` exercises the native course update with public acknowledgement, distinct public/lesson catalogues, cross-scope rejection, stable embeds, removal, stale updates, preserved lesson/total course duration and interrupted-save rollback. The fixture must enable the normal `embedded` video source in Tutor's supported sources. `highlights-browser.cjs` exercises Basics → public approval → synthetic upload → Ready → Save as Draft → same-page saved-state refresh → reopen → public-only picker, then checks coexistence with the lesson panel.
