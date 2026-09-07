<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Analytics;
use App\Libraries\AdRevenueToday;
use App\Models\LiveTrafficModel;
use App\Models\MovieModel;


class Dashboard extends BaseController
{
    public function index()
    {
        $title = 'Dashboard';
        $hidePageTitle = true;

        $analytics = new Analytics();
        $anytc = $analytics->init()
                            ->getData();

        $movieModel = new MovieModel();
        $topMovies = $movieModel->movies()
                                ->where('views > ', 0)
                                ->orderBy('views', 'DESC')
                                ->findAll(10);

        $liveTraffic = $this->liveTrafficSummary();
        $visitorStats = $this->visitorStatistics();
        $dailyPlayerAnalytics = $this->dailyPlayerAnalytics($visitorStats);
        $revenueSummary = (new AdRevenueToday())->cachedSummary();

        $data = compact('title', 'hidePageTitle', 'anytc', 'topMovies', 'liveTraffic', 'visitorStats', 'dailyPlayerAnalytics', 'revenueSummary');

        return view('admin/dashboard/index', $data);
    }

    public function live_traffic()
    {
        return $this->response->setJSON($this->liveTrafficSummary());
    }

    public function revenue_today()
    {
        return $this->response->setJSON((new AdRevenueToday())->synchronize());
    }

    private function liveTrafficSummary(): array
    {
        try {
            if (! db_connect()->tableExists('live_traffic')) {
                return ['active_now' => 0, 'tracking_ready' => false];
            }

            return [
                'active_now' => (new LiveTrafficModel())->activeEmbedVisitors(),
                'tracking_ready' => true,
            ];
        } catch (\Throwable $exception) {
            log_message('error', 'Live traffic summary could not be loaded: {message}', [
                'message' => $exception->getMessage(),
            ]);

            return ['active_now' => 0, 'tracking_ready' => false];
        }
    }

    private function visitorStatistics(): array
    {
        return (new \App\Libraries\GoogleAnalyticsAudience())->summary();
    }

    /**
     * Keep the dashboard focused on daily player activity rather than a
     * cumulative table: embeds opened, first plays, and unique browsers.
     */
    private function dailyPlayerAnalytics(array $visitorStats): array
    {
        $start = new \DateTimeImmutable('-6 days');
        $rowsByDate = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $start->modify("+{$day} days")->format('Y-m-d');
            $rowsByDate[$date] = [
                'date' => $date,
                'impressions' => 0,
                'play_clicks' => 0,
                'unique_visitors' => 0,
            ];
        }

        $result = [
            'rows' => array_values(array_reverse($rowsByDate)),
            'tracking_ready' => false,
        ];

        try {
            $db = db_connect();
            $metricsReady = $db->tableExists('traffic_daily_player_metrics');
            $visitorsReady = $visitorStats['tracking_ready'];

            if (! $metricsReady && ! $visitorsReady) {
                return $result;
            }

            $from = $start->format('Y-m-d');
            if ($metricsReady) {
                $metrics = $db->table('traffic_daily_player_metrics')
                    ->select('visit_date, impressions, play_clicks')
                    ->where('visit_date >=', $from)
                    ->get()
                    ->getResultArray();

                foreach ($metrics as $metric) {
                    $date = (string) $metric['visit_date'];
                    if (isset($rowsByDate[$date])) {
                        $rowsByDate[$date]['impressions'] = (int) $metric['impressions'];
                        $rowsByDate[$date]['play_clicks'] = (int) $metric['play_clicks'];
                    }
                }
            }

            if ($visitorsReady) {
                foreach ($visitorStats['dates'] as $index => $date) {
                    if (isset($rowsByDate[$date])) {
                        $rowsByDate[$date]['unique_visitors'] = $visitorStats['daily'][$index];
                    }
                }
            }
            $result['rows'] = array_values(array_reverse($rowsByDate));
            $result['tracking_ready'] = $metricsReady && $visitorsReady;
        } catch (\Throwable $exception) {
            log_message('error', 'Daily player analytics could not be loaded: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

}
