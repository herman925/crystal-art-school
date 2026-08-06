# ADR 0001 — What the CMS may edit is a whitelist, not a blacklist

Status: accepted, 2026-08-06
Applies to: every page except the blog

## Context

The same question came up three times during design review: which parts of a page
can be changed later without a developer? Up to now the answer was scattered — a
sentence here, a field list there — so each page was answering it differently, and
"can this be edited?" had no reliable answer.

Two failure modes were driving that. Leave everything open and the layout drifts:
a swapped-in bright photo makes overlaid text unreadable, a renamed tier breaks the
checkout copy that quotes it. Lock everything down and routine copy fixes need a
developer, which is worse for a one-person studio.

## Decision

**Nothing is editable unless the design direction names it as editable for that
section.** Default closed.

As a rule of thumb, the two sides fall out like this:

| Generally open | Generally locked |
| --- | --- |
| Heading and body copy, images, links, prices, the *values* of tags | Button labels and destinations, section headings, icons, layout, colour, typeface |

The blog is the sole exception: its content and its arrangement are both free.
Posts are authored in the block editor; post *templates* are not editable.

## Consequences

- When building a section, the field list in its design-direction chapter is the
  whole contract. If a field is not listed there, it is not editable — do not add
  a CMS control for it "because it seems harmless".
- Anything that another page quotes by name — membership tier names are the live
  example, since the checkout copy repeats them — stays locked even though it
  looks like ordinary text.
- Where a photo is editable and text sits on top of it, the scrim has to hold up
  for a bright image as well as a dark one, and the CMS field needs a note next to
  it saying so. An editable image with a scrim tuned only for the current photo is
  an unfinished implementation.
- The layered hero on the front page is not covered by any of this. It is not
  client-editable, and the reasons are commercial rather than technical; see the
  project decision log.

## References

Design direction §1.4.14 (the rule), §1.3 (fixed layouts outside the blog), and the
per-section field lists it cites — §3.4, §8.1, §9.3, and the membership tiers in §4.
Those documents are client working material and are gitignored; they exist locally
only.
