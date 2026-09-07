<div class="x_panel">
    <div class="x_title">
        <h2>SEO Setup</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group">
            <?= form_label('Meta Keywords:') ?>
            <?= form_textarea( [
                'name' => 'meta_keywords',
                'class' => 'form-control',
                'rows' => 3
            ], old('meta_keywords', $movie->meta_keywords ?? '' )) ?>
        </div>


        <div class="form-group">
            <?= form_label('Meta Description:') ?>
            <?= form_textarea( [
                'name' => 'meta_description',
                'class' => 'form-control',
                'rows' => 6
            ], old('meta_description', $movie->meta_description ?? '' )) ?>
        </div>


    </div>
</div>