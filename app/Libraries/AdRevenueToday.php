<?php

namespace App\Libraries;

use App\Models\PopupAdUnitModel;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/** Retrieves today's publisher revenue without exposing credentials to browsers. */
class AdRevenueToday
{
    private const CACHE_TTL = 600;

    /** @var PopupAdUnitModel */
    private $popupAdUnits;

    public function __construct()
    {
        $this->popupAdUnits = new PopupAdUnitModel();
    }

    /** Return the cached value only, so dashboard rendering never waits on APIs. */
    public function cachedSummary(): array
    {
        $date = $this->today();
        $cached = cache()->get($this->cacheKey($date));

        if (is_array($cached)) {
            $cached['cached'] = true;
            return $cached;
        }

        $summary = $this->emptySummary($date);
        $summary['configured_units'] = $this->configuredUnitCount();
        if ($summary['configured_units'] === 0) {
            $summary['status'] = 'unconfigured';
            $summary['message'] = 'Tambahkan Zone ID dan API token pada network iklan aktif.';
        } else {
            $summary['message'] = 'Memuat pendapatan hari ini…';
        }

        return $summary;
    }

    /**
     * Synchronize every active, configured unit. The supported networks report
     * their API revenue in USD; no currency conversion is performed here.
     */
    public function synchronize(bool $force = false): array
    {
        $date = $this->today();
        $cached = cache()->get($this->cacheKey($date));
        if (! $force && is_array($cached)) {
            $cached['cached'] = true;
            return $cached;
        }

        $summary = $this->emptySummary($date);

        if (! $this->credentialColumnsAvailable()) {
            $summary['status'] = 'unavailable';
            $summary['message'] = 'Jalankan migrasi database sebelum menyinkronkan pendapatan.';
            return $summary;
        }

        $units = $this->popupAdUnits
            ->select('id, provider, name, zone_id, api_token')
            ->where('page', 'embed')
            ->where('status', 'active')
            ->findAll();

        foreach ($units as $unit) {
            if (trim((string) ($unit['zone_id'] ?? '')) === '' || trim((string) ($unit['api_token'] ?? '')) === '') {
                continue;
            }

            $summary['configured_units']++;
            try {
                $metrics = $this->fetchUnitMetrics($unit, $date);
                $summary['total'] += $metrics['revenue'];
                $summary['impressions'] += $metrics['impressions'];
                $summary['unit_metrics'][(string) $unit['id']] = $metrics + [
                    'provider' => (string) $unit['provider'],
                ];
                $summary['synchronized_units']++;
            } catch (\Throwable $exception) {
                // No token or response body may reach the log.
                log_message('warning', 'Ad revenue sync failed for popup unit {id} ({provider}): {message}', [
                    'id' => (int) $unit['id'],
                    'provider' => (string) $unit['provider'],
                    'message' => $exception->getMessage(),
                ]);
                $summary['failed_units']++;
            }
        }

        if ($summary['configured_units'] === 0) {
            $summary['status'] = 'unconfigured';
            $summary['message'] = 'Tambahkan Zone ID dan API token pada network iklan aktif.';
        } elseif ($summary['synchronized_units'] === 0) {
            $summary['status'] = 'error';
            $summary['message'] = 'Pendapatan belum dapat diambil. Periksa Zone ID, API token, dan format network.';
        } elseif ($summary['failed_units'] > 0) {
            $summary['status'] = 'partial';
            $summary['message'] = 'Sebagian network belum dapat disinkronkan.';
        } else {
            $summary['status'] = 'ready';
            $summary['message'] = 'Total dari semua network yang terhubung.';
        }

        $summary['total'] = round($summary['total'], 6);
        $summary['display_total'] = $this->formatTotal($summary['total']);
        $summary['updated_at'] = date('c');
        cache()->save($this->cacheKey($date), $summary, self::CACHE_TTL);
        cache()->save('ad-revenue-latest-' . $date, $summary, 86400);

        return $summary;
    }

