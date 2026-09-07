const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const path=require('node:path');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage();
  await page.setContent('<div id="embed-player" data-movie-id="movie"><div id="servers" data-initial-id="one"></div><button class="next-stream-host">Ganti server</button><iframe></iframe></div>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/vendors/jquery/dist/jquery.min.js')});
  await page.addScriptTag({path:path.join(__dirname,'../public/themes/pirate/js/player.js')});
  const result=await page.evaluate(()=>{
   Player.init(); Player.activeLinkId='one'; Player.failedHosts=[];
   let attempts=0,requests=0;
   $.ajax=()=>{requests++;};
   Player.play=verified=>{if(verified)attempts++;};
   Player.skipHost(); Player.skipHost();
   return {failed:Player.failedHosts,active:Player.activeLinkId,attempts,requests};
  });
  assert.deepEqual(result,{failed:['one'],active:null,attempts:1,requests:0});
  console.log('PASS: stalled iframe can switch host once, excludes failed host, and does not report false deletion.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
