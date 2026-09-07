<div class="x_panel" id="x_panel_<?= $page ?>" >
    <div class="x_title">
        <h2>Page <?= $page ?>: </h2>
        <ul class="nav navbar-right panel_toolbox">
            <li>
                <a href="javascript:void(0)" class="select-all" data-type="select"><i class="fa fa-th"></i>&nbsp; select/ deselect all</a>
            </li>
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <?php if(! empty( $results )): ?>
        <div class="row" id="results">

            <?php foreach ($results as $movie): ?>

                <div class="col-6 col-md-4 col-lg-3 col-xl-2 mb-4">
                    <div class="card movie-item <?= $movie->isExist ? 'imported' : 'new';  ?> " data-tmdb="<?= $movie->tmdb_id ?>">
                        <div class="cover">
                            <img src="<?= poster_uri( $movie->poster_url ) ?>" class="card-img-top" alt="poster-image">
                            <div class="overly">
                                <div class="checked-icon">
                                    <i class="fa fa-check-square-o" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2 ">
                            <div>
                                <p class="mb-0 font-weight-bold">
                                    <?= esc( $movie->title ) ?>
                                </p>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <?php if(! $movie->isExist): ?>
                                    <a href="<?= $movie->getTmdbUrl() ?>" target="_blank" class="">TMDB <i class="fa fa-external-link"></i></a>
<!--                                    <button class="btn btn-info btn-sm px-3 mb-0 import-movie">-->
<!--                                        <i class="fa fa-download"></i>-->
<!--                                        <i class="fa fa-check" style="display: none"></i>-->
<!--                                    </button>-->
                                    <span class="badge badge-info">NEW</span>
                                <?php else: ?>
                                    <a href="<?= $movie->type == 'movie' ? admin_url("movies/edit/{$movie->movie_id}") : admin_url("series/edit/{$movie->movie_id}") ?>" target="_blank" class="">Edit <i class="fa fa-edit"></i></a>
                                    <span class="badge badge-success">IMPORTED</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
        <?php else: ?>
        <h3> Results not found </h3>
        <?php endif; ?>
    </div>
</div>