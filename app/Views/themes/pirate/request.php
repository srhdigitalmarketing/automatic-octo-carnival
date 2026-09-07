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
    <!-- row -->
    <div class="row">
    <div class="col">
    <div class="content request-">
    <div class="tabs requesttabs bg-dark-light font-weight-semi-bold w-auto d-inline-flex ">
    <a href="javascript:void(0)" class="btn" data-target="find-movies" onclick="Requests.changeType('movie')">
    <i class="bi bi-film"></i>
    &nbsp; <?= lang('Request.movie_tab_btn') ?>
    </a>
    <a href="javascript:void(0)" class="nav-link d-inline-block" onclick="Requests.changeType('tv')" data-target="find-shows">
    <i class="bi bi-tv"></i>
    &nbsp; <?= lang('Request.tv_shows_tab_btn') ?>
    </a>
    </div>
    </div>

    <?= display_alerts() ?>
    
    <div class="card p-0 mt-0">

    <!-- Movies Tab Content -->
    <div class="tab-content active" id="find-movies">
    <!-- Form Group -->
    <div class="form-group mb-0">
    <?= form_input([
    'class' => 'form-control form-control-lg movie-suggest-input',
    'placeholder' => lang('Request.movie_input_placeholder'),
    'onkeyup' => 'Requests.find()',
    'autofocus' => 'true'
    ]) ?>
    </div>
    </div>
    <!-- /. Movies Tab Content -->
    
    <!-- TV Shows Tab Content -->
    <div class="tab-content" id="find-shows">
                    <!-- Form Group -->
                    <div class="form-group mb-0">
                        <?= form_input([
                            'class' => 'form-control form-control-lg tv-suggest-input',
                            'placeholder' => lang('Request.tv_shows_input_placeholder'),
                            'onkeyup' => 'Requests.find()'
                        ]) ?>
                    </div>
                    <!-- /. Form Group -->
                </div>
                <!-- /. TV Shows Tab Content -->

            </div>

            <div class="content mx-10">

                <!-- Suggestion Results -->
                <div class="row row-eq-spacing" id="suggest-results"></div>
                <!-- /. Suggestion Results -->

                <!-- Results Not Found -->
                <div class="results-not-found text-center" style="display: none">
                    <h4 class="ve-text">
                        <?= lang('Request.content_not_found') ?>
                    </h4>
                </div>
                <!-- /. Results Not Found -->

                <!-- Content Loading -->
                <div class="content-loading text-center" style="display:none;">
                <div class="loader">
                <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
                </div>
                </div>
                <!-- /. Content Loading -->

            </div>



        </div>

    </div>
    <!-- /. row -->



</div>
<?= $this->endSection() ?>