const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
(async () => {
 const browser = await chromium.launch({channel:'msedge',headless:true});
 try {
  const page = await browser.newPage();
  await page.route('https://admin.test/result*', route => route.fulfill({contentType:'application/json',body:JSON.stringify({state:'connected',label:'Terhubung',message:'API valid',checked_at:'2026-09-08T00:00:00Z'})}));
  await page.setContent('<div class="provider-connection" data-url="https://admin.test/result"><span class="provider-result"></span><small class="provider-result-detail"></small><button>Cek ulang</button></div>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/provider-connection.js')});
  await page.waitForFunction(()=>document.querySelector('.provider-result').textContent==='Terhubung');
  assert.equal(await page.locator('button').isEnabled(),true);
  await page.unroute('https://admin.test/result*');
  await page.route('https://admin.test/result*', route=>route.fulfill({status:500,body:'failure'}));
  await page.locator('button').click();
  await page.waitForFunction(()=>document.querySelector('.provider-result').textContent==='Gagal memeriksa');
  assert.equal(await page.locator('button').isEnabled(),true);
  console.log('PASS: connection result loads asynchronously and recheck handles failure.');
 } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
