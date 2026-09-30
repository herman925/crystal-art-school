# Student dashboard subpages (#125)

Spec: `Dashboard.dc.html` and the owner decisions of 2026-09-30 (issue #125). Tested on the local Docker stack as a student with two enrolments, one incomplete order and one wishlist course. Tutor Pro is not installed locally.

## Approach (owner-chosen)

Tutor's own subpages each print a second top bar and sidebar. A student's request for one of them now redirects to the studio page with the matching tab open, so there is one shell, as in the prototype. Old links keep working.

| Request (student, GET) | Result |
|---|---|
| `/dashboard/courses/` (also `active-courses`, `completed-courses`, `my-courses`) | studio, My courses |
| `/dashboard/courses/wishlist/` | studio, Wishlist (new tab) |
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
| Wishlist | Tutor tab with list rows | Fixed: course cards, new tab |
| Profile | Tutor form under a second top bar, cover-photo banner, "Account" heading | Fixed: in the studio, cover photo and duplicate heading hidden, Save button added |
| Settings | Tutor tabs (Account, Security, Social, Billing Address, Preferences) | Main focus, Country, 3 switches, Save; Tutor's Login Info / Reset Password card kept |
| Quiz attempts, Reviews | reachable | Not reachable (tab, link, URL) |

## Defect found and fixed on the way

The studio page (`/dashboard/`) never started Tutor's settings component, so the Profile and Security forms printed Alpine errors (`tutorSettings is not defined`) and did nothing (no photo upload, no password reset). Tutor's script registers a page's components from the address. The studio now carries `?dashboard-page=settings` until the page has loaded, then the address is put back. Profile save was tested: first and last name saved through Tutor's own handler.

## Settings storage

| Field | Stored in |
|---|---|
| Main focus | usermeta `cas_main_focus` (Watercolour, Oils, Drawing, Acrylic, Ink) |
| Country | Tutor billing row `billing_country` (row created with the student's name and email when none exists) |
| New course alerts, Lesson reminders, Promotions | usermeta `cas_notify_new_course`, `cas_notify_reminders`, `cas_notify_promos`: '1' on, '0' or missing off (default off); `cas_notify_updated_at` (UTC) when any changes |

Save handler: `admin-post`, nonce, logged-in, current user only, three whitelisted keys. No Tutor notification table or addon. Worker 4 (#133) reads these flags. Nothing sends mail yet.

## Accepted differences (owner to confirm)

1. Settings has no "Full name" or "Email" field: names are edited in Profile, Tutor offers no email change. The prototype's "Privacy" card and "Delete account" are not built (owner decision).
2. The Country list is Tutor's own list (for example "Hong Kong S.A.R."), not the prototype's three options.
3. Wishlist is a seventh tab, which the prototype does not have.
4. Purchases has a status badge under the amount, and orders still waiting for payment show Tutor's Pay button in the Receipt cell. Invoice button is Tutor Pro's `tutor_dashboard_invoice_button`; it is empty without Pro.
5. Purchases shows the newest 50 orders, no paging.

## Verified locally

- All 13 request addresses above redirect as listed (checked as a student).
- Settings save: focus, country and two switches stored, `cas_notify_updated_at` written only when a flag changed; country written to the billing row; a student with no billing row gets one created (test row removed).
- Profile save through Tutor's handler.
- No console errors on Profile and Settings.
- 390px: no horizontal overflow on any tab.
- `php -l` clean, 11 contract tests pass (`test_dashboard_pages_contract.py` new, `test_dashboard_contract.py` updated for 7 panels).

## Not verified

- Tutor Pro's invoice button (Pro not installed locally): INFERRED to print in the Receipt cell because Tutor's own order card uses the same action.
- Live order history and live refunded order (issue mentions order 1 on the live site).
- Password reset email and avatar upload end to end.
- Email for the switches (#133).

Screenshots: `before-*.png`, `after-*.png`.
