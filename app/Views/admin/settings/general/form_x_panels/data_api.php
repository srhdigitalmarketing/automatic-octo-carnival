<div class="x_panel">
    <div class="x_title">
        <h2>Data APIs</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group row">
            <label class="control-label col-md-3">Tmdb api  <sup class="text-primary">Primary</sup> </label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'tmdb_api_key',
                    'class' => 'form-control',
                    'value' => get_config('tmdb_api_key')
                ]) ?>
                <small>This is main api for get movies/shows data via imdb or tmdb ids
                    <br>   to get api key                      <a href="https://developers.themoviedb.org/3/getting-started/introduction" target="_blank"> click here</a>

                </small>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-md-3">Omdb api</label>
            <div class="col-md-9">
                <?= form_input([
                    'type' => 'text',
                    'name' => 'omdb_api_key',
                    'class' => 'form-control',
                    'value' => get_config('omdb_api_key')
                ]) ?>
                <small>This is secondary api for just get data not found in tmdb
                    <br>   to get api key                      <a href="http://www.omdbapi.com/apikey.aspx" target="_blank"> click here</a>
                </small>
            </div>
        </div>




    </div>
</div>