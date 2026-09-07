<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">
        <?= form_open('/admin/settings/profile/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">

            <div class="x_content">
                <div class="form-group row">
                    <label class="control-label col-md-3">Display Name: </label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'display_name',
                            'class' => 'form-control',
                            'value' => old('display_name', $admin->display_name)
                        ]) ?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="control-label col-md-3">Username: </label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'text',
                            'name' => 'username',
                            'class' => 'form-control',
                            'value' => old('username', $admin->username)
                        ]) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="x_panel">
            <div class="x_title">
                <h2>Change password</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <div class="form-group row">
                    <label class="control-label col-md-3">Current password: </label>
                    <div class="col-md-9">
                        <?= form_password([
                            'name' => 'old_password',
                            'class' => 'form-control'
                        ]) ?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="control-label col-md-3">New password: </label>
                    <div class="col-md-9">
                        <?= form_password([
                            'name' => 'new_password',
                            'class' => 'form-control'
                        ]) ?>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="control-label col-md-3">Confirm new password: </label>
                    <div class="col-md-9">
                        <?= form_password([
                            'name' => 'confirm_password',
                            'class' => 'form-control'
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
