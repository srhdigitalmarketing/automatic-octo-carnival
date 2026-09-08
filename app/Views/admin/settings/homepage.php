<?php $this->extend('admin/__layout/default') ?>
<?php $this->section('content') ?>
<div class="row"><div class="col-lg-9"><div class="x_panel"><div class="x_title"><h2>Index Homepage <small>Video storage</small></h2><div class="clearfix"></div></div><div class="x_content">
<?= form_open(admin_url('/settings/homepage/update')) ?>
<div class="form-group"><label for="homepage-active">Homepage status</label><select id="homepage-active" name="homepage_active" class="form-control"><option value="1">Active — Video storage homepage</option><option value="0" <?= get_config('homepage_active') === false || get_config('homepage_active') === 0 || get_config('homepage_active') === '0' ? 'selected' : '' ?>>Non active — 403 Forbidden</option></select></div>
<div class="form-group"><label for="homepage-heading">Heading</label><input id="homepage-heading" class="form-control" name="homepage_heading" required maxlength="160" value="<?= esc(old('homepage_heading',get_config('homepage_heading') ?: 'Your videos. One organized space.'),'attr') ?>"></div>
<div class="form-group"><label for="homepage-intro">Description</label><textarea id="homepage-intro" class="form-control" name="homepage_intro" required maxlength="500" rows="3"><?= esc(old('homepage_intro',get_config('homepage_intro') ?: 'Store, organize, and share your video collection from one place.')) ?></textarea></div>
<p>Non active mengembalikan HTTP 403 pada homepage. Halaman admin dan embed player tetap dapat digunakan.</p>
<button class="btn btn-primary" type="submit">Save changes</button><a class="btn btn-default" href="<?= site_url('/') ?>" target="_blank" rel="noopener">View homepage</a>
<?= form_close() ?></div></div></div></div>
<?php $this->endSection() ?>
