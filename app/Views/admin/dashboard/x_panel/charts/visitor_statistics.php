<section class="dashboard-visitor-panel dashboard-visitor-panel--latest" aria-labelledby="latest-statistics-title">
    <header class="dashboard-visitor-panel__header">
        <div>
            <span class="dashboard-eyebrow">AUDIENCE OVERVIEW</span>
            <h2 id="latest-statistics-title">Audience latest statistic</h2>
            <p>Estimasi pengunjung unik browser selama 30 hari terakhir.</p>
        </div>
        <span class="dashboard-period-chip"><i class="fa fa-calendar"></i> 30 hari</span>
    </header>
    <div class="dashboard-visitor-total">
        <strong><?= $visitorStats['tracking_ready'] ? number_format($visitorStats['total']) : '&mdash;' ?></strong>
        <span>estimasi pengunjung unik</span>
    </div>
    <div id="visitor_statistics_chart" class="dashboard-visitor-chart" aria-label="Grafik pengunjung 30 hari terakhir"></div>
    <?php if (! $visitorStats['tracking_ready']): ?>
        <p class="dashboard-chart-notice"><i class="fa fa-info-circle"></i> <?= esc($visitorStats['notice']) ?></p>
    <?php endif; ?>
</section>

<section class="dashboard-visitor-panel dashboard-visitor-panel--platform" aria-labelledby="platform-title">
    <header class="dashboard-visitor-panel__header">
        <div>
            <span class="dashboard-eyebrow">DEVICES</span>
            <h2 id="platform-title">By platform</h2>
            <p>Estimasi unik per perangkat selama 30 hari; kategori dapat tumpang tindih.</p>
        </div>
        <span class="dashboard-period-chip"><i class="fa fa-mobile"></i> Platform</span>
    </header>
    <div id="visitor_platform_chart" class="dashboard-platform-chart" aria-label="Grafik platform desktop dan mobile"></div>
    <div class="dashboard-platform-legend">
        <span><i class="fa fa-desktop"></i> Desktop <b><?= $visitorStats['tracking_ready'] ? number_format($visitorStats['platforms']['desktop']) : '&mdash;' ?></b></span>
        <span><i class="fa fa-mobile"></i> Mobile <b><?= $visitorStats['tracking_ready'] ? number_format($visitorStats['platforms']['mobile']) : '&mdash;' ?></b></span>
        <span><i class="fa fa-tablet"></i> Tablet <b><?= $visitorStats['tracking_ready'] ? number_format($visitorStats['platforms']['tablet']) : '&mdash;' ?></b></span>
        <span>Lainnya <b><?= $visitorStats['tracking_ready'] ? number_format($visitorStats['platforms']['other']) : '&mdash;' ?></b></span>
    </div>
</section>
