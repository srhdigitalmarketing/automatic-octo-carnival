<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<?= form_open_multipart('/admin/movies/create') ?>
    <div class="row">
        <div class="col-lg-8">
            <?= $this->include('admin/movies/form_x_panels/general.php') ?>
            <?= $this->include('admin/movies/form_x_panels/stream_links.php') ?>
            <?= $this->include('admin/movies/form_x_panels/translations.php') ?>
        </div>
        <div class="col-lg-4">
            <?= $this->include('admin/movies/form_x_panels/publish.php') ?>
            <?= $this->include('admin/movies/form_x_panels/share_links.php') ?>
            <?= $this->include('admin/movies/form_x_panels/banner_image.php') ?>
        </div>
    </div>

    <?= form_hidden('type', 'movie') ?>

<?= form_close() ?>


<?php $this->endSection() ?>
