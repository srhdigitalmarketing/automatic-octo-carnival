<?php if(! empty( $results )): ?>
    <div class="text-right">
        <a href="javascript:void(0)">Clear All</a>
    </div>
    <div class="row">

        <?php foreach ($results as $movie): ?>

            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <div class="card movie-item <?= $movie['is_exist'] ? 'imported' : 'new';  ?> " data-tmdb="<?= $movie['tmdb_id'] ?>">
                    <div class="cover">
                        <img src="<?= esc( $movie['poster'] ) ?>" class="card-img-top" alt="poster-image">
                        <div class="overly">
                            <div class="checked-icon">
                                <i class="fa fa-check-square-o" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-2 ">
                        <div>
                            <p class="mb-0 font-weight-bold">
                                <?= esc( $movie['title'] ) ?>
                            </p>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <?php if(! $movie['is_exist'] ): ?>
                                <span class="badge badge-info">NEW</span>
                            <?php else: ?>
                                <span class="badge badge-success">IMPORTED</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>

    </div>
<?php else: ?>
    <div class="results-not-found text-center py-5">
        No results found
    </div>
<?php endif; ?>