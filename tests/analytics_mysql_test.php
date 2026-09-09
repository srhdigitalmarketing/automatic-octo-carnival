<?php
// Disposable MySQL only, never a production connection.
if (!isset($argv[1]) || (int)$argv[1] !== 13389) { echo "SKIP: supply isolated MySQL port 13389.\n"; exit(0); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysql = new mysqli('127.0.0.1', 'root', '', '', 13389);
$name = 'analytics_test_' . bin2hex(random_bytes(6));
$mysql->query("CREATE DATABASE `$name`");
foreach (['hostname'=>'127.0.0.1','username'=>'root','password'=>'','database'=>$name,'port'=>'13389','DBDriver'=>'MySQLi'] as $key=>$value) { putenv('database.default.'.$key.'='.$value); }
define('FCPATH', dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php'; $paths = new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
function check($ok, $message) { if (!$ok) { throw new RuntimeException($message); } }
try {
    $db = db_connect(); check($db->database === $name, 'Disposable database required');
    $db->query('CREATE TABLE links (id INT PRIMARY KEY, movie_id INT, type VARCHAR(30), requests BIGINT, reports_not_working INT, reports_wrong_link INT)');
    $db->query("INSERT INTO links VALUES (1,1,'stream',12,1,1),(2,1,'stream',NULL,0,0),(3,2,'direct_download',30,0,1),(4,2,'torrent_download',7,0,0),(5,2,NULL,3,NULL,NULL)");
    $queries = 0;
    CodeIgniter\Events\Events::on('DBQuery', static function() use (&$queries) { $queries++; });
    $analytics = new App\Libraries\Analytics();
    foreach (['initLinks', 'initLinksRequests', 'initReportedLinks'] as $method) {
        $reflection = new ReflectionMethod($analytics, $method); $reflection->setAccessible(true); $reflection->invoke($analytics);
    }
    check($queries === 1, 'Link statistics require more than one query');
    $data = $analytics->getData(true);
    check($data['links'] === ['total'=>5,'stream'=>2,'direct_dl'=>1,'torrent_dl'=>2], 'Link counts changed');
    check($data['links_requests'] === ['total'=>52,'stream'=>12,'direct_dl'=>30,'torrent_dl'=>10], 'Request totals changed');
    check($data['reported_links'] === ['total'=>2,'stream'=>1,'direct_dl'=>1,'torrent_dl'=>0], 'Duplicate reports or nulls counted incorrectly');
    $db->query('CREATE TABLE movies (id INT PRIMARY KEY, type VARCHAR(30), views BIGINT)');
    $db->query('CREATE TABLE series (id INT PRIMARY KEY, is_completed INT)');
    $db->query('CREATE TABLE seasons (id INT PRIMARY KEY, total_episodes INT)');
    $db->query('CREATE TABLE failed_movies (id INT PRIMARY KEY)');
    $db->query('DELETE FROM links');
    $data = $analytics->init()->getData(true);
    foreach (['links','links_requests','reported_links'] as $metric) {
        check($data[$metric] === ['total'=>0,'stream'=>0,'direct_dl'=>0,'torrent_dl'=>0], 'Repeated init returned stale statistics');
    }
    echo "PASS: 9 link-statistic queries reduced to 1; totals, nulls, report deduplication, empty database and refresh verified.\n";
} finally { $mysql->query("DROP DATABASE `$name`"); $mysql->close(); }
