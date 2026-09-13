<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>


<div class="row">
    <div class="col-lg-10 col-xxl-7">

        <?= $this->include('admin/third_party_apis/x_panels/usage.php') ?>

        <?= form_open(admin_url("/third-party-apis/update?id={$tpAPI->id}")) ?>
        <?= $this->include('admin/third_party_apis/x_panels/main_form.php') ?>
        <?= form_close() ?>
        <?php if (\App\Libraries\HostFileChecks::supported($tpAPI)): ?>
        <div class="x_panel"><div class="x_content">
            <h3>Cek file host</h3>
            <p>Atur pemeriksaan admin, cron, Bulk Fix dan laporan timeout otomatis. Perubahan sakelar langsung disimpan. Katalog dan Auto Grab tetap dapat digunakan.</p>
            <?= view('admin/third_party_apis/x_panels/file_check_control', ['api'=>$tpAPI]) ?>
        </div></div>
        <?php endif ?>

    </div>

</div>


<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= site_url('/admin-assets/js/host-file-checks.js?v=20260913-1') ?>"></script>
<?php $this->endSection() ?>
