const {chromium}=require('playwright');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 const page=await browser.newPage();
 await page.setContent('<div id="banner-migration-panel" data-url="https://example.test/run"><button id="banner-migration-start">Start</button><button id="banner-migration-stop">Stop</button><progress id="banner-migration-progress"></progress><p id="banner-migration-status"></p><ul id="banner-migration-log"></ul></div>');
 await page.evaluate(()=>{let id=0; window.fetch=async(url,options)=>{const action=options.body.get('action');const data=action==='start'?{total:3,token:'test-token'}:{id:++id,title:'Test',state:['success','skipped','failed'][id-1],message:'Result'};return {ok:true,json:async()=>data};};});
 await page.addScriptTag({path:'public/admin-assets/js/banner-migration.js'});
 await page.locator('#banner-migration-start').click();
 await page.waitForFunction(()=>document.querySelector('#banner-migration-status').textContent.startsWith('Selesai.'));
 assert.match(await page.locator('#banner-migration-status').textContent(),/Berhasil 1.*Dilewati 1.*Gagal 1/);
 assert.equal(await page.locator('#banner-migration-log li').count(),3);
 assert.equal(await page.locator('#banner-migration-start').isEnabled(),true);
 await page.evaluate(()=>{window.fetch=async()=>{throw new Error('Network interrupted');};});
 await page.locator('#banner-migration-start').click();
 await page.waitForFunction(()=>document.querySelector('#banner-migration-status').textContent.includes('Network interrupted'));
 assert.equal(await page.locator('#banner-migration-start').isEnabled(),true);
 console.log('PASS: batch progress, result counters and network failure recovery');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
