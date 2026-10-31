# Published Sign out fix — source reconciliation, 2026-10-31

The navigation configuration now records the correction already published to the guide:
`target: '_self'` gives Sign out a same-tab document navigation to the existing logout
endpoint, and `noIcon: true` preserves its intended appearance.

The reconciled `site/.vitepress/config.mjs` is byte-identical to the preserved local source
identified in [handoff #211](https://github.com/herman925/crystal-art-school/issues/211):
SHA256 `ab9655fc1cfd032ef7d0a3f71f360c1337a0822f804558d2db65f10dcef61040`.
Only the Sign out navigation entry and its explanation differ from main.

Publication was previously accepted for 22 existing HTML pages, including the embedded
configuration on the 404 page. This reconciliation performs no publication and makes no
new live-session claim. Real-reader logout, protected-page denial, navigation and search
remain pending until testing resumes.

The original checkout's historical helpers and private receipts remain untouched. Accepted
overlay r6 SHA256: `b8352649954b1557659583d75136621420273a72a216b5bfe73e0b9bedfb12a4`;
accepted invocation SHA256: `a21545291ce3cec307be74c80b7817028d4bf28490c49f5c079cf719ba0cffb1`.
They were read for source identity only; no helper was executed or rewritten.

Do not use `deploy.sh` or publish the full build for this reconciliation. The six local
money/access draft pages were absent from the accepted deployed inventory. Authentication
and server files, generated output, and guide content are outside this source delta.
