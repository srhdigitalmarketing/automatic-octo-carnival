const fs = require('fs');
const assert = require('node:assert/strict');
const {chromium} = require('playwright');
const loader = fs.readFileSync('public/js/google-analytics.js', 'utf8');
(async () => {
 const browser = await chromium.launch({channel:'msedge', headless:true});
 try {
  for (const scenario of ['normal','duplicate','blocked','existing','invalid']) {
   const page = await browser.newPage(); let requests=0; const errors=[];
   page.on('pageerror', error => errors.push(error.message));
   await page.route('https://www.googletagmanager.com/**', async route => { requests++; if (scenario === 'blocked') await route.abort(); else await route.fulfill({contentType:'application/javascript',body:'window.gaLoaded = true;'}); });
   await page.route('https://example.test/**', route => route.fulfill({contentType:'text/html',body:`<button onclick="this.textContent='Playing'">Play</button>`}));
   await page.goto('https://example.test/embed/video?token=secret#private');
   if (scenario === 'existing') await page.evaluate(()=>{window.gtag=function() {};});
   const id=scenario === 'invalid' ? 'bad' : 'G-TEST12345';
   for(let i=0;i<(scenario==='duplicate'?2:1);i++) await page.addScriptTag({content:'document.currentScript.setAttribute("data-ga4-id", "'+id+'");document.currentScript.setAttribute("data-ga4-page", "Embed player");'+loader});
   await page.getByRole('button').click(); assert.equal(await page.getByRole('button').textContent(),'Playing');
   if (scenario === 'existing' || scenario === 'invalid') {
    await page.waitForTimeout(250); assert.equal(requests,0);
   } else {
    await page.waitForFunction(()=>window.dataLayer && window.dataLayer.length===2);
    await page.waitForTimeout(150);
    assert.equal(requests,1,'Duplicate or missing external load');
    const config=await page.evaluate(()=>Array.from(window.dataLayer[1]));
    assert.equal(config[0],'config'); assert.equal(config[1],id);
    assert.equal(config[2].page_location,'https://example.test/embed/video');
    assert.equal(config[2].page_title,'Embed player');
    assert.equal(config[2].allow_google_signals,false);
    assert.equal(await page.locator('script[src*="googletagmanager"]').evaluate(el=>el.async),true);
   }
   assert.deepEqual(errors,[]); await page.close(); console.log('PASS GA4 '+scenario);
  }
 } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
