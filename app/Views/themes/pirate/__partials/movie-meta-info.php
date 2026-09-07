<div class="mb-5">
    <?= lang('General.imdb_rate') ?>:
    <span class="badge badge-secondary font-weight-semi-bold">
                        <?= $activeMovie->getMovieImdbRate() ?>
                    </span>
</div>

<div class="mb-5">
    <?= lang('General.genres') ?>:
    <?php
    if(! empty( $activeMovie->genres() )) {

        echo get_genres_links( $activeMovie->genres() );

    }else{

        echo 'N/A';

    }
    ?>
</div>

<div class="mb-5">
    <?= lang('General.year') ?>: <?php if(! empty( $activeMovie->year )) {
        echo anchor(
            library_url( [ 'year' => $activeMovie->year ] ),
            $activeMovie->year
        );
    }else{
        echo '<span class=""> N/A </span>';
    } ?>

</div>


<div class="mb-5">
    <?= lang('General.country') ?>: <?php if(! empty( $activeMovie->getMovieCountries() )) {
        echo display_country_list( $activeMovie->getMovieCountries() );
    }else{
        echo '<span class=""> N/A </span>';
    } ?>
</div>


<div class="mb-5">
    <?= lang('General.language') ?>: <?php if(! empty( $activeMovie->getMovieLanguages() )) {
        echo display_language_list( $activeMovie->getMovieLanguages() );
    }else{
        echo '<span class=""> N/A </span>';
    } ?>
</div>


<div class="mb-5">
    <?= lang('General.run_time') ?>: <span class="badge timer_">
                     <?= $activeMovie->duration ? $activeMovie->duration . ' min' : 'N/A' ?>
</span>
</div>

