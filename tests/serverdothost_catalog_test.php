<?php
require __DIR__.'/../app/Libraries/ServerDotHostCatalog.php';
use App\Libraries\ServerDotHostCatalog;
function verify($value, string $message): void { if (!$value) throw new RuntimeException($message); }
$video = ['id'=>'01990000-0000-7000-8000-000000000001', 'title'=>'Video Contoh',
    'description'=>'Deskripsi video', 'visibility'=>'private', 'processing_status'=>'ready',
    'moderation_status'=>'pending', 'duration'=>120.5,
    'created_at'=>'2026-09-13T01:00:00.000000Z', 'updated_at'=>'2026-09-13T01:10:00.000000Z'];
$body = ['data'=>[$video], 'meta'=>['current_page'=>1,'last_page'=>1], 'links'=>['next'=>null]];
$page = ServerDotHostCatalog::normalizePage($body);
$item = $page['items'][0];
verify($item['title'] === 'Video Contoh' && $item['description'] === 'Deskripsi video', 'Provided metadata preserved');
verify($item['source_id'] === $video['id'] && $item['duration_seconds'] === 120.5, 'UUID and fractional duration preserved');
verify($item['visibility'] === 'private' && $item['moderation_status'] === 'pending' && $item['processing_status'] === 'ready', 'Privacy and processing are separate');
verify($item['stream_urls'] === [] && $item['poster_url'] === '' && $item['movie_code'] === '', 'No fabricated playback, poster or movie code');
verify($page['next_page'] === null, 'Last page stops');
$body['meta']['last_page'] = 2;
$body['links']['next'] = 'https://attacker.example/steal-token';
verify(ServerDotHostCatalog::normalizePage($body)['next_page'] === 2, 'Pagination uses numeric metadata only');
$url = ServerDotHostCatalog::listUrl('contoh & page=9', 2);
parse_str(parse_url($url, PHP_URL_QUERY), $query);
verify(parse_url($url, PHP_URL_HOST) === 'serverdothost.com' && $query === ['q'=>'contoh & page=9','page'=>'2'], 'Search cannot inject query parameters or another origin');
$body['data'] = [$video, $video, ['title'=>['bad']]];
verify(count(ServerDotHostCatalog::normalizePage($body)['items']) === 1, 'Duplicate IDs and malformed rows skipped');
$body['data'] = [];
verify(ServerDotHostCatalog::normalizePage($body)['items'] === [], 'Empty results accepted');
foreach ([['message'=>'Token API diperlukan.'], ['data'=>['title'=>'detail'],'meta'=>['current_page'=>1,'last_page'=>1]], ['data'=>[],'meta'=>['current_page'=>0,'last_page'=>1]], ['data'=>[],'meta'=>['current_page'=>1,'last_page'=>[]]]] as $invalid) {
    try { ServerDotHostCatalog::normalizePage($invalid); throw new LogicException('Invalid response accepted'); }
    catch (RuntimeException $expected) {}
}
echo "PASS: ServerDotHost list metadata, pagination, missing URLs, privacy, query encoding and malformed payloads.\n";

$plain = 'bkp_'.str_repeat('a',64);
$share = str_repeat('b',48);
$video['embed_url'] = 'https://bobaplayer.com/embed/'.$share;
$video['poster_url'] = 'https://bobaplayer.com/v/'.$share.'/media/01990000-0000-7000-8000-000000000002';
$calls = [];
$client = new ServerDotHostCatalog(static function($url,$headers) use (&$calls,$video,$plain) {
    $calls[] = $url;
    verify(parse_url($url,PHP_URL_HOST) === 'serverdothost.com', 'Token destination is fixed');
    verify(in_array('Authorization: Bearer '.$plain,$headers,true), 'Bearer token header');
    verify(strpos($url,$plain) === false, 'No token in URL');
    return ['http'=>200,'body'=>strpos($url,'?') !== false ? ['data'=>[$video],'meta'=>['current_page'=>1,'last_page'=>2],'links'=>['next'=>'https://attacker.example/']] : ['data'=>$video]];
});
$page = $client->page($plain,'contoh & page=9');
verify($page['next_page'] === 2 && count($calls) === 1, 'One page per request, no unbounded pagination');
$item=$page['items'][0];
verify($item['stream_urls'] === [$video['embed_url']] && $item['poster_url'] === $video['poster_url'], 'Real returned URLs are mapped');
verify($item['visibility'] === 'private', 'Sharing can be enabled independently from private visibility');
verify($client->detail($plain,$video['id'])['source_id'] === $video['id'], 'Detail uses video UUID');
verify(strpos(json_encode($page),$plain) === false, 'Token never enters browser data');
foreach (['https://evil.example/embed/'.$share, 'http://bobaplayer.com/embed/'.$share, 'https://bobaplayer.com/embed/'.$share.'?token=secret', 'https://user:secret@bobaplayer.com/embed/'.$share, 'javascript:alert(1)'] as $unsafe) {
    $row = array_replace($video,['embed_url'=>$unsafe]);
    verify(ServerDotHostCatalog::normalizePage(['data'=>[$row],'meta'=>['current_page'=>1,'last_page'=>1]])['items'][0]['stream_urls'] === [], 'Unsafe or unregistered embed rejected');
}
$row=array_replace($video,['embed_url'=>null,'poster_url'=>null]);
verify(ServerDotHostCatalog::normalizePage(['data'=>[$row],'meta'=>['current_page'=>1,'last_page'=>1]])['items'][0]['stream_urls'] === [], 'Disabled sharing does not fabricate URLs');
foreach ([401,403,429,500,302] as $status) {
    $bad=new ServerDotHostCatalog(static function() use ($status,$plain) {return ['http'=>$status,'body'=>['message'=>$plain]];});
    try {$bad->page($plain); throw new LogicException('HTTP failure accepted');}
    catch (RuntimeException $e) {verify(strpos($e->getMessage(),$plain) === false, 'Error body and token redacted');}
}
try {$client->detail($plain,'../../admin'); throw new LogicException('Invalid UUID accepted');} catch (InvalidArgumentException $e) {}
try {$client->page($plain."\r\nInjected: value"); throw new LogicException('Invalid token accepted');} catch (InvalidArgumentException $e) {}
$mismatch=new ServerDotHostCatalog(static function() {return ['http'=>200,'body'=>['data'=>['id'=>'wrong']]];});
try {$mismatch->detail($plain,$video['id']); throw new LogicException('Mismatched detail accepted');} catch (RuntimeException $e) {}
echo "PASS: Bearer authentication, fixed origin, returned embed/poster mapping, bounded pagination, detail UUID, secret redaction and error handling.\n";

require __DIR__.'/../app/Libraries/ProviderConnection.php';
$api=(object)['provider'=>'serverdothost','status'=>'active','api_token'=>$plain,'embed_domains'=>'bobaplayer.com'];
foreach ([200,401,403,429] as $status) {
    $connection=new \App\Libraries\ProviderConnection(static function($url,$headers) use ($status,$video) {return ['http'=>$status,'body'=>['data'=>[$video],'meta'=>['current_page'=>1,'last_page'=>1]]];});
    $result=$connection->check($api);
    verify($result['state'] === ($status===200?'connected':'disconnected'),'Connection state follows publisher authentication');
    verify(strpos(json_encode($result),$plain) === false,'Connection result hides token');
}
$api->status='paused';
$connection=new \App\Libraries\ProviderConnection(static function() {throw new LogicException('Paused connection contacted');});
verify($connection->check($api)['state']==='paused','Paused connection makes no API request');
echo "PASS: ServerDotHost provider connection status and token redaction.\n";
