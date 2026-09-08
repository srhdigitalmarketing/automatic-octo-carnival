<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open('/admin/settings/backup/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">
            <div class="x_title">
                <h2>Database backup</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <p>Pengaturan CDN tersedia di menu CDN.</p>

                <div class="text-right">
                    <?= form_button([
                        'type' => 'submit',
                        'class' => 'btn btn-primary'
                    ], 'update') ?>
                </div>



            </div>
        </div>

        <?= form_close() ?>

    </div>
</div>

<?php $this->endSection() ?>
