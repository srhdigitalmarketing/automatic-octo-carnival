<?php
require __DIR__ . '/upnshare_health_test.php';
$calls=0;
foreach ([200=>'reachable',204=>'reachable',404=>'unknown',410=>'unknown',522=>'unknown',500=>'unknown',302=>'unknown',0=>'unknown'] as $code=>$state) {
 $client=new App\Libraries\CustomHostClient(static function($url,$target) use($code,&$calls) { $calls++; check($target['ip']==='8.8.8.8','Validated pinned address'); return $code; });
 $result=$client->videoStatus('https://8.8.8.8/embed/example');
 check($result['status']===$state,'Custom HTTP status');
 if ($code>=400 || $code===0) {
  check($result['skip_playback']===true,'Custom error rotates');
  $link=new App\Entities\Link(['id'=>42,'provider_status'=>'reachable']);
  $persist->invoke($health,$link,$result);
  check((int)$link->is_broken===1,'Custom failed host excluded');
  $persist->invoke($health,$link,['status'=>'reachable','message'=>'HTTP 200']);
  check((int)$link->is_broken===0,'Custom host recovers');
 }
}
$before=$calls;
foreach (['http://127.0.0.1/','http://169.254.169.254/','http://10.0.0.1/','http://100.64.0.1/','http://8.8.8.8:8080/','https://user:password@8.8.8.8/','file:///etc/passwd'] as $url) {
 check($client->videoStatus($url)['status']==='unknown','Unsafe URL rejected');
}
check($calls===$before,'Unsafe URLs never requested');
check($validate->invoke($admin,['provider'=>'custom_http','embed_domains'=>'player.example'])===[],'Custom hostname accepts no token');
helper('form');
$html=view('admin/third_party_apis/x_panels/main_form',['tpAPI'=>new App\Entities\ThirdPartyApi(['provider'=>'custom_http','name'=>'Example','embed_domains'=>'player.example'])]);
check(strpos($html,'name="api_token"')===false && strpos($html,'Save Custom hostname')!==false,'Custom form has no token field');
echo "PASS: custom hostname HTTP states, rotation/recovery, private URL rejection and token-free form.\n";
