<div class="x_panel">
    <div class="x_title">
        <h2>Movies Request</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <label class="control-label col-md-3">Request System</label>
            <div class="col-md-9">
                <div class="checkbox  mt-2">
                    <label>
                        <?= form_checkbox('request_system','', get_config('request_system')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Email Subscription</label>
            <div class="col-md-9">
                <div class="checkbox mt-2">
                    <label>
                        <?= form_checkbox('req_email_subscription','', get_config('req_email_subscription')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Captcha</label>
            <div class="col-md-9">
                <div class="checkbox mt-2">
                    <label>
                        <?= form_checkbox('is_request_captcha_enabled','', get_config('is_request_captcha_enabled')) ?>
                        Enable/ Disable
                    </label>
                </div>
            </div>
        </div>





    </div>
</div>