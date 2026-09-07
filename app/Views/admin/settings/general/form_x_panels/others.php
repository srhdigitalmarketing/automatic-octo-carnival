<div class="x_panel">
    <div class="x_title">
        <h2>Link reporting <small>Visitor feedback</small></h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content general-report-panel">
        <div class="general-report-card">
            <div class="general-report-card__icon"><i class="fa fa-flag-o"></i></div>
            <div class="general-report-card__content">
                <span class="general-report-card__eyebrow">Links Report</span>
                <h3>Let visitors report broken links</h3>
                <p>Show a report option to help your team identify unavailable or incorrect links.</p>
            </div>
            <label class="general-report-toggle" for="is_links_report">
                <input id="is_links_report" type="checkbox" name="is_links_report" value="1" <?= get_config('is_links_report') ? 'checked' : '' ?>>
                <span>Enable reporting</span>
                <small><?= get_config('is_links_report') ? 'Active' : 'Inactive' ?></small>
            </label>
        </div>

        <div class="text-right general-report-actions">
            <?= form_button([
                'type' => 'submit',
                'class' => 'btn btn-primary'
            ], 'Save changes') ?>
        </div>
    </div>
</div>
