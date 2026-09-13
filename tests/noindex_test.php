<?php
// Actual CI response/filter with injected settings; no database or network calls.
define('FCPATH', dirname(__DIR__).'/public/');
require dirname(__DIR__).'/app/Config/Paths.php';$paths=new Config\Paths();
require dirname(__DIR__).'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
use App\Libraries\SiteIndexing;
function check($ok,$message){if(!$ok)throw new RuntimeException($message);}
function mode($value): void {
    $settings=new class extends CodeIgniter\Config\BaseConfig {public $site_noindex;};$settings->site_noindex=$value;
    CodeIgniter\Config\Factories::injectMock('config','Settings',$settings);SiteIndexing::reset();
}
function responseFor($path,$controller='App\\Controllers\\Home',$status=200,$type='text/html',$method='get') {
    $request=Config\Services::request(null,false);$request->setMethod($method);$request->uri->setPath($path);
    $response=Config\Services::response(null,false);$response->setStatusCode($status)->setContentType($type);
    Config\Services::injectMock('response',$response);
    $router=(new ReflectionClass(CodeIgniter\Router\Router::class))->newInstanceWithoutConstructor();
    $property=new ReflectionProperty($router,'controller');$property->setAccessible(true);$property->setValue($router,$controller);
    Config\Services::injectMock('router',$router);
    $filter=new App\Filters\NoIndex();$filter->before($request);
    check($response->getHeaderLine('X-Robots-Tag')===SiteIndexing::NOINDEX,'Safe early fallback');
    $response->setHeader('X-Robots-Tag','index');$filter->after($request,$response);
    return $response->getHeaderLine('X-Robots-Tag');
}
$public=['/','/play/tt123','/embed/tt123?server=2','/download/tt123','/library/movies','/p/about','/watch/tt123/1/2','/sub/index.php/custom-player/tt123'];
foreach([null,true,1,'1','unexpected'] as $value){
    mode($value);foreach($public as $path)check(responseFor($path)===SiteIndexing::NOINDEX,'No Index covers '.$path);
    check(SiteIndexing::robots()===SiteIndexing::NOINDEX,'Matching HTML meta');
}
foreach([false,0,'0'] as $value){
    mode($value);foreach($public as $path)check(responseFor($path)===SiteIndexing::INDEX,'Index permits '.$path);
    check(SiteIndexing::robots()===SiteIndexing::INDEX,'No hardcoded noindex in public meta');
    foreach(['/admin','/admin/settings/site','/admin_login','/index.php/admin/users','/sub/index.php/admin/settings/site','/api','/ajax/get_stream_link'] as $path)check(responseFor($path)===SiteIndexing::NOINDEX,'Internal path remains excluded');
    check(responseFor('/custom-dashboard','App\\Controllers\\Admin\\Dashboard')===SiteIndexing::NOINDEX,'Controller protects custom admin routes');
    check(responseFor('/custom-json','App\\Controllers\\Ajax')===SiteIndexing::NOINDEX,'Resolved API/controller remains excluded');
    foreach([301,302,403,404,410,500] as $status)check(responseFor('/public','App\\Controllers\\Home',$status)===SiteIndexing::NOINDEX,'Non-200 response remains excluded');
    check(responseFor('/public','App\\Controllers\\Home',200,'application/json')===SiteIndexing::NOINDEX,'JSON remains excluded');
    check(responseFor('/public','App\\Controllers\\Home',200,'text/html','post')===SiteIndexing::NOINDEX,'POST remains excluded');
    check(responseFor('/public','App\\Controllers\\Home',200,'text/html','head')===SiteIndexing::INDEX,'HEAD matches GET');
}
$robots=file_get_contents(__DIR__.'/../public/robots.txt');
check(!preg_match('/^Disallow:\s*\//m',$robots),'Crawlers can read noindex');
$count=0;
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../app/Views')) as $file){
    if($file->getExtension()!=='php')continue;$source=file_get_contents($file->getPathname());
    if(!preg_match('/<head\s*>/i',$source))continue;
    check(preg_match('/<meta[^>]+name=["\x27]robots["\x27]/i',$source),'HTML head has robots meta: '.$file->getFilename());
    $isPublic=in_array(str_replace('\\','/',substr($file->getPathname(),strlen(__DIR__.'/../app/Views/'))),['themes/pirate/embed.php','themes/pirate/__layout/header.php','homepage/storage.php'],true);
    if($isPublic)check(strpos($source,'SiteIndexing::robots()')!==false,'Public meta uses shared policy');
    else check(preg_match('/content=["\x27]noindex/i',$source),'Private/error meta stays noindex');
    $count++;
}
$view=file_get_contents(__DIR__.'/../app/Views/admin/settings/site/index.php');
check(strpos($view,'form_x_panels/indexing')>strpos($view,'form_close()'),'Indexing control is outside original Site form');
echo "PASS: Index/No Index across public URLs, default privacy, admin and error exclusions, HEAD/JSON/POST, crawl visibility and {$count} HTML heads\n";
