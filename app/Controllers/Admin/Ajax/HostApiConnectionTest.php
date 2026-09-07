<?php

namespace App\Controllers\Admin\Ajax;

use App\Controllers\BaseAjax;
use App\Models\ThirdPartyApi;
use CodeIgniter\HTTP\CURLRequest;
use Throwable;

/**
 * Safely validates a configured video-host API and returns only an admin-facing
 * sample record. Tokens are accepted for the current request only and are
 * never included in the JSON response or application logs.
 */
class HostApiConnectionTest extends BaseAjax
{
    private const EARNVIDS_API_ROOT = 'https://earnvidsapi.com/api';
    private const UPNSHARE_API_ROOT = 'https://upnshare.com/api/v1';
    /** @var string|null */
    private $lastFailure = null;

    public function index()
    {
        $provider = trim((string) $this->request->getPost('provider'));
        $baseUrl = trim((string) $this->request->getPost('api_base_url'));
        $token = trim((string) $this->request->getPost('api_token'));
        $apiId = (int) $this->request->getPost('api_id');

        if (! in_array($provider, ['upnshare', 'vidhide', 'earnvids', 'xvideosharing', 'custom'], true)) {
            $this->addError('Choose a supported video-host provider before testing the connection.');

            return $this->jsonResponse();
        }

        // EarnVids publishes one fixed API root. The API key is the only
        // administrator-supplied connection setting.
        if ($provider === 'earnvids') {
            $baseUrl = self::EARNVIDS_API_ROOT;
        } elseif ($provider === 'upnshare') {
            $baseUrl = self::UPNSHARE_API_ROOT;
        }

        if (mb_strlen($baseUrl) > 255 || $this->getSafeHostConfig($baseUrl) === null) {
            $this->addError($provider === 'earnvids'
                ? 'The official EarnVids API endpoint is unavailable from this server.'
                : 'Enter a public HTTP(S) API base URL before testing the connection.');

            return $this->jsonResponse();
        }

        // When editing an existing entry, a blank password field intentionally
        // means "reuse the saved token". The client never receives that token.
        if ($token === '' && $apiId > 0) {
            $savedApi = (new ThirdPartyApi())->find($apiId);
            if ($savedApi !== null) {
                $token = trim((string) $savedApi->api_token);
            }
        }

        if ($token === '') {
            $this->addError('Enter an API token, or save the API access first so its saved token can be tested.');

            return $this->jsonResponse();
        }

        try {
            $result = $provider === 'upnshare'
                ? $this->testUpnShare($baseUrl, $token)
                : $this->testXVideoSharingHost($baseUrl, $token, $provider);

            if ($result === null) {
                $this->addError($this->lastFailure ?: 'The host did not return a valid API response. Check the API key and provider setting.');

                return $this->jsonResponse();
            }

            $this->addData($result);
        } catch (Throwable $exception) {
            log_message('warning', 'Video-host API connection test failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
            $hostLabel = $provider === 'upnshare' ? 'UPNShare' : ($provider === 'earnvids' ? 'EarnVids' : 'the provider');
            $this->addError('The connection test could not reach ' . $hostLabel . '. Check the API key and this server\'s outbound network access.');
        }

        return $this->jsonResponse();
    }

    /** @return array<string, mixed>|null */
    private function testUpnShare(string $baseUrl, string $token): ?array
    {
        foreach ($this->upnShareRoots($baseUrl) as $apiRoot) {
            $list = $this->requestJson($apiRoot . '/video/manage', [
                'page' => 1,
                'perPage' => 1,
            ], $token, false);

            if ($list === null) {
                $this->lastFailure = 'The UPNShare host could not be reached from this server.';
                continue;
            }

            if ($list['status'] < 200 || $list['status'] >= 300) {
                $this->lastFailure = 'UPNShare returned HTTP ' . $list['status'] . '. Verify the API token and base URL.';
                continue;
            }

            if (! is_array($list['payload'])) {
                $this->lastFailure = 'UPNShare returned a non-JSON response. Verify the API base URL.';
                continue;
            }

            $videos = $list['payload']['data'] ?? [];
            if (! is_array($videos)) {
                return null;
            }

            $sample = [];
            $apiResponses = ['video_list' => $this->redactSensitivePayload($list['payload'])];
            $endpoint = $apiRoot . '/video/manage';
            if (! empty($videos) && is_array($videos[0])) {
                $sample = $videos[0];
                $videoId = $this->firstString($sample, ['id', 'video_id', 'videoId', 'uuid']);

                if ($videoId !== '') {
                    $detail = $this->requestJson(
                        $apiRoot . '/video/manage/' . rawurlencode($videoId),
                        [],
                        $token,
                        false
                    );
                    if ($detail !== null && $detail['status'] >= 200 && $detail['status'] < 300 && is_array($detail['payload'])) {
                        $apiResponses['video_info'] = $this->redactSensitivePayload($detail['payload']);
                        $endpoint .= '/{id}';
                        $detailData = $detail['payload']['data'] ?? $detail['payload']['result'] ?? $detail['payload'];
                        if (is_array($detailData)) {
                            if (isset($detailData['video']) && is_array($detailData['video'])) {
                                $detailData = array_merge($detailData, $detailData['video']);
                            }
                            $sample = array_merge($sample, $detailData);
                        }
                    }
                }
            }

            $result = $this->testResponse('upnshare', $endpoint, $list['status'], $sample);
            $result['responses'] = $apiResponses;

            return $result;
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function testXVideoSharingHost(string $baseUrl, string $token, string $provider): ?array
    {
        foreach ($this->standardApiRoots($baseUrl) as $apiRoot) {
            $list = $this->requestJson($apiRoot . '/file/list', [
                'key' => $token,
                'per_page' => 1,
            ], $token, true);

            if ($list === null) {
                $this->lastFailure = 'The video-host API could not be reached from this server.';
                continue;
            }

            if ($list['status'] < 200 || $list['status'] >= 300) {
                $this->lastFailure = $provider === 'earnvids'
                    ? 'EarnVids returned HTTP ' . $list['status'] . '. Verify the API key.'
                    : 'The video host returned HTTP ' . $list['status'] . '. Verify the API token and base URL.';
                continue;
            }

            if (! is_array($list['payload'])) {
                $this->lastFailure = $provider === 'earnvids'
                    ? 'EarnVids returned a non-JSON response. Try the API key again.'
                    : 'The video host returned a non-JSON response. Verify the API base URL.';
                continue;
            }

            $files = $list['payload']['result']['files']
                ?? $list['payload']['files']
                ?? $list['payload']['data']['files']
                ?? $list['payload']['data']['items']
                ?? [];
            $sample = is_array($files) && ! empty($files) && is_array($files[0]) ? $files[0] : [];
            $apiResponses = $provider === 'earnvids'
                ? ['file_list' => $this->redactSensitivePayload($list['payload'])]
                : [];

            // A File List record confirms the key works. For EarnVids, fetch
            // the matching File Info record as well so the connection screen
            // can show its authoritative playback state and host artwork.
            $endpoint = $apiRoot . '/file/list';
            if ($provider === 'earnvids' && $sample !== []) {
                $fileCode = $this->firstString($sample, ['file_code', 'filecode', 'id']);
                if ($fileCode !== '') {
                    $info = $this->requestJson($apiRoot . '/file/info', [
                        'key' => $token,
                        'file_code' => $fileCode,
                    ], $token, true);

                    if ($info !== null && $info['status'] >= 200 && $info['status'] < 300 && is_array($info['payload'])) {
                        $apiResponses['file_info'] = $this->redactSensitivePayload($info['payload']);
                        $record = $info['payload']['result'] ?? $info['payload']['data'] ?? [];
                        if (is_array($record) && $record !== [] && array_keys($record) === range(0, count($record) - 1)) {
                            $record = $record[0] ?? [];
                        }
                        if (is_array($record)) {
                            $sample = array_merge($sample, $record);
                            $endpoint .= ' + /file/info';
                        }
                    }
                }
            }

            $result = $this->testResponse($provider, $endpoint, $list['status'], $sample);
            if ($apiResponses !== []) {
                $result['responses'] = $apiResponses;
            }

            return $result;
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function testResponse(string $provider, string $endpoint, int $status, array $record): array
    {
        $playerUrl = $this->safeExternalUrl($this->firstString($record, [
            'player_url', 'playerUrl', 'play_url', 'playUrl', 'video_url', 'videoUrl', 'embed_url', 'embedUrl',
            'watch_url', 'watchUrl', 'stream_url', 'streamUrl', 'download_url', 'downloadUrl', 'public_url',
            'publicUrl', 'file_url', 'fileUrl', 'video_link', 'videoLink', 'link', 'url',
        ]));
        $posterUrl = $this->safeExternalUrl($this->firstString($record, [
            'poster_url', 'posterUrl', 'poster', 'thumbnail', 'thumbnail_url', 'thumbnailUrl', 'player_img',
            'preview_url', 'previewUrl', 'preview', 'image_url', 'imageUrl', 'image',
        ]));

        return [
            'provider' => $provider,
            'endpoint' => $endpoint,
            'http_status' => $status,
            'message' => empty($record)
                ? 'Connection successful. The API accepted the token, but this account has no video sample to display.'
                : 'Connection successful. A sample video record was returned by the host.',
            'sample' => empty($record) ? null : [
                'title' => $this->firstString($record, ['title', 'video_title', 'videoTitle', 'file_title', 'fileTitle', 'name', 'original_name', 'originalName']),
                'file_name' => $this->firstString($record, ['file_name', 'fileName', 'filename', 'original_name', 'originalName', 'name', 'title']),
                'video_id' => $this->firstString($record, ['id', 'video_id', 'videoId', 'uuid', 'file_code', 'filecode', 'code']),
                'can_play' => array_key_exists('canplay', $record) ? ((int) (bool) $record['canplay'] === 1 ? 'Ready to play' : 'Not ready to play') : '',
                'duration' => $this->firstString($record, ['length', 'duration', 'duration_seconds']),
                'uploaded_at' => $this->firstString($record, ['uploaded', 'uploaded_at', 'created_at', 'file_last_download']),
                'views' => $this->firstString($record, ['views', 'view_count', 'views_count']),
                'player_url' => $playerUrl ?: '',
                'poster_url' => $posterUrl ?: '',
            ],
        ];
    }

    /** @return array{status: int, payload: array<string, mixed>|null}|null */
    private function requestJson(string $url, array $query, string $token, bool $includeKeyHeader): ?array
    {
        $host = $this->getSafeHostConfig($url);
        if ($host === null) {
            return null;
        }

        $headers = ['Accept' => 'application/json', 'api-token' => $token];
        if ($includeKeyHeader) {
            $headers['X-Api-Key'] = $token;
        }

        // EarnVids is a fixed, trusted host. Let cURL resolve its CDN/TLS
        // route normally: forcing a single DNS address can fail when its edge
        // network rotates addresses between validation and the HTTPS request.
        $response = $this->httpClient($host, $host['host'] !== 'earnvidsapi.com')->get($url, [
            'query' => $query,
            'headers' => $headers,
        ]);
        $payload = json_decode($response->getBody(), true);

        return [
            'status' => $response->getStatusCode(),
            'payload' => is_array($payload) ? $payload : null,
        ];
    }

    /** @return array<int, string> */
    private function upnShareRoots(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $parts = parse_url($baseUrl);
        $origin = ! empty($parts['scheme']) && ! empty($parts['host'])
            ? $parts['scheme'] . '://' . $parts['host'] . (! empty($parts['port']) ? ':' . $parts['port'] : '')
            : $baseUrl;
        $roots = [$baseUrl];

        if (! preg_match('#/api/v1$#i', $baseUrl)) {
            $roots[] = rtrim($origin, '/') . '/api/v1';
        }

        return array_values(array_unique($roots));
    }

    /** @return array<int, string> */
    private function standardApiRoots(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        // API Access stores an API root. When the documented File Info URL
        // is pasted, strip that endpoint before this test adds /file/list.
        $baseUrl = preg_replace('#/file/(?:info|list)$#i', '', $baseUrl) ?: $baseUrl;
        $roots = [$baseUrl];

        if (! preg_match('#/api(?:/v1)?$#i', $baseUrl)) {
            array_unshift($roots, $baseUrl . '/api');
        }

        return array_values(array_unique($roots));
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

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function redactSensitivePayload(array $payload): array
    {
        $redactedKeys = ['key', 'api_key', 'api_token', 'token', 'authorization', 'password', 'secret'];

        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $redactedKeys, true)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redactSensitivePayload($value);
            }
        }

        return $payload;
    }

    private function safeExternalUrl(string $url): ?string
    {
        $config = $this->getSafeHostConfig($url);

        return $config === null ? null : $url;
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

    private function httpClient(array $host, bool $pinDns = true): CURLRequest
    {
        $options = [
            'timeout' => 10,
            'http_errors' => false,
            'allow_redirects' => false,
        ];

        if ($pinDns) {
            $options['curl'] = [CURLOPT_RESOLVE => ["{$host['host']}:{$host['port']}:{$host['ip']}"]];
        }

        return service('curlrequest', $options, false);
    }
}
