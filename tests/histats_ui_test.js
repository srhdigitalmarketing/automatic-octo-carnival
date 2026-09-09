const fs = require('fs');
const assert = require('node:assert/strict');
const { chromium } = require('playwright');
const code = fs.readFileSync('tests/fixtures/histats-async.html','utf8');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  for(const scenario of ['normal','blocked','slow']) {
   const page=await browser.newPage();let requests=0;const errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   let release;const gate=new Promise(resolve=>{release=resolve});
   await page.route('https://s10.histats.com/**',async route=>{
    requests++;
    if(scenario==='slow')await gate;
    if(scenario==='blocked')return route.abort();
    return route.fulfill({contentType:'application/javascript',body:'window.histatsLoaded=true;'});
   });
   await page.route('https://example.test/**',route=>route.fulfill({contentType:'text/html',body:`<button onclick="this.textContent='Playing'">Play</button>${code}`}));
   await page.goto('https://example.test/play/test',{waitUntil:'domcontentloaded'});
   try {
    await page.getByRole('button').click();assert.equal(await page.getByRole('button').textContent(),'Playing');
    await page.waitForFunction(()=>Array.isArray(window._Hasync)&&window._Hasync.length===3);
    assert.equal(await page.locator('script[src*="histats.com"]').evaluate(s=>s.async),true);
    assert.deepEqual(errors,[]);
   } finally {release();}
   await page.waitForLoadState('load');
   assert.equal(requests,1);
   await page.close();console.log('PASS HiStats '+scenario);
  }
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1});
