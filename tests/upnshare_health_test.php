<?php
// No live API calls or database connection: actual framework entities with mocked persistence/transport.
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
// No saved per-host overrides in this unit fixture; never connect to a real DB.
$noOverrides = new class([]) extends CodeIgniter\Test\Mock\MockConnection {
    public function tableExists(string $tableName): bool { return false; }
};
$connections = new ReflectionProperty(Config\Database::class, 'instances');
$connections->setAccessible(true);$connections->setValue(null, ['default'=>$noOverrides,'tests'=>$noOverrides]);
$registered = new ReflectionProperty(App\Libraries\RegisteredStreamHost::class, 'domains'); $registered->setAccessible(true); $registered->setValue(null, ['ustreamplay.online,vid.example']);
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
$config = new Config\UpnShare(); $config->apiToken = 'test-only-token';
$calls = [];
function client(array $responses): App\Libraries\UpnShareClient {
    global $config, $calls; $calls = [];
    return new App\Libraries\UpnShareClient($config, static function ($path) use (&$responses) {
        $GLOBALS['calls'][] = $path;
        if (!$responses) { throw new RuntimeException('Unexpected API call: ' . $path); }
        return array_shift($responses);
    });
}
foreach (['ready'=>'available','deleted'=>'deleted','failed'=>'error','processing'=>'processing','new-provider-state'=>'unknown'] as $input=>$expected) {
    $result = client([['http'=>200,'body'=>['id'=>'abc123','status'=>$input]]])->videoStatus('abc123');
    check($result['status'] === $expected, 'Status mapping: ' . $input);
    check($calls === ['/video/manage/abc123'], 'Only documented detail path');
}
$c = client([['http'=>404,'body'=>['message'=>'Not found']], ['http'=>200,'body'=>['data'=>[]]], ['http'=>404,'body'=>['message'=>'Not found']]]);
check($c->videoStatus('abc123')['status'] === 'deleted', 'Verified missing file');
check($c->videoStatus('def456')['status'] === 'deleted', 'Inventory validation reused within batch');
check(count($calls) === 3 && $calls[1] === '/video/manage?page=1&perPage=1', 'One inventory verification');
foreach ([0,400,401,403,429,500,502] as $code) {
    check(client([['http'=>$code,'body'=>['message'=>'Not found']]])->videoStatus('abc123')['status'] === 'unknown', 'HTTP failure never deleted: ' . $code);
}
foreach ([null, '<html>Not found</html>', ['error'=>'Not found']] as $body) {
    check(client([['http'=>404,'body'=>$body]])->videoStatus('abc123')['status'] === 'unknown', 'Proxy/malformed 404 is inconclusive');
}
check(client([['http'=>404,'body'=>['message'=>'Not found']], ['http'=>401,'body'=>null]])->videoStatus('abc123')['status'] === 'unknown', 'Unverified account cannot mark deleted');
check(client([['http'=>200,'body'=>['id'=>'other','status'=>'ready']]])->videoStatus('abc123')['status'] === 'unknown', 'Reject wrong video response');
check(client([])->videoStatus('../abc')['status'] === 'unknown' && $calls === [], 'Reject invalid ID before request');
check(App\Libraries\VideoHostHealth::matchesHost('https://embed.example/e/abc123', 'embed.example, upnshare.com'), 'Exact embed host mapping');
check(!App\Libraries\VideoHostHealth::matchesHost('https://embed.example.evil.test/e/abc123', 'embed.example'), 'Reject suffix host mismatch');
check(App\Libraries\VideoHostHealth::videoId('https://embed.example/#abc123') === 'abc123', 'Fragment video ID');
check(App\Libraries\VideoHostHealth::videoId('https://embed.example/e/abc123?x=1') === 'abc123', 'Path video ID');
class MemoryLinks extends App\Models\LinkModel {
    public $saved = [];
    public function __construct() {}
    public function supportsProviderStatus(): bool { return true; }
    public function update($id = null, $data = null): bool { $this->saved = $data; return true; }
}
$links = new MemoryLinks(); $health = new App\Libraries\VideoHostHealth($links);
$persist = new ReflectionMethod($health, 'persist'); $persist->setAccessible(true);
$link = new App\Entities\Link(['id'=>1,'provider_status'=>null]);
$persist->invoke($health, $link, ['status'=>'deleted','message'=>'File missing']);
check((int)$links->saved['is_broken'] === 1 && $link->provider_status === 'deleted', 'Deleted blocked immediately');
$persist->invoke($health, $link, ['status'=>'unknown','message'=>'HTTP 429']);
check($link->provider_status === 'deleted' && (int)$links->saved['is_broken'] === 1, 'Failed check cannot resurrect deleted');
$persist->invoke($health, $link, ['status'=>'available','message'=>'Ready']);
check($link->provider_status === 'available' && (int)$links->saved['is_broken'] === 0 && $links->saved['last_error'] === null, 'Available restores link');
$persist->invoke($health, $link, ['status'=>'unknown','message'=>'HTTP 401']);
check($link->provider_status === 'unknown' && !isset($links->saved['is_broken']), 'Unknown does not mark playable link deleted');
// Exercise actual table badge rendering without a controller/database constructor.
$controller = (new ReflectionClass(App\Controllers\Admin\Ajax\TableData::class))->newInstanceWithoutConstructor();
$statusMethod = new ReflectionMethod($controller, 'streamLinkStatus'); $statusMethod->setAccessible(true);
$labelsMethod = new ReflectionMethod($controller, 'videoServerLabels'); $labelsMethod->setAccessible(true);
check($statusMethod->invoke($controller, ['provider_status'=>'deleted','is_broken'=>1], true) === 'deleted', 'Provider badge overrides generic broken');
$html = $labelsMethod->invoke($controller, [['name'=>'UPN <test>','status'=>'deleted']]);
check(strpos($html, 'Deleted') !== false && strpos($html, '<test>') === false, 'Visible, escaped Deleted badge');
// Actual admin validation: blank token is retained on edit by controller; malformed inputs rejected.
$admin = (new ReflectionClass(App\Controllers\Admin\ThirdPartyApis::class))->newInstanceWithoutConstructor();
$validate = new ReflectionMethod($admin, 'providerErrors'); $validate->setAccessible(true);
check($validate->invoke($admin, ['provider'=>'upnshare','api_token'=>'token','embed_domains'=>'embed.example']) === [], 'Valid account settings');
check(count($validate->invoke($admin, ['provider'=>'upnshare','api_token'=>'','embed_domains'=>'https://embed.example/e/id'])) === 2, 'Reject blank token and URL instead of hostname');

