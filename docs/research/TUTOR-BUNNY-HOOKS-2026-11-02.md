# Tutor 4.1.3: lesson uploader integration hooks

Inspected 2026-11-02 using read-only static inspection of the cached official Tutor 4.1.3 package. No target PHP/JavaScript was executed. Findings apply to this package and need browser verification against the deployed Tutor version.

## Frontend extension contract

- `Tutor.CourseBuilder.Curriculum.Lesson.registerField(slot, definition)` and `registerContent(slot, { component, priority })` exist. Lesson slots are `after_description` and `bottom_of_sidebar`; registration sorts each collection by ascending priority, default 10. The provider assigns `window.Tutor` inside a React effect and emits no readiness event. Feature detection after the builder mounts is necessary. [Source: main bundle, `CourseBuilderSlotContext.tsx`, module 79100, character 404882 onward][main]
- `registerContent` expects an already constructed React element. `CourseBuilderSlot` sends only `component` to `ContentRenderer`, which renders it unchanged inside an error boundary; it supplies no lesson ID, form instance, or callback. The lesson modal does not provide its own React Hook Form `FormProvider`. A separately imported `useFormContext` therefore does not offer a supported lesson-form bridge. [Source: curriculum bundle, `ContentRenderer.tsx` at character 107026, `CourseBuilderSlot.tsx` at 111268, `LessonModal.tsx` at 179681][curriculum]
- `FieldRenderer` supports the documented fixed field types, including text, number, password, textarea, select, radio, checkbox, switch, date, time, image, video, uploader and WPEditor. It does not forward arbitrary custom-renderer or `isHidden` properties. A `type: hidden` field is unsupported. `rules` is forwarded to React Hook Form's `Controller`. The underlying text input retains the registered field name. [Source: `FieldRenderer.tsx`, character 108577 onward][curriculum]; [official field documentation](https://docs.themeum.com/tutor-lms/developer-documentation/register-custom-fields/).

**Implementation inference:** a content-slot uploader plus registered text fields carrying opaque string tokens is feasible. Updating those inputs by their owned `name`, using the native input value setter and a bubbling input event, bridges to Tutor's existing controlled `onChange`. This event bridge and visually hiding owned text-field wrappers are compatibility adaptations, not a documented form API; verify them in-browser. There is no better public lesson-form setter in the inspected extension API. Use `rules.validate` to prevent submission while upload/token preparation is incomplete.

## Payload and initial values

- `findSlotFields({fields: G.Curriculum.Lesson})` collects all registered names from both lesson slots. `convertLessonDataToPayload` appends `Object.fromEntries(names.map(name => [name, formData[name] || '']))`. Truthy token strings survive unchanged; falsy values become empty strings. Registration alone does not save custom metadata: the backend extension must read and validate those POST fields. [Source: main module 33231, character 620267][main]; [curriculum module 24628, converter at character 777996][curriculum].
- An existing lesson loads via `tutor_lesson_details` only when both lesson/topic IDs are truthy. The modal resets registered names from `response[name]`. Add their default values with `tutor_lesson_details_response($data, $lesson_id)`. A new lesson has local defaults and no details request, so the custom uploader must initialize its own new-edit state. [Source: lesson modal at character 180926 and query at 782190][curriculum]; [LessonModel.php, `get_lesson_details`, lines 176–211][model].
- The converter always emits runtime fields, while video source fields are emitted only when its `video` form value is truthy. The backend native saver updates `_video` only for a truthy source, or deletes it for source `-1`. A late commit must overwrite canonical managed video metadata after this native saver. [Source: converter][curriculum]; [Lesson.php, lines 327–355][lesson].

## Hide native Video and playback duration

Use `tutor_localize_data` at a late filter priority, scoped to the managed current user, preserving all other existing settings:

```php
$data['visibility_control']['course_builder']['curriculum_lesson_video_instructor'] = 'off';
$data['visibility_control']['course_builder']['curriculum_lesson_video_playback_time_instructor'] = 'off';
```

