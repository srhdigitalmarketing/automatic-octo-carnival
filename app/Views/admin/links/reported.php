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

<div class="x_panel link-table-panel">
    <div class="card-box table-responsive">

        <table id="reported-links-datatable" class="table link-operations-table link-operations-table--reported data-list-table" data-source="<?= admin_url('/ajax/tables/reported-links') ?>" style="width:100%">
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


