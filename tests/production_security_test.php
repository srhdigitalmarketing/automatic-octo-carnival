<?php
namespace CodeIgniter\HTTP { interface RequestInterface {} interface ResponseInterface {} }
namespace CodeIgniter\Filters { interface FilterInterface {} }
namespace {
require __DIR__ . '/../app/Libraries/AdminOrigin.php';
require __DIR__ . '/../app/Libraries/SafeImageName.php';
require __DIR__ . '/../app/Libraries/Authentication.php';
require __DIR__ . '/../app/Filters/Auth.php';
require __DIR__ . '/../app/Helpers/language_helper.php';
function get_config($name) { return null; }
function check($ok, $message) { if (!$ok) { throw new \RuntimeException($message); } }
function service($name) { return $GLOBALS['services'][$name]; }
function session() { return new class { public function set($key, $value) {} }; }
function current_url() { return 'https://site.test/admin'; }
function redirect() { return new class { public function to($url) { return 'redirect'; } }; }
function config($name) { return (object)['baseURL' => 'https://site.test/']; }
$services = [
 'router' => new class { public $controller = '\\App\\Controllers\\Admin\\Movies'; public $method = 'delete'; public function controllerName() { return $this->controller; } public function methodName() { return $this->method; } },
 'auth' => new class { public $logged = true; public function isLogged() { return $this->logged; } },
 'response' => new class { public $status; public function setStatusCode($s) { $this->status = $s; return $this; } public function setBody($b) { return $this; } },
];
$request = new class implements \CodeIgniter\HTTP\RequestInterface {
 public $headers = []; public $method = 'get'; public $uri;
 public function __construct() { $this->uri = new class { public $path = '/admin/movies/delete/1'; public function getPath() { return $this->path; } }; }
 public function getMethod() { return $this->method; }
 public function getHeaderLine($key) { return $this->headers[$key] ?? ''; }
};
check(is_multi_languages_enabled() === false, 'Missing language configuration cannot cause HTTP 500');
check(get_selected_languages(true) === ['en-US'], 'Missing language list has an array default');
$filter = new \App\Filters\Auth();
check($filter->before($request)->status === 403, 'Reject destructive GET without origin proof');
$request->headers = ['Referer' => 'https://site.test/admin/movies'];
check($filter->before($request) === null, 'Allow same-origin admin link');
$request->headers = ['Origin' => 'https://evil.test', 'Referer' => 'https://site.test/'];
check($filter->before($request)->status === 403, 'Origin takes precedence over Referer');
$services['auth']->logged = false; $request->uri->path = '/Admin/Movies/delete/1';
check($filter->before($request) === 'redirect', 'Protect controller independently of URL casing');
$services['router']->controller = function () {}; $request->uri->path = '/';
check($filter->before($request) === null, 'Public closure routes do not fail');
$services['router']->controller = '\\App\\Controllers\\Admin\\Login'; $request->method = 'post';
check($filter->before($request)->status === 403, 'Reject cross-origin login');
$request->headers = ['Origin' => 'https://site.test'];
check($filter->before($request) === null, 'Allow same-origin login without session');
foreach (['https://site.test.evil/', 'http://site.test/', 'https://site.test:444/', 'null', 'https://user@site.test/'] as $source) {
 check(!\App\Libraries\AdminOrigin::matches($source, 'https://site.test'), 'Reject mismatched origin');
}
check(\App\Libraries\AdminOrigin::matches('https://SITE.test:443/a', 'https://site.test/'), 'Normalize host/default port');
check(!(new \App\Libraries\Authentication())->login([], []), 'Array login does not throw a TypeError');
$path = tempnam(sys_get_temp_dir(), 'safe-image-');
try {
 file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1cAAAAASUVORK5CYII='));
 $file = new class($path) { private $path; public function __construct($p) {$this->path=$p;} public function getPathname() {return $this->path;} public function getExtension() {return 'php';} };
 check(preg_match('/^[a-f0-9]{32}\.png$/', \App\Libraries\SafeImageName::random($file)) === 1, 'Uploaded extension cannot control stored extension');
 file_put_contents($path, '<?php echo 1;'); $rejected=false;
 try { \App\Libraries\SafeImageName::extension($file); } catch (\RuntimeException $e) {$rejected=true;}
 check($rejected, 'Non-image rejected');
} finally { unlink($path); }
echo "PASS: admin route guard, origin checks, login types, safe upload names.\n";
}
