<div class="x_panel" id="serverdothost-grab-panel" data-url="<?= esc(admin_url('/serverdothost-grab/run'), 'attr') ?>">
    <div class="x_title"><h2>Auto Grab Stream Links <small>ServerDotHost</small></h2><div class="clearfix"></div></div>
    <div class="x_content">
        <p>Tambahkan stream link ke Movies dan Episodes yang sudah ada berdasarkan judul persis. Huruf besar/kecil dan spasi diabaikan. Video yang belum diunggah, belum siap, belum memiliki embed, atau judul ambigu akan dilewati.</p>
        <div class="row" style="margin-top:16px">
            <div class="col-sm-6 form-group">
                <label for="serverdothost-grab-api">API ServerDotHost</label>
                <select id="serverdothost-grab-api" class="form-control">
                    <?php if (empty($serverDotHostApis)): ?><option value="">Belum ada ServerDotHost aktif</option><?php endif ?>
                    <?php foreach (($serverDotHostApis ?? []) as $api): ?>
                    <option value="<?= (int)$api->id ?>"><?= esc($api->name) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>
        <?php if (empty($serverDotHostApis)): ?>
        <p><a href="<?= esc(admin_url('/third-party-apis'), 'attr') ?>">Tambahkan ServerDotHost pada API &amp; R2 Storage</a>, lalu isi token videos:read dan domain embed.</p>
        <?php endif ?>
        <div class="form-group">
            <button type="button" id="serverdothost-grab-start" class="btn btn-primary" <?= empty($serverDotHostApis) ? 'disabled' : '' ?>>Mulai Grab Stream Links</button>
            <button type="button" id="serverdothost-grab-stop" class="btn btn-default" disabled>Berhenti</button>
        </div>
        <p>Link yang sudah ada tidak ditambahkan ulang. Judul, poster, konten, dan link lama tetap dipertahankan. Tidak memerlukan R2. Biarkan halaman terbuka selama proses.</p>
        <div class="progress" style="height:20px">
            <div id="serverdothost-grab-progress" class="progress-bar" role="progressbar" aria-label="Progres grab stream link" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width:0%">0%</div>
        </div>
        <p id="serverdothost-grab-status" role="status" aria-live="polite">Siap memulai.</p>
        <div style="max-height:300px;overflow:auto;border:1px solid #e9edf4;border-radius:8px;padding:12px">
            <ul id="serverdothost-grab-log" style="padding-left:20px;margin:0"></ul>
        </div>
    </div>
</div>
