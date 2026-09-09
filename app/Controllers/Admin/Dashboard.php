<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Analytics;
use App\Libraries\AdRevenueToday;
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

        $revenueSummary = (new AdRevenueToday())->cachedSummary();

        $data = compact('title', 'hidePageTitle', 'anytc', 'topMovies', 'revenueSummary');

        return view('admin/dashboard/index', $data);
    }

    public function revenue_today()
    {
        // Authentication has finished; external requests must not lock navigation
        // in other tabs/AJAX requests sharing the same admin session.
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

        return $this->response->setJSON((new AdRevenueToday())->synchronize());
    }

}
