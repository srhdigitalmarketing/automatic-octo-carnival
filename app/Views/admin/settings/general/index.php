<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open_multipart('/admin/settings/general/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <?= $this->include('/admin/settings/general/form_x_panels/media_files') ?>
        <?= $this->include('/admin/settings/general/form_x_panels/others') ?>


        <?= form_close() ?>

    </div>
</div>

<?php $this->endSection() ?>
