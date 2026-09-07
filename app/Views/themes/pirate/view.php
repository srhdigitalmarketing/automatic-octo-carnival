    <?= $this->extend( theme_path('__layout/base') ) ?>

    <?= $this->section("content") ?>

    <div class="container-fluid">
    <div class="row no-gutters top-streamapi row-eq-spacing">
    <div class="col">
    <!-- leaderboard ad-->
    <?php if( has_display_banner_ad('view.banner.player-bottom', $ads) ) {
    echo display_banner_ad('view.banner.player-bottom', $ads);
    } ?>
    <div class="title-streamapi">
    <?= esc( $title ) ?>
    </div> 
    <?php the_embed_player( $activeMovie ) ?>

    <!-- leaderboard ad-->
    <?php if( has_display_banner_ad('view.banner.player-top', $ads) ) {
    echo display_banner_ad('view.banner.player-top', $ads);
    } ?>

    <?php if(! empty($seasons)) : ?>
    <div class="card seasons-list" data-series-imdb="<?= $activeMovie->series_imdb_id ?>">
    <div class="input-group">
    <?= format_seasons_list( $activeMovie, $seasons ) ?>
    </div>
    </div>
    <?php endif; ?>    
    <div class="card">
    <h2 class="card-title">
    Storyline
    </h2>
    <p>
    <?= esc( $activeMovie->description ) ?>
    </p>
    </div>

    <div class="card">
    <?php the_embed_links_group( $activeMovie ); ?>
    </div>
    </div>
    
    <div class="col-auto">
    <img src="<?= poster_uri( $activeMovie->poster ) ?>" class="img-fluid w-full" alt="">
    <div class="card-quality">
    <div class="row align-items-center">
    <div class="col">
    Video Quality
    </div>
    <div class="col-auto">
    <span class="badge badge-primary"><?= esc( $activeMovie->quality ) ?></span>
    </div>
    </div>
    </div>
    <?php if(! empty( $activeMovie->trailer )): ?>
    <button class="btn btn-block btxtrailer" data-toggle="modal" data-target="trailer-modal">
    <i class="bi bi-play-btn-fill"></i> &nbsp; Watch Trailer</button>
    <?php endif; ?>
    <div class="metainfos">
    <?php the_movie_meta_info( $activeMovie ) ?>
    </div>
    <div class="download-box">
    <?php if( is_download_enabled() ) : ?>
    <a href="<?= esc( $activeMovie->getDownloadLink(true) ) ?>" id="ve-download--btn" class="ve-download--btn btn">
    <i class="bi bi-cloud-download"></i>
    <span class="d-none d-sm-inline-block"> &nbsp; <?= lang('General.download') ?></span>
    </a>
    <?php endif; ?>
    </div>        
    </div>
    </div>
    </div>

<?=$this->endSection() ?>

<?= $this->section('end-of-content') ?>

    <?php if(! empty( $activeMovie->trailer )): ?>
    <!-- Modal Trailer -->
    <div class="modal" id="trailer-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content modal-content-media w-600 p-10">
    <a href="javascript:void(0)" class="close" role="button" aria-label="Close" data-dismiss="modal" type="button">
    <span aria-hidden="true">&times;</span>
    </a>
    <div class="iframe-wrapper">
    <iframe class="lazy" width="560" height="315" data-src="<?= esc( $activeMovie->getMovieTrailer() ) ?>" title="trailer" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>
    </iframe>
    </div>
    </div>
    </div>
    </div>
    <?php endif; ?>

<?= $this->endSection() ?>