<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyPlayerAnalyticsModel extends Model
{
    protected $table = 'traffic_daily_player_metrics';
    protected $returnType = 'array';
    protected $allowedFields = ['visit_date', 'impressions', 'play_clicks', 'created_at', 'updated_at'];

    public function recordImpression(): void
    {
        $this->incrementMetric('impressions');
    }

    public function recordPlayClick(): void
    {
        $this->incrementMetric('play_clicks');
    }

    private function incrementMetric(string $column): void
    {
        if (! $this->db->tableExists($this->table)) {
            return;
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $impressions = $column === 'impressions' ? 1 : 0;
        $playClicks = $column === 'play_clicks' ? 1 : 0;

        try {
            $this->db->query(
                "INSERT INTO `{$this->table}` (`visit_date`, `impressions`, `play_clicks`, `created_at`, `updated_at`)\n"
                . "VALUES (?, ?, ?, ?, ?)\n"
                . "ON DUPLICATE KEY UPDATE `{$column}` = `{$column}` + 1, `updated_at` = VALUES(`updated_at`)",
                [$today, $impressions, $playClicks, $now, $now]
            );
        } catch (\Throwable $exception) {
            log_message('warning', 'Daily player metric could not be recorded: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
