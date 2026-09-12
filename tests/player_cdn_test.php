<?php
$value=null; $hostname=null;
function get_config($key){global $value, $hostname;return $key === 'player_cdn_hostname' ? $hostname : $value;}
function default_theme_name(){return 'pirate';}
function site_url($path){return 'https://origin.example/'.ltrim($path,'/');}
require __DIR__.'/../app/Helpers/template_helper.php';
foreach ([null,true,1,'1',false,0,'0'] as $value) {
    $enabled=$value===null || in_array($value,[true,1,'1'],true);
    $expected=$enabled?'https://oktostream.b-cdn.net/themes/pirate/js/player.js?v=1':'/themes/pirate/js/player.js?v=1';
    if(player_cdn_asset('/js/player.js?v=1')!==$expected)throw new RuntimeException('CDN toggle mismatch');
}
echo "PASS: enabled/default CDN and disabled origin URLs preserve asset version.\n";

$value=true; $hostname='a.cdn.com';
if(player_cdn_asset('js/player.js') !== 'https://a.cdn.com/themes/pirate/js/player.js') throw new RuntimeException('Custom host failed');
$hostname='https://bad.example/path';
if(player_cdn_hostname() !== 'oktostream.b-cdn.net') throw new RuntimeException('Invalid host fallback failed');

foreach (['http://old.example:8080/sub/index.php/ajax/get_stream_link'=>'/sub/index.php/ajax/get_stream_link',
    'https://old.example/sub/themes/pirate/js/player.js?v=2'=>'/sub/themes/pirate/js/player.js?v=2',
    '//old.example/ajax/get_stream_link'=>'/ajax/get_stream_link'] as $url=>$expected) {
    if(player_same_origin_url($url)!==$expected)throw new RuntimeException('Player URL crossed origin or lost base path');
}
