<?php
function check($value,$message){if(!$value)throw new RuntimeException($message);}
// The old call must succeed even without settings, database or tracking helpers.
set_error_handler(function($severity,$message){throw new RuntimeException($message);});
ob_start();
include __DIR__.'/../app/Views/partials/google_analytics.php';
$output=ob_get_clean();
restore_error_handler();
check($output==='','Retired GA4 view emitted content');
foreach(['themes/pirate/embed.php','themes/pirate/__layout/footer.php','homepage/storage.php'] as $name){
    $template=file_get_contents(__DIR__.'/../app/Views/'.$name);
    check(strpos($template,'partials/google_analytics')===false,'Current template still calls GA4: '.$name);
    check(substr_count($template,"view('partials/histats'")===1,'HiStats must appear once: '.$name);
}
check(!file_exists(__DIR__.'/../public/js/google-analytics.js'),'Removed GA4 loader returned');
echo "PASS: old GA4 view is inert; current player/public templates use HiStats once.\n";
