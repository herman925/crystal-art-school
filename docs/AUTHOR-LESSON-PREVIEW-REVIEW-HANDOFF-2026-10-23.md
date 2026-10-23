# Author lesson preview — source-only Review handoff (2026-10-23)

**NOT runtime-verified, deployed or authorised for an owner switch.** Development changed source, prepared fixtures/tests and ran syntax checks only. Actual Review owns real HTTP/E2E/playback/Pro acceptance. Frozen #180/#181/#182/#185 and all mounts/environments remain unchanged.

## Dependency manifest

- This focused preview branch starts at exact sorting-fix **ce20893f1ae7e16d65f02b2bc7e0dda77ee55c0f** (PR #185).
- Its ancestry includes frozen builder **c9aaa8609ef78eb29349851fb05cf3025d5bb4a7** (#181), price/shared-writer **917a8d7196435246d23278b157c0deb50389730a** (#180), and main ed29780. This branch changes neither the builder/sorting nor price implementations.
- Independent private-file/provenance **813a0b747fd45f196de03a7dc3e595769f51ca3f** (#182) is **separately required for complete author materials/image/download acceptance**. It is NOT merged/bundled here. Without that handler's `cas_private_author_can_download`, the author stage works but author-only material lists fail closed (hide files), and the old direct-file admin/enrolled gate remains. Do not call that partial result full acceptance.
- Exact preview SHA is the commit containing this document / its PR head. Review must record the exact combined candidate and loaded/served hashes, not reuse evidence from a changed snapshot.

## Authority / entrypoints

1. `inc/cas-content-role.php`: `cas_content_author_lesson_course(lesson_id, CHECKED_user_id)` requires actual cas_content, that user's manage_cas_content, current real non-trashed `lesson → topics → courses` DB graph, CURRENT course author equal to checked user, and REAL `user_can(checked_user,'edit_post',course)` permission. No request course ID, lesson author or instructor metadata is authority.
2. Object-level `edit_post` / object-supplied `edit_tutor_lesson` for this role map only a valid lesson through its real owning course's caps. Missing/invalid lesson object, unrelated type, other author or broken graph returns do_not_allow. No stored primitive singular cap, role-version/cap-set change, global CPT remap or instructor/admin WP role is added. The nested cap request is for a DB-verified COURSES object, so it cannot recurse into the lesson branch. `user_can(other_user,...)` is checked against the target user, not ambient current_user.
3. `inc/cas-lesson.php`: separate author decision at OUR ordinary lesson gate and outline links. Student/public-preview paths retain Tutor's model; no global `tutor_course_content_access` override, fake enrolment/order, free/public/_is_preview mutation or teacher access toggle is used. A limited user's stale instructor link is not a substitute at our protected-lesson gate.
4. Draft outline statuses are included only when the CURRENT queried lesson proves author authority on that same course, so a legitimate draft preview has the correct current item/title/image. Guest/student outline queries stay published-only; WordPress's own draft-preview/object-cap checks are not bypassed or widened globally.
5. Author lesson responses set DONOTCACHEPAGE plus private/no-store/no-cache/max-age=0 and Vary Cookie before output. Actual served cache behaviour must be measured by Review.
6. Materials/photos reuse the same author gate, then each author-only file is filtered through #182's protected CURRENT-reference authority. Stage access never grants an arbitrary private-file URL, stale mapping or guessed attachment ID. Existing admin/enrolled material behaviour stays unchanged; guests on public previews still get no materials.
7. `single-lesson.php`: non-enrolled author preview does not run the stock student auto-complete-course write. Actual enrolled students retain the existing completion flow; completion controls/progress are not fabricated for the author. Existing Tutor before-content hooks remain, so Pro behaviour still needs actual Review.

## Source fingerprints (served/loaded parity NOT verified here)

- `inc/cas-content-role.php`: `f578d21a28761e2a4314abd1feeadf64c657c9004dd054cc447ab8ee30febb6e`
- `inc/cas-lesson.php`: `3922d10ee738aefdb86dd1cfd0511795f51a0b1a558699a78fd6e7aa67cc3602`
- `single-lesson.php`: `a4bd2e0cd62b79276e215625e563f94bc4870ff315dc5cfe575bbe77080ba69a`

## Prepared fixture / tests — NOT EXECUTED

- `tests/fixtures/cas-author-preview-fixture.php`: explicit LOCAL setup/cleanup with an existing administrator, `CAS_RUNTIME_TESTS=1` and exact acknowledged `CAS_PREVIEW_FIXTURE_HOME`; no old-stack default or production URL. Fresh cas_content owner/other, enrolled subscriber, non-enrolled subscriber, ordinary editor and metadata-only instructor subscriber. Owner deliberately has NO instructor metadata and NO enrolment.
- Published paid own course + published NON-preview lesson authored by admin; own draft course/topic/lesson for a genuine WordPress preview; foreign course whose lesson is authored by OWNER (lesson-author-alone negative); orphan lesson; unrelated closed page; one real synthetic enrolled-student control; private PDF/reference PNG and foreign private file. Inert same-site blank.gif iframe markers prove stage output only, **not video playback**.
- Dynamic IDs/passwords/source hashes are emitted only when Review executes setup. Capture JSON mode 600. Ledger cleanup checks exact identities and removes only its own posts/users/attachment files/empty upload directory. No fixtures were created in this Development continuation.
- `tests/test_author_lesson_preview_runtime.py`: requires explicit Review container + captured fixture JSON. Prepares target-user/ambient-user permutations, no-argument/global singular-cap denial, invalid/unrelated/foreign/broken objects, missing target custom/course-edit caps, own published/draft preview, private headers, guest draft non-disclosure, forged request-course ID, metadata-only instructor/ordinary editor/other author negatives, enrolled/public-preview controls, separate-file dependency/direct URL checks, and trash at every graph level.
- It snapshots enrolment rows, synthetic-user orders/totals, public/free/preview/price flags, completed-course comments and completed-lesson metadata before/after views. No teacher enrolment or fake payment order is seeded. Existing preview-material contract is updated for the explicitly approved narrow author exception, preserving the student/guest baseline.

## Mandatory Review limits / controls

- No browser, HTTP, runtime fixture/test, Docker/stack, build/pull, production, migration or account-switch action was run by Dev. PHP/Python syntax is not acceptance; source/served parity is still unverified.
- Use a legitimate owner-session WordPress draft-preview link, including the real preview nonce if the installed editor/Pro supplies one. Do not disable nonce/status checks to make a fixture pass. Verify a guest/other actor cannot see draft title/body/stage via a copied link, preview flag or supplied course ID.
- Native Tutor/Pro before-content hooks, content-drip, player/signing, referer/token enforcement, real Bunny playback, seek/resume, media picker and browser screenshots remain unverified. This is a local custom-stage exception, not a global override of native instructor/student/addon policies. Existing native instructor permissions, if separately enabled, must be distinguished from this new author grant in differential tests.
- Verify non-enrolled author view cannot mark course/lesson complete even when historic completion data and the native auto-complete option would otherwise qualify. Do not change global options or seed progress outside the explicit synthetic Review plan. The new template condition skips that native write for the author-only path.
- Full files/photos acceptance requires separate #182 plus trusted current provenance, not merely this stage response. Missing dependency is a fail-closed partial test, not a pass or reason to broaden enrolled/instructor permissions.
- No guide labels/publication are updated here. Final combined release, detailed money guide, fresh manifests, actual Review sign-off and separate final owner OK still gate any switch.
