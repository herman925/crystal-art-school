# Paid-materials access harness

Read-only source review found that Tutor attachments resolve to direct WordPress media URLs. This harness tests that access path with synthetic files and synthetic accounts. It does not inspect or copy real course content.

**Do not seed fixtures or send HTTP requests until Worker 1 confirms isolated local/staging runtime, exact versions, and cleanup access. Never target production.** This harness accepts only loopback HTTP, requires explicit opt-in, refuses production identifiers, and rejects cross-origin redirects.

**Current status:** This change integrates test source and a design proposal only. Runtime fixture seeding, the access matrix, and the cleanup round-trip remain pending. Do not seed fixtures or send HTTP requests until Operator hands off the attachment phase.


The fixture seeder creates one synthetic, published, non-public course; one paid lesson; one preview lesson; and five harmless files:

- Course attachment, expected private.
- Paid-lesson PDF and reference image, expected private.
- Preview-lesson attachment, observed only. Its policy remains unknown until owner rules exist.
- Unattached public image control, which must remain public.

It also creates clean synthetic users: an unenrolled subscriber, an enrolled subscriber, and a WordPress editor. Guest means no cookies. It creates no order and takes no payment. The editor snapshot records course/lesson ownership, `edit_post` checks, `manage_options`, and Tutor content access. An editor with relevant edit or Tutor access may legitimately access those materials; the runner does not treat that as an unauthorized download. Do not change the synthetic role's capabilities to force a result. If it cannot edit the fixture, treat its result as diagnostic, not as Crystal's planned role test.

The runner checks page markup and direct file requests separately. It checks body markers and file hashes, not HTTP status alone. It expects paid course and lesson files to be private from guests and unenrolled users; enrolled students may access them. Editor expectations derive from the recorded per-post permissions. The explicit preview lesson remains available, ordinary public media stays public, and preview-attachment outcomes are observations only.

## Preconditions

1. Worker 1 confirms the runtime is isolated from production.
2. Run WP-CLI and the Python runner where they can read the same private manifest file. If WP-CLI runs in a container, bind-mount a private host temp directory into it. `CAS_PAID_TEST_MANIFEST` is the WP-CLI path; `CAS_PAID_TEST_HOST_MANIFEST` is the host path passed to Python.
3. Set `WP_ENVIRONMENT_TYPE=local` or `staging`.
4. Set `CAS_PAID_TEST_ISOLATED=1` and `CAS_PAID_TEST_LOCAL_OPT_IN=1` for both seeding and HTTP tests.
5. Set `CAS_PAID_TEST_MANIFEST` to a new absolute path outside the WordPress root and source root as WP-CLI sees them. Use a private temporary directory. The manifest contains one-time synthetic passwords. The seeder writes it with mode `0600` and never prints passwords. Set `CAS_PAID_TEST_HOST_MANIFEST` to the corresponding bind-mounted host path.
6. Set `CAS_PAID_TEST_BASE_URL` to the exact loopback site URL from the manifest, such as `http://127.0.0.1:18089`. The runner accepts only `localhost`, `127.0.0.1`, or `::1` over HTTP.
7. Set `CAS_PAID_TEST_WEB_ROOT` and `CAS_PAID_TEST_REPO_ROOT` to absolute paths as WP-CLI sees them. Set `CAS_PAID_TEST_HOST_REPO_ROOT` to the local checkout path used by Python. The runner checks the recorded WP paths; it does not need to mount WordPress files.
8. Set `CAS_PAID_TEST_ACTION=seed` before running the seeder. The seeder also requires a WP-CLI administrator and Tutor LMS `4.1.0`.
9. Preserve the runtime owner's verified mail-guard override as the final Compose override. Before seeding, verify the regular, read-only INI is parsed by PHP in both WordPress and WP-CLI. Effective `sendmail_path` must be `/bin/false`; PHPMailer must target unauthenticated local Mailpit. These are operator preconditions, not safeguards this attachment seeder enforces.

Do not use a real user, credential, order, course, or attachment. Do not reuse a manifest. Do not place manifests or logs in the repository or web root.

Run offline safety tests before any staging work:

```sh
python3 -B tests/test_paid_materials_safety.py
```

These tests make no HTTP requests and do not seed WordPress data.

Integration check (2026-10-19): **9 offline tests passed, 0 failed**. PHP syntax check: **1 file passed, 0 failed**, using a cached PHP image with `--network none` and no runtime mounts. Active access tests remain pending.

## Run sequence

Do not run these commands until Worker 1 confirms isolation.

1. Set the seeder preconditions in the WP-CLI environment. Set the loopback URL, opt-in flags, recorded WordPress root, and host manifest/repository paths in the Python environment.
2. Set `FIXTURE_ADMIN_ID` to the synthetic local administrator's ID. Run the seeder once, using paths visible inside the WP-CLI runtime:

   ```sh
   wp --user="$FIXTURE_ADMIN_ID" eval-file "$CAS_PAID_TEST_REPO_ROOT/tests/paid-materials-security/seed.php"
   ```

   The seeder prints only the run ID and manifest path. It does not implement private delivery; unauthorized access may produce an expected failing result.
3. Keep the same environment and manifest path. Run:

   ```sh
   python3 -B "$CAS_PAID_TEST_HOST_REPO_ROOT/tests/paid-materials-security/run.py" \
     --manifest "$CAS_PAID_TEST_HOST_MANIFEST" \
     --repo-root "$CAS_PAID_TEST_HOST_REPO_ROOT"
   ```

   The runner sends GET requests and POSTs only synthetic credentials to loopback `wp-login.php`.
4. Review findings. A direct private-file response is a failing result, even if the protected lesson page itself denies access.
5. After evidence capture, set `CAS_PAID_TEST_ACTION=cleanup` and run the seeder again. It validates every synthetic ID and marker before deleting. Cleanup uses raw SQL for the synthetic user rows, so it does not deserialize user metadata.
6. Read the seeder cleanup summary and confirm each fixture count is zero. Successful cleanup removes the private manifest.

If seeding stops after creating the manifest, keep it and use cleanup after checking its run ID. If any safety guard fails, stop. Do not weaken a guard to make the test run. Report the exact blocker to Operator.

## Result labels

| Result | Meaning |
|---|---|
| `PASS` | Observed behavior matches explicit test expectation. |
| `FAIL` | Paid material reached a guest or unenrolled user, or an editor without relevant edit/Tutor access; enrolled access failed; public control failed; or fixture page was unavailable. |
| `OBSERVE` | Preview-attachment result recorded without an access-policy assertion. |

A `FAIL` on the current implementation is useful evidence. Do not call it a harness failure. A missing or invalid marker is not enough to pass an unauthorized request unless response is a clear denial or same-origin login redirect.

## Known limits

- This is an isolated staging harness, not a production probe or full pentest.
- It does not test webhook authenticity, Bunny token enforcement, or media-cache behavior.
- It does not assert that every attachment on a preview lesson is public. A lesson preview flag does not decide attachment policy.
- It does not modify theme/plugin files or implement private downloads.
- It expects Tutor `4.1.0`; it stops on other versions or missing APIs.
