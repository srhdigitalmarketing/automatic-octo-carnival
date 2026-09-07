<div class="form-group">
    <label for="" class="font-size-12 text-muted ">
        <?= lang('Captcha.math_label') ?>
    </label>
    <div class="d-flex align-items-center  <?= $isCenter ? 'justify-content-center' : '' ?>">
        <img src="<?= site_url('/captcha') ?>" class="mr-5" alt="captcha-image">&nbsp;
        <?= form_input([
            'type' => 'number',
            'name' => 'captcha',
            'min'  => 1,
            'class' => 'form-control d-inline-block w-100',
            'placeholder' => lang('Captcha.math_placeholder'),
            'required' => 'required'
        ]) ?>
    </div>
</div>
