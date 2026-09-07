<?php
namespace App\Libraries;
use Config\UpnShare;

class UpnShareClient
{
    private $config;
    private $transport;
    private $accountVerified;
    public function __construct(?UpnShare $config = null, ?callable $transport = null)
    {
        $this->config = $config ?: config('UpnShare');
        $this->transport = $transport;
    }
    public function isConfigured(): bool { return trim((string) $this->config->apiToken) !== ''; }

    public function videoIsAvailable(string $videoId): ?bool
    {
        $result = $this->videoStatus($videoId);
        if ($result['status'] === 'available') { return true; }
        if (in_array($result['status'], ['deleted','error','processing'], true)) { return false; }
        return null;
    }

    /** Only GET requests to the documented API; never delete or modify provider files. */
    private function request(string $path): array
    {
        if ($this->transport) { return ($this->transport)($path); }
        try {
            $response = \Config\Services::curlrequest([
                'timeout' => $this->config->requestTimeoutSeconds,
                'connect_timeout' => 5, 'http_errors' => false, 'allow_redirects' => false,
                'verify' => true, 'headers' => ['api-token' => $this->config->apiToken, 'Accept' => 'application/json'],
            ], null, null, false)->get('https://upnshare.com/api/v1' . $path);
            return ['http' => (int) $response->getStatusCode(), 'body' => json_decode((string) $response->getBody(), true)];
        } catch (\Throwable $error) {
            // Do not log tokens or raw provider response bodies.
            return ['http' => 0, 'body' => null];
        }
    }

    public function videoStatus(string $id): array
    {
        if (! $this->isConfigured()) { return ['status' => 'unknown', 'message' => 'UPNShare API token is not configured']; }
        if (! preg_match('/^[A-Za-z0-9_-]{3,128}$/', $id)) { return ['status' => 'unknown', 'message' => 'Invalid UPNShare video ID']; }
        $response = $this->request('/video/manage/' . rawurlencode($id));
        $http = $response['http']; $body = $response['body'];
        if ($http === 404 && is_array($body) && strtolower(trim((string) ($body['message'] ?? ''))) === 'not found') {
            // A generic web/proxy 404 is not proof. Verify this token can read inventory.
            if ($this->accountVerified === null) {
                $account = $this->request('/video/manage?page=1&perPage=1');
                $this->accountVerified = $account['http'] === 200 && is_array($account['body'])
                    && isset($account['body']['data']) && is_array($account['body']['data']);
            }
            if ($this->accountVerified) { return ['status' => 'deleted', 'message' => 'UPNShare: file not found in the configured account (404)']; }
        }
        if ($http !== 200) {
            return ['status' => 'unknown', 'skip_playback' => in_array($http, [404,522], true), 'message' => $http === 0 ? 'UPNShare connection failed' : 'UPNShare check failed (HTTP ' . $http . ')'];
        }
        $record = is_array($body) ? ($body['data'] ?? $body) : null;
        if (! is_array($record) || ! isset($record['id']) || (string) $record['id'] !== $id) {
            return ['status' => 'unknown', 'message' => 'UPNShare returned an unexpected video response'];
        }
        $status = strtolower(trim((string) ($record['status'] ?? '')));
        if (in_array($status, ['deleted','removed'], true)) { return ['status'=>'deleted','message'=>'UPNShare reports the file as deleted']; }
        if (in_array($status, ['error','failed','failure','unavailable'], true)) { return ['status'=>'error','message'=>'UPNShare reports a video error']; }
        if (in_array($status, ['pending','processing','encoding','transcoding','queued','uploading'], true)) { return ['status'=>'processing','message'=>'UPNShare is processing this video']; }
        if (in_array($status, ['ready','active','available','completed','complete','finished','success','done'], true)) { return ['status'=>'available','message'=>'UPNShare reports the video as available']; }
        // The OpenAPI schema does not enumerate statuses. Never guess an unknown value.
        return ['status'=>'unknown','message'=>'UPNShare returned an unrecognized video status: ' . substr(preg_replace('/[^a-z0-9_-]/', '', $status), 0, 32)];
    }
}
