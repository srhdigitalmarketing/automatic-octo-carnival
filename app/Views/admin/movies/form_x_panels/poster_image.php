<div class="x_panel">
    <div class="x_title">
        <h2>Poster Image</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">


        <div class="poster-wrap">
            <?php if($movie->hasPoster()): ?>
                <img src="<?= poster_uri( $movie->poster ) ?>" class="w-100 mb-2" alt="poster image">
            <?php endif; ?>
        </div>


        <!-- start form for validation -->
        <div class="form-group">
            <?= form_label('Select from remote URL:') ?>
            <?= form_input( [
                'type' => 'url',
                'class' => 'form-control',
                'name' => 'poster_url',
                'value' => old('poster_url')
            ] ) ?>
        </div>
        <div class="separator"> or </div>
        <div class="form-group">
            <?= form_label('Select from PC :') ?>
            <?= form_input( [
                'type' => 'file',
                'name' => 'poster_file',
                'accept' => 'image/*'
            ] ) ?>
        </div>

    </div>
</div>