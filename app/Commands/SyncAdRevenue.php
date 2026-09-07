<?php

namespace App\Commands;

use App\Libraries\AdRevenueToday;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncAdRevenue extends BaseCommand
{
    protected $group = 'Revenue';
    protected $name = 'revenue:sync';
    protected $description = 'Synchronizes today\'s revenue and eCPM from active ad networks.';

    public function run(array $params)
    {
        $summary = (new AdRevenueToday())->synchronize(true);
        CLI::write(
            $summary['display_total'] . ' from ' . $summary['synchronized_units'] . ' network(s); status: ' . $summary['status'],
            $summary['status'] === 'ready' ? 'green' : 'yellow'
        );
    }
}
