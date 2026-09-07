<?php

if (! function_exists('download_image')) {
    function download_image($imgUrl, $dir = null)
    {
        try {
            $body = (new \App\Libraries\RemoteBannerImage())->download((string) $imgUrl);
            $info = @getimagesizefromstring($body);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if ($info === false || ! isset($extensions[$info['mime'] ?? ''])) { return null; }
            $dir = $dir ?: WRITEPATH . 'tmp';
            if (! is_dir($dir) && ! mkdir($dir, 0750, true) && ! is_dir($dir)) { return null; }
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . $extensions[$info['mime']];
            if (file_put_contents($path, $body) !== strlen($body)) {
                if (is_file($path)) { unlink($path); }
                return null;
            }
            return $path;
        } catch (\Throwable $error) {
            log_message('warning', 'Remote image download failed.');
            return null;
        }
    }
}
