/* Real GSAP + catalogue controls: sorting/filtering must not strand transparent cards.
   The app's automation-only motion shortcut is removed in the loopback fixture. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const {chromium} = require('playwright');
const root = path.resolve(__dirname, '../wp-content/themes/hello-elementor-child');
const chip = value => `<button class="fopt" data-val="${value}">${value}</button>`;
function markup(kind) {
  const courses = kind === 'courses', prefix = courses ? 'lesson' : 'artwork';
  const sorts = courses ? ['featured','price-asc','price-desc'] : ['newest','oldest','price-asc','price-desc'];
  const prices = [290,95,270,170,245,195,160,90,175,180,155,null];
  const cards = prices.map((price,i) => `<article id="card-${i}" class="${courses?'lesson-cell':'art-card'}" data-date="${12-i}" data-price="${price===null?'':price}" data-medium="${i%2?'Pencil':'Watercolour'}" data-media="${i%2?'Pencil':'Watercolour'}" data-type="${i%2?'Dog portrait':'Cat portrait'}"><img loading="lazy" width="50" height="50" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='50' height='50'%3E%3Crect width='50' height='50' fill='tan'/%3E%3C/svg%3E">Artwork ${i}</article>`).join('');
  return `<div class="rail"><div id="${courses?'lesson-sort':'artwork-sort'}">${sorts.map(chip).join('')}</div><button id="${courses?'fp-clear':'artwork-filter-clear'}">Clear all</button><div class="fgroup" data-group="medium">${(courses?['all','Watercolour','Pencil']:['Watercolour','Pencil']).map(chip).join('')}</div>${courses?'':`<div class="fgroup" data-group="type">${['Cat portrait','Dog portrait'].map(chip).join('')}</div>`}<div id="${prefix}-price-ui"><span id="${prefix}-price-val" data-symbol="£"></span><input id="${prefix}-price-slider" type="range" min="0" max="300" value="300"></div></div><span id="${courses?'lesson-count':'store-count'}"></span><div id="active-tags"></div><div id="${courses?'lesson-grid':'store-grid'}" class="${courses?'lesson-grid':'store-grid'}">${cards}</div><div id="${courses?'no-results':'store-empty'}" style="display:none">No results</div>`;
}
const server = http.createServer((req,res) => {
  const u = new URL(req.url,'http://localhost');
  if(u.pathname.startsWith('/js/')) {
    const file = path.resolve(root,'.'+u.pathname);
    if(!file.startsWith(root+path.sep)||!fs.existsSync(file)){res.writeHead(404);res.end();return;}
    let source = fs.readFileSync(file,'utf8');
    if(u.pathname==='/js/cas-motion.js') {
      const shortcut = 'if (navigator.webdriver === true || /Headless/i.test(navigator.userAgent) || /[?&]nomotion=1/.test(location.search)) reduce = true;';
      assert.ok(source.includes(shortcut));
      source = source.replace(shortcut,'if (/[?&]nomotion=1/.test(location.search)) reduce = true;');
    }
    res.setHeader('content-type','application/javascript');res.end(source);return;
  }
  const kind=u.pathname.includes('courses')?'courses':'gallery';
  res.setHeader('content-type','text/html');
  res.end(`<!doctype html><html><head><title>Catalogue reveal regression</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0}.hero{height:650px}.rail{padding:8px}button{min-height:32px}.store-grid,.lesson-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}article{height:240px;background:#eee;border:1px solid #888}.hidden{display:none!important}main{padding:16px}.tail{height:1000px}@media(max-width:600px){.store-grid,.lesson-grid{grid-template-columns:1fr}}</style></head><body class="cas-root"><div class="hero">Gallery banner</div><main id="content">${markup(kind)}</main><div class="tail"></div>${u.searchParams.has('no-gsap')?'':'<script src="/js/vendor/gsap.min.js"></script><script src="/js/vendor/ScrollTrigger.min.js"></script>'}<script src="/js/cas-motion.js"></script><script src="/js/cas-${kind}.js"></script></body></html>`);
});
async function visibleCards(page) {
  return page.locator('article:not(.hidden)').evaluateAll(els => els.filter(el=>{
    const r=el.getBoundingClientRect();return r.top<innerHeight&&r.bottom>0;
  }).map(el=>({id:el.id,opacity:Number(getComputedStyle(el).opacity)})));
}
(async()=>{
  await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
  const browser=await chromium.launch({headless:true,...(process.env.CAS_CHROME?{executablePath:process.env.CAS_CHROME}:{}),args:['--no-sandbox','--disable-dev-shm-usage']});
  const cases=[],errors=[];
  try {
    for(const kind of ['gallery','courses']) for(const width of [1440,390]) for(const mode of ['motion','reduced','no-gsap']) {
      if(mode==='no-gsap'&&width===390)continue;
      const context=await browser.newContext({viewport:{width,height:1000},reducedMotion:mode==='reduced'?'reduce':'no-preference'}),page=await context.newPage();
      page.on('pageerror',e=>errors.push(e.message));
      await page.goto(`http://127.0.0.1:${server.address().port}/${kind}${mode==='no-gsap'?'?no-gsap=1':''}`);
      await page.waitForTimeout(mode==='motion'?1500:50);
      if(mode==='motion')assert.equal(await page.locator('article').first().evaluate(el=>el.__casM),1,'real shared motion is active');
      const prefix=kind==='courses'?'lesson':'artwork',sorts=kind==='courses'?['price-asc','price-desc','featured']:['oldest','price-asc','price-desc','newest'];
      for(const sort of sorts){
        await page.locator(`#${prefix}-sort [data-val="${sort}"]`).click();
        await page.waitForTimeout(mode==='motion'?1200:50);
        const visible=await visibleCards(page);
        if(process.env.CAS_QA_OUTPUT)await page.screenshot({path:path.join(process.env.CAS_QA_OUTPUT,`${kind}-${width}-${mode}-${sort}.png`)});
        assert.ok(visible.length>0,'some cards intersect the viewport');
        assert.ok(visible.every(c=>c.opacity>=.99),`${kind}/${width}/${mode}/${sort}: reordered cards remain visible: ${JSON.stringify(visible)}`);
        assert.equal(await page.locator('article:not(.hidden)').count(),12,'sorting preserves every card');
        const values=await page.locator('article').evaluateAll((els,sort)=>els.map(el=>sort==='oldest'||sort==='newest'?Number(el.dataset.date):(el.dataset.price===''?null:Number(el.dataset.price))),sort);
        if(sort!=='featured'){
          const expected=values.slice().sort((a,b)=>a===null?1:b===null?-1:sort==='price-asc'||sort==='oldest'?a-b:b-a);
          assert.deepEqual(values,expected,'date/price order is correct, unpriced last');
        }
      }
      await page.locator('.fgroup[data-group="medium"] [data-val="Watercolour"]').click();
      await page.waitForTimeout(100);
      assert.equal(await page.locator('article:not(.hidden)').count(),6);
      assert.ok((await visibleCards(page)).every(c=>c.opacity>=.99),'filtered cards are opaque');
      if(kind==='gallery'){
        await page.locator('.fgroup[data-group="type"] [data-val="Dog portrait"]').click();
        assert.equal(await page.locator('article:not(.hidden)').count(),0,'medium and type intersect');
        await page.locator('#active-tags [data-filter="type"]').click();
        assert.equal(await page.locator('article:not(.hidden)').count(),6,'removing a tag restores matches');
      }
      const slider=page.locator(`#${prefix}-price-slider`);
      await slider.fill('200');await slider.dispatchEvent('change');
      assert.equal(await page.locator('article:not(.hidden)').count(),3,'price and medium intersect');
      await page.locator(`#${kind==='courses'?'fp-clear':'artwork-filter-clear'}`).click();
      assert.equal(await page.locator('article:not(.hidden)').count(),12);
      for(const card of await page.locator('article').all()){
        await card.scrollIntoViewIfNeeded();await page.waitForTimeout(mode==='motion'?100:10);
        assert.equal(await card.evaluate(el=>Number(getComputedStyle(el).opacity)),1,'every restored card remains opaque after scrolling');
        await card.locator('img').evaluate(img=>img.decode());
      }
      cases.push({kind,width,mode});await context.close();
      console.log('PASS',kind,width,mode);
    }
    assert.deepEqual(errors,[]);console.log(JSON.stringify({cases:cases.length,pageErrors:errors}));
  }finally{await browser.close();await new Promise(resolve=>server.close(resolve));}
})().catch(e=>{console.error(e);process.exitCode=1;});
