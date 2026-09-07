<div class="x_panel">
    <div class="x_title">
        <h2>Google Invisible Captcha </h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <p>This gcaptcha use in <b>embed</b> page to protect stream links </p>
        <div class="form-group row">
            <label class="control-label col-md-3">G Captcha</label>
            <div class="col-md-9">
                <div class="checkbox">
                    <label>
                        <?= form_checkbox('is_stream_gcaptcha_enabled','', get_config('is_stream_gcaptcha_enabled')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Site key </label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'gcaptcha_site_key',
                    'class' => 'form-control',
                    'value' => get_config('gcaptcha_site_key')
                ]) ?>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Secret key</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'gcaptcha_secret_key',
                    'class' => 'form-control',
                    'value' => get_config('gcaptcha_secret_key')
                ]) ?>
            </div>
        </div>




    </div>
</div>