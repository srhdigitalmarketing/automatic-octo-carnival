<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open_multipart('/admin/settings/general/update', [ 'id' => 'general-settings-form', 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <?= $this->include('/admin/settings/general/form_x_panels/media_files') ?>


        <?= form_close() ?>
        <?php if (!empty($apiSchemaError)): ?>
        <div class="alert alert-warning" role="alert"><?= esc($apiSchemaError) ?></div>
        <?php else: ?>
        <?= $this->include('admin/settings/general/form_x_panels/latest_grab') ?>
        <?= $this->include('admin/settings/general/form_x_panels/auto_grab') ?>
        <?= $this->include('admin/settings/general/form_x_panels/serverdothost_grab') ?>
        <?php endif ?>
        <?= $this->include('/admin/settings/general/form_x_panels/others') ?>

    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= site_url('/admin-assets/js/banner-migration.js?v=20260908-1') ?>"></script>
<script src="<?= site_url('/admin-assets/js/auto-grab.js?v=20260908-1') ?>"></script>
<script src="<?= site_url('/admin-assets/js/latest-grab.js?v=20260908-2') ?>"></script>
<script src="<?= site_url('/admin-assets/js/serverdothost-grab.js?v=20260913-1') ?>"></script>
<?php $this->endSection() ?>
