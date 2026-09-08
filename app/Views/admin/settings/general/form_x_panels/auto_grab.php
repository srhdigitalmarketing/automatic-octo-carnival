<div class="x_panel" id="auto-grab-panel" data-url="<?= esc(admin_url('/auto-grab/run'), 'attr') ?>">
    <div class="x_title"><h2>Auto Grab <small>Video tanpa image</small></h2><div class="clearfix"></div></div>
    <div class="x_content">
        <p>Ambil poster ke R2 dan tambahkan stream link untuk video No Image. Hanya kode persis seperti <strong>[batman]</strong> atau <strong>batman-123</strong>; English-Subtitle dan hasil ambigu dilewati.</p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0">
            <label for="auto-grab-api" style="margin:0">API katalog</label>
            <select id="auto-grab-api" class="form-control" style="width:260px;max-width:100%">
            <?php foreach ((new \App\Models\ThirdPartyApi())->where('provider','vod_catalog')->where('status','active')->findAll() as $api): ?>
                <option value="<?= (int)$api->id ?>"><?= esc($api->name) ?></option>
            <?php endforeach ?>
            </select>
            <button type="button" id="auto-grab-start" class="btn btn-primary" style="margin:0">Mulai Auto Grab</button>
            <button type="button" id="auto-grab-stop" class="btn btn-default" style="margin:0" disabled>Berhenti</button>
        </div>
        <p>Biarkan halaman ini terbuka selama proses. Judul, Video ID, dan link lama tetap dipertahankan.</p>
        <progress id="auto-grab-progress" value="0" max="1" style="width:100%;height:16px" aria-label="Progres auto grab"></progress>
        <p id="auto-grab-status" role="status" aria-live="polite">Siap memulai.</p>
        <div style="max-height:320px;overflow:auto;border:1px solid #e9edf4;border-radius:8px;padding:12px">
            <ul id="auto-grab-log" style="padding-left:20px;margin:0"></ul>
        </div>
    </div>
</div>
