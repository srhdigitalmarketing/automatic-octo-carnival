<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open_multipart('/admin/settings/site/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>


        <?= $this->include('admin/settings/site/form_x_panels/general.php') ?>
        <?= $this->include('admin/settings/site/form_x_panels/homepage.php') ?>
        <?= $this->include('admin/settings/site/form_x_panels/page_url.php') ?>

        <?= form_close() ?>
        <?= form_open(admin_url('/settings/homepage/update'), ['id'=>'homepage-settings-form']) ?><?= form_close() ?>
        <?= $this->include('admin/settings/site/form_x_panels/histats') ?>

    </div>
</div>


<?php $this->endSection() ?>
