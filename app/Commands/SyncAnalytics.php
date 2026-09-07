<?php
namespace App\Commands;

use App\Libraries\RedisAnalytics;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncAnalytics extends BaseCommand
{
    protected $group = 'Analytics';
    protected $name = 'analytics:sync';
    protected $description = 'Save retained Redis daily analytics snapshots to MySQL.';

    public function run(array $params)
    {
        try {
            $count = (new RedisAnalytics())->sync(db_connect());
            CLI::write("Analytics synchronized: {$count} day(s).", 'green');
        } catch (\Throwable $error) {
            CLI::error($error->getMessage());
            exit(1);
        }
    }
}
