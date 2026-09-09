<?php
$root=sys_get_temp_dir().'/remote-list-test-'.bin2hex(random_bytes(8));mkdir($root.'/public',0700,true);mkdir($root.'/writable',0700,true);
define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';require __DIR__.'/../app/Libraries/SecureBackupRemote.php';
function check($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
function rejects($fn){try{$fn();}catch(RuntimeException $e){return;}throw new LogicException('Expected rejection');}
class ListRemote extends App\Libraries\SecureBackupRemote {
 public $responses=[],$requests=[];
 protected function request(string $url,string $method,array $headers=[],?string $body=null,?string $file=null,array $extra=[]):array {
 $this->requests[]=compact('url','method','headers','extra');if(!$this->responses)throw new LogicException('Unexpected request');return array_shift($this->responses);
 }
}
try{
$r=new ListRemote();$r->save('ftp',['host'=>'ftp.example.com','port'=>'21','protocol'=>'ftps','username'=>'fixture','password'=>'fixture','directory'=>'backups']);
$body="../escape.sql\nfile.part\na\\bad.sql\nbackups/good.zip\n";for($i=0;$i<101;$i++)$body.="test$i.sql\n";
$r->responses=[['status'=>226,'body'=>$body]];$list=$r->listing('ftp');check(count($list['items'])===100 && $list['cursor']==='100','FTP pagination');
$req=end($r->requests);check($req['extra'][CURLOPT_DIRLISTONLY]===true && $req['extra'][CURLOPT_USE_SSL]===CURLUSESSL_ALL,'FTP list/TLS');check(in_array('good.zip',array_column($list['items'],'reference'),true),'FTP folder prefix');
$r->responses=[['status'=>226,'body'=>$body]];$page=$r->listing('ftp','100');check(count($page['items'])===2 && $page['cursor']==='','FTP last page');rejects(fn()=>$r->listing('ftp','../'));
$r->save('drive',['client_id'=>'fixture','client_secret'=>'fixture','refresh_token'=>'fixture','folder_id'=>'folder123']);
$r->responses=[['status'=>200,'body'=>'{"access_token":"fixture"}'],['status'=>200,'body'=>json_encode(['files'=>[['id'=>'drive-file','name'=>'backup.sql','size'=>'9'],['id'=>'bad','name'=>'photo.png']],'nextPageToken'=>'next123'])]];
$list=$r->listing('drive');check(count($list['items'])===1 && $list['items'][0]['reference']==='drive-file' && $list['cursor']==='next123','Drive filtering and cursor');$req=end($r->requests);parse_str(parse_url($req['url'],PHP_URL_QUERY),$q);check($q['q']==="'folder123' in parents and trashed = false",'Drive scope');
$r->save('s3',['endpoint'=>'https://s3.example.com','region'=>'auto','bucket'=>'private-backups','prefix'=>'daily copies','access_key'=>'fixture','secret_key'=>'secret','session_token'=>'']);
$r->responses=[['status'=>200,'body'=>'<ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Contents><Key>daily copies/backup.zip</Key><Size>123</Size></Contents><Contents><Key>other/wrong.sql</Key><Size>1</Size></Contents><Contents><Key>daily copies/sub/wrong.sql</Key><Size>1</Size></Contents><IsTruncated>true</IsTruncated><NextContinuationToken>next+token</NextContinuationToken></ListBucketResult>']];
$list=$r->listing('s3','previous+token');check(count($list['items'])===1 && $list['items'][0]['reference']==='backup.zip' && $list['cursor']==='next+token','S3 namespaces/filtering/pagination');
$req=end($r->requests);check(strpos($req['url'],'continuation-token=previous%2Btoken')!==false && strpos($req['url'],'prefix=daily%20copies%2F')!==false,'S3 query encoding');check($req['method']==='GET','S3 method');
foreach(['<!DOCTYPE x><ListBucketResult/>','<Error/>','malformed'] as $body){$r->responses=[['status'=>200,'body'=>$body]];rejects(fn()=>$r->listing('s3'));}
$r->responses=[['status'=>403,'body'=>'secret']];rejects(fn()=>$r->listing('s3'));
echo "PASS: remote listing, pagination, folder scope, archive filtering, FTPS, Drive IDs, S3 namespace and unsafe XML rejection (mock transports)\n";
}finally{
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
