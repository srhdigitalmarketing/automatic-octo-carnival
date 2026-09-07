<?php if(! empty($genres)): ?>

<div class="x_panel">
    <div class="x_title">
        <h2>Genres</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content">

        <div class="form-group">
            <label for="">Select genres:</label>
            <?php

            $options = [];
            $selected = array_keys($series->genres());

            foreach ($genres as $genre) {
                $options[$genre['id']] = trim($genre['name']);
            }

            ?>
            <?= form_multiselect([
                'name' => 'genres[]',
                'options' => $options,
                'class' => 'form-control',
                'id' => 'select-genres',
                'selected' => $selected
            ]) ?>
        </div>

    </div>
</div>

<?php endif; ?>