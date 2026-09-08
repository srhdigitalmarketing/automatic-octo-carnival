<?php
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require dirname(__DIR__) . '/system/bootstrap.php';
error_reporting(E_ALL & ~E_DEPRECATED);
$search = new App\Controllers\Admin\Ajax\HostVideoSearch();
$method = new ReflectionMethod($search, 'httpClient');
$method->setAccessible(true);
$host = ['host'=>'upnshare.com', 'port'=>443, 'ip'=>'1.1.1.1'];
$first = $method->invoke($search, $host);
$second = $method->invoke($search, $host);
if (!$first instanceof CodeIgniter\HTTP\CURLRequest || $first === $second) {
    throw new RuntimeException('Search must construct a fresh HTTP client');
}
echo "PASS: actual framework constructs independent search HTTP clients without TypeError or network requests\n";
