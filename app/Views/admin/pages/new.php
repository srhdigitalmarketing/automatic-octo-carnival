<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<?= form_open('/admin/pages/create') ?>

<div class="row">
    <div class="col-lg-8">

        <?= $this->include('admin/pages/form_x_panels/general.php') ?>
        <?= $this->include('admin/pages/form_x_panels/translations.php') ?>


    </div>
    <div class="col-lg-4">

        <?= $this->include('admin/pages/form_x_panels/publish.php') ?>
        <?= $this->include('admin/pages/form_x_panels/seo.php') ?>


    </div>
</div>

<?php $this->endSection() ?>

