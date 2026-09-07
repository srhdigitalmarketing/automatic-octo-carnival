const { chromium } = require('playwright');
const path = require('node:path');
const assert = require('node:assert/strict');
(async () => {
 const browser = await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({acceptDownloads:true});
  const errors=[]; page.on('pageerror',e=>errors.push(e.message)); page.on('dialog',async d=>{errors.push(d.message());await d.dismiss();});
  await page.setContent('<table id="videos"><thead><tr>'+Array.from({length:9},(_,i)=>'<th>Column '+i+'</th>').join('')+'</tr></thead></table>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/vendors/jquery/dist/jquery.min.js')});
  for(const url of ['https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js','https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js','https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js','https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js']) { await page.addScriptTag({url}); }
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/video-excel-export.js')});
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/js/video-export-all.js')});
  const requests=[];
  await page.route('https://export.test/videos*', async route=>{
   const query=new URL(route.request().url()).searchParams;
   const offset=Number(query.get('start')||0);
   const exporting=query.get('export')==='1';
   const length=exporting?1000:25;
   if(exporting){requests.push({offset,filter:query.get('filter'),search:query.get('search[value]')});}
   const data=Array.from({length:Math.min(length,2050-offset)},(_,i)=>{
    const id=offset+i+1;
    const row={DT_RowData:{excel:{id,name:'Example '+id,video_id:'tt'+id,image:'',embed:'https://example.com/embed/'+id}}};
    for(let c=0;c<9;c++){row[c]=String(id);} return row;
   });
   await route.fulfill({json:{draw:Number(query.get('draw')),recordsTotal:2050,recordsFiltered:2050,data},headers:{'Access-Control-Allow-Origin':'*'}});
  });
  await page.evaluate(()=>{
   $('#videos').DataTable({serverSide:true,ajax:{url:'https://export.test/videos',data:function(d){d.filter='without_image';}},search:{search:'Example'},dom:'Brt',pageLength:25,buttons:[window.allVideoExport(window.videoExcelExport(()=>$('#videos').DataTable().rows({page:'current'}).data().toArray()))]});
  });
  await page.waitForFunction(()=>$('#videos').DataTable().rows().count()===25);
  const start=Date.now();
  const downloadPromise=page.waitForEvent('download',{timeout:10000});
  await page.locator('.buttons-excel').click();
  let download;
  try {download=await downloadPromise;} catch(e){throw new Error('Excel download failed: '+errors.join('; '));}
  assert.equal(download.suggestedFilename(),'All Videos.xlsx');
  assert.deepEqual(errors,[]);
  const stream=await download.createReadStream(); const chunks=[]; for await(const chunk of stream){chunks.push(chunk);}
  const base64=Buffer.concat(chunks).toString('base64');
  const details=await page.evaluate(async data=>{
   const zip=await JSZip.loadAsync(data,{base64:true});
   const xml=await zip.file('xl/worksheets/sheet1.xml').async('string');
   const doc=new DOMParser().parseFromString(xml,'application/xml');
   return {rows:doc.querySelectorAll('row').length,last:doc.querySelector('c[r="A2051"]').textContent,current:$('#videos').DataTable().rows().count(),page:$('#videos').DataTable().page()};
  },base64);
  assert.deepEqual(requests.map(r=>r.offset),[0,1000,2000]);
  assert(requests.every(r=>r.filter==='without_image' && r.search==='Example'));
  assert.deepEqual(details,{rows:2051,last:'2050',current:25,page:0});
  console.log('PASS: all 2050 rows exported across 3 batches; filters, search and current table page preserved in '+(Date.now()-start)+' ms.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
