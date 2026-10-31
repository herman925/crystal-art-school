# Handoff — Crystal Art School WordPress build

## Takeover source checkpoint — 2026-10-31

The [dated handoff #211](https://github.com/herman925/crystal-art-school/issues/211),
[whole-site map #105](https://github.com/herman925/crystal-art-school/issues/105) and
[payment map #104](https://github.com/herman925/crystal-art-school/issues/104) supersede
conflicting historical state below. Closing #211 completed the handoff record, not the website.
The new assignment resumes source implementation only. All tests, deployments, runtime and
account operations remain suspended; incident holds and preserved evidence remain in force.

Last reported acceptance coverage remains **63/110**. It is not build completion; no build-only
percentage has been calculated.

| Classification | Current evidence and next action |
|---|---|
| Source implementation prepared, untested | `takeover/client-editing-20261031` now composes the existing PR #201 controls into five page editors covering the same 42 fields, with combined Home/About/Site details saves and related native lists. See [source scope and deferred checks](ROUTE-B-SOURCE-2026-10-31.md). Route B remains unaccepted and undeployed. Original PR #201 at `45996731a30c640b41ada90c9ecc62e260a4e92c`, guide #203 and log runbook #204 remain untouched/open. |
| Source correction committed, untested | Local commit `01185f2`, based on main `2e8bc45225011af9f40c360b962e1b6cbc089aa3`: lesson description uses current lesson content once, with its own cover and no course text/photo fallback. Lesson guide source corrected; nothing deployed or published. |
| Published fix, source reconciled | The 22-page guide Sign out overlay is already published. Local commit `0f32f88` records its exact navigation config source; see [source reconciliation](../docs-site/SIGNOUT-SOURCE-20261031.md). Original checkout's modified config, helpers and private evidence remain untouched. No rebuild or publication. |
| Built, outstanding verification | Native authoring/media/five-photo workflows, enrolled progress/completion, recovery, provider checkout/webhook/receipt and final guide-reader behaviour. Keep the detailed pending list in #211; do not classify each as missing code. |
| Client/owner/access blocked | Approved copy/prices/materials/Etsy destinations, adviser legal/VAT/consent, settlement evidence, deferred inbox proof, human/device checks, ownership handover and explicit launch approval. Keep incident-held operations stopped. |

The lesson correction follows the written Design Direction §1.4.9 and §8.1, which expressly
require `lesson_description` and the lesson cover. The prototype's course-description comment
and the August register's cancelled-field/course-blurb entries conflict with those revisions.
Use the existing lesson `post_content` and featured image, without adding a field. Remove the
second rendering of lesson content under the lesson heading. Preserve sanitization, layout,
Tutor access and completion handling. Native cover editing remains unverified.

The written design was read in place at
`/home/herman925/workspace/SafeMode/Crystal Education Project/01 - Meeting Notes/20260803/20260804_Design_Direction.md`
(§1.4.9 lines 198–220; §8.1 lines 818–837). The older scratch candidate and its evidence
were read only, not reused or changed. This checkpoint records source work, not acceptance.

Wayfinder publication: the GitHub connector rejected the attempted #105 status update with
`403 Resource not accessible by integration`. The remote map was not updated; this local
checkpoint is the pending update. No alternative credential or access route was attempted.

---

Written 2026-08-23; current-state update 2026-08-30. This is the "read this first" document for anyone picking the build up.
`CLAUDE.md` in the project root is still the authoritative decision log; this file is the
current-state snapshot that log does not give you.

---

## 1. What exists right now

The site is live at **https://crystalau.com** on Hostinger, with `noindex` set site-wide.
It is not public in any meaningful sense, but it is a real server with real DNS.

Signup is closed since 2026-09-30, for security. The cause and the recovery are in `docs/incidents/2026-09-21-tutor-object-injection.md`. Do not reopen signup without map #99 (issues #99 to #102).

| Layer | State |
|---|---|
| Hosting | Hostinger Business, order `1009902478`, UK datacenter (`server660`), PHP 8.3.30 |
| WordPress | 7.1.2, child theme **Crystal Art School** on `hello-elementor` |
| Design system | The prototype's `industrial-1/2/3.css` (291KB) enqueued wholesale |
| Elementor Pro | licensed to 2027-08-22, subscription `A-S03296618` |
| Tutor LMS Pro | licensed to 2027-08-22, native eCommerce, cart enabled. Tutor and Tutor Pro 4.1.0, tutor-stripe 1.0.9 (Stripe Checkout Sessions). Base currency GBP (Herman's decision, 2026-09-30). Gateways are in TEST mode only: Stripe test keys and PayPal sandbox. The live Stripe keys in `.env` are not used for tests |
| Video | Bunny Stream library `734616`, 22 lesson films + 1 highlights clip = 23 items (count reported by the Operator 2026-09-30, not re-measured here), UK+SG replicated. The 12 free films on the Free Videos page are YouTube links, not items in this library (live page 2026-09-30: 12 distinct YouTube ids, 0 video-host links) |
| Email | `hello@crystalau.com`, a Hostinger Email mailbox (Starter Business trial to 2027-09-29, 100 sends a day, 100 recipients a message). WordPress sends through SMTP (`smtp.hostinger.com:465`) with a `phpmailer_init` hook in `wp-content/mu-plugins/cas-audit-log.php`. The credentials are `CAS_SMTP_USER` and `CAS_SMTP_PASS` in wp-config. SPF, DKIM and DMARC header check: not measured yet |
| Security | `cas-audit-log.php` (mu-plugin) writes `~/cas-logs/audit.log`, emails Herman when any user gains the administrator role, and checks the admin count daily. `mu-plugins` is chmod 555 (lift it only to deploy, then restore). `uploads/.htaccess` denies PHP |
| Built pages | Home, Courses (archive + single, with the 5-photo strip), About, FAQ, Gallery, Showcase, Blog (list + article), Free Videos, Log in, Sign up, Dashboard, Basket, Checkout, the five archive surfaces, header, footer |
| Courses | 4 imported, published by the owner (confirmed 2026-10-20) (Corrected 2026-10-20 (owner confirmed publication); previous wording: "4 imported as **draft**"), 22 lessons, covers attached, 2 free preview lessons. Plus a private test course 269 (topic 270, lesson 271) for payment tests: delete it after testing |

Built on **2026-08-23**, first pass: Showcase, Blog and Free Videos, plus the Courses page's
filter and sort rail and its THREE WAYS TO STUDY tier block (**the tier block is now obsolete —
see §6b**). About's prose and photographs were
opened to editing in the same pass. Each new page is a `inc/cas-*.php` shortcode on a plain WP
Page, except the blog article, which is the site's one Elementor Theme Builder content template
(id 205, condition `singular/post`).

Those three pages still render their empty state, because no content has been supplied: zero
showcase pieces, zero free videos, and only WordPress's stock `hello-world` post.

Built on **2026-08-23**, second pass — **the homepage, the account surfaces and the archives**:

| Block | Module | Renders today? |
|---|---|---|
| §3.3 Free videos promo card | `inc/cas-home-free-videos.php` | Yes |
| §3.4 About Crystal card | `inc/cas-home-about.php` | Yes — closes register defect A1 |
| §3.5 Shop all courses + course rail | `inc/cas-home-courses.php` | Rail: No — 0 published courses (Corrected 2026-10-20 (owner confirmed publication): the four courses are now published; rendering not re-checked; previous wording: "Rail: No — 0 published courses."). The Course overview tail (below) renders her copy regardless |
| §3.5 Course overview block | `inc/cas-home-course-overview.php` | **Yes** — two ledes, two principles, four questions (built 2026-08-25) |
| §3.6 Art Gallery card | `inc/cas-home-gallery.php` | No — 0 published artworks |
| §3.7 Blog & Tools + blog rail | `inc/cas-home-blog.php` | No — no post has a featured image |
| §5 New release row + carousel | `inc/cas-home-release.php` + `inc/cas-home-releases.php` | Row: Yes. Carousel: No — the four courses are now published by the owner (confirmed 2026-10-20); rendering not re-checked (built 2026-08-25) (Corrected 2026-10-20 (owner confirmed publication); previous wording: "Carousel: No — every course is a draft (built 2026-08-25, renders nothing until one is published)") |
| Styled 404 | `404.php` + `inc/cas-404.php` | **Yes** (built 2026-08-25) |
| Log in, Sign up, Dashboard, Basket, Checkout | `inc/cas-account.php` | Yes |
| Category, tag, date, author, search | `inc/cas-archives.php` | Yes |
| Live region + site-wide scroll reveal | `js/cas-site-extras.js` | Yes, on every page |

Homepage order, read from the chapter: `[cas_hero]`, `[cas_home_release_row]`,
`[cas_home_courses]`, `[cas_home_blog]`, `[cas_community_band]`, `[cas_testimonials]`,
`[cas_social_strip]`. Three of those return **zero bytes** today rather than a heading over a
blank grid — measured, not assumed. The homepage as served is hero, the two-card New release
row, community band and social strip.

`/login/` is a new WP Page (id 237) holding `[cas_login]`; the header's Log in and Sign up
buttons now point at it and at Tutor's registration page instead of `wp-login.php`. The other
four account surfaces adopt themselves through Tutor's own hooks — do not place their
shortcodes on Tutor's pages.

`js/cas-site-extras.js` closed a real site-wide defect: `window.CasAnnounce` was called by
three shipped modules and defined by none, so every screen-reader announcement on the site was
silently dropped, and `.cas-sr-live` had no CSS rule anywhere. Both are fixed. Note the cost:
the same file generalised the prototype's *fallback* scroll-reveal driver site-wide, and the
prototype only ever did that on one page. Three pages (About, Showcase, Free Videos) still
carry their own reveal one-liner and are unaffected — those three are the ones still matching
the prototype, so do not "tidy" their one-liners away without settling this first.

Built on **2026-08-25**, third pass — **the whole §6a fix list, plus everything the first two
passes left unbuilt** (tracked as GitHub issues #23-31, merged via PR #41, deployed to the
VPS same day; `php -l` clean on all ten PHP modules in place):

| Block | Module | Renders today? |
|---|---|---|
| Header auth state | `inc/cas-header.php` | **Yes** — LOG IN only while signed out and registration is closed; SIGN UP hidden |
| Account surfaces | `inc/cas-account.php` | **Yes** — cart contrast fixed, dashboard JS error gone, `<main>` landmark added |
| §5 New release carousel | `inc/cas-home-releases.php` + `js/cas-home-release.js` | No — the four courses are now published by the owner (confirmed 2026-10-20); rendering not re-checked (Corrected 2026-10-20 (owner confirmed publication); previous wording: "No — every course is a draft (by design; no heading over an empty rail)") |
| §3.5 Course overview block | `inc/cas-home-course-overview.php` | **Yes** — her two ledes, two numbered principles, four self-answered questions; the lifetime-access sentence withheld per §7.4 |
| Archive headings | `inc/cas-archives.php` | **Yes** — author `<h1>` shows the display name, search truncates the raw query |
| Styled 404 | `404.php` + `inc/cas-404.php` | **Yes** — the last stock hello-elementor page is gone |
| About reverse-jump | `js/cas-site-extras.js` | **Yes** — 16px jump closed |
| Courses hero + FREE TRIAL | (built earlier, verified) | **Yes** |
| Chalk cursor + dust motes | `js/cas-site-extras.js` | **Yes** |

The four courses are now published by the owner (confirmed 2026-10-20), so the course rail and the carousel must be re-checked; the Art
Gallery card and the blog rail still render nothing — that is the intended empty state, not
a regression. (Corrected 2026-10-20 (owner confirmed publication); previous wording, line breaks shown as spaces: "The four course drafts are still unpublished, so the course rail, the carousel, the Art Gallery card and the blog rail still render nothing — that is the intended empty state, not a regression.")

### Still not built — corrected 2026-08-25 by the parity audit

**This section previously read "Nothing. The fix list is closed." That was true of the §6a fix
list and false of the port as a whole.** A thirteen-page read-only parity audit against the
prototype (one agent per page, 2026-08-25) found **63 unbuilt items and 67 content-gated
non-defects**. The index is **[issue #55](https://github.com/herman925/crystal-art-school/issues/55)**
— read that first; it carries the full table, the cross-cutting fixes and the ordered
worklist. One issue per page: #48 Home · #51 Courses · #44 Gallery · #49 Showcase ·
#47 Free Videos · #52 Blog list · #46 Blog post · #54 About · #42 FAQ · #43 Log in ·
#45 Sign up · #53 Dashboard · #50 Checkout.

| Page | Issue | Unbuilt | Content-gated | Every block present? |
|---|---|---:|---:|:---:|
| Dashboard | #53 | 11 | 6 | No |
| Courses | #51 | 7 | 6 | No |
| Home | #48 | 5 | 5 | Yes |
| Gallery | #44 | 5 | 7 | No |
| Blog post | #46 | 5 | 6 | No |
| Blog list | #52 | 5 | 8 | No |
| FAQ | #42 | 5 | 1 | No |
| Log in | #43 | 5 | 3 | No |
| Checkout | #50 | 5 | 7 | No |
| Sign up | #45 | 4 | 3 | No |
| About | #54 | 2 | 3 | No |
| Free Videos | #47 | 2 | 7 | Yes |
| Showcase | #49 | 2 | 5 | Yes |
| **Total** | | **63** | **67** | **3 / 13** |

**The distinction that makes this list usable.** 67 of the 130 findings are *content-gated*:
the code exists and renders nothing where nothing is published — the four courses are now published by the owner (confirmed 2026-10-20; rendering not re-checked) (Corrected 2026-10-20 (owner confirmed publication); previous wording: "the code exists and correctly renders nothing because nothing is published — 4 draft courses,"),
0 artworks, 0 showcase pieces, 12 free films (YouTube links, PR #98), 1 stock post, `users_can_register = 0`, zero
orders. **They are not defects and must not be re-reported as missing work.** Each agent proved
its set by rendering server-side over `wp eval-file` with in-process filters and
`do_shortcode()`, writing nothing. A class-name diff cannot tell the two apart, and this project
has already spent afternoons on that mistake.

**The four largest real gaps**, in the order a visitor meets them:

1. **All three hero "View courses" buttons link to the homepage.** `inc/cas-hero.php:142-143`
   calls `get_page_by_path('courses')`, which is permanently NULL because Tutor owns the slug.
   `cas_page_url()` already solves this (`cas-footer.php:62-73`); the hero bypasses it. One line.
2. **Four `/dashboard/account/*` sub-pages render with no header, footer or hero at all.**
   Tutor's `account.php` prints its own document and never fires `tutor_dashboard/before|after/wrap`.
   Same root cause loses the checkout confirmation card (#50).
3. **A signed-in member has no way to reach the dashboard or log out.** `cas-header.php:87-92`
   hides LOG IN / SIGN UP when signed in and puts nothing back; the homepage has zero hrefs
   matching "dashboard".
4. **The dashboard's six-panel studio is not built** — Tutor's stock sidebar stands in. All 39
   dashboard CSS classes already ship in `industrial-*.css`, so this is markup, not styling.

**Four site-wide one-liners** account for a large share of the per-page counts and should be
fixed once, not thirteen times: `.glow-layer` is emitted on **no page** while
`industrial-1.css:324` sets `--glow-op:1` under the default theme D (verified: `grep -c
glow-layer` on the live homepage returns 0); **no page has a meta description** and the title is
WordPress's default (verified: 0, and no SEO plugin is active); Tutor's `reset.css`
`white-space:nowrap` on `<button>` (see §4); and `wp_img_tag_add_auto_sizes` suppressed in five
render functions but not in `cas_render_blog_post()`.

What remains open with the client is in §7 and is unchanged.

---

## 2. Access

Nothing secret is in this file. Everything lives in `.secrets/.env` in the project root —
outside the git repo, and the only readable copy. See the `client-credentials-locations`
memory for the full picture.

- **The client site guide** is live at **https://docs.crystalau.com** — VitePress, behind a
  real login page with PHP sessions. Source is `docs-site/`: Markdown in `site/`, theme and
  sidebar in `site/.vitepress/config.mjs`, the login layer in `server/`. Publish with
  `bash docs-site/deploy.sh` — it builds in a temp directory (npm cannot install onto Google
  Drive; the sync client writes package files in pieces and npm reports "Invalid package
  config"), then uploads.
- **Nothing is served straight off disk.** The built site sits in `_private/`, denied by its
  own `.htaccess`, and every request goes through `gate.php`, which redirects a signed-out
  visitor to `/login.php`. Pages, stylesheets and the search index are all behind the same
  door. Logins are bcrypt hashes in `server/users.php` (chmod 600, never web-readable); the
  plain passwords are in `.secrets/.env` under `DOCS_AUTH_*`.
  These two logins were rotated and deployed on 2026-09-30 (they were not in the first rotation).
- **The editability register is deliberately NOT on that site.** It carries server paths,
  dev-day costings and the Phase 2 scope argument. The guide is client-facing only; the
  register stays in `docs/`.
- **SSH** to the server is by key: `.secrets/hostinger_ed25519`, port 65002, user
  `u202887698`, host `82.29.185.202`. This is how essentially all the work was done —
  WP-CLI over SSH, not the admin UI.
- **WordPress** has two administrators: `cas_dev` (developer) and `cas_owner` (Crystal).
  `cas_dev`'s display name is **"Herman"**, so a browser signed in as the developer account
  looks like Crystal's. That hid a real bug for a whole session. `cas_owner`'s display name
  is literally `cas_owner`.
- **REST API** is available: Application Password `claude-code-tooling` on `cas_dev`, in
  `.secrets/.env` as `WORDPRESS_APP_PASSWORD*`. Use it for media uploads; structural work
  still needs `wp eval` over SSH. See the tooling entry in `CLAUDE.md`.
- **Bunny**, **Namecheap**, **Elementor**, **Tutor** logins are all in the same `.env`.
- **Rotated 2026-09-30** (see the incident log, section 5): both WordPress admin passwords, the app password, the Tutor API key, the Bunny library key and pull-zone key, the DB password and the WordPress salts. `.secrets/.env.bak-2026-09-30` and `.secrets/.env.bak-H-2026-09-30` hold the old values. Keys added to `.env` on 2026-09-30: `HOSTINGER_MAIL`, `HOSTINGER_MAILPASSWORD`, `STRIPE_TEST_*` (including `STRIPE_TEST_WEBHOOK_SECRET`), `PAYPAL_CLIENTID`, `PAYPAL_SECRET` (sandbox), and `CAS_PAYTEST_STUDENT_*` (temporary: remove at cleanup). `wp plugin update tutor-pro` prints the licence key in its output; never paste that output.

- **Password reset has no web fallback, even for admins.** Core `wp-login.php?action=lostpassword`
  redirects to Tutor's `/dashboard/retrieve-password` (PR #156). If that page fails, nobody can
  request a reset from the web. **Break-glass, over SSH:** `read -rs NEWPASS` (prompts; nothing is
  echoed), then `wp user update <user> --user_pass="$NEWPASS"` with the same `--path` and
  `--skip-plugins --skip-themes` as the other WP-CLI calls. Never type a password literal into a
  command or print one.

Read the key names before asking anyone for a credential. On 2026-08-22 a request went to
Herman for Namecheap access that had been sitting in that file the whole time.

---

## 3. Architecture decisions worth knowing before you change anything

**The theme is template parts, not Elementor widgets.** Header, footer, hero and every page
are PHP in `inc/cas-*.php`, exposed as shortcodes, rendered through Elementor Theme Builder
templates that contain a single shortcode widget. This was chosen deliberately: the
prototype's CSS is written against specific markup, and reproducing that markup with
Elementor's native widgets would have meant rewriting a large share of working,
breakpoint-tested CSS. Menu items remain editable in WordPress either way.

**Tutor owns `/courses/`.** Its course post type slug is literally `courses`, so a
WordPress page of that slug is unreachable behind the archive, and Tutor further swaps the
template via `template_include` — which runs *after* the theme hierarchy. Both the archive
and the single-course templates are claimed back with a `template_include` filter at
priority 999. If a course page suddenly looks like default Tutor, that filter is why.

**Elementor Pro caches Theme Builder conditions.** A template inserted over WP-CLI will
exist and never display until you call
`\ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager()->get_cache()->regenerate()`.
Both the header and footer hit this.

**`.cas-root` is on `<body>`.** The prototype scoped its whole design system to
`.cas-root[data-theme]` on a wrapper div; rewriting ~40 selectors was the worse option, so
the class and attribute go on `<body>` instead. Consequence: any rule written
`body, .cas-root { … }` now matches the body at *class* specificity. This silently broke
document scrolling once — see the traps below.

---

## 4. Traps that have already cost time

**Full-page screenshots lose light-on-dark detail.** Twice, images and a logo appeared
"missing" in a screenshot while being present, correctly sized and fully opaque in the DOM.
Zoom into the region before concluding anything is broken.

**`document.hidden` is true in automated Chrome tabs**, so `requestAnimationFrame` never
fires. Any rAF-driven code looks dead when it is fine. Verify computed styles and event
wiring instead, and leave motion for a human to confirm.

**When the Chrome tab stops responding to script injection, close it and make a new one.**
It works. Do not conclude the session is degraded.

**Google Drive serves an HTML interstitial** for files over 100MB via `uc?export=download`.
Use `drive.usercontent.google.com/download?id=…&export=download&confirm=t`, and check
`Content-Type` before batching — otherwise you cheerfully store 22 web pages as videos.

**`head -n N` on a file you are appending to** will truncate what you just added. This
cost several diagnostic rounds chasing a CSS rule that was no longer in the file.

**The primary button on a draft course reads *Publish*, not Update.** Clicking it to test a
save published Little House once. Use **Save Draft**, or save over WP-CLI / REST.

**Prefer `user_can($id, $cap, $post)` to `wp_set_current_user()` inside `wp eval`** — no
session mutation, harder to get wrong. One `wp eval` fatalled at `pluggable.php:27` (the
declaration line of `wp_set_current_user`) and does not reproduce. Cause unconfirmed: the
error message was lost to a `tail -5`, and two explanations were recorded and retracted before
that was noticed. Do not attach it to plugins or to capability state.

**Tutor does not give administrators `edit_others_tutor_courses`.** Combined with
`post_author = 0` on imported courses, that made every course uneditable by everyone and
invisible to REST. Both are fixed, but the capability grant lives in the `casbmqt_user_roles`
option — a Tutor update that re-runs its role installer can wipe it, and the symptom looks
like a brand-new bug.

**Corrected 2026-10-20 (option name):** the grant is stored in `casbmqt_user_roles` (table prefix `casbmqt_`, runbook §3). **Previous wording:** "the capability grant lives in the `wp_user_roles`" + line break + "option".

**Two agents must never own one file, and replying to a peer message can create a second one.**
Both collisions in the 2026-08-23 runs were orchestration errors, not agent errors. One task
brief assigned `cas-about.php` to two agents at once; separately, answering an agent's question
by message *resumed* it alongside the workflow's own copy of the same agent, and the two fought
over one file until a duplicate-function fatal reached the live site. Give each agent exactly
one file that does not yet exist, forbid shared files outright, and let a single integrator wire
everything afterwards. Tell agents to make the safe choice and report rather than ask and wait.

**Bash calls on this box have been observed executing twice.** That once inserted a PHP block
into a theme file twice — a fatal, caught only by `php -l` — and once created six test posts
twice. Read every mutation back before believing it.

**Verify before reporting a defect.** A blog block-restriction filter was reported as "not
applied" because the test passed a `stdClass` where the code correctly requires a
`WP_Post`. The code was right and the test was wrong. This project has produced more false
findings than real defects.

**WordPress prepends `sizes="auto,"` to every lazy image, and that breaks `height: 100%`.**
`wp_img_tag_add_auto_sizes()` rewrites the attribute after the theme has emitted it. On the
About certificates it made the browser resolve every scan at 1500px inside a 172px mount, so
all four certificate images painted entirely outside their own `overflow: hidden` box and the
page showed four empty cream rectangles. The DOM looked correct, the files were fine, and the
prototype rendered the same markup properly. Suspect this whenever an image is present,
opaque, correctly sized in the file, and invisible.

**Tutor LMS ships a `reset.css` that forces `white-space: nowrap` on every `<button>`.** The
About honours table is a list of buttons, so rows are cut off mid-word with no ellipsis.
Nothing in the site's own bundle counters it. Any control built as a `<button>` with wrapping
text needs an explicit `white-space: normal`. **Re-measured 2026-08-25 (#54): it is 5 of 8 rows,
not 5 of 7** — at 1440px the 2026 row measures `scrollWidth/clientWidth` 637/320 and
`text-overflow` computes to `clip`, so there is not even an ellipsis. It defeats
`industrial-3.css:1089`'s two-line clamp. Below 1100px the table is the only certificate
surface on the page, so the fix wants checking at ~900px too.

**A working copy on Google Drive can be many commits behind `origin/main`, and the theme lives
only on the remote branch.** The parity audit produced a false finding this way: an agent ran
`git ls-files wp-content`, got 0, and reported §8's claim that the theme is versioned in the
repo as wrong. The local checkout was **11 commits behind**; `git ls-tree -r origin/main
--name-only wp-content` lists **132 files**. `git fetch` and compare against `origin/main`
before concluding anything about what is or is not in this repo.

**Three post types registered by three parallel agents all claimed `menu_position 22`.**
WordPress resolves the collision with a hash-based fractional offset, so nothing is lost and
the admin menu order is simply arbitrary. The ladder now in use is 21 Artwork, 22 Student
work, 23 Free videos, 24 About photos, 26 FAQ, 27 Testimonials. Two Customizer sections had
collided on priority 60 the same way. Check both before adding either.

**`wpautop` on the homepage is solved, and not the way it looks.** Every homepage shortcode
sits on its own line separated by a **blank line**. WordPress then wraps each in its own
paragraph and `shortcode_unautop` — which runs after `wpautop` and before `do_shortcode` —
strips the wrapper. Putting them on consecutive lines instead puts every `<section>` inside a
stray `<p>` with `<br />` between them, which is where the page was before. Do not "tidy" the
blank lines out of page 12.

**Four modules disable `wp_img_tag_add_auto_sizes` globally and none of them restores it.**
`cas_render_about()`, `cas_render_home_courses()`, `cas_render_home_gallery()`,
`cas_render_home_blog()` and `cas_render_home_about()` each call
`add_filter('wp_img_tag_add_auto_sizes','__return_false')` inside the render, all for the same
correct reason (the `sizes="auto,"` trap above). The result is one site-wide behaviour decided
privately in five files: the first module to render on a request changes it for every image on
that page, including images it does not own. It should be one decision in one place.

**A Customizer section with a description and zero controls does not render.** Pointer-only
sections — the ones whose whole job is to say "this is edited under Dashboard → X" — are
invisible in the panel. Five of the ten Pages sections are pointer-only and therefore never
appear. Give a pointer section at least one control, or accept that nobody will read it.

**`page_for_posts` silently swallows a page's own content.** The Blog page was set as
WordPress's posts page, so its `post_content` was never run and `[cas_blog]` would have been
dropped with no error anywhere. Clearing the setting fixed it. Permalinks were unaffected
because the structure is `/%postname%/` at the site root.

**Elementor's `save_conditions()` takes an array of arrays.** `["include","singular","post"]`,
not `"include/singular/post"` — the string form fatals at `conditions-manager.php:304` with
"Cannot unset string offsets". Then regenerate the conditions cache, as above.

**`.php.bak-*` files in the web root are served as `text/plain` with a 200.** Six were sitting
there, including a full copy of `cas-about.php`. Back files up outside `public_html`.

---

## 5. The editability rule, and why it matters more than it sounds

The direction document's §1.4.14 is default-closed: only fields each chapter explicitly
names may be edited by the client. It also lists what is *generally* open — body text,
images, links, prices — versus *generally* locked — button text and destinations, section
headings, icons, layout, colours, fonts.

Reading "default-closed" as "hardcode unless told otherwise" is wrong and produced a real
defect: the FAQ chapter explicitly grants editable question-and-answer content (:1264) and
it had been built as a PHP array. Fixed by moving to a post type.

The **editability register** now exists to stop this recurring: `EDITABILITY-REGISTER.md`
beside this file, with a machine-readable `editability-register.tsv`. One row per field —
page, field, whether it is editable, where it is edited, and what decided it.
**275 rows: 185 OK, 41 DEFECT, 49 UNDECIDED.** (Was 221 / 138 / 37 / 46 before the homepage,
account and archive build. Five homepage sections that carried one "not implemented" row each
now carry a row per field, which is most of the growth.)

`UNDECIDED` is the status that justified the exercise. "Locked because we decided" and
"locked because nobody considered it" look identical in code and are completely different
things. It started at 54 fields, fell to 46 when **Herman's Rule** (recorded at the foot of the
register, 2026-08-23) settled the rest — structure and headers are his, pictures and texts are
hers — and is now 49, because the new pages raised ten small new silences while closing seven
old ones. Rows the rule decided carry `decided_by = Herman's Rule 2026-08-23`, so a decision
and a silence never look alike again.

`DEFECT` runs in three directions, which was not the original definition:

- **Class A — the document opens it, she cannot edit it.** 8 rows. Still mostly sections that
  were never built rather than fields wrongly hardcoded: the Courses page hero and FREE TRIAL
  band, two of the four per-lesson fields, and now the §5 New release carousel (A7). The
  homepage About Crystal image (A1) closed with the homepage build; the Courses tier entry
  (A2) closed earlier the same day.
- **Class B — the code opens what §1.4.14 closes by default.** 19 rows, dominated by the
  entire Tutor course surface (title, cover, prices, currency, level, category, order,
  description, benefits, syllabus). Almost certainly intended, but written down nowhere, so
  there is no baseline for "she changed it and the page broke". Also here: the three brand
  images, the primary nav, FAQ ordering, and now the homepage course rail, which puts that
  same Tutor surface on a second page.
- **Class C — Herman's Rule opens it, the 2026-08-23 build did not.** 14 rows, and the pattern
  is now unmistakable: it is always body copy or an image description. Three About prose
  blocks and two band paragraphs left in PHP, a portrait hardcoded on Free Videos while it is
  editable on About, heading colours dropped from blog articles — and from the second pass,
  the homepage About card's alt text (C9, the same fault as C3 recorded hours earlier), three
  hardcoded lede sentences on the account pages (C10), and the author archive heading printing
  a login name (C11). On a new page, check the copy first.

Two findings that are not editability at all, caught while comparing: DD:775-777 requires
the course CTA to read `Add to Cart` before purchase and `View Course` after — the code
emits "Add to cart" unconditionally, with no purchase-state branch. And DD:634-635 requires
a member-terms clause saying username and avatar may appear in student sharing; the
testimonial cards publish both, and that terms panel is one of the three not implemented.

The register ends with **decision briefs for all 16 undecided areas**, ordered by ongoing
developer burden, each with change frequency, cost of opening, cost of leaving closed, and
a recommendation. The three at the top:

1. **Course detail images** — the spec says five per course, one renders. Highest value per
   pound, because the frequency is tied to revenue: every new course needs a developer to
   finish its page.
2. **The About page** — 1,151 lines of hardcoded PHP covering her own biography. Split it:
   open the prose and the photos, leave the honours table closed. That table is where an
   earlier build invented credentials, and where a deliberately pixelated scan could be
   overwritten with an unmasked one.
3. **Seven missing homepage sections** — not a field to open but the homepage to build, and
   three of the seven are blocked on the course titles and prices, not on code.

The document is still silent on **course detail**, on what surrounds a blog article
(DD:1548-1550 says §12 never described the inner page), and — newly — on whether Tutor's own
dashboard panels are acceptable inside our chrome, on whether new accounts may be created at
all, and on the three archive questions in brief 17: there is no search field anywhere on the
site, search returns WordPress Pages beside articles, and the 404 is unstyled. Those are open
questions, not settled policy. Showcase, Free Videos, the blog fields and the five new homepage
sections are no longer among them — Herman's Rule decided them and the register records which
rows it decided.

---

## 6. Must not reach launch

These are all live on the site today and all are safe only because the site is `noindex`.

1. **"lifetime access"** in FAQ answer 3. The direction document requires Crystal to
   confirm that phrase before it appears anywhere.
2. **The PayPal guest-checkout claim** in FAQ answer 9 — published verbatim from her
   document at Herman's instruction. It promises card payment without a PayPal account,
   which varies by country. Verify it holds for HK and UK before launch.
3. **Three "Demo" certificate rows** on About, captioned "Layout scaffolding — not an
   award", plus a "Scan not supplied" row.
4. **The lower live-drawing carousel on About repeats the same six photographs**, rotated
   by half. Crystal has not supplied a second set.
5. **Three legal panels are missing from About `#terms`** — licensing/household sharing,
   Cookies, and Privacy choices & data rights. They were withheld because the prototype's
   placeholder copy invented UK GDPR terms including "Deletion requests are completed
   within 30 days" for a Hong Kong-based artist. Do not write replacement wording; it needs
   to come from Crystal or her adviser.
6. **Bunny token authentication.** Referrer restriction is in place and verified (403
   without a referer, 200 from the site), but Bunny permits its own iframe origin, so
   anyone holding a video GUID can embed the player anywhere. GUIDs are only rendered on
   enrolment-gated lesson pages, so a non-buyer never sees one — but an enrolled student
   could redistribute. Token auth is the real fix and is scheduled with the payment work.
   *Update 2026-09-30:* the signing code is merged (PR #98) and deployed. `CAS_BUNNY_TOKEN_KEY` in wp-config held the Bunny library API key (write access to the videos) until the rotation; it now holds the pull-zone security key, stored in `.env` as `BUNNY_TOKEN_KEY`. Enforcement on the pull zone, and the acceptance criteria of #87, are UNVERIFIED.
7. **The 2016 exhibition poster** sits in the About certificates table as a dated row and
   is not an award. That was our decision on 2026-08-16 and has never been put to her.
8. ~~**Every certificate scan on About renders as an empty cream mount.**~~ **STRUCK
   2026-08-25 by the parity audit (#54).** `inc/cas-about.php:742` now calls
   `add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' )`, so the `sizes="auto,"` trap no
   longer applies to this page. The About agent additionally reports that the screenshot which
   originally showed four empty cream rectangles was a capture artifact. The *separate* half of
   this item — the highlighted certificate having no table row and no lightbox handler — was not
   re-verified by the audit and is **still open**; see the register.
9. ~~**The Showcase wall renders its first print unpinned and behind the others.**~~ **STRUCK
   2026-08-25 by the parity audit (#49).** `inc/cas-showcase.php:492-495` carries the `-1`
   sentinel guard on both the lead and the mate (`$is_mate = ( $pair_lead >= 0 && $index ===
   $pair_lead + 1 )`), so index 0 no longer takes the mate branch. Verified by reading the
   deployed source, not by publishing a piece — the wall still holds zero photographs.
10. **The header tells a signed-in member to log in.** `inc/cas-header.php` renders LOG IN and
    SIGN UP unconditionally on every page — including the dashboard, directly above a band
    reading "Welcome back, Herman." There is no `is_user_logged_in()` branch, because before
    the account pages existed there was nowhere to log in and it did not matter. It matters
    now.
11. **The header advertises Sign up while registration is closed.** `users_can_register` is
    `0`, so that primary-styled pill leads to an honest "New accounts are closed at the
    moment." card on every page of the site. The login page already suppresses its own
    equivalent link for exactly this reason; the header does not.
12. **The basket's only message is invisible in the dark theme.** Tutor's "No courses in the
    cart" sentence computes at **1.05:1** contrast against the card behind it in theme D,
    which is the default. `cas_account_css()` remaps about thirty of Tutor's own custom
    properties onto the site palette and `--tutor-body-color` is not among them. One token.
    Theme A is fine, and no other element on that page falls below 3:1.
13. **"Course photographs to come" reaches shoppers on two of the four courses.** Little House
    and Urban Sketch have no `_cas_course_photo_1..4` values at all, so publishing either puts
    that developer sentence in the lower third of its homepage card. The string is the
    prototype's own, so it is not invented copy — it is still a status message written for a
    developer and shown to a customer. Fill the four photo slots, or agree different wording.
14. **The site has no cookie banner at all.** That is the correct outcome — the prototype's was
    fabricated placeholder copy asserting UK GDPR rights and 30-day deletion for a Hong Kong
    artist, and it was deliberately not ported. But a UK-facing site that takes payments has no
    consent surface. This belongs with item 5 above; the wording has to come from Crystal or
    her adviser, not from us.
15. **A JavaScript exception on the signed-in dashboard.** **Probably closed, 2026-08-25 (#53):**
    `elementor-frontend` is now correctly dequeued and `elementorFrontendConfig` no longer appears
    in the served page. Not struck, because that render was made as an administrator with the
    admin bar, and no real non-administrator student account exists to test with
    (`users_can_register = 0`). The second half of this item stands and is worse than recorded:
    the audit measured **four** `/dashboard/account/*` sub-pages (Profile, Reviews, Billing,
    Settings) rendering with `site-nav 0, site-footer 0, dash-hero 0` — no site chrome at all,
    because `plugins/tutor/templates/account.php` prints its own document and never fires
    `tutor_dashboard/before|after/wrap`. Original text follows. `ReferenceError:
    elementorFrontendConfig is not defined`. Tutor prints its own document for that page, so
    Elementor's header/footer templates never render and Elementor never prints its config —
    while its scripts are still enqueued. Two consequences: the dashboard is the one page whose
    chrome comes from the shortcodes rather than the Theme Builder templates, so a later edit
    inside those templates will not appear on it; and it has no `<main>` landmark.
16. **The search results heading can print a raw HTML entity.** `mb_strimwidth()` is applied
    after `esc_attr()`, so a search phrase containing `&` truncated at 60 characters renders as
    `&amp` in the `<h1>` and again in the lead line. Truncate `get_search_query(false)` and
    escape afterwards.
17. **The archive banner is the only page hero on the site that is not full-bleed.** It is
    boxed at 1140px, because the rule that undoes hello-elementor's box for `.cas-archive`
    lives in `cas-courses.php` and is only enqueued on the course catalogue. Two other modules
    already solve the same problem their own way. One line.
18. **HALF CLOSED, rewritten 2026-08-25 (#48). The helper is fixed; the hero is not.**
    `cas_page_url()` now special-cases the slug (`inc/cas-footer.php:62-73`, returning
    `get_post_type_archive_link('courses')`), and the footer's "All courses" and "Membership"
    links resolve to `https://crystalau.com/courses/` on the live site — measured by curl, not
    assumed. **What is still broken is the homepage hero**, which never used the helper:
    `inc/cas-hero.php:142-143` calls `get_page_by_path('courses')` directly, gets NULL because
    Tutor owns the slug, and falls back to `home_url('/')`. All three slides serve
    `class="hero__cta" href="https://crystalau.com/"`. One line, on the most-visited page.

### Two reported problems that are not problems

Both were raised in this build's own reports and both are wrong. Recorded so that acting on
them does not cost a working link and an afternoon.

- **The YouTube link is not fabricated.** `https://youtube.com/@crystallauart` resolves 200 to
  a channel titled "Crystalauart", and the prototype's `js/site-footer.js` annotates it
  "Supplied by the client 2026-08-08" — with a separate pending-placeholder branch for links
  that genuinely are outstanding. The `CLAUDE.md` 2026-07-22 entry about invented handles
  concerns `@crystalsartschool`, which is a different string. It is unconfirmed in writing by
  Crystal, which is a smaller and different thing to raise.
- **The Customizer section titles render correctly.** "Home — Free videos card" and "Home —
  blog & tools" were reported as printing raw `&mdash;` entities to Crystal. Read back from
  the live Customizer signed in as `cas_dev`, they show a real em dash and a correctly escaped
  ampersand.

---

---

## 6a. Fix list from the 2026-08-23 build — verified, not yet fixed

> **RESOLVED 2026-08-25.** Every item below was fixed, linted (`php -l` / `node --check`),
> deployed to the VPS and verified live, tracked as GitHub issues #23-31 and merged via
> PR #41. The "Refuted during verification" items remain refuted — still do not "fix" them.
> The list is kept below as the historical record; §1's third-pass table is the current state.
>
> **SUPERSEDED AS THE CURRENT WORKLIST, same day.** This list closed a fix list, not the port.
> The live worklist is now the parity audit —
> **[issue #55](https://github.com/herman925/crystal-art-school/issues/55)** and the thirteen
> per-page tickets it links. Read §1 "Still not built" for the summary. Do not treat this
> section's "RESOLVED" banner as meaning the site matches the prototype; it does not.
>
> Two items on this list were re-measured by the audit and moved:
> - Item 4, "The New release row is 397px short on desktop" — the carousel was built
>   (`inc/cas-home-releases.php`) and the row is correct in code. It renders nothing because
>   the four courses are now published by the owner (confirmed 2026-10-20), and the carousel's empty state was the draft state (content-gated, not short). See #48. (Corrected 2026-10-20 (owner confirmed publication); previous wording: "every course is a draft, which is **content-gated**, not short. See #48.")
> - Item 5, "Crystal's Course Overview copy renders on no page" — built
>   (`inc/cas-home-course-overview.php`) and renders today. Closed.

Found by adversarial verification after the homepage / accounts / archives build. Each was
proved, not suspected. Ordered by what a visitor sees.

**Visible to anyone, on every page**

1. **The header shows LOG IN and SIGN UP to a signed-in member** — on every page, including
   the dashboard they have just logged into. A small conditional in `inc/cas-header.php`.
2. **The header advertises "Sign up" while registration is closed.** `users_can_register = 0`,
   so the signup screen itself is honest and the site-wide call to action that leads to it is
   not. Either hide the button while registration is off, or turn registration on — that
   second one is Herman's decision and also unblocks the membership CTAs.
3. **The cart's only message is invisible in dark theme.** "No courses in the cart" measures
   **1.05:1** contrast. Caused by the token remap in `cas-account.php`.

**Visible on the homepage**

4. **The New release row is 397px short on desktop.** The §5 carousel was never built, so the
   `.grid-12.release-trio` row renders two of three columns. Correct below 900px. The
   "New release" heading is deliberately NOT printed, because `industrial-1.css:766` pins it
   above the missing column — printing it would put a heading over an empty cell.
5. **Crystal's Course Overview copy — roughly 1,100 words she supplied — renders on no page of
   the site.** The CSS for it ships and nothing emits the markup. Blocked on one decision: the
   prose wants Customizer fields, and the four questions can grow so they want a post type.

**Lower, but real**

6. `ReferenceError: elementorFrontendConfig is not defined` on the logged-in dashboard.
7. **Author archives publish raw usernames as the `<h1>`** — `/author/cas_dev/` renders
   "Herman", `/author/cas_owner/` renders "cas_owner".
8. The search results heading truncates inside an HTML entity, so searching a phrase with "&"
   shows a literal `&amp` to the visitor.
9. **The 404 page is now the only page still rendering raw hello-elementor.** The archives
   module excludes `is_404()` deliberately and correctly; nothing has replaced it.
10. A visible 16px reverse-jump on the homepage About column, introduced by
    `js/cas-site-extras.js`.

**Refuted during verification — do NOT "fix" these**

- The YouTube social default is **not** fabricated. An earlier report called it invented;
  acting on that would have deleted a client-supplied link.
- Two Customizer section titles do **not** print their HTML entities literally.

---

## 6b. Session of 2026-08-28 — decisions, two new contracted gaps, and the execution order

A design session settled every decision the parity tickets were blocked on. Full reasoning is in
`CLAUDE.md`'s two entries for this date; this is the state it left behind.

**The count moved 63 → 65.** A spec audit found two things the parity audit had missed, both
**contracted rather than nice-to-have**:

1. **Free lesson previews were broken.** `tutor_addons_config` did not exist in the database, so
   **all 28 Tutor Pro addons were disabled** — including `tutor-course-preview`. Two lessons carry
   `_is_preview=1` and Crystal's own live FAQ promises previews. Addon enabled 2026-08-28; prior
   state backed up to `~/cas-backups/tutor_addons_config.before.json`. **Not verified end to
   end** — both lessons sit in courses the owner has published (confirmed 2026-10-20), so the re-check is due now. (Corrected 2026-10-20 (owner confirmed publication); previous wording, line breaks shown as spaces: "both lessons sit in draft courses a logged-out visitor cannot reach. Re-check the moment courses publish.") Issue **#56**.
2. **Course materials — bundled and per-item download — specified in three documents and her own
   FAQ.** There is still no lesson template in WordPress, but the course single renderer now has
   a guarded Tutor attachment shelf merged in PR **#85**. It uses Tutor's official attachment
   template, renders only supplied attachment records and keeps Tutor's native empty state.
   The change is **not deployed or live-verified yet**. `tutor-course-attachments` remains enabled.
   Issue **#58** stays open for Crystal-supplied notes and reference photographs.

**SUPERSEDED THE SAME EVENING — there are no membership tiers.** This section briefly recorded a
switch of the Fellow tier from Discord to Tutor Q&A + Assignments (issue #57, now closed as
superseded). Crystal answered on WhatsApp, 2026-08-28: 「我決定簡單些，只提供觀看課程，不設批改及
Q & A」 — course viewing only, no marking, no Q&A. Herman proposed dropping tier language entirely
and she confirmed.

**The model now:** paid unlocks that course's video lessons and downloadable materials; unpaid
sees the public pages. No Wanderer / Apprentice / Fellow, no Discord, no Q&A, no assignments.

Applied on the server and verified 2026-08-28: `tutor-assignments` **disabled**,
`enable_q_and_a_on_course` **0**. `tutor-course-preview` and `tutor-course-attachments` stay
enabled — both separately contracted and unaffected.

**Source-only developer cleanup delivered 2026-08-30 via PR #84** (merged, deployed and live-
verified): the `THREE WAYS TO STUDY` block and its three Courses CTAs, the developer-owned
Discord/membership surfaces on Home, Courses, About, Showcase and the footer, and the obsolete
developer FAQ intro default are gone. FAQ posts **82** and **84** remain untouched because they
are **Crystal's own words** and still need approved replacement wording for the video-only
product. Post 82 also carries the unconfirmed "lifetime access" phrase. Issue **#65** remains
open for that authored-copy gate.

**Do not build from direction §1.4.8's tier table** — the client's own instruction supersedes it.
The proposal (`proposal_text.txt:96-99`) and `PRD.md:169` also define three tiers and are
likewise superseded.

**Decisions that unblocked five tickets** (#43 #44 #45 #46 #50, now `ready-for-agent`): guest login
dropped; gallery gets a medium field; blog gets bio *and* comments; legal mechanisms built with
wording blank; dashboard restyles Tutor's shell **except** the six-panel body, which is built.
Site-wide: `.container` 1440px, Rank Math for meta, motion wired everywhere.

### Execution order — prefactors first

Six cross-cutting fixes must land **before** the 13 page tickets fan out. Each currently exists
only as scattered bullets inside several page tickets, and on this project a shared bug has already
been independently patched in four separate files because nobody owned it centrally.

| | Prefactor | Blocked by |
|---|---|---|
| P1 | Glow layer site-wide | none |
| P2 | Page width `.container` 1440px | none |
| P3 | Hero CTA destination + responsive hero image | none |
| P4 | SEO titles and descriptions (Rank Math) | none |
| P5 | Motion wired site-wide | P1, P2 |
| P6 | Publish courses + enable registration | P1–P4 |

The 13 page tickets are blocked by P6, because publishing makes the content-gated blocks render —
which is what lets an agent verify what it builds. **Everything verified so far was verified
against an empty state.**

**Not enabled deliberately:** `tutor-certificate`. Nothing in the spec asks for a completion
certificate; §14's 證書 is Crystal's own exhibition certificates on the About page.

**Found, unresolved, nobody's decision yet:** no purchase receipt or enrolment email is specified
anywhere in the proposal, PRD or direction document. That is a real hole in a site that takes money.
Since 2026-09-30 mail delivery works (`hello@crystalau.com`, SMTP), so a receipt could now be sent. What it says is still nobody's decision.

## 6c. Session of 2026-08-30 — source cleanup delivered; course attachment shelf merged

The source-only portion of issue **#65** landed in PR **#84**, merge commit
`58de80edf661f02dc7dc054095d0099ee591d842`. The six theme PHP surfaces were deployed and the
rendered Home, Courses, Showcase and About surfaces were checked; authored FAQ content remained
unchanged. The issue stays open only because replacement wording for Crystal's authored FAQ/docs
copy has not been approved.

The repository-only portion of issue **#58** landed in PR **#85**, merge commit
`0082497809f6572027c8c9f7a06011883bce53bd`. `cas-course-single.php` now resolves the requested
course's Tutor attachments and reuses Tutor's official `global.attachments` template, including
its escaped attachment records and honest empty state. No files were created, uploaded or seeded.
This merge has **not** been deployed or live-verified. Keep #58 open until Crystal supplies the
actual notes/reference photographs and the integrator completes the separate backup/deploy/
served-output verification.

The repository checks for #58 passed: focused contract and sabotage/restoration proof, existing
Home/Courses/About/Dashboard parity contracts, Python compilation, PHP 8.3 lint on 35/35 child-
theme files, JavaScript syntax on 15/15 files, `git diff --check`, and a zero-finding added-line
security scan. Full pytest is unavailable in this environment.

## 6d. Session of 2026-08-31 — login PR reconciled

PR **#79** (login-form dependency cleanup) was already deployed and live-verified, but had been
left open unnecessarily. It was merged into `main` at `8ac76eaaaa188341d649f2abd94a960dcb8c5080`,
its remote branch was deleted, and the repository now has no open pull requests. Issue **#43**
was already closed as completed; no new live WordPress mutation was made during this cleanup.

## 6e. Incident of 2026-09-21 — signup closed, secrets rotated

An attacker used open signup and a subscriber-reachable Tutor endpoint (CVE-2026-78175, Tutor 4.0.7 and earlier) to get an administrator account on 2026-09-21. It was found and contained on 2026-09-30. Tutor and Tutor Pro are on 4.1.0, the secrets held on the server were rotated, and monitoring is live. Full record: `docs/incidents/2026-09-21-tutor-object-injection.md`.

Rules that stay: never `wp user delete` or `get_user_meta` a suspect user (it fires the payload; use raw SQL). Keep `mu-plugins` at 555, keep `uploads/.htaccess`, keep signup closed until map #99 is done. The ajax guard mu-plugin `cas-ajax-guard.php` (PRs #127 and #134) denies non-admin access to listed Tutor handlers, including instructor applications (the site takes no instructors), and Q&A, lesson comments and lesson notes are switched off (owner decision 2026-09-30). Keep both.

## 6f. Payment test of 2026-09-30, and the student-screen decision

Test purchase (Stripe test mode, private course 269): the order was paid by the webhook event `charge.succeeded`. A refund made in Tutor's admin order modal works (order 1's enrolment removed, order 2's kept, per the 'remove enrolment' checkbox) (production rows read by Dev, 2026-10-20); the Stripe-dashboard path was never run and, per code, leaves access in place (Stripe.php:194-200). Corrected 2026-10-20 (Review, refund item A); previous wording: "A refund works." Refund effect on enrolment depends on the path (Tutor 4.1.0, HooksHandler.php:329-400): the admin order modal honours the 'remove enrolment' checkbox (ticked removes access, unticked keeps it); a refund made in Stripe's dashboard does not change the Tutor order or enrolment: tutor-stripe 1.0.9 ignores `charge.refunded` and returns nothing to Tutor (payments/Stripe.php:194-200; ecommerce/PaymentHandler.php:86-88 in Tutor 4.1.0), so, for a completed, paid order, access stays until an administrator changes the order in Tutor's admin (verified by code reading of tutor-stripe 1.0.9 and Tutor 4.1.0, 2026-10-20; not tested against live Stripe); and a Tutor bug (enrolment lookup with no status filter) can leave access after a second refund of a re-purchase. Owner decisions pending: refund policy, and whether to fix the bug in theme code (#114). All Tutor order emails are off, and admin emails would reach every administrator. Site emails are our own theme code (issue #133, PR #136 in review); the Tutor Email and Notifications addons stay off for good (owner decision 2026-09-30). Owner decisions 2026-09-30: transactional mail only, no promos; no refunds (the site is strictly buy-a-course; a refund made in the Stripe dashboard does not reach Tutor, code-read of tutor-stripe 1.0.9 Stripe.php:194-200). Live since 2026-09-30: the transactional emails (PR #136, issue #133) and the dashboard subpages (PR #137, issue #125). Issue #138 (required consent checkbox at checkout, no refunds) is live and verified with order 3 (PR #139, merge 5fc827d); PR #139 also fixed a bug found on live orders 1 and 2: the site-terms consent was never stored on paid orders. Test artefacts still in place (cleanup: issue #118, after Herman's go-ahead): user 9, course 269 (topic 270, lesson 271), order 1 and order 2 (the owner's browser purchase through the new checkout: paid by the webhook, enrolment 275, refunded), order 3 (user 9, course 64, £24, Stripe test, paid, not refunded; on the real course 64, so remove carefully), enrolments 272, 273, 275 and 276, earnings row 8 (order 3), a Stripe test webhook and a Stripe test customer.

**Corrected 2026-10-20 by code reading (verified by Review 2026-10-20 (code reading, Tutor 4.1.0 hashes b2723b4d…/44645502…); tutor-stripe file hashes Dev-reported; post-refund charge.updated UNVERIFIED).** A refund made in Stripe's dashboard does not change the Tutor order or enrolment, for a completed, paid order: tutor-stripe 1.0.9 returns an empty result for `charge.refunded` (`payments/Stripe.php:194-200`), and no forwarded Stripe webhook can cancel an existing order that is completed and paid (`HooksHandler.php:200-202` guard; traced for `payment_intent.canceled`, `payment_intent.payment_failed` and `charge.updated`). Access therefore stays until an administrator changes the order in Tutor's admin. Evidence: trace and excerpts held outside the repo (vendor code; not committed). Also found: the two £1 test refunds in production (orders 1 and 2) were made by Herman through Tutor's admin, not the Stripe dashboard, and their enrolments differ (order 1 cancelled, order 2 completed). UNVERIFIED: if Stripe sends charge.updated with status 'succeeded' after a refund made in Tutor's admin, Tutor would mark the order paid/completed again and re-enrol the student. Check this in Stripe test mode before live payments. **Previous wording** (sentence changed 2026-10-20): "a refund in Stripe's dashboard removes access automatically; and a Tutor bug".

**Corrected 2026-10-20 (cite only):** `payments/Stripe.php:194-199` in the paragraph above is now `payments/Stripe.php:194-200` (the early return for `charge.refunded`). **Previous wording:** "payments/Stripe.php:194-199".

**Decision: everything a student sees must match the prototype.** The lesson page is now ported (PR #128, deployed 2026-09-30, template `inc/cas-lesson.php`; owner browser check pending, issue #122). Quizzes and assignments keep Tutor's own screens; there are no quizzes at launch (owner decision, #124). The Tutor onboarding tour is suppressed as part of the lesson work. Worker 3 files the issues under Map C (#105).

## 7. Open with the client

- **Course titles and all prices are unapproved placeholders.** The four courses were held as drafts for this reason and are now published by the owner (confirmed 2026-10-20); the titles and prices are still unapproved placeholders. (Corrected 2026-10-20 (owner confirmed publication); previous wording: "This is why all four courses are still draft.") Three homepage blocks — the course rail, the Art Gallery card and
  the blog rail — are now built and each renders nothing until content is published, so this
  sign-off is what stands between the homepage and doing its job.
- **Course materials.** The merged PR #85 adds the attachment shelf without inventing content,
  but no Crystal notes or reference photographs were created or uploaded. Those files still need
  to be supplied and attached before #58 can close; the merged code also still needs deployment
  and served-output verification.
- **Authored membership wording.** PR #84 removed the obsolete developer-owned tier/Discord
  surfaces. FAQ posts 82 and 84 remain unchanged until Crystal approves replacement wording;
  do not edit them casually or treat the source-only cleanup as the whole #65 ticket.
- **About 1,100 words of her own homepage copy render nowhere.** The Course Overview block
  (§3.5) — two ledes, two numbered principles, four self-answered questions, all verbatim from
  `assets/00 - Home/Under_Shopallcourses.docx` — was not built. Nothing is lost and nothing was
  invented; it needs one decision (see §1) and one more module.
- **The Free Videos and About Crystal homepage cards ship with stand-in photographs.** The
  first uses the prototype's own `A2.jpeg`, the second her supplied `A3.jpeg`. Both are now
  Customizer image fields, so she can replace them herself — but the direction document still
  records that the *official* About image is owed by Crystal or Summy.
- **Every gallery artwork needs a real Etsy listing URL.** The Gallery deliberately renders
  no Buy button when the URL is missing, rather than falling back to an Etsy search the way
  the prototype did. Gallery is functional but Buy is inert until she supplies links.
- **`crystalauart.com` does not exist.** The PRD and Proposal §12 both assume it is her
  live Wix site that stays up during cutover. It returns NXDOMAIN. `crystalauart` is her
  Instagram handle; the two look to have been conflated. Ask for the real current-site URL
  before planning the Week 13-15 cutover.
- **One message, ordered by what blocks the build** (agreed 2026-08-28): course titles and prices
  first, since they unblock the most; the tiers are now confirmed gone and their developer-owned
  surfaces have been removed; then the course-naming convention, the Gallery "filter by medium"
  line, and comment moderation.
- **Blog posts.** Her three real posts exist as drafts in `docs/blog-drafts/` and carry a
  visible "Developer's note" about copy corrections she has not approved. They must not go
  live with that note showing. Post them under `cas_owner`, not `cas_dev`, and give each one a
  Category — the byline prints the author's display name and the topic comes from the
  category, so an article filed nowhere shows no topic at all.
- **The Showcase has photographs and nothing else.** `assets/03 - Showcase/` holds ten images
  (IMG_1077–IMG_1088) with no student names, no titles and no media. The wall works and is
  empty. The prototype's ten student names are invented and were correctly not carried over.
  Two things to settle with her: what credit goes under each print, and whether that credit is
  a **username** — the direction says it is (DD:622), and the member-terms clause that
  authorises publishing it (DD:634-635) is written around a username plus avatar.
- **Free Videos: 12 free films are published** (PR #98; YouTube links: five step-by-step demos, seven shorts; the live page carries 12 distinct YouTube ids, checked 2026-09-30). The films she gave as video files were left out: PR #98 records three, and its reason is that each address is a file-sharing preview link the player cannot open. Earlier notes named two masters, `Mountain.MOV` (766MB) and `PinkFloral.MOV` (612MB), and said `Watercolor Airport.MOV` never existed. The count (two or three) is UNVERIFIED.
  Bunny library 734616 is separate from these 12: the Operator reported 23 items in it (22 lesson films plus the highlights clip), not re-measured here.
  Still open: nothing has been assigned to the **Quick Inspiration** row,
  the third row's name (**Shorts**) is still the developer's proposal and not hers,
  and "Tram Watercolor" and "Doggie Watercolor" carry the same YouTube id in her document,
  so one of them has no working link.

Resolved recently, recorded here so it is not re-asked: **clouds-beginner has no Lesson 3**
because lesson 2 is a bundle of lessons 2 and 3. The source data already models this as
`spans: 2`, though the import did not carry that field across, so the syllabus currently
numbers 01–13 with no indication that item 02 covers two.

---

## 8. Where things live

```
Sample Website/crystal-art-school/     the static prototype — this IS the design spec
  docs/20260804_Design_Direction.md    the written direction, 1554 lines, Chinese
  index.html, About.dc.html, …         per-page prototype markup
  css/industrial-1/2/3.css             the design system
  js/site-nav.js, js/site-footer.js    header/footer markup the WordPress port reproduces
  js/course-data.js                    generated course data the Tutor import came from
  wp-content/themes/hello-elementor-child/   the LIVE WordPress theme, versioned here
                                            (source of truth since 2026-08-25; see below)

.secrets/.env                          every credential, outside the repo
docs-site/                             the client site guide (docs.crystalau.com)
docs/EDITABILITY-REGISTER.md           281 rows: 192 OK, 41 DEFECT, 48 UNDECIDED, the defect list, decision briefs
docs/editability-register.tsv          the same register, machine-readable
docs/                                  this file, plus the 2026-07-22 decision and spec docs
CLAUDE.md                              the decision log — read it before changing scope
```

**Pull before you read the theme locally.** Local `main` is often behind `origin/main`, where
the theme actually lives. On a stale checkout `wp-content/` is an empty shell and
`git ls-files -- wp-content` returns 0, which looks like proof the theme is unversioned. It is
not. That misreading reached the issue tracker on 2026-08-25.

On the server, the theme is at
`~/domains/crystalau.com/public_html/wp-content/themes/hello-elementor-child/`, with all
PHP modules under `inc/`.

**The theme's source of truth moved into the repo on 2026-08-25** (commit `97a355f`,
imported from the VPS; the stale draft files Copilot had left at the repo root were deleted
in the same PR). From then on: edit in the repo, then deploy to the VPS — never edit on the
server and pull back by hand. Deploy flow (integrator only, per the house rules): tar the
changed files using **relative** paths (MSYS tar chokes on absolute Windows paths), scp to
`~/deploy-cas-YYYYMMDD/`, extract in place, `php -l` every `.php` on the box, read the
mutations back. Pre-deploy snapshots go to `~/backups/` — the pre-fix-set snapshot is
`~/backups/cas-fixes-20260825/`.

**After a theme deploy, some course pages served the old markup for a few minutes** (cache not identified; there were no cache headers). Verify with a `?cb=` URL and again after a few minutes before you report a failure.

**Homepage Course overview block** (built 2026-08-25): six Customizer fields in section
`Home — Course overview` (panel `cas_pages`, priority 14) — `cas_home_overview_lede_1`,
`cas_home_overview_lede_2`, `cas_home_overview_point_1_t/b`, `cas_home_overview_point_2_t/b`.
The four questions live in post type `cas_overview_qa` (menu position 25, order by Page
Attributes); four seeded posts (IDs 238-241) carry her copy. The overview renders even while
the four courses are now published by the owner (confirmed 2026-10-20), and course 269 is private; (Corrected 2026-10-20 (owner confirmed publication); previous wording: "no course is published;") the lifetime-access sentence is withheld per §7.4.

A note on the prototype: it is the newer, authoritative source. `CLAUDE.md`'s May 2026
entries record a scope argument about layered heroes and parallax that the project moved on
from — the agreed hero is a three-slide carousel. Check the prototype's markup before
reasoning from that log.
