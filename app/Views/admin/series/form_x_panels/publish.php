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

        <div class="form-group row ">
            <?= form_label('Status:', '',['class' => 'control-label col-md-3 col-sm-3 pt-2' ]) ?>
            <div class="col-md-9 col-sm-9 ">
                <?= form_dropdown([
                    'name' => 'status',
                    'options' => [
                        'returning' => 'Returning',
                        'ended' => 'Ended'
                    ],
                    'selected' => [ old('status',  $series->status) ],
                    'class' => 'form-control'
                ]); ?>
            </div>
        </div>

        <?php if(! empty( $series->id )): ?>
        <div class="mt-3">
            <?php if($series->is_completed): ?>
                <span class="badge badge-success">Completed</span>
                <a href="<?= admin_url("/series/completed/{$series->id}?done=0") ?>" class="d-inline-block float-right">Mark as not completed</a>
            <?php else: ?>
                <span class="badge badge-danger">Not Completed</span>
                <a href="<?= admin_url("/series/completed/{$series->id}?done=1") ?>" class="d-inline-block float-right">Mark as completed</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="form-group mt-3">
            <?= form_submit([
                'class' => 'btn btn-primary btn-block'
            ], 'Publish') ?>
        </div>

        <?php if(! empty( $series->id )): ?>
            <div class="text-right">
                <a href="javascript:void(0)" data-url="<?= admin_url("/series/delete/{$series->id}") ?>" class="text-danger del-item">Delete</a>
            </div>
        <?php endif; ?>

    </div>
</div>