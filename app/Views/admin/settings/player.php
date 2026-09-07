<?php
$buttonColor = old('player_button_color', get_config('player_button_color') ?: '#d28a15');
$iconColor = old('player_icon_color', get_config('player_icon_color') ?: '#ffffff');
$buttonStyle = old('player_button_style', get_config('player_button_style') ?: 'solid');
$buttonIcon = old('player_button_icon', get_config('player_button_icon') ?: 'play');
$buttonSize = (int) old('player_button_size', get_config('player_button_size') ?: 88);
$buttonSize = $buttonSize >= 48 && $buttonSize <= 140 ? $buttonSize : 88;
$iconClasses = ['play' => 'fa-play', 'play-circle' => 'fa-play-circle', 'film' => 'fa-film', 'bolt' => 'fa-bolt'];
$previewIcon = $iconClasses[$buttonIcon] ?? $iconClasses['play'];
?>
<?php $this->extend('admin/__layout/default') ?>

<?php $this->section('content') ?>

<div class="row">
    <div class="col-lg-10 col-xxl-8">
        <?= form_open('/admin/settings/player/update', ['method' => 'post', 'class' => 'form-horizontal form-label-left player-settings-form']) ?>

        <section class="x_panel player-settings-panel">
            <div class="x_title">
                <h2>Player appearance</h2>
                <ul class="nav navbar-right panel_toolbox"><li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li></ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <p class="player-settings-panel__intro">Customize only the play overlay shown before the video starts. The player controls, server panel, reporting panel, and embed-link panel are disabled.</p>

                <div class="player-settings-layout">
                    <div class="player-settings-fields">
                        <div class="form-group row">
                            <label class="control-label col-md-4" for="player-button-style">Button style</label>
                            <div class="col-md-8">
                                <?= form_dropdown('player_button_style', ['solid' => 'Solid fill', 'outline' => 'Outline'], $buttonStyle, ['id' => 'player-button-style', 'class' => 'form-control player-preview-control']) ?>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="control-label col-md-4" for="player-button-icon">Play icon</label>
                            <div class="col-md-8">
                                <?= form_dropdown('player_button_icon', ['play' => 'Play', 'play-circle' => 'Play circle', 'film' => 'Film', 'bolt' => 'Bolt'], $buttonIcon, ['id' => 'player-button-icon', 'class' => 'form-control player-preview-control']) ?>
                            </div>
                        </div>
                        <div class="form-group row player-settings-color-row">
                            <label class="control-label col-md-4" for="player-button-color">Button color</label>
                            <div class="col-md-8 player-settings-color-input">
                                <?= form_input(['type' => 'color', 'id' => 'player-button-color', 'name' => 'player_button_color', 'class' => 'player-preview-control', 'value' => $buttonColor]) ?>
                                <code class="player-color-value" data-for="player-button-color"><?= esc($buttonColor) ?></code>
                            </div>
                        </div>
                        <div class="form-group row player-settings-color-row">
                            <label class="control-label col-md-4" for="player-icon-color">Icon color</label>
                            <div class="col-md-8 player-settings-color-input">
                                <?= form_input(['type' => 'color', 'id' => 'player-icon-color', 'name' => 'player_icon_color', 'class' => 'player-preview-control', 'value' => $iconColor]) ?>
                                <code class="player-color-value" data-for="player-icon-color"><?= esc($iconColor) ?></code>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="control-label col-md-4" for="player-button-size">Button size</label>
                            <div class="col-md-8 player-size-control">
                                <input type="range" id="player-button-size" name="player_button_size" class="player-preview-control" min="48" max="140" value="<?= $buttonSize ?>">
                                <output for="player-button-size" id="player-button-size-value"><?= $buttonSize ?> px</output>
                            </div>
                        </div>
                    </div>

                    <aside class="player-preview" aria-label="Player appearance preview">
                        <span class="player-preview__label">Live preview</span>
                        <div class="player-preview__stage">
                            <button type="button" class="player-preview__button" data-style="<?= esc($buttonStyle) ?>" aria-label="Play preview">
                                <i class="fa <?= esc($previewIcon) ?>" aria-hidden="true"></i>
                            </button>
                        </div>
                        <small>Changes here are visible before saving.</small>
                    </aside>
                </div>
            </div>
        </section>

        <div class="text-right mb-3">
            <?= form_button(['type' => 'submit', 'class' => 'btn btn-primary'], 'Save player appearance') ?>
        </div>
        <?= form_close() ?>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.player-settings-form');
    if (! form) return;

    var preview = document.querySelector('.player-preview__button');
    var iconMap = {play: 'fa-play', 'play-circle': 'fa-play-circle', film: 'fa-film', bolt: 'fa-bolt'};
    var controls = form.querySelectorAll('.player-preview-control');

    function syncPreview() {
        var buttonColor = document.getElementById('player-button-color').value;
        var iconColor = document.getElementById('player-icon-color').value;
        var style = document.getElementById('player-button-style').value;
        var icon = document.getElementById('player-button-icon').value;
        var size = document.getElementById('player-button-size').value;

        preview.style.setProperty('--preview-button-color', buttonColor);
        preview.style.setProperty('--preview-icon-color', iconColor);
        preview.style.setProperty('--preview-button-size', size + 'px');
        preview.style.setProperty('--preview-icon-size', Math.round(parseInt(size, 10) * 0.38) + 'px');
        preview.setAttribute('data-style', style);
        preview.querySelector('i').className = 'fa ' + (iconMap[icon] || iconMap.play);
        document.getElementById('player-button-size-value').textContent = size + ' px';
        document.querySelectorAll('.player-color-value').forEach(function (item) {
            item.textContent = document.getElementById(item.getAttribute('data-for')).value;
        });
    }

    controls.forEach(function (control) { control.addEventListener('input', syncPreview); });
    syncPreview();
});
</script>
<?php $this->endSection() ?>
