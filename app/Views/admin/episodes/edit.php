<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<?php if(! empty( $series )): ?>

    <?= form_open_multipart("/admin/episodes/save/{$movie->id}") ?>

    <div class="row">
        <div class="col-lg-8">
            <?= $this->include('admin/episodes/form_x_panels/general.php') ?>
            <?= $this->include('admin/movies/form_x_panels/general.php') ?>
            <?= $this->include('admin/movies/form_x_panels/stream_links.php') ?>
            <?= $this->include('admin/movies/form_x_panels/translations.php') ?>
        </div>
        <div class="col-lg-4">
            <?= $this->include('admin/movies/form_x_panels/publish.php') ?>
            <?= $this->include('admin/movies/form_x_panels/quality.php') ?>
            <?= $this->include('admin/movies/form_x_panels/categories.php') ?>
            <?= $this->include('admin/movies/form_x_panels/seo.php') ?>
            <?= $this->include('admin/movies/form_x_panels/banner_image.php') ?>
        </div>
    </div>

    <?= form_hidden('type', 'episode') ?>

    <?= form_close() ?>

<?php endif; ?>

<?php $this->endSection() ?>
