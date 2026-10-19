# Student dashboard subpages (#125)

Spec: `Dashboard.dc.html` and owner decisions. The original #137 UI render used local Docker with a student, two enrolments, one incomplete order and one wishlist course. Tutor Pro is not installed locally. Owner removed Wishlist on 2026-10-19; Docker is unavailable for a fresh render after that change.

## Approach (owner-chosen)

Tutor's own subpages each print a second top bar and sidebar. A student's request for one of them now redirects to the studio page with the matching tab open, so there is one shell, as in the prototype. Old links keep working.

| Request (student, GET) | Result |
|---|---|
| `/dashboard/courses/` (also `active-courses`, `completed-courses`, `my-courses`) | studio, My courses |
| `/dashboard/courses/wishlist/` | studio home; legacy route kept, wishlist records retained but no dashboard UI |
| `/dashboard/account/billing/` | studio, Purchases |
| `/dashboard/account/profile/`, `/dashboard/account/` | studio, Profile |
| `/dashboard/account/settings/` | studio, Settings |
| `/dashboard/quiz-attempts/`, `/dashboard/courses/my-quiz-attempts/`, `/dashboard/my-quiz-attempts/` | studio home (not reachable) |
| `/dashboard/account/reviews/`, `/dashboard/reviews/` | studio home (not reachable) |

Also removed: the Quiz Attempts tab on Tutor's Courses tab list (`tutor_enrolled_courses_page_tabs`). Instructor and admin views are unchanged. `discussions` is unchanged.

## Measured differences and result

| Page | Difference before | Result |
|---|---|---|
| My courses | Tutor top bar ("Crystal Art School / Hi, Student One") and Tutor sidebar under the studio hero; list rows, not cards | Fixed: `mc-course` card grid in the studio. Progress bar fill was missing (markup lacked `.pfill`, span was inline): fixed on all four bars |
| Purchases | Tutor "Billing" page with bullet list, badge, filter icons | Fixed: `dash-table` (Order, Date, Item, Amount, Receipt) |
| Wishlist | Tutor tab with list rows | Initially restyled as cards; owner removed tab/panel on 2026-10-19. Old route returns home; stored records remain untouched. |
| Profile | Tutor form under a second top bar, cover-photo banner, "Account" heading | Fixed: in the studio, cover photo and duplicate heading hidden, Save button added |
| Settings | Tutor tabs (Account, Security, Social, Billing Address, Preferences) | Main focus, Country, Save; Tutor's Login Info / Reset Password card kept |
| Quiz attempts, Reviews | reachable | Not reachable (tab, link, URL) |

## Defect found and fixed on the way

The studio page (`/dashboard/`) never started Tutor's settings component, so the Profile and Security forms printed Alpine errors (`tutorSettings is not defined`) and did nothing (no photo upload, no password reset). Tutor's script registers a page's components from the address. The studio now carries `?dashboard-page=settings` until the page has loaded, then the address is put back. Profile save was tested: first and last name saved through Tutor's own handler.

## Settings storage

| Field | Stored in |
|---|---|
| Main focus | usermeta `cas_main_focus` (Watercolour, Oils, Drawing, Acrylic, Ink) |
| Country | Tutor billing row `billing_country` (row created with the student's name and email when none exists) |

Save handler: `admin-post`, nonce, logged-in, current user only.

## Accepted differences (owner, 2026-09-30)

1. Settings has no "Full name" or "Email" field: names are edited in Profile, Tutor offers no email change. The prototype's "Privacy" card and "Delete account" are not built (owner decision).
2. Notification switches are not built: transactional mail only.
3. The Country list is Tutor's own list (for example "Hong Kong S.A.R."), not the prototype's three options.
4. Purchases has a status badge under the amount, and orders still waiting for payment show Tutor's Pay button in the Receipt cell. Invoice button is Tutor Pro's `tutor_dashboard_invoice_button`; it is empty without Pro.
5. Purchases shows the newest 50 orders, no paging.

## Wishlist removal (owner decision, 2026-10-19)

The earlier accepted seventh Wishlist tab is superseded. The panel and student navigation are removed. Tutor's legacy `/dashboard/courses/wishlist/` route sends students to the studio home. Saved Tutor wishlist data is not read, changed or deleted. `after-wishlist.png` is a historical screenshot of the pre-removal UI, not the current design.

## Verification from the original #137 implementation

These results predate the 2026-10-19 Wishlist removal:

- All 13 Tutor subpage addresses redirected as listed (local student).
- Settings saved Main focus and Country; the billing row was created when none existed.
- Profile save worked through Tutor's handler; no console errors appeared on Profile or Settings.
- 390px render had no horizontal overflow on any of the then-seven tabs.

## Wishlist-removal patch (2026-10-19)

- Static contract checks six dashboard panels, no Wishlist tab/panel/renderer, removal of Tutor's student Wishlist tab, and the legacy route redirecting to the studio home.
- The Wishlist renderer was removed. No usermeta deletion or Tutor wishlist removal API was added; saved records remain untouched.
- Docker is unavailable. No post-change HTTP or browser render was run.
- `tests/test_dashboard_pages_contract.py` and `tests/test_dashboard_contract.py` cover the removal.

## Not verified

- Tutor Pro's invoice button (Pro not installed locally): INFERRED to print in the Receipt cell because Tutor's own order card uses the same action.
- Live order history and live refunded order (issue mentions order 1 on the live site).
- Password reset email and avatar upload end to end.

Screenshots: `before-*.png`, `after-*.png`.
