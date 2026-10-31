# Owner-requested CMS controls — 2026-10-31

The owner authorised the picker, the ten follow-up corrections, scoped tests, deployment,
and clearly labelled preview content. This supplements the dated handoff and local Wayfinder
checkpoint. It does not reopen incident-held operations or approve launch.

## Implementation

- Home Gallery Image chooses an existing published Artwork, using a responsive scrollable
  grid, one radio selection, Save and Cancel. Save uses the existing actor/page snapshot,
  nonce, capability and conditional-write guard independently of unfinished Home text.
  It stores the artwork ID, so replacing its Featured Image updates Home. Unpublished,
  password-protected, missing/private or non-image choices are rejected. Previous order
  remains the fallback before a choice, or when a chosen artwork becomes unavailable.
- Home Student Comment Limit is 1–12, default 3, applied in the public query. Extras remain
  stored. Home now has 25 fields in its combined form plus a separately saved artwork choice.
  The original fixed 42 fields remain covered: these two owner-authorised additions bring
  the fixed controls to **44**, not a new website-completion or acceptance score.
- Blog's main action opens Manage Posts. Its six newest published articles with Featured
  Images supply Home's article row; no suitable posts means the row is omitted.
- Showcase uses a registered-student account selector, with account IDs to disambiguate names.
  User queries request only public columns; credits resolve the current display name.
  Invalid submissions preserve credit/medium. Old text credits remain until explicitly changed.
  Staff accounts are excluded from new choices. Photos remain manually uploaded Featured Images.
- Hub cards keep the site materials. All inner fixed editors use native WordPress appearance.
  Sticky section navigation and Save controls work at desktop/mobile widths. Project-owned
  workspace headings, actions, menus, custom post type and meta box labels use title case.
- Course archive introduction uses the full container width and wraps with the viewport.
- Guide source explains the new workflows and the current Bunny limitation: upload to the
  separate Bunny library, then attach the approved lesson reference. Direct upload from
  WordPress to Bunny is **not implemented**. No provider operation was performed.

## Executed checks

236 in-memory PHP cases, 14 mocked media JS cases, and 45 Python source contracts passed.
Four existing DB-runtime tests were skipped; they require explicit separate runtime setup.
These are source/synthetic evidence, not native acceptance by themselves.

Scoped native WordPress 7.1.3/PHP 8.3.36 checks on the existing independent takeover instance:
12-artwork grid, one selection, Cancel, persisted independent save, unsaved Home text
preservation, display-limit save/public query, invalid artwork/nonce rejection, generic
TinyMCE, sticky About section links, registered-student save/reload and forged-student denial.
Mobile picker adapts to two columns and scrolls without page overflow. Six demo posts render
four topic filters at desktop/mobile widths. No JS exceptions in the changed editing flows.
Tutor Pro remains absent locally; this is not full course-authoring or launch acceptance.

The first Showcase UI check was correctly denied because the restricted editor did not own
that new local demo post. Only its new local fixture author was changed before testing the
selector. No real/shared account or production ownership boundary was changed.
The first Blog render check found the local fixture page had no shortcode. Only the two
new local empty fixture pages were connected to the real Blog/Showcase shortcodes. Failures
and original artifacts remain preserved privately.

## Preview content and release

Six clearly labelled Demo Blog posts use four Categories: Watercolour, Oil Painting,
Diary and Tools. One labelled Showcase sample is credited to a new Subscriber named
Demo Student. Seven labelled geometric graphics are not teacher or student artwork.
Existing drafts, Artwork, course data and users remain intact. No mail notification is sent.
New production Blog post IDs: 311, 313, 315, 317, 319, 321; Showcase 323; attachments
312, 314, 316, 318, 320, 322, 324; the new Subscriber is 11. Exact category changes are
recorded privately for bounded later cleanup. The initial new Sketching category 25 was
removed only after its two owned demo posts moved to existing Diary and its use count was
confirmed empty. Existing Diary posts and category were preserved.
Replace/remove the demos before launch; do not delete by broad matching or infer ownership.

Release r1 applied and hash-verified: **22 theme files, 86 guide files, 22 guide pages**.
Authentication hashes and retained guide assets are unchanged. Seventeen staged PHP files
passed native PHP 8.3.36 syntax checks. Live Home shows six demo cards; Blog filters and
Showcase credit/lightbox passed read-only browser checks. Course introduction uses the
container edge at 1440/768px and wraps without overflow at 390px. Guide still requires login.
The r2 follow-up is also deployed: one theme file and 29 changed guide files were
hash-verified after native PHP lint. It completes preposition casing and aligns the guide
with Diary reuse. Authentication, 22-page inventory and retained assets were rechecked.
The final source matches the two owned release manifests. Own test containers were stopped;
volumes, original failures and receipts are preserved.
The gallery preflight found a comment-only difference from Git in the live helper; its exact
bytes were preserved and reviewed before accepting that specific preimage. Runtime statements
were identical. The initial preflight wrote no live files.

Private receipts: `~/cas-evidence/cms-controls-20261031-r1/` and the new `controls-r1/` fixture
inside the independent takeover evidence directory. No raw logs or account secrets belong
on GitHub. Guide release is limited to the existing 22 pages; all six drafts stay excluded.

Acceptance coverage remains **63/110**. All previous pending full journeys, human/device,
provider, guide-reader session and owner/content blockers remain as recorded in #211.
GitHub #105 publication remains blocked by the connector's previously observed 403; this
local record and HANDOFF.md hold the pending Wayfinder update. Do not retry with another identity.

## Newly observed content drift

Production post **278**, “Website test post — placeholder”, is currently **published** in
Diary, conflicting with the dated handoff's draft status. The new demo scripts did not edit
that post. It remains unchanged rather than assuming authority to undo a possible owner edit.
Review that placeholder before launch. This finding does not reset the acceptance score.
