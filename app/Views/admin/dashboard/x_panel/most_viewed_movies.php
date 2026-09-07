<div class="x_panel dashboard-library-panel">
    <div class="x_title mb-0">
        <h2>Popular Videos <small>Most viewed in your library</small></h2>
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
                <th class="border-top-0"">Views</th>
            </tr>
            </thead>
            <tbody>
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
