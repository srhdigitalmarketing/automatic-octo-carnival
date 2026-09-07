<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<div class="row">
    <div class="col-lg-10 col-xxl-7">

        <?= $this->include('admin/third_party_apis/x_panels/usage.php') ?>

        <?= form_open(admin_url("/third-party-apis/update?id={$tpAPI->id}")) ?>
        <?= $this->include('admin/third_party_apis/x_panels/main_form.php') ?>
        <?= form_close() ?>

    </div>

</div>


<?php $this->endSection() ?>
