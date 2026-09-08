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

<form method="get" id="reported-host-filter" class="reported-host-filter">
    <label for="reported-host">Stream host</label>
    <select id="reported-host" name="host" class="form-control">
        <option value="">All hosts</option>
        <?php foreach ($hosts as $hostname): ?>
        <option value="<?= esc($hostname, 'attr') ?>" <?= $host === $hostname ? 'selected' : '' ?>><?= esc($hostname) ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
    <button type="button" id="bulk-link-fix" class="btn btn-primary" data-url="<?= esc(admin_url('/bulk-link-fix/run'), 'attr') ?>">Bulk Fix Broken Links</button>
</form>
<div id="bulk-fix-progress" class="x_panel" hidden>
    <strong>Bulk Fix Broken Links</strong><p>Memproses seluruh link terhapus/404 pada hostname terpilih, termasuk halaman tabel lainnya. Biarkan halaman ini terbuka.</p>
    <progress id="bulk-fix-bar" max="1" value="0" style="width:100%"></progress>
    <p id="bulk-fix-status" role="status"></p><button type="button" id="bulk-fix-stop" class="btn btn-light">Stop</button>
    <ul id="bulk-fix-log" style="max-height:240px;overflow:auto"></ul>
</div>
<script defer src="<?= site_url('/admin-assets/js/bulk-link-fix.js?v=2') ?>"></script>
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


