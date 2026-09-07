<?php

namespace App\Libraries;

use Config\UpnShare;

class UpnShareClient
{
    /** @var UpnShare */
    private $config;

    public function __construct(?UpnShare $config = null)
    {
        $this->config = $config ?: config('UpnShare');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->config->baseUrl) && ! empty($this->config->apiToken);
    }

    /**
     * Confirms whether a UPNShare video is still available. Returns false only
     * when the provider explicitly confirms deletion. Null means the caller
     * should use its normal HTTP availability fallback.
     */
    public function videoIsAvailable(string $videoId): ?bool
    {
        if (! $this->isConfigured() || empty($videoId)) {
            return null;
        }

        try {
            $response = service('curlrequest', [
                'timeout' => $this->config->requestTimeoutSeconds,
                'http_errors' => false,
                'headers' => ['api-token' => $this->config->apiToken],
            ])->get($this->config->baseUrl . '/api/v1/video/manage/' . rawurlencode($videoId));

            $status = $response->getStatusCode();
            if ($status === 404 || $status === 410) {
                return false;
            }

            if ($status !== 200) {
                return null;
            }

            $payload = json_decode($response->getBody(), true);
            $record = is_array($payload) ? ($payload['data'] ?? $payload['result'] ?? $payload) : null;
            if (is_array($record) && isset($record['video']) && is_array($record['video'])) {
                $record = $record['video'];
            }

            return is_array($record) && $record !== [] ? true : null;
        } catch (\Throwable $exception) {
            log_message('warning', 'UPNShare health check failed: {message}', ['message' => $exception->getMessage()]);
            return null;
        }
    }
}
