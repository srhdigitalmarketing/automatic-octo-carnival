<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="x_panel">
    <div class="card-box table-responsive">

        <table id="series-list-datatable" class="table text-center table-striped table-bordered data-list-table" style="width:100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>Poster</th>
                <th>Name</th>
                <th>IMDB Id</th>
                <th>TMDB Id</th>
                <th>Year</th>
                <th>Seasons</th>
                <th>Total Episodes</th>
                <th>Pending Episodes</th>
                <th>Completed Episodes</th>
                <th>Completion</th>
                <th>Is Done</th>
                <th>Created At</th>
                <th>Updated At</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>


            <tbody>

            <?php foreach ($series as $show) : ?>
                <tr>
                    <td> <?= $show->id ?> </td>
                    <td style="width: 75px">
                        <img src=" <?= poster_uri( $show->poster ) ?> " height="75" alt="poster">
                    </td>
                    <td> <b> <?= esc( $show->title ) ?> </b> </td>
                    <td> <a href="#"> <?= esc( $show->imdb_id ) ?> </a> </td>
                    <td> <a href="#"> <?= esc( $show->tmdb_id ) ?> </a> </td>
                    <td> <?= $show->year ?> </td>
                    <td> <?= $show->total_seasons ?> </td>
                    <td> <?= $show->total_episodes ?> </td>
                    <td> <?= $show->pending_episodes ?> </td>
                    <td> <?= $show->completed_episodes ?> </td>
                    <td> <?= $show->completion_rate ?>% </td>
                    <td>
                        <?php if($show->is_completed): ?>
                            <i class="fa fa-check-square"></i>
                        <?php else: ?>
                        --
                        <?php endif; ?>
                    </td>
                    <td> <?= format_date_time( $show->created_at ) ?> </td>
                    <td> <?= format_date_time( $show->updated_at ) ?> </td>
                    <td> <?= $show->status ?> </td>
                    <td>
                        <a href="<?= admin_url("/series/edit/{$show->id}") ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="javascript:void(0)"  data-url="<?= admin_url("/series/delete/{$show->id}") ?>" class="btn btn-sm btn-danger del-item">Del</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts'); ?>

<!-- Datatables -->
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.4/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.colVis.min.js"></script>

<?php $this->endSection(); ?>
