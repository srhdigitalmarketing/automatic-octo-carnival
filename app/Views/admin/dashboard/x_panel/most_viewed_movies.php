<div class="x_panel dashboard-library-panel">
    <div class="x_title mb-0">
        <h2><?= esc($cardTitle ?? 'Popular Videos') ?> <small><?= esc($cardPeriod ?? 'All time') ?></small></h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content p-0">
        <table class="table table-hover">
            <thead>
            <tr >
                <th class="border-top-0">Title</th>
                <th class="border-top-0">Views</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($topMovies)): ?><tr><td colspan="2" class="text-muted">Belum ada data view untuk periode ini.</td></tr><?php endif ?>
            <?php foreach ($topMovies as $movie) : ?>
            <tr>
                <td>
                    <a href="<?= admin_url("/movies/edit/{$movie->id}") ?>">
                        <?= esc( $movie->title ) ?>
                    </a>
                </td>
                <td class="text-center"> <?= number_format( $movie->views ) ?> </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>
