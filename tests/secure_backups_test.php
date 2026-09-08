<?php
$root=sys_get_temp_dir().'/secure-backup-test-'.bin2hex(random_bytes(8));
mkdir($root.'/public/uploads',0700,true);mkdir($root.'/writable/cache',0700,true);mkdir($root.'/.git',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';
file_put_contents($root.'/public/uploads/banner.jpg','image-fixture');file_put_contents($root.'/.env','FAKE=test');file_put_contents($root.'/.git/config','exclude');file_put_contents($root.'/writable/cache/runtime','exclude');
try {
 $store=new App\Libraries\SecureBackups();$store->files('uploads');$items=$store->entries();
 if(count($items)!==1)throw new RuntimeException('Missing backup');
 $zip=new ZipArchive();$zip->open($store->path($items[0]['id']));if($zip->getFromName('public/uploads/banner.jpg')!=='image-fixture')throw new RuntimeException('Upload archive wrong');$zip->close();
 $store->files('application');$items=$store->entries();$app=array_values(array_filter($items,fn($x)=>$x['kind']==='application'))[0];$zip->open($store->path($app['id']));
 if($zip->getFromName('.env')!=='FAKE=test'||$zip->locateName('.git/config')!==false||$zip->locateName('writable/cache/runtime')!==false)throw new RuntimeException('Archive scope wrong');$zip->close();
 try{$store->path('../.env');throw new LogicException('Traversal accepted');}catch(RuntimeException $expected){}
 $fake=new class($root) {private $root;function __construct($root){$this->root=$root;file_put_contents($root.'/input.sql','SELECT 1;');}function isValid(){return true;}function hasMoved(){return false;}function getClientName(){return 'backup.sql';}function getSize(){return 9;}function move($dir,$name){rename($this->root.'/input.sql',$dir.$name);}};
 $store->upload($fake);if(count($store->entries())!==3)throw new RuntimeException('Upload failed');
 foreach($store->entries() as $item)$store->remove($item['id']);
 if($store->entries()!==[]||!is_file($root.'/public/uploads/banner.jpg'))throw new RuntimeException('Delete changed source');
 echo "PASS: ZIP scopes, SQL upload, traversal rejection, private metadata and archive-only deletion\n";
}finally{
 // Only this test's randomly created temporary root is removed.
 if(strpos($root,sys_get_temp_dir().'/secure-backup-test-')!==0)throw new RuntimeException('Invalid cleanup root');
 $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($iterator as $file){if($file->isDir())rmdir($file->getPathname());else unlink($file->getPathname());}rmdir($root);
}
