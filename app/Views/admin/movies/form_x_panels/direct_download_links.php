<div class="x_panel">
    <div class="x_title">
        <h2>Direct Download Links</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content" id="direct-dl-group-content">

        <?php if(! empty($directDownloadLinks)): ?>
            <?php foreach ($directDownloadLinks as $key => $link):
                $key += 1; ?>

                <div class="direct-dl-group">
                    <div class="form-group mb-0">
                        <?= form_label("Link {$key}:", "", ['class'=>'font-weight-bold']) ?>

                        <div class="link-meta-info mb-1">
                            <span class="requests-count">Requests :  <?= $link->requests ?></span>
                            <span class="status float-right">Status : <?= format_links_status( $link->is_broken ) ?> </span>
                        </div>

                        <div class="input-group mb-0">
                            <?= form_input([
                                'type' => 'url',
                                'name' => "direct_dl_links[{$key}][url]",
                                'class' => 'form-control link',
                                'value' => old("direct_dl_links.{$key}.url", $link->link)
                            ]) ?>

                            <span class="input-group-btn ml-2">
                                <?= form_button( [
                                    'class' => 'btn btn-light clone-direct-dl-group'
                                ],
                                    '<i class="fa fa-plus"></i>'
                                ) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col">
                            <div class="form-group form-row">
                                <?= form_label('Res. :', '', ['class'=>'control-label col-md-3']) ?>
                                <div class="col-md-9">

                                    <?= form_dropdown([
                                        'name' => "direct_dl_links[{$key}][resolution]",
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
                                        'name' => "direct_dl_links[{$key}][quality]",
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
                                                'name' => "direct_dl_links[{$key}][size_val]",
                                                'type' => 'number',
                                                'min' => 'number',
                                                'step' => 'any',
                                                'class' => 'form-control form-control-sm size-val',
                                                'value' => old("direct_dl_links.{$key}.size_val", $link->size_val)
                                        ]) ?>
                                        <?= form_dropdown([
                                                'name' => "direct_dl_links[{$key}][size_lbl]",
                                                'options' => [
                                                        'MB' => 'MB',
                                                        'GB' => 'GB'
                                                ],
                                                'selected' => old("direct_dl_links.{$key}.size_lbl", $link->size_lbl),
                                                'class' => 'form-control form-control-sm dl-size-label'
                                        ]) ?>
                                    </div>

                                </div>
                            </div>
                        </div>


                    </div>
                    <?= form_hidden("direct_dl_links[{$key}][id]", $link->id); ?>
                </div>








            <?php endforeach; ?>
        <?php else: ?>
            <?php for($i = 1; $i <= 3; $i++) : ?>

                <div class="direct-dl-group">
                    <div class="form-group mb-0">
                        <?= form_label("Link {$i}:") ?>
                        <div class="input-group mb-0">
                            <?= form_input([
                                'type' => 'url',
                                'name' => "direct_dl_links[{$i}][url]",
                                'class' => 'form-control link',
                                'value' => old("direct_dl_links.{$i}.url")
                            ]) ?>

                            <span class="input-group-btn ml-2">
                                <?= form_button( [
                                    'class' => 'btn btn-light clone-direct-dl-group'
                                ],
                                    '<i class="fa fa-plus"></i>'
                                ) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col">
                            <div class="form-group row">
                                <?= form_label('Res.:', '', ['class'=>'control-label col-md-3']) ?>
                                <div class="col-md-9">

                                    <?php

                                    echo form_dropdown([
                                            'name' => "direct_dl_links[{$i}][resolution]",
                                            'options' => getResolutionFormatOptions(),
                                            'selected' => old("direct_dl_links.{$i}.resolution"),
                                            'class' => 'form-control form-control-sm resolution'
                                    ]) ?>

                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group row">

                                <?= form_label('Quality :', '', ['class'=>'control-label col-md-4']) ?>

                                <div class="col-md-8">

                                    <?php

                                    echo form_dropdown([
                                            'name' => "direct_dl_links[{$i}][quality]",
                                            'options' => getQualityFormatOptions(),
                                            'selected' => old("direct_dl_links.{$i}.quality"),
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
                                            'name' => "direct_dl_links[{$i}][size_val]",
                                            'type' => 'number',
                                            'min' => '1',
                                            'step' => 'any',
                                            'class' => 'form-control form-control-sm size-val',
                                            'value' => old("direct_dl_links.{$i}.size_val")
                                        ]) ?>
                                        <?= form_dropdown([
                                            'name' => "direct_dl_links[{$i}][size_lbl]",
                                            'options' => [
                                                'MB' => 'MB',
                                                'GB' => 'GB'
                                            ],
                                            'selected' => old("direct_dl_links.{$i}.size_lbl"),
                                            'class' => 'form-control form-control-sm dl-size-label'
                                        ]) ?>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            <?php endfor; ?>
        <?php endif; ?>

    </div>
</div>