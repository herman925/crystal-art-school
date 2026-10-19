# Paid course materials: private-delivery proposal

**Status:** proposal only. No production code or storage migration is included. Operator review required before implementation.

## Finding and boundary

The source review found two delivery patterns:

- Public course detail can render Tutor course attachments.
- The gated lesson page links to Tutor attachment URLs directly.

Tutor LMS 4.1.0 resolves those URLs with `wp_get_attachment_url()`. A page-level enrolment check cannot protect a static file after its URL is known. The current live file inventory and whether any private files are attached remain unknown.

Ordinary Gallery and marketing media must stay public. The site must distinguish public media from paid teaching materials at the attachment level. A lesson's `_is_preview` flag controls lesson access; it does not by itself make every attached file public.

## Recommended minimum design

1. **Classify every teaching attachment.** Default paid course and lesson files to `private`. Mark a file `public` or `preview` only when an owner-approved rule explicitly allows it. Keep Gallery images on WordPress's normal public media path.
2. **Keep private originals off public uploads.** Store originals outside the document root. Use private object storage only if private local storage is unavailable and access tokens can be validated server-side.
3. **Serve through one authenticated download route.** Accept an opaque attachment identifier, then resolve the file and its real course/lesson parent server-side. Do not trust a submitted course ID, file path, or visibility value.
4. **Check access for each request.** For private files, allow an enrolled student with Tutor content access or a verified content manager who can edit the specific owning course/lesson. Record actual per-object capabilities and ownership; do not infer authority from a role label. For public or preview files, apply the explicit per-file classification. A preview lesson must not grant access to its unclassified course or lesson attachments.
5. **Constrain file resolution.** Resolve only registered fixture/content IDs. Verify canonical path stays inside private storage. Reject traversal, symlinks outside that root, unknown attachment IDs, and non-file content.
6. **Return private responses.** Stream the file without exposing its storage URL. Use private, non-cacheable response headers and safe content disposition. Do not put access tokens in logs or reusable public URLs.
7. **Preserve public media behavior.** Gallery images and explicitly public preview assets keep their current public URLs. Do not blanket-protect `wp-content/uploads`; that would break public Gallery and normal site images.

The fixture records the editor's authorship and per-object `edit_post` checks for the course, lesson, and files. It also records `manage_options` and Tutor content access. The runner uses those measured facts; it does not treat the WordPress role name as authority. A standard Editor without relevant capability is only a diagnostic role, not a substitute for Crystal's planned content-manager role.


| Case | Expected access |
|---|---|
| Guest requests paid course/lesson file | Denied |
| Unenrolled student requests paid course/lesson file | Denied |
| Enrolled student or authorized Tutor administrator | Allowed for the owning course |
| Content editor with `edit_post` on the owning course/lesson or file | Allowed as an authorized content manager; record exact ownership and capability checks |
| Editor without authority on the owning course/lesson/file | Denied |
| Enrolled student requests paid file for that course | Allowed |
| User requests file attached to another course | Denied |
| Guest requests ordinary public Gallery image | Allowed |
| Guest opens explicitly marked preview lesson | Allowed |
| Guest requests attachment on preview lesson | Unknown until owner approves per-file policy; do not infer from lesson flag |

## Limits and trade-off

An enrolled student can save a file and redistribute its contents. Authenticated delivery prevents unauthenticated URL reuse; it cannot prevent an authorized learner from copying a downloaded resource. Watermarking or DRM would be separate scope.

Private storage changes affect only teaching materials with private classification. They must not change existing Gallery media, public marketing images, or explicitly approved free previews.
