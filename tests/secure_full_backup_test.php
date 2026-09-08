<?php
namespace App\Libraries {
 function is_file($p){return $p==='/www/server/mysql/bin/mysqldump'||\is_file($p);}
 function is_executable($p){return $p==='/www/server/mysql/bin/mysqldump'||\is_executable($p);}
 function db_connect(){return new class{public $DBDriver='MySQLi',$username='test',$password='fixture',$hostname='localhost',$port=3306,$database='fixture';function initialize(){}};}
 function proc_open($cmd,$desc,&$pipes,$cwd=null,$env=null,$options=[]){file_put_contents($desc[1][1],"CREATE TABLE fixture(id INT);\n");$pipes=[fopen('php://memory','r+')];return fopen('php://memory','r+');}
 function proc_get_status($p){return ['running'=>false,'exitcode'=>$GLOBALS['exitCode']];}
 function proc_close($p){fclose($p);return 0;}
 function proc_terminate($p){return true;}
}
namespace {
$root=sys_get_temp_dir().'/secure-full-test-'.bin2hex(random_bytes(8));mkdir($root.'/public/uploads',0700,true);mkdir($root.'/writable',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';require __DIR__.'/../app/Libraries/SecureRestore.php';
function check($ok,$msg){if(!$ok)throw new RuntimeException($msg);}
try{
 file_put_contents($root.'/.env','FIXTURE=1');file_put_contents($root.'/public/uploads/banner.jpg','image-fixture');$GLOBALS['exitCode']=0;
 $store=new App\Libraries\SecureBackups();$store->files('full');$items=$store->entries();check(count($items)===1&&$items[0]['kind']==='full','Full archive missing or intermediates exposed');
 $zip=new ZipArchive();check($zip->open($store->path($items[0]['id']))===true,'Invalid outer zip');
 $manifest=json_decode($zip->getFromName('secure-backup.json'),true);check($manifest['format']==='secure-full-backup','Missing manifest');
 foreach(['files.zip','database.sql'] as $name){$data=$zip->getFromName($name);check(hash('sha256',$data)===$manifest['files'][$name]['sha256'],'Checksum mismatch');check(strlen($data)===$manifest['files'][$name]['size'],'Size mismatch');}
 check(strpos($zip->getFromName('database.sql'),'CREATE TABLE fixture')!==false,'SQL absent');
 file_put_contents($root.'/inner.zip',$zip->getFromName('files.zip'));$inner=new ZipArchive();$inner->open($root.'/inner.zip');
 check($inner->getFromName('.env')==='FIXTURE=1'&&$inner->getFromName('public/uploads/banner.jpg')==='image-fixture','Application or uploads missing');
 for($i=0;$i<$inner->numFiles;$i++)check(strpos($inner->getNameIndex($i),'writable/')!==0,'Private staging included');$inner->close();$zip->close();
 try{(new App\Libraries\SecureRestore($store))->preview($items[0]['id']);throw new LogicException('Outer package accepted as files');}catch(RuntimeException $e){check(strpos($e->getMessage(),'Full Backup')!==false,'Missing restore instructions');}
 check(glob(WRITEPATH.'secure-backups/full-work-*')===[],'Staging not cleaned');
 $GLOBALS['exitCode']=1;try{$store->files('full');throw new LogicException('Partial backup accepted');}catch(RuntimeException $e){}
 check(count($store->entries())===1,'Failed backup changed existing archives');check(glob(WRITEPATH.'secure-backups/full-work-*')===[],'Failed staging not cleaned');
 echo "PASS: full ZIP contents, checksums, excluded staging, SQL export failure cleanup and outer-package restore guard (simulated MySQL)\n";
}finally{
 if(strpos($root,sys_get_temp_dir().'/secure-full-test-')!==0)throw new RuntimeException('Unsafe cleanup');
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
}
