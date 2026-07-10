/* Crystal's Art School — shared journal data.
   Loaded on index.html, Blog.dc.html and blog-post.dc.html so the "most read"
   ordering is identical everywhere. `reads` is the display-only read counter
   (client request: home page shows the 4 most-read posts, never randomised).
   `layout` picks the article template on blog-post.dc.html:
     notes | essay | list | steps | photo | qa                                */
window.CAS_BLOG = [
  {
    id: 'block-in-portrait', layout: 'notes',
    title: 'How to block in a portrait — value first',
    excerpt: 'Ignore the features for the first hour. Big shapes, three values — the shortcut that looks like a delay.',
    img: 'cover1.jpg', banner: 'Bannerphoto.jpg', tag: 'Practice', date: '2026-06-08', d: 'Jun 08', reads: 24100, mins: 6
  },
  {
    id: 'five-brushes', layout: 'list',
    title: 'Five brushes worth owning',
    excerpt: 'The short list of tools that actually change how you paint — and which ones to skip.',
    img: 'BrushA.jpg', tag: 'Tools', date: '2026-06-19', d: 'Jun 19', reads: 18300, mins: 4,
    intro: 'Every beginner buys too many brushes. I did. Here are the five that survived eight years of teaching — and the honest reason each one earns its place on the desk.',
    items: [
      { t: 'A size 10 round, natural hair', d: 'One brush that does eighty percent of a watercolour. A good round holds a wash in its belly and still comes to a point — buy the best you can afford here and economise everywhere else.' },
      { t: 'A one-inch flat', d: 'For skies, grounds, and any shape bigger than a coin. The flat forces you to think in planes instead of wobbling around edges with a small brush.' },
      { t: 'A cheap bristle brush you can ruin', d: 'Scrubbing, lifting, dry brush over rough paper. Every studio needs one brush nobody feels sorry for.' },
      { t: 'A rigger', d: 'The long thin one. Branches, rigging, whiskers, signatures. Nothing else makes that line.' },
      { t: 'A size 4 round for the last ten minutes', d: 'Details are the reward, not the meal. Keeping the small brush physically out of reach until the end is the cheapest discipline there is.' }
    ]
  },
  {
    id: 'beginner-mistakes', layout: 'list',
    title: "Five things I wish I'd known as a beginner",
    excerpt: "The little habits that would have saved me a year of muddy paintings.",
    img: 'Postercolor.jpg', tag: 'Practice', date: '2026-02-18', d: 'Feb 18', reads: 21700, mins: 5,
    intro: 'None of these are techniques. They are habits — the boring, repeatable kind — and each one would have saved me months. I tell every new student the same five things.',
    items: [
      { t: 'Muddy colour is a value problem', d: 'Nine times out of ten the mix is fine — it is sitting next to the wrong value. Fix the neighbour, not the puddle.' },
      { t: 'Finish more, polish less', d: 'Ten finished mediocre paintings teach more than one endlessly reworked "good" one. Volume is the teacher.' },
      { t: 'Draw the envelope first', d: 'The outer shape of the whole subject, checked twice, before anything inside it. Everything hangs off that decision.' },
      { t: 'Stand up, step back', d: 'Every fifteen minutes, three metres back. The painting you see from your chair is not the painting anyone else will ever see.' },
      { t: 'Keep the failures', d: 'Date them and keep them in a drawer. In six months they are the only proof of progress you will believe.' }
    ]
  },
  {
    id: 'three-greys', layout: 'steps',
    title: 'Three greys, endless range',
    excerpt: 'Warm and cool greys, and the quiet power of a limited value palette.',
    img: 'BrushB.jpg', tag: 'Colour', date: '2026-03-24', d: 'Mar 24', reads: 15200, mins: 5,
    intro: 'Most of nature is grey — warm grey, cool grey, grey pretending to be a colour. Mix these three strings once a week and your paintings quiet down immediately.',
    materials: 'Ultramarine blue · burnt sienna · titanium white · a palette knife and a clean patch of palette.',
    items: [
      { t: 'Mix the mother grey', d: 'Ultramarine and burnt sienna in roughly equal parts until neither wins. You should not be able to name the colour. That neutrality is the whole point.' },
      { t: 'Pull a warm string', d: 'Take half the pile, tip it toward sienna, then lighten it in three steps with white. Label the swatches — warm 1, 2, 3.' },
      { t: 'Pull a cool string', d: 'Other half toward the blue, same three steps. Six swatches now sit on your palette: a complete lighting kit.' },
      { t: 'Paint a study with nothing else', d: 'One object, one hour, only the six greys. Notice how "colourful" it still reads — that is value doing the work colour usually gets credit for.' }
    ]
  },
  {
    id: 'earthy-neutrals', layout: 'steps',
    title: 'Mixing earthy neutrals',
    excerpt: 'Walnut, oak, iron — building the warm-neutral palette of a workshop from three tubes.',
    img: 'Liquidwatercolor.jpg', tag: 'Colour', date: '2026-06-04', d: 'Jun 04', reads: 12900, mins: 4,
    intro: 'The palette of this studio — plaster, oak, walnut, iron — comes out of three tubes. This is the exact recipe, in the order I mix it every morning.',
    materials: 'Yellow ochre · burnt umber · ultramarine blue · white (gouache or oil, the logic is the same).',
    items: [
      { t: 'Oak', d: 'Yellow ochre with a whisper of umber and plenty of white. Stop while it still looks like sunlight on wood, not mustard.' },
      { t: 'Walnut', d: 'Burnt umber knocked back with a touch of ultramarine so it goes deep instead of orange. This is your darkest warm.' },
      { t: 'Iron', d: 'Ultramarine and umber pushed almost to black, cooled with the tiniest bit more blue. Use it where you would reach for the black tube.' },
      { t: 'Plaster', d: 'White warmed with the leftover oak puddle. Every "white" wall in my paintings is this mix — pure white stays in the highlights only.' }
    ]
  },
  {
    id: 'colour-journal', layout: 'qa',
    title: 'Why I keep a colour journal',
    excerpt: "A little notebook of mixes I've tried — not for anyone else, just for me.",
    img: 'Sketchbook.jpg', tag: 'Practice', date: '2026-03-02', d: 'Mar 02', reads: 11400, mins: 4,
    intro: 'Students see the battered notebook on my desk and ask the same questions about it, so here they are, answered honestly.',
    items: [
      { q: 'What actually goes in it?', a: 'Swatches with the recipe written underneath — pigments, rough ratio, what it was for. A failed mix gets a page as readily as a good one. Failures are the useful ones.' },
      { q: 'Why not just remember?', a: 'Because you will not. Colour memory is astonishingly bad. The journal is not nostalgia, it is a lookup table — "the grey from the harbour painting" takes ten seconds to find and would take an hour to rediscover.' },
      { q: 'How often do you use old pages?', a: 'Weekly. Before any commission I flip through the last year of pages the way a cook flips through a recipe box. Half my palette decisions are made before the easel is even up.' },
      { q: 'Should beginners keep one?', a: 'From day one. One page per session, three swatches minimum. In a year you own a book no shop could sell you — a record of your own eye getting better.' }
    ]
  },
  {
    id: 'oil-vs-poster', layout: 'qa',
    title: 'Oil vs poster colour',
    excerpt: 'Which to start with, and why it barely matters as much as you think.',
    img: 'cover2.jpg', tag: 'Colour', date: '2026-04-12', d: 'Apr 12', reads: 8900, mins: 4,
    intro: 'The question I am asked most in the first lesson, in the form I am usually asked it.',
    items: [
      { q: 'Which is more forgiving?', a: 'Poster colour, easily. It is opaque, cheap, dries in minutes and repaints without guilt. Every mistake costs you nothing but the ten minutes it took.' },
      { q: 'So why does anyone start with oils?', a: 'Time. Oil stays open for hours, so you can push paint around instead of racing it. Blending, soft edges, changing your mind — oil forgives slowness the way poster colour forgives error.' },
      { q: 'Which teaches better habits?', a: 'They teach different ones. Poster colour teaches commitment — you must decide and place. Oil teaches patience. A student who does a term of each comes out stronger than a purist.' },
      { q: 'Your honest recommendation?', a: 'Start with whichever is already in the drawer. The medium matters far less than the mileage. The paint does not know it is being learned on.' }
    ]
  },
  {
    id: 'unhurried-sketchbook', layout: 'photo',
    title: 'The unhurried sketchbook',
    excerpt: 'Building a daily habit without the pressure of making anything good.',
    img: 'cover3.jpg', tag: 'Practice', date: '2026-05-18', d: 'May 18', reads: 9700, mins: 3,
    intro: 'A sketchbook is not a portfolio. Mine is proof — pages from one ordinary week, shown exactly as they happened, including the bad ones.',
    items: [
      { src: 'Sketchbook.jpg', cap: 'Monday — ten minutes, the mug on the windowsill. The wobbly ellipse stays.' },
      { src: 'cover3.jpg', cap: 'Wednesday — a field remembered from the bus. Memory drawings are always braver.' },
      { src: 'Rubber.jpg', cap: 'Friday — nothing to say, so I drew the eraser. Some days the habit *is* the achievement.' }
    ],
    outro: 'One page a day, no standards, no audience. The habit compounds; the quality follows on its own schedule, uninvited.'
  },
  {
    id: 'reading-light', layout: 'photo',
    title: 'Reading light in a room',
    excerpt: 'Notes from late evenings by the window, and what shadows tell you about form.',
    img: 'studio.jpg', tag: 'Practice', date: '2026-04-30', d: 'Apr 30', reads: 5300, mins: 3,
    intro: 'Before you can paint light you have to notice it. Three photographs from the studio, one lesson in each.',
    items: [
      { src: 'studio.jpg', cap: 'North window, 6pm — one light source means one family of shadows. Squint: everything is either lit or not.' },
      { src: 'cover1.jpg', cap: 'The same head, lamp moved half a metre — the likeness survives, the mood does not. Light is the subject.' },
      { src: 'BrushA.jpg', cap: 'Brushes against the wall — the shadow shape describes the wall better than the wall does.' }
    ],
    outro: 'Homework: one photograph of your own room each evening this week, same corner, different hour. Paint none of them. Just look.'
  },
  {
    id: 'my-studio-honestly', layout: 'photo',
    title: 'My studio, honestly — plaster, wood and mess',
    excerpt: 'A wander around the small room where everything actually gets made.',
    img: 'BrushB.jpg', tag: 'Studio', date: '2026-02-06', d: 'Feb 06', reads: 8200, mins: 3,
    intro: 'Studio tours online are suspiciously tidy. This one is not. Three corners of the real room, dust included.',
    items: [
      { src: 'studio.jpg', cap: 'The easel corner — north light, a stool I keep meaning to fix, and the good silence.' },
      { src: 'BrushB.jpg', cap: 'The brush jar. Cleaning happens on Fridays. Sometimes.' },
      { src: 'Bannerphoto.jpg', cap: 'The worktable mid-lesson — this is what "organised" looks like by 4pm.' }
    ],
    outro: 'You do not need a beautiful studio. You need a door you can close and a chair you return to. Everything in this room earns its place or leaves.'
  },
  {
    id: 'slow-looking', layout: 'essay',
    title: 'Slow looking: a warm-up',
    excerpt: 'Ten quiet minutes before you draw anything at all. A small ritual that pays off.',
    img: 'Sketchbook.jpg', tag: 'Practice', date: '2026-06-12', d: 'Jun 12', reads: 6200, mins: 4,
    body: [
      'Before the first mark, I sit with the subject for ten minutes and draw nothing. Students find this excruciating, then indispensable — usually in that order, usually within a month.',
      'The rules are simple. Phone in the other room. Eyes on the subject. You are looking for three things only: where the light comes from, which shape is biggest, and where the darkest dark meets the lightest light. Everything else is detail, and detail is a trap you can walk into later with your eyes open.',
      'What changes is not the drawing — it is the order of decisions. Ten minutes of looking means the first mark lands with intention instead of hope. The warm-up is not before the work. It is the work.'
    ],
    quote: 'The first mark should be the hundredth decision, not the first.'
  },
  {
    id: 'paper-care', layout: 'essay',
    title: 'Caring for your paper',
    excerpt: 'Storage, tooth, and why cold-press matters more than the brand on the block.',
    img: 'Postercolor.jpg', tag: 'Tools', date: '2026-05-27', d: 'May 27', reads: 4800, mins: 3,
    body: [
      'Paper is the cheapest thing you will ever blame. Before you switch brands again, check three boring things: how it is stored, which side you are on, and whether the tooth suits the brush you actually use.',
      'Storage first — flat, dry, away from the window. Paper that has lived in humidity buckles under the first wash no matter what it cost. The kettle and the paper drawer should not share a room.',
      'Tooth second. Cold-press is the honest middle: enough texture to hold a wash, smooth enough for detail. Rough paper flatters loose painters and punishes tight ones; hot-press is the reverse. Most "I hate this paper" complaints are a mismatch, not a defect. Buy one sheet of each, cut them into quarters, and run the same small study across all three. The paper will tell you which painter you are.'
    ],
    quote: 'Paper is the cheapest thing you will ever blame.'
  },
  {
    id: 'framing-student-work', layout: 'essay',
    title: 'Framing student work',
    excerpt: 'How we hang the studio wall — mats, bevels and the small mistakes to avoid.',
    img: 'cover1.jpg', tag: 'Studio', date: '2026-05-09', d: 'May 09', reads: 3400, mins: 3,
    body: [
      'Nothing changes how a student sees their own work like putting a mat around it. The painting they were apologising for suddenly looks intentional — because framing is a statement that someone decided this was finished.',
      'The studio rules: mats always warm white, never bright white, which turns paper grey by comparison. Bevel cut wide at the bottom — the eye reads equal margins as bottom-heavy. And the frame quieter than the work; if a visitor compliments the frame, the frame is wrong.',
      'The mistake to avoid is waiting for a masterpiece. Frame the block-in, the study, the honest failure with one good passage. The wall is not a trophy case — it is a record of the work being taken seriously, which is the thing that keeps it going.'
    ],
    quote: 'Framing is a statement that someone decided this was finished.'
  },
  {
    id: 'eraser-as-brush', layout: 'essay',
    title: 'The eraser as a brush',
    excerpt: 'Subtractive drawing: lifting light back out of a toned ground.',
    img: 'Rubber.jpg', tag: 'Tools', date: '2026-03-11', d: 'Mar 11', reads: 5900, mins: 3,
    body: [
      'Tone the whole sheet with graphite until it hums a middle grey. Now you own two directions: pencil pushes darker, eraser pulls lighter. Half your students will never draw the old way again.',
      'A kneaded rubber shaped to a wedge lifts a highlight the way a brush lays one down — with pressure, angle and intent. The plastic eraser cuts hard edges; the kneaded one breathes soft ones. You are not correcting any more. You are painting in reverse.',
      'The deeper lesson is psychological. On white paper every mark is a commitment and beginners freeze. On a toned ground the drawing is already "wrong" everywhere, so there is nothing to protect — and the freeze never comes.'
    ],
    quote: 'You are not correcting any more. You are painting in reverse.'
  },
  {
    id: 'sharpening', layout: 'steps',
    title: 'Sharpening, the right way',
    excerpt: 'A knife, a block, and two minutes — the only pencil-point method you need.',
    img: 'cover4.jpg', tag: 'Tools', date: '2026-04-21', d: 'Apr 21', reads: 7600, mins: 3,
    intro: 'The barrel sharpener makes a point for writing. Drawing wants a long collar of exposed graphite — and that takes a knife. Two minutes, four moves.',
    materials: 'A craft knife with a fresh blade · a sanding block · a pencil you like.',
    items: [
      { t: 'Cut the wood, not the lead', d: 'Blade angled low, thumb behind the cut, shaving the cedar away in long strokes. You are aiming for a centimetre of exposed graphite.' },
      { t: 'Rotate as you go', d: 'A quarter-turn per stroke keeps the collar even. Hurrying here is how points snap later.' },
      { t: 'Shape on the block', d: 'Drag the exposed lead across the sanding block at a shallow angle, rotating. A long conical point for lines; flatten one side for broad tonal strokes.' },
      { t: 'Wipe before you draw', d: 'Graphite dust on the point becomes a smear on the paper. One pass on a rag. Done — and the point lasts an hour, not five minutes.' }
    ]
  },
  {
    id: 'studio-worth-returning', layout: 'essay',
    title: 'A studio worth returning to',
    excerpt: 'Concrete, plants, lamplight — designing a calm room you actually want to work in.',
    img: 'ProfilePic.jpg', tag: 'Studio', date: '2026-04-02', d: 'Apr 02', reads: 6100, mins: 4,
    body: [
      'The best studio is not the best-equipped one. It is the one your feet take you to without an argument. Designing that room is a painting problem: values, warmth, and what you choose to leave out.',
      'Keep the walls quiet — plaster, concrete, wood — so the work is the loudest thing in the room. One good lamp beats four bright ones; shadows are information and flat light kills them. A plant, because a room with something alive in it is a room you tend rather than visit.',
      'And leave the easel loaded. A blank canvas at the door is a threshold; a half-finished painting is an invitation. The room should always be mid-sentence when you walk in — finishing the sentence is how tomorrow starts itself.'
    ],
    quote: 'The room should always be mid-sentence when you walk in.'
  }
];
