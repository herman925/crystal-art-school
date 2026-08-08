"""Design round 2 verification — one browser seam, both themes.

Issue #16. A single browser pass over the eleven content pages, asserting only
what a visitor could observe: rendered text, where things sit on the screen,
which face a heading is drawn in, where a link points. Deliberately not class
names, DOM shape, or CSS property values, because any of those could be achieved
another way and none of them survives the WordPress migration at the end of
August. The rendered page is the only seam that does.

    python tools/verify-round-2.py

Starts its own server on an unused port and launches a fresh Chromium, so it does
not depend on anything already running. Exits non-zero if any assertion fails.

Two habits this file exists to enforce:

  * `wait_until='domcontentloaded'` plus a fixed wait, never `networkidle` —
    the Google Drive iframes never settle and networkidle simply times out.
  * a real browser, never an iframe — a cache-buster on an iframe's document URL
    does not bust its `<script src>`, which produced three false readings.
"""
import atexit
import subprocess
import sys
import time
import urllib.request
from pathlib import Path

from playwright.sync_api import sync_playwright

REPO = Path(__file__).resolve().parent.parent
PORT = 8907
BASE = 'http://localhost:%d/' % PORT
SETTLE_MS = 2300

# The mark's accessible name. Identifying it this way rather than by class is the
# point: a visitor finds it by what it is, and so does a screen reader.
LOGO2_ALT = 'Crystal Lau Art'

# path, where Logo2 should sit. §1.4.5: a page carrying the wood band puts the
# mark at the band's right-hand end; a page without one centres it.
CONTENT_PAGES = [
    ('index.html', 'centre'),
    ('Lessons.dc.html', 'right'),
    ('course.dc.html?id=clouds-beginner', 'centre'),
    ('lesson.dc.html?c=clouds-beginner', 'right'),
    ('Gallery.dc.html', 'right'),
    ('Showcase.dc.html', 'right'),
    ('Blog.dc.html', 'right'),
    ('blog-post.dc.html?id=watercolour-kit', 'centre'),
    # About gained the wooden banner the other section pages carry (client
    # feedback 2026-08-08), so its Logo2 moved from a centred block above the
    # content into the banner, where it sits right like every other banner page.
    ('About.dc.html', 'right'),
    ('FAQ.dc.html', 'right'),
    ('FreeVideos.dc.html', 'right'),
]

# §1.4.5 excludes Logo2 from the account and transaction pages.
EXCLUDED_PAGES = ['Login.dc.html', 'Signup.dc.html', 'Dashboard.dc.html', 'Checkout.dc.html']

# §7.4 / #14. Refund terms the client has not confirmed, plus the commercial claim
# that travelled with them.
UNCONFIRMED_COPY = ['14 days', '14-day', 'a quarter', '5 working days', 'Lifetime access']

DELETED_WORDMARK = "Crystal's Art School"


def serving():
    try:
        with urllib.request.urlopen(BASE + 'index.html', timeout=4) as r:
            return r.status == 200
    except Exception:
        return False


