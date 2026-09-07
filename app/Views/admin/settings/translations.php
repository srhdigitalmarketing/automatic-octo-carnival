<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-9">


        <?= form_open('/admin/settings/translations/update', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">
            <div class="x_title">
                <h2>Main Language</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="form-group row mb-5">
                    <label class="control-label col-md-3">Main Language</label>
                    <div class="col-md-9">
                        <?= form_dropdown([
                            'name' => 'main_language',
                            'class' => 'form-control w-auto',
                            'options' => get_lang_list( true ),
                            'selected' => get_main_language()
                        ])  ?>

                    </div>
                </div>

            </div>
        </div>
        <div class="x_panel">
            <div class="x_title">
                <h2>Multi Languages</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="form-group row">
                    <label class="control-label col-md-3">Multi Language</label>
                    <div class="col-md-9">
                        <div class="checkbox">
                            <label>
                                <?= form_checkbox('is_multi_lang','1', get_config('is_multi_lang')) ?>
                                Enable/ Disable
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">Select Languages</label>
                    <div class="col-md-9">
                        <div class="row">
                            <?php foreach (get_lang_list(true) as $langCode => $langName) : ?>
                                <label class="col-lg-6">
                                    <?= form_checkbox("lang[$langCode]",$langCode, is_selected_language( $langCode )) ?>
                                    <?= $langName ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    </div>
                </div>




                <div class="text-right">
                    <?= form_button([
                        'type' => 'submit',
                        'class' => 'btn btn-primary'
                    ], 'update') ?>
                </div>



            </div>
        </div>

        <?= form_close() ?>

        <?php if(! empty( $crashedLanguages )) : ?>

        <?= form_open('/admin/settings/translations/remove_lang', [ 'method' => 'post', 'class' => 'form-horizontal form-label-left' ] ) ?>

        <div class="x_panel">
            <div class="x_title">
                <h2>Remove Translation</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                    </li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <div class="alert alert-danger">
                    Remove translation from all movies and shows. when you removed these , you can not recover again.
                </div>

                <div class="form-group row">
                    <label class="control-label col-md-3">Select Language</label>
                    <div class="col-md-9">
                        <?= form_dropdown([
                            'name' => 'language',
                            'class' => 'form-control w-auto',
                            'options' => $crashedLanguages
                        ])  ?>
                    </div>
                </div>

                <div class="text-right">
                    <?= form_button([
                        'type' => 'submit',
                        'class' => 'btn btn-danger'
                    ], 'remove') ?>
                </div>



            </div>
        </div>

        <?= form_close() ?>

        <?php endif; ?>


    </div>
</div>



<?php $this->endSection() ?>
