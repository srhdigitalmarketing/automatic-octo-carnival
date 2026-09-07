<div class="x_panel">

    <div class="x_content">

        <div class="form-group">
            <?= form_label('Enter Imdb/ Tmdb ids list:') ?>
            <?= form_textarea( [
                'name' => 'title',
                'class' => 'form-control',
                'id' => 'ids-list',
                'placeholder' => 'tt0944947, tt4283088, tt4283094',
                'rows' => 8
            ],  implode(" , \n", $ids) ) ?>

        </div>

        <div class="form-group text-right">
            <label for="">Type : </label>
            <?= form_dropdown([
                    'name' => 'type',
                    'class' => 'form-control d-inline-block w-auto',
                    'options' => [
                            'movies' => 'Movies',
                            'tv' => 'TV Shows'
                    ],
                'selected' => $type
            ]) ?>
        </div>


        <div class="text-right mt-4">

            <?= form_button([
                'class' => 'btn btn-primary px-4 btn-lg',
                'id' => 'run-importer'
            ], '<span class="spinner-border spinner-border" role="status" aria-hidden="true" style="display: none"></span>
 &nbsp;<i class="fa fa-download"></i> &nbsp;Import') ?>
        </div>


    </div>
</div>


<div class="x_panel">
    <div class="x_content">

        <ul>
            <li class="mb-1">Separate each id by comma. Ex : <i>tt0944947</i> , <i>tt4283088</i> </li>
            <li class="mb-1">Episodes import pattern :
            <ul>
                <li>All Seasons/ Episodes:  <mark> <b>IMDB_ID[ * ]</b> </mark>&nbsp;  Ex: <i>tt0944947[ * ]</i></li>
                <li>
                    Single Season:
                    <mark> <b>IMDB_ID[ SEASON ]</b> </mark>&nbsp; Ex: <i>tt0944947[1]</i> </li>
                <li>
                    Specific Season/ Episodes :
                    <mark> <b>IMDB_ID[ SEASON . EPISODE-RANGE ]</b> </mark>&nbsp; Ex: <i>tt0944947[1.1-10]</i> </li>
            </ul>
            </li>
            <li >
                You can use IMDB or TMDB ids to import movies and shows
            </li>
        </ul>

    </div>
</div>
