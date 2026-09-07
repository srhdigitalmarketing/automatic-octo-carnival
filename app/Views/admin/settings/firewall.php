<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">

        <?= form_open('/admin/settings/firewall/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">
            <div class="x_title">
                <h2>Block Other Sites <small>for embed</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">


                <div class="form-group row">
                    <label class="control-label col-md-3">Sites blocker</label>
                    <div class="col-md-9">
                        <div class="checkbox">
                            <label>
                                <?= form_checkbox('is_referer_blocked','', get_config('is_referer_blocked')) ?>
                               Enable/ Disable
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">Allowed Sites</label>
                    <div class="col-md-9">
                        <?= form_textarea([
                            'name' => 'allowed_referer_list',
                            'class' => 'form-control',
                            'rows' => 5
                        ], implode(', ', get_config('allowed_referer_list'))) ?>
                        <small>You can add, which sites allowed to use our embed API</small>
                    </div>
                </div>



            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Limit requests
                    per user <small> - a day</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">


                <div class="form-group row">
                    <label class="control-label col-md-3">Stream links requests</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'number',
                            'name' => 'stream_links_requests_limit',
                            'class' => 'form-control',
                            'value' => get_config('stream_links_requests_limit'),
                            'min' => 0
                        ]) ?>
                        <small>Min: 0, &nbsp;&nbsp;default: 25,&nbsp;&nbsp; for unlimited stream use 0 </small>
                        <br><small> <b>page: </b> <i>https://mysite.com/embed/xxx</i> </small>
                    </div>
                </div>


                <div class="form-group row">
                    <label class="control-label col-md-3">Download links requests</label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'number',
                            'name' => 'download_links_requests_limit',
                            'class' => 'form-control',
                            'value' => get_config('download_links_requests_limit'),
                            'min' => 0
                        ]) ?>
                        <small>Min: 0, &nbsp;&nbsp;default: 25,&nbsp;&nbsp; for unlimited downloads use 0 </small>
                        <br><small> <b>page: </b> <i>https://mysite.com/link/xxx</i> </small>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">Links reporting </label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'number',
                            'name' => 'report_requests_limit',
                            'class' => 'form-control',
                            'value' => get_config('report_requests_limit'),
                            'min' => 1
                        ]) ?>
                        <small>Min: 1, &nbsp;&nbsp;default: 10 </small>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">API - status check </label>
                    <div class="col-md-9">
                        <?= form_input([
                            'type' => 'number',
                            'name' => 'api_status_check_rate_limit',
                            'class' => 'form-control',
                            'value' => get_config('api_status_check_rate_limit'),
                            'min' => 0
                        ]) ?>
                        <small>Min: 0, &nbsp;&nbsp;default: 100,  for unlimited requests use 0 </small>
                    </div>
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
