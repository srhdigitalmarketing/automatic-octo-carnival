<?php
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$config = new Config\UpnShare(); $config->apiToken = 'test-token';
function attempt($records, $expected, $status='ready') {
 global $config;
 $calls=0;
 $client=new App\Libraries\UpnShareClient($config,function($path) use ($records,$status,&$calls) {
  $calls++;
  if ($calls===1) return ['http'=>200,'body'=>['data'=>$records,'metadata'=>['maxPage'=>1]]];
  return ['http'=>200,'body'=>['id'=>'new123','status'=>$status]];
 });
 if ($client->replacementId('No Copyright Drone Shots','old123') !== $expected) throw new RuntimeException('Unsafe replacement selection');
}
$record=['id'=>'new123','name'=>'No Copyright Drone Shots','status'=>'ready'];
attempt([$record], 'new123');
attempt([$record,array_merge($record,['id'=>'another123'])],null);
attempt([array_merge($record,['id'=>'old123'])],null);
attempt([array_merge($record,['name'=>'No Copyright Drone Shots 2'])],null);
attempt([$record],null,'processing');
attempt([$record],null,'deleted');
echo "PASS: exact title, unique candidate, old ID exclusion and availability validation\n";
