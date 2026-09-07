<?php

namespace App\Libraries;

use App\Entities\Link;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;
use Config\UpnShare;

/**
 * Chooses an available stream host, remembers the outcome and rotates the next
 * request to a different healthy link. All probing occurs server-side, so the
 * browser never needs cross-origin access to a host.
 */
class StreamResolver
{
    private const EARNVIDS_API_ROOT = 'https://earnvidsapi.com/api';
    /** @var LinkModel */
    private $links;
    /** @var UpnShare */
    private $config;
    /** @var UpnShareClient */
    private $upnShare;

    public function __construct(?LinkModel $links = null, ?UpnShareClient $upnShare = null)
    {
        $this->links = $links ?: new LinkModel();
        $this->config = config('UpnShare');
        $this->upnShare = $upnShare ?: new UpnShareClient($this->config);
    }

    public function resolve(int $movieId, ?int $preferredId = null, array $excludedIds = []): ?Link
    {
        $excludedIds = array_values(array_filter(array_map('intval', $excludedIds)));
        $candidates = $this->links->findByMovieId($movieId, 'stream', false);

        // The original database does not contain the stream-health columns.
        // Fall back to its normal link ordering instead of making playback fail.
        if (! $this->links->supportsStreamHealthFields()) {
            foreach ($candidates as $link) {
                if (! in_array((int) $link->id, $excludedIds, true)) {
                    return $link;
                }
            }

            return null;
        }

        if (! empty($preferredId) && ! in_array($preferredId, $excludedIds, true)) {
            usort($candidates, function (Link $left, Link $right) use ($preferredId) {
                return ($left->id === $preferredId ? 0 : 1) <=> ($right->id === $preferredId ? 0 : 1);
            });
        }

        foreach ($candidates as $link) {
            if (in_array((int) $link->id, $excludedIds, true)) {
                continue;
            }

            if (! $this->isHealthy($link)) {
                continue;
            }

            $this->recordSuccess($link);
            return $link;
        }

        return null;
    }

    public function recordPlayerFailure(int $linkId, string $reason = 'Player did not load'): void
    {
        if (! $this->links->supportsStreamHealthFields()) {
            return;
        }

        $link = $this->links->getLink($linkId);
        if ($link === null || $link->type !== 'stream') {
            return;
        }

        $count = (int) $link->failure_count + 1;
        $this->links->protect(false)->update($linkId, [
            'failure_count' => $count,
            'last_checked_at' => date('Y-m-d H:i:s'),
            'last_failure_at' => date('Y-m-d H:i:s'),
            'last_error' => substr($reason, 0, 255),
            'is_broken' => $count >= $this->config->failureThreshold ? 1 : 0,
        ]);
    }

    /**
     * EarnVids direct links are generated for the current viewer IP, so they
     * must be requested only when playback starts and must never be stored.
     */
    public function deliveryUrl(Link $link, string $endUserIp): string
    {
        if (! filter_var($endUserIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || empty($link->api_id)) {
            return (string) $link->link;
        }

        $api = (new ThirdPartyApi())->find((int) $link->api_id);
        $videoId = trim((string) $link->upnshare_video_id) ?: $this->videoIdFromUrl((string) $link->link);
        if ($api === null || $api->provider !== 'earnvids' || $api->status !== 'active' || $videoId === '') {
            return (string) $link->link;
        }

        $response = $this->providerRequest(self::EARNVIDS_API_ROOT . '/file/direct_link', [
            'key' => (string) $api->api_token,
            'file_code' => $videoId,
            'ip' => $endUserIp,
        ], (string) $api->api_token);

        if ($response === null || $response['status'] < 200 || $response['status'] >= 300 || ! is_array($response['payload'])) {
            return (string) $link->link;
        }

        return $this->directUrlFromPayload($response['payload']) ?: (string) $link->link;
    }

    /** Run one explicit availability check, used by the scheduled health job. */
    public function check(Link $link): bool
    {
        if (! $this->links->supportsStreamHealthFields() || $link->type !== 'stream') {
            return false;
        }

        if (! $this->isHealthy($link)) {
            return false;
        }

        $this->recordSuccess($link, false);
        return true;
    }

    private function isHealthy(Link $link): bool
    {
        $lastCheck = $link->last_checked_at ? strtotime($link->last_checked_at) : 0;
        if ($lastCheck && (time() - $lastCheck) < $this->config->healthCacheSeconds) {
            return ! (bool) $link->is_broken && empty($link->last_error);
        }

        // A provider API is authoritative for deletions. HTTP probing remains
        // the safe fallback for links without a configured API/video identifier.
        $apiAvailability = $this->providerAvailability($link);
        $healthy = $apiAvailability !== null
            ? $apiAvailability
            : $this->isSafePublicUrl($link->link) && $this->probeHost($link->link);

        if (! $healthy) {
            $this->recordPlayerFailure((int) $link->id, 'Host health check failed');
        } else {
            $this->links->protect(false)->update($link->id, [
                'last_checked_at' => date('Y-m-d H:i:s'),
                'last_error' => null,
            ]);
        }

        return $healthy;
    }

    /**
     * Returns true/false only when an API can make an authoritative decision.
     * Returning null deliberately falls back to a lightweight HTTP probe.
     */
    private function providerAvailability(Link $link): ?bool
    {
        $videoId = trim((string) $link->upnshare_video_id) ?: $this->videoIdFromUrl((string) $link->link);
        if ($videoId === '') {
            return null;
        }

        $api = ! empty($link->api_id)
            ? (new ThirdPartyApi())->find((int) $link->api_id)
            : $this->configuredApiForLink($link);
        if ($api !== null && $api->status === 'active' && trim((string) $api->api_token) !== '') {
            return $this->checkConfiguredProvider($api, $videoId);
        }

        if ($this->upnShare->isConfigured()) {
            return $this->upnShare->videoIsAvailable($videoId);
        }

        return null;
    }

    private function configuredApiForLink(Link $link): ?object
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

        $segments = explode('/', $path);
        $candidate = trim((string) end($segments));

        return preg_match('/^[A-Za-z0-9_-]{3,128}$/', $candidate) ? $candidate : '';
    }

