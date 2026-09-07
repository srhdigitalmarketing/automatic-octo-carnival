<?php

namespace App\Libraries;

use CodeIgniter\Files\File;
use RuntimeException;

/** Downloads a bounded image from a public HTTP(S) address before publishing it. */
class RemoteBannerImage
{
    private const MAX_BYTES = 4194304;

    public function upload(string $url, CloudflareR2Storage $storage): string
    {
        $body = $this->download(trim($url));
        $info = @getimagesizefromstring($body);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if ($info === false || ! isset($extensions[$info['mime'] ?? ''])) {
            throw new RuntimeException('The URL must return a JPG, PNG, or WebP image.');
        }
        $path = tempnam(sys_get_temp_dir(), 'r2-image-');
        if ($path === false) {
            throw new RuntimeException('Unable to prepare the image. Please try again.');
        }
        try {
            if (file_put_contents($path, $body) !== strlen($body)) {
                throw new RuntimeException('Unable to save the downloaded image.');
            }
            return $storage->uploadBanner(new File($path));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    private function download(string $url): string
    {
        // Redirects are intentionally not followed: each supplied address must be public.
        $target = $this->publicTarget($url);
        $body = '';
        $tooLarge = false;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => [$target['host'] . ':' . $target['port'] . ':' . $target['ip']],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: image/jpeg, image/png, image/webp'],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, &$tooLarge): int {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) {
                    $tooLarge = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        try {
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($curl);
        }
        if ($tooLarge) {
            throw new RuntimeException('The image exceeds the 4 MB limit.');
        }
        if ($status >= 300 && $status < 400) {
            throw new RuntimeException('This URL redirects. Please paste the final direct image URL.');
        }
        if ($ok === false || $status !== 200 || $body === '') {
            throw new RuntimeException('Unable to download the image. Check that the direct URL is publicly accessible.');
        }
        return $body;
    }

    private function publicTarget(string $url): array
    {
        if (strlen($url) > 4096 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Enter a valid public image URL.');
        }
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (! in_array($scheme, ['http', 'https'], true) || $host === ''
            || isset($parts['user']) || isset($parts['pass'])
            || ! in_array($port, [80, 443], true) || strpos($host, ':') !== false) {
            throw new RuntimeException('Use a public HTTP(S) image URL on port 80 or 443.');
        }
        // Resolve once and pin cURL to the checked address to avoid DNS rebinding.
        $ips = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ? [$host] : @gethostbynamel($host);
        if (! $ips) {
            throw new RuntimeException('The image hostname could not be resolved.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || preg_match('/^(0\.|127\.|169\.254\.|192\.0\.0\.|198\.(18|19)\.|22[4-9]\.|23[0-9]\.)/', $ip)
                || (ip2long($ip) >= ip2long('100.64.0.0') && ip2long($ip) <= ip2long('100.127.255.255'))) {
                throw new RuntimeException('Private or reserved network addresses are not allowed.');
            }
        }
        return ['host' => $host, 'port' => $port, 'ip' => $ips[0]];
    }
}
