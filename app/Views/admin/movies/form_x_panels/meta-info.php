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

        <div class="form-group">
            <?= form_label('Released Date :') ?>
            <?= form_input( [
                'type' => 'date',
                'name' => 'released_at',
                'value' => old('released_at', $movie->released_at),
                'class' => 'form-control',
                'placeholder' => 'yyyy-mm-dd'
            ] ) ?>
<small>Date Format: MM/DD/YYY</small>
        </div>

        <div class="form-group">
            <?= form_label('IMDB Rate :') ?>
            <?= form_input( [
                'name' => 'imdb_rate',
                'value' => old('imdb_rate', $movie->imdb_rate),
                'class' => 'form-control'
            ] ) ?>
        </div>

        <div class="form-group">
            <?= form_label('Run Time :') ?>
            <?= form_input( [
                'type' => 'number',
                'name' => 'duration',
                'value' => old('duration', $movie->duration),
                'class' => 'form-control',
                'min' => 0
            ] ) ?>
        </div>

        <?php if(! empty($movie->type) && ! $movie->isEpisode()): ?>

        <div class="form-group">
            <?= form_label('Language :') ?>
            <?= form_input( [
                'type' => 'text',
                'name' => 'language',
                'value' => old('language', $movie->language),
                'class' => 'form-control'
            ] ) ?>
        </div>

        <div class="form-group">
            <?= form_label('Country :') ?>
            <?= form_input( [
                'type' => 'text',
                'name' => 'country',
                'value' => old('country', $movie->country),
                'class' => 'form-control'
            ] ) ?>
        </div>

        <?php endif; ?>

    </div>
</div>