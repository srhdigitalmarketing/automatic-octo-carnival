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
                'value' => old('title', $movie->title),
                'class' => 'form-control title-suggest',
                'data-type' => 'movie'
            ] ) ?>
            <span class="form-text">Enter a video title to find suggestions.</span>
        </div>

        <div id="suggest-results"></div>
        <div id="host-api-results" aria-live="polite"></div>

        <div class="form-group">
            <?= form_label('Video ID *:') ?>
            <?=  form_input( [
                'name' => 'imdb_id',
                'value' => old('imdb_id', $movie->imdb_id),
                'class' => 'form-control',
                'placeholder' => 'Enter video ID'
            ] ) ?>
        </div>

        <div class="form-group">
            <?= form_label('Short Description *:') ?>
            <?= form_textarea( [
                'name' => 'description',
                'class' => 'form-control',
                'rows' => 8
            ], old('description', $movie->description ?? '' ) ) ?>
        </div>


    </div>
</div>
