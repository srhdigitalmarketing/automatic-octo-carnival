<?php
// Only a disposable MySQL instance: php tests/upnshare_mysql_test.php 13389
if (!isset($argv[1]) || (int)$argv[1] !== 13389) { echo "SKIP: supply isolated MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli('127.0.0.1','root','','',13389);
$name = 'upn_test_' . bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$name`");
foreach (['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$name,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value) { putenv('database.default.' . $key . '=' . $value); }
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
function check($ok,$message) { if(!$ok) { throw new RuntimeException($message); } }
try {
    $db = db_connect();
    check($db->database === $name, 'Only disposable database is used');
    $db->query('CREATE TABLE movies (id INT PRIMARY KEY)');
    $db->query('INSERT INTO movies VALUES (1)');
    $db->query("CREATE TABLE third_party_apis (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(128), provider VARCHAR(30), api_token VARCHAR(255), api_base_url VARCHAR(255), status VARCHAR(20), created_at DATETIME, updated_at DATETIME)");
    $db->query("CREATE TABLE links (id INT AUTO_INCREMENT PRIMARY KEY, movie_id INT, api_id INT NULL, link VARCHAR(1000), type VARCHAR(30), host_priority INT DEFAULT 100, failure_count INT DEFAULT 0, is_broken TINYINT DEFAULT 0, last_checked_at DATETIME NULL, last_success_at DATETIME NULL, last_failure_at DATETIME NULL, last_served_at DATETIME NULL, last_error VARCHAR(255) NULL, upnshare_video_id VARCHAR(128) NULL, reports_not_working INT DEFAULT 0, reports_wrong_link INT DEFAULT 0, resolution VARCHAR(20), quality VARCHAR(20), size_val VARCHAR(20), size_lbl VARCHAR(20), created_at DATETIME, updated_at DATETIME)");
    require dirname(__DIR__) . '/app/Database/Migrations/2026-09-08-000001_AddUpnShareHealthStatus.php';
    $migration = new App\Database\Migrations\AddUpnShareHealthStatus(Config\Database::forge());
    $migration->up(); $db->resetDataCache(); $migration->up(); $db->resetDataCache();
    check((new App\Models\LinkModel())->supportsProviderStatus(), 'Migration and idempotent replay');
    $apis = new App\Models\ThirdPartyApi();
    $apiId = $apis->insert(['name'=>'Test account','provider'=>'upnshare','api_token'=>'test-only-token','api_base_url'=>'https://upnshare.com/api/v1','embed_domains'=>'embed.example','status'=>'active']);
    check($apiId !== false, 'Account saved: ' . json_encode($apis->errors()));
    $links = new App\Models\LinkModel();
    $id = $links->insert(['movie_id'=>1,'api_id'=>$apiId,'link'=>'https://embed.example/e/abc123','type'=>'stream']);
    check($id !== false, 'Link saved: ' . json_encode($links->errors()));
    $health = new App\Libraries\VideoHostHealth($links);
    $config = new Config\UpnShare(); $config->apiToken = 'test-only-token';
    $client = new App\Libraries\UpnShareClient($config, static function ($path) {
        return strpos($path,'?') !== false ? ['http'=>200,'body'=>['data'=>[]]] : ['http'=>404,'body'=>['message'=>'Not found']];
    });
    $property = new ReflectionProperty($health,'clients'); $property->setAccessible(true); $property->setValue($health,['api-'.$apiId=>$client]);
    $result = $health->check($links->find($id));
    check($result['status'] === 'deleted' && (int)$links->find($id)->is_broken === 1, 'Confirmed deletion persisted with actual model validation');
    check((int)$links->find($id)->reports_not_working === 1, '404 creates report');
    $health->check($links->find($id));
    check((int)$links->find($id)->reports_not_working === 1, 'Repeated 404 does not inflate reports');
    check($links->findByMovieId(1,'stream',false) === [], 'Deleted link excluded from playback');
    $property->setValue($health,['api-'.$apiId=>new App\Libraries\UpnShareClient($config, static function ($path) { return ['http'=>200,'body'=>['id'=>'abc123','status'=>'ready']]; })]);
    $health->check($links->find($id));
    check((int)$links->find($id)->is_broken === 0 && count($links->findByMovieId(1,'stream',false)) === 1, 'Recovered file returns to playback');
    check((int)$links->find($id)->reports_not_working === 0, 'Recovery clears broken report');
    $links->protect(false)->update($id,['provider_status'=>'deleted','is_broken'=>1]); $links->protect(true);
    $movieModel = new App\Models\MovieModel();
    $method = new ReflectionMethod($movieModel,'addLinks'); $method->setAccessible(true);
    $method->invoke($movieModel,1,[['id'=>$id,'api_id'=>$apiId,'url'=>'https://embed.example/e/new456']], 'stream');
    $changed = $links->find($id);
    check($changed->provider_status === null && (int)$changed->is_broken === 0 && $changed->health_job_checked_at === null, 'Changing URL clears stale deletion and queues fresh check');
    // Duplicate hostnames are ambiguous; never use an old link api_id to override them.
    $apis->insert(['name'=>'Other account','provider'=>'upnshare','api_token'=>'test-only-token','embed_domains'=>'embed.example','status'=>'active']);
    $links->update($id,['api_id'=>null]);
    $result = (new App\Libraries\VideoHostHealth($links))->check($links->find($id));
    check($result['status'] === 'unknown' && strpos($result['message'],'Multiple') !== false, 'Ambiguous account is not marked deleted');
    $table = new App\Controllers\Admin\Ajax\TableData();
    $request = Config\Services::request();
    $requestProperty = new ReflectionProperty($table, 'request'); $requestProperty->setAccessible(true); $requestProperty->setValue($table, $request);
    $filter = new ReflectionMethod($table, 'applyReportedHostFilter'); $filter->setAccessible(true);
    foreach (['embed.example'=>1, 'example'=>0, 'embed.example.evil'=>0, "x' OR 1=1"=>0] as $host=>$expected) {
        $request->setGlobal('get', ['host'=>$host]);
        $builder = $db->table('links'); $filter->invoke($table, $builder);
        check($builder->countAllResults() === $expected, 'Exact host filter: ' . $host);
    }
    $request->setGlobal('get', []);
    $admin = new App\Controllers\Admin\ThirdPartyApis();
    $hostErrors = new ReflectionMethod($admin, 'hostnameErrors'); $hostErrors->setAccessible(true);
    check(count($hostErrors->invoke($admin, ['provider'=>'custom_http','status'=>'active','embed_domains'=>'EMBED.EXAMPLE'])) === 1, 'Reject hostname overlap across different providers');
    $vidId = $apis->insert(['name'=>'Custom account','provider'=>'custom_http','api_token'=>'test-only-token','embed_domains'=>'vid.example','status'=>'active']);
    check($vidId !== false, 'Custom hostname provider accepted by model');
    helper(['form','template','general']);
    $vidHtml = view('admin/third_party_apis/x_panels/main_form', ['tpAPI'=>$apis->find($vidId)]);
    check(strpos($vidHtml, 'Custom hostname health checks') !== false && strpos($vidHtml, 'test-only-token') === false, 'Custom hostname settings render without token');
    $newHtml = view('admin/movies/form_x_panels/stream_links', ['streamLinks'=>[]]);
    check(strpos($newHtml, 'UPNShare account') === false && strpos($newHtml, '[api_id]') === false, 'New link form contains no account selector');
    $apis->update($vidId,['status'=>'paused']);
    // Render actual PHP partials, including persisted account selection and token masking.
    helper(['form','template','general']);
    $html = view('admin/movies/form_x_panels/stream_links', ['streamLinks'=>[$links->find($id)]]);
    check(strpos($html, 'Check failed') !== false && strpos($html, '[api_id]') === false && strpos($html, 'UPNShare account') === false, 'Stream form renders badge without per-link account fields');
    $unregistered = $links->find($id);
    $unregistered->link = 'https://unregistered.example/play/test';
    $hiddenHtml = view('admin/movies/form_x_panels/stream_links', ['streamLinks'=>[$unregistered]]);
    check(strpos($hiddenHtml, 'Server status') === false && strpos($hiddenHtml, 'stream-check-now') === false, 'Unregistered domain has no status or check button');
    check(strpos($newHtml, 'Server status') === false, 'Empty new links have no stale status');
    $html = view('admin/third_party_apis/x_panels/main_form', ['tpAPI'=>$apis->find($apiId)]);
    check(strpos($html, 'upn-token') !== false && strpos($html, 'test-only-token') === false, 'UPN form renders without stored token');
    $apis->where('provider','upnshare')->set(['status'=>'paused'])->update();
    $links->protect(false)->update($id,['provider_status'=>'deleted','is_broken'=>1]); $links->protect(true);
    $command = (new ReflectionClass(App\Commands\CheckStreamHealth::class))->newInstanceWithoutConstructor();
    $command->run([]);
    check($links->find($id)->health_job_checked_at !== null, 'Cron advances separate rotation timestamp');
    check($links->find($id)->provider_status === 'deleted', 'Paused account cannot clear known deletion');
    // Deleted high-priority/preferred links must never block a lower-priority host.
    $links->protect(false)->update($id, ['host_priority'=>100,'provider_status'=>'deleted','is_broken'=>0,
        'last_checked_at'=>date('Y-m-d H:i:s'),'last_success_at'=>date('Y-m-d H:i:s'),'last_error'=>null]);
    $links->protect(true);
    $fallback = $links->insert(['movie_id'=>1,'link'=>'https://fallback.example/e/low123','type'=>'stream','host_priority'=>1,
        'last_checked_at'=>date('Y-m-d H:i:s'),'last_success_at'=>date('Y-m-d H:i:s')]);
    $resolver = new App\Libraries\StreamResolver($links);
    $healthyMethod = new ReflectionMethod($resolver,'isHealthy'); $healthyMethod->setAccessible(true);
    check($healthyMethod->invoke($resolver,$links->find($id)) === false, 'Deleted overrides a fresh successful cache');
    check((int)$resolver->resolve(1,(int)$id)->id === (int)$fallback, 'Deleted preferred priority 100 falls back to priority 1');
    check($resolver->resolve(1,(int)$id,[(int)$fallback]) === null, 'No available host never returns deleted link');
    $links->protect(false)->update($id,['provider_status'=>'available','is_broken'=>0,'last_error'=>null]); $links->protect(true);
    $oldPlayerLink = $links->find($id);
    $api404 = new App\Libraries\UpnShareClient($config, static function($path) { return ['http'=>404,'body'=>null]; });
    $result404 = $api404->videoStatus('abc123');
    $persist404 = new ReflectionMethod($health,'persist'); $persist404->setAccessible(true);
    $persist404->invoke($health,$links->find($id),$result404);
    check($links->find($id)->provider_status === 'unknown' && (int)$links->find($id)->is_broken === 1, 'Unconfirmed API 404 skips playback but is not Deleted');
    check((int)$resolver->resolve(1,(int)$id)->id === (int)$fallback, 'API 404 at priority 100 rotates to non-API link at priority 1');
    $staleSuccess = new ReflectionMethod($resolver,'recordSuccess'); $staleSuccess->setAccessible(true);
    check($staleSuccess->invoke($resolver,$oldPlayerLink) === false, 'Concurrent cached player success cannot undo API 404 exclusion');
    $resolver->recordPlayerFailure((int)$id);
    check((int)$links->find($id)->is_broken === 1, 'Player failure report cannot reactivate API 404');
    $stale = $links->find($fallback);
    $links->protect(false)->update($fallback,['provider_status'=>'deleted','is_broken'=>1]); $links->protect(true);
    $success = new ReflectionMethod($resolver,'recordSuccess'); $success->setAccessible(true);
    check($success->invoke($resolver,$stale) === false, 'Stale player success cannot override concurrent API deletion');
    check((int)$links->find($fallback)->is_broken === 1, 'Deleted host stays blocked after stale success');
    $links->protect(false)->update($id,['provider_status'=>'available','is_broken'=>0,'last_error'=>null]); $links->protect(true);
    $db->table('links')->where('id', $id)->update(['last_checked_at'=>'2020-01-01 00:00:00','last_success_at'=>'2020-01-01 00:00:00','reports_not_working'=>2]);
    check((int)$resolver->resolve(1)->id === (int)$id, 'Recovered API host returns to eligible priority ordering');
    $served = $links->find($id);
    check($served->last_checked_at === '2020-01-01 00:00:00' && $served->last_success_at === '2020-01-01 00:00:00', 'Serving URL falsely refreshed health timestamps');
    check((int)$served->reports_not_working === 2, 'Serving URL cleared unresolved reports');
    $links->delete($fallback);
    $migration->down(); $db->resetDataCache();
    check(!in_array('provider_status',$db->getFieldNames('links'),true), 'Migration rollback');
    check($db->table('links')->countAllResults() === 1, 'Migration preserves video links');
    echo "PASS: real MySQL migration/replay, account/link save, deletion, recovery, URL reset, account ambiguity and rollback.\n";
} finally { $mysql->query("DROP DATABASE `$name`"); $mysql->close(); }
