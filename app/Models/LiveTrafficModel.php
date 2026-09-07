<?php

namespace App\Models;

use CodeIgniter\Model;

class LiveTrafficModel extends Model
{
    protected $table = 'live_traffic';
    protected $returnType = 'array';
    protected $allowedFields = ['page', 'visitor_key', 'last_seen_at'];

    public function touchEmbedVisitor(string $visitorKey): void
    {
        $this->pruneExpiredLiveVisitors();

        $existing = $this->where('page', 'embed')
            ->where('visitor_key', $visitorKey)
            ->first();

        $data = [
            'page' => 'embed',
            'visitor_key' => $visitorKey,
            'last_seen_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing === null) {
            $this->insert($data);
            return;
        }

        $this->update($existing['id'], $data);
    }

    public function activeEmbedVisitors(int $withinSeconds = 180): int
    {
        $since = date('Y-m-d H:i:s', time() - $withinSeconds);

        return $this->where('page', 'embed')
            ->where('last_seen_at >=', $since)
            ->countAllResults();
    }

    /**
     * Record one anonymous browser per day. The endpoint calls this only for
     * the initial player ping, not for the recurring live-traffic heartbeat.
     */
    public function recordDailyEmbedVisitor(string $visitorKey, string $platform): void
    {
        if (! $this->db->tableExists('traffic_daily_visitors')) {
            return;
        }

        $this->pruneDailyVisitors();

        $today = date('Y-m-d');
        $dailyVisitors = $this->db->table('traffic_daily_visitors');
        $exists = $dailyVisitors->where('visit_date', $today)
            ->where('visitor_key', $visitorKey)
            ->countAllResults();

        if ($exists > 0) {
            return;
        }

        try {
            $this->db->table('traffic_daily_visitors')->insert([
                'visit_date' => $today,
                'visitor_key' => $visitorKey,
                'platform' => $platform === 'mobile' ? 'mobile' : 'desktop',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $exception) {
            // The unique index makes concurrent first visits harmless.
        }
    }

    private function pruneDailyVisitors(): void
    {
        $cacheKey = 'traffic_daily_visitors_pruned_at';

        try {
            if (cache()->get($cacheKey)) {
                return;
            }

            $cutoff = date('Y-m-d', strtotime('-29 days'));
            $this->db->table('traffic_daily_visitors')
                ->where('visit_date <', $cutoff)
                ->delete();

            cache()->save($cacheKey, 1, 21600);
        } catch (\Throwable $exception) {
            log_message('warning', 'Daily traffic retention cleanup failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function pruneExpiredLiveVisitors(): void
    {
        $cacheKey = 'live_traffic_pruned_at';

        try {
            if (cache()->get($cacheKey)) {
                return;
            }

            $cutoff = date('Y-m-d H:i:s', time() - 600);
            $this->db->table('live_traffic')
                ->where('page', 'embed')
                ->where('last_seen_at <', $cutoff)
                ->delete();

            cache()->save($cacheKey, 1, 600);
        } catch (\Throwable $exception) {
            log_message('warning', 'Live traffic retention cleanup failed: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

}
