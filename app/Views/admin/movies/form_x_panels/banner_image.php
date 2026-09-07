<div class="x_panel">
    <div class="x_title">
        <h2>Banner Image</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">


            <div class="banner-wrap ">
                <?php if($movie->hasBanner()): ?>
                <img src="<?= banner_uri( $movie->banner ) ?>" class="w-100 mb-2" alt="banner image">
                <?php endif; ?>
            </div>


        <div class="form-group">
            <?= form_label('Link dari R2:') ?>
            <?= form_input([
                'type' => 'url',
                'class' => 'form-control',
                'value' => filter_var((string) $movie->banner, FILTER_VALIDATE_URL) !== false ? $movie->banner : '',
                'placeholder' => 'Link banner R2 akan muncul setelah upload',
                'readonly' => 'readonly',
                'data-r2-banner-link' => 'true',
            ]) ?>
        </div>
        <div class="separator"> or </div>
        <div class="form-group">
            <?= form_label('Select from PC:') ?>
            <?= form_input([
                'type' => 'file',
                'name' => 'banner_file',
                'class' => 'form-control',
                'accept' => 'image/*'
            ]) ?>
        </div>
        <?= form_hidden('remove_banner', old('remove_banner', '0')) ?>
        <div class="banner-image-actions">
            <button type="submit" name="upload_banner" value="1" class="btn btn-primary" data-upload-banner-to-r2><i class="fa fa-cloud-upload"></i> Upload to R2</button>
            <button type="button" class="btn btn-danger" data-clear-banner-image disabled><i class="fa fa-trash"></i> Delete image</button>
        </div>
        <small class="banner-image-actions__hint">Select a file from PC, then upload it to the configured Cloudflare R2 storage.</small>

    </div>
</div>
