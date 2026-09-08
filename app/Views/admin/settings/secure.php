<?php $this->extend('admin/__layout/default') ?>
<?php $this->section('content') ?>
<div id="secure-backups" data-url="<?= esc(admin_url('/settings/secure/run'),'attr') ?>" data-download="<?= esc(admin_url('/settings/secure/download'),'attr') ?>" data-token="<?= esc($token,'attr') ?>">
<div class="alert alert-info">Arsip tersimpan di <strong>writable/secure-backups</strong>. Download salinan ke perangkat lain. Upload menyimpan arsip saja; file aktif dan database tidak ditimpa.</div>
<?php if ($error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif ?>
<div class="row">
 <div class="col-lg-6"><div class="x_panel p-4"><h5 class="font-weight-bold">Backup Files</h5><p class="text-muted">Buat arsip ZIP untuk disimpan atau dipindahkan.</p>
 <label for="secure-scope">Cakupan file</label><select id="secure-scope" class="form-control mb-3"><option value="uploads">public/uploads — banner dan media lokal</option><option value="application">File aplikasi dan uploads</option></select>
 <small class="d-block text-muted mb-3">Backup aplikasi menyertakan konfigurasi .env jika ada; tidak menyertakan .git, node_modules, atau writable. File di R2 tidak ikut diunduh.</small>
 <button type="button" class="btn btn-primary" data-secure-action="files"><i class="fa fa-file-archive-o mr-1"></i> Backup Files</button></div></div>
 <div class="col-lg-6"><div class="x_panel p-4"><h5 class="font-weight-bold">Backup Database</h5><p class="text-muted">Ekspor seluruh database MySQL/MariaDB ke file SQL.</p><p class="small">Mencakup tabel, data, view, trigger, routine dan event. Menggunakan mysqldump yang terpasang di aaPanel.</p>
 <button type="button" class="btn btn-primary" data-secure-action="database"><i class="fa fa-database mr-1"></i> Backup Database</button></div></div>
</div>
<div class="x_panel p-4"><h5 class="font-weight-bold">Upload Backup</h5><p class="text-muted">Simpan arsip ZIP atau SQL, maksimal 512 MB atau sesuai batas upload PHP/aaPanel yang lebih kecil.</p>
 <form id="secure-upload"><label for="secure-file">File backup</label><input id="secure-file" name="backup_file" type="file" accept=".zip,.sql" required class="form-control"><button class="btn btn-primary mt-3" type="submit"><i class="fa fa-upload mr-1"></i> Upload Backup</button></form>
</div>
<div id="secure-progress" class="x_panel p-4" hidden><div class="d-flex align-items-center mb-3"><span id="secure-spinner" class="spinner-border spinner-border-sm text-primary mr-2" aria-hidden="true"></span><strong id="secure-status" role="status" aria-live="polite">Memproses…</strong></div><div class="progress" style="height:10px"><div id="secure-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%" role="progressbar" aria-label="Proses backup"></div></div><small class="text-muted d-block mt-2">Biarkan halaman tetap terbuka sampai selesai.</small></div>
<div class="x_panel p-4"><h5 class="font-weight-bold mb-3">Daftar Backup</h5><div class="table-responsive"><table class="table"><thead><tr><th>File</th><th>Jenis</th><th>Dibuat</th><th>Ukuran</th><th>Aksi</th></tr></thead><tbody id="secure-list"></tbody></table></div></div>
<script type="application/json" id="secure-initial"><?= json_encode($entries,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
</div>
<script defer src="<?= site_url('/admin-assets/js/secure-backups.js?v=1') ?>"></script>
<?php $this->endSection() ?>
