<?php
namespace App\Libraries;

class MysqlAudience
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

    public function record(string $visitor, string $agent): void
    {
        $now = date('Y-m-d H:i:s');
        $ok = db_connect()->query(
            'INSERT INTO traffic_daily_visitors (visit_date, visitor_key, platform, created_at, updated_at) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE visitor_key = VALUES(visitor_key)',
            [date('Y-m-d'), $visitor, self::platform($agent), $now, $now]
        );
        if ($ok === false) { throw new \RuntimeException('Audience record could not be saved.'); }
    }

    public function audience(): array
    {
        $key = 'mysql_audience_v2_' . date('Ymd');
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

}
