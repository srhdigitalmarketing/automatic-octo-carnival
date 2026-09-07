<div class="x_panel">
    <div class="x_title">
        <h2>General</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">


        <div class="form-group row">
            <label class="control-label col-md-3">Site Title</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'site_title',
                    'class' => 'form-control',
                    'value' => get_config('site_title')
                ]) ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Site Name/ Logo</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'file',
                    'name' => 'logo_file',
                    'class' => 'mb-3',
                ]) ?>
                <img src="<?= site_logo() ?>" height="35" alt="site_logo">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'site_name',
                    'class' => 'form-control',
                    'value' => get_config('site_name')
                ]) ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Site Favicon</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'file',
                    'name' => 'favicon_file',
                    'class' => 'mb-3',
                ]) ?>
                <img src="<?= site_favicon() ?>" height="35" alt="site_logo">
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Items per page in watch history</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'number',
                    'name' => 'watch_history_limit',
                    'class' => 'form-control',
                    'value' => get_config('watch_history_limit'),
                    'min' => 0,
                    'max' => 50
                ]) ?>
                <small> Min: 0 , Max: 50 </small>
            </div>
        </div>

        <div class="form-group row mt-4">
            <label class="control-label col-md-3">Ad Block Detector</label>
            <div class="col-md-9">
                <div class="checkbox">
                    <label>
                        <?= form_checkbox('ad_block_detector','1', get_config('ad_block_detector')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>


        <div class="text-right mb-3">
            <?= form_button([
                'type' => 'submit',
                'class' => 'btn btn-primary'
            ], 'update') ?>
        </div>



    </div>
</div>
