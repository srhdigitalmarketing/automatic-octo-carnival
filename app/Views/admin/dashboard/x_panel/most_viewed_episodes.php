<div class="x_panel">
    <div class="x_title mb-0">
        <h2>Episodes <small>Most Viewed</small></h2>
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
            <?php foreach ($topEpisodes as $movie) : ?>
            <tr>
                <td>
                    <a href="<?= admin_url("/movies/edit/{$movie->id}") ?>">
                        <?= esc( $movie->getMovieTitle() ) ?>
                    </a>
                </td>
                <td class="text-center"> <?= number_format( $movie->views ) ?> </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>