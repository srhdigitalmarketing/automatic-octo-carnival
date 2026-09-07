<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open('/admin/settings/email/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">

            <div class="x_content">

                <div class="form-group row">
                    <label class="control-label col-md-3">Email Address</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'email',
                            'name' => 'email',
                            'class' => 'form-control',
                            'value' => get_config('email_address')
                        ]) ?>

                        <?php if(! empty( get_config('email_address') )): ?>
                        <div class="mt-1">
                            <a href="<?= admin_url('/settings/email/test') ?>" target="_blank">Send Test Mail</a>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>



            </div>
        </div>

        <div class="x_panel">
            <div class="x_title">
                <h2>SMTP Settings</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="form-group row">
                    <label class="control-label col-md-3">SMTP Host</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'smtp[host]',
                            'class' => 'form-control',
                            'value' => smtp_config('host')
                        ]) ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">SMTP User</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'smtp[user]',
                            'class' => 'form-control',
                            'value' => smtp_config('user')
                        ]) ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">SMTP Pass</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'smtp[pass]',
                            'class' => 'form-control',
                            'value' => smtp_config('pass')
                        ]) ?>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">SMTP Port</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'smtp[port]',
                            'class' => 'form-control',
                            'value' => smtp_config('port')
                        ]) ?>
                    </div>
                </div>


            </div>
        </div>

        <div class="text-right">
            <?= form_button([
                'type' => 'submit',
                'class' => 'btn btn-primary'
            ], 'update') ?>
        </div>

        <?= form_close() ?>

    </div>
</div>

<?php $this->endSection() ?>
