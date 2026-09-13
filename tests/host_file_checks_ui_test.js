const {chromium} = require('playwright');
const assert = require('node:assert/strict');
const {execFileSync} = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname,'..');
// Render the actual PHP partial with fixed provider states; DB behavior is tested separately.
const php = process.env.PHP_BINARY || (process.platform === 'win32' ? 'C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe' : 'php');
const fixture = String.raw`
namespace App\Libraries {
 class HostFileChecks {
  static function supported($api){return $api->provider!=='cloudflare_r2';}
  static function enabled($api){return $api->id===1;}
  static function available(){return true;}
 }
}
namespace {
 function admin_url($url){return '/admin'.$url;}
 function esc($value,$context='html'){return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
 foreach([1=>'upnshare',2=>'serverdothost',3=>'cloudflare_r2'] as $id=>$provider){
  $api=(object)['id'=>$id,'provider'=>$provider,'status'=>'active'];
  echo '<td>';include 'app/Views/admin/third_party_apis/x_panels/file_check_control.php';echo '</td>';
 }
}`;
const controls = execFileSync(php,['-r',fixture],{cwd:root,encoding:'utf8'});
assert.equal((controls.match(/<form /g)||[]).length,2,'Only supported accounts get switches');
(async()=>{
 const browser=await chromium.launch({channel:'msedge',headless:true});
 try {
  const page=await browser.newPage({viewport:{width:390,height:844}});
  let mode='ok';const posts=[],errors=[];
  page.on('pageerror',error=>errors.push(error.message));
  await page.route('**/*',async route=>{
   const url=new URL(route.request().url());
   if(url.pathname==='/admin/third-party-apis/file-check'){
    const body=new URLSearchParams(route.request().postData());
    posts.push(Object.fromEntries(body));
    assert.equal(route.request().method(),'POST');
    assert.equal(route.request().headers()['x-requested-with'],'XMLHttpRequest');
    await new Promise(resolve=>setTimeout(resolve,200));
    if(mode==='offline')return route.abort('failed');
    if(mode==='html')return route.fulfill({status:200,contentType:'text/html',body:'Login expired'});
    if(mode==='error')return route.fulfill({status:400,contentType:'application/json',body:JSON.stringify({error:'Rejected'})});
    return route.fulfill({contentType:'application/json',body:JSON.stringify({enabled:body.get('enabled')==='1',message:'Pengaturan tersimpan.'})});
   }
   return route.fulfill({contentType:'text/html',body:'<!doctype html><html><body><div class="host-api-list-panel"><div class="table-responsive"><table class="table host-api-table"><tr>'+controls+'</tr></table></div></div></body></html>'});
  });
  await page.goto('https://admin.example/admin/third-party-apis');
  for(const file of ['vendors/bootstrap5/css/bootstrap.min.css','css/custom.min.css','css/bootstrap5-compat.css','css/admin-polish.css'])await page.addStyleTag({path:path.join(root,'public/admin-assets',file)});
  await page.addScriptTag({path:path.join(root,'public/admin-assets/js/host-file-checks.js')});
  const first=page.locator('#host-file-check-1'),second=page.locator('#host-file-check-2');
  assert.equal(await first.isChecked(),true);assert.equal(await second.isChecked(),false);
  assert.equal(await page.locator('.host-file-check-save').first().isVisible(),false,'AJAX mode hides fallback submit');
  await first.uncheck();
  assert.equal(await first.isDisabled(),true,'Disable during save prevents rapid duplicate requests');
  await page.waitForFunction(()=>!document.querySelector('#host-file-check-1').disabled);
  assert.deepEqual(posts,[{api_id:'1',enabled:'0'}]);
  assert.equal(await page.locator('.host-file-check-label').first().textContent(),'nonaktif');
  assert.equal(await second.isChecked(),false,'Changing one account leaves other account unchanged');
  await second.check();
  await page.waitForFunction(()=>!document.querySelector('#host-file-check-2').disabled);
  assert.deepEqual(posts[1],{api_id:'2',enabled:'1'});
  assert.equal(await page.locator('.host-file-check-label').nth(1).textContent(),'aktif');
  for(const failure of ['error','html','offline']){
   mode=failure;await second.uncheck();
   await page.waitForFunction(()=>!document.querySelector('#host-file-check-2').disabled);
   assert.equal(await second.isChecked(),true,'Failed or unconfirmed save reverts control');
   assert.match(await page.locator('.host-file-check-message').nth(1).textContent(),/belum terkonfirmasi/);
   assert.equal(await page.locator('.host-file-check-label').nth(1).textContent(),'aktif');
  }
  if(process.env.HOST_CHECK_SCREENSHOT)await page.screenshot({path:process.env.HOST_CHECK_SCREENSHOT,fullPage:true});
  const dimensions=await page.evaluate(()=>({page:document.documentElement.scrollWidth,width:innerWidth,table:document.querySelector('.table-responsive').scrollWidth,container:document.querySelector('.table-responsive').clientWidth}));
  assert.equal(dimensions.page<=dimensions.width,true,'Mobile table scroll stays within page');
  // The no-JS form submits a deterministic OFF value; checked ON overrides it in PHP.
  const noJs=await browser.newPage({javaScriptEnabled:false});
  await noJs.route('**/*',route=>route.fulfill({contentType:'text/html',body:'<table><tr>'+controls+'</tr></table>'}));
  await noJs.goto('https://admin.example/admin/third-party-apis');
  assert.equal(await noJs.locator('.host-file-check-save').first().isVisible(),true);
  assert.equal(await noJs.locator('form').first().getAttribute('method'),'post');
  assert.equal(await noJs.locator('form').nth(1).locator('input[type=hidden][name=enabled]').inputValue(),'0');
  assert.deepEqual(errors,[],'No browser script errors');
  console.log('PASS: real PHP controls, auto-save ON/OFF, isolated rows, in-flight guard, errors/offline, mobile containment and no-JS fallback');
 } finally {await browser.close();}
})().catch(error=>{console.error(error);process.exit(1);});
