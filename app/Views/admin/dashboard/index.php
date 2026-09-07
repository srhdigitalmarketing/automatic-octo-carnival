<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<div class="dashboard-page">
    <div class="dashboard-rizz-welcome">
        <div class="dashboard-rizz-welcome__copy">
            <span class="dashboard-eyebrow">DASHBOARD OVERVIEW</span>
            <h2>Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, welcome back.</h2>
            <p>Keep an eye on your video library, audience activity, and the actions that need attention.</p>
        </div>
        <div class="dashboard-rizz-welcome__actions">
            <span class="dashboard-rizz-date"><i class="fa fa-calendar"></i> <?= date('D, d M') ?></span>
            <a class="dashboard-primary-action" href="<?= admin_url('/movies/new') ?>">
                <i class="fa fa-plus"></i> Add Video
            </a>
        </div>
    </div>

    <div class="dashboard-rizz-spotlight">
        <span class="dashboard-rizz-spotlight__icon"><i class="fa fa-play"></i></span>
        <div>
            <span>VIDEO LIBRARY</span>
            <strong>Manage your streaming catalog from one clear workspace.</strong>
        </div>
        <a href="<?= admin_url('/movies') ?>">Open library <i class="fa fa-arrow-right"></i></a>
    </div>

    <div class="dashboard-metrics-grid">
        <?= $this->include('admin/dashboard/x_panel/top_tiles') ?>
    </div>

    <div class="dashboard-visitor-grid">
        <?= $this->include('admin/dashboard/x_panel/charts/visitor_statistics') ?>
    </div>

    <div class="dashboard-tables-grid">
        <div class="dashboard-table-cell">
            <?= $this->include('admin/dashboard/x_panel/most_viewed_movies') ?>
        </div>
        <div class="dashboard-table-cell">
            <?= $this->include('admin/dashboard/x_panel/daily_player_analytics') ?>
        </div>
    </div>
</div>


<?php $this->endSection() ?>



<?php $this->section('scripts'); ?>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<?= $this->include('admin/dashboard/charts_js') ?>

<script>
(function () {
    var endpoint = <?= json_encode(admin_url('/dashboard/live-traffic')) ?>;
    var count = document.querySelector('.js-active-now');
    var caption = document.querySelector('.js-live-traffic-caption');
    if (! count || ! window.fetch) return;

    function refreshLiveTraffic() {
        fetch(endpoint, {credentials: 'same-origin'})
            .then(function (response) { return response.ok ? response.json() : null; })
            .then(function (data) {
                if (! data) return;
                count.textContent = Number(data.active_now || 0).toLocaleString();
                if (caption && data.tracking_ready) {
                    caption.textContent = 'Visitors using the embed player in the last 3 minutes.';
                }
            })
            .catch(function () {});
    }

    window.setInterval(refreshLiveTraffic, 30000);
})();

(function () {
    var endpoint = <?= json_encode(admin_url('/dashboard/revenue-today')) ?>;
    var total = document.querySelector('.js-revenue-total');
    var caption = document.querySelector('.js-revenue-caption');
    var updated = document.querySelector('.js-revenue-updated');
    if (! total || ! window.fetch) return;

    fetch(endpoint, {credentials: 'same-origin'})
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) {
            if (! data) return;
            total.textContent = data.display_total || '—';
            if (caption) caption.textContent = data.message || '';
            if (updated && data.updated_at) {
                var time = new Date(data.updated_at);
                updated.textContent = 'Terakhir diperbarui ' + time.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            }
        })
        .catch(function () {
            if (caption && caption.textContent === 'Memuat pendapatan hari ini…') {
                caption.textContent = 'Pendapatan belum dapat dimuat. Coba segarkan halaman.';
            }
        });
})();
</script>

<?php $this->endSection() ?>
