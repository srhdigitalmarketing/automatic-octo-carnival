<?php
$root=sys_get_temp_dir().'/secure-remote-test-'.bin2hex(random_bytes(8));mkdir($root.'/public',0700,true);mkdir($root.'/writable',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';require __DIR__.'/../app/Libraries/SecureBackupRemote.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn){try{$fn();}catch(RuntimeException $e){return $e->getMessage();}throw new LogicException('Invalid action accepted');}
class FakeRemote extends App\Libraries\SecureBackupRemote {
 public $requests=[],$responses=[];
 protected function request(string $url,string $method,array $headers=[],?string $body=null,?string $file=null,array $extra=[]):array {
  $this->requests[]=compact('url','method','headers','body','file','extra');
  if(!$this->responses)throw new LogicException('Unexpected network request');
  return array_shift($this->responses);
 }
}
try {
 $store=new App\Libraries\SecureBackups();$remote=new FakeRemote();
 $file=new class($root){private $path;function __construct($r){$this->path=$r.'/input.sql';file_put_contents($this->path,'SELECT 1;');}function isValid(){return true;}function hasMoved(){return false;}function getClientName(){return 'backup.sql';}function getSize(){return 9;}function move($dir,$name){rename($this->path,$dir.$name);}};
 $store->upload($file);$entry=$store->entries()[0];$id=$entry['id'];
 $ftp=['host'=>'ftp.example.com','port'=>'21','protocol'=>'ftps','username'=>'backup','password'=>'fixture-password','directory'=>'private/backups'];
 rejects(fn()=>$remote->save('ftp',array_merge($ftp,['password'=>[]])));
 $remote->save('ftp',$ftp);$safe=$remote->settings();check(!isset($safe['ftp']['password'])&&$safe['ftp']['password_saved'],'Password leaked');
 check(strpos(file_get_contents(WRITEPATH.'secure-backups/.remote-settings'),'fixture-password')===false,'Plaintext config');
 $remote->save('ftp',array_merge($ftp,['password'=>'']));
 $remote->responses=[['status'=>226,'headers'=>[],'body'=>'']];$remote->send($store,$id,'ftp');$req=end($remote->requests);
 check($req['file']===$store->path($id),'FTP upload not streamed');check($req['extra'][CURLOPT_USE_SSL]===CURLUSESSL_ALL,'FTPS TLS disabled');check($req['extra'][CURLOPT_USERPWD]==='backup:fixture-password','Blank password not preserved');check(substr($req['url'],-5)==='.part'&&count($req['extra'][CURLOPT_POSTQUOTE])===2,'Partial FTP published');
 $s3=['endpoint'=>'https://s3.ap-southeast-1.amazonaws.com','region'=>'ap-southeast-1','bucket'=>'private-backups','prefix'=>'nightly copies','access_key'=>'FIXTURE_ACCESS','secret_key'=>'fixture-secret','session_token'=>''];
 $remote->save('s3',$s3);$remote->responses=[['status'=>200,'headers'=>[],'body'=>'']];$remote->send($store,$id,'s3');$req=end($remote->requests);
 check(strpos($req['url'],'/private-backups/nightly%20copies/')!==false,'S3 key encoding incorrect');check($req['method']==='PUT'&&$req['file']===$store->path($id),'S3 upload not streamed');
 $headers=implode("\n",$req['headers']);check(strpos($headers,'AWS4-HMAC-SHA256 Credential=FIXTURE_ACCESS/')!==false&&strpos($headers,'x-amz-content-sha256: '.hash_file('sha256',$store->path($id)))!==false,'Missing S3 signing');check(strpos($headers,'fixture-secret')===false,'Secret in authorization');
 rejects(fn()=>$remote->save('s3',array_merge($s3,['endpoint'=>'http://unsafe.example'])));rejects(fn()=>$remote->save('s3',array_merge($s3,['endpoint'=>'https://user:pass@example.com'])));rejects(fn()=>$remote->save('ftp',array_merge($ftp,['directory'=>'../public'])));
 $drive=['client_id'=>'client-fixture','client_secret'=>'client-secret','refresh_token'=>'refresh-fixture','folder_id'=>'folder123'];$remote->save('drive',$drive);
 $remote->responses=[['status'=>200,'headers'=>[],'body'=>'{"access_token":"access-fixture"}'],['status'=>200,'headers'=>['location'=>'https://www.googleapis.com/upload/drive/v3/files?upload_id=fixture'],'body'=>''],['status'=>200,'headers'=>[],'body'=>json_encode(['id'=>'drive-file','size'=>'9','md5Checksum'=>md5('SELECT 1;')])]];
 $result=$remote->send($store,$id,'drive');check($result['reference']==='drive-file','Drive completion not recorded');$req=end($remote->requests);check($req['file']===$store->path($id),'Drive not streamed');check(count($store->entries()[0]['remote_uploads'])===3,'Remote statuses missing');
 $remote->responses=[['status'=>200,'headers'=>[],'body'=>'{"access_token":"access-fixture"}'],['status'=>200,'headers'=>['location'=>'https://attacker.example/upload'],'body'=>'']];$before=count($remote->requests);rejects(fn()=>$remote->send($store,$id,'drive'));check(count($remote->requests)===$before+2,'Token sent outside Google');
 $remote->responses=[['status'=>403,'headers'=>[],'body'=>'fixture-secret']];$error=rejects(fn()=>$remote->send($store,$id,'s3'));check(strpos($error,'fixture-secret')===false,'Provider response leaked');check(is_file($store->path($id)),'Local backup removed on failure');
 check(count($store->entries())===1,'Configuration leaked into backup list');
 $config=WRITEPATH.'secure-backups/.remote-settings';$bytes=file_get_contents($config);$bytes[30]=chr(ord($bytes[30])^1);file_put_contents($config,$bytes);rejects(fn()=>$remote->settings());
 echo "PASS: encrypted settings, secret preservation/redaction, FTP staging/TLS, S3 signing and streaming, Drive checksum and trusted upload URL, failure preserves local archive (mock transports)\n";
}finally{
 if(strpos($root,sys_get_temp_dir().'/secure-remote-test-')!==0)throw new RuntimeException('Unsafe cleanup');
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
