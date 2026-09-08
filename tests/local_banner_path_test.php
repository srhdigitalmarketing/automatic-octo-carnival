<?php
require __DIR__.'/../app/Libraries/LocalBannerPath.php';
$base=sys_get_temp_dir().'/banner-test-'.bin2hex(random_bytes(8)); mkdir($base); mkdir($base.'/banners'); file_put_contents($base.'/banners/test.jpg','fixture'); file_put_contents($base.'/outside.jpg','outside');
try {
    if (App\Libraries\LocalBannerPath::resolve($base.'/banners','test.jpg') !== realpath($base.'/banners/test.jpg')) throw new RuntimeException('Valid file rejected');
    foreach (['../outside.jpg','missing.jpg',$base.'/outside.jpg',"bad\0.jpg"] as $name) {
        try { App\Libraries\LocalBannerPath::resolve($base.'/banners',$name); throw new LogicException('Unsafe path accepted'); } catch (RuntimeException $expected) {}
    }
    echo "PASS: local banner path validation rejects traversal, missing and absolute paths.\n";
} finally { unlink($base.'/banners/test.jpg'); unlink($base.'/outside.jpg'); rmdir($base.'/banners'); rmdir($base); }
