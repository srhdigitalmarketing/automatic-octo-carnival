<div class="x_panel">
    <div class="x_title">
        <h2>New/ Edit Genre</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <?= form_open("/admin/genres/save/{$genre->id}") ?>

        <div class="form-group">
            <?= form_label('Name *:') ?>
            <?= form_input( [
                'name' => 'name',
                'value' => old('name', $genre->name),
                'class' => 'form-control'
            ] ) ?>
        </div>


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

                <?php foreach ($translations as $translation) :  ?>

                <div class="form-group">
                    <?= form_label( lang_name( $translation->lang ) .   ' :') ?>
                    <?= form_input( [
                        'name' => "translations[$translation->lang]",
                        'value' => old("translations.{$translation->lang}", $translation->name),
                        'class' => 'form-control'
                    ] ) ?>
                </div>


                <?php endforeach; ?>



            </div>
        </div>

        <?php endif; ?>








        <div class="form-group">
            <?= form_submit([
                'class' => 'btn btn-primary btn-block'
            ], 'Save') ?>
        </div>

        <?= form_close() ?>


    </div>
</div>