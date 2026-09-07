<div class="x_panel">
    <div class="x_title">
        <h2>Trailer</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">



        <div class="form-group">
            <?= form_label('Video URL :') ?>
            <?= form_input( [
                 'type' => 'url',
                'name' => 'trailer',
                'value' => old('trailer', $movie->trailer),
                'class' => 'form-control'
            ] ) ?>
        </div>




    </div>
</div>