Tutor maps a user containing the `administrator` role to suffix `admin`; every other role maps to `instructor`. Missing keys default to visible; only `on` is visible when a key exists. This hides the native controls through their own render logic, including the duration wrapper. It requires no generated CSS class or translated label selector. [Source: `useVisibilityControl.tsx`, main module 78988, character 580423; visibility constants in module 19393][main]. The filtered data is localized onto `tutor-course-builder` by [Assets.php, lines 490–497][assets]. [Official Advanced settings documentation](https://docs.themeum.com/tutor-lms/settings/advanced/) confirms native Admin/Instructor visibility controls.

## Save ordering and commit points

`save_lesson_meta` is registered on `save_post_{lesson_post_type}` at priority 10. Both `wp_insert_post` and `wp_update_post` run that native saver before the following Tutor actions:

| Operation | Post-native-meta action | Arguments |
| --- | --- | --- |
| Create | `tutor/lesson/created` | new lesson ID |
| Update | `tutor/lesson_update/after` | existing lesson ID |

Both actions run before Tutor sends the success JSON. These are the direct commit points for assigning a completed upload and restoring canonical `_video`/runtime. `tutor/lesson_update/before` runs before `wp_update_post`, but there is no corresponding create-before action. Preflight authorization/token validation should therefore run on `wp_ajax_tutor_save_lesson` before Tutor's priority-10 callback; it must reject invalid requests before native post/meta changes. The after actions are ordering hooks, not a database transaction guarantee; Tutor's update path does not inspect the return value of `wp_update_post`. [Source: Lesson.php, constructor lines 78–82 and save handler lines 407–520][lesson].

[main]: /tmp/review-180-181.p7l6Ue/vendor-tutor-4.1.3/tutor/assets/js/tutor-course-builder.js
[curriculum]: /tmp/review-180-181.p7l6Ue/vendor-tutor-4.1.3/tutor/assets/js/lazy-chunks/tutor-course-builder-curriculum.js
[lesson]: /tmp/review-180-181.p7l6Ue/vendor-tutor-4.1.3/tutor/classes/Lesson.php
[model]: /tmp/review-180-181.p7l6Ue/vendor-tutor-4.1.3/tutor/models/LessonModel.php
[assets]: /tmp/review-180-181.p7l6Ue/vendor-tutor-4.1.3/tutor/classes/Assets.php

## Bunny REST video readiness: separate enum from webhooks

Verified against the **live public Stream OpenAPI**, `components.schemas.VideoModelStatus`, on 2026-11-02:

| REST `status` | Meaning |
| --- | --- |
| 0 | Created |
| 1 | Uploaded |
| 2 | Processing |
| 3 | Transcoding |
| 4 | Finished |
| 5 | Error |
| 6 | UploadFailed |
| 7 | JitSegmenting |
| 8 | JitPlaylistsCreated |

These are **GET Video response states**, not webhook event codes. In particular, REST `3` is still transcoding and terminal REST failures are `5` and `6`. [Primary source: live Stream OpenAPI](https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json); [official Get Video reference](https://bunny.net/docs/api-reference/stream/manage-videos/get-video).

The schema defines `length` in seconds; `availableResolutions` lists completed, playable encoded resolutions; `outputCodecs` lists generated codecs; and `encodeProgress` reaches 100 when transcoding finishes. **Conservative implementation inference:** accept a completed standard upload only at status `4`, with positive duration and nonempty playable resolutions/codecs; keep other nonfailure states pending. Do not treat upload completion, thumbnail generation, or status `3` as completion. This deliberately waits for finished encoding. [Source: live OpenAPI `VideoModel`](https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json).

For intentional early/JIT support, GET `/library/{libraryId}/videos/{videoId}/play` provides `isPlaylistPlayable` and `isPlayable`: the latter can also mean original-source playback. Prefer these explicit readiness booleans over inventing readiness rules for statuses `7`/`8`; the enum names alone do not guarantee encoded output. This endpoint requires a signed `token`/`expires` pair when embed token authentication is enabled. `jitEncodingEnabled: null` inherits the library default. [Source: live OpenAPI `VideoPlayDataModel` and `Video_GetVideoPlayData`](https://video.bunnycdn.com/openapi/bunnynet-video-api.public.json). Bunny documents JIT as on-demand segment preparation supported by its own player, with TUS processing potentially waiting for the entire upload. [Official Premium Encoding/JIT guide](https://bunny.net/docs/stream/premium-encoding).
