const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
(async () => {
 const browser = await chromium.launch({channel:'msedge', headless:true});
 try {
  const page = await browser.newPage();
  await page.setContent(`<div id="st-group-content"><div class="st-group"><label>Link 1</label><input class="link" name="st_links[1][url]" value="https://host/e/old123" readonly><input class="stream-priority" name="st_links[1][host_priority]" value="200"><select name="st_links[1][api_id]"><option value="">Automatic</option><option value="7" selected>Account</option></select><input type="hidden" name="st_links[1][id]" value="42"><input type="hidden" name="st_links[1][upnshare_video_id]" value="old123"><span class="stream-server-badge is-broken">Deleted</span><button class="clone-st-group">+</button></div></div>`);
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/vendors/jquery/dist/jquery.min.js')});
  const source = fs.readFileSync(path.join(__dirname,'../public/admin-assets/js/custom.js'),'utf8');
  const handlers = source.slice(source.indexOf('    function init_links_groups()'),source.indexOf('    function init_autoload()'));
  await page.addScriptTag({content:handlers+'\ninit_links_groups();'});
  assert.equal(await page.locator('select').inputValue(),'7', 'Initial account selection');
  await page.locator('.clone-st-group').first().click();
  const groups = page.locator('.st-group');
  assert.equal(await groups.count(),2);
  const clone=groups.nth(1);
  assert.equal(await clone.locator('select').getAttribute('name'),'st_links[2][api_id]');
  assert.equal(await clone.locator('select').inputValue(),'');
  assert.equal(await clone.locator('.link').inputValue(),'');
  assert.equal(await clone.locator('input[type=hidden]').count(),0);
  assert.equal((await clone.locator('.stream-server-badge').innerText()).trim(),'Not checked');
  assert.equal(await groups.first().locator('select').inputValue(),'7');
  console.log('PASS: cloned link resets UPNShare account, video identity and badge without modifying original.');
 } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
