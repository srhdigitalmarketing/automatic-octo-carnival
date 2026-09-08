<?php
require __DIR__ . '/vod_catalog_test.php';
use App\Libraries\AutoGrabMatch;
foreach (['batman'=>'batman','[batman]'=>'batman','BATMAN-123'=>'batman-123','[batman-123] Film title'=>'batman-123','[English-Subtitle] batman'=>'','batman English-Subtitle'=>'','The Batman movie'=>''] as $title=>$expected) {
    check(AutoGrabMatch::code($title) === $expected, 'Code extraction: '.$title);
}
$item = ['title'=>'Video name','movie_code'=>'batman-123','poster_url'=>'https://example.com/thumb.jpg','auto_poster_url'=>'https://example.com/poster.jpg','stream_urls'=>[]];
check(AutoGrabMatch::select('batman-123',[$item])['poster_url'] === $item['auto_poster_url'], 'Use poster_url, not thumb');
check(AutoGrabMatch::select('batman',[$item]) === null, 'No prefix match');
check(AutoGrabMatch::select('batman-123',[$item,$item]) === null, 'Ambiguous results skipped');
$item['title'] = '[English-Subtitle] Batman';
check(AutoGrabMatch::select('batman-123',[$item]) === null, 'Subtitle result excluded even with matching code');
$item['title'] = 'Batman'; $item['auto_poster_url'] = '';
check(AutoGrabMatch::select('batman-123',[$item]) === null, 'Thumb-only result skipped');
echo "PASS: exact code, subtitle exclusion, ambiguity and poster-only matching.\n";
