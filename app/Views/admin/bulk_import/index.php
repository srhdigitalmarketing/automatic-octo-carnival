<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>



    <div class="row">
        <div class="col-lg-8">
            <?= $this->include('admin/bulk_import/form_x_panels/main_form.php') ?>
            <?= $this->include('admin/bulk_import/form_x_panels/success_ids.php') ?>
            <?= $this->include('admin/bulk_import/form_x_panels/exist_ids.php') ?>
            <?= $this->include('admin/bulk_import/form_x_panels/failed_ids.php') ?>
        </div>
        <div class="col-lg-4">
            <?= $this->include('admin/bulk_import/form_x_panels/imported-links.php') ?>
        </div>
    </div>




<?php $this->endSection() ?>
