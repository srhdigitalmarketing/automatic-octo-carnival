<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open('/admin/settings/cache/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">
            <div class="x_title">
                <h2>Web pages</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="form-group row">
                    <label class="control-label col-md-3">Web pages cache</label>
                    <div class="col-md-9">
                        <div class="checkbox">
                            <label>
                                <?= form_checkbox('web_page_cache','', get_config('web_page_cache')) ?>
                                Enable/ Disable
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="control-label col-md-3">Cache duration</label>
                    <div class="col-md-9">

                        <div class="input-group mb-3">
                            <?= form_input([
                                'type' => 'number',
                                'name' => 'web_page_cache_duration',
                                'class' => 'form-control',
                                'value' => get_config('web_page_cache_duration'),
                                'min' => 300
                            ]) ?>
                            <div class="input-group-append">
                                <span class="input-group-text" >seconds</span>
                            </div>
                        </div>
                        <small>Min: 60, &nbsp;&nbsp;default: 86400 (1 day)</small>
                    </div>
                </div>

                <div>
                    <b>Supported Pages:</b>
                    <ul>
                        <li> <i>Embed page</i> </li>
                        <li> <i>View page</i> </li>
                        <li> <i>Download page</i> </li>
                        <li> <i>Library page</i> </li>
                    </ul>
                </div>

                <div class="text-right">
                    <?= form_button([
                        'type' => 'submit',
                        'class' => 'btn btn-primary'
                    ], 'update') ?>
                </div>



            </div>
        </div>


        <?= form_close() ?>

    </div>
</div>

<?php $this->endSection() ?>
