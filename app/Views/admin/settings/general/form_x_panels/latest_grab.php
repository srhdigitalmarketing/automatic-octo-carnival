<div class="x_panel" id="latest-grab-panel" data-url="<?= esc(admin_url('/latest-grab/run'), 'attr') ?>">
    <div class="x_title"><h2>Grab Latest Video <small>Video terbaru</small></h2><div class="clearfix"></div></div>
    <div class="x_content">
        <p>Ambil video terbaru dari API VOD: poster ke R2, judul, deskripsi, tahun, kualitas, durasi dan stream link. Duplikat serta data tidak lengkap dilewati.</p>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:16px 0">
            <label for="latest-grab-api" style="margin:0">API katalog</label>
            <select id="latest-grab-api" class="form-control" style="width:260px;max-width:100%">
            <?php foreach (($vodApis ?? []) as $api): ?>
                <option value="<?= (int)$api->id ?>"><?= esc($api->name) ?></option>
            <?php endforeach ?>
            </select>
            <label for="latest-grab-category">Kategori</label><select id="latest-grab-category" class="form-control" style="width:200px;max-width:100%"><option value="">Semua kategori</option></select><button type="button" id="latest-grab-categories" class="btn btn-default" style="margin:0">Muat kategori</button>
            <label for="latest-grab-count">Jumlah video</label><input id="latest-grab-count" type="number" min="1" max="100" value="5" class="form-control" style="width:90px">
            <button type="button" id="latest-grab-start" class="btn btn-primary" style="margin:0">Mulai Grab Latest Video</button>
            <button type="button" id="latest-grab-stop" class="btn btn-default" style="margin:0" disabled>Berhenti</button>
        </div>
        <p>Biarkan halaman ini terbuka selama proses. Video baru dibuat dengan status Public dan Video ID internal otomatis. Maksimal 100 video per proses.</p>
        <progress id="latest-grab-progress" value="0" max="1" style="width:100%;height:16px" aria-label="Progres auto grab"></progress>
        <p id="latest-grab-status" role="status" aria-live="polite">Siap memulai.</p>
        <div style="max-height:320px;overflow:auto;border:1px solid #e9edf4;border-radius:8px;padding:12px">
            <ul id="latest-grab-log" style="padding-left:20px;margin:0"></ul>
        </div>
    </div>
</div>
