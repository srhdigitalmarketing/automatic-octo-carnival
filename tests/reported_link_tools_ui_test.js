const {chromium}=require('playwright');const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 for(const scenario of ['clear','export','cancel','failure']){
  const page=await browser.newPage();await page.setContent(`<form id="reported-host-filter"><select id="reported-host"><option value="">All hosts</option><option value="a.example">A</option></select><button type="submit">Filter</button><button type="button" id="bulk-link-fix">Fix</button><button type="button" id="bulk-report-clear" data-url="/tools">Clear</button><button type="button" id="export-error-links" data-url="/tools">Export</button></form><div id="reported-tools-progress" hidden><span id="reported-tools-status"></span><div id="reported-tools-bar"></div><button id="reported-tools-stop">Stop</button></div>`);
  if(scenario==='export')await page.selectOption('#reported-host','a.example');
  await page.evaluate(scenario=>{
   window.calls=[];window.confirm=()=>scenario!=='cancel';window.downloaded=null;
   URL.createObjectURL=blob=>{window.blob=blob;return 'blob:test'};URL.revokeObjectURL=()=>{};
   HTMLAnchorElement.prototype.click=function(){window.downloaded=this.download;};
   window.fetch=async(url,options)=>{const data=Object.fromEntries(options.body);window.calls.push(data);return {ok:scenario!=='failure',json:async()=>scenario==='failure'?{message:'test failure'}:data.action==='start'?{total:2,max_id:3}:data.cursor==='0'?{cursor:1,count:1,done:false,rows:[{title:'=SUM(1,2)',link:'https://a.example/#1'}]}:{cursor:3,count:1,done:true,rows:[{title:'Title "two"\nnext',link:'https://a.example/#2'}]}};};
  },scenario);
  await page.addScriptTag({path:'public/admin-assets/vendors/jszip/jszip.min.js'});
  await page.addScriptTag({path:'public/admin-assets/js/reported-links-excel.js'});
  await page.addScriptTag({path:'public/admin-assets/js/reported-link-tools.js'});
  await page.click(scenario==='export'?'#export-error-links':'#bulk-report-clear');
  if(scenario==='cancel'){assert.equal(await page.evaluate(()=>calls.length),0);}
  else{
   await page.waitForFunction(()=>/Selesai|Gagal/.test(document.getElementById('reported-tools-status').textContent));
   assert.equal(await page.locator('#reported-host').isEnabled(),true);
   assert.equal(await page.locator('#bulk-link-fix').isEnabled(),true);
   if(scenario==='export'){
    assert.equal(await page.evaluate(()=>downloaded),'error-links-a.example.xlsx');
    const sheet=await page.evaluate(async()=>{const zip=await JSZip.loadAsync(await blob.arrayBuffer());return zip.file('xl/worksheets/sheet1.xml').async('string');});
    assert(sheet.includes('Judul'));assert(sheet.includes('=SUM(1,2)'));assert(sheet.includes('Title &quot;two&quot;\nnext'));assert(!sheet.includes('<f>'));assert.equal(await page.evaluate(()=>calls.some(c=>c.action==='clear')),false);
   }else if(scenario==='clear'){assert.equal(await page.evaluate(()=>calls.filter(c=>c.action==='clear').length),2);assert.equal(await page.evaluate(()=>calls[0].host),'');}
  }
  await page.close();console.log('PASS reported tools '+scenario);
 }
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
