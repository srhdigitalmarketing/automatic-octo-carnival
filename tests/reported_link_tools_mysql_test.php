<?php
if (!isset($argv[1]) || (int)$argv[1]!==13389) { echo "SKIP: supply disposable MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql=new mysqli('127.0.0.1','root','','',13389);$name='reported_tools_test_'.bin2hex(random_bytes(6));$mysql->query("CREATE DATABASE `$name`");
foreach(['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$name,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value)putenv('database.default.'.$key.'='.$value);
define('FCPATH',dirname(__DIR__).'/public/');require dirname(__DIR__).'/app/Config/Paths.php';$paths=new Config\Paths();require dirname(__DIR__).'/system/bootstrap.php';error_reporting(E_ALL & ~E_DEPRECATED);
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
try{
 $db=db_connect();check($db->database===$name,'Disposable database required');
 $db->query('CREATE TABLE movies (id INT PRIMARY KEY,title TEXT)');
 $db->table('movies')->insert(['id'=>1,'title'=>"=Title, with comma\nand newline"]);
 $db->query('CREATE TABLE links (id INT PRIMARY KEY,movie_id INT,link TEXT,type VARCHAR(30),reports_not_working INT,reports_wrong_link INT,is_broken INT,provider_status VARCHAR(20),requests INT)');
 for($i=1;$i<=252;$i++)$db->table('links')->insert(['id'=>$i,'movie_id'=>1,'link'=>'https://a.example/#'.$i,'type'=>'stream','reports_not_working'=>2,'reports_wrong_link'=>1,'is_broken'=>1,'provider_status'=>'deleted','requests'=>123]);
 foreach([[253,'https://a.example.evil/#1','stream'],[254,'https://a.example:443/?x=1','stream'],[255,'https://a.example/#download','direct_download'],[256,'https://b.example/#1','stream']] as [$id,$url,$type])$db->table('links')->insert(['id'=>$id,'movie_id'=>999,'link'=>$url,'type'=>$type,'reports_not_working'=>1,'reports_wrong_link'=>0]);
 $tools=new App\Libraries\ReportedLinkTools($db);
 check($tools->start('a.example')['total']===253,'Exact host or stream filter failed');
 $snapshot=$tools->start('');check($snapshot['total']===256,'All-host scope wrong');
 $before=$db->table('links')->orderBy('id')->get()->getResultArray();
 $batch=$tools->batch('a.example',0,$snapshot['max_id'],false);check(count($batch['rows'])===250&&!$batch['done'],'Export must paginate');check($batch['rows'][0]['title']==="=Title, with comma\nand newline",'Title mismatch');
 $next=$tools->batch('a.example',$batch['cursor'],$snapshot['max_id'],false);check(count($next['rows'])===3&&$next['done'],'Export missing remaining pages');check($next['rows'][2]['title']==='[Video tidak ditemukan]','Orphan link missing');
 check($before===$db->table('links')->orderBy('id')->get()->getResultArray(),'Export modified data');
 $cursor=0;$count=0;do{$batch=$tools->batch('a.example',$cursor,$snapshot['max_id'],true);$cursor=$batch['cursor'];$count+=$batch['count'];}while(!$batch['done']);check($count===253,'Clear failed across batches');
 check($tools->start('')['total']===3,'Clear escaped host/type scope');
 $row=$db->table('links')->where('id',1)->get()->getRowArray();check($row['link']===$before[0]['link']&&(int)$row['requests']===123&&(int)$row['is_broken']===1&&$row['provider_status']==='deleted','Clear changed link or health data');
 $batch=$tools->batch('',0,$snapshot['max_id'],true);check($batch['count']===3&&$tools->start('')['total']===0,'All-host clear failed');
 check($db->table('links')->countAllResults()===256&&$db->table('movies')->countAllResults()===1,'Content deleted');
 foreach(['a.example OR 1=1','https://a.example',[]] as $bad){try{App\Libraries\ReportedLinkTools::host($bad);throw new RuntimeException('Bad host accepted');}catch(InvalidArgumentException $expected){}}
 echo "PASS: real MySQL batched clear/export, exact host, all hosts, titles, orphan links, content and health preservation.\n";
}finally{$mysql->query("DROP DATABASE `$name`");$mysql->close();}
