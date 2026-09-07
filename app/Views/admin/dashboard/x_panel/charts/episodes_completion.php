<div class="x_panel">
    <div class="x_title">
        <h2>Episodes Completion</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>

        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">
        <div id="episodes_completion_chart"></div>
        <ul class="list-group list-group-flush font-weight-bold">
            <li class="list-group-item">Completed :
                <span class="float-right px-1">
                            <?= number_format( $anytc->episodes->completed ) ?>
                        </span>
            </li>
            <li class="list-group-item">Incomplete :
                <span class="float-right  px-1">
                            <?= number_format( $anytc->episodes->incomplete) ?>
                        </span>
            </li>
        </ul>
    </div>
</div>