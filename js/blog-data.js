/* Crystal's Art School — shared journal data.
   Loaded on index.html, Blog.dc.html and blog-post.dc.html so ordering is
   identical everywhere. Posts sort newest-first by `date`.

   2026-07-26: the 16 placeholder posts that shipped with the prototype have been
   DELETED. Everything below is real content supplied by Crystal on 2026-07-23
   (`assets/04 - Blog/`). The fabricated `reads` counters went with them — no
   invented numbers anywhere on the site now.

   `layout` picks the article template on blog-post.dc.html:
     guide  — lede + hero photo + numbered sections, each with its own photo
     essay  — paragraphs + one pull-quote

   `devnote` renders a visible amber "Developer's note" callout at the top of the
   article, declaring edits made to Crystal's text before she signed off. DELETE
   the field (not the post) once she approves. See docs/blog-drafts/.

   2026-08-09 (ticket 12) — DECISION: the note renders again on all three posts,
   and the copy corrections stay. Reverting them would publish a known error:
   "masking fluid" is what Crystal's own painted label reads, and Winsor &
   Newton / Claessens are the manufacturers' spellings. So the page cannot carry
   the edits with the declaration removed, which is the state this ticket was
   raised about.
   The wording is NOT the original note. That text was removed on 2026-08-07
   (ff37d09) because it described her source material, and this repository is
   public — the page and its source are readable by anyone. Each note now states
   what was changed and that she has not signed it off, and nothing about how
   the source arrived. The internal list stays in the gitignored working notes.

   2026-08-09 (ticket 17): `srcset` / `sizes` / `w` / `h` accompany every image.
   Unlike `img`, `banner` and a section's `src` — which are bare filenames the
   pages prefix with 'img/' — the paths inside `srcset` are already site-
   relative and must NOT be prefixed. `w`/`h` are the master's intrinsic pixels,
   for the width/height attributes that stop the article reflowing as photos
   arrive. The `sizes` on a post is written for the widest slot the file is
   painted in (the 1392px article banner); the Blog card (393px) and the home
   card (150px) are the same file in a much smaller slot and should set their
   own. The ladder itself is produced by tools/import-assets.py from the widths
   measured on the rendered page — regenerate it there, not here, and copy the
   strings across from tools/asset-manifest.json ("static").                    */
