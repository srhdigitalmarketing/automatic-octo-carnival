<div class="sidebar  <?= sidebar_disabled() ? 'd-block d-md-none' : '' ?>" >
    <div class="sidebar-menu">
        <div class="sidebar-content d-block d-md-none">
            <a href="<?= site_url()?>" class="btn btn-lg font-weight-semi-bold mb-10" data-toggle="tooltip" data-title="Home" data-placement="right" >
                <i class="fa fa-home" aria-hidden="true"></i>
            </a>
            <a href="<?= library_url()?>" class="btn btn-lg font-weight-semi-bold mb-10" data-toggle="tooltip" data-title="Videos" data-placement="right" >
                <i class="fa fa-film" aria-hidden="true"></i>
            </a>
            <a href="<?= site_url('/api') ?>" class="btn btn-lg font-weight-semi-bold mb-10" data-toggle="tooltip" data-title="API" data-placement="right" >
                <i class="fa fa-magic" aria-hidden="true"></i>
            </a>
        </div>
         <div class="sidebar-divider d-block d-md-none"></div>

        <div class="sidebar-content <?= sidebar_disabled() ? 'd-none' : '' ?>">

            <?php if(! empty( get_config('items_per_trending_page') )): ?>
            <a href="<?= site_url('trending/movies') ?>" class="btn btn-lg font-weight-semi-bold trending-btn mb-10" data-toggle="tooltip" data-title="<?= lang('SideNav.trending') ?>" data-placement="right" >
                <i class="fa fa-fire text-danger" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

            <?php if(! empty( get_config('items_per_recommend_page') )): ?>
            <a href="<?= site_url('recommend/movies') ?>" class="btn btn-lg font-weight-semi-bold recommend-btn mb-10" data-toggle="tooltip" data-title="<?= lang('SideNav.recommend') ?>" data-placement="right">
                <i class="fa fa-thumbs-up text-success" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

            <?php if(! empty( get_config('items_per_new_release_page') )): ?>
            <a href="<?= site_url('recent-releases') ?>" class="btn btn-lg font-weight-semi-bold new-release-btn mb-10" data-toggle="tooltip" data-title="<?= lang('SideNav.recent_releases') ?>" data-placement="right">
                <i class="fa fa-gift text-primary" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

            <?php if(! empty( get_config('items_per_imdb_top_page') )): ?>
            <a href="<?= site_url('imdb-top/movies') ?>" class="btn btn-lg font-weight-semi-bold imdb-top-btn mb-10" data-toggle="tooltip" data-title="<?= lang('SideNav.imdb_top') ?>" data-placement="right">
                <i class="fa fa-trophy text-secondary" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

            <?php if(! empty( get_config('watch_history_limit') )): ?>
            <a href="<?= site_url('history/movies') ?>" class="btn btn-lg font-weight-semi-bold watch-history-btn" data-toggle="tooltip" data-title="<?= lang('SideNav.watch_history') ?>" data-placement="right">
                <i class="fa fa-history text-muted" aria-hidden="true"></i>
            </a>
            <?php endif; ?>

        </div>




    </div>
</div>
