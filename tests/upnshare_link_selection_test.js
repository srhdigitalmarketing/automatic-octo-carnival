const {chromium}=require('playwright');
const fs=require('node:fs');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 const page=await browser.newPage();
 await page.setContent(`<input name="title" value="Original title"><input name="banner_url" value="original.jpg"><textarea name="description">Original description</textarea><input name="imdb_id" value="original-id"><div id="links"><input name="st_links[1][url]" value="https://old.example/video"></div><button class="clone-st-group">Add</button><div id="results"></div><button class="host-video-result">Result</button>`);
 await page.addScriptTag({path:'public/admin-assets/vendors/jquery/dist/jquery.min.js'});
 const code=fs.readFileSync('public/admin-assets/js/custom.js','utf8');
 const start=code.indexOf("        $(document).on('click', '.host-video-result'");
 const end=code.indexOf('        function load_results()',start);
 await page.addScriptTag({content:"let hostResultsContent = $('#results');\n"+code.slice(start,end)});
 await page.evaluate(()=>{
  $('.clone-st-group').on('click',()=>$('#links').append('<input name="st_links[2][url]">'));
  $('.host-video-result').data('hostVideo',{provider:'upnshare',title:'Replacement title',poster_url:'replacement.jpg',player_url:'https://player.example/#abc123'});
 });
 await page.locator('.host-video-result').click();
 assert.equal(await page.locator('input[name="title"]').inputValue(),'Original title');
 assert.equal(await page.locator('input[name="banner_url"]').inputValue(),'original.jpg');
 assert.equal(await page.locator('textarea').inputValue(),'Original description');
 assert.equal(await page.locator('input[name="imdb_id"]').inputValue(),'original-id');
 assert.deepEqual(await page.locator('#links input').evaluateAll(xs=>xs.map(x=>x.value)),['https://old.example/video','https://player.example/#abc123']);
 await page.locator('.host-video-result').click();
 assert.equal(await page.locator('#links input').count(),2);
 await page.locator('#links input').last().fill('');
 await page.locator('.host-video-result').click();
 assert.equal(await page.locator('#links input').count(),2);
 assert.equal(await page.locator('#links input').last().inputValue(),'https://player.example/#abc123');
 console.log('PASS: UPNShare adds only stream URLs, preserves metadata and old links, reuses empty fields and rejects duplicates');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
