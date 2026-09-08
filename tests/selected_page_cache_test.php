<?php
namespace CodeIgniter\HTTP { class URI { public static function createURIString($scheme,$authority,$path,$query='') { return $scheme.'://'.$authority.'/'.$path.($query!==''?'?'.$query:''); } } }
namespace {
require __DIR__.'/../app/Libraries/SelectedPageCache.php';
$values=['web_page_cache'=>true,'web_page_cache_types'=>['embed']];
function get_config($name) { global $values; return $values[$name]??null; }
function web_page_cache_time() { return 300; }
function config($name) { return (object)['cacheQueryString'=>true]; }
$store=new class {public $data=[];public function get($key){return $this->data[$key]??null;} public function save($key,$value,$ttl){$this->data[$key]=$value;return true;}public function delete($key){unset($this->data[$key]);return true;}};
function cache(){global $store;return $store;}
function check($ok,$message){if(!$ok)throw new \RuntimeException($message);}
check(App\Libraries\SelectedPageCache::enabled('embed'),'Selected embed');
check(!App\Libraries\SelectedPageCache::enabled('view') && !App\Libraries\SelectedPageCache::enabled('download'),'Unselected pages disabled');
$request=new class {public function getUri(){return new class {public function getScheme(){return 'https';} public function getAuthority(){return 'example.com';} public function getPath(){return 'embed/tt123';}public function getQuery(){return 'server=2';}};}};
App\Libraries\SelectedPageCache::register(12,$request);
$key=md5('https://example.com/embed/tt123?server=2'); $store->data[$key]='html';$store->data['unrelated']='keep';
App\Libraries\SelectedPageCache::clear(12);
check(!isset($store->data[$key]) && $store->data['unrelated']==='keep','Per-video cache clear isolated');
echo "PASS: page selection and per-video cache invalidation.\n";
}
