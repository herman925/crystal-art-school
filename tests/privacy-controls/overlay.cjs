/* Regression: real privacy script, no WordPress account or external network. */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');
const root = path.resolve(__dirname, '../../wp-content/themes/hello-elementor-child');
const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/privacy.css"></head>
<body class="cas-root" style="--surface:#f6f0e4;--ink:#474138;--line:#ccc;--paper:white;--coral:#9a6436">
<main><details><summary>Cookie notes</summary><button class="cas-privacy-link" data-cas-privacy-open>Hidden accordion control</button></details>
<div class="stage" style="position:relative;height:240px"><iframe title="Test video" data-cas-media-src="https://video.example.test/player"></iframe></div></main>
<footer><button id="persistent" class="cas-privacy-link" data-cas-privacy-open>Privacy choices</button></footer>
<aside id="cas-cookie-banner" class="cas-cookie-banner ck-bar" hidden><div class="ck-in"><div>Cookies</div><div class="ck-acts"><button data-cas-privacy-reject>Essential only</button><button id="manage" data-cas-privacy-open>Manage</button><button data-cas-privacy-allow>Accept all</button></div></div></aside>
<div id="cas-privacy-modal" class="cas-privacy-modal ck-modal" role="dialog" aria-modal="true" aria-label="Privacy choices" hidden inert><div class="ck-modal-bg" data-cas-privacy-close></div><div class="ck-card"><div class="ck-card-in">
<button class="ck-x" data-cas-privacy-close>Close</button><h2>Privacy choices</h2><input type="checkbox" checked disabled aria-label="Essential"><label for="cas-privacy-media">External videos</label><input type="checkbox" id="cas-privacy-media"><input type="checkbox" disabled aria-label="Analytics"><input type="checkbox" disabled aria-label="Marketing">
<button data-cas-privacy-close>Cancel</button><button data-cas-privacy-save>Save choices</button></div></div></div><script src="/privacy.js"></script></body></html>`;
(async () => {
  const server = http.createServer((req, res) => {
    const file = req.url === '/privacy.js' ? 'js/cas-privacy.js' : req.url === '/privacy.css' ? 'css/cas-privacy.css' : null;
    res.setHeader('Content-Type', file ? (file.endsWith('.js') ? 'text/javascript' : 'text/css') : 'text/html');
    res.end(file ? fs.readFileSync(path.join(root, file)) : html);
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  let browser;
  try {
    browser = await chromium.launch({headless: true, executablePath: process.env.CAS_CHROME || undefined, args: ['--no-sandbox', '--disable-dev-shm-usage', '--no-zygote', '--single-process', '--disable-gpu']});
    const context = await browser.newContext({viewport: {width: 390, height: 844}, reducedMotion: 'reduce'});
    const errors = []; let requests = 0;
    await context.route('https://video.example.test/**', route => { requests++; return route.fulfill({contentType: 'text/html', body: '<button>Play</button>'}); });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(`http://127.0.0.1:${server.address().port}/`);
    await page.locator('.cas-media-choice').waitFor();
    assert.equal(requests, 0);
    await page.locator('#manage').click();
    await page.waitForFunction(() => document.activeElement.classList.contains('ck-x'));
    await page.keyboard.press('Shift+Tab');
    assert(await page.locator('[data-cas-privacy-save]').evaluate(node => node === document.activeElement));
    await page.keyboard.press('Escape');
    assert(await page.locator('#manage').evaluate(node => node === document.activeElement));
    assert.equal(await page.evaluate(() => localStorage.getItem('cas-privacy-v1')), null);
    await page.locator('#manage').click();
    await page.locator('[data-cas-privacy-save]').click();
    assert(await page.locator('#persistent').evaluate(node => node === document.activeElement));
    assert.equal(await page.evaluate(() => document.body.style.overflow), '');
    await page.locator('#persistent').click();
    await page.locator('#cas-privacy-media').check();
    await page.locator('[data-cas-privacy-save]').click();
    await page.frameLocator('iframe').getByRole('button', {name: 'Play'}).waitFor();
    assert.equal(requests, 1);
    await page.locator('#persistent').click();
    await page.locator('#cas-privacy-media').uncheck();
    await page.locator('[data-cas-privacy-save]').click();
    await page.locator('.cas-media-choice').waitFor();
    assert.equal(await page.locator('iframe').getAttribute('src'), null);
    await page.reload();
    await page.locator('.cas-media-choice').waitFor();
    assert.equal(requests, 1);
    assert.deepEqual(errors, []);
    console.log('PASS: privacy focus, hidden accordion exclusion, saved choices and video gating');
  } finally {
    if (browser) await browser.close();
    await new Promise(resolve => server.close(resolve));
  }
})().catch(error => { console.error(error.message); process.exitCode = 1; });
