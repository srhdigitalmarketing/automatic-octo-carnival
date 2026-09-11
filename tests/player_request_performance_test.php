<?php
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$registered = new ReflectionProperty(App\Libraries\RegisteredStreamHost::class, 'domains'); $registered->setAccessible(true); $registered->setValue(null, ['unresolvable-host.invalid']);
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
$resolver = (new ReflectionClass(App\Libraries\StreamResolver::class))->newInstanceWithoutConstructor();
$config = new ReflectionProperty($resolver, 'config'); $config->setAccessible(true); $config->setValue($resolver, new Config\UpnShare());
$probe = new class {
    public $calls = 0;
    public function check($link) { $this->calls++; return ['status'=>'available']; }
};
$property = new ReflectionProperty($resolver, 'hostHealth'); $property->setAccessible(true); $property->setValue($resolver, $probe);
$healthy = new ReflectionMethod($resolver, 'isHealthy'); $healthy->setAccessible(true);
foreach ([null, 'available', 'reachable', 'unknown'] as $status) {
    $link = new App\Entities\Link(['link'=>'https://unresolvable-host.invalid/embed/test', 'provider_status'=>$status, 'is_broken'=>0]);
    check($healthy->invoke($resolver, $link) === true, 'Unchecked/stale candidate should not wait on DNS or HTTP');
}
foreach (['deleted', 'error', 'processing', 'unknown', null] as $status) {
    $link = new App\Entities\Link(['link'=>'https://unresolvable-host.invalid/embed/test', 'provider_status'=>$status, 'is_broken'=>1]);
    check($healthy->invoke($resolver, $link) === false, 'Broken/deleted candidate became eligible');
}
$link = new App\Entities\Link(['link'=>'https://unresolvable-host.invalid/embed/test', 'is_broken'=>0, 'last_error'=>'Recent failure', 'last_checked_at'=>date('Y-m-d H:i:s')]);
check($healthy->invoke($resolver, $link) === false, 'Recent failure cooldown ignored');
check($probe->calls === 0, 'Viewer request contacted provider');
check($healthy->invoke($resolver, $link, true) === true && $probe->calls === 1, 'Explicit health check no longer contacts provider');
echo "PASS: cold/stale viewer paths avoid DNS/HTTP/API, exclusions and cooldown persist, explicit checks still run.\n";

$unregistered = new App\Entities\Link(['link'=>'https://unregistered.example/video', 'provider_status'=>'deleted', 'is_broken'=>1, 'last_error'=>'Host health check failed']);
check($healthy->invoke($resolver, $unregistered) === true, 'Unregistered host must ignore historical automatic deletion');
$before = $probe->calls;
check($healthy->invoke($resolver, $unregistered, true) === false && $probe->calls === $before, 'Forced check must skip unregistered host without probing');
