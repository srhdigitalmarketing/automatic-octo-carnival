<?php
require __DIR__.'/../app/Libraries/HistatsCode.php';
$values=[];
function get_config($key){return $GLOBALS['values'][$key]??null;}
function renderTag($context){$histatsContext=$context;ob_start();include __DIR__.'/../app/Views/partials/histats.php';return ob_get_clean();}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
$code=file_get_contents(__DIR__.'/fixtures/histats-async.html');
check(trim(renderTag('embed'))==='','Default must be off');
$values=['histats_code'=>$code,'histats_enabled'=>true,'histats_scope'=>'embed'];
check(strpos(renderTag('embed'),$code)!==false,'Official code changed or missing');
check(trim(renderTag('public'))==='','Embed scope leaked');
$values['histats_scope']='public';check(strpos(renderTag('public'),$code)!==false,'Public scope missing');
check(trim(renderTag('admin'))==='','Admin tracked');
$values['histats_enabled']=false;check(trim(renderTag('embed'))==='','Disabled code executed');
$values=['ga4_enabled'=>true,'ga4_measurement_id'=>'G-TEST12345'];check(trim(renderTag('embed'))==='','Old GA settings enabled tracking');
check(!\App\Libraries\HistatsCode::isValid(str_replace('hs.async = true','hs.async = false',$code)),'Blocking loader accepted');
check(!\App\Libraries\HistatsCode::isValid($code.'<script>document.write("bad")</script>'),'document.write accepted');
check(!\App\Libraries\HistatsCode::isValid(str_repeat('x',20001).$code),'Oversized code accepted');
echo "PASS: HiStats default off, intact code, public/embed scopes, legacy GA ignored and async validation.\n";
