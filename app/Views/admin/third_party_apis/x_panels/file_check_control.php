<?php
use App\Libraries\HostFileChecks;
if (empty($api->id) || !HostFileChecks::supported($api)) return;
$checkEnabled = HostFileChecks::enabled($api);
$checkAvailable = HostFileChecks::available();
$checkId = 'host-file-check-'.(int)$api->id;
?>
<form class="host-file-check-control" action="<?= esc(admin_url('/third-party-apis/file-check'), 'attr') ?>" method="post" data-saved="<?= $checkEnabled ? '1' : '0' ?>">
    <input type="hidden" name="api_id" value="<?= (int)$api->id ?>">
    <input type="hidden" name="enabled" value="0">
    <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="<?= $checkId ?>" <?= $checkEnabled ? 'checked' : '' ?> <?= $checkAvailable ? '' : 'disabled' ?> aria-describedby="<?= $checkId ?>-message">
        <label class="form-check-label" for="<?= $checkId ?>">Cek file <span class="host-file-check-label"><?= $checkEnabled ? 'aktif' : 'nonaktif' ?></span></label>
    </div>
    <small class="host-file-check-message d-block mt-1" id="<?= $checkId ?>-message" role="status" aria-live="polite"><?= !$checkAvailable ? 'Pengaturan tidak dapat dibaca.' : ($api->status === 'paused' ? 'Berlaku ketika API kembali Active.' : 'Katalog dan koneksi API tetap tersedia.') ?></small>
    <button type="submit" class="btn btn-sm btn-primary mt-2 host-file-check-save" <?= $checkAvailable ? '' : 'disabled' ?>>Simpan cek file</button>
</form>
