<?= form_open(admin_url('/settings/google-analytics/update')) ?>
<div class="x_panel">
    <div class="x_title"><h2>Google Analytics 4</h2><div class="clearfix"></div></div>
    <div class="x_content">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="ga4-enabled" name="ga4_enabled" value="1" <?= get_config('ga4_enabled') ? 'checked' : '' ?>>
            <label class="form-check-label" for="ga4-enabled">Aktifkan Google Analytics</label>
        </div>
        <div class="mb-3">
            <label class="form-label" for="ga4-id">Measurement ID</label>
            <input class="form-control" id="ga4-id" name="ga4_measurement_id" value="<?= esc((string) get_config('ga4_measurement_id'), 'attr') ?>" placeholder="G-XXXXXXXXXX" maxlength="22" autocomplete="off" spellcheck="false">
            <small class="text-muted">Kosongkan atau nonaktifkan jika belum digunakan. Tidak memerlukan API key.</small>
        </div>
        <div class="mb-3">
            <label class="form-label" for="ga4-scope">Halaman yang dihitung</label>
            <select class="form-select" id="ga4-scope" name="ga4_scope">
                <option value="embed" <?= get_config('ga4_scope') !== 'public' ? 'selected' : '' ?>>Embed saja</option>
                <option value="public" <?= get_config('ga4_scope') === 'public' ? 'selected' : '' ?>>Semua halaman publik termasuk embed</option>
            </select>
        </div>
        <p class="text-muted">Halaman admin tidak dihitung. Tag dimuat asinkron tanpa mencatat statistik ke database website. Hapus pemasangan GA/GTM lain pada custom code agar tidak ganda. Pemutaran video di iframe host tidak otomatis terukur.</p>
        <button type="submit" class="btn btn-primary">Simpan Google Analytics</button>
    </div>
</div>
<?= form_close() ?>
