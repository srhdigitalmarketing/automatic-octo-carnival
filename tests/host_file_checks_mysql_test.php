<?php
// This test must never use a website database.
if (!isset($argv[1]) || (int)$argv[1] !== 13389) { echo "SKIP: supply isolated MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli('127.0.0.1','root','','',13389);
$databaseName = 'file_checks_'.bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach (['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$databaseName,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value) putenv('database.default.'.$key.'='.$value);
define('FCPATH', dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
use App\Libraries\HostFileChecks;
use App\Libraries\RegisteredStreamHost;
use App\Libraries\VideoHostHealth;
function check($ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function fresh(): void { HostFileChecks::reset(); RegisteredStreamHost::reset(); }
function controller(string $class, array $post = [], array $get = [], string $method = 'post') {
    $controller = new $class();
    $request = Config\Services::request(null, false);
    $request->setMethod($method)->setGlobal('post',$post)->setGlobal('get',$get)->setHeader('X-Requested-With','XMLHttpRequest');
    foreach (['request'=>$request,'response'=>Config\Services::response(null,false)] as $property=>$value) {
        $field = new ReflectionProperty($controller,$property); $field->setAccessible(true); $field->setValue($controller,$value);
    }
    return $controller;
}
try {
    $db = db_connect();check($db->database === $databaseName,'Disposable database only');
    $db->query('CREATE TABLE settings (name VARCHAR(255) PRIMARY KEY, value TEXT, data_type VARCHAR(20)) ENGINE=InnoDB');
    $db->table('settings')->insert(['name'=>'site_name','value'=>'Original site','data_type'=>'string']);
    $db->query('CREATE TABLE movies (id INT PRIMARY KEY, title VARCHAR(255), views INT) ENGINE=InnoDB');
    $db->table('movies')->insert(['id'=>1,'title'=>'Original title','views'=>456]);
    $db->query('CREATE TABLE third_party_apis (id INT PRIMARY KEY, name VARCHAR(128), provider VARCHAR(30), status VARCHAR(20), embed_domains VARCHAR(1000), api_token VARCHAR(255), api_base_url VARCHAR(255), created_at DATETIME, updated_at DATETIME) ENGINE=InnoDB');
    $accounts = [1=>['upnshare','embed.example','active'],2=>['serverdothost','9.9.9.9','active'],3=>['cloudflare_r2','r2.example','active'],4=>['custom_http','custom.example','paused'],5=>['vod_catalog','vod.example','active']];
    foreach ($accounts as $id=>[$provider,$domains,$status]) $db->table('third_party_apis')->insert(['id'=>$id,'name'=>'Account '.$id,'provider'=>$provider,'status'=>$status,'embed_domains'=>$domains,'api_token'=>'preserve-token-'.$id,'api_base_url'=>'https://example.test/api']);
    $db->query("CREATE TABLE links (id INT PRIMARY KEY, movie_id INT, api_id INT, link VARCHAR(1000), type VARCHAR(30), is_broken TINYINT DEFAULT 0, host_priority INT DEFAULT 17, failure_count INT DEFAULT 0, last_checked_at DATETIME, last_success_at DATETIME, last_failure_at DATETIME, last_served_at DATETIME, last_error VARCHAR(255), provider_status VARCHAR(30), provider_message VARCHAR(255), provider_checked_at DATETIME, health_job_checked_at DATETIME, reports_not_working INT DEFAULT 7, reports_wrong_link INT DEFAULT 2, created_at DATETIME, updated_at DATETIME) ENGINE=InnoDB");
    foreach ([1=>'https://embed.example/e/abc123',2=>'https://9.9.9.9/embed/abc123'] as $id=>$url) $db->table('links')->insert(['id'=>$id,'movie_id'=>1,'api_id'=>$id,'link'=>$url,'type'=>'stream','is_broken'=>1,'provider_status'=>'deleted']);
    $apis = new App\Models\ThirdPartyApi();$api = $apis->find(1);$sdh = $apis->find(2);
    $originalApis = $db->table('third_party_apis')->orderBy('id')->get()->getResultArray();
    $originalMovies = $db->table('movies')->get()->getResultArray();
    $originalLinks = $db->table('links')->orderBy('id')->get()->getResultArray();
    fresh();
    check(HostFileChecks::enabled($api) && !HostFileChecks::enabled($sdh),'UPN on and ServerDotHost off by default');
    check(HostFileChecks::enabled($apis->find(4)) && HostFileChecks::enabled($apis->find(5)),'Custom and VOD default on');
    check(!HostFileChecks::supported($apis->find(3)),'R2 has no file switch');
    check(RegisteredStreamHost::matches($originalLinks[0]['link']) && !RegisteredStreamHost::matches($originalLinks[1]['link']),'Default eligibility');
    helper(['form','template','general']);
    $enabledHtml = view('admin/third_party_apis/x_panels/file_check_control',['api'=>$api]);
    $disabledHtml = view('admin/third_party_apis/x_panels/file_check_control',['api'=>$sdh]);
    check(strpos($enabledHtml,'data-saved="1"')!==false && strpos($disabledHtml,'data-saved="0"')!==false,'Partial renders saved state');
    check(strpos($enabledHtml,'preserve-token')===false,'Control does not expose credentials');
    check(trim(view('admin/third_party_apis/x_panels/file_check_control',['api'=>$apis->find(3)]))==='','No R2 control');
    HostFileChecks::save($api,false);HostFileChecks::save($api,false);fresh();
    check(!HostFileChecks::enabled($api) && !RegisteredStreamHost::matches($originalLinks[0]['link']),'Persisted disable applies across requests');
    check($db->table('settings')->where('name','host_file_check_1')->countAllResults()===1,'Repeated save is idempotent');
    check($db->table('third_party_apis')->orderBy('id')->get()->getResultArray()===$originalApis,'Switch preserves provider, credentials, active status and domains');
    check($db->table('links')->orderBy('id')->get()->getResultArray()===$originalLinks,'Saving toggle does not change content or link rows');
    check($db->table('settings')->where('name','site_name')->get()->getRow()->value==='Original site','Unrelated setting preserved');
    $links = new App\Models\LinkModel();$health = new VideoHostHealth($links);$calls = 0;
    $config = new Config\UpnShare();$config->apiToken = 'local-test';
    $transport = new App\Libraries\UpnShareClient($config,static function($path) use (&$calls){$calls++;return ['http'=>200,'body'=>['id'=>'abc123','status'=>'ready']];});
    $clients = new ReflectionProperty($health,'clients');$clients->setAccessible(true);$clients->setValue($health,['api-1'=>$transport]);
    check($health->check($links->find(1))===null && $calls===0,'Disabled host sends no file request');
    check(count($links->findByMovieId(1,'stream',false))===2,'Historic broken flags do not prevent playback while off');
    $resolver = new App\Libraries\StreamResolver($links);
    $before = $links->find(1)->toRawArray();$resolver->recordPlayerFailure(1,'Timeout');
    check($links->find(1)->toRawArray()===$before,'Timeout report cannot mark disabled host Broken');
    check($resolver->check($links->find(1))===false && $calls===0,'Cron resolver skips host');
    $response = controller(App\Controllers\Admin\StreamHealth::class,[],['id'=>1])->check();
    check($response->getStatusCode()===422,'Manual health route rejects disabled host before probing');
    $response = controller(App\Controllers\Admin\BulkLinkFix::class,['host'=>'embed.example'])->run();
    check($response->getStatusCode()===422,'Bulk Fix cannot bypass switch');
    $deleted = ['status'=>'deleted','message'=>'HTTP 404'];
    check((new App\Libraries\UpnShareReplacement())->replace($links->find(1),$deleted)===$deleted,'Disabled host replacement skips API and URL changes');
    check($links->activateUnregisteredStream($links->find(1)),'Old false broken flag can be cleared');
    check((int)$links->find(1)->is_broken===0 && $links->find(1)->provider_status===null,'Returns Active');
    foreach (['link','movie_id','api_id','host_priority','reports_not_working','reports_wrong_link'] as $field) check((string)$links->find(1)->$field===(string)$before[$field],'Activation preserves '.$field);
    HostFileChecks::save($api,true);fresh();
    check(RegisteredStreamHost::matches($originalLinks[0]['link']),'Re-enable restores eligibility');
    check($health->check($links->find(1))['status']==='available' && $calls===1,'Enabled provider runs actual health client');
    $before = $links->find(1)->toRawArray();
    $clients->setValue($health,['api-1'=>new App\Libraries\UpnShareClient($config,static function($path) use ($api){HostFileChecks::save($api,false);return ['http'=>200,'body'=>['id'=>'abc123','status'=>'deleted']];})]);
    $result = $health->check($links->find(1));
    check($result['status']==='unknown' && $links->find(1)->toRawArray()===$before,'Disable during pending request discards returned result');
    HostFileChecks::save($sdh,true);fresh();
    check(RegisteredStreamHost::matches($originalLinks[1]['link']),'ServerDotHost can be explicitly enabled');
    $sdhCalls=0;$clients->setValue($health,['api-2'=>new App\Libraries\CustomHostClient(static function($url,$target) use (&$sdhCalls){$sdhCalls++;return 200;})]);
    check($health->check($links->find(2))['status']==='reachable' && $sdhCalls===1,'Opt-in ServerDotHost uses bounded HTTP check');
    HostFileChecks::save($sdh,false);fresh();
    check($health->check($links->find(2))===null && $sdhCalls===1,'Opt-out stops further ServerDotHost probes');
    $paused=$apis->find(4);HostFileChecks::save($paused,true);fresh();
    check(!RegisteredStreamHost::matches('https://custom.example/e/test'),'Paused account not activated by switch');
    check(strpos(view('admin/third_party_apis/x_panels/file_check_control',['api'=>$paused]),'API kembali Active')!==false,'Paused preference explained');
    $endpoint=App\Controllers\Admin\ThirdPartyApis::class;
    $response=controller($endpoint,['api_id'=>'1','enabled'=>'1'])->fileCheck();
    check($response->getStatusCode()===200 && json_decode($response->getBody(),true)['enabled']===true,'Controller persists enabled boolean');
    foreach ([['api_id'=>'1','enabled'=>'2'],['api_id'=>'0','enabled'=>'1'],['api_id'=>'999','enabled'=>'1'],['api_id'=>'3','enabled'=>'1']] as $invalid) check(controller($endpoint,$invalid)->fileCheck()->getStatusCode()===400,'Invalid switch rejected');
    check(controller($endpoint,['api_id'=>'1','enabled'=>'0'],[],'get')->fileCheck()->getStatusCode()===405,'GET cannot change setting');
    fresh();check(HostFileChecks::enabled($api),'Failed/GET requests preserve saved state');
    check($db->table('third_party_apis')->orderBy('id')->get()->getResultArray()===$originalApis && $db->table('movies')->get()->getResultArray()===$originalMovies,'All toggles preserve API accounts and movies');
    $db->query('ALTER TABLE settings RENAME COLUMN value TO unavailable_value');$db->resetDataCache();fresh();
    check(!HostFileChecks::available() && !RegisteredStreamHost::available(),'Unreadable settings cannot trigger mass reactivation');
    $before=$links->find(2)->toRawArray();check(!$links->activateUnregisteredStream($links->find(2)) && $links->find(2)->toRawArray()===$before,'Database read failure preserves link state');
    check(controller($endpoint,['api_id'=>'1','enabled'=>'0'])->fileCheck()->getStatusCode()===400,'Schema failure returns controlled save error');
    echo "PASS: saved defaults, off/on, player eligibility, no disabled probes, pending-result guard, admin and bulk guards, opt-in SDH, paused accounts, content preservation, validation and DB failures\n";
} finally {
    if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
    $mysql->query("DROP DATABASE `$databaseName`");$mysql->close();
}
