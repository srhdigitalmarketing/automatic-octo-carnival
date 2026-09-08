const {chromium}=require('playwright');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 const page=await browser.newPage();
 await page.setContent('<div id="latest-grab-panel" data-url="https://example.test/run"><input id="latest-grab-count" value="5"><select id="latest-grab-api"><option value="1">Catalog</option></select><button id="latest-grab-start">Start</button><button id="latest-grab-stop">Stop</button><progress id="latest-grab-progress"></progress><p id="latest-grab-status"></p><ul id="latest-grab-log"></ul></div>');
 await page.evaluate(()=>{let id=0; window.fetch=async(url,options)=>{const action=options.body.get('action');const data=action==='start'?{total:3,target:5,token:'test-token'}:{id:++id,title:'Test',state:['success','skipped','failed'][id-1],message:'Result'};return {ok:true,json:async()=>data};};});
 await page.addScriptTag({path:'public/admin-assets/js/latest-grab.js'});
 await page.locator('#latest-grab-start').click();
 await page.waitForFunction(()=>document.querySelector('#latest-grab-status').textContent.startsWith('Selesai.'));
 assert.match(await page.locator('#latest-grab-status').textContent(),/Berhasil 1.*Dilewati 1.*Gagal 1/);
 assert.equal(await page.locator('#latest-grab-log li').count(),3);
 assert.equal(await page.locator('#latest-grab-start').isEnabled(),true);
 await page.evaluate(()=>{window.fetch=async()=>{throw new Error('Network interrupted');};});
 await page.locator('#latest-grab-start').click();
 await page.waitForFunction(()=>document.querySelector('#latest-grab-status').textContent.includes('Network interrupted'));
 assert.equal(await page.locator('#latest-grab-start').isEnabled(),true);
 console.log('PASS: batch progress, latest import counters and network failure recovery');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
