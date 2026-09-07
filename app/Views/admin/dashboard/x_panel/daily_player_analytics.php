<div class="x_panel dashboard-daily-analytics-panel">
    <div class="x_title mb-0">
        <h2>Daily Player Analytics <small>Embed activity by day</small></h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><span class="dashboard-period-chip"><i class="fa fa-calendar"></i> 7 hari</span></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content p-0">
        <div class="table-responsive">
            <table class="table table-hover dashboard-daily-analytics-table">
                <thead>
                <tr>
                    <th><i class="fa fa-calendar"></i> Date</th>
                    <th class="text-right"><i class="fa fa-eye"></i> Impressions</th>
                    <th class="text-right"><i class="fa fa-play-circle"></i> Play clicks</th>
                    <th class="text-right"><i class="fa fa-users"></i> Unique visitors</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($dailyPlayerAnalytics['rows'] as $row): ?>
                    <tr>
                        <td><strong><?= esc($row['date']) ?></strong></td>
                        <td class="text-right"><?= number_format($row['impressions']) ?></td>
                        <td class="text-right"><?= number_format($row['play_clicks']) ?></td>
                        <td class="text-right"><?= number_format($row['unique_visitors']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (! $dailyPlayerAnalytics['tracking_ready']): ?>
            <p class="dashboard-chart-notice dashboard-daily-analytics-notice"><i class="fa fa-info-circle"></i> Import tabel analytics atau jalankan migration untuk mulai mencatat impressions dan play clicks.</p>
        <?php endif; ?>
    </div>
</div>
