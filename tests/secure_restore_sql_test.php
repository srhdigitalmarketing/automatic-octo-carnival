<?php
namespace App\Libraries {
    // Replace the Linux process boundary only; never contact a database in this test.
    function is_file($path) { return $path==='/www/server/mysql/bin/mysql' || \is_file($path); }
    function is_executable($path) { return $path==='/www/server/mysql/bin/mysql' || \is_executable($path); }
    function db_connect() { return $GLOBALS['fakeDb']; }
    function proc_open($command,$descriptors,&$pipes,$cwd=null,$env=null,$options=[]) {
        $GLOBALS['commands'][]=$command;
        \check($GLOBALS['snapshots']>0,'Import started without safety backup');
        \check(in_array('--binary-mode',$command,true)&&in_array('--batch',$command,true)&&in_array('--local-infile=0',$command,true),'Unsafe client flags');
        \check(in_array('--database=site',$command,true),'Wrong database');
        \check(strpos(implode(' ',$command),'fake-secret')===false,'Password in argv');
        \check($descriptors[0][0]==='file'&&\is_file($descriptors[0][1]),'SQL not streamed from saved archive');
        $cnf=substr($command[1],strlen('--defaults-extra-file='));
        \check(strpos(file_get_contents($cnf),'password="fake-secret"')!==false,'Missing private credentials');
        $pipes=[];return fopen('php://memory','r+');
    }
    function proc_get_status($process) { return ['running'=>false,'exitcode'=>$GLOBALS['exitStatus']]; }
    function proc_close($process) { fclose($process); return $GLOBALS['exitStatus']; }
    function proc_terminate($process) { return true; }
}
namespace {
$root=sys_get_temp_dir().'/secure-sql-test-'.bin2hex(random_bytes(8));mkdir($root.'/public',0700,true);mkdir($root.'/writable',0700,true);
define('ROOTPATH',$root.'/');define('FCPATH',$root.'/public/');define('WRITEPATH',$root.'/writable/');
require __DIR__.'/../app/Libraries/SecureBackups.php';require __DIR__.'/../app/Libraries/SecureRestore.php';
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$GLOBALS['snapshots']=0;$GLOBALS['commands']=[];$GLOBALS['exitStatus']=0;$GLOBALS['snapshotFail']=false;
$GLOBALS['fakeDb']=new class {
 public $database='site',$DBDriver='MySQLi',$username='site',$password='fake-secret',$hostname='localhost',$port=3306;
 function initialize(){}
 function query($query){check($query==='SHOW GRANTS FOR CURRENT_USER','Unexpected query');return new class{function getResultArray(){return [['g'=>'GRANT ALL PRIVILEGES ON `site`.* TO `site`@`localhost`']];}};}
};
try{
 $store=new class extends App\Libraries\SecureBackups { public function database():void { if($GLOBALS['snapshotFail'])throw new RuntimeException('Safety backup failed');$GLOBALS['snapshots']++; }};
 $file=new class($root) {
  private $path;function __construct($root){$this->path=$root.'/input.sql';file_put_contents($this->path,'SELECT 1;');}
  function isValid(){return true;}function hasMoved(){return false;}function getClientName(){return 'input.sql';}function getSize(){return 9;}function move($dir,$name){rename($this->path,$dir.$name);}
 };
 $store->upload($file);$id=$store->entries()[0]['id'];$restore=new App\Libraries\SecureRestore($store);$plan=$restore->preview($id);
 check($plan['target']==='Database: site','Wrong preview');check(count($GLOBALS['commands'])===0,'Preview executed SQL');
 $restore->restore($id,$plan['sha256']);check(count($GLOBALS['commands'])===1,'SQL not imported');
 check(glob(WRITEPATH.'secure-backups/import-*')===[],'Credentials not cleaned');
 $GLOBALS['exitStatus']=1;
 try{$restore->restore($id,$plan['sha256']);throw new LogicException('Failed process accepted');}catch(RuntimeException $e){check(strpos($e->getMessage(),'sebagian')!==false,'Partial restore not reported');}
 check(glob(WRITEPATH.'secure-backups/import-*')===[],'Failed import left credentials');
 $GLOBALS['snapshotFail']=true;$before=count($GLOBALS['commands']);
 try{$restore->restore($id,$plan['sha256']);throw new LogicException('Missing safety backup accepted');}catch(RuntimeException $e){}
 check(count($GLOBALS['commands'])===$before,'Import ran after backup failure');
 echo "PASS: SQL process boundary, database scope, pre-import backup, error reporting, credential cleanup; no live MySQL used\n";
}finally{
 if(strpos($root,sys_get_temp_dir().'/secure-sql-test-')!==0)throw new RuntimeException('Unsafe cleanup');
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($root);
}
}
