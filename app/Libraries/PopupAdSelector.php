<?php

namespace App\Libraries;

use App\Models\LiveTrafficModel;
use App\Models\PopupAdUnitModel;

/**
 * Chooses a popup unit using same-day eCPM, not the legacy rotation weight.
 *
 * Low traffic deliberately explores under-sampled networks more often. High
 * traffic protects revenue by favoring the established eCPM leader while
 * retaining a small exploration share in case market performance changes.
 */
class PopupAdSelector
{
    private const HIGH_TRAFFIC_THRESHOLD = 10;
    private const MINIMUM_IMPRESSIONS = 200;

    /** @var PopupAdUnitModel */
    private $popupAdUnits;

    public function __construct()
    {
        $this->popupAdUnits = new PopupAdUnitModel();
    }

    public function selectId(): ?int
    {
        $units = $this->popupAdUnits
            ->select('id')
            ->where('page', 'embed')
            ->where('status', 'active')
            ->where('ad_code !=', '')
            ->findAll();

        if ($units === []) {
            return null;
        }

        $summary = (new AdRevenueToday())->latestSummary();
        $metrics = $summary['unit_metrics'] ?? [];
        $candidates = [];

        foreach ($units as $unit) {
            $id = (string) $unit['id'];
            $hasMetric = is_array($metrics[$id] ?? null);
            $metric = $hasMetric ? $metrics[$id] : [];
            $candidates[] = [
                'id' => (int) $unit['id'],
                'impressions' => max(0, (int) ($metric['impressions'] ?? 0)),
                'ecpm' => max(0, (float) ($metric['ecpm'] ?? 0)),
                'tracked' => $hasMetric,
            ];
        }

        // Once at least one provider supplies usable statistics, do not send
        // traffic to a provider whose API failed or is not supported yet.
        $tracked = array_values(array_filter($candidates, function (array $candidate): bool {
            return $candidate['tracked'];
        }));
        if ($tracked !== []) {
            $candidates = $tracked;
        }

        if (count($candidates) === 1) {
            return $candidates[0]['id'];
        }

        $traffic = $this->activeTraffic();
        $highTraffic = $traffic >= self::HIGH_TRAFFIC_THRESHOLD;
        $underSampled = array_filter($candidates, function (array $candidate): bool {
            return $candidate['impressions'] < self::MINIMUM_IMPRESSIONS;
        });

        // Until every network has a meaningful sample, favor the least-tested
        // option. This avoids declaring a winner from a few impressions.
        if ($underSampled !== []) {
            return $this->leastSampledId(array_values($underSampled));
        }

        // Once data is reliable: high traffic is mostly exploit (90%), while
        // low traffic keeps a larger 40% exploration share.
        $explorationRate = $highTraffic ? 10 : 40;
        if (random_int(1, 100) <= $explorationRate) {
            return $this->leastSampledId($candidates);
        }

        usort($candidates, function (array $left, array $right): int {
            return $right['ecpm'] <=> $left['ecpm'];
        });

        return $candidates[0]['id'];
    }

    private function leastSampledId(array $candidates): int
    {
        usort($candidates, function (array $left, array $right): int {
            if ($left['impressions'] === $right['impressions']) {
                return $left['id'] <=> $right['id'];
            }
            return $left['impressions'] <=> $right['impressions'];
        });

        $minimum = $candidates[0]['impressions'];
        $tied = array_values(array_filter($candidates, function (array $candidate) use ($minimum): bool {
            return $candidate['impressions'] === $minimum;
        }));

        return $tied[array_rand($tied)]['id'];
    }

    private function activeTraffic(): int
    {
        try {
            if (! db_connect()->tableExists('live_traffic')) {
                return 0;
            }

            return (new LiveTrafficModel())->activeEmbedVisitors();
        } catch (\Throwable $exception) {
            return 0;
        }
    }
}
