<div class="card movie-card p-0 border-0 mb-10"> <!-- p-0 = padding: 0 -->
    <div class="front" >
<div class="poster-img lazy"  data-bg-multi=" url(<?= poster_uri( esc( $movie->poster ) )  ?>)">

</div>
        <div class="top">
            <?php if(! empty($movie->season)): ?>
                <span class="badge bg-dark-dm border-0">
                    <?= the_episode_label($movie->season, $movie->episode) ?>
                </span>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <?php if(! $imdbBased): ?>

                <?php if(! empty( $movie->quality )): ?>
                    <span class="badge bg-dark-dm border-0 font-weight-semi-bold float-right">
                    <?= esc( $movie->quality ) ?>
                </span>
                <?php endif; ?>

            <?php else: ?>

                <?php if(! empty( $movie->imdb_rate )): ?>
                    <span class="badge badge-secondary border-0 font-weight-semi-bold float-right">
                    <?= $movie->imdb_rate ?>
                </span>
                <?php endif; ?>

            <?php endif; ?>

        </div>
        <div class="bottom">
            <?php if(! empty( $movie->duration )): ?>
                <span class="badge bg-dark-dm border-0">
                    <?= $movie->duration ?> min
                </span>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
            <?php if(is_movie_viewed( $movie->imdb_id )): ?>
            <span class="badge bg-views border-0 float-right">
            <i class="fa-solid fa-play fa-fade" style="color: #ffffff;"></i>
            </span>
            <?php endif; ?>
        </div>

    </div>
    <div class="back">
    <a href="<?= esc( $movie->getViewLink() ) ?>" class="play_streamapi"></a>
    </div>
    <span class="embed--link--1 d-none" ><?= esc( $movie->getEmbedLink(true) ) ?></span>
    <span class="embed--link--2 d-none" ><?= esc( $movie->getEmbedLink() ) ?></span>
</div>
<a href="<?= esc( $movie->getViewLink() ) ?>" class="title-sa_"><?= esc( $movie->title ) ?> <?php if(! empty( $movie->year )) echo ' - ' . $movie->year; ?></a>