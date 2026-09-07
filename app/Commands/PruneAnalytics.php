<?php
namespace App\Commands;

use App\Libraries\MysqlAnalytics;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class PruneAnalytics extends BaseCommand
{
    protected $group = 'Analytics';
    protected $name = 'analytics:prune';
    protected $description = 'Delete analytics older than the latest 30 calendar days and expired live visitors.';

    public function run(array $params)
    {
        try {
            $count = (new MysqlAnalytics())->prune(db_connect());
            CLI::write("Analytics cleanup: {$count} row(s) deleted.", 'green');
        } catch (\Throwable $error) {
            CLI::error($error->getMessage());
            exit(1);
        }
    }
}
