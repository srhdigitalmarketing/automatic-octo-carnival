const {chromium}=require('playwright');const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
const page=await browser.newPage();
await page.setContent(`<div id="secure-backups" data-url="/run" data-download="/download" data-token="test-token">
<select id="secure-scope"><option value="uploads">Uploads</option></select>
<form id="secure-upload"><input id="secure-file" type="file"><button>Upload</button></form>
<div id="secure-progress" hidden><span id="secure-spinner"></span><p id="secure-status"></p><div id="secure-bar"></div></div>
<div id="secure-confirm" hidden><span id="secure-restore-name"></span><span id="secure-restore-target"></span><p id="secure-restore-detail"></p><input id="secure-restore-confirmation"><button id="secure-restore-execute" disabled>Restore sekarang</button><button id="secure-restore-cancel">Batal</button></div>
<table><tbody id="secure-list"></tbody></table><script type="application/json" id="secure-initial">[{"id":"a","name":"test.zip","kind":"uploaded","created_at":"2026-09-08T12:00:00Z","size":1}]</script></div>`);
await page.evaluate(()=>{window.calls=[];window.fetch=async(url,{body})=>{const data=Object.fromEntries(body);window.calls.push(data);if(data.token!=='test-token')throw Error('Missing token');if(data.action==='restore-preview')return {ok:true,json:async()=>({message:'Periksa',nonce:'nonce-test',plan:{id:'a',name:'<script>unsafe</script>',target:'public/uploads',detail:'Menimpa file'}})};if(data.nonce!=='nonce-test'||data.confirmation!=='RESTORE')throw Error('Missing confirmation');return {ok:true,json:async()=>({message:'Restore selesai',entries:[]})};};});
await page.addScriptTag({path:'public/admin-assets/js/secure-backups.js'});
await page.locator('[data-secure-action="restore-preview"]').click();await page.waitForFunction(()=>!document.getElementById('secure-confirm').hidden);
assert.equal(await page.locator('#secure-restore-execute').isDisabled(),true);
assert.equal(await page.locator('#secure-restore-name script').count(),0);
await page.locator('#secure-restore-confirmation').fill('restore');assert.equal(await page.locator('#secure-restore-execute').isDisabled(),true);
await page.locator('#secure-restore-cancel').click();assert.equal((await page.evaluate(()=>window.calls)).length,1);
await page.locator('[data-secure-action="restore-preview"]').click();await page.locator('#secure-restore-confirmation').fill('RESTORE');await page.locator('#secure-restore-execute').click();await page.waitForFunction(()=>document.getElementById('secure-status').textContent==='Restore selesai');
assert.equal(await page.locator('#secure-confirm').isVisible(),false);assert.equal(await page.locator('#secure-restore-execute').isDisabled(),true);
const calls=await page.evaluate(()=>window.calls);assert.equal(calls.filter(x=>x.action==='restore').length,1);
console.log('PASS: restore preview, escaped metadata, typed confirmation, cancel without mutation, nonce and one-time UI submission');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
