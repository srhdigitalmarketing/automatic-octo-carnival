<div class="x_panel">
    <div class="x_title">
        <h2>Meta Info</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <div class="col">
                <?= form_label('Total Seasons :') ?>
                <?=
                 form_input( [
                    'type' => 'number',
                    'name' => 'total_seasons',
                    'value' => old('total_seasons', $series->total_seasons),
                    'class' => 'form-control'
                ] ) ?>
            </div>
            <div class="col">
                <?= form_label('Total Episodes :') ?>
                <?= form_input( [
                    'type' => 'number',
                    'name' => 'total_episodes',
                    'value' => old('total_episodes', $series->total_episodes),
                    'class' => 'form-control'
                ] ) ?>
            </div>
        </div>
        <div class="form-group row">
            <div class="col">
                <?= form_label('IMDB Rate:') ?>
                <?=
                form_input([
                    'name' => 'imdb_rate',
                    'value' => old('imdb_rate', $series->imdb_rate),
                    'class' => 'form-control'
                ])
                ?>
            </div>
            <div class="col">
                <?= form_label('Released Date:') ?>
                <?= form_input( [
                    'type' => 'date',
                    'name' => 'released_at',
                    'value' => old('released_at', $series->released_at),
                    'class' => 'form-control'
                ] ) ?>
            </div>
        </div>

        <div class="form-group row">
            <div class="col">
                <?= form_label('Country:') ?>
                <?=
                form_input( [
                    'name' => 'country',
                    'value' => old('country', $series->country),
                    'class' => 'form-control'
                ] )
                 ?>
            </div>
            <div class="col">
                <?= form_label('Language :') ?>
                <?= form_input( [
                    'name' => 'language',
                    'value' => old('language', $series->language),
                    'class' => 'form-control'
                ] ) ?>
            </div>
        </div>

    </div>
</div>