<?php
namespace App\Libraries;

class AnalyticsRetention
{
    public static function cutoff(?\DateTimeImmutable $today = null): string
    {
        // Keep today and the preceding 29 calendar dates in the application timezone.
        return ($today ?? new \DateTimeImmutable('today'))->modify('-29 days')->format('Y-m-d');
    }

    public function prune($db): array
    {
        $result = [];
        // Explicit allowlist: never delete movies, links, settings, or other business data.
        foreach (['traffic_daily_visitors', 'traffic_daily_player_metrics', 'analytics_daily', 'video_daily_views'] as $table) {
            if (! $db->tableExists($table)) { continue; }
            $result[$table] = ['deleted' => 0, 'more' => false];
            for ($batch = 0; $batch < 100; $batch++) {
                if ($db->query("DELETE FROM `{$table}` WHERE `visit_date` < ? LIMIT 5000", [self::cutoff()]) === false) {
                    throw new \RuntimeException('Analytics cleanup failed for ' . $table);
                }
                $affected = $db->affectedRows();
                $result[$table]['deleted'] += $affected;
                $result[$table]['more'] = $affected === 5000;
                if ($affected < 5000) { break; }
            }
        }
        return $result;
    }
}
