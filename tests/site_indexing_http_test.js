const assert=require('node:assert/strict');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const net=require('node:net');
const http=require('node:http');
const {spawn}=require('node:child_process');
const root=path.resolve(__dirname,'..');
const php=process.env.PHP_BINARY||(process.platform==='win32'?'C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe':'php');
const temp=fs.mkdtempSync(path.join(os.tmpdir(),'site-indexing-http-'));
function request(port,url,method='GET'){
 return new Promise((resolve,reject)=>{http.request({hostname:'127.0.0.1',port,path:url,method},res=>{
  let body='';res.setEncoding('utf8');res.on('data',chunk=>body+=chunk);res.on('end',()=>resolve({status:res.statusCode,headers:res.headers,rawHeaders:res.rawHeaders,body}));
 }).on('error',reject).end();});
}
(async()=>{
 let server;
 try{
  const port=await new Promise(resolve=>{const socket=net.createServer();socket.listen(0,'127.0.0.1',()=>{const port=socket.address().port;socket.close(()=>resolve(port));});});
  server=spawn(php,['-S','127.0.0.1:'+port,'-t',temp,path.join(root,'tests/fixtures/site-indexing-http.php')],{cwd:root,env:{...process.env,SITE_INDEXING_TEST_ROOT:root},windowsHide:true,stdio:'ignore'});
  await new Promise((resolve,reject)=>{server.once('error',reject);server.once('spawn',resolve);});
  let ready=false;
  for(let i=0;i<100;i++){try{await request(port,'/');ready=true;break;}catch(error){await new Promise(r=>setTimeout(r,50));}}
  assert.ok(ready,'Local PHP fixture starts');
  const off='noindex, nofollow, noimageindex, nosnippet';
  for(const mode of ['noindex','index'])for(const page of ['/','/play/tt123','/embed/tt123','/watch/tt123','/download/tt123','/sub/index.php/watch/tt123']){
   const result=await request(port,page+'?mode='+mode);assert.equal(result.status,200);
   const expected=mode==='index'?'index, follow':off;
   assert.equal(result.headers['x-robots-tag'],expected,mode+' header on '+page);
   assert.equal(result.rawHeaders.filter(value=>value.toLowerCase()==='x-robots-tag').length,1,'No duplicate bootstrap noindex header');
   assert.ok(result.body.includes('content="'+expected+'"'),'HTML meta matches header');
  }
  for(const page of ['/admin','/admin_login','/ajax/get_stream_link','/missing','/redirect','/bootstrap-error']){
   assert.equal((await request(port,page+'?mode=index')).headers['x-robots-tag'],off,'Protected/technical response excluded: '+page);
  }
  const head=await request(port,'/play/tt123?mode=index','HEAD');assert.equal(head.headers['x-robots-tag'],'index, follow');assert.equal(head.body,'');
  console.log('PASS: real HTTP headers and meta, both modes on public URLs, one robots header, HEAD, redirects, admin/AJAX/errors and pre-bootstrap fallback');
 }finally{
  if(server&&server.exitCode===null){server.kill();await new Promise(resolve=>server.once('exit',resolve));}
  const target=path.resolve(temp);
  if(path.dirname(target)===path.resolve(os.tmpdir())&&path.basename(target).startsWith('site-indexing-http-'))fs.rmSync(target,{recursive:true,force:true});
 }
})().catch(error=>{console.error(error);process.exitCode=1;});
