<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<?php
$activeFilter = $filter ?: 'all';
$typeLabels = [
    'stream' => 'Stream',
    'direct_download' => 'Direct',
    'torrent_download' => 'Torrent',
];
?>

<div class="link-workspace-header">
    <div>
        <span class="link-workspace-header__eyebrow">LINK OPERATIONS</span>
        <h4>All links</h4>
        <p>Review destinations, request volume, and recent changes from one organized table.</p>
    </div>
    <nav class="link-filter-pills" aria-label="Link type filter">
        <?php foreach (['all' => 'All', 'stream' => 'Stream', 'direct_download' => 'Direct', 'torrent_download' => 'Torrent'] as $value => $label): ?>
            <a class="<?= $activeFilter === $value ? 'is-active' : '' ?>" href="<?= admin_url('/links') ?><?= $value === 'all' ? '' : '?filter=' . $value ?>">
                <?= esc($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<div class="x_panel link-table-panel">
    <div class="card-box table-responsive">

        <table id="links-list-datatable" class="table link-operations-table data-list-table" data-source="<?= admin_url('/ajax/tables/links') ?>" data-filter="<?= esc($activeFilter) ?>" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Link</th>
                <th>Type</th>
                <th>Requests</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Actions</th>
            </tr>
            </thead>

            <tbody></tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>


<?php $this->section('scripts'); ?>

    <!-- Datatables -->
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.colVis.min.js"></script>

<?php $this->endSection(); ?>
