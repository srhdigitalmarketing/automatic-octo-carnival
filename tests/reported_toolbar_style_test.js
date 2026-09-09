const fs=require('fs');const assert=require('node:assert/strict');const {chromium}=require('playwright');
(async()=>{const browser=await chromium.launch({channel:'msedge',headless:true});try{
 let form=fs.readFileSync('app/Views/admin/links/reported.php','utf8').match(/<form[\s\S]*?<\/form>/)[0];
 form=form.replace(/<\?php foreach[\s\S]*?<\?php endforeach \?>/g,'<option value="ustreamplay.online">ustreamplay.online</option>').replace(/<\?[\s\S]*?\?>/g,'');
 const page=await browser.newPage();
 await page.setContent(`<div class="right_col" style="margin:0;padding:16px"><div id="reported-links-datatable_wrapper"><div class="link-table-toolbar"><div class="link-table-toolbar__left">Show 25 per page</div><div class="link-table-toolbar__host">${form}</div><div class="link-table-toolbar__right">Search reported links…</div></div></div></div>`);
 for(const path of ['public/admin-assets/vendors/bootstrap5/css/bootstrap.min.css','public/admin-assets/css/custom.min.css','public/admin-assets/css/bootstrap5-compat.css','public/admin-assets/css/admin-polish.css'])await page.addStyleTag({path});
 for(const width of [1440,768,390]){
  await page.setViewportSize({width,height:700});
  for(const id of ['bulk-report-clear','export-error-links']){
   const button=page.locator('#'+id);await button.hover();await page.waitForTimeout(200);
   const check=async()=>page.locator('#'+id).evaluate(el=>{
    const s=getComputedStyle(el);const lum=color=>{const c=color.match(/[\d.]+/g).slice(0,3).map(Number).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;});return c[0]*.2126+c[1]*.7152+c[2]*.0722;};const a=lum(s.color),b=lum(s.backgroundColor);const rect=el.getBoundingClientRect();return {contrast:(Math.max(a,b)+.05)/(Math.min(a,b)+.05),left:rect.left,right:rect.right,text:el.textContent.trim()};
   });
   for(const state of ['hover','focus','normal','disabled']){
    if(state==='focus'){await page.mouse.move(0,0);await button.focus();}
    if(state==='normal')await button.evaluate(el=>el.blur());
    if(state==='disabled')await button.evaluate(el=>el.disabled=true);
    await page.waitForTimeout(180);const result=await check();assert(result.contrast>=4.5,`${id} ${state}: contrast ${result.contrast}`);assert(result.left>=0&&result.right<=width,`${id} outside viewport`);assert(result.text.length>5);
   }
   await button.evaluate(el=>el.disabled=false);
  }
  if(process.env.TOOLBAR_SCREENSHOT_DIR)await page.screenshot({path:process.env.TOOLBAR_SCREENSHOT_DIR+'/reported-toolbar-'+width+'.png'});
 }
 console.log('PASS: toolbar contrast normal/hover/focus/disabled and desktop/tablet/mobile bounds.');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
