<?php
namespace App\Commands;

use App\Libraries\RegisteredStreamHost;
use App\Models\LinkModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ActivateUnregisteredStreams extends BaseCommand
{
    protected $group = 'Streams';
    protected $name = 'streams:activate-unregistered';
    protected $description = 'Activate streams excluded from health checks, including hosts with file checking disabled, without probing URLs.';

    public function run(array $params)
    {
        if (!RegisteredStreamHost::available()) {
            CLI::error('Konfigurasi host API tidak dapat dibaca. Status link tidak diubah.');
            return;
        }
        $model = new LinkModel(); $last = 0; $count = 0;
        $top = (new LinkModel())->selectMax('id')->first();
        $max = (int)($top->id ?? 0);
        do {
            $batch = (new LinkModel())->where('type','stream')->where('id >',$last)
                ->where('id <=',$max)->orderBy('id','ASC')->findAll(250);
            foreach ($batch as $link) {
                $last = (int)$link->id;
                if ($model->activateUnregisteredStream($link)) $count++;
            }
        } while (count($batch) === 250);
        CLI::write($count.' link yang dikecualikan dari pengecekan diaktifkan. URL, konten dan laporan pengguna tetap disimpan.', 'green');
    }
}
