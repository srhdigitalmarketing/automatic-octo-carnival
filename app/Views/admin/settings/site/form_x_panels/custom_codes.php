<div class="x_panel">
    <div class="x_title">
        <h2>Custom Codes</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">




        <div class="form-group row">
            <label class="control-label">Header codes</label>
            <?= form_textarea([
                'name' => 'custom_header_codes',
                'class' => 'form-control',
                'rows' => 15
            ], header_custom_codes()) ?>
        </div>


        <div class="form-group row">
            <label class="control-label">Footer codes</label>
            <?= form_textarea([
                'name' => 'custom_footer_codes',
                'class' => 'form-control',
                'rows' => 15
            ], footer_custom_codes()) ?>
        </div>



    </div>
</div>