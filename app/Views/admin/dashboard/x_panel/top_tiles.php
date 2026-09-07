<div class="dashboard-metric dashboard-live-traffic">
    <div class="live-traffic-card">
        <div class="live-traffic-card__header">
            <div>
                <span class="live-traffic-card__eyebrow">LIVE ACTIVITY</span>
                <h3>Live Traffic</h3>
            </div>
            <span class="live-traffic-card__status <?= $liveTraffic['tracking_ready'] ? '' : 'is-pending' ?>">
                <i class="fa fa-circle"></i> <?= $liveTraffic['tracking_ready'] ? 'Live' : 'Setup required' ?>
            </span>
        </div>
        <div class="live-traffic-card__count js-active-now"><?= number_format($liveTraffic['active_now']) ?></div>
        <p class="live-traffic-card__caption js-live-traffic-caption">
            <?= $liveTraffic['tracking_ready'] ? 'Visitors active in the last 3 minutes.' : 'Import the latest database update to start tracking.' ?>
        </p>
        <div class="live-traffic-card__footer"><i class="fa fa-refresh"></i> Updates every 30 seconds</div>
    </div>
</div>

<div class="dashboard-metric metric-video">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-film"></i></div>
        <div class="count">
            <?= number_format( $anytc->movies->total ) ?>
        </div>
        <h3>Videos</h3>
        <p class="metric-card__context">Total videos in your library</p>
    </div>
</div>
<div class="dashboard-metric metric-revenue">
    <div class="tile-stats metric-card metric-card--revenue">
        <div class="icon"><i class="fa fa-line-chart"></i></div>
        <div class="count js-revenue-total"><?= esc($revenueSummary['display_total']) ?></div>
        <h3>Pendapatan hari ini</h3>
        <p class="js-revenue-caption"><?= esc($revenueSummary['message']) ?></p>
        <small class="js-revenue-updated">
            <?= ! empty($revenueSummary['updated_at']) ? 'Terakhir diperbarui ' . esc(date('H:i', strtotime($revenueSummary['updated_at']))) : '' ?>
        </small>
        <a href="<?= admin_url('/ads/embed_page') ?>">Konfigurasi monetisasi <i class="fa fa-arrow-right"></i></a>
    </div>
</div>
<div class="dashboard-metric metric-coverage">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-trophy"></i></div>
        <div class="count"><span class="<?= $anytc->coverage->color_class ?>">
                        <?= number_format( $anytc->coverage->value ) ?>
                    </span>%</div>
        <h3>Coverage</h3>
    </div>
</div>
<div class="dashboard-metric metric-views">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-eye"></i></div>
        <div class="count">
            <?= number_format( $anytc->movies->views ) ?>
        </div>
        <h3>Views</h3>
    </div>
</div>
<div class="dashboard-metric metric-links">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-link"></i></div>
        <div class="count">
            <?= number_format( $anytc->links->total ) ?>
        </div>
        <h3>Links</h3>
    </div>
</div>
<div class="dashboard-metric metric-requests">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-exchange"></i></div>
        <div class="count">
            <?= number_format( $anytc->links_requests->total ) ?>
        </div>
        <h3>Requests <small>to links</small> </h3>
    </div>
</div>
<div class="dashboard-metric metric-reported">
    <div class="tile-stats metric-card">
        <div class="icon"><i class="fa fa-unlink"></i></div>
        <div class="count red">
            <?= number_format( $anytc->reported_links->total ) ?>
        </div>
        <h3>Reported <small>links</small></h3>
    </div>
</div>
