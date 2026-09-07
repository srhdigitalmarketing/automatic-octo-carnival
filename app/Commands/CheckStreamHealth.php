<?php

namespace App\Commands;

use App\Libraries\StreamResolver;
use App\Models\LinkModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckStreamHealth extends BaseCommand
{
    protected $group = 'Streams';
    protected $name = 'streams:health-check';
    protected $description = 'Checks reported stream links first, then a rotating batch, using provider APIs first and HTTP fallback.';
    protected $usage = 'streams:health-check [--limit 100]';
    protected $options = [
        '--limit' => 'Maximum stream links to check in one run (1-500; default: 100).',
    ];

    public function run(array $params)
    {
        $links = new LinkModel();
        if (! $links->supportsStreamHealthFields()) {
            CLI::error('Stream health fields are unavailable. Run php spark migrate first.');
            return;
        }

        $limit = (int) (CLI::getOption('limit') ?: 100);
        $limit = max(1, min(500, $limit));
        // Work through visitor-reported playback failures first so links that
        // recover can leave the review queue without manual intervention.
        $batch = (new LinkModel())->where('type', 'stream')
            ->where('reports_not_working >', 0)
            ->orderBy('reports_not_working', 'DESC')
            ->orderBy('last_checked_at', 'ASC')
            ->limit($limit)
            ->findAll();

        $remaining = $limit - count($batch);
        if ($remaining > 0) {
            $checkedIds = array_map(static function ($link): int {
                return (int) $link->id;
            }, $batch);

            $rotating = new LinkModel();
            $rotating->where('type', 'stream');
            if ($checkedIds !== []) {
                $rotating->whereNotIn('id', $checkedIds);
            }

            $batch = array_merge($batch, $rotating
                ->orderBy('last_checked_at', 'ASC')
                ->limit($remaining)
                ->findAll());
        }

        $resolver = new StreamResolver($links);
        $healthy = 0;
        $unavailable = 0;
        $autoClearedReports = 0;

        foreach ($batch as $link) {
            if ($resolver->check($link)) {
                $healthy++;
                $autoClearedReports += (int) ($link->reports_not_working ?? 0);
            } else {
                $unavailable++;
            }
        }

        CLI::write(
            'Checked ' . count($batch) . ' stream link(s): ' . $healthy . ' available, ' . $unavailable . ' unavailable, '
            . $autoClearedReports . ' not-working report(s) auto-cleared.',
            $unavailable > 0 ? 'yellow' : 'green'
        );
    }
}
