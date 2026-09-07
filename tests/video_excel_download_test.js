const { chromium } = require('playwright');
const path = require('node:path');
const assert = require('node:assert/strict');
(async () => {
 const browser = await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({acceptDownloads:true});
  const errors=[]; page.on('pageerror',e=>errors.push(e.message));
  await page.setContent('<table id="videos"><thead><tr>'+Array.from({length:9},(_,i)=>'<th>Column '+i+'</th>').join('')+'</tr></thead></table>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/vendors/jquery/dist/jquery.min.js')});
  for(const url of ['https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js','https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js','https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js','https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js']) { await page.addScriptTag({url}); }
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/video-excel-export.js')});
  await page.evaluate(()=>{
   const rows=Array.from({length:25},(_,i)=>{let row={DT_RowData:{excel:{id:i+1,name:'Example '+i,video_id:'tt'+i,image:'',embed:'https://example.com/embed/'+i}}};for(let c=0;c<9;c++){row[c]=String(i);}return row;});
   $('#videos').DataTable({data:rows,dom:'Brt',pageLength:25,buttons:[window.videoExcelExport(()=>$('#videos').DataTable().rows({page:'current'}).data().toArray())]});
  });
  const start=Date.now();
  const downloadPromise=page.waitForEvent('download',{timeout:10000});
  await page.locator('.buttons-excel').click();
  let download;
  try {download=await downloadPromise;} catch(e){throw new Error('Excel download failed: '+errors.join('; '));}
  assert.equal(download.suggestedFilename(),'All Videos.xlsx');
  assert.deepEqual(errors,[]);
  console.log('PASS: actual Excel button downloaded 25 rows in '+(Date.now()-start)+' ms.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
