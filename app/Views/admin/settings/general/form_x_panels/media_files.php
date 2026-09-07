<div class="x_panel">
    <div class="x_title">
        <h2>Media Files <small>( Banners )</small> </h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <label class="control-label col-md-3">Saving Method</label>
            <div class="col-md-9">
                <div class="checkbox d-inline-block mt-2 mr-3">
                    <label>
                        <?= form_radio('is_media_download_to_server','1', get_config('is_media_download_to_server') == 1) ?>
                     Download to server
                    </label>
                </div>
                <div class="checkbox d-inline-block  mt-2 mr-3">
                    <label>
                        <?= form_radio('is_media_download_to_server','0', get_config('is_media_download_to_server') == 0) ?>
                        Use Remote File
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Default Banner</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'file',
                    'name' => 'default_banner_file',
                    'class' => 'mb-3',
                ]) ?>
                <img src="<?= default_banner_uri() ?>" height="100" alt="default banner">
            </div>
        </div>







    </div>
</div>
