<?php
namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** GA4 audience summaries. No visitor rows or OAuth tokens are persisted locally. */
class GoogleAnalyticsAudience
{
    private $settings;

    public function __construct($settings = null)
    {
        $this->settings = $settings ?? config('GoogleAnalytics');
    }

    public function summary(): array
    {
        $today = new DateTimeImmutable('today', new DateTimeZone($this->settings->timezone));
        $result = $this->emptySummary($today);
        if (! preg_match('/^[0-9]+$/', $this->settings->propertyId) || ! is_readable($this->settings->credentialsPath)) {
            $result['notice'] = 'Pelacakan GA4 disiapkan. Hubungkan service account dengan akses Viewer untuk menampilkan laporan.';
            return $result;
        }
        // A fixed filename per configuration avoids accumulating daily cache files.
        $cachePath = WRITEPATH . 'cache/ga4-audience-' . hash('sha256', $this->settings->propertyId . $this->settings->timezone) . '.json';
        $cached = is_file($cachePath) ? json_decode((string) @file_get_contents($cachePath), true) : null;
        if (is_array($cached) && ($cached['day'] ?? '') === $today->format('Y-m-d') && ($cached['expires'] ?? 0) > time()) {
            return $cached['result'];
        }
        try {
            $token = $this->accessToken();
            $response = $this->request(
                'https://analyticsdata.googleapis.com/v1beta/properties/' . $this->settings->propertyId . ':batchRunReports',
                json_encode(['requests' => $this->reportRequests($today)]),
                ['Content-Type: application/json', 'Authorization: Bearer ' . $token]
            );
            $result = $this->normalize($response, $today);
        } catch (\Throwable $error) {
            $result['notice'] = 'Laporan GA4 belum tersedia. Periksa Property ID, akses Viewer service account, dan aktivasi Analytics Data API.';
            // Never expose provider responses or private credentials in the dashboard/log.
        }
        @file_put_contents($cachePath, json_encode(['day' => $today->format('Y-m-d'), 'expires' => time() + ($result['tracking_ready'] ? 300 : 60), 'result' => $result]), LOCK_EX);
        return $result;
    }

    protected function reportRequests(DateTimeImmutable $today): array
    {
        $base = [
            'dateRanges' => [['startDate' => $today->modify('-29 days')->format('Y-m-d'), 'endDate' => $today->format('Y-m-d')]],
            'metrics' => [['name' => 'totalUsers']],
            'dimensionFilter' => ['andGroup' => ['expressions' => [
                ['filter' => ['fieldName' => 'pagePath', 'stringFilter' => ['matchType' => 'EXACT', 'value' => '/embed-player']]],
                ['filter' => ['fieldName' => 'eventName', 'stringFilter' => ['matchType' => 'EXACT', 'value' => 'page_view']]],
            ]]],
            'limit' => '100',
        ];
        return [
            array_merge($base, ['dimensions' => [['name' => 'date']]]),
            array_merge($base, ['dimensions' => [['name' => 'deviceCategory']]]),
            $base,
        ];
    }

    protected function emptySummary(DateTimeImmutable $today): array
    {
        $result = ['labels' => [], 'dates' => [], 'daily' => [], 'total' => 0,
            'platforms' => ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'other' => 0],
            'tracking_ready' => false, 'source' => 'Google Analytics 4', 'notice' => ''];
        for ($i = 29; $i >= 0; $i--) {
            $date = $today->modify('-' . $i . ' days');
            $result['dates'][] = $date->format('Y-m-d');
            $result['labels'][] = $date->format('d M');
            $result['daily'][] = 0;
        }
        return $result;
    }

    protected function normalize(array $response, DateTimeImmutable $today): array
    {
        if (! isset($response['reports']) || count($response['reports']) !== 3) {
            throw new RuntimeException('Invalid GA4 response');
        }
        $result = $this->emptySummary($today);
        $indices = array_flip(array_map(static function ($date) { return str_replace('-', '', $date); }, $result['dates']));
        foreach ($response['reports'][0]['rows'] ?? [] as $row) {
            $date = $row['dimensionValues'][0]['value'] ?? '';
            if (isset($indices[$date])) {
                $result['daily'][$indices[$date]] = max(0, (int) ($row['metricValues'][0]['value'] ?? 0));
            }
        }
        foreach ($response['reports'][1]['rows'] ?? [] as $row) {
            $device = $row['dimensionValues'][0]['value'] ?? 'other';
            $device = array_key_exists($device, $result['platforms']) ? $device : 'other';
            $result['platforms'][$device] += max(0, (int) ($row['metricValues'][0]['value'] ?? 0));
        }
        // GA4 calculates the unique total; do not sum daily users or device categories.
        $result['total'] = max(0, (int) ($response['reports'][2]['rows'][0]['metricValues'][0]['value'] ?? 0));
        $result['tracking_ready'] = true;
        return $result;
    }

    protected function accessToken(): string
    {
        $credentials = json_decode((string) file_get_contents($this->settings->credentialsPath), true);
        if (($credentials['type'] ?? '') !== 'service_account' || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Invalid service account file');
        }
        $encode = static function ($value) { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); };
        $now = time();
        $jwt = $encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $encode(json_encode([
            'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
        ]));
        if (! openssl_sign($jwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign service account request');
        }
        $response = $this->request('https://oauth2.googleapis.com/token', http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt . '.' . $encode($signature),
        ]), ['Content-Type: application/x-www-form-urlencoded']);
        if (empty($response['access_token'])) {
            throw new RuntimeException('Google authentication failed');
        }
        return $response['access_token'];
    }

    protected function request(string $url, string $body, array $headers): array
    {
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
        try {
            $raw = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        } finally {
            curl_close($curl);
        }
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if ($status !== 200 || ! is_array($data) || isset($data['error'])) {
            throw new RuntimeException('Google Analytics request failed');
        }
        return $data;
    }
}
