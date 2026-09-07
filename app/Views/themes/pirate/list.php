<?= $this->extend( theme_path('__layout/base') ) ?>

<?= $this->section("content") ?>

<div class="container-fluid">

    <div class="content d-flex align-items-center justify-content-between my-15">
        <h1 class="content-title mb-0">
            <?= $formattedTitle ?>
        </h1>
        <div class="tabs p-5 bg-dark-light font-weight-semi-bold mr-0">
            <a href="<?= site_url("/{$basePage}/movies") ?>" class="d-inline-block <?= $isMovie  ? 'btn' : 'nav-link' ?>" >
                <i class="fa fa-film" aria-hidden="true"></i>&nbsp;
                <?= lang('General.movies') ?>
            </a>
            <a href="<?= site_url("/{$basePage}/shows") ?>" class="d-inline-block <?= ! $isMovie ? 'btn' : 'nav-link' ?>" >
                <i class="fa fa-television" aria-hidden="true"></i>&nbsp;
                <?= lang('General.tv_shows') ?>
            </a>
        </div>

    </div>


    <?php if(! empty( $movies )):  ?>

        <div class="row row-eq-spacing mx-10 mt-0">
            <?php foreach ($movies as $movie): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2 px-5">
                    <?php the_movie_item( $movie, $basePage == 'imdb-top' ); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center">
            <?php if( $isMovie ): ?>
            <a href="<?= library_url([],'movies') ?>" class="btn btn-lg">
                <?= lang('Page.explore_all_movies') ?>
            </a>
            <?php else: ?>
                <a href="<?= library_url([],'shows') ?>" class="btn btn-lg">
                    <?= lang('Page.explore_all_tv_shows') ?>
                </a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <div class="content h-600">
            <h4> <?= lang('Page.results_not_found') ?> </h4>
        </div>

    <?php endif; ?>


</div>


<?= $this->endSection() ?>
