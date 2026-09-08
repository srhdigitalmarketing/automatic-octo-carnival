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

$example = ['code'=>1,'list'=>[['id'=>83,'name'=>'Video name','movie_code'=>'abc-123','description'=>'Movie description',
    'poster_url'=>'[https://upload18.cc/v/abc-123/poster.jpg](https://upload18.cc/v/abc-123/poster.jpg)',
    'episodes'=>['server_name'=>'VIP #1','server_data'=>['Full'=>['slug'=>'full','link_embed'=>'https://upload18.org/play/index/abc-123']]]]]];
$actual = VodCatalog::normalize($example)[0];
check($actual['title'] === 'Video name' && $actual['description'] === 'Movie description', 'User JSON title and description');
check($actual['poster_url'] === 'https://upload18.cc/v/abc-123/poster.jpg', 'User JSON poster');
check($actual['stream_urls'] === ['https://upload18.org/play/index/abc-123'], 'Nested Full embed');
$example['list'][0]['episodes'] = [
 ['server_data'=>[['link_embed'=>'https://example.com/e/1'],['link_embed'=>'javascript:alert(1)']]],
 ['server_data'=>[['link_embed'=>'https://example.com/e/1'],['link_embed'=>'https://example.com/e/2']]]
];
check(VodCatalog::normalize($example)[0]['stream_urls'] === ['https://example.com/e/1','https://example.com/e/2'], 'Multiple servers deduplicate and reject unsafe embed URLs');
echo "PASS: supplied JSON fields and nested episode URLs.\n";

$catalogStatus = App\Libraries\VodFileHealth::classify('https://example.com/e/abc', [['stream_urls'=>['https://example.com/e/abc']]]);
check($catalogStatus['status'] === 'unknown' && strpos($catalogStatus['message'], 'persis') !== false, 'Catalog presence does not prove playback');
check(App\Libraries\VodFileHealth::classify('https://example.com/e/abc', [['stream_urls'=>['https://other.example/e/abc']]])['status'] === 'unknown', 'Same ID on another hostname does not prove presence');
check(App\Libraries\VodFileHealth::classify('https://example.com/e/abc', [])['status'] === 'unknown', 'Empty catalog cannot prove deletion');
