<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open('/admin/settings/api/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">

            <div class="x_content">

                <div class="form-group row">
                    <label class="control-label col-md-3">API</label>
                    <div class="col-md-9">
                        <div class="checkbox">
                            <label>
                                <?= form_checkbox('dev_api','1', get_config('dev_api')) ?>
                                Enable/ Disable
                            </label>
                        </div>
                        <p class="form-text">This API for administrators to add movies/ links to system via API</p>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">API Key</label>
                    <div class="col-md-9">
                        <?php if(! empty(get_config('dev_apikey'))) :  ?>

                        <?= form_input([
                            'type' => 'text',
                            'name' => 'apikey',
                            'class' => 'form-control',
                            'value' => get_config('dev_apikey'),
                            'disabled' => 'disabled'
                        ]) ?>

                        <div class="mt-1">
                            <a href="<?= admin_url('/settings/api/generate') ?>" >Re Generate API Key</a>
                        </div>

                        <?php else: ?>

                            <a href="<?= admin_url('/settings/api/generate') ?>" class="btn btn-sm btn-info">Generate API Key</a>

                        <?php endif; ?>

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
