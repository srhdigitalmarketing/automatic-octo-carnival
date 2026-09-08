<?php $this->extend('admin/__layout/default') ?>
<?php $this->section('content') ?>
<div class="row"><div class="col-lg-9"><div class="x_panel"><div class="x_title"><h2>Page Cache</h2><div class="clearfix"></div></div><div class="x_content">
<?= form_open(admin_url('/settings/cache/update')) ?>
<p>Pilih halaman yang dicache. Kosongkan semua pilihan untuk menonaktifkan cache halaman.</p>
<?php foreach (['embed'=>'Embed player','view'=>'View page','download'=>'Download page'] as $key=>$label): ?>
<label style="display:block;margin:12px 0"><input type="checkbox" name="cache_pages[]" value="<?= $key ?>" <?= \App\Libraries\SelectedPageCache::enabled($key)?'checked':'' ?>> <?= $label ?></label>
<?php endforeach ?>
<label for="cache-duration">Cache duration (seconds)</label><input id="cache-duration" class="form-control" name="web_page_cache_duration" type="number" min="60" max="86400" required value="<?= (int)(get_config('web_page_cache_duration') ?: 300) ?>">
<p>60–86400 detik. Cache disimpan di writable/cache menggunakan konfigurasi file bawaan.</p>
<hr><h4>Bunny CDN — Aset Player</h4>
<label for="bunny-cdn-enabled">Status Bunny CDN</label>
<select id="bunny-cdn-enabled" name="player_bunny_cdn_enabled" class="form-control">
<option value="1" <?= player_cdn_enabled() ? 'selected' : '' ?>>Aktif — oktostream.b-cdn.net</option>
<option value="0" <?= !player_cdn_enabled() ? 'selected' : '' ?>>Nonaktif — server website</option>
</select>
<p>Hanya untuk aset statis player. Pilihan ini terpisah dari cache halaman. Save juga membersihkan cache aplikasi agar perubahan diterapkan.</p>
<button type="submit" class="btn btn-primary">Save cache settings</button>
<?= form_close() ?>
<hr><p>Clear All Cache juga menghapus cache sementara grab/migrasi. Selesaikan proses tersebut sebelum membersihkan cache.</p>
<?= form_open(admin_url('/settings/cache/clean')) ?><button type="submit" class="btn btn-danger">Clear All Cache</button><?= form_close() ?>
</div></div></div></div>
<?php $this->endSection() ?>
