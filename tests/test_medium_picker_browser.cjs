/* Real catalogue controls and native dialog; long lists are synthetic loopback QA data. */
const assert=require('node:assert/strict'),fs=require('node:fs'),path=require('node:path'),http=require('node:http');
const {chromium}=require('playwright');
const root=path.resolve(__dirname,'../wp-content/themes/hello-elementor-child');
const names=['Acrylic','Charcoal','Gouache','Graphite','Ink & wash','Oil','Pastel','Sketching','Watercolour'];
function html(kind,count,theme){
 const course=kind==='courses',media=names.slice(0,count),prefix=course?'lesson':'artwork';
 const value=(name,i)=>course?'m'+i:name;
 const chips=media.map((n,i)=>`<button class="fopt" data-val="${value(n,i)}" aria-pressed="false">${n.replace('&','&amp;')}</button>`).join('');
 const cards=media.map((n,i)=>`<article class="${course?'lesson-cell':'art-card'}" data-media="${value(n,i)}" data-medium="${value(n,i)}" data-date="${i}" data-price="20" data-difficulty="${i%2?'intermediate':'beginner'}">${n}</article>`).join('');
 const nav=`<div class="mega-cats"><a class="mega-cat is-all" href="/${kind}">All courses</a>${media.map((n,i)=>`<a class="mega-cat" href="/${kind}?cat=m${i}">${n.replace('&','&amp;')}</a>`).join('')}</div>`;
 const fields=`<div class="fgroup" data-group="medium"><div class="fg-title">Medium</div><div class="fg-opts">${course?'<button class="fopt active" data-val="all" aria-pressed="true">Any</button>':''}${chips}</div></div>`;
 return `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><title>Medium picker regression</title>${['industrial-1','industrial-2','industrial-3','cas-nav','cas-mobile-filters','cas-medium-picker'].map(n=>`<link rel="stylesheet" href="/css/${n}.css">`).join('')}<style>.mega-cats{padding:16px}.mega-cats a{margin:4px}.lesson-cell,.art-card{min-height:100px;border:1px solid #999}.hidden{display:none!important}</style></head><body class="cas-root" data-theme="${theme}">${nav}<main class="container" id="content"><h1>Courses</h1><div class="${course?'catalog-layout':'store-layout'}"><div class="filter-col"><aside class="filter-panel"><button id="${course?'fp-clear':'artwork-filter-clear'}">Clear all</button>${fields}</aside></div><section><div><span id="${course?'lesson-count':'store-count'}">${count} pieces</span><div id="active-tags"></div></div><div class="${course?'lesson-grid':'store-grid'}" id="${course?'lesson-grid':'store-grid'}">${cards}</div><div id="${course?'no-results':'store-empty'}" style="display:none">No results</div></section></div></main><script src="/js/cas-site-extras.js"></script><script src="/js/cas-${kind}.js"></script><script src="/js/cas-mobile-filters.js"></script><script src="/js/cas-medium-picker.js"></script></body></html>`;
}
const server=http.createServer((req,res)=>{
 const u=new URL(req.url,'http://localhost');
 if(/^\/(js|css)\//.test(u.pathname)){
  const p=path.resolve(root,'.'+u.pathname);if(!p.startsWith(root+path.sep)||!fs.existsSync(p)){res.writeHead(404);res.end();return;}
  res.setHeader('content-type',p.endsWith('.css')?'text/css':'application/javascript');res.end(fs.readFileSync(p));return;
 }
 res.setHeader('content-type','text/html');res.end(html(u.pathname.includes('gallery')?'gallery':'courses',Number(u.searchParams.get('n')||9),u.searchParams.get('theme')||'A'));
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));
 const b=await chromium.launch({headless:true,...(process.env.CAS_CHROME?{executablePath:process.env.CAS_CHROME}:{}),args:['--no-sandbox','--disable-dev-shm-usage']});
 const errors=[],cases=[];const base='http://127.0.0.1:'+server.address().port;
 try{
  for(const kind of ['courses','gallery'])for(const width of [1440,390])for(const theme of ['A','D']){
   const c=await b.newContext({viewport:{width,height:900}}),p=await c.newPage();p.on('pageerror',e=>errors.push(e.message));
   await p.goto(base+'/'+kind+'?theme='+theme);
   const field=p.locator('.fgroup[data-group="medium"]'),drawer=p.locator('#cas-mobile-filter-drawer');
   if(width===390)await p.getByRole('button',{name:kind==='courses'?'Filter courses':'Filter artwork',exact:true}).click();
   await field.getByRole('button',{name:'Choose Medium',exact:true}).click();
   const dialog=p.locator('.cas-medium-dialog[open]');await dialog.waitFor();
   const search=dialog.getByRole('searchbox',{name:'Search media'});
   await search.fill('WATER');assert.equal(await dialog.locator('.cas-medium-options label:visible').count(),1);
   await dialog.getByRole('checkbox',{name:'Watercolour',exact:true}).check();
   assert.equal(await p.locator('article:not(.hidden)').count(),1);
   await search.fill('Oil');await dialog.getByRole('checkbox',{name:'Oil',exact:true}).check();
   assert.equal(await p.locator('article:not(.hidden)').count(),kind==='courses'?1:2);
   await dialog.getByRole('button',{name:'Remove Medium: Oil',exact:true}).click();
   assert.equal(await p.locator('article:not(.hidden)').count(),kind==='courses'?9:1);
   await search.fill('ink &');assert.equal(await dialog.locator('.cas-medium-options label:visible').count(),1);
   await search.fill('no such medium');assert.ok(await dialog.getByText('No media match your search.',{exact:true}).isVisible());
   await dialog.getByRole('button',{name:'Clear selection',exact:true}).click();assert.equal(await p.locator('article:not(.hidden)').count(),9);
   for(let i=0;i<15;i++){await p.keyboard.press('Tab');assert.ok(await dialog.evaluate(el=>el.contains(document.activeElement)),'Tab remains in native modal');}
   await p.keyboard.press('Escape');assert.equal(await dialog.count(),0);
   if(width===390){assert.equal(await drawer.getAttribute('hidden'),null,'Escape closes picker and leaves filter drawer open');await p.locator('.cas-filter-close').click();}
   const nav=p.locator('.mega-cats');await nav.getByRole('button',{name:'Choose Medium',exact:true}).click();
   const navDialog=p.locator('.cas-medium-dialog[open]');await navDialog.getByRole('searchbox',{name:'Search media'}).fill('water');
   const link=navDialog.getByRole('link',{name:'Watercolour',exact:true});assert.ok((await link.getAttribute('href')).endsWith('?cat=m8'));
   await link.click();await p.waitForURL('**?cat=m8');
   if(kind==='courses')assert.equal(await p.locator('article:not(.hidden)').count(),1,'navigation selects the same Medium filter');
   cases.push({kind,width,theme});await c.close();console.log('PASS',kind,width,theme);
  }
  const c=await b.newContext(),p=await c.newPage();await p.goto(base+'/courses?n=6');assert.equal(await p.locator('.cas-medium-open').count(),0,'six media stay inline');await c.close();
  const nojs=await b.newContext({javaScriptEnabled:false,viewport:{width:390,height:844}}),p2=await nojs.newPage();await p2.goto(base+'/courses');assert.equal(await p2.locator('.fgroup .fopt:visible').count(),10,'no-JS fallback retains every choice');await nojs.close();
  assert.deepEqual(errors,[]);console.log(JSON.stringify({cases:cases.length,threshold:true,noJS:true,errors}));
 }finally{await b.close();await new Promise(r=>server.close(r));}
})().catch(e=>{console.error(e);process.exitCode=1;});
