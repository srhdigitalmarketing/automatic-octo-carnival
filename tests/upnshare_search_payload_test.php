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
}
