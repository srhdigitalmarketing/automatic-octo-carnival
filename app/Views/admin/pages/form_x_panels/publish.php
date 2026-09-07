<div class="x_panel">
    <div class="x_title">
        <h2>Publish</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row mb-3">
            <?= form_label('Status:', '',['class' => 'control-label col-md-3 col-sm-3 pt-2' ]) ?>
            <div class="col-md-9 col-sm-9 ">
                <?= form_dropdown([
                    'name' => 'status',
                    'options' => [
                        'public' => 'Public',
                        'draft' => 'Draft'
                    ],
                    'selected' => [ old('status', $page->status) ],
                    'class' => 'form-control'
                ]); ?>
            </div>
        </div>

        <div class="form-group">
            <?= form_submit([
                'class' => 'btn btn-primary btn-block'
            ], 'Save') ?>
        </div>

    </div>
</div>