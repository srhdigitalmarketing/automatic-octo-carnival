<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Models\ThirdPartyApi;
use CodeIgniter\HTTP\CURLRequest;
use Throwable;

/**
 * Searches the enabled video-host accounts without exposing API tokens to the
 * browser. It supports the standard XVideoSharing File List response used by
 * StreamHG and compatible hosts.
 */
class HostVideoSearch extends BaseAjax
{
    private const RESULTS_PER_HOST = 6;
    private const MAX_RESULTS = 12;
    private const MAX_UPNSHARE_LOCAL_PAGES = 5;
    private $searchFailure = '';
    private const STREAMHG_API_ROOT = 'https://streamhgapi.com/api';

    public function index()
    {
        $title = trim((string) $this->request->getGet('title'));

        if (mb_strlen($title) < 3 || mb_strlen($title) > 160) {
            $this->addError('Enter at least 3 characters to search video hosts.');

            return $this->jsonResponse();
        }

        $apis = [];
        foreach ((new ThirdPartyApi())->where('status', 'active')->findAll() as $api) {
            if (in_array((string) $api->provider, ['upnshare'], true) && trim((string) $api->api_token) !== '') {
                $apis[] = $api;
            }
        }

        $items = [];
        $errors = [];

        foreach ($apis as $api) {
            if (count($items) >= self::MAX_RESULTS) {
                break;
            }

            try {
                $this->searchFailure = '';
                $search = $this->searchHostFiles($api, $title);
                if ($search === null) {
                    $errors[] = (string) $api->name . ': ' . ($this->searchFailure ?: 'Pencarian API gagal. Periksa token dan koneksi server.');
                    continue;
                }

                foreach ($search['files'] as $file) {
                    if (count($items) >= self::MAX_RESULTS) {
                        break 2;
                    }

                    if (! is_array($file) || (isset($file['canplay']) && ! filter_var($file['canplay'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) {
                        continue;
                    }

                    $playerUrl = $this->safeExternalUrl(
                        $file['link'] ?? $file['player_url'] ?? $file['video_url'] ?? $file['embed_url'] ?? $file['url'] ?? ''
                    );
                    // Only entries with a usable player URL may be selected.
                    if ($playerUrl === null) {
                        continue;
                    }

                    $posterUrl = $this->safeExternalUrl(
                        $file['thumbnail'] ?? $file['player_img'] ?? $file['thumb'] ?? $file['poster'] ?? $file['poster_url'] ?? ''
                    );
                    $fileCode = trim((string) ($file['file_code'] ?? $file['filecode'] ?? $file['id'] ?? ''));

                    // File List normally includes thumbnail. File Info is used
                    // only as a small fallback for hosts that expose player_img
                    // separately (such as some XVideoSharing installations).
                    if (!in_array((string) $api->provider, ['upnshare'], true) && $posterUrl === null && $fileCode !== '') {
                        $posterUrl = $this->posterFromFileInfo($api, $search['api_root'], $fileCode);
                    }

                    $items[] = [
                        'api_id' => (int) $api->id,
                        'source' => (string) $api->name,
                        'provider' => (string) $api->provider,
                        'title' => trim((string) ($file['title'] ?? $file['file_title'] ?? $file['name'] ?? $title)),
                        'player_url' => $playerUrl ?: '',
                        'poster_url' => null,
                        'file_code' => $fileCode,
                    ];
                }
            } catch (Throwable $exception) {
                // A single unavailable host must not break the Add/Edit form.
                $errors[] = (string) $api->name . ': koneksi pencarian gagal. Coba kembali atau periksa koneksi API.';
                // Exception messages may contain credentials; only record the error class.
                log_message('warning', 'Video-host search failed for API access {api}: {error}', [
                    'api' => (string) $api->id,
                    'error' => get_class($exception),
                ]);
            }
        }

        $this->addData([
            'items' => $items,
            'configured_hosts' => count($apis),
            'errors' => $errors,
        ]);

        return $this->jsonResponse();
    }

    /** @return array{files: array<int, array<string, mixed>>, api_root: string}|null */
    private function searchHostFiles(object $api, string $title): ?array
    {
        if ($api->provider === 'streamhg') return null;
        if ((string) $api->provider === 'upnshare') {
            return $this->searchUpnShareFiles($api, $title);
        }

        foreach ($this->apiRoots($api) as $apiRoot) {
            $host = $this->getSafeHostConfig($apiRoot);
            if ($host === null) {
                continue;
            }

            $response = $this->httpClient($host)->get($apiRoot . '/file/list', [
                'query' => [
                    'key' => (string) $api->api_token,
                    'title' => $title,
                    'per_page' => self::RESULTS_PER_HOST,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                $this->searchFailure = 'File List ditolak (HTTP ' . (int) $response->getStatusCode() . '). Periksa API key dan izin akun.';
                continue;
            }

            $payload = json_decode($response->getBody(), true);
            if (! is_array($payload) || ! $this->isFileListPayload($payload)) {
                $this->searchFailure = 'Respons File List tidak valid (status API ' . (int) ($payload['status'] ?? 0) . '). Periksa API key dan izin akun.';
                continue;
            }

            $codes = [];
            foreach ($this->filesFromPayload($payload) as $file) {
                $code = is_array($file) ? (string) ($file['file_code'] ?? $file['filecode'] ?? '') : '';
                if (preg_match('/^[A-Za-z0-9_-]{3,128}$/', $code)) $codes[] = $code;
            }
            $codes = array_slice(array_values(array_unique($codes)), 0, self::RESULTS_PER_HOST);
            if (!$codes) return ['files' => [], 'api_root' => $apiRoot];
            $info = $this->httpClient($host)->get($apiRoot . '/file/info', [
                'query' => ['key' => (string) $api->api_token, 'file_code' => implode(',', $codes)],
                'headers' => ['Accept' => 'application/json'],
            ]);
            $body = json_decode((string) $info->getBody(), true);
            if ((int) $info->getStatusCode() !== 200 || !is_array($body) || (int) ($body['status'] ?? 0) !== 200 || !is_array($body['result'] ?? null)) {
                $this->searchFailure = 'File Info gagal (HTTP ' . (int) $info->getStatusCode() . ', status API ' . (int) ($body['status'] ?? 0) . '). Periksa API key dan izin akun.';
                return null;
            }
            // File Info provides status; File List provides the documented player link.
            foreach ($body['result'] as &$record) {
                if (!is_array($record)) continue;
                foreach ($this->filesFromPayload($payload) as $listed) {
                    if (is_array($listed) && (string) ($listed['file_code'] ?? '') === (string) ($record['file_code'] ?? '')) {
                        $record['link'] = $listed['embed_url'] ?? $listed['player_url'] ?? $listed['link'] ?? '';
                        break;
                    }
                }
            }
            unset($record);
            return ['files' => $this->streamHgInfoFiles($body, $codes, $api), 'api_root' => $apiRoot];
        }

        return null;
    }

    /**
     * UPNShare uses a REST video inventory rather than XVideoSharing's
     * /file/list. First ask the host to search, then fall back to a bounded
     * local title match across its newest video pages when the host ignores or
     * does not implement query parameters.
     *
     * @return array{files: array<int, array<string, mixed>>, api_root: string}|null
     */
    private function searchUpnShareFiles(object $api, string $title): ?array
    {
        $cache = cache();
        // Version the key so empty responses saved by the former summary
        // endpoint never hide a result after this catalogue-search update.
        $cacheKey = 'upnshare-title-v4-' . sha1((string) $api->id . '|' . $this->normaliseTitle($title));
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && isset($cached['files'], $cached['api_root'])) {
            return $cached;
        }

        foreach ($this->upnShareApiRoots($api) as $apiRoot) {
            $firstPage = $this->upnShareVideoPage($api, $apiRoot, [
                'search' => $title,
                'page' => 1,
                'perPage' => 50,
            ]);

            if ($firstPage === null) {
                continue;
            }

            $matches = $this->matchingUpnShareVideos($firstPage['videos'], $title);

            // A number of UPNShare deployments expose only a paginated list.
            // In that case get the unfiltered first page and compare titles in
            // this application, then continue only a small number of pages.
            if (empty($matches)) {
                $localPage = $this->upnShareVideoPage($api, $apiRoot, [
                    'page' => 1,
                    'perPage' => 50,
                ]);

                if ($localPage !== null) {
                    $matches = $this->matchingUpnShareVideos($localPage['videos'], $title);
                    $lastPage = min(
                        max(1, (int) $localPage['last_page']),
                        self::MAX_UPNSHARE_LOCAL_PAGES
                    );

                    for ($page = 2; $page <= $lastPage && count($matches) < self::RESULTS_PER_HOST; $page++) {
                        $nextPage = $this->upnShareVideoPage($api, $apiRoot, [
                            'page' => $page,
                            'perPage' => 50,
                        ]);

                        if ($nextPage === null || empty($nextPage['videos'])) {
                            break;
                        }

                        $matches = array_merge($matches, $this->matchingUpnShareVideos($nextPage['videos'], $title));
                    }
                }
            }

            $files = [];
            foreach (array_slice($matches, 0, self::RESULTS_PER_HOST) as $video) {
                $videoId = $this->firstString($video, ['id', 'video_id', 'videoId', 'uuid', 'file_code', 'filecode', 'code']);
                // The inventory already supplies id/name; a detail failure must not hide it.
                $file = $this->normaliseUpnShareVideo($video, [], $videoId);
                if ($file['link'] === '' && preg_match('/^[A-Za-z0-9_-]{3,128}$/', $videoId)) {
                    $hostnames = preg_split('/[\s,]+/', strtolower(trim((string) $api->embed_domains)), -1, PREG_SPLIT_NO_EMPTY);
                    $hostname = $hostnames[0] ?? '';
                    if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && strpos($hostname, '.') !== false) {
                        $file['link'] = 'https://' . $hostname . '/#' . rawurlencode($videoId);
                    }
                }

                // The management list always contains a title and poster, but
                // an installation can restrict playback URLs to its detail
                // endpoint. Keep that result visible so the admin can still
                // choose the matching file using its configured embed hostname.
                if ($file['link'] !== '' && $file['canplay']) {
                    $files[] = $file;
                }
            }

            $result = ['files' => $files, 'api_root' => $apiRoot];
            $cache->save($cacheKey, $result, 60);

            return $result;
        }

        return null;
    }

    /** @return array<int, string> */
    private function upnShareApiRoots(object $api): array
    {
        $base = rtrim((string) $api->api_base_url, '/');
        $parts = parse_url($base);
        $origin = ! empty($parts['scheme']) && ! empty($parts['host'])
            ? $parts['scheme'] . '://' . $parts['host'] . (! empty($parts['port']) ? ':' . $parts['port'] : '')
            : $base;

        $roots = [$base];
        if (! preg_match('#/api/v1$#i', $base)) {
            $roots[] = rtrim($origin, '/') . '/api/v1';
        }

        return array_values(array_unique($roots));
    }

    /** @return array{videos: array<int, array<string, mixed>>, last_page: int}|null */
    private function upnShareVideoPage(object $api, string $apiRoot, array $query): ?array
    {
        $host = $this->getSafeHostConfig($apiRoot);
        if ($host === null) {
            return null;
        }

        // /video is only UPNShare's account summary. /video/manage is the
        // documented paginated catalogue and is the only endpoint that
        // accepts a title search.
        $response = $this->httpClient($host)->get($apiRoot . '/video/manage', [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
                'api-token' => (string) $api->api_token,
            ],
        ]);

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return null;
        }

        $payload = json_decode($response->getBody(), true);

        return is_array($payload) ? $this->upnSharePageFromPayload($payload) : null;
    }

    /** @return array{videos: array<int, array<string, mixed>>, last_page: int}|null */
    private function upnSharePageFromPayload(array $payload): ?array
    {
        $container = $payload['data'] ?? $payload['result'] ?? $payload;
        if (! is_array($container)) {
            return null;
        }

        $videos = null;
        if (isset($container['id']) && (isset($container['name']) || isset($container['title']))) {
            $videos = [$container];
        } elseif ($this->isListArray($container)) {
            $videos = $container;
        } else {
            foreach (['data', 'videos', 'items', 'results'] as $key) {
                if (isset($container[$key]) && is_array($container[$key])) {
                    $videos = $container[$key];
                    break;
                }
            }
        }

        if ($videos === null) {
            return null;
        }

        $lastPage = (int) (
            $payload['metadata']['maxPage']
            ?? $container['metadata']['maxPage']
            ?? $container['last_page']
            ?? $container['meta']['last_page']
            ?? $payload['last_page']
            ?? $payload['meta']['last_page']
            ?? 1
        );

        return [
            'videos' => array_values(array_filter($videos, 'is_array')),
            'last_page' => max(1, $lastPage),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function matchingUpnShareVideos(array $videos, string $query): array
    {
        $matches = [];
        $needle = $this->normaliseTitle($query);

        foreach ($videos as $video) {
            $videoTitle = $this->firstString($video, ['title', 'video_title', 'file_title', 'name', 'original_name']);
            if ($videoTitle !== '' && $this->titlesMatch($needle, $this->normaliseTitle($videoTitle))) {
                $matches[] = $video;
            }
        }

        return $matches;
    }

    private function upnShareVideoDetails(object $api, string $apiRoot, string $videoId): array
    {
        $host = $this->getSafeHostConfig($apiRoot);
        if ($host === null) {
            return [];
        }

        $response = $this->httpClient($host)->get(
            $apiRoot . '/video/manage/' . rawurlencode($videoId),
            ['headers' => ['Accept' => 'application/json', 'api-token' => (string) $api->api_token]]
        );

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return [];
        }

        $payload = json_decode($response->getBody(), true);
        $details = is_array($payload) ? ($payload['data'] ?? $payload['result'] ?? $payload) : [];
        if (is_array($details) && isset($details['video']) && is_array($details['video'])) {
            $details = array_merge($details, $details['video']);
        }

        return is_array($details) && ! $this->isListArray($details) ? $details : [];
    }

    /** @return array<string, mixed> */
    private function normaliseUpnShareVideo(array $video, array $details, string $videoId): array
    {
        $record = array_merge($video, $details);

        return [
            'title' => $this->firstString($record, ['title', 'video_title', 'videoTitle', 'file_title', 'fileTitle', 'name', 'original_name', 'originalName']),
            'link' => $this->firstString($record, ['player_url', 'playerUrl', 'play_url', 'playUrl', 'video_url', 'videoUrl', 'embed_url', 'embedUrl', 'watch_url', 'watchUrl', 'public_url', 'publicUrl', 'video_link', 'videoLink', 'link', 'url']),
            'thumbnail' => $this->firstString($record, ['poster_url', 'posterUrl', 'poster', 'thumbnail', 'thumbnail_url', 'thumbnailUrl', 'player_img', 'preview_url', 'previewUrl', 'preview', 'image_url', 'imageUrl', 'image']),
            'file_code' => $videoId,
            'canplay' => !in_array(strtolower((string) ($record['status'] ?? '')), ['deleted', 'removed', 'error', 'failed', 'processing', 'pending', 'encoding'], true) && filter_var($record['canplay'] ?? $record['playable'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function firstString(array $record, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($record[$key]) && is_scalar($record[$key]) && trim((string) $record[$key]) !== '') {
                return trim((string) $record[$key]);
            }
        }

        return '';
    }

    private function normaliseTitle(string $title): string
    {
        $title = mb_strtolower($title, 'UTF-8');
        $title = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title);

        return trim((string) preg_replace('/\s+/u', ' ', $title));
    }

    private function titlesMatch(string $needle, string $haystack): bool
    {
        if ($needle === '' || $haystack === '') {
            return false;
        }

        if (mb_strpos($haystack, $needle, 0, 'UTF-8') !== false || mb_strpos($needle, $haystack, 0, 'UTF-8') !== false) {
            return true;
        }

        $tokens = array_filter(explode(' ', $needle), static function ($token) {
            return mb_strlen($token, 'UTF-8') >= 3;
        });

        foreach ($tokens as $token) {
            if (mb_strpos($haystack, $token, 0, 'UTF-8') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Admins commonly enter either a host origin or its /api root. Try both
     * forms safely so an otherwise valid API Access does not silently miss
     * XVideoSharing's standard endpoint.
     *
     * @return array<int, string>
     */
    private function apiRoots(object $api): array
    {
        if (in_array((string) $api->provider, ['streamhg'], true)) {
            return [self::STREAMHG_API_ROOT];
        }

        $base = rtrim((string) $api->api_base_url, '/');
        if ((string) $api->provider !== 'upnshare') {
            $base = preg_replace('#/file/(?:info|list)$#i', '', $base) ?: $base;
        }
        $roots = [$base];

        if (! preg_match('#/api(?:/v1)?$#i', $base)) {
            array_unshift($roots, $base . '/api');

            if ((string) $api->provider === 'upnshare') {
                $roots[] = $base . '/api/v1';
            }
        } elseif (preg_match('#/api/v1$#i', $base)) {
            $roots[] = substr($base, 0, -3);
        }

        return array_values(array_unique($roots));
    }

    private function streamHgInfoFiles(array $payload, array $codes, object $api): array
    {
        $files = [];
        foreach ($payload['result'] ?? [] as $record) {
            if (!is_array($record) || !in_array((string) ($record['file_code'] ?? ''), $codes, true)
                || (int) ($record['status'] ?? 0) !== 200 || !filter_var($record['canplay'] ?? false, FILTER_VALIDATE_BOOLEAN)) continue;
            $record['title'] = (string) ($record['file_title'] ?? $record['title'] ?? '');
            $files[] = $this->normaliseStreamHgFile($record, $api);
        }
        return $files;
    }

    private function normaliseStreamHgFile(array $file, object $api): array
    {
        // Use the provider URL; do not invent an embed path for StreamHG.
        $file['link'] = $this->safeExternalUrl($file['embed_url'] ?? $file['player_url'] ?? $file['link'] ?? '') ?: '';
        return $file;
    }

    private function isFileListPayload(array $payload): bool
    {
        return isset($payload['result']['files'])
            || isset($payload['files'])
            || isset($payload['data']['files'])
            || isset($payload['data']['items']);
    }

    /** @return array<int, array<string, mixed>> */
    private function filesFromPayload(array $payload): array
    {
        $files = $payload['result']['files']
            ?? $payload['files']
            ?? $payload['data']['files']
            ?? $payload['data']['items']
            ?? [];

        return is_array($files) ? $files : [];
    }

    /** @return array{host: string, port: int, ip: string}|null */
    private function getSafeHostConfig(string $baseUrl): ?array
    {
        $parts = parse_url($baseUrl);
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
        return \Config\Services::curlrequest([
            'timeout' => 8,
            'http_errors' => false,
            'allow_redirects' => false,
            // Pin the verified public address for this request. This prevents a
            // configured host from resolving to an internal address mid-request.
            'curl' => [CURLOPT_RESOLVE => ["{$host['host']}:{$host['port']}:{$host['ip']}"]],
        ], null, null, false);
    }

    private function posterFromFileInfo(object $api, string $apiRoot, string $fileCode): ?string
    {
        $host = $this->getSafeHostConfig($apiRoot);
        if ($host === null) {
            return null;
        }

        $response = $this->httpClient($host)->get(
            $apiRoot . '/file/info',
            [
                'query' => [
                    'key' => (string) $api->api_token,
                    'file_code' => $fileCode,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                    'api-token' => (string) $api->api_token,
                ],
            ]
        );

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return null;
        }

        $payload = json_decode($response->getBody(), true);
        if (! is_array($payload)) {
            return null;
        }

        $info = $payload['result'] ?? $payload['data'] ?? $payload;
        if (is_array($info) && $this->isListArray($info)) {
            $info = $info[0] ?? [];
        }

        return is_array($info)
            ? $this->safeExternalUrl($info['player_img'] ?? $info['thumbnail'] ?? $info['poster'] ?? '')
            : null;
    }

    private function isListArray(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    private function safeExternalUrl($url): ?string
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
