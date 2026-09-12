<div class="x_panel host-api-form-panel"><div class="x_content">
<input type="hidden" name="provider" value="serverdothost">
<h3>ServerDotHost / Bangkong</h3>
<p>Hubungkan pencarian judul dengan video, embed, dan poster Bangkong. Pilih hasil pencarian pada formulir video untuk mengisi datanya.</p>
<label for="sdh-name">Nama koneksi</label>
<input id="sdh-name" class="form-control mb-3" name="name" required maxlength="128" value="<?= esc((string)($tpAPI->name ?: 'ServerDotHost'), 'attr') ?>">
<label for="sdh-endpoint">Endpoint API</label>
<input id="sdh-endpoint" class="form-control mb-3" value="https://serverdothost.com/api/v1" readonly>
<label for="sdh-token">Bearer token (videos:read)</label>
<input id="sdh-token" class="form-control mb-2" type="password" name="api_token" maxlength="68" autocomplete="new-password" value="" <?= empty($tpAPI->id) ? 'required' : '' ?> placeholder="<?= empty($tpAPI->id) ? 'bkp_…' : 'Tersimpan — kosongkan untuk mempertahankan token' ?>">
<p>Token dibuat di Domain &amp; API Bangkong. Token hanya digunakan oleh server jplayer dan tidak dikirim ke player pengunjung.</p>
<label for="sdh-domains">Domain player yang diizinkan</label>
<input id="sdh-domains" class="form-control mb-2" name="embed_domains" required maxlength="1000" value="<?= esc((string)($tpAPI->embed_domains ?: 'bobaplayer.com, serverdothost.com'), 'attr') ?>">
<p>Pisahkan hostname dengan koma. Gunakan domain player aktif di Bangkong. Video yang belum memiliki tautan embed aktif tidak ditawarkan untuk diimpor.</p>
<label for="sdh-status">Status</label>
<select id="sdh-status" class="form-control mb-3" name="status"><option value="active">Active</option><option value="paused" <?= $tpAPI->status === 'paused' ? 'selected' : '' ?>>Paused</option></select>
<button type="submit" class="btn btn-primary">Simpan ServerDotHost</button>
</div></div>
