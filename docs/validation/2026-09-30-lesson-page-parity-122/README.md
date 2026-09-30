# Lesson page parity (#122)

The lesson page was never ported. Tutor 4.1.0 showed its own dark navy learning area, with its own
onboarding tour modal on a student's first visit. This port renders the lesson in the structure of
`lesson.dc.html` and switches the tour off.

Screenshots (local WordPress with Tutor 4.1.0 and seeded data; the video host answers 404 there
because the seeded film ids are not real):

| File | What it shows |
|---|---|
| `prototype-desktop.jpg` | `lesson.dc.html?c=clouds-intermediate&u=1` at 1440px |
| `port-desktop.jpg` | The port, lesson 2 of 4, two reference photos, course materials |
| `port-desktop-course-complete.jpg` | The port once every lesson is done (finish card) |
| `port-desktop-native-player.jpg` | A lesson whose film is a YouTube link keeps Tutor's own player in the stage |
| `port-mobile-phases.jpg` | 390px, a course with two phases |

## How it is wired

`inc/cas-lesson.php` claims the ordinary lesson view through `template_include` at priority 999,
the route `inc/cas-course-single.php` already takes for the single course. It does not fork Tutor's
`learning-area/index.php`: that file also serves quizzes, quiz attempts and the course sub-pages,
and a forked copy would go stale with every Tutor release. A visitor Tutor would refuse, a
`?subpage=` request, or any other post type falls through to Tutor untouched.

Tutor's core and learning-area stylesheets and scripts are dequeued on this route only. Tutor's core sheet sets `body { background-color: var(--tutor-surface-base) }`, a navy ground in dark mode that painted over the site's warm charcoal; it is not a local-only effect. A quiz or any other learning-area screen still gets Tutor's assets.

Access is Tutor's own `CourseModel::has_course_content_access()`. Completion is Tutor's own form
post (`tutor_action=tutor_complete_lesson`, with its nonce); a following hook moves on to the next
lesson, only after Tutor's handler has finished.

## Prototype element to Tutor data

| Prototype | Bound to |
|---|---|
| Crumbs, course link | course archive, course permalink, course title |
| Meta row: Media, Level, Lessons, Running time | first course category, `_tutor_course_level`, lesson count, the sum of the lesson runtimes the phase list shows (written like the prototype's `runtimeStr()`, for example `~1h 8m`; left out when no lesson has a runtime) |
| Stage poster | the lesson's featured image |
| Stage player | the film's address from the lesson's `_video` embed, passed through `cas_video_sign_url()`; other sources keep Tutor's player |
| Lesson flag | "Lesson N — title" from the lesson order and title |
| Course description, cover | course post content; first of `cas_course_photos()` |
| Phase eyebrow, lesson title, sub-line | topic title (only when the course has more than one topic), lesson title, lesson post content |
| Mark complete & continue | Tutor's completion form |
| Reference photos | the lesson's image attachments |
| Course materials | the course's attachments plus the lesson's non-image attachments; the whole card is hidden when there are none |
| Your progress, n of N, bar | `get_course_completed_percent()` counts |
| Phase list rows | topics and lessons, with featured image, runtime, done and current state |

## Left out, and why

| Prototype element | Reason |
|---|---|
| "Taught by Crystal Lau" | hard-coded in the prototype; no Tutor value holds it |
| Teach-card quote, "Share your skies" band | demo copy; none of it is supplied wording |
| Finish card heading and blurb, "Join the community", "Watch again" | heading is demo copy; the other two need a community and a progress reset the site does not have |
| Restart course | resets browser storage in the prototype; Tutor progress is server-side and its retake feature is off |
| Course films card, multi-part lessons | Tutor holds no data for them |
| Portrait pillarboxing of the film | Tutor stores no orientation |
| Source notes about Google Drive | not applicable to the video host |
| "Course materials ... still to be supplied" sentence | owner-facing placeholder; the card is hidden instead |

## One deviation from the prototype

The prototype's odd-row stripe (`.unit-row[data-odd="1"]`) resets `background`, which removes the oak
grain of `.unit-row.current` while the dark title colour stays. On the prototype the current lesson
is unreadable whenever its number is even (see lesson 2 in `prototype-desktop.jpg`). The port keeps
the oak ground on the current row.

## Tour

`tutor/shared/tour.php` is an empty override (Tutor resolves it through `tutor_get_template`, checked
locally: the path is the child theme's and the output is 0 bytes). New students also get
`_tutor_tour_completed` set in `cas_account_skip_tutor_tour()` on `tutor_new_user_registered`.
The tour is not new in 4.1.0; it is present in 4.0.7.

## Local checks

| Check | Result |
|---|---|
| `php -l` on every PHP file changed | no syntax errors |
| Enrolled student, 4-lesson course | page renders; progress 1 of 4; Mark complete & continue recorded lesson 2 and redirected to lesson 3 (progress 2 of 4) |
| Every lesson done | finish card shows; last lesson's button reads "Mark complete & finish" |
| Course with two topics | "Phase 1 — …", "Phase 2 — …" headings and notes eyebrow |
| Signed-out visitor, free preview lesson | renders; no progress card, no complete button; the other rows are not links |
| Signed-out visitor, paid lesson | Tutor's sign-in screen |
| `?subpage=course-info` | Tutor's own page |
| Lesson with a YouTube source | Tutor's player fills the stage |
| Tour override | child template path, 0 bytes of output |
| Page ground | `body` is `rgb(33, 30, 26)` (warm charcoal); no Tutor stylesheet is loaded on the route |
| Running time | 6:11 + 3:00 + 7:12 + 8:13 + 9:14 shows `~34m` |
| `tests/test_lesson_page_contract.py` | passes; fails when the signed-address call is removed |
| Existing contract tests | all pass |

Not checked here: the live video host, the live header (the local site has no menu), and Tutor Pro
add-ons (not installed locally).
