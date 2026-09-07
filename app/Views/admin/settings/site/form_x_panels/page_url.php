<div class="x_panel">
    <div class="x_title">
        <h2>Page Urls</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <label class="control-label col-md-3">View Slug</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'view_slug',
                    'class' => 'form-control',
                    'value' => get_config('view_slug'),
                    'placeholder' => 'view'
                ]) ?>
                <small>Default: <i>view</i></small> <br>
                <small>Page: <i>https://mysite.com/view/xxx</i></small>

            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Embed Slug</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'embed_slug',
                    'class' => 'form-control',
                    'value' => get_config('embed_slug'),
                    'placeholder' => 'embed'
                ]) ?>
                <small>Default: <i>embed</i></small> <br>
                <small>Page: <i>https://mysite.com/embed/xxx</i></small>

            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Download Slug</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'download_slug',
                    'class' => 'form-control',
                    'value' => get_config('download_slug'),
                    'placeholder' => 'download'
                ]) ?>
                <small>Default: <i>download</i></small> <br>
                <small>Page: <i>https://mysite.com/download/xxx</i></small>

            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Link Slug</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'link_slug',
                    'class' => 'form-control',
                    'value' => get_config('link_slug'),
                    'placeholder' => 'link'
                ]) ?>
                <small>Default: <i>link</i></small> <br>
                <small>Page: <i>https://mysite.com/link/xxx</i></small>

            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Library Slug</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'library_slug',
                    'class' => 'form-control',
                    'value' => get_config('library_slug'),
                    'placeholder' => 'library'
                ]) ?>
                <small>Default: <i>library</i></small> <br>
                <small>Page: <i>https://mysite.com/library/xxx</i></small>

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