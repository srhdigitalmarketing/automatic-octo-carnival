<?php
namespace App\Libraries {
 function curl_init($url) { $GLOBALS['r2_url']=$url; return new \stdClass(); }
 function curl_setopt_array($handle,$options) { $GLOBALS['r2_options']=$options; return true; }
 function curl_exec($handle) { return ''; }
 function curl_getinfo($handle,$option) { return $GLOBALS['r2_http'] ?? 200; }
 function curl_error($handle) { return ''; }
 function curl_close($handle) {}
}
namespace {
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
function check($ok,$message) { if (!$ok) throw new RuntimeException($message); }
foreach (['upnshare','vidhide'] as $provider) {
 $api=(object)['provider'=>$provider,'status'=>'active','api_token'=>'private-test-token'];
 foreach ([
  [['http'=>200,'body'=>$provider==='upnshare' ? ['data'=>[]] : ['status'=>200,'result'=>['login'=>'test']]],'connected'],
  [['http'=>401,'body'=>['data'=>[]]],'disconnected'],
  [['http'=>200,'body'=>['status'=>403,'message'=>'private-test-token']],'disconnected'],
  [['http'=>200,'body'=>[]],'disconnected'],
  [['http'=>500,'body'=>null],'disconnected'],
 ] as [$response,$state]) {
  $service=new App\Libraries\ProviderConnection(static function() use($response) { return $response; });
  $result=$service->check($api);
  check($result['state']===$state,'Provider response classification');
  check(strpos(json_encode($result),'private-test-token')===false,'No token in result');
 }
 $service=new App\Libraries\ProviderConnection(static function() { throw new RuntimeException('private-test-token'); });
 check($service->check($api)['state']==='disconnected','Timeout/exception handled');
 $api->status='paused';
 check($service->check($api)['state']==='paused','Paused provider not requested');
}
$api=(object)['provider'=>'cloudflare_r2','status'=>'active','r2_account_id'=>str_repeat('a',32),'r2_access_key_id'=>'key','r2_secret_access_key'=>'secret','r2_bucket'=>'test-bucket','r2_public_url'=>'https://media.example'];
check((new App\Libraries\ProviderConnection())->check($api)['state']==='connected','R2 head success');
check($GLOBALS['r2_options'][CURLOPT_CUSTOMREQUEST]==='HEAD' && $GLOBALS['r2_options'][CURLOPT_NOBODY]===true,'R2 uses read-only HEAD');
check($GLOBALS['r2_url']==='https://'.str_repeat('a',32).'.r2.cloudflarestorage.com/test-bucket','Correct bucket endpoint');
$GLOBALS['r2_http']=403;
check((new App\Libraries\ProviderConnection())->check($api)['state']==='disconnected','R2 denied access');
echo "PASS: connection success, auth failure, malformed response, paused, secret redaction and read-only R2 HEAD.\n";
}
