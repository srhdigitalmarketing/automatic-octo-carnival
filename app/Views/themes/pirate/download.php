<?= $this->extend( theme_path('__layout/base') ) ?>

<?= $this->section("content") ?>

<div class="container-fluid mb-20">

    <div class="row row-eq-spacing">

        <!-- col-lg-4 col-xl-3 -->
        <div class="col-lg-4 col-xl-3 order-1 order-lg-0">

            <div class="mb-5">
                <a href="<?= library_url() ?>" >
                    <?= lang('General.back_to_library') ?>:
                </a>
            </div>

            <!-- Poster Card -->
            <div class="w-250 mw-full ">
                <div class="card m-0 mb-10 p-5">
                    <img src="<?= poster_uri( $activeMovie->poster) ?>" class="img-fluid w-full" alt="poster image">
                    <div class="p-10">
                        <?= lang('General.video_quality') ?>:
                        <span class="badge float-right">HD</span>
                    </div>
                </div>
                <a href="<?= esc( $activeMovie->getViewLink(true) ) ?>" class="btn btn-secondary btn-block font-weight-semi-bold" >
                    <i class="fa fa-video-camera" aria-hidden="true"></i>&nbsp;
                    <?= lang('Download.watch_online') ?>:
                </a>
            </div>
            <!-- /. Poster Card -->

            <!-- Meta Content -->
            <div class="content">
                <?php the_movie_meta_info( $activeMovie ) ?>
            </div>
            <!-- /. Meta Content -->

            <!-- leaderboard ad-->
            <?php if( has_display_banner_ad('download.banner.title-bottom', $ads) ) {
                echo display_banner_ad('download.banner.title-bottom', $ads);
            } ?>

        </div>
        <!-- /. col-lg-4 col-xl-3 -->

        <!-- col-lg-8 col-xl-9 -->
        <div class="col-lg-8 col-xl-9">

            <!-- Content Title -->
            <h1 class="content-title mt-5">
                <?= esc( $title ) ?>
            </h1>
            <!-- /. Content Title -->

            <!-- leaderboard ad-->
            <?php if( has_display_banner_ad('download.banner.title-bottom', $ads) ) {
                echo display_banner_ad('download.banner.title-bottom', $ads);
            } ?>
            <!-- /. leaderboard ad-->

            <!-- Overview Card -->
            <div class="card">
                <h2 class="card-title">
                    <?= lang('General.storyline') ?>
                </h2>
                <p>
                    <?= esc( $activeMovie->description ) ?>
                </p>
            </div>
            <!-- /. Overview Card -->

            <?php if (! empty($links)): ?>

                <?php foreach ($links as $val): ?>

                    <div class="card">
                        <h2 class="card-title mb-0">
                            <?= esc( $val["label"] ) ?>
                        </h2>
                    </div>

                    <?php foreach ($val["links"] as $linksGroup): ?>

                        <!-- Main Links Group Content -->
                        <div class="content text-center">
                            <h3 class="font-size-18 font-weight-light">
                                <?= esc( $linksGroup["label"] ) ?>
                            </h3>

                            <!--  Btn list group -->
                            <div class="btn-list">
                                <?php foreach ($linksGroup["links"] as $link) : ?>

                                    <!-- Single Link Group -->
                                    <div class="mx-5 d-inline-block" role="group" >
                                        <a href="<?= $link->getEncLink() ?>" class="btn" >
                                            <?= esc( $link->getHost(true) ) ?>&nbsp;
                                            <i class="fa fa-external-link" aria-hidden="true"></i>
                                        </a>
                                        <?php if( is_links_report_enabled() ): ?>

                                        <!-- Report Form Dropdown -->
                                        <div class="dropdown with-arrow">

                                            <!-- Report Btn -->
                                            <button class="btn mr-5 mb-10" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa fa-exclamation-triangle  text-secondary" aria-hidden="true"></i>
                                            </button>
                                            <!-- /. Report Btn -->

                                            <!-- Dropdown Menu -->
                                            <div class="dropdown-menu dropdown-menu-right w-250 w-sm-300" aria-labelledby="sign-in-dropdown-toggle-btn">

                                                <!-- Dropdown Content -->
                                                <div class="dropdown-content p-10">

                                                    <!-- Content Title -->
                                                    <h3 class="content-title font-size-16 text-danger">
                                                        <?= lang('Report.report_link') ?>
                                                    </h3>
                                                    <!-- /. Content Title -->

                                                    <!-- Report Form -->
                                                    <form>

                                                        <!-- Reason Selection -->
                                                        <div class="mb-20">
                                                            <?= lang('Report.select_reason') ?>:&nbsp;
                                                            <select class="form-control d-inline-block w-auto" name="reason">
                                                                <option value="not_working" selected="selected">  <?= lang('Report.not_working') ?> </option>
                                                                <option value="wrong_link">  <?= lang('Report.wrong_link') ?> </option>
                                                            </select>
                                                        </div>
                                                        <!-- /. Reason Selection -->

                                                        <!-- Hidden Token -->
                                                        <?= form_hidden('token', \App\Libraries\UniqToken::create( [$link->movie_id, $link->id] )) ?>
                                                        <!-- /. Hidden Token -->


                                                        <div class="dropdown-divider"></div>
                                                        <div class="text-right mt-10">
                                                            <button class="btn btn-danger report-dl-link" type="button">
                                                                <?= lang('Report.report_btn') ?>
                                                            </button>
                                                        </div>
                                                    </form>
                                                    <!-- /. Report Form -->


                                                </div>
                                                <!-- /. Dropdown Content -->

                                            </div>
                                            <!-- /. Dropdown Menu -->

                                        </div>
                                        <!-- /. Report Form Dropdown -->

                                        <?php endif; ?>
                                    </div>
                                    <!-- /. Single Link Group -->

                                <?php endforeach; ?>

                            </div>
                            <!--  /. Btn list group -->
                        </div>
                        <!-- /. Main Links Group Content-->

                    <?php endforeach; ?>

                    <!-- leaderboard ad-->
                    <?php if( has_display_banner_ad('download.banner.links-group-middle', $ads) ) {
                        echo display_banner_ad('download.banner.links-group-middle', $ads);
                    } ?>
                    <!-- /. leaderboard ad-->

                <?php endforeach; ?>



            <?php else: ?>

                <div class="card">
                    <h3 class="card-title text-center mb-0 ">
                        <?= lang('Report.links_not_found') ?>
                    </h3>
                </div>

            <?php endif; ?>


            <!-- Next/ Prev Episode Content -->
            <div class="content dl-jump-group flex-column flex-md-row " >

                <?php if (!empty($prevEpisode)): ?>

                <!-- Previous Episode Group -->
                <a href="<?= $prevEpisode->getDownloadLink(true) ?>">
                    <!-- prev episode -->
                    <div class="prev-episode mb-15 m-md-0">

                        <!-- Poster Image -->
                        <div class="img-wrap mr-15">
                            <img src="<?= poster_uri( $prevEpisode->poster) ?>" height="75" alt="">
                        </div>
                        <!-- /. Poster Image -->

                        <div class="right">
                            <h4 class="episode-title"><?= esc( $prevEpisode->getMovieTitle()) ?></h4>
                            <span class="text-primary link-label d-block text-right">
                                 <i class="fa fa-long-arrow-left" aria-hidden="true"></i>&nbsp;
                                 <?= lang('Download.previous_episode') ?>
                            </span>
                        </div>

                    </div>
                    <!-- /. prev episode -->
                </a>
                <!-- /. previous Episode Group -->

                <?php else: ?>
                    <div></div>
                <?php endif; ?>


                <?php if (!empty($nextEpisode)): ?>

                    <!-- Next Episode Group -->
                    <a href="<?= $nextEpisode->getDownloadLink(true) ?>">
                        <!-- Next episode -->
                        <div class="next-episode mb-15 m-md-0">

                            <div class="left">
                                <h4 class="episode-title font-size-16"><?= esc(
                                        $nextEpisode->getMovieTitle()
                                    ) ?></h4>
                                <span class="text-primary link-label">
                                    <?= lang('Download.next_episode') ?>
                                    &nbsp;<i class="fa fa-long-arrow-right" aria-hidden="true"></i>
                                </span>
                            </div>

                            <!-- Poster Image -->
                            <div class="img-wrap ml-15">
                                <img src="<?= poster_uri(
                                    $nextEpisode->poster
                                ) ?>" height="75" alt="">
                            </div>
                            <!-- /. Poster Image -->

                        </div>
                        <!-- /. Next episode -->
                    </a>
                    <!-- /. Next Episode Group -->
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

            </div>
            <!-- /. Next/ Prev Episode Content -->

        </div>
        <!-- /. col-lg-8 col-xl-9 -->

    </div>
    <!-- /. row -->

</div>

<?= $this->endSection() ?>



<?= $this->section('end-of-content') ?>



<?= $this->endSection() ?>
