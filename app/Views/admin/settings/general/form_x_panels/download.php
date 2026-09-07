<div class="x_panel">
    <div class="x_title">
        <h2>Download Links</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <label class="control-label col-md-3">Count Down Timer</label>
            <div class="col-md-9">
                <div class="checkbox  mt-2">
                    <label>
                        <?= form_checkbox('is_count_down_timer','', get_config('is_count_down_timer')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>


        <div class="form-group row">
            <label class="control-label col-md-3">Count Down time </label>
            <div class="col-md-9">

                <div class="input-group">
                    <?= form_input([
                        'type' => 'number',
                        'name' => 'dl_link_waiting_time',
                        'class' => 'form-control',
                        'min' => 3,
                        'value' => get_config('dl_link_waiting_time')
                    ]) ?>
                    <div class="input-group-append">
                        <span class="input-group-text">Seconds</span>
                    </div>

                </div>
                <small>Min: 3 seconds</small>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Captcha</label>
            <div class="col-md-9">
                <div class="checkbox mt-2">
                    <label>
                        <?= form_checkbox('is_download_link_captcha','', get_config('is_download_link_captcha')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>





    </div>
</div>