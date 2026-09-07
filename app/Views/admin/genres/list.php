<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<div class="row">
    <div class="col-lg-8">
        <?= $this->include('admin/genres/form_x_panels/list.php') ?>


    </div>
    <div class="col-lg-4">
        <?= $this->include('admin/genres/form_x_panels/new.php') ?>
    </div>
</div>



<?php $this->endSection() ?>