foreach ([200,404,410] as $fileStatus) {
    $c = new App\Libraries\StreamHgClient('test-only-key', static function ($id) use ($fileStatus) {
        return ['http'=>200, 'body'=>['status'=>200, 'result'=>[['file_code'=>$id,'status'=>$fileStatus,'canplay'=>1]]]];
    });
    check($c->videoStatus('vid123')['status'] === ($fileStatus === 200 ? 'available' : 'deleted'), 'StreamHg per-file status');
}
foreach ([
 ['http'=>404,'body'=>null],
 ['http'=>200,'body'=>['status'=>403]],
 ['http'=>200,'body'=>['status'=>200,'result'=>[['file_code'=>'other','status'=>404]]]],
 ['http'=>200,'body'=>['status'=>200,'result'=>[['file_code'=>'vid123','status'=>200,'canplay'=>null]]]],
] as $response) {
    $c = new App\Libraries\StreamHgClient('test-only-key', static function ($id) use ($response) { return $response; });
    check($c->videoStatus('vid123')['status'] === 'unknown', 'StreamHg inconclusive response cannot mean deleted');
}
$c = new App\Libraries\StreamHgClient('test-only-key', static function ($id) {
 return ['http'=>200,'body'=>['status'=>200,'result'=>[['file_code'=>$id,'status'=>200,'canplay'=>0]]]];
});
check($c->videoStatus('vid123')['status'] === 'error', 'StreamHg unplayable file is Error');
$hostHealth = new App\Libraries\VideoHostHealth(new MemoryLinks());
$apisProperty = new ReflectionProperty($hostHealth, 'apis'); $apisProperty->setAccessible(true);
$clientsProperty = new ReflectionProperty($hostHealth, 'clients'); $clientsProperty->setAccessible(true);
$apisProperty->setValue($hostHealth, [
 (object)['id'=>1,'provider'=>'upnshare','embed_domains'=>'ustreamplay.online'],
 (object)['id'=>2,'provider'=>'streamhg','embed_domains'=>'vid.example'],
]);
$seen = [];
$clientsProperty->setValue($hostHealth, [
 'api-1'=>new App\Libraries\UpnShareClient($config, static function($path) use (&$seen) { $seen[]='upn'; return ['http'=>200,'body'=>['id'=>'upn123','status'=>'ready']]; }),
 'api-2'=>new App\Libraries\StreamHgClient('test-only-key', static function($id) use (&$seen) { $seen[]=$id; return ['http'=>200,'body'=>['status'=>200,'result'=>[['file_code'=>$id,'status'=>200,'canplay'=>1]]]]; }),
]);
check($hostHealth->check(new App\Entities\Link(['id'=>1,'api_id'=>2,'link'=>'https://ustreamplay.online/e/upn123','upnshare_video_id'=>'stale']))['status'] === 'available', 'UPN selected by hostname despite stale account/id');
check($hostHealth->check(new App\Entities\Link(['id'=>2,'api_id'=>1,'link'=>'https://vid.example/embed-vid123.html','upnshare_video_id'=>'stale'])) === null, 'Retired provider cannot run a file check through a stale API fixture');
check($seen === ['upn'], 'Supported host dispatched by hostname; retired provider skipped');
check($hostHealth->check(new App\Entities\Link(['id'=>3,'api_id'=>1,'link'=>'https://unconfigured.example/e/vid123'])) === null, 'Unconfigured host cannot use stale account');
check(App\Libraries\VideoHostHealth::videoId('https://ustreamplay.online/#9aboc') === '9aboc', 'User-reported fragment ID extracted correctly');
$unknownHealth = new class(new MemoryLinks()) extends App\Libraries\VideoHostHealth {
    public function check(App\Entities\Link $link): ?array { return ['status'=>'unknown','message'=>'HTTP 401']; }
};
$resolver = new App\Libraries\StreamResolver(new MemoryLinks());
$healthProperty = new ReflectionProperty($resolver,'hostHealth'); $healthProperty->setAccessible(true); $healthProperty->setValue($resolver,$unknownHealth);
$healthyMethod = new ReflectionMethod($resolver,'isHealthy'); $healthyMethod->setAccessible(true);
check($healthyMethod->invoke($resolver,new App\Entities\Link(['id'=>1,'link'=>'https://ustreamplay.online/#9aboc']),true) === false, 'Unknown API must not fall back to HTTP success');
foreach ([['http'=>404,'body'=>null], ['http'=>410,'body'=>null], ['http'=>200,'body'=>['status'=>404]]] as $failure) {
    $vid404 = new App\Libraries\StreamHgClient('test-key', static function($id) use ($failure) { return $failure; });
    $result = $vid404->videoStatus('x4qkbbog3gc4');
    check($result['status'] === 'unknown' && $result['skip_playback'] === true, 'StreamHg missing endpoint/API record skips playback without false Deleted');
    $link404 = new App\Entities\Link(['id'=>99,'provider_status'=>'available']);
    $persist->invoke($health, $link404, $result);
    check((int)$link404->is_broken === 1, 'StreamHg 404 excluded from playback');
}
$upn522 = client([['http'=>522,'body'=>null]])->videoStatus('abc123');
check($upn522['status'] === 'unknown' && $upn522['skip_playback'] === true, 'UPNShare 522 rotates without false Deleted');
foreach ([['http'=>522,'body'=>null], ['http'=>200,'body'=>['status'=>522]], ['http'=>200,'body'=>['status'=>200,'result'=>[['file_code'=>'abc123','status'=>522]]]]] as $response522) {
    $vid522 = new App\Libraries\StreamHgClient('test-key', static function($id) use ($response522) { return $response522; });
    $result522 = $vid522->videoStatus('abc123');
    check($result522['status'] === 'unknown' && $result522['skip_playback'] === true, 'StreamHg 522 rotates at HTTP/API/file levels');
    $failed522 = new App\Entities\Link(['id'=>99,'provider_status'=>'available']);
    $persist->invoke($health,$failed522,$result522);
    check((int)$failed522->is_broken === 1 && $failed522->provider_status === 'unknown', '522 excluded from playback');
    $persist->invoke($health,$failed522,['status'=>'available','message'=>'Recovered']);
    check((int)$failed522->is_broken === 0, 'Recovery after 522 restores host');
}
echo "PASS: HTTP/API/file 522 skip playback and recover without marking Deleted.\n";
echo "PASS: Legacy client responses and supported-host routing, including stale account/video IDs.\n";
echo "PASS: UPNShare API responses, account verification, host/ID matching, persistence recovery, badges and admin validation.\n";
