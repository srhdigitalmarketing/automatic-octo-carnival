<?php
// Test-only server entry point; never copied to public/ by deployment.
$root=getenv('SITE_INDEXING_TEST_ROOT');
if(!$root || !is_file($root.'/app/Filters/NoIndex.php')){http_response_code(500);exit;}
// Execute only the actual front controller's early header statement.
$front=file_get_contents($root.'/public/index.php');
if(!preg_match("/header\\('X-Robots-Tag: [^']+'\\);/",$front,$fallback)){http_response_code(500);exit;}
eval($fallback[0]);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/bootstrap-error'){http_response_code(500);echo 'Bootstrap failure';exit;}
define('FCPATH',$root.'/public/');
require $root.'/app/Config/Paths.php';$paths=new Config\Paths();
require $root.'/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$settings=new class extends CodeIgniter\Config\BaseConfig {public $site_noindex;};
$settings->site_noindex=($_GET['mode']??'noindex')==='index' ? false : true;
CodeIgniter\Config\Factories::injectMock('config','Settings',$settings);
App\Libraries\SiteIndexing::reset();
$request=Config\Services::request(null,false);$request->setMethod($_SERVER['REQUEST_METHOD']);$request->uri->setPath($path);
$response=Config\Services::response(null,false);$response->setContentType('text/html');
Config\Services::injectMock('response',$response);
$router=(new ReflectionClass(CodeIgniter\Router\Router::class))->newInstanceWithoutConstructor();
$field=new ReflectionProperty($router,'controller');$field->setAccessible(true);
$field->setValue($router,strpos($path,'/admin')===0 ? 'App\\Controllers\\Admin\\Dashboard' : 'App\\Controllers\\Home');
Config\Services::injectMock('router',$router);
$filter=new App\Filters\NoIndex();$filter->before($request);
if($path==='/missing')$response->setStatusCode(404);
if($path==='/redirect')$response->setStatusCode(302)->setHeader('Location','/');
if($path==='/ajax/get_stream_link')$response->setJSON(['success'=>true]);
else $response->setBody('<!doctype html><html><head><meta name="robots" content="'.App\Libraries\SiteIndexing::robots().'"></head><body>HTTP fixture</body></html>');
$filter->after($request,$response);$response->send();