window.CAS_BLOG = [
  {
    id: 'watercolour-kit', layout: 'guide',
    title: "What's in my watercolour kit — the 21 tools I actually use",
    excerpt: 'Every tube, brush, block and bottle in my kit — painted, cut out and laid on the table.',
    /* The banner was blog-kit-18-21.jpg, which is also the photograph for items
       18-21 further down, so the reader met the same picture twice. The
       all-21-tools shot is the establishing image for an article about 21 tools
       and appears nowhere else in the body. */
    img: 'blog-kit-all.jpg', banner: 'blog-kit-all.jpg',
    srcset: 'img/blog-kit-all-192.jpg 192w, img/blog-kit-all-384.jpg 384w, img/blog-kit-all-768.jpg 768w, img/blog-kit-all.jpg 1600w',
    sizes: '(max-width: 600px) 358px, (max-width: 1000px) 1173px, 1392px',
    w: 1600, h: 1600,
    tag: 'Tools', date: '2026-07-23', d: 'Jul 23', mins: 8,
    devnote: "This post has not been approved yet. Crystal supplied the text and the paintings; the wording published here has been edited. Four material names were corrected against her own painted labels and the manufacturers' spellings — masking fluid (items 7 and 8), Winsor & Newton, Claessens, and squirrel hair (items 17 and 18) — two instructions were corrected (item 5 “after use”, item 6 “still damp”), and an unverified “artist-grade” claim was dropped. The title, the opening and closing paragraphs and the six section headings are ours: her original was a numbered list of twenty-one items. She has the full list and this note stays until she signs it off.",

    intro: [
      'People ask what I use far more often than they ask how I paint. So I painted my whole kit — every tube, brush, block and bottle — cut each one out, and laid them all on the table. Twenty-one things. This is honestly what comes with me.',
      'A note before the list: almost none of this is essential to start. Numbers 1, 2, 9 or 10, and 12 or 13 will get you painting. Everything else accumulated over years of finding out what I actually reach for.'
    ],
    /* No in-article hero: the banner above the title is already this photograph,
       and repeating it 550px later just makes the reader scroll past the same
       picture twice. `hero` stays supported for posts whose banner differs. */
    sections: [
      {
        t: 'Paints and palette', src: 'blog-kit-1-4.jpg', start: 1,
        srcset: 'img/blog-kit-1-4-384.jpg 384w, img/blog-kit-1-4-768.jpg 768w, img/blog-kit-1-4.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 1066,
        items: [
          { t: 'Winsor & Newton watercolour palette box', d: 'A hand-held mixing box for sketching and for beginners. Lightweight PVC, with a water bottle and a brush wash tray, and room for twelve solid watercolours.' },
          { t: 'Winsor & Newton Cotman', d: 'Their entry-level range. Natural, delicate colours and a good place to start.' },
          { t: 'Holbein student grade', d: 'The well-known Japanese brand. A rich, vivid palette — I use it most for illustration, flowers, fruit and other still life.' },
          { t: 'Van Gogh, by Royal Talens', d: 'Bright, transparent, intense colours with high tinting strength.' }
        ]
      },
      {
        t: 'Highlights, masking and scraping', src: 'blog-kit-5-8.jpg', start: 5,
        srcset: 'img/blog-kit-5-8-384.jpg 384w, img/blog-kit-5-8-768.jpg 768w, img/blog-kit-5-8.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 1039,
        items: [
          { t: 'ZIG opaque white ink', d: 'Japanese, and it covers strongly. I use it for stippled highlights and for lifting things back to white in the final stages. It dries out easily, so close the lid straight after use.' },
          { t: 'Claessens all-steel knife', d: 'Belgian, very springy, and rust-resistant. It works as a paper cutter, and while the paint is still damp you can scrape with it to bring the paper colour back and leave a highlight.' },
          { t: 'Holbein masking fluid', d: 'Paint it over the areas you want to keep white before you colour, then peel it off afterwards. The pink one is much easier to see on the paper than the white.' },
          { t: 'Masking fluid pick-up', d: 'A rubber block made for lifting dried masking fluid off the paper without tearing the surface.' }
        ]
      },
      {
        t: 'Paper', src: 'blog-kit-9-11.jpg', start: 9,
        srcset: 'img/blog-kit-9-11-384.jpg 384w, img/blog-kit-9-11-768.jpg 768w, img/blog-kit-9-11.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 1158,
        items: [
          { t: 'Arches watercolour block', d: 'French handmade paper, and the choice of a lot of professional watercolourists. 100% cotton, cold pressed, glued on all four sides. Very absorbent, stands up to scrubbing, easy to lift, and the colour sits beautifully.' },
          { t: 'Holbein White Ibis', d: 'A well-known Japanese watercolour block. Cotton pulp, good for graded washes and layering. Absorbs well and the price is sensible.' },
          { t: 'Baohong', d: 'A compact, light sketching block — the one I take out with me so I can paint wherever I happen to be.' }
        ]
      },
      {
        t: 'Drawing and erasing', src: 'blog-kit-12-13.jpg', start: 12,
        srcset: 'img/blog-kit-12-13-384.jpg 384w, img/blog-kit-12-13-768.jpg 768w, img/blog-kit-12-13.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 998,
        items: [
          { t: 'UNI mechanical pencil', d: 'No sharpening, and you can put whatever lead you like in it — HB, 2B, whatever the drawing needs.' },
          { t: 'Holbein kneaded eraser', d: 'For drafting on watercolour paper. A hard eraser rubbed over the surface too many times will wreck it. This one lifts the graphite without damaging the paper.' }
        ]
      },
      {
        t: 'Large brushes, for washes', src: 'blog-kit-14-17.jpg', start: 14,
        srcset: 'img/blog-kit-14-17-384.jpg 384w, img/blog-kit-14-17-768.jpg 768w, img/blog-kit-14-17.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 1156,
        items: [
          { t: 'Two-inch wool brush', d: 'Pure wool, holds an enormous amount of water, very soft.' },
          { t: 'Youngly 129 Orientalism, 1.5 inch', d: 'Mixed hair. Holds plenty of water and the bristles have some spring to them.' },
          { t: 'Da Vinci Cosmotop Mix B', d: 'A blend of sable, squirrel, black sable and synthetic fibre. Excellent water retention and a smooth stroke — this is the one for covering large areas.' },
          { t: 'Qiuhongzhai, "M" series', d: 'Squirrel mixed with wool. Springy and absorbent, and easy to control.' }
        ]
      },
      {
        t: 'Round and detail brushes', src: 'blog-kit-18-21.jpg', start: 18,
        srcset: 'img/blog-kit-18-21-384.jpg 384w, img/blog-kit-18-21-768.jpg 768w, img/blog-kit-18-21.jpg 1600w',
        sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 760px',
        w: 1600, h: 1076,
        items: [
          { t: 'Qiuhongzhai, "Dye" series', d: 'Squirrel mixed with raccoon dog. Round belly, absorbent and springy. It comes in a large and a small size — both are in the photo, which is why there are five brushes for four numbers.' },
          { t: 'Qiuhongzhai, "Ye Chan" series', d: 'High-grade wolf hair blended with wool. Extremely springy and thirsty. This is my hooking and line brush.' },
          { t: 'Macro Wave', d: 'Raccoon mixed with cowhide hair, wool and synthetic fibre. Good for trees, foliage and stone.' },
          { t: 'Phoenix', d: 'A long-tipped nylon brush. Inexpensive, and the best thing I own for long thin lines.' }
        ]
      }
    ],
    outro: 'If you are starting out, ignore most of this. Buy one decent block of paper, one good round brush and a small set of pans, and paint until you know what annoys you. What annoys you is what tells you which of these twenty-one to buy next.'
  },
  {
    id: 'stage-fright', layout: 'essay',
    title: 'How painting cured my stage fright',
    excerpt: 'I used to freeze in front of a room. Then I started painting in front of one.',
    img: 'blog-stage-fright.jpg', banner: 'blog-stage-fright.jpg',
    srcset: 'img/blog-stage-fright-192.jpg 192w, img/blog-stage-fright-384.jpg 384w, img/blog-stage-fright-768.jpg 768w, img/blog-stage-fright.jpg 2048w',
    sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 452px',
    w: 2048, h: 1332,
    tag: 'Journal', date: '2026-07-23', d: 'Jul 23', mins: 2,
    devnote: "This post has not been approved yet. Crystal supplied the text; it has been rewritten in the first person for publication. No claim has been changed. Two sentences are ours and are not in her original: “The first time I painted in front of a crowd I expected the same panic — and it never arrived” and “Neither of those was true about me ten years ago.” This note stays until she signs it off.",

    body: [
      'I am introverted, and not especially good in a room full of people. For most of my life, standing up to speak meant a pounding heart and a mind that went completely blank. Friends who knew me then still ask how on earth I ended up teaching.',
      'Live demonstrations are what changed it. The first time I painted in front of a crowd I expected the same panic — and it never arrived. Once I was inside the work, the room simply stopped existing. I could paint, explain what I was doing, and answer questions at the same time, and none of it made me nervous.',
      'Now invitations to speak turn up regularly, and I say yes to them. I like going somewhere I have never been. I like the hours spent making the thing. Neither of those was true about me ten years ago.',
      "Did art change me? I genuinely don't know. But painting has given me a great deal, and I am grateful for all of it."
    ],
    quote: 'Once I was inside the work, the room simply stopped existing.'
  },
  {
    id: 'painting-true-to-myself', layout: 'essay',
    title: 'Painting true to myself',
    excerpt: 'I spent months deciding what I should paint. All it produced was confusion.',
    img: 'blog-true-to-myself.jpg', banner: 'blog-true-to-myself.jpg',
    srcset: 'img/blog-true-to-myself-192.jpg 192w, img/blog-true-to-myself-384.jpg 384w, img/blog-true-to-myself-768.jpg 768w, img/blog-true-to-myself.jpg 1600w',
    sizes: '(max-width: 600px) 358px, (max-width: 1000px) 720px, 452px',
    w: 1600, h: 1200,
    tag: 'Journal', date: '2026-07-23', d: 'Jul 23', mins: 2,
    devnote: "This post has not been approved yet. Crystal supplied the text; it has been rewritten in the first person for publication. No claim has been changed. One clause is ours — “and I have stopped worrying about whether the body of work looks consistent” — and the pull-quote was tightened from “What is the point of creating when it goes against one's core?” to the line shown. This note stays until she signs it off.",

    body: [
      'A lot of artists pick one subject and stay with it. It builds a recognisable identity, and for plenty of people it works — you can see the results.',
      'I spent a long time trying to work out which subject that should be for me. What do people want to see? What tends to win prizes? I turned it over for months, and all it produced was confusion. Eventually I had lost sight of why I was painting at all.',
      'Painting is supposed to make me happy. That is the whole of it. So now I paint whatever I respond to, and I have stopped worrying about whether the body of work looks consistent. A style comes out of the practice on its own, in its own time — it isn’t something you can decide in advance.',
      'Following my instincts and working honestly is the only real conversation I have with myself. The person on the paper turns out to be the actual one.'
    ],
    quote: 'What is the point of making something if it goes against who you are?'
  }
];
