<?php
namespace App\Libraries {
 function copy($from,$to) {
  if (($GLOBALS['failCopy'] ?? null)===$to) { $GLOBALS['failCopy']=null; return false; }
  return \copy($from,$to);
 }
}
namespace {
$root=sys_get_temp_dir().'/secure-restore-test-'.bin2hex(random_bytes(8));
mkdir($root.'/public/uploads',0700,true);mkdir($root.'/writable',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';
require __DIR__.'/../app/Libraries/SecureRestore.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function rejected($fn){try{$fn();}catch(RuntimeException $e){return;}throw new LogicException('Unsafe operation accepted');}
function archive($store,$root,$names,$symlink=false){
 $path=$root.'/input.zip';$zip=new ZipArchive();$zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE);
 foreach($names as $name=>$body)$zip->addFromString($name,$body);
 if($symlink)$zip->setExternalAttributesIndex(0,3,0120777<<16);
 $zip->close();$before=array_column($store->entries(),'id');
 $file=new class($path){private $path;function __construct($p){$this->path=$p;}function isValid(){return true;}function hasMoved(){return false;}function getClientName(){return 'input.zip';}function getSize(){return filesize($this->path);}function getTempName(){return $this->path;}function move($dir,$name){rename($this->path,$dir.$name);}};
 $store->upload($file);foreach($store->entries() as $entry)if(!in_array($entry['id'],$before,true))return $entry['id'];
 throw new RuntimeException('Missing upload');
}
try{
 $store=new App\Libraries\SecureBackups();$restore=new App\Libraries\SecureRestore($store);
 file_put_contents($root.'/public/uploads/banner.jpg','current');file_put_contents($root.'/public/uploads/keep.jpg','keep');
 $id=archive($store,$root,['public/uploads/banner.jpg'=>'restored','public/uploads/new/nested.jpg'=>'new']);
 $plan=$restore->preview($id);check($plan['count']===2&&$plan['scope']==='uploads','Wrong preview');
 rejected(fn()=>$restore->restore($id,str_repeat('0',64)));
 check(file_get_contents($root.'/public/uploads/banner.jpg')==='current','Preview changed files');
 $restore->restore($id,$plan['sha256']);
 check(file_get_contents($root.'/public/uploads/banner.jpg')==='restored','Restore failed');
 check(file_get_contents($root.'/public/uploads/new/nested.jpg')==='new','New file missing');
 check(file_get_contents($root.'/public/uploads/keep.jpg')==='keep','Unrelated file changed');
 $rollback=archive($store,$root,['public/uploads/banner.jpg'=>'should-rollback','public/uploads/keep.jpg'=>'should-fail']);
 $rollbackPlan=$restore->preview($rollback);
 // realpath uses native separators on Windows, including the temporary root itself.
 $GLOBALS['failCopy']=realpath($root).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'keep.jpg';
 rejected(fn()=>$restore->restore($rollback,$rollbackPlan['sha256']));
 check(file_get_contents($root.'/public/uploads/banner.jpg')==='restored'&&file_get_contents($root.'/public/uploads/keep.jpg')==='keep','Rollback did not recover active files');
 $failingStore=new class extends App\Libraries\SecureBackups { public function files(string $scope):void {throw new RuntimeException('Backup unavailable');} };
 $failingRestore=new App\Libraries\SecureRestore($failingStore);
 rejected(fn()=>$failingRestore->restore($rollback,$rollbackPlan['sha256']));
 check(file_get_contents($root.'/public/uploads/banner.jpg')==='restored','Restore ran without safety backup');
 $backups=array_values(array_filter($store->entries(),fn($x)=>$x['kind']==='uploads'));
 check(count($backups)===2,'Safety backup missing');$found=false;foreach($backups as $backup){$z=new ZipArchive();$z->open($store->path($backup['id']));if($z->getFromName('public/uploads/banner.jpg')==='current')$found=true;$z->close();}check($found,'Safety backup wrong');
 foreach(['../escape','/absolute','C:/escape','public\\uploads\\escape','writable/runtime','WritAble/runtime','.git/config','public/uploads/../escape','public/uploads/trailing.','public/uploads/file:stream','public/uploads/NUL.txt'] as $name){
  $bad=archive($store,$root,[$name=>'unsafe']);rejected(fn()=>$restore->preview($bad));
 }
 $bad=archive($store,$root,['public/uploads/link'=>'../../.env'],true);rejected(fn()=>$restore->preview($bad));
 $bad=archive($store,$root,['public/uploads/A.jpg'=>'a','public/uploads/a.jpg'=>'b']);rejected(fn()=>$restore->preview($bad));
 $bad=archive($store,$root,['public/uploads/file'=>'a','public/uploads/file/child'=>'b']);rejected(fn()=>$restore->preview($bad));
 $tampered=archive($store,$root,['public/uploads/banner.jpg'=>'tampered']);file_put_contents($store->path($tampered),'invalid',FILE_APPEND);rejected(fn()=>$restore->preview($tampered));
 file_put_contents($root.'/.env','OLD=1');$app=archive($store,$root,['.env'=>'NEW=1']);$plan=$restore->preview($app);check($plan['scope']==='application','Application scope wrong');$restore->restore($app,$plan['sha256']);check(file_get_contents($root.'/.env')==='NEW=1','Application restore failed');
 App\Libraries\SecureRestore::validateGrants([['g'=>'GRANT USAGE ON *.* TO `site`@`localhost`'],['g'=>'GRANT ALL PRIVILEGES ON `site`.* TO `site`@`localhost`']],'site');
 App\Libraries\SecureRestore::validateGrants([['g'=>'GRANT ALL PRIVILEGES ON `my\_site`.* TO `site`@`localhost`']],'my_site');
 foreach(['GRANT ALL PRIVILEGES ON *.* TO `root`@`localhost`','GRANT SELECT ON `other`.* TO `site`@`localhost`','GRANT `role` TO `site`@`localhost`','GRANT ALL ON `site`.* TO `site`@`localhost` WITH GRANT OPTION','GRANT ALL ON `my_site`.* TO `site`@`localhost`'] as $grant)rejected(fn()=>App\Libraries\SecureRestore::validateGrants([['g'=>$grant]],'site'));
 check(glob(WRITEPATH.'secure-backups/restore-*')===[],'Staging files remain');
 echo "PASS: ZIP restore, safety backup, original files preserved, application restore, checksum/path/symlink/collision rejection, scoped SQL grants\n";
}finally{
 if(strpos($root,sys_get_temp_dir().'/secure-restore-test-')!==0)throw new RuntimeException('Unsafe cleanup');
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir()&&!$f->isLink())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
}
