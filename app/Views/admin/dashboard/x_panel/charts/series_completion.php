<div class="x_panel">
    <div class="x_title">
        <h2>TV Shows Completion</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>

        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div id="tv_shows_completion_chart"></div>
        <ul class="list-group list-group-flush font-weight-bold">
            <li class="list-group-item">Completed :
                <span class="float-right px-1">
                            <?= number_format( $anytc->series->completed ) ?>
                        </span>
            </li>
            <li class="list-group-item">Incomplete :
                <span class="float-right  px-1">
                            <?= number_format( $anytc->series->incomplete ) ?>
                        </span>
            </li>
        </ul>
    </div>
</div>