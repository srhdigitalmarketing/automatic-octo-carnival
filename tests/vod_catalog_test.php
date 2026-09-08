<?php
require __DIR__ . '/upnshare_health_test.php';
use App\Libraries\VodCatalog;
check(VodCatalog::hostname(' Catalog.Example ') === 'catalog.example', 'Hostname normalized');
foreach (['https://catalog.example', 'example.com/path', 'localhost', 'example.com:443', 'user@example.com'] as $host) {
    try { VodCatalog::hostname($host); throw new RuntimeException('Invalid hostname accepted'); } catch (InvalidArgumentException $expected) {}
}
$rows = VodCatalog::normalize(['list'=>[['vod_name'=>'<b>AB-123</b>','vod_pic'=>'javascript:alert(1)','vod_content'=>'<p>Description</p>'], ['vod_name'=>['invalid']]]]);
check(count($rows) === 1 && $rows[0]['title'] === 'AB-123' && $rows[0]['poster_url'] === '' && $rows[0]['description'] === 'Description', 'Unsafe and malformed data sanitized');
check(VodCatalog::normalize(['list'=>[]]) === [], 'Valid empty search');
try { VodCatalog::normalize(['error'=>'invalid']); throw new LogicException('Bad JSON accepted'); } catch (RuntimeException $expected) {}
$admin = new App\Controllers\Admin\ThirdPartyApis();
$method = new ReflectionMethod($admin, 'providerData'); $method->setAccessible(true);
$data = $method->invoke($admin, ['provider'=>'vod_catalog','name'=>'Catalog','api_base_url'=>'catalog.example','api_token'=>'must-not-save']);
check($data['api_base_url'] === 'catalog.example' && !isset($data['api_token']), 'Catalog configuration has no token');
echo "PASS: VOD normalization, hostname validation, empty results and configuration.\n";
