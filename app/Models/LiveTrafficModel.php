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
