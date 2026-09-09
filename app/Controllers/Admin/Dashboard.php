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
        $revenueSummary = (new AdRevenueToday())->cachedSummary();

        $data = compact('title', 'hidePageTitle', 'anytc', 'topMovies', 'liveTraffic', 'revenueSummary');

        return view('admin/dashboard/index', $data);
    }

    public function live_traffic()
    {
        return $this->response->setJSON($this->liveTrafficSummary());
    }

    public function revenue_today()
    {
        // Authentication has finished; external requests must not lock navigation
        // in other tabs/AJAX requests sharing the same admin session.
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

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

}
