# Native-listed draft sorting — focused follow-up / source only

2026-10-23. **No runtime/fixture/browser/stack/production execution by Dev.** Actual Review owns retesting. This fixes approved normal authoring, not a request to publish drafts or ask Herman to sort them.

## Exact composition

- Direct parent: reviewed author preview **200690905085118d274d9c9f05d28ed215562f9a** (#186), including sorting **ce20893f1ae7e16d65f02b2bc7e0dda77ee55c0f** (#185), builder c9aaa860 and price917a8d7 ancestry.
- Independent composed file remains private #182 `inc/cas-private-files.php`, SHA-256 **d6fd882e820e9928885d91269baef69be19e1accc996a1382512eff634024b1a**. It is not bundled/changed here; final Review must record the exact composition again.
- Only production delta: `inc/cas-content-builder.php`; source SHA-256 **455ca1ca64a4054c991d45426f06bb75c9aa0c5487e88d901efae9f78471bc19**. Exact commit is this document's PR head. Frozen refs/mounts are unchanged.

## Why this needs both a native set and a scoped read bridge

Tutor4.1.3 `Course::get_course_contents()` (Course.php1251–1286) is the actual builder-list API. Its topic/content WP_Query calls (Utils.php965–980 and1064–1079) do not hardcode published-only status; the real backend actor can legitimately receive draft lessons. The prior guard incorrectly demanded publish for every child.

Tutor's private `validate_course_content_order()` ALSO hardcodes publish in its topic/content IDs-only get_posts checks (Course.php3663–3693). Merely relaxing our first guard would still reject legitimate drafts later. The focused compatibility bridge therefore adjusts ONLY those exact private-validator IDs-only read queries, after the complete matrix is approved, under the same real backend editor/course. It pins post__in to the native-listed, raw-graph-checked IDs, uses their CURRENT stored statuses and disables query-result caching. It does NOT change any stored status, body/front-end query, global capability, publish setting or role.

The private method has no vendor validation hook, so the bridge additionally requires that exact vendor class/method on a no-arguments backtrace (bounded16frames), plus fields=ids, original status=publish, exact topic/course/type selectors, same actor and real own-course edit permission. This version-pinned guard must be reverified on Tutor upgrades; it fails closed if the private method/query changes. It cannot alter an unrelated matching IDs query or the native listing itself.

## Preserved boundaries

- Same strict entire-collection schema, duplicate/type rejection and before-course-row-save + before-sort entrypoints as #185.
- Authoritative CURRENT native list fetched under the checked actor, then every source topic/child verified against the real current DB parent/type/status graph.
- Full native matrix + stored child conservation remains mandatory. Native-listed drafts are accepted unchanged. Last-draft cross-topic moves and genuine empty topics/courses remain valid; implicit detach/omission remains409.
- Trash, unsupported types and stored children not in that actor's native list remain distinct conflicts; never silently dropped, retyped or published. No new deletion permission.
- Invalid later rows / foreign / trash cannot partly mutate course title/body or child parent/order/status/author. No transaction/concurrency lock or orphan recovery is added.

## Prepared retest material (NOT run)

`tests/fixtures/cas-builder-sort-fixture.php` now seeds mixed publish/draft own lessons, a last draft in its own topic, foreign draft, separate trash and unsupported controls, own PUBLISHED40/31 course, empty own course, exact fixture ledger/reset. Real synthetic Tutor metadata/native linkage only; explicit LOCAL existing-admin/home acknowledgement. No fixtures created by Dev.

`tests/test_builder_sort_runtime.py` first requests the REAL `tutor_course_contents` API using the owner's HTTP session nonce, verifies the draft list, then prepares both dedicated reorder and ordinary native course-save controls for array/indexed-object matrices and last-draft moves. Every positive preserves statuses/authors; every rejection preserves full parent/order/status/author/title/body snapshots. Existing malformed/later-row/foreign/conservation cases stay, plus trash/unsupported boundary and unscoped published-query negative. Explicit Review container/fixture JSON only, no old-stack default or launch.

PHP lint, Python AST syntax and diff whitespace checks only. Served hash parity, actual native UI/reorder, query isolation/cache behaviour, Pro and final combined acceptance remain actual Review gates. No deployment/merge/owner switch or guide publication is authorised here.
