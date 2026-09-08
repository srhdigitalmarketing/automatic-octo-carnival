<?php
$value=null;
function get_config($key){global $value;return $value;}
function default_theme_name(){return 'pirate';}
function site_url($path){return 'https://origin.example/'.ltrim($path,'/');}
require __DIR__.'/../app/Helpers/template_helper.php';
foreach ([null,true,1,'1',false,0,'0'] as $value) {
    $enabled=$value===null || in_array($value,[true,1,'1'],true);
    $expected=$enabled?'https://oktostream.b-cdn.net/themes/pirate/js/player.js?v=1':'https://origin.example/themes/pirate/js/player.js?v=1';
    if(player_cdn_asset('/js/player.js?v=1')!==$expected)throw new RuntimeException('CDN toggle mismatch');
}
echo "PASS: enabled/default CDN and disabled origin URLs preserve asset version.\n";
