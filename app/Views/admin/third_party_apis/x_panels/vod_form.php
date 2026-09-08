<div class="x_panel host-api-form-panel"><div class="x_content">
<input type="hidden" name="provider" value="vod_catalog">
<h3>VOD title search API</h3>
<label for="vod-name">Display name</label>
<input id="vod-name" class="form-control mb-3" name="name" required maxlength="128" value="<?= esc((string)$tpAPI->name, 'attr') ?>">
<label for="vod-host">API hostname</label>
<input id="vod-host" class="form-control mb-2" name="api_base_url" required maxlength="253" placeholder="inputhostname.com" value="<?= esc((string)$tpAPI->api_base_url, 'attr') ?>">
<p>Masukkan hostname saja. GET https://hostname/api.php/provide/vod?ac=detail&amp;wd=judul</p>
<label for="vod-embed-hosts">Embed hostnames (opsional untuk cek file)</label>
<input id="vod-embed-hosts" class="form-control mb-2" name="embed_domains" maxlength="1000" placeholder="upload18.org" value="<?= esc((string)$tpAPI->embed_domains, 'attr') ?>">
<p>Pisahkan hostname dengan koma. Cek file menggunakan kode URL dan mencocokkan link embed persis. Katalog tidak membuktikan playback atau penghapusan file.</p>
<label for="vod-status">Status</label>
<select id="vod-status" class="form-control mb-3" name="status"><option value="active">Active</option><option value="paused" <?= $tpAPI->status === 'paused' ? 'selected' : '' ?>>Paused</option></select>
<button type="submit" class="btn btn-primary">Save VOD API</button>
</div></div>