def start_server():
    if serving():
        return
    proc = subprocess.Popen([sys.executable, '-m', 'http.server', str(PORT)], cwd=str(REPO),
                            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    atexit.register(proc.terminate)
    for _ in range(24):
        if serving():
            return
        time.sleep(0.5)
    sys.exit('FATAL: nothing is serving on port %d' % PORT)


PROBE = r"""
(alt) => {
  const text = document.body.innerText || '';
  // §1.4.4 deleted the wordmark that sat beside the Lau. mark in the header. The
  // school's name in running prose — the cookie notice explains what the site is
  // — was never the wordmark and is not what the section removed, so this reads
  // the banner rather than the whole document.
  const banner = document.querySelector('header, [role="banner"], nav.site-nav');
  const bannerText = banner ? (banner.innerText || '') : '';

  // Where the mark sits, as a fraction of the viewport. A visitor reads position,
  // not the rule that produced it.
  const mark = [...document.images].find(i => i.alt === alt);
  let markAt = null, markLoaded = null;
  if (mark) {
    const r = mark.getBoundingClientRect();
    markAt = (r.left + r.width / 2) / window.innerWidth;
    markLoaded = mark.complete && mark.naturalWidth > 0;
  }

  // Is the heading actually drawn in Arizonia, or has it fallen through to the
  // serif behind it? Measure the same string in both stacks; a connected script
  // and a didone never set to the same width.
  const scriptHeads = [...document.querySelectorAll('h1, h2, h3, strong')]
    .filter(h => getComputedStyle(h).fontFamily.toLowerCase().includes('arizonia'));
  let faceVerdict = 'none on page';
  if (scriptHeads.length) {
    const probe = document.createElement('span');
    probe.textContent = (scriptHeads[0].textContent || 'Sample').slice(0, 30);
    probe.style.cssText = 'position:absolute;visibility:hidden;white-space:nowrap;font-size:60px';
    document.body.appendChild(probe);
    const widthIn = (stack) => { probe.style.fontFamily = stack; return probe.getBoundingClientRect().width; };
    const withScript = widthIn('Arizonia, "Bodoni Moda", Georgia, cursive');
    const without = widthIn('"Bodoni Moda", Georgia, cursive');
    probe.remove();
    faceVerdict = Math.abs(withScript - without) > 1 ? 'Arizonia' : 'FALLBACK';
  }

  // Anything a visitor could click through to Etsy, and anything that says a
  // link is still to come. The placeholders live in a hover overlay, so they are
  // invisible to innerText and have to be counted by their text content.
  const etsy = [...document.querySelectorAll('a[href]')]
    .map(a => a.href).filter(h => h.includes('etsy.com'));
  const buyPending = [...document.querySelectorAll('*')]
    .filter(e => e.children.length === 0 && e.textContent.trim() === 'Etsy link to come').length;

  return {
    text,
    bannerText,
    markAt,
    markLoaded,
    hasMark: !!mark,
    faceVerdict,
    etsy,
    etsySearch: etsy.filter(h => h.includes('/search')),
    buyPending,
    // §1.4.9 removed the standalone parts control; a visitor would see it as a
    // bar labelled "Parts" above the player.
    partsControl: [...document.querySelectorAll('button, [role="tablist"], div')]
      .some(e => e.children.length === 0 && e.textContent.trim() === 'Parts'),
    // What the page actually looks like. The <body> is transparent here; the
    // themed surface is the element carrying the theme, so read what a visitor
    // sees behind the content.
    surface: (() => {
      const el = document.querySelector('.cas-root') || document.body;
      return getComputedStyle(el).backgroundColor;
    })(),
  };
}
"""

HOME_CARDS = r"""
() => {
  // §1.4.7: the fact strip on each course card carries a lesson count and a
  // level, and no longer a total duration. Read it as a visitor does — the text
  // of the thing that links to the course.
  return [...document.querySelectorAll('a[href*="course.dc.html"]')]
    .map(el => (el.innerText || '').trim())
    .filter(t => /\blessons\b/i.test(t));
}
"""

failures = []
notes = []


def check(page_name, ok, message):
    if not ok:
        failures.append('%s: %s' % (page_name, message))


def run_page(browser, path, want_mark, theme):
    name = path.split('.')[0]
    label = '%s [%s]' % (name, theme)
    ctx = browser.new_context(viewport={'width': 1440, 'height': 900})
    # Theme A is reached the way a returning visitor reaches it: the preference
    # they set last time.
    ctx.add_init_script("localStorage.setItem('cas-theme', %r);" % theme)
    if path.startswith('Dashboard'):
        ctx.add_init_script(
            "localStorage.setItem('cas-user', JSON.stringify("
            "{name:'Verification', email:'verify@example.com', tier:'Voyager'}));")
    page = ctx.new_page()
    errors = []
    page.on('pageerror', lambda e: errors.append(str(e)))
    try:
        page.goto(BASE + path, wait_until='domcontentloaded', timeout=30000)
        page.wait_for_timeout(SETTLE_MS)
        r = page.evaluate(PROBE, LOGO2_ALT)

        # 1 — the wordmark is gone (§1.4.4)
        check(label, DELETED_WORDMARK not in r['bannerText'],
              'the deleted wordmark is still rendered in the header')

        # 2 / 3 — where Logo2 sits, and where it must not appear (§1.4.5)
        if want_mark == 'absent':
            check(label, not r['hasMark'], 'Logo2 appears on a page that excludes it')
        else:
            check(label, r['hasMark'], 'Logo2 is missing')
            if r['hasMark']:
                check(label, r['markLoaded'], 'the Logo2 image did not load')
                at = r['markAt']
                if want_mark == 'centre':
                    check(label, 0.44 < at < 0.56, 'Logo2 sits at %.2f of the width, not centred' % at)
                else:
                    check(label, at > 0.60, 'Logo2 sits at %.2f of the width, not at the right' % at)

        # 6 — unconfirmed refund terms (§7.4)
        for phrase in UNCONFIRMED_COPY:
            check(label, phrase not in r['text'], 'unconfirmed copy on the page: %r' % phrase)

        # 7 — the named headings are drawn in Font A, not the serif behind it (§2.2)
        check(label, r['faceVerdict'] != 'FALLBACK', 'a Font A heading fell back to the serif')

        # 5 — the standalone parts control is gone (§1.4.9)
        check(label, not r['partsControl'], 'the standalone Parts control is still rendered')

        # 9 — Buy links point at listings, not a search query (§9.3)
        check(label, not r['etsySearch'],
              'Buy link is a search query: %s' % (r['etsySearch'][:1] or ''))
        shopFront = [h for h in r['etsy'] if h.rstrip('/').endswith('etsy.com')]
        if shopFront:
            notes.append('%s: %d Buy links open the Etsy shop front rather than a listing — '
                         'blocked on the client for per-artwork URLs (#17)' % (label, len(shopFront)))
        if r['buyPending']:
            notes.append('%s: %d Buy buttons are inert placeholders (#17)' % (label, r['buyPending']))

        # 8 — home card fields (§1.4.7)
        if path == 'index.html':
            cards = page.evaluate(HOME_CARDS)
            check(label, bool(cards), 'no course card exposes a lesson count')
            for t in cards:
                check(label, 'LEVEL' in t.upper(), 'a course card shows no level: %r' % t[:60])
                has_duration = any(unit in t.lower() for unit in (' hrs', ' hours', 'h total', ' hr '))
                check(label, not has_duration, 'a course card still shows a total duration: %r' % t[:60])

        check(label, not errors, 'page error: %s' % (errors[0][:100] if errors else ''))
        return r
    except Exception as exc:
        failures.append('%s: LOAD FAILED %s' % (label, str(exc)[:120]))
        return None
    finally:
        ctx.close()


def run_theme_fallback(browser):
    """§1.4.6 — only two themes exist. A URL asking for a third must still render
    one of the two, not a half-styled page."""
    baselines = {}
    for theme in ('D', 'A'):
        ctx = browser.new_context(viewport={'width': 1440, 'height': 900})
        ctx.add_init_script("localStorage.setItem('cas-theme', %r);" % theme)
        page = ctx.new_page()
        page.goto(BASE + 'index.html', wait_until='domcontentloaded')
        page.wait_for_timeout(SETTLE_MS)
        baselines[theme] = page.evaluate(
            "() => getComputedStyle(document.querySelector('.cas-root') || document.body).backgroundColor")
        ctx.close()

    if baselines['A'] == baselines['D']:
        failures.append('themes: A and D render identically, so this check proves nothing')

    for bogus in ('B', 'C'):
        ctx = browser.new_context(viewport={'width': 1440, 'height': 900})
        page = ctx.new_page()
        page.goto(BASE + 'index.html?theme=' + bogus, wait_until='domcontentloaded')
        page.wait_for_timeout(SETTLE_MS)
        got = page.evaluate(
            "() => getComputedStyle(document.querySelector('.cas-root') || document.body).backgroundColor")
        check('themes', got in baselines.values(),
              '?theme=%s rendered %s, which is neither of the two themes' % (bogus, got))
        ctx.close()
    return baselines


def check_light_theme_invert():
    """The light theme inverts the brand marks with `filter: brightness(0)`. That
    is right for a cut-out and wrong for the opaque files the client actually
    uploaded — on those it paints a black slab — so the two rules are commented
    out while her raw artwork is in place. The reasoning is in
    docs/20260808_Transparency_Work_Not_In_Scope.md (gitignored).

    Re-enabling them is a step nobody will remember, so the trigger fires by
    itself: the moment a shipped mark carries real transparency this fails until
    the rules are uncommented, and if the rules are ever live over an opaque mark
    it fails the other way."""
    marks = ['img/lau-logo.png', 'img/logo2.png']
    try:
        from PIL import Image
    except ImportError:
        return                      # no Pillow, no opinion

    transparent = []
    for rel in marks:
        p = REPO / rel
        if not p.exists():
            continue
        im = Image.open(p).convert('RGBA')
        hist = im.getchannel('A').histogram()
        if hist[0] / float(im.size[0] * im.size[1]) > 0.01:
            transparent.append(rel)

    css = (REPO / 'css' / 'industrial-1.css').read_text(encoding='utf-8').splitlines()
    live = [l for l in css if l.startswith('[data-theme="A"]') and 'brightness(0)' in l]
    off = [l for l in css if l.startswith('/* [data-theme="A"]') and 'brightness(0)' in l]

    if transparent and not live:
        failures.append(
            'light-theme invert: %s now carry transparency, so the two '
            '[data-theme="A"] brightness(0) rules in css/industrial-1.css have to be '
            'uncommented — without them the light theme puts a cream mark on cream'
            % ', '.join(transparent))
    elif not transparent and live:
        failures.append(
            'light-theme invert: the brightness(0) rules are live but the marks are '
            'opaque, so each one paints a black rectangle. Comment them out again.')
    elif not transparent and off:
        notes.append('light-theme invert is off while the brand marks are the '
                     'client’s own opaque uploads — correct for now, and this check '
                     'fails the moment transparent artwork lands (#17)')


def main():
    start_server()
    with sync_playwright() as p:
        browser = p.chromium.launch()
        for theme in ('D', 'A'):
            for path, want in CONTENT_PAGES:
                run_page(browser, path, want, theme)
            for path in EXCLUDED_PAGES:
                run_page(browser, path, 'absent', theme)
        baselines = run_theme_fallback(browser)
        browser.close()

    check_light_theme_invert()

    print('themes reachable: %s' % ', '.join('%s=%s' % kv for kv in sorted(baselines.items())))
    print('pages checked: %d content + %d excluded, in both themes'
          % (len(CONTENT_PAGES), len(EXCLUDED_PAGES)))
    for n in sorted(set(notes)):
        print('NOTE  ' + n)
    if failures:
        print('\nFAIL (%d)' % len(failures))
        for f in failures:
            print('  ' + f)
        return 1
    print('\nPASS — every assertion holds on every page, in both themes')
    return 0


if __name__ == '__main__':
    sys.exit(main())
