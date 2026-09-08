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







        <section id="banner-migration-panel" data-url="<?= esc(admin_url('/banner-migration/run'),'attr') ?>" style="border-top:1px solid #e9edf4;padding-top:20px;margin-top:20px">
            <h4>Migrasi Banner Lokal → R2 Storage</h4>
            <p>Pindahkan referensi banner video dan series dari <code>/public/uploads/banners</code> ke R2. File lokal tetap disimpan. File yang tidak digunakan di database tidak diproses.</p>
            <button type="button" id="banner-migration-start" class="btn btn-primary">Mulai migrasi</button>
            <button type="button" id="banner-migration-stop" class="btn btn-default" disabled>Berhenti</button>
            <progress id="banner-migration-progress" value="0" max="1" style="width:100%;height:16px" aria-label="Progres migrasi"></progress>
            <p id="banner-migration-status" role="status" aria-live="polite">Siap. Pastikan R2 aktif dan biarkan halaman terbuka selama proses.</p>
            <ul id="banner-migration-log" style="max-height:280px;overflow:auto;padding-left:20px"></ul>
        </section>
    </div>
</div>
