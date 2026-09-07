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
    check($links->findByMovieId(1,'stream',false) === [], 'Deleted link excluded from playback');
    $property->setValue($health,['api-'.$apiId=>new App\Libraries\UpnShareClient($config, static function ($path) { return ['http'=>200,'body'=>['id'=>'abc123','status'=>'ready']]; })]);
    $health->check($links->find($id));
    check((int)$links->find($id)->is_broken === 0 && count($links->findByMovieId(1,'stream',false)) === 1, 'Recovered file returns to playback');
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
    $admin = new App\Controllers\Admin\ThirdPartyApis();
    $hostErrors = new ReflectionMethod($admin, 'hostnameErrors'); $hostErrors->setAccessible(true);
    check(count($hostErrors->invoke($admin, ['provider'=>'vidhide','status'=>'active','embed_domains'=>'EMBED.EXAMPLE'])) === 1, 'Reject hostname overlap across different providers');
    $vidId = $apis->insert(['name'=>'VidHide account','provider'=>'vidhide','api_token'=>'test-only-token','embed_domains'=>'vid.example','status'=>'active']);
    check($vidId !== false, 'VidHide provider accepted by model');
    helper(['form','template','general']);
    $vidHtml = view('admin/third_party_apis/x_panels/main_form', ['tpAPI'=>$apis->find($vidId)]);
    check(strpos($vidHtml, 'VidHide video health checks') !== false && strpos($vidHtml, 'test-only-token') === false, 'VidHide settings render with token masked');
    $newHtml = view('admin/movies/form_x_panels/stream_links', ['streamLinks'=>[]]);
    check(strpos($newHtml, 'UPNShare account') === false && strpos($newHtml, '[api_id]') === false, 'New link form contains no account selector');
    $apis->update($vidId,['status'=>'paused']);
    // Render actual PHP partials, including persisted account selection and token masking.
    helper(['form','template','general']);
    $html = view('admin/movies/form_x_panels/stream_links', ['streamLinks'=>[$links->find($id)]]);
    check(strpos($html, 'Check failed') !== false && strpos($html, '[api_id]') === false && strpos($html, 'UPNShare account') === false, 'Stream form renders badge without per-link account fields');
    $html = view('admin/third_party_apis/x_panels/main_form', ['tpAPI'=>$apis->find($apiId)]);
    check(strpos($html, 'upn-token') !== false && strpos($html, 'test-only-token') === false, 'UPN form renders without stored token');
    $apis->where('provider','upnshare')->set(['status'=>'paused'])->update();
    $links->protect(false)->update($id,['provider_status'=>'deleted','is_broken'=>1]); $links->protect(true);
    $command = (new ReflectionClass(App\Commands\CheckStreamHealth::class))->newInstanceWithoutConstructor();
    $command->run([]);
    check($links->find($id)->health_job_checked_at !== null, 'Cron advances separate rotation timestamp');
    check($links->find($id)->provider_status === 'deleted', 'Paused account cannot clear known deletion');
    $migration->down(); $db->resetDataCache();
    check(!in_array('provider_status',$db->getFieldNames('links'),true), 'Migration rollback');
    check($db->table('links')->countAllResults() === 1, 'Migration preserves video links');
    echo "PASS: real MySQL migration/replay, account/link save, deletion, recovery, URL reset, account ambiguity and rollback.\n";
} finally { $mysql->query("DROP DATABASE `$name`"); $mysql->close(); }
