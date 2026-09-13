<?php $siteNoIndex = \App\Libraries\SiteIndexing::noIndex(); ?>
<?= form_open(admin_url('/settings/indexing/update'), ['id'=>'site-indexing-form']) ?>
<div class="x_panel">
    <div class="x_title"><h2>Index / No Index</h2><div class="clearfix"></div></div>
    <div class="x_content">
        <label class="form-label" for="site-noindex">Indeks mesin pencari</label>
        <select class="form-select mb-2" id="site-noindex" name="site_noindex" required aria-describedby="site-noindex-help">
            <option value="1" <?= $siteNoIndex ? 'selected' : '' ?>>No Index - jangan indeks seluruh halaman</option>
            <option value="0" <?= !$siteNoIndex ? 'selected' : '' ?>>Index - izinkan indeks halaman publik</option>
        </select>
        <p id="site-noindex-help">No Index berlaku untuk seluruh halaman website, termasuk beranda, film, serial, player/embed, dan halaman download.</p>
        <p class="text-muted">Pada mode Index, halaman admin, login dan error tetap No Index. Perubahan hasil pencarian mengikuti kunjungan ulang mesin pencari.</p>
        <button type="submit" class="btn btn-primary">Simpan pengaturan indeks</button>
    </div>
</div>
<?= form_close() ?>
