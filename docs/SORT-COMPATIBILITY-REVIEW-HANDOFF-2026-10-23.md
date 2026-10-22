# Sorting compatibility hardening — source-only handoff

2026-10-23. Direct parent/frozen review ref: **84384e6fe2609ea0a2a21b724a710505e528fa80** (#187), preserving #186/#185/#181/#180 ancestry. Independent private-file composition remains #182 module SHA256 **d6fd882e820e9928885d91269baef69be19e1accc996a1382512eff634024b1a**. No frozen ref/mount was changed. Exact new SHA is this document's PR head.

## Source analysis / bounded fix

Private method names, query IDs and a callsite alone do not establish compatibility with a future vendor release. Draft widening now requires BOTH actual `TUTOR_VERSION` and the plugin's real `tutor.php` header Version to equal **4.1.3**, plus the exact inspected native sources:

- `classes/Course.php`: **ff2420312a655fda5c853d4e8fa5e270ec8fed835f369117b2adba7544855467**
- `classes/Utils.php`: **b5fbf74b3747b742f5f75abb3ce9043ca566670aead26fa5908087f4cacf4cf9**

Loaded Reflection file identities, method declaring class/visibility/staticness/arity, actual Utils instance, native AJAX/save-post callback bindings, real backend POST and session nonce/context are checked. No client-supplied version, configuration override or generic compatibility framework exists. Different vendor bytes (even the same version label) require a separately reviewed pin, not automatic widening.

The raw same-course graph/status probe runs BEFORE the vendor listing call, so an unsupported draft request does not call an unknown getter first. Ordinary native `tutor_update_course` now also runs the complete preflight in admin_init after auth/nonce/ownership, BEFORE any vendor handler/course-row write; core pre-insert and save-post checks remain as additional boundaries.

After conservation/native membership checks, a widening scope is created ONLY when non-published native items require it. The exact pinned private validator is invoked READ-ONLY during early preflight: this proves that its actual queries and currently installed query filters can accept the matrix before a title/body/publication save. If that check fails, the scope is cleared and503 returned before the native save. The pinned method only reads/validates; the native writer is never invoked here.

The bridge additionally requires the FIRST global get_posts caller to be the exact private validator, not merely a validator somewhere above a nested unrelated query. IDs are pinned, query caching disabled, and no body/front-end/native-listing query expands. No stored status, vendor file, role, capability, enrolment/payment or publication setting changes.

Published-only matrices use NO widening scope and retain the pinned, intact native path. Missing writer/structural prerequisites reject requested sorting, rather than silently discarding it. Ordinary title/body saves with NO sorting field stay outside this bridge; empty/new course controls remain. No blanket guarantee is made for arbitrary future plugins, concurrent code changes or privileged code that bypasses WordPress. This is preflight compatibility, not a transaction/concurrency redesign.

## Prepared cases — NOT executed by Development

- Existing real-WP mixed/draft/conservation/no-partial-mutation runner retained.
- `tests/fixtures/cas-builder-sort-fixture.php` adds a published-only control course, records exact local-home acknowledgement and actual vendor version/header/source hashes. Fresh synthetic IDs/passwords only on Review execution; capture mode600.
- `tests/fixtures/builder-sort-compat-harness.php` + `tests/test_builder_sort_compat_unit.py`: isolated no-WP/no-database future-version, unrecognised structure and non-admin-context negative profiles. They assert503, no caller title/body/publication mutation, no vendor getter call and empty scope; source checks also pin the exact version/fingerprints and early ordering. Unsupported-version test does NOT spoof a loaded WordPress plugin constant, avoiding accidental plugin upgrades/migrations.
- Actual-WP runner additionally prepares a REAL non-admin PHP-context course update with a draft matrix, proving the course/child snapshot remains unchanged before native write.
- `tests/fixtures/cas-sort-compat-fault.php`: LOCAL Review-only mu-plugin, never production. Requires the exact fresh fixture ledger/home/synthetic owner and a real Tutor nonce; otherwise inactive. It removes a native save binding for that request, or narrows the validator's IDs query AFTER the bridge, or issues an unrelated nested published-only IDs query. No vendor edits/status writes/version overrides or persistent settings changes.
- Real HTTP negatives cover missing binding / incompatible query filter on BOTH native actions, unchanged parent/order/status/author/title/body, safe published-only/no-sort controls, and nested unrelated-query isolation via a fixture response header. Set `CAS_SORT_COMPAT_FAULT_READY=1` only after actual Review installs that exact acknowledged fixture. Skips are not full acceptance. Inspect target before copying; never overwrite another mu-plugin; remove ONLY that exact task-owned test copy afterwards. Do not install into the ongoing frozen review snapshot.

Module source SHA256 **b25b61521eb00f7a295ee50d6273f27a0b3a3887757afa3373703b263d9b2f13**. PHP lint, Python AST parsing and whitespace checks ONLY; no unit harness, runtime, browser, fixture, stack, build/pull or production execution by Dev. Actual Review must verify the new exact composition, supported positive flow, negative contexts, served hashes, UI/Pro and full release acceptance. All HOLD/no merge/deploy/switch remains.
