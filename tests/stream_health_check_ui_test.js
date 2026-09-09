const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const path = require('node:path');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage();
  await page.route('https://admin.test/check',route=>{
   assert.equal(route.request().method(),'POST');
   return route.fulfill({contentType:'application/json',body:JSON.stringify({status:'deleted',message:'UPNShare: file not found in configured account'})});
  });
  await page.setContent('<div class="st-group"><span class="stream-server-badge is-healthy">Healthy</span><button class="stream-check-now" data-url="https://admin.test/check">Cek file via API</button><small class="stream-check-message"></small><input value="unsaved edit"></div>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/stream-health-check.js')});
  await page.locator('button').click();
  await page.waitForFunction(()=>document.querySelector('.stream-server-badge').textContent==='Deleted');
  assert.match(await page.locator('.stream-server-badge').getAttribute('class'),/is-broken/);
  assert.equal(await page.locator('input').inputValue(),'unsaved edit');
  assert.equal(await page.locator('button').isEnabled(),true);
  await page.setContent('<form><div class="st-group"><input name="st_links[1][url]" value="https://upload18.org/a"><div class="stream-health-status" data-saved-url="https://upload18.org/a"><button class="stream-check-now">Cek file</button></div></div></form>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/stream-health-check.js')});
  await page.locator('input').fill('https://abcdomain.org/play/a');
  assert.equal(await page.locator('.stream-health-status').isHidden(),true);
  await page.locator('input').fill('https://upload18.org/a');
  assert.equal(await page.locator('.stream-health-status').isVisible(),true);
  console.log('PASS: direct file API check replaces stale Healthy with Deleted and preserves form edits.');
 } finally { await browser.close(); }
})().catch(e=>{console.error(e);process.exitCode=1;});
