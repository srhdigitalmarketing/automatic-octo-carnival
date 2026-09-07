<?php if(! empty($seasons)) : ?>

    <?php foreach ($seasons as $key => $season) : ?>

<div class="x_panel" style="<?= $key != 0 ? 'height: auto;' : '';  ?>" >
    <div class="x_title">
        <h2> Season <?= esc( $season->season ) ?> </h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a href="javascript:void(0)" class="disabled">  Completed Rate:  <span class="<?= $season->completeRateColorClass ?>"><?= $season->completeRate ?> </span> </a> </li>
            <li><a href="<?= site_url("admin/episodes/new?series_id={$season->series_id}&sea={$season->season}") ?>"  class="text-secondary">
                    <i class="fa fa-plus"></i></a>
            <li><a class="collapse-link"><i class="fa fa-chevron-<?= $key != 0 ? 'down' : 'up';  ?>"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content" style="<?= $key != 0 ? 'display: none;' : '';  ?>">

        <div class="row form-row">
            <div class="col">
                <div class="item form-group">
                    <label class="col-form-label col label-align" for="first-name">Total Episodes
                    </label>
                    <div class="col">
                        <?= form_input( [
                            'type' => 'number',
                            'name' => "total_season_episodes[{$season->id}]",
                            'value' => old('total_episodes', $season->total_episodes),
                            'class' => 'form-control'
                        ] ) ?>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="item form-group">
                    <label class="col-form-label col label-align" for="first-name">Completed Episodes
                    </label>
                    <div class="col">
                        <?= form_input( [
                            'type' => 'number',
                            'value' => count( $season->episodes() ),
                            'class' => 'form-control',
                            'disabled' => 'disabled'
                        ] ) ?>
                    </div>
                </div>
            </div>
        </div>


        <?php if( $season->hasEpisodes() ): ?>

        <table class="table table-bordered text-center data-list-table">
            <thead>
            <tr>
                <th>Episode</th>
                <th class="text-left">Name</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($season->episodes() as $episode) :  ?>
            <tr>
                <th scope="row"> <?= $episode->episode ?> </th>
                <td class="text-left"> <?= esc( $episode->title ) ?> </td>
                <td>
                    <a href="<?= site_url() ?>" class="text-info">View</a>
                    <a href="<?= admin_url("/episodes/edit/{$episode->id}") ?>" class="text-warning ml-2">Edit</a>
                    <a href="<?= admin_url("/episodes/delete/{$episode->id}") ?>" class="text-danger ml-2">Del</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    </div>
</div>

        <?php endforeach; ?>
<?php endif; ?>