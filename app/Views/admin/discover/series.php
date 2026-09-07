<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>




<div class="x_panel">

    <div class="x_content">

        <div class="form-group mb-0 d-inline-block float-right">
            <?= form_label('Sort By: ') ?>
            <?= form_dropdown([
                'class' => 'form-control w-auto d-inline-block',
                'name' => 'sort',
                'options' => [
                    'popularity' => 'popularity',
                    'first_air_date' => 'first air date',
                    'vote_average' => 'vote average'
                ]
            ]) ?>
            <?= form_dropdown([
                'class' => 'form-control w-auto d-inline-block',
                'name' => 'sort_dir',
                'options' => [
                    'asc' => 'asc',
                    'desc' => 'desc'
                ],
                'selected' => 'desc'
            ]) ?>
        </div>

    </div>
</div>
<div class="x_panel">

    <div class="x_content">



        <div class="form-group d-inline-block mb-0 mr-3">
            <?= form_label('Year: ') ?>
            <?= form_input([
                'type' => 'number',
                'name' => 'year',
                'class' => 'form-control w-auto d-inline-block',
                'min' => '1900',
                'max' => date('Y'),
                'step' => 1,
                'value' => date('Y')
            ]) ?>
        </div>

        <div class="form-group d-inline-block mb-0 mr-3">
            <?= form_label('Language: ') ?>
            <?= form_dropdown([
                'class' => 'form-control w-auto d-inline-block',
                'name' => 'lang',
                'options' => $langList
            ]) ?>
        </div>

        <div class="form-group d-inline-block mb-0 mr-3">
            <?= form_label('Status: ') ?>
            <?= form_dropdown([
                'class' => 'form-control w-auto d-inline-block',
                'name' => 'status',
                'options' => [
                    '' => '',
                    '0' => 'Returning Series',
                    '3' => 'Ended'
                ]
            ]) ?>
        </div>

        <div class="form-group d-inline-block mb-0 mr-3">
            <?= form_label('Type: ') ?>
            <?= form_dropdown([
                'class' => 'form-control w-auto d-inline-block',
                'name' => 'type',
                'options' => [
                    '' => '',
                    '0' => 'Documentary',
                    '1' => 'News',
                    '2' => 'Miniseries',
                    '3' => 'Reality',
                    '4' => 'Scripted',
                    '5' => 'Talk Show'
                ]
            ]) ?>
        </div>



    </div>
</div>
<div class="x_panel">
    <div class="x_title">
        <h2>Genres: </h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <?php if(! empty($genres)): ?>
            <div class="form-group d-inline-block mb-0 mr-3">
                <?php foreach ($genres as $k => $genre) :  ?>
                    <div class="form-group d-inline-block mb-0 mr-3">
                        <?= form_checkbox([
                            'name' => 'genre',
                            'id' => "genre-{$k}",
                            'value' => $genre
                        ]) ?>
                        <label class="align-text-bottom mb-0" for="genre-<?= $k ?>">
                            <?= esc($genre) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>



<div class="x_content">
    <div class="form-group float-left">
        <input type="checkbox" id="filter-imported-movies">
        <label for="filter-imported-movies" >Filter Already Imported TV Shows</label>
    </div>
    <div class="float-right">
        <div class="form-group d-inline-block mb-0 mr-3">
            <?= form_label('Page: ') ?>
            <div class="input-group d-inline-flex w-auto">
                <input type="number" name="page" class="form-control w-auto" min="1" value="1" style="max-width: 75px;"  >
                <div class="input-group-append" style="display: none">
                    <span class="input-group-text w-auto">/ <span class="total-pages ">N/A</span> </span>
                </div>
            </div>
        </div>
        <button class="btn btn-primary " id="init-discover" data-type="tv">
            <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true" style="display: none"></span>
            Get TV Shows</button>
    </div>

</div>

<div class="x_panel" id="items-selected-panel" style="display: none">
    <div class="x_content">
        <form action="<?= admin_url('/bulk-import') ?>" method="GET">
            <?= form_hidden('ids') ?>
            <?= form_hidden('type', 'tv') ?>
            <div class="text-right">
                <button class="btn btn-danger" type="submit">Bulk Import - ( <span class="items-selected">0</span> )</button>
                <button class="btn reset-selected-items" type="button">Reset</button>
            </div>
        </form>
    </div>
</div>

<div class="discover" id="results" ></div>


<div class="next-page-btn-wrap text-center" style="display: none">
    <button class="btn btn-warning load-next-page" ><i class="fa fa-hand-o-right"></i>&nbsp;  <span class="spinner-border spinner-border-sm mr-2" role="status" aria-hidden="true" style="display: none"></span>Get Next Page</button>
</div>


<?php $this->endSection() ?>
