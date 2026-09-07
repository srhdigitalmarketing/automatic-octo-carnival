<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="group-selection d-none ve-results--filter">
    <label class="control-label" for="first-name">Filter:</label>
    <?= form_dropdown([
            'class' => 'form-control',
            'onchange' => 'filter_movie_results(this.value)',
            'options' => [
                    'all' => 'All',
                    'with_st_links' => 'Have Stream Links',
                    'without_st_links' => 'Haven\'t Stream Links',
                    'good_health_servers' => 'Good Health Server',
                    'bad_health_network' => 'Bad Health Network'
            ],
            'selected' => $filter ?? ''
    ]) ?>
</div>

<div class="x_panel">
    <div class="card-box table-responsive">

        <table id="movies-list-datatable" class="table table-hover data-list-table" data-source="<?= admin_url('/ajax/tables/videos') ?>" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Video ID</th>
                <th>Server</th>
                <th>Added on</th>
                <th>Last updated</th>
                <th>Views</th>
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