    public static function forgetCachedRevenue(): void
    {
        $timezone = new DateTimeZone(config('App')->appTimezone ?: 'UTC');
        $date = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
        cache()->delete('ad-revenue-today-' . $date);
        cache()->delete('ad-revenue-latest-' . $date);
    }

    /** Return the latest same-day value even after the short dashboard cache expires. */
    public function latestSummary(): array
    {
        $date = $this->today();
        $latest = cache()->get('ad-revenue-latest-' . $date);
        return is_array($latest) ? $latest : $this->cachedSummary();
    }

    private function fetchUnitMetrics(array $unit, string $date): array
    {
        switch (strtolower((string) $unit['provider'])) {
            case 'clickadu':
                return $this->clickaduRevenue($unit, $date);
            case 'clickadilla':
                return $this->clickadillaRevenue($unit, $date);
            case 'adsterra':
                return $this->adsterraRevenue($unit, $date);
            default:
                throw new RuntimeException('This ad network does not have a revenue adapter.');
        }
    }

    private function clickaduRevenue(array $unit, string $date): array
    {
        $payload = $this->requestJson('https://v2.api.clickadu.com/partner/stats', [
            'token' => (string) $unit['api_token'],
            'dateFrom' => $date,
            'dateTo' => $date,
            'groupBy' => 'zone',
            'zoneId' => (string) $unit['zone_id'],
            'format' => 'json',
            'timezone' => config('App')->appTimezone ?: 'UTC',
        ]);

        if (! isset($payload['total']) || ! is_array($payload['total'])) {
            throw new RuntimeException('ClickAdu returned no total for this zone.');
        }

        return $this->metricsFromRecord($payload['total'], ['money']);
    }

    private function clickadillaRevenue(array $unit, string $date): array
    {
        $payload = $this->requestJson(
            'https://publishers.clickadilla.com/backend/api/public/stats',
            [
                'date1' => $date,
                'date2' => $date,
                'fields' => 'date,money,impressions',
                'filters' => 'spot=' . (string) $unit['zone_id'],
                'orderBy' => '-date',
                'limit' => 50,
                'offset' => 0,
            ],
            ['X-AUTH-TOKEN' => (string) $unit['api_token']]
        );

        return $this->metricsFromPayload($payload, ['money']);
    }

    private function adsterraRevenue(array $unit, string $date): array
    {
        $identifiers = preg_split('/\s*[:|,]\s*/', (string) $unit['zone_id']);
        if (! is_array($identifiers) || count($identifiers) !== 2 || $identifiers[0] === '' || $identifiers[1] === '') {
            throw new RuntimeException('Adsterra requires Zone ID in domain_id:placement_id format.');
        }

        $payload = $this->requestJson(
            'https://api3.adsterratools.com/publisher/stats.json',
            [
                'domain' => $identifiers[0],
                'placement' => $identifiers[1],
                'start_date' => $date,
                'finish_date' => $date,
                'group_by' => 'placement',
            ],
            ['Accept' => 'application/json', 'X-API-Key' => (string) $unit['api_token']]
        );

        return $this->metricsFromPayload($payload, ['revenue', 'money']);
    }

    private function requestJson(string $url, array $query, array $headers = []): array
    {
        $client = service('curlrequest', [
            'timeout' => 6,
            'connect_timeout' => 4,
            'http_errors' => false,
            'allow_redirects' => false,
            'headers' => $headers,
        ]);
        $response = $client->get($url, ['query' => $query]);
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Publisher API returned HTTP ' . $status . '.');
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Publisher API returned an invalid JSON response.');
        }