    private function normalisedHost(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return preg_replace('/^www\./', '', $host) ?: '';
    }

    private function checkConfiguredProvider(object $api, string $videoId): ?bool
    {
        $provider = (string) $api->provider;
        $roots = $this->apiRoots((string) $api->api_base_url, $provider);

        foreach ($roots as $root) {
            $response = $provider === 'upnshare'
                ? $this->providerRequest($root . '/video/manage/' . rawurlencode($videoId), [], (string) $api->api_token)
                : $this->providerRequest($root . '/file/info', [
                    'key' => (string) $api->api_token,
                    'file_code' => $videoId,
                ], (string) $api->api_token);

            if ($response === null) {
                continue;
            }

            if ($response['status'] === 404 || $response['status'] === 410) {
                return false;
            }

            if ($response['status'] < 200 || $response['status'] >= 300) {
                continue;
            }

            $payload = $response['payload'];
            if (is_array($payload) && isset($payload['status']) && (int) $payload['status'] >= 400) {
                return false;
            }

            $record = is_array($payload) ? ($payload['data'] ?? $payload['result'] ?? $payload) : null;
            if (is_array($record) && isset($record['video']) && is_array($record['video'])) {
                $record = $record['video'];
            }
            if (is_array($record) && array_keys($record) === range(0, count($record) - 1)) {
                $record = $record[0] ?? [];
            }

            if (! is_array($record) || $record === []) {
                continue;
            }

            // EarnVids/VidHide File Info returns a per-file status and
            // canplay flag. A successful HTTP response alone is not enough:
            // deleted, disabled, or still-encoding files must not be marked
            // healthy or used as a banner source.
            if (isset($record['status']) && is_numeric($record['status']) && (int) $record['status'] >= 400) {
                return false;
            }
            if (array_key_exists('canplay', $record)) {
                $canPlay = filter_var($record['canplay'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($canPlay === false) {
                    return false;
                }
            }

            // A successful API response with a returned record confirms that
            // the provider still has this file. Ambiguous responses use HTTP.
            return true;
        }

        return null;
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
            // Existing settings can contain /api/file/info or /api/file/list.
            // The availability request below appends its own endpoint.
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

    /** @param array<string, mixed> $payload */
    private function directUrlFromPayload(array $payload): ?string
    {
        $values = [
            $payload['result'] ?? null,
            $payload['data'] ?? null,
            $payload['direct_link'] ?? null,
            $payload['link'] ?? null,
            $payload['url'] ?? null,
        ];

        foreach ($values as $value) {
            if (is_string($value) && $this->isSafePublicUrl($value)) {
                return $value;
            }
            if (is_array($value)) {
                foreach (['direct_link', 'link', 'url', 'file_url'] as $key) {
                    if (! empty($value[$key]) && is_string($value[$key]) && $this->isSafePublicUrl($value[$key])) {
                        return $value[$key];
                    }
                }
            }
        }

        return null;
    }

    /** @return array{status: int, payload: array<string, mixed>|null}|null */
    private function providerRequest(string $url, array $query, string $token): ?array
    {
        if (! $this->isSafePublicUrl($url)) {
            return null;
        }

        try {
            $response = service('curlrequest', [
                'timeout' => $this->config->requestTimeoutSeconds,
                'http_errors' => false,
                'allow_redirects' => false,
            ])->get($url, [
                'query' => $query,
                'headers' => ['Accept' => 'application/json', 'api-token' => $token],
            ]);

            $payload = json_decode((string) $response->getBody(), true);
            return [
                'status' => $response->getStatusCode(),
                'payload' => is_array($payload) ? $payload : null,
            ];
        } catch (\Throwable $exception) {
            log_message('warning', 'Provider availability check failed: {message}', ['message' => $exception->getMessage()]);
            return null;
        }
    }

    private function recordSuccess(Link $link, bool $markServed = true): void
    {
        if (! $this->links->supportsStreamHealthFields()) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $data = [
            'failure_count' => 0,
            'is_broken' => 0,
            'last_checked_at' => $now,
            'last_success_at' => $now,
            'last_error' => null,
        ];

        // Scheduled checks must not affect the round-robin order used for
        // real viewers. Only a selected playback link is considered served.
        if ($markServed) {
            $data['last_served_at'] = $now;
        }

        $this->links->protect(false)->update($link->id, $data);

        // A successful provider/API or HTTP check confirms the visitor report
        // is no longer actionable. Keep "wrong video" reports for a person to
        // review because availability alone cannot validate video contents.
        if ((int) ($link->reports_not_working ?? 0) > 0) {
            $this->links->clearNotWorkingReports((int) $link->id);
        }
    }

    private function probeHost(string $url): bool
    {
        try {
            $response = service('curlrequest', [
                'timeout' => $this->config->requestTimeoutSeconds,
                'http_errors' => false,
                'allow_redirects' => true,
                'headers' => ['Range' => 'bytes=0-1'],
            ])->get($url);

            $status = $response->getStatusCode();
            return $status >= 200 && $status < 400;
        } catch (\Throwable $exception) {
            log_message('warning', 'Stream host probe failed: {message}', ['message' => $exception->getMessage()]);
            return false;
        }
    }

    private function isSafePublicUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['host']) || empty($parts['scheme'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower($parts['host']);
        if ($host === 'localhost' || substr($host, -10) === '.localhost') {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
