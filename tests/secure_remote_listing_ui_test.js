const {chromium}=require('playwright');const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 const page=await browser.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 await page.setContent(`<div id="secure-backups" data-url="/run" data-download="/download" data-token="fixture">
 <select id="secure-scope"><option value="full">Full</option></select><select id="secure-destination"><option value="local">Local</option></select>
 <select id="secure-restore-provider"><option value="ftp">FTP</option><option value="drive">Drive</option><option value="s3">S3</option></select>
 <select id="secure-remote-reference"></select><p id="secure-remote-message"></p><button id="secure-remote-more" data-secure-action="remote-list" data-id="more" hidden>More</button><button id="secure-remote-restore" data-secure-action="remote-download">Restore remote</button>
 <form id="secure-upload"><input id="secure-file" type="file"></form><div id="secure-progress" hidden><span id="secure-spinner"></span><p id="secure-status"></p><div id="secure-bar"></div></div><table><tbody id="secure-list"></tbody></table>
 <div id="secure-confirm" hidden><span id="secure-restore-name"></span><span id="secure-restore-target"></span><span id="secure-restore-detail"></span><input id="secure-restore-confirmation"><button id="secure-restore-execute">Confirm</button><button id="secure-restore-cancel">Cancel</button></div>
 <script type="application/json" id="secure-initial">[]</script></div>`);
 await page.evaluate(()=>{window.calls=[];window.fetch=async(url,{body})=>{const d=Object.fromEntries(body);window.calls.push(d);let result={message:'OK',entries:[]};
 if(d.action==='remote-list'){
 if(d.destination==='s3')return {ok:false,json:async()=>({message:'Access denied'})};
 result.remote={items:[{name:d.cursor?'older.zip':'backup.sql',reference:d.destination==='drive'?'drive123':d.cursor?'older.zip':'backup.sql',size:9}],cursor:d.destination==='ftp'&&!d.cursor?'100':''};}
 if(d.action==='remote-download')result.imported_id='local123';
 if(d.action==='restore-preview'){result.plan={id:'local123',name:'backup.sql',target:'Database client',detail:'Review'};result.nonce='nonce';}
 return {ok:true,json:async()=>result};};});
 await page.addScriptTag({path:'public/admin-assets/js/secure-backups.js'});
 await page.waitForFunction(()=>document.querySelector('#secure-remote-reference').options.length===2);assert.equal(await page.locator('#secure-remote-restore').isDisabled(),true);
 await page.locator('#secure-remote-more').click();await page.waitForFunction(()=>document.querySelector('#secure-remote-reference').options.length===3);
 await page.locator('#secure-restore-provider').selectOption('drive');await page.waitForFunction(()=>document.querySelector('#secure-remote-reference').options.length===2 && document.querySelector('#secure-remote-reference').options[1].value==='drive123');
 assert.equal(await page.locator('#secure-remote-more').isHidden(),true);await page.locator('#secure-remote-reference').selectOption('drive123');await page.locator('#secure-remote-restore').click();await page.locator('#secure-confirm').waitFor({state:'visible'});
 let calls=await page.evaluate(()=>window.calls);assert.equal(calls.find(x=>x.action==='remote-download').destination,'drive');assert.equal(calls.find(x=>x.action==='remote-download').reference,'drive123');assert.equal(calls.at(-1).action,'restore-preview');assert.equal(calls.some(x=>x.action==='restore'),false);
 assert.equal(await page.locator('#secure-restore-execute').isDisabled(),true);await page.locator('#secure-restore-confirmation').fill('RESTORE');await page.locator('#secure-restore-execute').click();await page.waitForFunction(()=>window.calls.some(x=>x.action==='restore'));
 await page.locator('#secure-restore-provider').selectOption('s3');await page.waitForFunction(()=>document.querySelector('#secure-remote-message').textContent==='Access denied');assert.equal(await page.locator('#secure-remote-restore').isDisabled(),true);assert.equal(await page.locator('#secure-remote-reference option').count(),1);assert.deepEqual(errors,[]);
 console.log('PASS: automatic remote listing, paging, source isolation, selection downloads then previews, explicit restore confirmation, error clears stale choices');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
