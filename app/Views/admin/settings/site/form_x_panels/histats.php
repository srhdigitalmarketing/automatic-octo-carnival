<?= form_open(admin_url('/settings/histats/update')) ?>
<div class="x_panel">
    <div class="x_title"><h2>HiStats</h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="histats-enabled" name="histats_enabled" value="1" <?= get_config('histats_enabled') ? 'checked' : '' ?>>
            <label class="form-check-label" for="histats-enabled">Aktifkan HiStats</label>
        </div>
        <div class="mb-3">
            <label class="form-label" for="histats-code">Counter Code HiStats (async)</label>
            <textarea class="form-control" id="histats-code" name="histats_code" rows="9" maxlength="20000" spellcheck="false" placeholder="Tempel Counter Code versi async dari akun HiStats Anda"><?= esc((string) get_config('histats_code')) ?></textarea>
            <small class="text-muted">Gunakan kode lengkap dari histats.com, bukan URL atau Site ID saja. Kode hanya dijalankan pada halaman publik setelah diaktifkan.</small>
        </div>
        <div class="mb-3">
            <label class="form-label" for="histats-scope">Halaman yang dihitung</label>
            <select class="form-select" id="histats-scope" name="histats_scope">
                <option value="embed" <?= get_config('histats_scope') !== 'public' ? 'selected' : '' ?>>Embed / player saja</option>
                <option value="public" <?= get_config('histats_scope') === 'public' ? 'selected' : '' ?>>Semua halaman publik termasuk embed</option>
            </select>
        </div>
        <p class="text-muted">Lihat statistik di akun HiStats. Pilih hidden tracker jika tidak ingin menampilkan counter pada halaman. Hapus kode tracking manual yang lama agar tidak ganda. Tidak ada heartbeat atau statistik pengunjung yang ditulis ke database website.</p>
        <button type="submit" class="btn btn-primary">Simpan HiStats</button>
    </div>
</div>
<?= form_close() ?>
