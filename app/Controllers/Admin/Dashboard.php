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
        $dailyPlayerAnalytics = $this->dailyPlayerAnalytics();
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

    /**
     * Build a 30-day view from compact, anonymous daily visitor rows.
     * The raw rows are automatically pruned by LiveTrafficModel after 30 days.
     */
    private function visitorStatistics(): array
    {
        try {
            $cached = cache()->get('dashboard_visitor_statistics_30_days');
            if (is_array($cached)) {
                return $cached;
            }
        } catch (\Throwable $exception) {
            // Statistics remain available when the optional cache is unavailable.
        }

        $start = new \DateTimeImmutable('-29 days');
        $labels = [];
        $daily = [];
        $byDate = [];

        for ($day = 0; $day < 30; $day++) {
            $date = $start->modify("+{$day} days");
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('d M');
            $daily[] = 0;
            $byDate[$key] = $day;
        }

        $result = [
            'labels' => $labels,
            'daily' => $daily,
            'total' => 0,
            'platforms' => ['desktop' => 0, 'mobile' => 0],
            'tracking_ready' => false,
        ];

        try {
            $db = db_connect();
            if (! $db->tableExists('traffic_daily_visitors')) {
                return $result;
            }

            $from = $start->format('Y-m-d');
            $dailyRows = $db->table('traffic_daily_visitors')
                ->select('visit_date, COUNT(*) AS visitors')
                ->where('visit_date >=', $from)
                ->groupBy('visit_date')
                ->get()
                ->getResultArray();

            foreach ($dailyRows as $row) {
                $date = (string) $row['visit_date'];
                if (isset($byDate[$date])) {
                    $result['daily'][$byDate[$date]] = (int) $row['visitors'];
                }
            }

            $platformRows = $db->table('traffic_daily_visitors')
                ->select('platform, COUNT(*) AS visitors')
                ->where('visit_date >=', $from)
                ->groupBy('platform')
                ->get()
                ->getResultArray();

            foreach ($platformRows as $row) {
                $platform = (string) $row['platform'];
                if (array_key_exists($platform, $result['platforms'])) {
                    $result['platforms'][$platform] = (int) $row['visitors'];
                }
            }

            $totalRow = $db->table('traffic_daily_visitors')
                ->select('COUNT(DISTINCT visitor_key) AS visitors', false)
                ->where('visit_date >=', $from)
                ->get()
                ->getFirstRow();

            $result['total'] = $totalRow ? (int) $totalRow->visitors : 0;
            $result['tracking_ready'] = true;

            try {
                cache()->save('dashboard_visitor_statistics_30_days', $result, 300);
            } catch (\Throwable $exception) {
                // A cache failure must not block the admin dashboard.
            }
        } catch (\Throwable $exception) {
            log_message('error', 'Visitor statistics could not be loaded: {message}', [
                'message' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Keep the dashboard focused on daily player activity rather than a
     * cumulative table: embeds opened, first plays, and unique browsers.
     */
    private function dailyPlayerAnalytics(): array
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
            $visitorsReady = $db->tableExists('traffic_daily_visitors');

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
                $visitors = $db->table('traffic_daily_visitors')
                    ->select('visit_date, COUNT(*) AS visitors')
                    ->where('visit_date >=', $from)
                    ->groupBy('visit_date')
                    ->get()
                    ->getResultArray();

                foreach ($visitors as $visitor) {
                    $date = (string) $visitor['visit_date'];
                    if (isset($rowsByDate[$date])) {
                        $rowsByDate[$date]['unique_visitors'] = (int) $visitor['visitors'];
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
