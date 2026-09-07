const {chromium}=require('playwright');
const assert=require('node:assert/strict');
const path=require('node:path');
const fs=require('node:fs');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage();
  await page.setContent('<div id="embed-player" data-movie-id="movie"><div id="servers" data-initial-id="one"></div><iframe></iframe><div class="error"><span class="msg"></span></div></div>');
  await page.addScriptTag({path:path.join(__dirname,'../public/admin-assets/vendors/jquery/dist/jquery.min.js')});
  await page.addScriptTag({content:"const BASE_URL='https://example.test/';"});
  await page.addScriptTag({path:path.join(__dirname,'../public/themes/pirate/js/player.js')});
  const result=await page.evaluate(()=>{
   Player.init(); Player.activeLinkId='one'; Player.failedHosts=[];
   let attempts=0,requests=0,timeout;
   $.ajax=()=>{requests++;};
   Player.play=verified=>{if(verified)attempts++;};
   const original=window.setTimeout;
   window.setTimeout=(callback,delay)=>{if(delay===15000){timeout=callback;return 123;}return original(callback,delay);};
   Player.loadFrame('about:blank');
   timeout(); timeout();
   window.setTimeout=original;
   return {failed:Player.failedHosts,active:Player.activeLinkId,attempts,requests};
  });
  assert.deepEqual(result,{failed:['one'],active:null,attempts:1,requests:1});
  const shortDeadline=await page.evaluate(()=>{
   Player.activeLinkId='two'; Player.failedHosts=[]; Player.frameLoadTimeoutMs=5000;
   const callbacks=[]; const original=window.setTimeout;
   window.setTimeout=(callback,delay)=>{callbacks.push({callback,delay});return 456;};
   Player.loadFrame('about:blank#first');
   Player.activeLinkId='three'; Player.loadFrame('about:blank#second');
   callbacks[0].callback();
   const staleIgnored=Player.failedHosts.length===0;
   callbacks[1].callback(); callbacks[1].callback();
   window.setTimeout=original;
   return {delays:callbacks.map(item=>item.delay),staleIgnored,failed:Player.failedHosts};
  });
  assert.deepEqual(shortDeadline,{delays:[5000,5000],staleIgnored:true,failed:['three']});
  const template=fs.readFileSync(path.join(__dirname,'../app/Views/themes/pirate/embed.php'),'utf8');
  assert.equal(template.includes('next-stream-host'),false);
  assert.equal(template.includes('Ganti server'),false);
  console.log('PASS: iframe timeout rotates automatically once, excludes failed host and player has no switch panel.');
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
