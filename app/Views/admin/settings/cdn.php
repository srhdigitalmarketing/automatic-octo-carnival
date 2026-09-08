<?php $this->extend('admin/__layout/default') ?>
<?php $this->section('content') ?>
<div class="row"><div class="col-lg-9"><div class="x_panel">
    <div class="x_title"><h2>CDN — Aset Player</h2><div class="clearfix"></div></div>
    <div class="x_content" id="cdn-settings">
        <?= form_open(admin_url('/settings/cdn/update')) ?>
        <div class="form-group">
            <label for="cdn-hostname">CDN hostname</label>
            <input id="cdn-hostname" name="player_cdn_hostname" class="form-control" maxlength="253" required placeholder="a.cdn.com" value="<?= esc(old('player_cdn_hostname', player_cdn_hostname()), 'attr') ?>">
            <small class="form-text text-muted">Hostname saja, contoh a.cdn.com. Siapkan HTTPS dan origin website Anda pada layanan CDN.</small>
        </div>
        <div class="form-group">
            <label for="cdn-enabled">Status CDN</label>
            <select id="cdn-enabled" name="player_bunny_cdn_enabled" class="form-control">
                <option value="1" <?= (string) old('player_bunny_cdn_enabled', player_cdn_enabled() ? '1' : '0') === '1' ? 'selected' : '' ?>>Aktif — hostname CDN di atas</option>
                <option value="0" <?= (string) old('player_bunny_cdn_enabled', player_cdn_enabled() ? '1' : '0') === '0' ? 'selected' : '' ?>>Nonaktif — server website</option>
            </select>
            <small class="form-text text-muted">CDN mempercepat aset statis player melalui path /themes/. Video tetap menggunakan stream host yang dipilih sistem.</small>
        </div>
        <div class="text-right"><button type="submit" class="btn btn-primary">Save CDN settings</button></div>
        <?= form_close() ?>
    </div>
</div></div></div>
<?php $this->endSection() ?>
