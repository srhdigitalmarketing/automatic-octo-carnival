<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<?= form_open_multipart('/admin/series/create') ?>
<div class="row">
    <div class="col-lg-8">
        <?= $this->include('admin/series/form_x_panels/general.php') ?>
        <?= $this->include('admin/series/form_x_panels/meta_info.php') ?>
    </div>
    <div class="col-lg-4">
        <?= $this->include('admin/series/form_x_panels/publish.php') ?>
        <?= $this->include('admin/series/form_x_panels/autoload.php') ?>
        <?= $this->include('admin/series/form_x_panels/categories.php') ?>
        <?= $this->include('admin/series/form_x_panels/poster_image.php') ?>
        <?= $this->include('admin/series/form_x_panels/banner_image.php') ?>

    </div>
</div>

<?= form_close() ?>

<?php $this->endSection() ?>
