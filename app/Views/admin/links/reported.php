<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="link-workspace-header link-workspace-header--reported">
    <div>
        <span class="link-workspace-header__eyebrow">LINK HEALTH</span>
        <h4>Reported links</h4>
        <p>Reported stream links are rechecked automatically; healthy links clear “Not working” reports without manual review.</p>
    </div>
    <span class="reported-links-summary"><i class="fa fa-exclamation-circle"></i> <?= number_format($linksCount) ?> need review</span>
</div>

<form method="get" class="form-inline" style="margin-bottom:15px">
    <label for="reported-host">Stream host:&nbsp;</label>
    <select id="reported-host" name="host" class="form-control">
        <option value="">All hosts</option>
        <?php foreach ($hosts as $hostname): ?>
        <option value="<?= esc($hostname, 'attr') ?>" <?= $host === $hostname ? 'selected' : '' ?>><?= esc($hostname) ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit" class="btn btn-primary">Filter</button>
</form>
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


