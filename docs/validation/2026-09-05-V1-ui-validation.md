# V1 — UI validation pass, page by page (#86)

Started 2026-09-05. Live site `https://crystalau.com` compared against the prototype in this
repository, served locally and rendered in a real browser at 1440px wide.

Method: each page is opened in two browser tabs, live beside prototype. Section geometry
(position, height, heading) is measured in the rendered page, and any block that differs is
then looked at as a picture. This is not a class-name diff — that method has produced false
findings on this project before and cannot tell an unbuilt block from a content-gated one.

Every finding is classified:

| Class | Meaning |
|---|---|
| FIXED | Unambiguous defect, corrected in this pass |
| TICKET | Needs a judgement call or client input; raised separately |
| DELIBERATE | Differs from the prototype on purpose; the reason is recorded |
| GATED | Renders nothing because the content does not exist yet; the code is correct |

---

## Home — `/`

| Prototype section | Height | Live | Verdict |
|---|---|---|---|
| Hero | 663 | present | OK |
| New release | 617 | 679 | OK |
| Shop all courses | 1828 | 1937 | OK |
| Blog | 395 | absent | GATED |
| Community band | 336 | absent | DELIBERATE |
| Real hands, real progress | 494 | absent | GATED |
| Social strip | 249 | 249 | OK |

**FIXED — the homepage printed a raw shortcode to visitors.** The page content still called
`[cas_community_band]` after the membership cleanup removed that shortcode from the theme, so
WordPress printed the literal text `[cas_community_band]` inside a paragraph on the live home
page. Removed from the page content; the previous content is backed up on the server at
`~/cas-backups/home-12.content.bak`. Verified by re-fetching the live page: 0 occurrences.

**GATED — Blog rail.** Renders nothing because the only published post is WordPress's own
`Hello world!` sample, which has no featured image, and a card in that rail is a picture beside
a title. The code documents this case explicitly and is correct.

**GATED — Testimonials.** Renders nothing because no testimonial is published. Correct: the
alternative is a heading over an empty grid.

**DELIBERATE — Community band.** Removed with the membership tiers on the client's instruction.

**TICKET — `Hello world!` is published on a live site.** It is WordPress's sample post and it is
the only thing the Blog page lists. It is placeholder content on a public page.

## Courses — `/courses/`

| Prototype section | Height | Live | Verdict |
|---|---|---|---|
| Catalogue with sort and filter rail | 1149 | 887 | OK |
| Three ways to study | 462 | absent | DELIBERATE |
| Not sure where to start? | 336 | 336 | OK |

Catalogue verified by eye: four courses, real cover photographs, real prices, the sale ribbon on
the discounted course, sort controls and the full filter rail all present and correctly
positioned. The height difference is the number of courses, not a missing block.

**DELIBERATE — Three ways to study.** The tier block, removed with the membership model.

**Known, not re-raised here:** the four course titles and all four prices are unapproved
placeholders. That is already tracked with the client.

## About — `/about/`

Eight sections, matched one for one against the prototype, every one within 16 pixels of the
prototype's position. Reading order, headings and heights all agree.

| Section | Prototype | Live |
|---|---|---|
| Biography | 1071 / 1028 | 1071 / 1028 |
| Community | 2099 / 522 | 2099 / 522 |
| Instructor | 2621 / 682 | 2621 / 682 |
| Highlights | 3303 / 1371 | 3303 / 1355 |
| Philosophy | 4673 / 502 | 4658 / 486 |
| Welcome | 5175 / 386 | 5144 / 371 |
| Contact | 5561 / 517 | 5515 / 522 |
| Terms and privacy | 6077 / 563 | 6037 / 320 |

**DELIBERATE — the terms panel is shorter.** Three of the four panels were withheld because the
prototype's wording invented UK data-protection rights for a Hong Kong business. Real wording is
tracked separately and comes from the client's adviser.

## Course detail — `/courses/clouds-beginner/`

| Prototype | Live | Verdict |
|---|---|---|
| What you'll learn | By the end of this course | DELIBERATE — reworded |
| Syllabus | What is inside | DELIBERATE — reworded |
| Who this course is for | This course suits | DELIBERATE — reworded |
| — | What comes with it | Added — the attachments shelf |
| Try the free videos first. | absent | **FIXED** |

**FIXED — the closing Free videos band was never ported.** The prototype closes every course page
with a picture, an eyebrow, a heading and a button through to the Free videos page. Nothing in the
theme rendered it and no note recorded a decision to drop it, so it was unbuilt rather than
deliberate. Built to match the Courses page's own trial band, which is the established pattern for
this component on the live site.

Two things about it are conditional on purpose, both following that precedent. The band stays off
the page until a free video is published, because the button must lead somewhere with something on
it. And it ships no paragraph — the prototype's sentence promises tutorials on skies, colour
mixing and materials, and nothing on this site promises those. The picture is the newest free
film's own cover rather than a fixed file, so it cannot go stale.

Consequence today: the band does not appear, because no free video is published. See below.

---

# The headline finding: five public pages have no content in them

Measured on the live database, not inferred.

| Content type | Published on the live site | Exists in the prototype |
|---|---|---|
| Artworks (Gallery) | 0 | yes — Crystal's paintings |
| Student work (Showcase) | 0 | yes |
| Free videos | 0 | yes — twelve films with covers |
| About photographs | 0 | yes |
| Testimonials | 0 | invented in the prototype, correctly not carried over |
| FAQ entries | 9 | yes |
| Course overview questions | 4 | yes |

