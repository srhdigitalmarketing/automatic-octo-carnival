    <?= $this->extend( theme_path('__layout/base') ) ?>

    <?= $this->section("content") ?>
    <div class="container-fluid">
    <div class="row align-items-center">
    <div class="col">
    <div class="section-title library">
    <h2><?= esc( $title ) ?></h2>
    </div>
    </div>
    </div>

    <div class="card d-flex align-items-md-center
    justify-content-md-between flex-column flex-md-row  p-10 my-0">

        <!-- LEFT Side content-->
        <div class="left mb-15 mb-md-0" id="filter">

            <?php if(! empty($genres)) : ?>

            <!-- Genres Dropdown Filter -->
            <div class="dropdown mb-15 mb-md-0 mr-10">

                <!-- Filter Button -->
                <button class="btn" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-hashtag" aria-hidden="true"></i>&nbsp;
                    <?= lang('General.genres') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i>
                </button>
                <!-- /. Filter Button -->

                <!-- Filter Dropdown Menu Content -->
                <div class="dropdown-menu w-500">
                    <div class="dropdown-content p-0">

                        <div class="genres-list overflow-hidden py-10">

                            <?php

                            $selectedGenres = $filtersData['genres'] ?? [];
                            if(! is_array($selectedGenres)) $selectedGenres = [];

                            ?>

                            <?php foreach ($genres as $k => $genre) :
                                $isChecked = in_array($genre['name'], $selectedGenres);
                                ?>

                                <div class="dropdown-item">
                                    <div class="custom-checkbox d-block">
                                        <?= form_checkbox([
                                            'id' => "genre-{$k}",
                                            'class' => 'genre',
                                            'value' => $genre['name'],
                                            'checked' => $isChecked
                                        ]) ?>
                                        <label class="w-full" for="genre-<?= $k ?>">
                                            <?= esc( ucfirst($genre['name']) ) ?>
                                        </label>
                                    </div>
                                </div>

                            <?php endforeach; ?>

                        </div>
                        <div class="clearfix"></div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-content">
                            <div class="text-right" role="group" >
                                <button class="btn " onclick="libraryFilter('genres')" type="button">
                                    <?= lang('Library.apply_btn') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /. Filter Dropdown Menu Content -->
            </div>
            <!-- /. Genres Dropdown Filter -->

            <?php endif; ?>


            <?php if(! empty(get_stream_quality_formats())): ?>

            <!-- Qualities Dropdown Filter -->
            <div class="dropdown mb-15 mb-md-0 mr-10">

                <!-- Filter Button -->
                <button class="btn" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-video-camera" aria-hidden="true"></i>&nbsp;
                    <?= lang('General.quality') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i> <!-- ml-5 = margin-left: 0.5rem (5px) -->
                </button>
                <!-- /. Filter Button -->

                <!-- Filter Dropdown Menu Content -->
                <div class="dropdown-menu" >

                    <?php
                        $selectedQuality = $filtersData['quality'] ?? [];
                        if(! is_array($selectedQuality)) $selectedQuality = [];
                    ?>

                    <?php foreach (get_stream_quality_formats() as $k => $format) : ?>
                    <div class="dropdown-item">
                        <div class="custom-checkbox d-block">
                            <?php $isChecked = in_array($format, $selectedQuality); ?>
                            <?= form_checkbox([
                                'id' => "quality-{$k}",
                                'class' => 'quality',
                                'value' => $format,
                                'checked' => $isChecked
                            ]) ?>
                            <label class="w-full" for="quality-<?= $k ?>">
                                <?= esc( $format ) ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="dropdown-divider"></div>
                    <div class="dropdown-content">
                        <button class="btn btn-block" onclick="libraryFilter('quality')" type="button">
                            <?= lang('Library.apply_btn') ?>
                        </button>
                    </div>
                </div>
                <!-- /. Filter Dropdown Menu Content -->
            </div>
            <!-- /. Qualities Dropdown Filter -->

            <?php endif; ?>


            <?php if(! empty($years)): ?>

            <!-- Years Dropdown Filter -->
            <div class="dropdown mb-15 mb-md-0 mr-10">

                <!-- Filter Button -->
                <button class="btn" data-toggle="dropdown" type="button" id="dropdown-toggle-btn-31" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-calendar-o" aria-hidden="true"></i>&nbsp;
                    <?= lang('General.year') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i> <!-- ml-5 = margin-left: 0.5rem (5px) -->
                </button>
                <!-- /. Filter Button -->

                <!-- Filter Dropdown Menu Content -->
                <div class="dropdown-menu h-auto overflow-y-auto"  style="max-height: 20rem">

                    <?php $selectedYear = $filtersData['year'] ?? ''; ?>

                    <?php foreach ($years as $k => $year) : ?>
                    <div class="dropdown-item">
                        <div class="custom-radio">
                            <?php $isChecked =  $year === $selectedYear ?>
                            <?= form_radio([
                                    'id' => "year-{$k}",
                                    'name' => 'year',
                                    'value' => $year,
                                    'onchange' => 'libraryFilter(\'year\')',
                                    'checked' => $isChecked
                            ]) ?>
                            <label for="year-<?= $k ?>" class="w-full">
                                <?= $year ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="dropdown-divider"></div>
                    <div class="dropdown-content">
                    </div>

                </div>
                <!-- Filter Dropdown Menu Content -->
            </div>
            <!-- /. Years Dropdown Filter -->

            <?php endif; ?>


            <?php if(! empty($countries)): ?>

            <!-- Countries Dropdown Filter -->
            <div class="dropdown mb-15 mb-md-0 mr-10">

                <!-- Filter Button -->
                <button class="btn" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-globe" aria-hidden="true"></i>&nbsp;
                    <?= lang('General.country') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i>
                </button>
                <!-- /. Filter Button -->

                <!-- Filter Dropdown Menu Content -->
                <div class="dropdown-menu h-auto overflow-y-auto"  style="max-height: 20rem">
                    <?php $selectedCountry = $filtersData['country'] ?? ''; ?>
                    <?php foreach ($countries as $k => $country) : ?>
                    <div class="dropdown-item">
                        <div class="custom-radio">
                            <?php $isChecked =  $country === $selectedCountry ?>
                            <?= form_radio([
                                'id' => "country-{$k}",
                                'name' => 'country',
                                'value' => $country,
                                'onchange' => 'libraryFilter(\'country\')',
                                'checked' => $isChecked
                            ]) ?>
                            <label for="country-<?= $k ?>" class="w-full">
                                <?= $country ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-content">
                    </div>
                </div>
                <!-- /. Filter Dropdown Menu Content -->
            </div>
            <!-- /. Countries Dropdown Filter -->

            <?php endif; ?>


            <?php if(! empty($languages)): ?>

            <!-- Languages Dropdown Filter -->
            <div class="dropdown mb-15 mb-md-0 mr-10">

                <!-- Filter Button -->
                <button class="btn" data-toggle="dropdown" type="button" >
                    <i class="fa fa-globe" aria-hidden="true"></i>&nbsp;
                    <?= lang('General.language') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i> <!-- ml-5 = margin-left: 0.5rem (5px) -->
                </button>
                <!-- /. Filter Button -->

                <!-- Filter Dropdown Menu Content -->
                <div class="dropdown-menu h-auto overflow-y-auto"  style="max-height: 20rem">
                    <?php $selectedLanguage = $filtersData['lang'] ?? ''; ?>
                    <?php foreach ($languages as $k => $language) : ?>
                        <div class="dropdown-item">
                            <div class="custom-radio">
                                <?php $isChecked =  $language === $selectedLanguage ?>
                                <?= form_radio([
                                    'id' => "lang-{$k}",
                                    'name' => 'lang',
                                    'value' => $language,
                                    'onchange' => 'libraryFilter(\'lang\')',
                                    'checked' => $isChecked
                                ]) ?>
                                <label for="lang-<?= $k ?>" class="w-full">
                                    <?= $language ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="dropdown-divider"></div>
                    <div class="dropdown-content">
                    </div>
                </div>
                <!-- /. Filter Dropdown Menu Content -->
            </div>
            <!-- /. Languages Dropdown Filter -->

            <?php endif; ?>

        </div>
        <!-- /. LEFT Side content-->

        <!-- RIGHT Side content-->
        <div class="right d-flex align-items-center justify-content-end">

            <!-- Sort Dropdown -->
            <div class="dropdown">

                <!-- Sort btn -->
                <a href="javascript:void(0)" class="font-weight-semi-bold nav-link" data-toggle="dropdown" type="button" id="dropdown-toggle-btn-112" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-sort" aria-hidden="true"></i>&nbsp;
                    <?= lang('Library.sort_btn') ?>
                    <i class="fa fa-angle-down ml-5" aria-hidden="true"></i>
                </a>
                <!-- /. Sort btn -->

                <!-- Dropdown menu -->
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-toggle-btn-1">

                    <?php
                         $sorts = [
                                 'created_at' => lang('Library.sort_added_date'),
                                 'title' => lang('Library.sort_title'),
                                 'imdb_rate' => lang('Library.sort_imdb_rating'),
                                 'year' => lang('Library.sort_release_year')
                         ];
                    ?>
                    <?php foreach ($sorts as $k => $val) :  ?>
                    <div class="dropdown-item">
                        <div class="custom-radio d-block">
                            <?php $isChecked = $sortBy == $k; ?>
                            <?= form_radio([
                                'id' => "sort-{$k}",
                                'name' => 'sort_by',
                                'value' => $k,
                                'checked' => $isChecked,
                                'onchange' => 'librarySort()'
                            ]) ?>
                            <label class="w-full" for="sort-<?= $k ?>">
                                <?= $val ?>
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
                <!-- /. Sort btn -->
            </div>
            <!-- /. Sort Dropdown -->


            <?php $sortDir = array_val($filtersData, 'sort_dir');  ?>

            <!-- Sort Dir -->
            <?php if($sortDir == 'asc'): ?>
            <a href="javascript:void(0)" onclick="sortDirChanged('desc')" class="nav-link sort_asc font-weight-semi-bold px-10">
                <i class="fa fa-sort-amount-asc" aria-hidden="true"></i>
            </a>
            <?php else: ?>
                <a href="javascript:void(0)" onclick="sortDirChanged('asc')" class="nav-link sort_desc font-weight-semi-bold px-10">
                    <i class="fa fa-sort-amount-desc" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
            <!-- /. Sort Dir -->


        </div>
        <!-- /. RIGHT Side content-->
    </div>

    <?php if($type != 'movie') : ?>

    <div class="content my-0 p-5">
        <div class="form-group mb-0">
            <?= form_checkbox([
                    'id' => 'only-parents-shows',
                    'name' => 'only-parents-shows',
                    'onchange' => "libraryFilter('parent-shows')",
                    'checked' => array_val($filtersData, 'parents') == 1
            ]) ?>
            <label for="only-parents-shows">&nbsp;
                <?= lang('Library.show_only_parent_tv_shows') ?>
            </label>
        </div>
    </div>

    <?php endif; ?>

    <?php if(! empty( $movies )):  ?>

        <div class="row row-eq-spacing mx-10">
            <?php foreach ($movies as $movie): ?>
                <div class="col-6 col-md-4 col-lg-3 col-xl-2 px-5">
                    <?php the_movie_item( $movie );?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <div class="content h-600">
            <h4> <?= lang('Library.results_not_found') ?> </h4>
        </div>

    <?php endif; ?>

    <?= $pager->links('default', 'pirate_pager') ?>


</div>


<?= $this->endSection() ?>
