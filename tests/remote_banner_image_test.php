<?php
// Standalone: php tests/remote_banner_image_test.php (no database or R2 writes).
namespace CodeIgniter\Files {
    class File {
        private $path;
        public function __construct($path) { $this->path = $path; }
        public function getPathname() { return $this->path; }
    }
}
namespace App\Libraries {
    class CloudflareR2Storage {
        public $path;
        public $fail = false;
        public function uploadBanner($file): string {
            $this->path = $file->getPathname();
            if (! is_file($this->path)) { throw new \Exception('Temporary image missing'); }
            if ($this->fail) { throw new \RuntimeException('Upload failed'); }
            return 'https://images.example.com/banners/test.png';
        }
    }
    function gethostbynamel($host) { return ['93.184.216.34']; }
    function curl_init($url) { return new \stdClass(); }
    function curl_setopt_array($handle, $options) { $GLOBALS['options'] = $options; }
    function curl_exec($handle) {
        $body = $GLOBALS['body'];
        $written = $GLOBALS['options'][CURLOPT_WRITEFUNCTION]($handle, $body);
        return $written === strlen($body);
    }
    function curl_getinfo($handle, $key) { return $GLOBALS['httpStatus']; }
    function curl_close($handle) {}
}
namespace {
    require __DIR__ . '/../app/Libraries/RemoteBannerImage.php';
    function check($ok, $message) { if (! $ok) { throw new \Exception($message); } }
    function fails($call, $message) {
        try { $call(); } catch (\RuntimeException $e) { check(strpos($e->getMessage(), $message) !== false, $e->getMessage()); return; }
        throw new \Exception('Expected failure: ' . $message);
    }
    $client = new \App\Libraries\RemoteBannerImage();
    $storage = new \App\Libraries\CloudflareR2Storage();
    $httpStatus = 200;
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1cAAAAASUVORK5CYII=');
    $body = $png;
    check($client->upload('https://images.example.com/test.png', $storage) === 'https://images.example.com/banners/test.png', 'Upload result');
    check(! file_exists($storage->path), 'Temporary file cleanup');
    check($options[CURLOPT_FOLLOWLOCATION] === false, 'Redirect protection');
    check($options[CURLOPT_RESOLVE] === ['images.example.com:443:93.184.216.34'], 'DNS pinning');
    foreach (['127.0.0.1', '10.0.0.1', '169.254.169.254', '100.64.0.1', '224.0.0.1'] as $host) {
        fails(function () use ($client, $storage, $host) { $client->upload('http://' . $host . '/image.png', $storage); }, 'not allowed');
    }
    fails(function () use ($client, $storage) { $client->upload('file:///etc/passwd', $storage); }, 'HTTP(S)');
    $body = '<html>not an image</html>';
    fails(function () use ($client, $storage) { $client->upload('https://images.example.com/test', $storage); }, 'must return');
    $body = str_repeat('a', 4194305);
    fails(function () use ($client, $storage) { $client->upload('https://images.example.com/test', $storage); }, '4 MB');
    $body = $png;
    $httpStatus = 302;
    fails(function () use ($client, $storage) { $client->upload('https://images.example.com/test', $storage); }, 'redirects');
    $httpStatus = 404;
    fails(function () use ($client, $storage) { $client->upload('https://images.example.com/test', $storage); }, 'Unable to download');
    $httpStatus = 200;
    $storage->fail = true;
    fails(function () use ($client, $storage) { $client->upload('https://images.example.com/test', $storage); }, 'Upload failed');
    check(! file_exists($storage->path), 'Temporary file cleanup on upload failure');
    echo "PASS: image upload, validation, size limit, private addresses, DNS pinning, HTTP failures, cleanup.\n";
}
