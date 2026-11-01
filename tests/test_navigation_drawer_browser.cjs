/* Run with Playwright on NODE_PATH; all sessions/cart mutations below are synthetic. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const {execFileSync} = require('node:child_process');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '..');
const theme = path.join(root, 'wp-content/themes/hello-elementor-child');
const evidence = process.env.CAS_QA_OUTPUT;
if (evidence) fs.mkdirSync(evidence, {recursive: true});
const php = (fixture, input, extra = []) => execFileSync('php', [path.join(__dirname, 'fixtures', fixture), ...extra], {input: JSON.stringify(input), encoding: 'utf8'});
const state = {count: 1, failRead: false, failRemove: false, holdRead: false, pending: null, removals: 0};
const calls = [];
function reply(res, status, data) {
  res.writeHead(status, {'content-type': 'application/json', 'cache-control': 'private, no-store'});
  res.end(JSON.stringify(data));
}
const server = http.createServer(async (req, res) => {
  try {
    const url = new URL(req.url, 'http://localhost');
    if (url.pathname.startsWith('/theme/')) {
      const relative = url.pathname.slice('/theme/'.length);
      const file = path.resolve(theme, relative);
      if (!file.startsWith(theme + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {res.writeHead(404); res.end(); return;}
      res.setHeader('content-type', file.endsWith('.css') ? 'text/css; charset=utf-8' : file.endsWith('.js') ? 'text/javascript; charset=utf-8' : file.endsWith('.ttf') ? 'font/ttf' : file.endsWith('.png') ? 'image/png' : file.endsWith('.svg') ? 'image/svg+xml' : 'image/jpeg');
      res.end(fs.readFileSync(file)); return;
    }
    const cookie = Object.fromEntries((req.headers.cookie || '').split(';').map(x => x.trim().split('=')));
    const member = url.searchParams.has('member') ? url.searchParams.get('member') === '1' : cookie.member === '1';
    const guest = url.searchParams.has('guest') ? url.searchParams.get('guest') === '1' : cookie.guest === '1';
    const registration = url.searchParams.get('registration') === '1';
    if (url.pathname === '/ajax') {
      let text = ''; for await (const chunk of req) text += chunk;
      const post = Object.fromEntries(new URLSearchParams(text)); calls.push(post);
      if (post.action === 'cas_cart_count') {
        if (post.view === 'drawer' && state.holdRead) await new Promise(resolve => state.pending = resolve);
        if (post.view === 'drawer' && state.failRead) {reply(res, 503, {success: false, data: {known: false}}); return;}
        const result = JSON.parse(php('cart_count_harness.php', {uid: member ? 7 : 0, guest_enabled: guest, post,
          counts: {'7': state.count, '0': state.count}, titles: {'1': 'A native course'},
          prices: {'1': {regular_price: 20, sale_price: 12.5}}}, [path.join(theme, 'inc/cas-cart-count.php')]));
        reply(res, result.response.status, result.response); return;
      }
      if (post.action === 'tutor_delete_course_from_cart') {
        assert.equal(post._tutor_nonce, 'native-nonce'); assert.equal(post.course_id, '1');
        assert(member || guest); assert(!('user_id' in post) && !('cart_id' in post));
        if (state.failRemove) {reply(res, 400, {status_code: 400}); return;}
        state.count = 0; state.removals++;
        reply(res, 200, {status_code: 200, data: {cart_count: 0}}); return;
      }
      reply(res, 400, {}); return;
    }
    const header = php('header_navigation.php', {member, registration});
    const styles = ['industrial-1', 'industrial-2', 'industrial-3', 'cas-nav', 'cas-cart-drawer'].map(n => `<link rel="stylesheet" href="/theme/css/${n}.css">`).join('');
    const config = {cartUrl: '/cart/', loginUrl: '/login/', requiresLogin: !member && !guest, removeAction: 'tutor_delete_course_from_cart', removeNonce: 'native-nonce'};
    res.writeHead(200, {'content-type': 'text/html; charset=utf-8', 'set-cookie': [`member=${+member}; Path=/; SameSite=Lax`, `guest=${+guest}; Path=/; SameSite=Lax`]});
    res.end(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Navigation parity fixture</title>${styles}</head><body class="cas-root" data-theme="${url.searchParams.get('theme') || 'D'}">${header}<main id="content" class="container" style="min-height:1000px;padding-top:70px"><h1>Navigation parity fixture</h1><button id="outside">Outside navigation</button></main><script>window.casCartCount={ajaxUrl:'/ajax',nonce:'valid'};${url.searchParams.has('noDrawer') ? '' : 'window.casCartDrawer=' + JSON.stringify(config) + ';'}</script><script src="/theme/js/cas-site-extras.js"></script><script src="/theme/js/cas-header.js"></script><script src="/theme/js/cas-cart-drawer.js"></script></body></html>`);
  } catch (error) {console.error(error.stack); reply(res, 500, {error: 'Synthetic fixture failed'});}
});
async function waitFor(page, predicate) { await page.waitForFunction(predicate); }
(async () => {
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const base = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch({headless: true, ...(process.env.CAS_CHROME ? {executablePath: process.env.CAS_CHROME} : {})});
  const results = []; const errors = [];
  try {
    for (const width of [1440, 768, 390, 320]) for (const mode of ['A', 'D']) for (const persona of [
      {name: 'signed-out', member: 0, guest: 0, registration: 0},
      {name: 'member', member: 1, guest: 0, registration: 0},
      {name: 'guest', member: 0, guest: 1, registration: 0},
      {name: 'registration-open', member: 0, guest: 0, registration: 1},
    ]) {
      state.count = 1; state.failRead = false; state.failRemove = false;
      const context = await browser.newContext({viewport: {width, height: width === 1440 ? 1000 : 844}, reducedMotion: 'reduce'});
      await context.route('https://example.test/cover-*.jpg', route => route.fulfill({contentType: 'image/jpeg', body: fs.readFileSync(path.join(theme, 'img/hero-1-large.jpg'))}));
      const page = await context.newPage(); page.on('pageerror', e => errors.push(e.message));
      page.setDefaultTimeout(6000);
      const query = new URLSearchParams({...persona, theme: mode});
      const response = await page.goto(base + '/?' + query); assert.equal(response.status(), 200);
      assert.equal(await page.title(), 'Navigation parity fixture');
      assert.equal(await page.locator('main h1').textContent(), 'Navigation parity fixture');
      assert.equal(await page.locator('.nav-user').count(), persona.member ? 1 : 0);
      if (persona.member) assert(await page.locator('.nav-user').isVisible());
      assert.equal(await page.locator('a[href="/student-registration/"]').count(), persona.registration && !persona.member ? 1 : 0);
      if (width <= 1080) {
        await page.locator('.nav-burger').click();
        assert.equal(await page.locator('.nav-sheet').getAttribute('aria-hidden'), 'false');
        const account = page.locator('.nav-sheet a').filter({hasText: persona.member ? 'My studio' : persona.registration ? 'Log in / Sign up' : 'Log in'});
        assert(await account.isVisible());
        await page.keyboard.press('Escape'); assert.equal(await page.locator('.nav-burger').getAttribute('aria-expanded'), 'false');
        assert.equal(await page.locator('.nav-burger').evaluate(e => e === document.activeElement), true);
        assert.equal(await page.locator('.nav-sheet').evaluate(e => e.inert), true);
        await page.locator('.nav-burger').click(); await page.mouse.click(width - 10, 800);
        assert.equal(await page.locator('.nav-burger').getAttribute('aria-expanded'), 'false');
      }
      await page.evaluate(() => localStorage.setItem('cas-cart', '[{"title":"FAKE","qty":999,"price":1}]'));
      const before = page.url(); await page.locator('.cart-btn').click();
      assert.equal(page.url(), before); assert.equal(await page.locator('.mc-drawer').evaluate(e => e.inert), false);assert.notEqual(await page.locator('.mc-drawer').getAttribute('aria-hidden'), 'true');
      await page.getByRole('dialog', {name: 'Shopping cart'}).waitFor();
      const drawer = page.locator('.mc-drawer');
      assert(await drawer.evaluate(e => getComputedStyle(e).transitionDuration.split(',').every(value => parseFloat(value) <= 0.00001)));
      if (!persona.member && !persona.guest) {
        await page.getByText('Log in to view your basket.', {exact: true}).waitFor();
        assert.equal(await drawer.getByRole('link', {name: /^Log in →$/i}).getAttribute('href'), '/login/');
        assert.equal(await page.locator('.mc-line').count(), 0);
      } else {
        await page.locator('.mc-line').waitFor();
        assert.equal(await page.locator('.mc-name').textContent(), 'A native course');
        assert.equal(await page.locator('.mc-price .now').textContent(), '$12.50');
        assert.equal(await page.locator('.mc-sub b').textContent(), '$12.50');
        assert.equal(await drawer.getByRole('link', {name: /Go to checkout →/i}).getAttribute('href'), 'https://example.test/checkout/');
        assert.equal(await page.locator('.mc-qty').count(), 0);
      }
      await page.locator('.mc-close').focus(); await page.keyboard.press('Shift+Tab');
      assert.equal(await drawer.evaluate(e => e.contains(document.activeElement)), true);
      await page.keyboard.press('Tab'); assert.equal(await page.locator('.mc-close').evaluate(e => e === document.activeElement), true);
      if (evidence && persona.name === 'member') await page.screenshot({path: path.join(evidence, `drawer-${width}-${mode}.png`)});
      if (persona.member || persona.guest) {
        await drawer.getByRole('button', {name: 'Remove A native course'}).click();
        await drawer.locator('.mc-empty').getByText('Your basket is empty.', {exact: true}).waitFor();
        await waitFor(page, () => document.querySelector('.cart-badge').hidden);
        assert.equal(state.count, 0);
      }
      await page.keyboard.press('Escape'); assert.equal(await drawer.getAttribute('aria-hidden'), 'true');
      assert.equal(await page.locator('.cart-btn').evaluate(e => e === document.activeElement), true);
      assert.equal(await page.locator('body').evaluate(e => e.style.overflow), '');
      assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
      results.push({width, mode, persona: persona.name, passed: true}); console.log('PASS', width, mode, persona.name);await context.close();
    }
    // Coarse-pointer menus and basket actions stay reachable in short viewports.
    for (const [width, height] of [[320, 480], [844, 390], [568, 320]]) for (const mode of ['A', 'D']) {
      const mobile = await browser.newContext({viewport: {width, height}, reducedMotion: 'reduce', isMobile: true, hasTouch: true});
      const p = await mobile.newPage(); p.setDefaultTimeout(6000); p.on('pageerror', e => errors.push(e.message));
      await p.goto(base + '/?member=1&theme=' + mode); state.count = 1;
      for (const selector of ['.nav-brand','.nav-user','.mode-toggle','.cart-btn','.nav-burger']) {
        const target = await p.locator(selector).boundingBox(); assert(target.width >= 44 && target.height >= 44, selector + ' must remain a touch target');
      }
      await p.locator('.nav-burger').tap();
      const sheet = p.locator('.nav-sheet');
      assert((await sheet.boundingBox()).y + (await sheet.boundingBox()).height <= height + 1);
      await sheet.locator('a').last().scrollIntoViewIfNeeded();
      assert(await sheet.locator('a').last().evaluate(e => {const r=e.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight}));
      await p.keyboard.press('Escape');
      // A synthetic toolbar exercises the native administrator overlay offset without real account changes.
      await p.evaluate(() => {const bar=document.createElement('div');bar.id='wpadminbar';bar.style.cssText='position:fixed;top:0;left:0;width:100%;height:46px;z-index:99999';document.body.appendChild(bar);document.body.style.paddingTop='46px'});
      await p.locator('.cart-btn').tap(); await p.locator('.mc-line').waitFor();
      const box = await p.locator('.mc-drawer').boundingBox();
      assert.equal(box.y, 46); assert(box.y + box.height <= height + 1);
      await p.locator('.mc-drawer').evaluate(e => e.scrollTop=e.scrollHeight);
      const action = p.locator('.mc-footwrap [data-keep]');
      await action.scrollIntoViewIfNeeded();
      assert(await action.evaluate(e => {const r=e.getBoundingClientRect();return r.top>=46&&r.bottom<=innerHeight}));
      await action.tap(); assert.equal(await p.locator('.mc-drawer').getAttribute('aria-hidden'), 'true');
      await p.locator('.cart-btn').tap(); await p.locator('.mc-line').waitFor();
      assert.equal(await p.locator('.mc-drawer').evaluate(e => e.scrollTop), 0);
      await p.locator('.mc-close').tap(); assert.equal(await p.locator('.mc-drawer').getAttribute('aria-hidden'), 'true');
      results.push({width, height, mode, persona:'touch-member-toolbar', passed:true});
      console.log('PASS short touch viewport', width, height, mode); await mobile.close();
    }
    const context = await browser.newContext({viewport: {width: 390, height: 844}, reducedMotion: 'reduce'});
    const page = await context.newPage(); page.setDefaultTimeout(6000); page.on('pageerror', e => errors.push(e.message));
    await page.goto(base + '/?member=1'); state.count = 1; state.failRead = true;
    await page.locator('.cart-btn').click(); await page.getByText('Your basket could not be loaded.', {exact: false}).waitFor();
    assert.equal(await page.locator('.mc-empty').count(), 0); assert.equal(await page.locator('.mc-line').count(), 0);
    state.failRead = false; await page.getByRole('button', {name: /^Try again$/i}).click(); await page.locator('.mc-line').waitFor();
    state.failRemove = true; await page.getByRole('button', {name: 'Remove A native course'}).click();
    await page.getByText('The course could not be removed.', {exact: false}).waitFor(); assert.equal(await page.locator('.mc-line').count(), 1); assert.equal(state.count, 1);
    assert.equal(await page.locator('.mc-rm').isEnabled(), true); state.failRemove = false;
    await page.keyboard.press('Escape'); state.holdRead = true; await page.locator('.cart-btn').click();
    await waitFor(page, () => document.querySelector('.mc-list').getAttribute('aria-busy') === 'true');
    while (!state.pending) await new Promise(resolve => setTimeout(resolve, 10));
    await page.keyboard.press('Escape'); state.holdRead = false; state.pending(); state.pending = null;
    await page.waitForTimeout(150); assert.equal(await page.locator('.mc-drawer').getAttribute('aria-hidden'), 'true');
    assert.equal(await page.locator('.cart-btn').evaluate(e => e === document.activeElement), true);
    await page.goto(base + '/?member=1&noDrawer=1'); await page.locator('.cart-btn').click(); assert.equal(new URL(page.url()).pathname, '/cart/');
    await context.close();
    const animated = await browser.newContext({viewport: {width: 390, height: 844}, reducedMotion: 'no-preference'});
    const motionPage = await animated.newPage(); state.count = 0;
    await motionPage.goto(base + '/?member=1');
    await motionPage.locator('.nav-burger').click(); await motionPage.setViewportSize({width: 1440, height: 1000});
    await waitFor(motionPage, () => document.querySelector('.nav-sheet').inert);
    assert.equal(await motionPage.locator('.nav-burger').getAttribute('aria-expanded'), 'false');
    await motionPage.locator('.cart-btn').click();
    await motionPage.locator('.mc-empty').waitFor(); await motionPage.waitForTimeout(450);
    assert.equal(await motionPage.locator('.mc-drawer').evaluate(e => getComputedStyle(e).transitionDuration), '0.42s, 0s');
    assert.equal(await motionPage.locator('.mc-drawer').evaluate(e => e.getBoundingClientRect().top), 0);
    await motionPage.keyboard.press('Escape');
    assert.equal(await motionPage.locator('.cart-btn').evaluate(e => e === document.activeElement), true);
    await animated.close();
    assert.deepEqual(errors, []); assert(state.removals > 0);
    console.log(JSON.stringify({status: 'PASS',syntheticBrowserCases: results.length, nativeTutorIntegration: 'not executed; native API contract inspected separately', failuresAndStaleResponseChecked: true, normalAndReducedMotionChecked: true, removals: state.removals, pageErrors: errors}, null, 2));
    if (evidence) fs.writeFileSync(path.join(evidence, 'browser-results.json'), JSON.stringify({results, calls, pageErrors: errors}, null, 2));
  } catch (error) {
    if (evidence) {const pages = browser.contexts().flatMap(c => c.pages()); const last = pages.at(-1); if (last) {await last.screenshot({path: path.join(evidence, 'failure.png')}); fs.writeFileSync(path.join(evidence, 'failure-dom.txt'), await last.locator('body').innerHTML());}}
    throw error;
  } finally {if (state.pending) state.pending();await browser.close(); await new Promise(resolve => server.close(resolve));}
})().catch(error => {console.error(error.stack); server.close(); process.exitCode = 1;});
