<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>





<div class="row">
    <div class="col-lg-8">

        <div class="x_panel">
            <div class="x_content">
                <?= form_open("/admin/links/update/{$link->id}") ?>

                    <div class="direct-dl-group">

                        <div class="form-group">
                            <?= form_label("Movie: ") ?>

                            <div class="input-group mb-0">
                                <?= form_input([
                                    'type' => 'text',
                                    'class' => 'form-control link',
                                    'value' =>  $movie->title,
                                    'disabled' => 'disabled'
                                ]) ?>

                                <span class="input-group-btn ml-2">
                                    <a href="<?= admin_url("movies/edit/{$link->movie_id}") ?>" target="_blank" class="btn btn-light clone-direct-dl-group">
                                    <i class="fa fa-external-link"></i>
                                    </a>

                            </span>
                            </div>


                        </div>

                        <div class="form-group">
                            <?= form_label("Link: ") ?>


                            <?= form_input([
                                'type' => 'url',
                                'name' => "link",
                                'class' => 'form-control link',
                                'value' => old("link", $link->link)
                            ]) ?>
                        </div>

                        <?php if($link->type != 'stream'): ?>

                        <div class="row">

                            <div class="col">
                                <div class="form-group form-row">
                                    <?= form_label('Res. :', '', ['class'=>'control-label col-md-3']) ?>
                                    <div class="col-md-9">

                                        <?= form_dropdown([
                                            'name' => "resolution",
                                            'options' => getResolutionFormatOptions(),
                                            'selected' => $link->resolution,
                                            'class' => 'form-control form-control-sm resolution'
                                        ]) ?>

                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group row">

                                    <?= form_label('Quality :', '', ['class'=>'control-label col-md-4']) ?>

                                    <div class="col-md-8">

                                        <?= form_dropdown([
                                            'name' => "quality",
                                            'options' => getQualityFormatOptions(),
                                            'selected' => $link->quality,
                                            'class' => 'form-control form-control-sm quality'
                                        ]) ?>

                                    </div>
                                </div>
                            </div>

                            <div class="col">
                                <div class="form-group row">

                                    <?= form_label('Size:', '', ['class'=>'control-label col-md-3']) ?>

                                    <div class="col-md-9">

                                        <div class="input-group">
                                            <?= form_input([
                                                'name' => "size_val",
                                                'type' => 'number',
                                                'min' => '0',
                                                'step' => 'any',
                                                'class' => 'form-control form-control-sm size-val',
                                                'value' => old("size_val", $link->size_val)
                                            ]) ?>
                                            <?= form_dropdown([
                                                'name' => "size_lbl",
                                                'options' => [
                                                    'MB' => 'MB',
                                                    'GB' => 'GB'
                                                ],
                                                'selected' => old("size_lbl", $link->size_lbl),
                                                'class' => 'form-control form-control-sm dl-size-label'
                                            ]) ?>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        </div>

                        <?php endif; ?>

                        <?php if($link->type === 'stream'): ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <?= form_label('Host priority') ?>
                                    <?= form_input([
                                        'name' => 'host_priority',
                                        'type' => 'number',
                                        'min' => '0',
                                        'class' => 'form-control',
                                        'value' => old('host_priority', $link->host_priority ?? 100)
                                    ]) ?>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <?php
                                    $serverHost = parse_url((string) $link->link, PHP_URL_HOST);
                                    $serverHost = is_string($serverHost) && $serverHost !== '' ? preg_replace('/^www\./i', '', $serverHost) : 'Unknown server';
                                    $isUnavailable = (bool) ($link->is_broken ?? false) || ! empty($link->last_error);
                                    $isHealthy = ! $isUnavailable && ! empty($link->last_checked_at) && ! empty($link->last_success_at);
                                    ?>
                                    <?= form_label('Server status') ?>
                                    <div class="stream-server-status">
                                        <span class="stream-server-host"><i class="fa fa-server"></i> <?= esc($serverHost) ?></span>
                                        <?php if ($isUnavailable): ?>
                                            <span class="stream-server-badge is-broken" title="The last availability check failed"><i class="fa fa-times"></i> Unavailable</span>
                                        <?php elseif ($isHealthy): ?>
                                            <span class="stream-server-badge is-healthy" title="Video link passed the latest check"><i class="fa fa-check"></i> Healthy</span>
                                        <?php else: ?>
                                            <span class="stream-server-badge is-unchecked" title="Waiting for the first availability check"><i class="fa fa-clock-o"></i> Not checked</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <ul class="list-unstyled ">
                            <li class="mb-1"> <b>Type: </b>   <?= $link->type ?> </li>
                            <li class="mb-1"> <b>Created At: </b>   <?= format_date_time($link->created_at) ?></li>
                            <li> <b>Updated At: </b>   <?= format_date_time($link->updated_at) ?></li>
                        </ul>

                        <div class="text-right mt-3">
                            <a href="<?= previous_url() ?>" class="btn btn-secondary">Cancel</a>
                            <?= form_button([
                                    'type' => 'submit',
                                    'class' => 'btn btn-primary'
                            ], 'Update') ?>
                        </div>

                    </div>

                <?= form_close() ?>
            </div>
        </div>

    </div>
</div>





<?php $this->endSection() ?>
