<?php

namespace App\Libraries;

use App\Models\ThirdPartyApi;
use CodeIgniter\Files\File;
use RuntimeException;

/**
 * Small S3-compatible Cloudflare R2 client used only for banner artwork.
 * Credentials stay server-side; browsers receive only the public image URL.
 */
class CloudflareR2Storage
{
    private const REGION = 'auto';
    private const SERVICE = 's3';

    /** @var object */
    private $config;

    private function __construct($config)
    {
        $this->config = $config;
    }

    public static function active(): ?self
    {
        $config = (new ThirdPartyApi())
            ->where('provider', 'cloudflare_r2')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        if ($config === null || ! self::isConfigured($config)) {
            return null;
        }

        return new self($config);
    }

    public static function isConfigured($config): bool
    {
        foreach (['r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket', 'r2_public_url'] as $field) {
            if (empty($config->{$field})) {
                return false;
            }
        }

        return filter_var($config->r2_public_url, FILTER_VALIDATE_URL) !== false;
    }

    public function uploadBanner(File $file): string
    {
        $extension = strtolower((string) pathinfo($file->getName(), PATHINFO_EXTENSION));
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $key = 'banners/' . date('Y/m') . '/' . bin2hex(random_bytes(16)) . '.' . $extension;
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        $this->signedRequest('PUT', $key, $file->getPathname(), $mime);

        $publicUrl = rtrim((string) $this->config->r2_public_url, '/') . '/' . $key;
        $this->verifyPublicImage($publicUrl);

        return $publicUrl;
    }

    public function isManagedUrl(string $url): bool
    {
        $base = rtrim((string) $this->config->r2_public_url, '/') . '/';

        return strncmp($url, $base, strlen($base)) === 0;
    }

    public function deletePublicUrl(string $url): bool
    {
        if (! $this->isManagedUrl($url)) {
            return false;
        }

        $key = ltrim(substr($url, strlen(rtrim((string) $this->config->r2_public_url, '/'))), '/');
        if ($key === '') {
            return false;
        }

        $this->signedRequest('DELETE', rawurldecode($key));

        return true;
    }

    private function signedRequest(string $method, string $key, ?string $filePath = null, ?string $contentType = null): void
    {
        $host = trim((string) $this->config->r2_account_id) . '.r2.cloudflarestorage.com';
        $canonicalUri = '/' . rawurlencode((string) $this->config->r2_bucket) . '/' . implode('/', array_map('rawurlencode', explode('/', ltrim($key, '/'))));
        $payloadHash = $filePath === null ? hash('sha256', '') : hash_file('sha256', $filePath);
        $timestamp = gmdate('Ymd\\THis\\Z');
        $date = gmdate('Ymd');

        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $timestamp,
        ];
        if ($contentType !== null) {
            $headers['content-type'] = $contentType;
        }
        ksort($headers);

        $canonicalHeaders = '';
        foreach ($headers as $name => $value) {
            $canonicalHeaders .= $name . ':' . trim($value) . "\n";
        }
        $signedHeaders = implode(';', array_keys($headers));
        $canonicalRequest = $method . "\n" . $canonicalUri . "\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
        $credentialScope = $date . '/' . self::REGION . '/' . self::SERVICE . '/aws4_request';
        $stringToSign = 'AWS4-HMAC-SHA256' . "\n" . $timestamp . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);
        $signingKey = $this->signingKey($date);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        $headers['authorization'] = 'AWS4-HMAC-SHA256 Credential=' . $this->config->r2_access_key_id . '/' . $credentialScope . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;

        $requestHeaders = [];
        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        $curl = curl_init('https://' . $host . $canonicalUri);
        $stream = null;
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 45,
        ]);
        if ($filePath !== null) {
            $stream = fopen($filePath, 'rb');
            if ($stream === false) {
                curl_close($curl);
                throw new RuntimeException('Unable to read the banner file for upload.');
            }
            curl_setopt($curl, CURLOPT_UPLOAD, true);
            curl_setopt($curl, CURLOPT_INFILE, $stream);
            curl_setopt($curl, CURLOPT_INFILESIZE, filesize($filePath));
        }

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if ($response === false || $status < 200 || $status >= 300) {
            throw new RuntimeException($error !== '' ? $error : 'Cloudflare R2 returned HTTP ' . $status);
        }
    }

    private function signingKey(string $date): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->config->r2_secret_access_key, true);
        $regionKey = hash_hmac('sha256', self::REGION, $dateKey, true);
        $serviceKey = hash_hmac('sha256', self::SERVICE, $regionKey, true);

        return hash_hmac('sha256', 'aws4_request', $serviceKey, true);
    }

    private function verifyPublicImage(string $url): void
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_RANGE => '0-0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response === false || $status < 200 || $status >= 300 || stripos($contentType, 'image/') !== 0) {
            $detail = $error !== '' ? $error : 'HTTP ' . $status;
            throw new RuntimeException('Cloudflare R2 public URL is not serving the uploaded image (' . $detail . '). Enable the bucket public development URL or configure a public custom domain.');
        }
    }
}
