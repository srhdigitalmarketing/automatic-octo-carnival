<?php
// Uses the real CodeIgniter File and R2 uploader; only network calls are simulated.
namespace App\Libraries {
    function curl_init($url) { return (object) ['url' => $url, 'options' => []]; }
    function curl_setopt_array($curl, $options) { $curl->options += $options; }
    function curl_setopt($curl, $key, $value) { $curl->options[$key] = $value; }
    function curl_exec($curl) { $GLOBALS['requests'][] = $curl; return ''; }
    function curl_getinfo($curl, $key) { return $key === CURLINFO_HTTP_CODE ? 200 : $GLOBALS['expectedMime']; }
    function curl_error($curl) { return ''; }
    function curl_close($curl) {}
}
namespace {
    require __DIR__ . '/../system/Files/File.php';
    require __DIR__ . '/../app/Libraries/CloudflareR2Storage.php';
    function check($value, $message) { if (! $value) { throw new \RuntimeException($message); } }
    $reflection = new \ReflectionClass(\App\Libraries\CloudflareR2Storage::class);
    $storage = $reflection->newInstanceWithoutConstructor();
    $config = $reflection->getProperty('config');
    $config->setAccessible(true);
    $config->setValue($storage, (object) [
        'r2_account_id' => 'test', 'r2_bucket' => 'test',
        'r2_access_key_id' => 'test', 'r2_secret_access_key' => 'test',
        'r2_public_url' => 'https://images.example.com',
    ]);
    $path = tempnam(sys_get_temp_dir(), 'r2-regression-');
    $image = imagecreatetruecolor(2, 2);
    try {
        foreach (['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $extension => $expectedMime) {
            if ($extension === 'jpg') { imagejpeg($image, $path); }
            elseif ($extension === 'png') { imagepng($image, $path); }
            else { imagewebp($image, $path); }
            clearstatcache(true, $path);
            $requests = [];
            $url = $storage->uploadBanner(new \CodeIgniter\Files\File($path));
            check(substr($url, -strlen('.' . $extension)) === '.' . $extension, 'Wrong object extension');
            check(count($requests) === 2, 'Upload and public image check expected');
            check(in_array('content-type: ' . $expectedMime, $requests[0]->options[CURLOPT_HTTPHEADER], true), 'Wrong content type');
        }
        file_put_contents($path, '<html>This is not an image</html>');
        $requests = [];
        $rejected = false;
        try { $storage->uploadBanner(new \CodeIgniter\Files\File($path)); }
        catch (\RuntimeException $error) { $rejected = true; }
        check($rejected && $requests === [], 'Non-image must be rejected before upload');
        echo "PASS: actual uploader accepts JPG/PNG/WebP temporary files and rejects non-images.\n";
    } finally {
        imagedestroy($image);
        unlink($path);
    }
}
