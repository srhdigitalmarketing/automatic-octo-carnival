<?php
namespace CodeIgniter\HTTP { interface RequestInterface {} interface ResponseInterface {} }
namespace CodeIgniter\Filters { interface FilterInterface {} }
namespace {
require __DIR__ . '/../app/Filters/NoIndex.php';
function service($name) { return $GLOBALS['response']; }
$response = new class implements \CodeIgniter\HTTP\ResponseInterface {
 public $headers=[]; public function setHeader($name,$value) {$this->headers[$name]=$value;return $this;}
};
$request = new class implements \CodeIgniter\HTTP\RequestInterface {};
$filter = new \App\Filters\NoIndex();
$expected='noindex, nofollow, noimageindex, nosnippet';
$filter->before($request);
if (($response->headers['X-Robots-Tag']??null)!==$expected) {throw new \RuntimeException('Missing early header');}
$response->headers['X-Robots-Tag']='index'; $filter->after($request,$response);
if ($response->headers['X-Robots-Tag']!==$expected) {throw new \RuntimeException('Missing final header');}
$robots=file_get_contents(__DIR__.'/../public/robots.txt');
if (preg_match('/^Disallow:\s*\//m',$robots)) {throw new \RuntimeException('Crawler cannot see noindex');}
$files=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__.'/../app/Views'));
$count=0;
foreach($files as $file) {
 if($file->getExtension()!=='php')continue;
 $content=file_get_contents($file->getPathname());
 if(preg_match('/<head\s*>/i',$content)) {
  if(!preg_match('/<meta[^>]+name=["\x27]robots["\x27][^>]+content=["\x27][^"\x27]*noindex/i',$content)) {throw new \RuntimeException('Missing meta: '.$file->getFilename());}
  $count++;
 }
}
echo "PASS: before/after noindex headers, crawl visibility, {$count} HTML head templates.\n";
}
