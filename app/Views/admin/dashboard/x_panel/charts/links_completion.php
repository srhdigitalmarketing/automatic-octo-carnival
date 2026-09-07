<div class="x_panel dashboard-health-panel">
    <div class="x_title">
        <h2>Library Health <small>Link coverage overview</small></h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>

        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content dashboard-health-content">
        <div class="dashboard-health-chart">
            <div id="links_completion_chart"></div>
        </div>
        <div class="dashboard-health-summary">
            <span class="dashboard-health-caption">Content requiring attention</span>
            <ul class="list-group list-group-flush font-weight-bold">
            <li class="list-group-item"><span><i class="fa fa-circle health-dot health-dot--blue"></i> Missing stream link</span>
                <span class="float-right px-1">
                            <?= number_format( $anytc->links_completion->stream->without ) ?>
                        </span>
            </li>
            <li class="list-group-item"><span><i class="fa fa-circle health-dot health-dot--purple"></i> Missing download link</span>
                <span class="float-right  px-1">
                            <?= number_format( $anytc->links_completion->download->without ) ?>
                        </span>
            </li>
        </ul>
        </div>
    </div>
</div>
