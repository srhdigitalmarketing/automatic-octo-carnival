<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Entities\Link;
use App\Libraries\StreamResolver;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;
use CodeIgniter\HTTP\CURLRequest;
use DOMDocument;
use Throwable;

/**
 * Retrieves a host-provided poster URL for an existing video's stream links.
 * The image is never proxied through the browser: configured provider APIs are
 * tried first, followed by Open Graph metadata on the public stream page.
 */
class StreamPoster extends BaseAjax
{
    private const EARNVIDS_API_ROOT = 'https://earnvidsapi.com/api';
    public function index()
    {
        $movieId = (int) $this->request->getGet('movie_id');
        if ($movieId < 1) {
            $this->addError('A saved video is required before a host image can be retrieved.');

            return $this->jsonResponse();
        }

        // Check every configured stream candidate first. This lets a recovered
        // host return to service and prevents an unavailable host from being
        // used merely because it appears first in the database.
        $links = (new LinkModel())->findByMovieId($movieId, 'stream', true) ?: [];
        $resolver = new StreamResolver();
        foreach ($links as $link) {
            try {
                if (! $resolver->check($link)) {
                    continue;
                }

                $result = $this->posterFromProvider($link) ?: $this->posterFromStreamPage($link);
                if ($result !== null) {
                    $this->addData($result);

                    return $this->jsonResponse();
                }
            } catch (Throwable $exception) {
                log_message('warning', 'Could not retrieve stream poster for link {link}: {message}', [
                    'link' => (string) $link->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->addError('No thumbnail or poster was supplied by the available stream hosts.');

        return $this->jsonResponse();
    }

    /** @return array{poster_url: string, source: string}|null */
    private function posterFromProvider(Link $link): ?array
    {
        $videoId = trim((string) ($link->upnshare_video_id ?? '')) ?: $this->videoIdFromUrl((string) $link->link);
        $api = ! empty($link->api_id)
            ? (new ThirdPartyApi())->find((int) $link->api_id)
            : $this->apiForLink($link);

        if ($videoId === '') {
            return null;
        }
        if ($api === null || $api->status !== 'active' || trim((string) $api->api_token) === '') {
            return null;
        }

        foreach ($this->apiRoots((string) $api->api_base_url, (string) $api->provider) as $root) {
            $request = (string) $api->provider === 'upnshare'
                ? $this->requestJson($root . '/video/manage/' . rawurlencode($videoId), [], (string) $api->api_token)
                : $this->requestJson($root . '/file/info', [
                    'key' => (string) $api->api_token,
                    'file_code' => $videoId,
                ], (string) $api->api_token);

            if ($request === null || $request['status'] < 200 || $request['status'] >= 300 || ! is_array($request['payload'])) {
                continue;
            }

            $record = $request['payload']['data'] ?? $request['payload']['result'] ?? $request['payload'];
            if (is_array($record) && isset($record['video']) && is_array($record['video'])) {
                $record = array_merge($record, $record['video']);
            }
            if (is_array($record) && array_keys($record) === range(0, count($record) - 1)) {
                $record = $record[0] ?? [];
            }

            $poster = is_array($record) ? $this->firstImageUrl($record) : null;
            if ($poster !== null) {
                return ['poster_url' => $poster, 'source' => 'Host API: ' . (string) $api->name];
            }
        }

        return null;
    }

    private function apiForLink(Link $link): ?object
    {
        $linkHost = $this->normalisedHost((string) $link->link);
        if ($linkHost === '') {
            return null;
        }

        foreach ((new ThirdPartyApi())->where('status', 'active')->findAll() as $api) {
            if ($this->normalisedHost((string) $api->api_base_url) === $linkHost) {
                return $api;
            }
        }

        return null;
    }

    private function videoIdFromUrl(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return '';
        }

        $fragment = trim((string) ($parts['fragment'] ?? ''));
        if ($fragment !== '') {
            return $fragment;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path === '') {
            return '';
        }

        $parts = explode('/', $path);
        $candidate = trim((string) end($parts));

        return preg_match('/^[A-Za-z0-9_-]{3,128}$/', $candidate) ? $candidate : '';
    }

    private function normalisedHost(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return preg_replace('/^www\./', '', $host) ?: '';
    }

    /** @return array{poster_url: string, source: string}|null */
    private function posterFromStreamPage(Link $link): ?array
    {
        $url = (string) $link->link;
        $host = $this->safeHost($url);
        if ($host === null) {
            return null;
        }

        $response = $this->httpClient($host)->get($url, [
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml',
                'User-Agent' => 'Mozilla/5.0 (compatible; BangkongAI-PosterFetcher/1.0)',
            ],
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return null;
        }

        $contentType = strtolower($response->getHeaderLine('Content-Type'));
        if ($contentType !== '' && strpos($contentType, 'html') === false) {
            return null;
        }

        $poster = $this->imageFromHtml((string) $response->getBody(), $url);
        if ($poster === null) {
            return null;
        }

        return ['poster_url' => $poster, 'source' => 'Stream page: ' . (string) parse_url($url, PHP_URL_HOST)];
    }

    /** @return array{status: int, payload: array<string, mixed>|null}|null */
    private function requestJson(string $url, array $query, string $token): ?array
    {
        $host = $this->safeHost($url);
        if ($host === null) {
            return null;
        }

        $response = $this->httpClient($host)->get($url, [
            'query' => $query,
            'headers' => ['Accept' => 'application/json', 'api-token' => $token],
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        return [
            'status' => $response->getStatusCode(),
            'payload' => is_array($payload) ? $payload : null,
        ];
    }

    /** @return array<int, string> */
    private function apiRoots(string $baseUrl, string $provider): array
    {
        if ($provider === 'earnvids') {
            return [self::EARNVIDS_API_ROOT];
        }

        $baseUrl = rtrim($baseUrl, '/');
        if ($baseUrl === '') {
            return [];
        }

        if ($provider !== 'upnshare') {
            $baseUrl = preg_replace('#/file/(?:info|list)$#i', '', $baseUrl) ?: $baseUrl;
        }

        $roots = [$baseUrl];
        if ($provider === 'upnshare') {
            $parts = parse_url($baseUrl);
            $origin = ! empty($parts['scheme']) && ! empty($parts['host'])
                ? $parts['scheme'] . '://' . $parts['host'] . (! empty($parts['port']) ? ':' . $parts['port'] : '')
                : $baseUrl;
            if (! preg_match('#/api/v1$#i', $baseUrl)) {
                $roots[] = rtrim($origin, '/') . '/api/v1';
            }
        } elseif (! preg_match('#/api(?:/v1)?$#i', $baseUrl)) {
            array_unshift($roots, $baseUrl . '/api');
        }

        return array_values(array_unique($roots));
    }

    private function firstImageUrl(array $record): ?string
    {
        foreach (['poster_url', 'posterUrl', 'poster', 'thumbnail', 'thumbnail_url', 'thumbnailUrl', 'player_img', 'preview_url', 'previewUrl', 'preview', 'image_url', 'imageUrl', 'image'] as $key) {
            if (! empty($record[$key]) && is_scalar($record[$key]) && $this->safeImageUrl((string) $record[$key])) {
                return (string) $record[$key];
            }
        }

        return null;
    }

    private function imageFromHtml(string $html, string $pageUrl): ?string
    {
        if ($html === '' || ! class_exists(DOMDocument::class)) {
            return null;
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach ($document->getElementsByTagName('meta') as $meta) {
            $name = strtolower((string) ($meta->getAttribute('property') ?: $meta->getAttribute('name')));
            if (! in_array($name, ['og:image', 'og:image:url', 'twitter:image', 'twitter:image:src'], true)) {
                continue;
            }

            $imageUrl = $this->absoluteUrl((string) $meta->getAttribute('content'), $pageUrl);
            if ($imageUrl !== null && $this->safeImageUrl($imageUrl)) {
                return $imageUrl;
            }
        }

        return null;
    }

    private function absoluteUrl(string $url, string $pageUrl): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (strpos($url, '//') === 0) {
            return (string) parse_url($pageUrl, PHP_URL_SCHEME) . ':' . $url;
        }
        if (parse_url($url, PHP_URL_SCHEME) !== null) {
            return $url;
        }

        $parts = parse_url($pageUrl);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $origin = $parts['scheme'] . '://' . $parts['host'] . (! empty($parts['port']) ? ':' . $parts['port'] : '');

        if (strpos($url, '/') === 0) {
            return $origin . $url;
        }

        $path = isset($parts['path']) ? dirname($parts['path']) : '';
        return rtrim($origin . '/' . trim($path, '/'), '/') . '/' . $url;
    }

    private function safeImageUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false && $this->safeHost($url) !== null;
    }

    /** @return array{host: string, port: int, ip: string}|null */
    private function safeHost(string $url): ?array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || $host === 'localhost' || substr($host, -10) === '.localhost') {
            return null;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return [
            'host' => $host,
            'port' => (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80)),
            'ip' => $ip,
        ];
    }

    private function httpClient(array $host): CURLRequest
    {
        return service('curlrequest', [
            'timeout' => 8,
            'http_errors' => false,
            'allow_redirects' => false,
            'curl' => [CURLOPT_RESOLVE => ["{$host['host']}:{$host['port']}:{$host['ip']}"]],
        ], false);
    }
}
