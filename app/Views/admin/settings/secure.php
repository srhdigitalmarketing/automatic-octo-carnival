<?php $this->extend('admin/__layout/default') ?>
<?php $this->section('content') ?>
<div id="secure-backups" data-url="<?= esc(admin_url('/settings/backup/run'),'attr') ?>" data-download="<?= esc(admin_url('/settings/backup/download'),'attr') ?>" data-token="<?= esc($token,'attr') ?>">
<div class="alert alert-info">Arsip tersimpan di <strong>writable/secure-backups</strong>. Download salinan ke perangkat lain. Upload menyimpan arsip. Untuk memulihkan file atau database, pilih <strong>Restore</strong> pada daftar backup lalu konfirmasikan.</div>
<?php if ($error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif ?>
<div class="x_panel p-4">
 <h5 class="font-weight-bold">Tujuan Backup</h5>
 <label for="secure-destination">Simpan backup baru ke</label>
 <select id="secure-destination" class="form-control mb-2"><option value="local">Lokal saja</option><option value="ftp">FTP / FTPS</option><option value="drive">Google Drive</option><option value="s3">S3 / Cloudflare R2 / Backblaze B2</option></select>
 <small class="text-muted">Berlaku untuk Backup Files, Full Backup dan Backup Database. Salinan lokal tetap disimpan. Untuk arsip yang sudah ada, pilih tujuan lalu klik Kirim pada daftar backup.</small>
 <p class="small text-muted mt-3">Fitur ini tanpa biaya lisensi tambahan. Kuota penyimpanan mengikuti akun: Google Drive hingga 15 GB bersama layanan Google lainnya; R2 dan Backblaze B2 menyediakan kuota gratis terbatas. FTP memakai kapasitas server Anda. Pemakaian di atas kuota dapat berbayar.</p>
 <details class="mt-3"><summary class="font-weight-bold">Pengaturan FTP, Google Drive dan S3</summary>
 <p class="text-muted mt-3">Isi dan simpan pengaturan sebelum mengirim backup. Kolom rahasia yang kosong mempertahankan nilai tersimpan. Simpan backup hanya pada akun dan folder pribadi.</p>
 <div class="row">
<?php
$remoteForms=[
 'ftp'=>['FTP / FTPS',[
  'host'=>['Hostname','text','ftp.example.com'], 'port'=>['Port','number','21'],
  'username'=>['Username','text',''], 'password'=>['Password','password',''], 'directory'=>['Folder tujuan (relatif login FTP)','text','backups']]],
 'drive'=>['Google Drive (OAuth)',[
  'client_id'=>['OAuth Client ID','text',''], 'client_secret'=>['Client Secret','password',''],
  'refresh_token'=>['Refresh Token','password',''], 'folder_id'=>['Folder ID','text','ID dari URL folder Google Drive']]],
 's3'=>['S3 / R2 / Backblaze B2',[
  'endpoint'=>['Endpoint HTTPS','url','https://s3.ap-southeast-1.amazonaws.com'], 'region'=>['Region','text','ap-southeast-1'],
  'bucket'=>['Bucket','text',''], 'prefix'=>['Folder / prefix','text','backups'], 'access_key'=>['Access Key ID','password',''],
  'secret_key'=>['Secret Access Key','password',''], 'session_token'=>['Session Token (opsional)','password','']]],
];
foreach($remoteForms as $provider=>$form): $config=$remote[$provider]??[];
?>
 <div class="col-xl-4 mb-3"><form id="secure-remote-<?= esc($provider,'attr') ?>" data-remote-provider="<?= esc($provider,'attr') ?>" class="border rounded p-3 h-100">
  <h6 class="font-weight-bold"><?= esc($form[0]) ?></h6>
  <?php if($provider==='ftp'): ?><label for="remote-ftp-protocol">Protokol</label><select name="protocol" id="remote-ftp-protocol" class="form-control mb-2"><option value="ftps" <?= ($config['protocol']??'ftps')==='ftps'?'selected':'' ?>>FTPS (TLS, direkomendasikan)</option><option value="ftp" <?= ($config['protocol']??'')==='ftp'?'selected':'' ?>>FTP tanpa enkripsi</option></select><?php endif ?>
  <?php foreach($form[1] as $field=>$meta): $value=$meta[1]==='password'?'':($config[$field]??($field==='port'?'21':'')); ?>
  <label class="mt-2" for="remote-<?= esc($provider.'-'.$field,'attr') ?>"><?= esc($meta[0]) ?></label>
  <input id="remote-<?= esc($provider.'-'.$field,'attr') ?>" name="<?= esc($field,'attr') ?>" type="<?= esc($meta[1],'attr') ?>" class="form-control" value="<?= esc($value,'attr') ?>" placeholder="<?= esc(!empty($config[$field.'_saved'])?'Tersimpan - kosongkan untuk mempertahankan':$meta[2],'attr') ?>" autocomplete="<?= $meta[1]==='password'?'new-password':'off' ?>">
  <?php endforeach ?>
  <?php if($provider==='s3'): ?><label class="small mt-2"><input type="checkbox" name="clear_session_token" value="1"> Hapus Session Token tersimpan</label><p class="small text-muted mt-2">AWS S3 atau endpoint S3-compatible seperti R2. Gunakan bucket privat dan izin ListBucket, PutObject serta GetObject untuk restore. Untuk R2, region: auto.</p><?php endif ?>
  <?php if($provider==='drive'): ?><p class="small text-muted mt-2">Aktifkan Drive API pada proyek Google Anda. Gunakan OAuth Client ID, Client Secret dan Refresh Token dari akun yang memiliki akses folder tujuan. Refresh Token memerlukan akses offline dan izin Drive.</p><?php endif ?>
  <?php if($provider==='ftp'): ?><p class="small text-muted mt-2">FTPS menggunakan TLS eksplisit, biasanya port 21. FTP biasa mengirim data dan password tanpa enkripsi. Folder backup sebaiknya di luar direktori website publik.</p><?php endif ?>
  <button class="btn btn-primary mt-3" type="submit">Simpan <?= esc($form[0]) ?></button>
 </form></div>
<?php endforeach ?>
 </div></details>
</div>
<div class="row">
 <div class="col-lg-6"><div class="x_panel p-4"><h5 class="font-weight-bold">Backup Files</h5><p class="text-muted">Buat arsip ZIP untuk disimpan atau dipindahkan.</p>
 <label for="secure-scope">Cakupan file</label><select id="secure-scope" class="form-control mb-3"><option value="uploads">public/uploads — banner dan media lokal</option><option value="application">File aplikasi dan uploads</option><option value="full">Full Backup - Files dan Database</option></select>
 <small class="d-block text-muted mb-3">Backup aplikasi menyertakan konfigurasi .env jika ada; tidak menyertakan .git, node_modules, atau writable. File di R2 tidak ikut diunduh. <strong>Full Backup</strong> menggabungkan files.zip dan database.sql dalam satu ZIP. Hentikan perubahan website selama proses. Untuk restore, ekstrak paket di komputer pribadi lalu upload kedua komponen secara terpisah.</small>
 <button type="button" class="btn btn-primary" data-secure-action="files"><i class="fa fa-file-archive-o mr-1"></i> Backup Files</button></div></div>
 <div class="col-lg-6"><div class="x_panel p-4"><h5 class="font-weight-bold">Backup Database</h5><p class="text-muted">Ekspor seluruh database MySQL/MariaDB ke file SQL.</p><p class="small">Mencakup tabel, data, view, trigger, routine dan event. Menggunakan mysqldump yang terpasang di aaPanel.</p>
 <button type="button" class="btn btn-primary" data-secure-action="database"><i class="fa fa-database mr-1"></i> Backup Database</button></div></div>
</div>
<div class="x_panel p-4"><h5 class="font-weight-bold">Restore dari lokal</h5><p class="text-muted">Upload arsip, lalu klik Restore pada Daftar Backup. Gunakan ZIP atau SQL, maksimal 512 MB atau sesuai batas upload PHP/aaPanel yang lebih kecil.</p>
 <form id="secure-upload"><label for="secure-file">File backup</label><input id="secure-file" name="backup_file" type="file" accept=".zip,.sql" required class="form-control"><button class="btn btn-primary mt-3" type="submit"><i class="fa fa-upload mr-1"></i> Upload Backup</button></form>
</div>
<div class="x_panel p-4"><h5 class="font-weight-bold">Restore dari remote</h5>
 <p class="text-muted">Pilih tujuan yang sudah dikonfigurasi. Daftar arsip dimuat otomatis dari folder remote. Pilih arsip lalu klik Restore untuk mengunduh dan memeriksa tujuan pemulihan.</p>
 <label for="secure-restore-provider">Sumber backup</label>
 <select id="secure-restore-provider" class="form-control mb-3">
 <?php foreach(['ftp'=>'FTP / FTPS','drive'=>'Google Drive','s3'=>'S3 / R2 / B2'] as $key=>$label): if(empty($remote[$key]))continue; ?>
 <option value="<?= esc($key,'attr') ?>"><?= esc($label) ?></option>
 <?php endforeach ?>
 </select>
 <label for="secure-remote-reference">Arsip backup</label>
 <select id="secure-remote-reference" class="form-control"><option value="">Pilih sumber backup terlebih dahulu</option></select>
 <p id="secure-remote-message" class="small text-muted mt-2" role="status" aria-live="polite">Simpan pengaturan remote jika belum ada sumber yang tersedia.</p>
 <button type="button" class="btn btn-outline-primary mt-2" data-secure-action="remote-list">Muat ulang</button>
 <button type="button" id="secure-remote-more" class="btn btn-outline-primary mt-2" data-secure-action="remote-list" data-id="more" hidden>Muat berikutnya</button>
 <button type="button" id="secure-remote-restore" class="btn btn-primary mt-2" data-secure-action="remote-download" disabled>Restore arsip terpilih</button>
 <small class="d-block text-muted mt-2">Maksimal 5 GB per arsip. Arsip diunduh ke penyimpanan lokal; pemulihan membutuhkan konfirmasi RESTORE. Full Backup dipulihkan sebagai files.zip dan database.sql secara terpisah.</small>
</div>
<div id="secure-confirm" class="x_panel p-4 border border-warning" hidden role="region" aria-labelledby="secure-confirm-title">
 <h5 id="secure-confirm-title" class="font-weight-bold">Konfirmasi Restore</h5>
 <dl class="row mb-2"><dt class="col-sm-3">Arsip</dt><dd class="col-sm-9" id="secure-restore-name"></dd><dt class="col-sm-3">Tujuan</dt><dd class="col-sm-9" id="secure-restore-target"></dd></dl>
 <p id="secure-restore-detail"></p>
 <div class="alert alert-warning">Restore menimpa data aktif. Hentikan perubahan konten, upload, dan deployment selama proses. ZIP dan SQL dipulihkan terpisah. Backup pengaman dibuat sebelum penimpaan; jika proses SQL gagal, pemulihan melalui aaPanel mungkin diperlukan.</div>
 <label for="secure-restore-confirmation">Ketik <strong>RESTORE</strong> untuk melanjutkan</label>
 <input id="secure-restore-confirmation" class="form-control mb-3" autocomplete="off" spellcheck="false" placeholder="RESTORE">
 <button type="button" id="secure-restore-execute" class="btn btn-danger" disabled>Restore sekarang</button>
 <button type="button" id="secure-restore-cancel" class="btn btn-outline-secondary">Batal</button>
</div>
<div id="secure-progress" class="x_panel p-4" hidden><div class="d-flex align-items-center mb-3"><span id="secure-spinner" class="spinner-border spinner-border-sm text-primary mr-2" aria-hidden="true"></span><strong id="secure-status" role="status" aria-live="polite">Memproses…</strong></div><div class="progress" style="height:10px"><div id="secure-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%" role="progressbar" aria-label="Proses backup"></div></div><small class="text-muted d-block mt-2">Biarkan halaman tetap terbuka sampai selesai.</small></div>
<div class="x_panel p-4"><h5 class="font-weight-bold mb-3">Daftar Backup</h5><div class="table-responsive"><table class="table"><thead><tr><th>File</th><th>Jenis</th><th>Dibuat</th><th>Ukuran</th><th>Aksi</th></tr></thead><tbody id="secure-list"></tbody></table></div></div>
<script type="application/json" id="secure-initial"><?= json_encode($entries,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
</div>
<script defer src="<?= site_url('/admin-assets/js/secure-backups.js?v=20260910-3') ?>"></script>
<?php $this->endSection() ?>
