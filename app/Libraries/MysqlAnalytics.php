<?php
namespace App\Libraries;

class MysqlAnalytics
{
    public static function cutoff(?\DateTimeImmutable $today = null): string
    {
        return ($today ?? new \DateTimeImmutable('today'))->modify('-29 days')->format('Y-m-d');
    }

    public static function platform(string $agent): string
    {
        if ($agent === '') { return 'other'; }
        if (preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $agent)) { return 'tablet'; }
        return preg_match('/mobile|iphone|ipod|android/i', $agent) ? 'mobile' : 'desktop';
    }

    public function record(string $visitor, string $platform, bool $impression, bool $play): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $queries = [
            ["INSERT INTO live_traffic (page, visitor_key, last_seen_at) VALUES ('embed', ?, ?) ON DUPLICATE KEY UPDATE last_seen_at = VALUES(last_seen_at)", [$visitor, $now]],
            ["INSERT INTO traffic_daily_visitors (visit_date, visitor_key, platform, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE visitor_key = VALUES(visitor_key)", [date('Y-m-d'), $visitor, $platform, $now, $now]],
        ];
        if ($impression || $play) {
            $queries[] = ["INSERT INTO traffic_daily_player_metrics (visit_date, impressions, play_clicks, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE impressions = impressions + VALUES(impressions), play_clicks = play_clicks + VALUES(play_clicks), updated_at = VALUES(updated_at)", [date('Y-m-d'), (int) $impression, (int) $play, $now, $now]];
        }
        foreach ($queries as [$sql, $params]) {
            if ($db->query($sql, $params) === false) { throw new \RuntimeException('MySQL analytics write failed.'); }
        }
    }

    public function audience(): array
    {
        $key = 'mysql_audience_' . date('Ymd');
        if ($cached = cache()->get($key)) { return $cached; }
        $db = db_connect();
        $from = self::cutoff();
        $until = date('Y-m-d');
        $daily = $db->query('SELECT visit_date, COUNT(*) AS total FROM traffic_daily_visitors WHERE visit_date BETWEEN ? AND ? GROUP BY visit_date', [$from, $until])->getResultArray();
        $byDate = array_column($daily, 'total', 'visit_date');
        $result = ['dates' => [], 'labels' => [], 'daily' => [], 'platforms' => ['desktop' => 0, 'mobile' => 0, 'tablet' => 0, 'other' => 0], 'tracking_ready' => true, 'notice' => 'Statistik MySQL, diperbarui setiap 5 menit.'];
        $start = new \DateTimeImmutable($from);
        for ($i = 0; $i < 30; $i++) {
            $day = $start->modify("+{$i} days"); $date = $day->format('Y-m-d');
            $result['dates'][] = $date; $result['labels'][] = $day->format('d M'); $result['daily'][] = (int) ($byDate[$date] ?? 0);
        }
        $result['total'] = (int) $db->query('SELECT COUNT(DISTINCT visitor_key) AS total FROM traffic_daily_visitors WHERE visit_date BETWEEN ? AND ?', [$from, $until])->getRowArray()['total'];
        foreach ($db->query('SELECT platform, COUNT(DISTINCT visitor_key) AS total FROM traffic_daily_visitors WHERE visit_date BETWEEN ? AND ? GROUP BY platform', [$from, $until])->getResultArray() as $row) {
            $platform = isset($result['platforms'][$row['platform']]) ? $row['platform'] : 'other';
            $result['platforms'][$platform] += (int) $row['total'];
        }
        cache()->save($key, $result, 300);
        return $result;
    }

    public function prune($db): int
    {
        $deleted = 0;
        // Bounded batches avoid a single very large delete/transaction.
        foreach (['traffic_daily_visitors', 'traffic_daily_player_metrics', 'analytics_daily', 'live_traffic'] as $table) {
            if (! $db->tableExists($table)) { continue; }
            $column = $table === 'live_traffic' ? 'last_seen_at' : 'visit_date';
            $cutoff = $table === 'live_traffic' ? date('Y-m-d H:i:s', time()-600) : self::cutoff();
            for ($batch = 0; $batch < 100; $batch++) {
                if ($db->query("DELETE FROM `{$table}` WHERE `{$column}` < ? LIMIT 5000", [$cutoff]) === false) {
                    throw new \RuntimeException('Analytics retention cleanup failed.');
                }
                $affected = $db->affectedRows(); $deleted += $affected;
                if ($affected < 5000) { break; }
            }
        }
        return $deleted;
    }
}
