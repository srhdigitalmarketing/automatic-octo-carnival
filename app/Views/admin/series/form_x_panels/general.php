<div class="x_panel">
    <div class="x_title">
        <h2>General Information</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group">
            <?= form_label('Title *:') ?>
            <?= form_input( [
                'name' => 'title',
                'value' => old('title', $series->title),
                'class' => 'form-control title-suggest',
                'data-type' => 'tv'
            ] ) ?>
            <span class="form-text">Enter tv show title here to suggest tv shows.</span>
        </div>
        <div  id="suggest-results"></div>

        <div class="form-group row">
            <div class="col">
                <?= form_label('IMDB Id *:') ?>
                <?=
                 form_input( [
                    'name' => 'imdb_id',
                    'value' => old('imdb_id', $series->imdb_id),
                    'class' => 'form-control'
                ] ) ?>
            </div>
            <div class="col">
                <?= form_label('TMDB Id :') ?>
                <?= form_input( [
                    'name' => 'tmdb_id',
                    'value' => old('tmdb_id', $series->tmdb_id),
                    'class' => 'form-control'
                ] ) ?>
            </div>
        </div>




    </div>
</div>