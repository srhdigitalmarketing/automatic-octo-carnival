<?php
namespace App\Commands;

use App\Libraries\AnalyticsRetention;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneAnalytics extends BaseCommand
{
    protected $group = 'Analytics';
    protected $name = 'analytics:prune';
    protected $description = 'Delete daily analytics outside the latest 30 calendar days.';

    public function run(array $params)
    {
        try {
            $rows = (new AnalyticsRetention())->prune(db_connect());
            foreach ($rows as $table => $result) {
                CLI::write($table . ': ' . $result['deleted'] . ' rows deleted.' . ($result['more'] ? ' Batch limit reached; run again to continue.' : ''));
            }
            CLI::write('Cleanup complete. Retained dates start at ' . AnalyticsRetention::cutoff(), 'green');
        } catch (\Throwable $error) {
            CLI::error($error->getMessage());
            exit(1);
        }
    }
}