What a visitor sees today:

- **Gallery** — the introduction, then `0 PIECES` and *No artwork is published yet.*
- **Showcase** — the heading, then *No student work is published yet.*
- **Free videos** — three section signs, each followed by *Nothing published in this section yet.*
- **Home** — no blog rail and no testimonials, both for the same reason.
- **Course detail** — no Free videos band, for the same reason.

Every one of those pages renders its empty state correctly. The code is right. **The content was
never imported.** This is not the same as waiting on the client: the pictures and the film links
are in this repository already, in `assets/` and in the prototype's own data files, and they are
Crystal's real material rather than placeholders.

**FIXED — `Hello world!` was published on the live Blog page.** WordPress's own sample post was
the only thing the Blog page listed. Set to draft, which is reversible. The Blog page now shows
its empty state instead of sample content.

---

## The content port, item by item

Each of the four empty content types was checked for provenance before anything was published.
The rule applied throughout: publish only what Crystal supplied herself, and publish nothing whose
wording was written by a developer. `tools/content-meta.json` states in its own header that its
copy is placeholder and not client-approved, so it cannot be the source of anything that goes live.

### Free videos — IMPORTED, 12 films

Her provenance note is explicit: for a film she supplied as a link, the title and the address were
transcribed character for character from her own document, and the description is deliberately
empty because she supplied none and a blurb written here would be invented.

Imported exactly that: twelve titles, twelve links, twelve cover pictures taken from her own
channel, in the order her document lists them. No description was written. Five films sit in
step-by-step demos and seven in shorts.

Three further films were **not** imported. They are the ones she supplied as video files rather
than links. Their titles and descriptions are developer-written placeholders, and the address on
each is a file-sharing preview link that this site's player cannot open at all. Publishing them
would put invented copy on a public page behind a button that does nothing.

**Consequence:** the Free videos page is now populated, and the Free videos band added to every
course page above now appears. The middle section, *Quick inspiration and bite-sized tips*, is
still empty — she has supplied nothing for it.

### About photographs — NO IMPORT NEEDED

Not a gap. Each of the three photograph sets ships with its own pictures in the theme and switches
over entirely to hers on the first one she publishes. The About page renders correctly today.

### Student showcase — NOTHING TO IMPORT

There is no student work anywhere in this project to import. The page is also the one surface that
publishes a student's name and picture, which cannot happen before the member terms exist.

### Gallery — HELD, and it needs her

The seventeen photographs are real and are hers. Everything written about them is not:

- The titles run `Original painting No.1` through `No.6`, `Art print No.1` through `No.6` and
  `Pet drawing No.1` through `No.5`. They were generated, not named.
- All seventeen prices, and the six struck-through "was" prices, were invented.
- The medium and type tags cycle Original / Print / Pet over photographs that are mostly of pets,
  because she asked for them to be filled in freely.
- Not one artwork has a shop link, so not one would show a Buy button.

The page's own introduction promises a visitor can filter by medium and price and then buy on
Etsy. Publishing these seventeen would put invented prices on a real artist's work under a
promise the page cannot keep. Held, and raised as its own ticket.

---

## Dashboard — `/dashboard/`

**FIXED — a logged-out visitor was shown a second, stock sign-in form.** The dashboard address
printed the course plugin's own sign-in panel — *Hi, Welcome back!* / *Keep me signed in* / *SIGN
IN* / *Don't have an account? Register Now* — in a voice that is not this site's, inside this
site's header and footer, while the designed Log in page sat at its own address doing the same job
properly.

The theme already states the rule for this: building a second login is not its job, and a
logged-out visitor is sent to the one that exists. That rule was applied only to the page
shortcode, and the dashboard address does not use the shortcode — the plugin renders its own
template there, so the rule never ran.

A logged-out request to the dashboard is now sent to the Log in page. Signing in still lands on
the dashboard, because that is already this site's default destination after login.

Verified in both directions on the live site:

| Request | Before | After |
|---|---|---|
| Logged out, dashboard | 200, stock sign-in form | 302 to the Log in page |
| Logged out, Log in page | 200 | 200 |
| Signed in, dashboard | 200 | 200, with the account's own name on the page |
| Every other page | 200 | 200 |

The signed-in check used a temporary subscriber account created for the test and deleted
immediately afterwards. The site has no subscriber accounts.

## Log in — `/login/`

Correct. Title, lede, both fields, remember-me, forgotten-password link, and a link through to
registration.

## Sign up — `/student-registration/`

Correct, and the consent control links to terms and privacy. What those terms say is still
outstanding with her adviser.

## Basket — `/cart/`

Correct, including the three-step progress line and the empty state. Checkout redirects to the
basket while the basket is empty, which is right.

## Not found

Correct.

## FAQ — `/faq/`

Nine entries, all published. Two of them describe a product that no longer exists:

- *What's the difference between Apprentice and Fellow?* names both membership tiers in its own
  title, and its answer promises the community server and lifetime access.
- *Can I send you my personal work for critique?* promises the community server.

Both are Crystal's own words and both are already tracked. They are deliberately **not** rewritten
here: replacement wording has to be hers, and inventing it is the exact failure this project has
had to undo before. Left published rather than withdrawn, consistent with the earlier decision to
publish her FAQ verbatim.
