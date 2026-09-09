<?php
$root=sys_get_temp_dir().'/secure-receive-test-'.bin2hex(random_bytes(8));mkdir($root.'/public',0700,true);mkdir($root.'/writable',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';require __DIR__.'/../app/Libraries/SecureBackupRemote.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejects($fn){try{$fn();}catch(RuntimeException $e){return $e->getMessage();}throw new LogicException('Invalid input accepted');}
class ReceiveRemote extends App\Libraries\SecureBackupRemote {
 public $requests=[],$responses=[],$downloads=[],$payload='SELECT 1;',$status=200,$fail=false;
 protected function request(string $url,string $method,array $headers=[],?string $body=null,?string $file=null,array $extra=[]):array {
  $this->requests[]=compact('url','method','headers');if(!$this->responses)throw new LogicException('Unexpected request');return array_shift($this->responses);
 }
 protected function downloadTo(string $url,string $path,array $headers,array $extra):int {
  $this->downloads[]=compact('url','path','headers','extra');file_put_contents($path,$this->payload);
  if($this->fail)throw new RuntimeException('Interrupted');return $this->status;
 }
}
try {
 $store=new App\Libraries\SecureBackups();$remote=new ReceiveRemote();
 file_put_contents(FCPATH.'unchanged.txt','keep-content');
 $remote->save('ftp',['host'=>'ftp.example.com','port'=>'21','protocol'=>'ftps','username'=>'fixture','password'=>'fixture-secret','directory'=>'private/backups']);
 $remote->status=226;$id=$remote->receive($store,'ftp','backup.sql');$req=end($remote->downloads);
 check($req['url']==='ftp://ftp.example.com:21/private/backups/backup.sql','FTP path');check($req['extra'][CURLOPT_USE_SSL]===CURLUSESSL_ALL,'TLS disabled');
 check(file_get_contents($store->path($id))==='SELECT 1;','Import incorrect');
 foreach(['../backup.sql','a/backup.sql','a\\backup.sql','https://evil.example/a.sql',"bad\r\n.sql",'backup.php'] as $bad) rejects(fn()=>$remote->receive($store,'ftp',$bad));
 check(count($remote->downloads)===1,'Invalid input reached transport');
 $remote->save('s3',['endpoint'=>'https://r2.example.com','region'=>'auto','bucket'=>'backup-bucket','prefix'=>'nightly copies','access_key'=>'fixture-access','secret_key'=>'fixture-secret','session_token'=>'']);
 $remote->status=200;$remote->receive($store,'s3','backup.sql');$req=end($remote->downloads);
 check($req['url']==='https://r2.example.com/backup-bucket/nightly%20copies/backup.sql','S3 path encoding');
 $c=['region'=>'auto','secret_key'=>'fixture-secret','access_key'=>'fixture-access'];
 $get=App\Libraries\SecureBackupRemote::s3Headers($c,'/bucket/a.sql','r2.example.com',hash('sha256',''),'20260910T010203Z','GET');
 $put=App\Libraries\SecureBackupRemote::s3Headers($c,'/bucket/a.sql','r2.example.com',hash('sha256',''),'20260910T010203Z');
 check($get!==$put,'GET signature is PUT signature');check(strpos(implode('\n',$req['headers']),'AWS4-HMAC-SHA256')!==false,'Unsigned S3 GET');
 $remote->save('drive',['client_id'=>'fixture-id','client_secret'=>'fixture-secret','refresh_token'=>'fixture-refresh','folder_id'=>'folder123']);
 $token=['status'=>200,'body'=>'{"access_token":"fixture-token"}'];
 $meta=['status'=>200,'body'=>json_encode(['name'=>'backup.sql','size'=>'9','md5Checksum'=>md5('SELECT 1;'),'parents'=>['folder123'],'trashed'=>false])];
 $remote->responses=[$token,$meta];$remote->receive($store,'drive','file123');$req=end($remote->downloads);
 check($req['url']==='https://www.googleapis.com/drive/v3/files/file123?alt=media&supportsAllDrives=true','Drive arbitrary host');
 check($req['headers']===['Authorization: Bearer fixture-token'],'Missing OAuth');
 $count=count($remote->downloads);$bad=$meta;$bad['body']=str_replace('folder123','otherfolder',$meta['body']);$remote->responses=[$token,$bad];rejects(fn()=>$remote->receive($store,'drive','file123'));check(count($remote->downloads)===$count,'Outside folder fetched');
 $before=count($store->entries());$remote->responses=[$token,$meta];$remote->payload='SELECT 2;';rejects(fn()=>$remote->receive($store,'drive','file123'));check(count($store->entries())===$before,'Bad checksum registered');
 $remote->status=403;$remote->payload='denied';rejects(fn()=>$remote->receive($store,'s3','backup.sql'));
 $remote->status=200;rejects(fn()=>$remote->receive($store,'s3','backup.zip'));
 $remote->fail=true;rejects(fn()=>$remote->receive($store,'ftp','backup.sql'));
 check(!(glob(WRITEPATH.'secure-backups/.download-*')?:[]),'Partial downloads not removed');check(count($store->entries())===$before,'Failure created entry');
 check(file_get_contents(FCPATH.'unchanged.txt')==='keep-content','Download restored website');
 rejects(fn()=>$store->importRemote(FCPATH.'unchanged.txt','backup.sql'));
 echo "PASS: FTP/S3/Drive private import, GET signing, path rejection, folder validation, checksum, failed download cleanup, no restore on import (mock transports)\n";
} finally {
 if(strpos($root,sys_get_temp_dir().'/secure-receive-test-')!==0)throw new RuntimeException('Unsafe cleanup');
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
