<?php
// Only run against the disposable database server used by the audit runner.
if (!isset($argv[1]) || (int)$argv[1] !== 13389) { echo "SKIP: supply isolated MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli('127.0.0.1', 'root', '', '', 13389);
$name = 'sdh_grab_test_'.bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach (['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$name,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value) putenv('database.default.'.$key.'='.$value);
define('FCPATH', dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
use App\Libraries\ServerDotHostCatalog;
use App\Libraries\ServerDotHostStreamGrab;
function check($ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
function row(int $number, string $title = 'Video One', string $status = 'ready'): array {
    return ['id'=>'01990000-0000-7000-8000-'.str_pad((string)$number,12,'0',STR_PAD_LEFT),
        'title'=>$title, 'processing_status'=>$status, 'moderation_status'=>'pending', 'visibility'=>'private',
        'embed_url'=>'https://bobaplayer.com/embed/'.str_repeat('b',32).$number];
}
function client(array $pages, $detail, array &$calls): ServerDotHostCatalog {
    return new ServerDotHostCatalog(static function($url, $headers) use ($pages,$detail,&$calls) {
        $calls[] = $url;
        check(db_connect()->transDepth === 0, 'No database transaction during remote call');
        if (strpos($url, '?') !== false) {
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            return ['http'=>200,'body'=>['data'=>$pages[(int)$query['page'] - 1],
                'meta'=>['current_page'=>(int)$query['page'],'last_page'=>count($pages)]]];
        }
        return is_int($detail) ? ['http'=>$detail,'body'=>['message'=>'private token must never be exposed']]
            : ['http'=>200,'body'=>['data'=>$detail]];
    });
}
function finish(ServerDotHostStreamGrab $grab, array &$job, object $api): array {
    $last=[];
    for ($i=0; $i<50 && !$job['done']; $i++) {
        $result = $grab->step($job,$api);
        if (isset($result['state'])) $last = $result;
    }
    check($job['done'], 'Bounded job completes');
    return $last;
}
try {
    $db = db_connect();
    check($db->database === $name, 'Only disposable database');
    $db->query('CREATE TABLE movies (id INT PRIMARY KEY, title VARCHAR(500), type VARCHAR(30), banner VARCHAR(255), views INT DEFAULT 0) ENGINE=InnoDB');
    $db->query('CREATE TABLE third_party_apis (id INT PRIMARY KEY) ENGINE=InnoDB');
    $db->query('INSERT INTO third_party_apis VALUES (1)');
    $db->query("CREATE TABLE links (id INT AUTO_INCREMENT PRIMARY KEY, movie_id INT, api_id INT NULL, type VARCHAR(30), link VARCHAR(1000), host_priority INT DEFAULT 100, is_broken TINYINT DEFAULT 0, reports_not_working INT DEFAULT 0, created_at DATETIME, updated_at DATETIME) ENGINE=InnoDB");
    $api = (object)['id'=>1,'provider'=>'serverdothost','status'=>'active','api_token'=>'bkp_'.str_repeat('a',64),'embed_domains'=>'bobaplayer.com'];
    $db->table('movies')->insert(['id'=>1,'title'=>'Video One','type'=>'movie','banner'=>'original.jpg','views'=>123]);
    $db->table('links')->insert(['movie_id'=>1,'type'=>'stream','link'=>'https://old.example/play/abc','host_priority'=>17,'is_broken'=>1,'reports_not_working'=>42]);
    $beforeMovie=$db->table('movies')->get()->getRowArray();
    $beforeLink=$db->table('links')->get()->getRowArray();
    $remote=row(1, '  VIDEO   ONE  '); $list=$remote; unset($list['embed_url']);
    $calls=[]; $grab=new ServerDotHostStreamGrab($db,client([[row(2,'Video One Trailer')],[$list]],$remote,$calls));
    $job=$grab->start($api,'test-owner');
    check($calls===[], 'Starting does not fetch remote catalog');
    $db->table('movies')->insert(['id'=>2,'title'=>'Created later','type'=>'episode']);
    $step=$grab->step($job,$api);
    check(count($calls)===1 && !isset($step['state']) && $job['processed']===0,'One page per step, partial matches never imported');
    $step=$grab->step($job,$api);
    check(count($calls)===2 && !isset($step['state']),'Exact match waits for detail');
    $step=$grab->step($job,$api);
    check($step['state']==='success' && $step['done'] && count($calls)===3,'Ready exact title on later page imports');
    check($db->table('movies')->where('id',1)->get()->getRowArray()===$beforeMovie,'Movie title/banner/views unchanged');
    check($db->table('links')->where('id',$beforeLink['id'])->get()->getRowArray()===$beforeLink,'Old URL, status, priority and reports unchanged');
    $new=$db->table('links')->where('id >',$beforeLink['id'])->get()->getRowArray();
    check($new['link']===$remote['embed_url'] && (int)$new['api_id']===1 && (int)$new['is_broken']===0,'Actual embed saved as active stream');
    check($db->table('links')->where('movie_id',2)->countAllResults()===0,'Snapshot excludes movies added later');
    $db->table('movies')->where('id',2)->delete();
    $job=$grab->start($api,'test-owner');$repeat=finish($grab,$job,$api);
    check($repeat['state']==='skipped' && $db->table('links')->countAllResults()===2,'Replay does not duplicate stream');
    check(strpos(json_encode($job),$api->api_token)===false,'Job cache excludes API token');

    $cases=[
        'absent'=>[[],null],
        'processing'=>[[row(1,'Video One','processing')],null],
        'pending'=>[[row(1,'Video One','pending')],null],
        'no embed'=>[[row(1)],array_replace(row(1),['embed_url'=>null])],
        'blocked'=>[[row(1)],array_replace(row(1),['moderation_status'=>'blocked'])],
        'changed processing'=>[[row(1)],row(1,'Video One','processing')],
        'changed remote title'=>[[row(1)],row(1,'Another Video')],
        'detail deleted'=>[[row(1)],404],
        'unregistered embed'=>[[row(1)],array_replace(row(1),['embed_url'=>'https://evil.example/embed/'.str_repeat('a',32)])],
        'same code different title'=>[[row(1,'Video One - English Subtitle')],null],
    ];
    foreach ($cases as $label=>[$items,$detail]) {
        $calls=[];$grab=new ServerDotHostStreamGrab($db,client([$items],$detail,$calls));$job=$grab->start($api,'owner');
        $result=finish($grab,$job,$api);
        check($result['state']==='skipped' && $db->table('links')->countAllResults()===2,$label.' is skipped without DB mutation');
        if ($detail===null) check(count($calls)===1,$label.' skips detail API');
    }
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)],[row(2)]],null,$calls));$job=$grab->start($api,'owner');
    $result=finish($grab,$job,$api);
    check($result['state']==='skipped' && strpos($result['message'],'satu')!==false && count($calls)===2,'Duplicate exact titles across pages skip');
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)],[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');
    $result=finish($grab,$job,$api);
    check(strpos($result['message'],'sudah ada')!==false,'Repeated same UUID across pages is not ambiguous');
    $rows=array_fill(0,12,row(2,'Trailer'));$rows[]=row(1);$rows[]=row(3);
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([$rows],null,$calls));$job=$grab->start($api,'owner');
    $result=finish($grab,$job,$api);
    check(strpos($result['message'],'satu')!==false,'No truncation at 12 rows hides ambiguous matches');
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client(array_fill(0,21,[row(1)]),null,$calls));$job=$grab->start($api,'owner');
    $result=finish($grab,$job,$api);
    check(count($calls)===20 && $result['state']==='skipped','Pagination capped without importing an unverified match');

    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');
    $paused=clone $api;$paused->status='paused';
    try {$grab->step($job,$paused);throw new LogicException('Paused accepted');} catch(RuntimeException $e) {}
    $edited=clone $api;$edited->api_token='bkp_'.str_repeat('z',64);
    try {$grab->step($job,$edited);throw new LogicException('Edited accepted');} catch(RuntimeException $e) {}
    check($calls===[],'Changed or paused API makes no requests');
    foreach([401,403,429,500] as $http) {
        $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],$http,$calls));$job=$grab->start($api,'owner');
        $grab->step($job,$api);
        try {$grab->step($job,$api);throw new LogicException('HTTP error accepted');}
        catch(RuntimeException $e) {check($e->getCode()===$http && $job['processed']===0,'API failure stops instead of counting absent video');}
    }
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');$grab->step($job,$api);
    $db->table('movies')->where('id',1)->update(['title'=>'Locally edited']);
    $result=$grab->step($job,$api);
    check($result['state']==='skipped' && strpos($result['message'],'lokal')!==false,'Concurrent title edit skipped');
    $db->table('movies')->where('id',1)->update(['title'=>'Video One','type'=>'episode']);
    $db->table('links')->where('id',$new['id'])->delete();
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');
    check(finish($grab,$job,$api)['state']==='success','Episodes also receive matching stream');
    $db->table('links')->where('id >',$beforeLink['id'])->delete();
    $db->query('ALTER TABLE links DROP COLUMN host_priority, DROP COLUMN api_id');$db->resetDataCache();
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');
    check(finish($grab,$job,$api)['state']==='success','Legacy optional columns are not required or altered');
    $db->table('links')->where('id >',$beforeLink['id'])->delete();
    $db->query("CREATE TRIGGER reject_grab BEFORE INSERT ON links FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='test save failure'");
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([[row(1)]],row(1),$calls));$job=$grab->start($api,'owner');
    $grab->step($job,$api);
    try {$grab->step($job,$api);throw new LogicException('Failed insert accepted');}
    catch (RuntimeException $e) {}
    check($db->transDepth===0 && $db->table('links')->countAllResults()===1,'Failed insert rolls back without removing old links');
    $db->query('DROP TRIGGER reject_grab');
    $db->table('movies')->where('id',1)->delete();
    $calls=[];$grab=new ServerDotHostStreamGrab($db,client([],null,$calls));$job=$grab->start($api,'owner');
    check($job['total']===0 && $grab->step($job,$api)['done'] && $calls===[],'Empty catalog makes no API calls');
    check(ServerDotHostStreamGrab::titleKey("  Title\u{00a0}  ONE ") === 'title one','Unicode spaces normalized');
    check(ServerDotHostStreamGrab::titleKey('Title-1') !== ServerDotHostStreamGrab::titleKey('Title 1'),'Punctuation remains significant');
    // Exercise the real endpoint, cache ownership and method validation without remote HTTP.
    $fields=['name','provider','api_token','api_base_url','embed_domains','r2_account_id','r2_access_key_id','r2_secret_access_key','r2_bucket','r2_public_url','status','created_at','updated_at'];
    foreach ($fields as $field) $db->query('ALTER TABLE third_party_apis ADD `'.$field.'` VARCHAR(255) NULL');
    $db->resetDataCache();
    $db->table('third_party_apis')->where('id',1)->update((array)$api);
    function endpoint(array $post, string $method='post', bool $ajax=true) {
        $controller=new App\Controllers\Admin\ServerDotHostGrab();
        $request=Config\Services::request(null,false);
        $request->setMethod($method);$request->setGlobal('post',$post);
        if ($ajax) $request->setHeader('X-Requested-With','XMLHttpRequest');
        else $request->removeHeader('X-Requested-With');
        foreach (['request'=>$request,'response'=>Config\Services::response(null,false)] as $name=>$value) {
            $property=new ReflectionProperty($controller,$name);$property->setAccessible(true);$property->setValue($controller,$value);
        }
        return $controller->run();
    }
    check(endpoint(['action'=>'start','api_id'=>1],'get')->getStatusCode()===403,'GET cannot start batch');
    check(endpoint(['action'=>'start','api_id'=>1],'post',false)->getStatusCode()===403,'Non-AJAX cannot start batch');
    $response=endpoint(['action'=>'start','api_id'=>1]);$data=json_decode($response->getBody(),true);
    check($response->getStatusCode()===200 && isset($data['token']) && $data['total']===0,'Endpoint starts with installed framework and config');
    $token=$data['token'];$owner=session()->get('serverdothost_grab_owner');
    check(strpos($response->getBody(),$api->api_token)===false,'Endpoint does not expose API token');
    $result=endpoint(['action'=>'next','token'=>$token]);
    check($result->getStatusCode()===200 && json_decode($result->getBody(),true)['done'],'Empty job reaches done through endpoint');
    check(session()->get('serverdothost_grab_owner')===$owner,'Job owner is stable between steps');
    session()->set('serverdothost_grab_owner',str_repeat('f',64));
    check(endpoint(['action'=>'next','token'=>$token])->getStatusCode()===400,'Other session cannot use job token');
    check(endpoint(['action'=>'stop','token'=>$token])->getStatusCode()===400,'Other session cannot stop job');
    session()->set('serverdothost_grab_owner',$owner);
    check(endpoint(['action'=>'stop','token'=>$token])->getStatusCode()===200 && cache()->get('sdh_grab_'.$token)===null,'Stop removes owned job');
    check(endpoint(['action'=>'next','token'=>$token])->getStatusCode()===400,'Expired job refused');
    check(endpoint(['action'=>'next','token'=>'../bad'])->getStatusCode()===400,'Malformed token refused');
    $db->table('third_party_apis')->where('id',1)->update(['status'=>'paused']);
    check(endpoint(['action'=>'start','api_id'=>1])->getStatusCode()===400,'Paused account rejected by endpoint');
    echo "PASS: endpoint method validation, configured API, stable session owner, cache token isolation and stop/expiry.\n";
    echo "PASS: title matching, pagination, ready status, detail fallback, ambiguous and absent skips, duplicate prevention, safe replay, snapshot, unchanged content and old links, legacy schema, failures and API config changes.\n";
} finally {
    $mysql->query("DROP DATABASE `$name`");$mysql->close();
}
