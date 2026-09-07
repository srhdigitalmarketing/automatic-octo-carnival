
<?php if(! empty( $translations )): ?>

    <div class="x_panel">
        <div class="x_title">
            <h2>Translations</h2>
            <ul class="nav navbar-right panel_toolbox">
                <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>

        <div class="x_content">

            <?php foreach ( $translations as $translation) : ?>

                <div class="x_panel">
                    <div class="x_title">
                        <h2> <?= esc( $translation->getLangName() ) ?> </h2>
                        <ul class="nav navbar-right panel_toolbox">
                            <li><a class="collapse-link"><i class="fa fa-chevron-down"></i></a>
                            </li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content " style="display: none;">

                        <div class="form-group">
                            <?= form_label('Title :') ?>
                            <?= form_input( [
                                'name' => "translations[$translation->lang][title]",
                                'value' => old("translations.{$translation->lang}.title", $translation->title),
                                'class' => 'form-control'
                            ] ) ?>
                        </div>

                        <div class="form-group">
                            <?= form_label('Content :') ?>
                            <?= form_textarea( [
                                'name' => "translations[$translation->lang][content]",
                                'class' => 'form-control summernote',
                                'rows' => 5
                            ], old("translations.{$translation->lang}.content", (string) base64_decode( $translation->content ))) ?>
                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>