        return $payload;
    }

    private function metricsFromPayload(array $payload, array $fields): array
    {
        foreach (['total', 'summary'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->metricsFromRecord($payload[$key], $fields);
            }
        }
        foreach (['data', 'stats', 'result', 'rows'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $this->isList($payload[$key])
                    ? $this->metricsFromRecords($payload[$key], $fields)
                    : $this->metricsFromPayload($payload[$key], $fields);
            }
        }
        if ($this->isList($payload)) {
            return $this->metricsFromRecords($payload, $fields);
        }

        return $this->metricsFromRecord($payload, $fields);
    }

    private function metricsFromRecords(array $records, array $fields): array
    {
        if ($records === []) {
            return ['revenue' => 0.0, 'impressions' => 0, 'ecpm' => 0.0];
        }

        $revenue = 0.0;
        $impressions = 0;
        $found = false;
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }
            try {
                $metrics = $this->metricsFromRecord($record, $fields);
                $revenue += $metrics['revenue'];
                $impressions += $metrics['impressions'];
                $found = true;
            } catch (RuntimeException $exception) {
                continue;
            }
        }
        if (! $found) {
            throw new RuntimeException('Publisher API returned no revenue field.');
        }

        return [
            'revenue' => $revenue,
            'impressions' => $impressions,
            'ecpm' => $impressions > 0 ? ($revenue / $impressions) * 1000 : 0.0,
        ];
    }

    private function metricsFromRecord(array $record, array $fields): array
    {
        $revenue = null;
        foreach ($fields as $field) {
            if (! array_key_exists($field, $record)) {
                continue;
            }
            $value = $record[$field];
            if (is_numeric($value)) {
                $revenue = (float) $value;
                break;
            }
            if (is_string($value)) {
                $normalized = str_replace(',', '', trim($value));
                if (is_numeric($normalized)) {
                    $revenue = (float) $normalized;
                    break;
                }
            }
        }

        if ($revenue === null) {
            throw new RuntimeException('Publisher API returned no usable revenue value.');
        }

        $impressions = $this->integerFromRecord($record, ['impressions', 'imps']);
        return [
            'revenue' => $revenue,
            'impressions' => $impressions,
            'ecpm' => $impressions > 0 ? ($revenue / $impressions) * 1000 : 0.0,
        ];
    }

    private function integerFromRecord(array $record, array $fields): int
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $record)) {
                continue;
            }
            $value = is_string($record[$field]) ? str_replace(',', '', trim($record[$field])) : $record[$field];
            if (is_numeric($value)) {
                return max(0, (int) $value);
            }
        }

        return 0;
    }

    private function configuredUnitCount(): int
    {
        if (! $this->credentialColumnsAvailable()) {
            return 0;
        }
        return $this->popupAdUnits->where('page', 'embed')->where('status', 'active')
            ->where('zone_id !=', '')->where('api_token !=', '')->countAllResults();
    }

    private function credentialColumnsAvailable(): bool
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('popup_ad_units')) {
                return false;
            }
            $fields = $db->getFieldNames('popup_ad_units');
            return in_array('zone_id', $fields, true) && in_array('api_token', $fields, true);
        } catch (\Throwable $exception) {
            log_message('warning', 'Unable to inspect ad revenue fields: {message}', ['message' => $exception->getMessage()]);
            return false;
        }
    }

    private function emptySummary(string $date): array
    {
        return [
            'date' => $date,
            'currency' => 'USD',
            'total' => 0.0,
            'display_total' => $this->formatTotal(0),
            'impressions' => 0,
            'unit_metrics' => [],
            'configured_units' => 0,
            'synchronized_units' => 0,
            'failed_units' => 0,
            'status' => 'pending',
            'message' => '',
            'updated_at' => null,
            'cached' => false,
        ];
    }

    private function cacheKey(string $date): string
    {
        return 'ad-revenue-today-' . $date;
    }

    private function today(): string
    {
        $timezone = new DateTimeZone(config('App')->appTimezone ?: 'UTC');
        return (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
    }

    private function formatTotal(float $total): string
    {
        return 'US$ ' . number_format($total, 2, '.', ',');
    }

    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
