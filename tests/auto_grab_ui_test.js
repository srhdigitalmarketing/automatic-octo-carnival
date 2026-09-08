const {chromium}=require('playwright');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 const page=await browser.newPage();
 await page.setContent('<div id="auto-grab-panel" data-url="https://example.test/run"><select id="auto-grab-api"><option value="1">Catalog</option></select><button id="auto-grab-start">Start</button><button id="auto-grab-stop">Stop</button><progress id="auto-grab-progress"></progress><p id="auto-grab-status"></p><ul id="auto-grab-log"></ul></div>');
 await page.evaluate(()=>{let id=0; window.fetch=async(url,options)=>{const action=options.body.get('action');const data=action==='start'?{total:3,max_id:3}:{id:++id,title:'Test',state:['success','skipped','failed'][id-1],message:'Result'};return {ok:true,json:async()=>data};};});
 await page.addScriptTag({path:'public/admin-assets/js/auto-grab.js'});
 await page.locator('#auto-grab-start').click();
 await page.waitForFunction(()=>document.querySelector('#auto-grab-status').textContent.startsWith('Selesai.'));
 assert.match(await page.locator('#auto-grab-status').textContent(),/Berhasil 1.*Dilewati 1.*Gagal 1/);
 assert.equal(await page.locator('#auto-grab-log li').count(),3);
 assert.equal(await page.locator('#auto-grab-start').isEnabled(),true);
 await page.evaluate(()=>{window.fetch=async()=>{throw new Error('Network interrupted');};});
 await page.locator('#auto-grab-start').click();
 await page.waitForFunction(()=>document.querySelector('#auto-grab-status').textContent.includes('Network interrupted'));
 assert.equal(await page.locator('#auto-grab-start').isEnabled(),true);
 console.log('PASS: batch progress, result counters and network failure recovery');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
