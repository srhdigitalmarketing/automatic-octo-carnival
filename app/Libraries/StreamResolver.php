<?php

namespace App\Libraries;

use App\Entities\Link;
use App\Models\LinkModel;
use Config\UpnShare;

/**
 * Chooses an available stream host, remembers the outcome and rotates the next
 * request to a different healthy link. All probing occurs server-side, so the
 * browser never needs cross-origin access to a host.
 */
class StreamResolver
{
    /** @var LinkModel */
    private $links;
    /** @var UpnShare */
    private $config;
    private $hostHealth;

    public function __construct(?LinkModel $links = null)
    {
        $this->links = $links ?: new LinkModel();
        $this->config = config('UpnShare');
        $this->hostHealth = new VideoHostHealth($this->links);
    }

    public function resolve(int $movieId, ?int $preferredId = null, array $excludedIds = []): ?Link
    {
        $excludedIds = array_values(array_filter(array_map('intval', $excludedIds)));
        $candidates = $this->links->findByMovieId($movieId, 'stream', false);

        // The original database does not contain the stream-health columns.
        // Fall back to its normal link ordering instead of making playback fail.
        if (! $this->links->supportsStreamHealthFields()) {
            foreach ($candidates as $link) {
                if (! in_array((int) $link->id, $excludedIds, true)) {
                    return $link;
                }
            }

            return null;
        }

        if (! empty($preferredId) && ! in_array($preferredId, $excludedIds, true)) {
            usort($candidates, function (Link $left, Link $right) use ($preferredId) {
                return ((int)$left->id === $preferredId ? 0 : 1) <=> ((int)$right->id === $preferredId ? 0 : 1);
            });
        }

        foreach ($candidates as $link) {
            if (in_array((int) $link->id, $excludedIds, true)) {
                continue;
            }

            if (! $this->isHealthy($link)) {
                continue;
            }

            if ($this->recordSuccess($link)) { return $link; }
        }

        return null;
    }

    public function recordPlayerFailure(int $linkId, string $reason = 'Player did not load'): void
    {
        if (! $this->links->supportsStreamHealthFields()) {
            return;
        }

        $link = $this->links->getLink($linkId);
        if ($link === null || $link->type !== 'stream') {
            return;
        }

        if (!RegisteredStreamHost::matches((string)$link->link)) { return; }
        if (in_array($link->provider_status, ['deleted','error','processing'], true) || ($link->provider_status === 'unknown' && (bool)$link->is_broken)) { return; }
        $count = (int) $link->failure_count + 1;
        $this->links->protect(false)->update($linkId, [
            'failure_count' => $count,
            'last_checked_at' => date('Y-m-d H:i:s'),
            'last_failure_at' => date('Y-m-d H:i:s'),
            'last_error' => substr($reason, 0, 255),
            'is_broken' => $count >= $this->config->failureThreshold ? 1 : 0,
        ]);
    }

    /** Iframe timeout must not query optional API configuration on player requests. */
    public function frameLoadTimeout(Link $link): int
    {
        return 15000;
    }

    /** Removed providers cannot rewrite or request a new delivery URL. */
    public function deliveryUrl(Link $link, string $endUserIp): string
    {
        return (string) $link->link;
    }

    /** Run one explicit availability check, used by the scheduled health job. */
    public function check(Link $link): bool
    {
        if (! $this->links->supportsStreamHealthFields() || $link->type !== 'stream') {
            return false;
        }

        if (!RegisteredStreamHost::matches((string)$link->link)) { return false; }
        if (! $this->isHealthy($link, true)) {
            return false;
        }

        return $this->recordSuccess($link, false);
    }

    private function isHealthy(Link $link, bool $force = false): bool
    {
        if (!RegisteredStreamHost::matches((string)$link->link)) { return !$force; }
        // Provider deletion overrides cached HTTP success and host priority.
        // Explicit cron checks may still recover a restored file.
        if (!$force && in_array($link->provider_status, ['deleted','error','processing'], true)) { return false; }
        if (!$force && $link->provider_status === 'unknown' && (bool)$link->is_broken) { return false; }
        $lastCheck = $link->last_checked_at ? strtotime($link->last_checked_at) : 0;
        if (! $force && $lastCheck && (time() - $lastCheck) < $this->config->healthCacheSeconds) {
            return ! (bool) $link->is_broken && empty($link->last_error);
        }

        // Viewer requests must never wait for DNS, HTTP or provider APIs. A stale
        // or unchecked candidate can be tried by the browser, which rotates on
        // failure; authoritative checks remain in cron and the admin Check action.
        if (!$force) { return !(bool) $link->is_broken; }
        $providerStatus = $force ? $this->hostHealth->check($link) : null;
        if ($providerStatus !== null) {
            if (in_array($providerStatus['status'], ['available','reachable'], true)) { return true; }
            if (in_array($providerStatus['status'], ['deleted','error','processing'], true)) { return false; }
            // HTTP 200 can be the provider's 'video deleted' error page.
            // An inconclusive API check must never be upgraded to success by HTTP.
            return false;
        }
        // No generic HTTP fallback: unconfigured hosts must never be probed.
        return false;
    }

    private function recordSuccess(Link $link, bool $markServed = true): bool
    {
        if (! $this->links->supportsStreamHealthFields()) {
            return true;
        }

        if ($markServed && !RegisteredStreamHost::matches((string)$link->link)) {
            // Ignore historical automatic flags; do not claim the file was checked.
            try { return (bool)$this->links->protect(false)->update($link->id, ['last_served_at'=>date('Y-m-d H:i:s')]); }
            finally { $this->links->protect(true); }
        }
        $now = date('Y-m-d H:i:s');
        $data = [
            'failure_count' => 0,
            'is_broken' => 0,
            'last_checked_at' => $now,
            'last_success_at' => $now,
            'last_error' => null,
        ];

        // Scheduled checks must not affect the round-robin order used for
        // real viewers. Only a selected playback link is considered served.
        if ($markServed) {
            // Selecting a URL is not proof that the video is healthy. Keep check
            // timestamps and reports intact until a real health check completes.
            $data = ['last_served_at' => $now];
            $this->links->where('is_broken', 0);
        }

        // A cron check can mark a link deleted after candidates were loaded.
        // Never overwrite that authoritative state with a cached player success.
        if ($this->links->supportsProviderStatus()) {
            $this->links->groupStart()->where('provider_status', null)
                ->orWhereNotIn('provider_status', ['deleted','error','processing'])->groupEnd();
            $this->links->groupStart()->where('provider_status', null)
                ->orWhere('provider_status !=', 'unknown')->orWhere('is_broken', 0)->groupEnd();
        }
        $saved = $this->links->protect(false)->update($link->id, $data);
        $this->links->protect(true);
        if (!$saved) { return false; }
        $current = $this->links->getLink((int)$link->id);
        if ($current === null || (bool)$current->is_broken
            || in_array($current->provider_status, ['deleted','error','processing'], true)) { return false; }


        // A successful provider/API or HTTP check confirms the visitor report
        // is no longer actionable. Keep "wrong video" reports for a person to
        // review because availability alone cannot validate video contents.
        if (!$markServed && (int) ($link->reports_not_working ?? 0) > 0) {
            $this->links->clearNotWorkingReports((int) $link->id);
        }
        return true;
    }

}
