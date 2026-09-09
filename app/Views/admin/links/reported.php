<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="link-workspace-header link-workspace-header--reported">
    <div>
        <span class="link-workspace-header__eyebrow">LINK HEALTH</span>
        <h4>Reported links</h4>
        <p>Reported stream links are rechecked automatically; healthy links clear “Not working” reports without manual review.</p>
    </div>
    <span class="reported-links-summary"><i class="fa fa-exclamation-circle"></i> <span id="reported-review-count"><?= number_format($linksCount) ?></span> need review</span>
</div>

<form method="get" id="reported-host-filter" class="reported-host-filter flex-wrap">
    <label for="reported-host">Stream host</label>
    <select id="reported-host" name="host" class="form-control">
        <option value="">All hosts</option>
        <?php foreach ($hosts as $hostname): ?>
        <option value="<?= esc($hostname, 'attr') ?>" <?= $host === $hostname ? 'selected' : '' ?>><?= esc($hostname) ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <button type="button" id="bulk-link-fix" class="btn btn-primary" data-url="<?= esc(admin_url('/bulk-link-fix/run'), 'attr') ?>">Bulk Fix Broken Links</button>
    <button type="button" id="bulk-report-clear" class="btn btn-outline-warning" data-url="<?= esc(admin_url('/reported-link-tools/run'), 'attr') ?>">Bulk Clear Reports</button>
    <button type="button" id="export-error-links" class="btn btn-outline-primary" data-url="<?= esc(admin_url('/reported-link-tools/run'), 'attr') ?>">Export Error Links (CSV)</button>
</form>
<div id="reported-tools-progress" class="x_panel p-3" hidden>
    <p class="small text-muted">Mengikuti pilihan host, mencakup semua halaman tabel. Clear hanya menghapus laporan; tidak memperbaiki atau menghapus link. Export berisi link yang dilaporkan, termasuk wrong video. Pencarian tabel tidak membatasi kedua aksi ini.</p>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span id="reported-tools-status" role="status" aria-live="polite"></span>
        <button type="button" id="reported-tools-stop" class="btn btn-outline-secondary btn-sm">Berhenti</button>
    </div>
    <div class="progress"><div id="reported-tools-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-label="Progres laporan" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div></div>
</div>
<script defer src="<?= site_url('/admin-assets/js/reported-link-tools.js?v=1') ?>"></script>
<div id="bulk-fix-progress" class="x_panel p-4" hidden>
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <div class="d-flex align-items-center mb-2">
            <span id="bulk-fix-spinner" class="spinner-border spinner-border-sm text-primary mr-3" aria-hidden="true" hidden></span>
            <div><h5 class="mb-1 font-weight-bold">Bulk Fix Broken Links</h5><small class="text-muted">Semua link terhapus/404 pada hostname terpilih. Biarkan halaman tetap terbuka.</small></div>
        </div>
        <button type="button" id="bulk-fix-stop" class="btn btn-outline-secondary btn-sm mb-2" hidden>Hentikan proses</button>
    </div>
    <div class="progress mb-3" style="height:10px;border-radius:8px">
        <div id="bulk-fix-bar" class="progress-bar bg-primary" role="progressbar" aria-label="Progres perbaikan link" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%"></div>
    </div>
    <p id="bulk-fix-status" class="small mb-3" role="status" aria-live="polite"></p>
    <ul id="bulk-fix-log" class="list-group list-group-flush mb-0" style="max-height:240px;overflow:auto"></ul>
</div>
<script defer src="<?= site_url('/admin-assets/js/bulk-link-fix.js?v=3') ?>"></script>
<div class="x_panel link-table-panel">
    <div class="card-box table-responsive">

        <table id="reported-links-datatable" class="table link-operations-table link-operations-table--reported data-list-table" data-source="<?= esc(admin_url('/ajax/tables/reported-links') . '?host=' . rawurlencode($host), 'attr') ?>" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Link</th>
                <th>Req.</th>
                <th>Reason</th>
                <th>Reports</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>


