<?php if(! empty($nextMovie)): ?>

    <div class="x_panel">
        <div class="x_title">
            <h2>Next Movie</h2>
            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>

        <div class="x_content">

            <div class="d-flex ">
                <img src="<?= poster_uri( $nextMovie->poster ) ?>" width="50" class="" alt="">
                <div class="w-100 ml-2">
                    <?= anchor("admin/movies/edit/{$nextMovie->id}", $nextMovie->title) ?> <br>
                    <div class="text-right  mt-2">
                        <?= anchor("admin/movies/edit/{$nextMovie->id}", 'Go to Next') ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

<?php endif; ?>