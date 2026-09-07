<div class="x_panel">
    <div class="x_title">
        <h2>TV Show</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group">
            <label for="">Select TV Show:</label>
            <?php
                $options = [''=>''];
                $selected = $movie->series()->id;
                foreach ($series as  $show){
                    $options[$show['id']] = $show['title'];

                    if(isset( $activeSeriesId ) && $activeSeriesId == $show['id'])
                        $selected = $activeSeriesId;

                }
                $seriesIdField = [
                    'name' => 'series_id',
                    'options' => $options,
                    'class' => 'select2_multiple form-control',
                    'id' => 'select-tv-show',
                    'selected' =>  [ $selected ]
                ];

                echo form_dropdown( $seriesIdField )
            ?>

            <?php if(! empty($movie->id)): ?>
                <div class="text-right mt-1">
                    <a href="<?= site_url( "admin/series/edit/{$movie->series()->id}" ) ?>"  class="">Go to TV Show</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-group row">
            <div class="col">
                <?= form_label('Season *:') ?>
                <?php
                $seasonNum = empty($movie->id) ? $movie->nextSeason : $movie->season()->season ;
                $imdbIdField = [
                    'name' => 'season',
                    'value' => old('season', $seasonNum),
                    'class' => 'form-control'
                ];

                echo form_input( $imdbIdField ) ?>
            </div>
            <div class="col">
                <?= form_label('Episode *:') ?>
                <?php
                $episodeNum = empty($movie->id) ? $movie->nextEpisode : $movie->episode ;
                $imdbIdField = [
                    'name' => 'episode',
                    'value' => old('episode', $episodeNum),
                    'class' => 'form-control'
                ];

                echo form_input( $imdbIdField ) ?>
            </div>

        </div>




    </div>
</div>