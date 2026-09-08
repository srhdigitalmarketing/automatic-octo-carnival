<?php
namespace App\Controllers { class BaseAjax {} }
namespace {
require __DIR__ . '/../app/Controllers/Admin/Ajax/HostVideoSearch.php';
$search = new \App\Controllers\Admin\Ajax\HostVideoSearch();
function invoke($name, ...$args) {
    global $search;
    $method = new \ReflectionMethod($search, $name);
    $method->setAccessible(true);
    return $method->invoke($search, ...$args);
}
$record = ['id'=>'video_id', 'name'=>'video_name', 'poster'=>'poster_url', 'status'=>'status', 'duration'=>0, 'play'=>0];
foreach ([$record, ['data'=>$record], ['data'=>[$record]], ['result'=>[$record]], [$record]] as $payload) {
    $page = invoke('upnSharePageFromPayload', $payload);
    if (($page['videos'][0] ?? null) !== $record) throw new \RuntimeException('Record format not accepted');
    $matches = invoke('matchingUpnShareVideos', $page['videos'], 'video_name');
    if (count($matches) !== 1) throw new \RuntimeException('Name not matched');
    $file = invoke('normaliseUpnShareVideo', $matches[0], [], 'video_id');
    if ($file['title'] !== 'video_name' || $file['file_code'] !== 'video_id' || !$file['canplay']) throw new \RuntimeException('Record normalization failed');
}
$deleted = invoke('normaliseUpnShareVideo', array_merge($record, ['status'=>'deleted']), [], 'video_id');
if ($deleted['canplay']) throw new \RuntimeException('Deleted result selectable');
echo "PASS: single and wrapped UPNShare objects, lists, name matching and deleted filtering\n";
$api = (object) ['provider'=>'streamhg', 'embed_domains'=>'streamhg.com', 'api_base_url'=>'https://old.example/api'];
if (invoke('apiRoots', $api) !== ['https://streamhgapi.com/api']) throw new \RuntimeException('Wrong StreamHg endpoint');
$payload = ['status'=>200, 'result'=>['files'=>[['file_code'=>'2p5k0u8gnyj1', 'link'=>'https://streamhg.com/example-player', 'title'=>'No Copyright Drone Shots', 'canplay'=>1]]]];
if (!invoke('isFileListPayload', $payload)) throw new \RuntimeException('File List rejected');
$file = invoke('normaliseStreamHgFile', invoke('filesFromPayload', $payload)[0], $api);
if ($file['link'] !== 'https://streamhg.com/example-player') throw new \RuntimeException('Wrong embed URL');
echo "PASS: StreamHg endpoint, documented File List and example embed URL\n";

$info = ['status'=>200, 'result'=>[
 ['file_code'=>'2p5k0u8gnyj1', 'link'=>'https://streamhg.com/example-player','file_title'=>'No Copyright Drone Shots','status'=>200,'canplay'=>1],
 ['file_code'=>'deleted123','status'=>404,'canplay'=>0],
 ['file_code'=>'unrequested','status'=>200,'canplay'=>1]
]];
$files = invoke('streamHgInfoFiles', $info, ['2p5k0u8gnyj1','deleted123'], $api);
if (count($files) !== 1 || $files[0]['title'] !== 'No Copyright Drone Shots' || $files[0]['link'] !== 'https://streamhg.com/example-player') throw new \RuntimeException('File Info filtering failed');
echo "PASS: File Info title, playable status, deleted and unsolicited records\n";

}
