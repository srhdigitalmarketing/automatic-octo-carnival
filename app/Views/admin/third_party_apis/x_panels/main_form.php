<?php
$isExisting = ! empty($tpAPI->id);
?>

<div class="x_panel host-api-form-panel">
    <div class="x_content">
        <?= form_hidden('provider', 'cloudflare_r2') ?>

        <div class="host-api-fixed-endpoint">
            <strong>Cloudflare R2 banner storage</strong>
            <span>Banner dari PC disimpan di bucket R2 melalui S3 API. Browser hanya menerima URL publik gambar; kredensial selalu berada di server.</span>
        </div>

        <section class="provider-api-structure" aria-labelledby="r2-api-structure-title">
            <div class="provider-api-structure__header">
                <span>CLOUDFLARE R2 · BANNER STORAGE</span>
                <h3 id="r2-api-structure-title">PC upload → private R2 API → public banner URL</h3>
                <p>Gunakan <em>R2 Access Key ID</em> dan <em>R2 Secret Access Key</em>, bukan Global API Key Cloudflare. Masukkan domain publik bucket (custom domain atau <code>r2.dev</code>) agar gambar dapat ditampilkan.</p>
            </div>
        </section>

        <div class="host-api-form-grid">
            <div class="form-group">
                <label class="control-label" for="host-api-name">Display name</label>
                <?= form_input(['id' => 'host-api-name', 'name' => 'name', 'class' => 'form-control', 'value' => old('name', $tpAPI->name), 'placeholder' => 'Example: Primary banner storage', 'maxlength' => 128, 'required' => 'required']) ?>
            </div>
            <div class="form-group">
                <label class="control-label">Provider</label>
                <input type="text" class="form-control" value="Cloudflare R2" readonly>
            </div>
        </div>

        <section class="r2-settings">
            <div class="host-api-form-grid">
                <div class="form-group">
                    <label class="control-label" for="r2-account-id">Cloudflare account ID</label>
                    <?= form_input(['id' => 'r2-account-id', 'name' => 'r2_account_id', 'class' => 'form-control', 'value' => old('r2_account_id', $tpAPI->r2_account_id), 'maxlength' => 64, 'autocomplete' => 'off', 'required' => 'required']) ?>
                </div>
                <div class="form-group">
                    <label class="control-label" for="r2-bucket">Bucket name</label>
                    <?= form_input(['id' => 'r2-bucket', 'name' => 'r2_bucket', 'class' => 'form-control', 'value' => old('r2_bucket', $tpAPI->r2_bucket), 'maxlength' => 255, 'autocomplete' => 'off', 'required' => 'required']) ?>
                </div>
            </div>
            <div class="host-api-form-grid">
                <div class="form-group">
                    <label class="control-label" for="r2-access-key">R2 access key ID</label>
                    <?= form_input(['id' => 'r2-access-key', 'name' => 'r2_access_key_id', 'type' => 'password', 'class' => 'form-control', 'value' => '', 'placeholder' => $isExisting && ! empty($tpAPI->r2_access_key_id) ? 'Saved — leave blank to keep the current key' : 'R2 access key ID', 'maxlength' => 128, 'autocomplete' => 'new-password', 'required' => $isExisting ? null : 'required']) ?>
                </div>
                <div class="form-group">
                    <label class="control-label" for="r2-secret-key">R2 secret access key</label>
                    <?= form_input(['id' => 'r2-secret-key', 'name' => 'r2_secret_access_key', 'type' => 'password', 'class' => 'form-control', 'value' => '', 'placeholder' => $isExisting && ! empty($tpAPI->r2_secret_access_key) ? 'Saved — leave blank to keep the current secret' : 'R2 secret access key', 'maxlength' => 255, 'autocomplete' => 'new-password', 'required' => $isExisting ? null : 'required']) ?>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label" for="r2-public-url">Public bucket URL</label>
                <?= form_input(['id' => 'r2-public-url', 'name' => 'r2_public_url', 'type' => 'url', 'class' => 'form-control', 'value' => old('r2_public_url', $tpAPI->r2_public_url), 'placeholder' => 'https://media.example.com', 'maxlength' => 255, 'required' => 'required']) ?>
                <small>URL publik tanpa nama file. Contoh: <code>https://media.example.com</code>. Aktifkan <em>Public Development URL</em> pada bucket untuk memakai <code>r2.dev</code>, atau gunakan custom domain publik.</small>
            </div>
        </section>

        <div class="form-group host-api-status">
            <?= form_label('Status', 'host-api-status', ['class' => 'control-label']) ?>
            <?= form_dropdown(['id' => 'host-api-status', 'name' => 'status', 'options' => ['active' => 'Active', 'paused' => 'Paused'], 'selected' => old('status', $tpAPI->status ?: 'active'), 'class' => 'form-control']) ?>
        </div>
    </div>
</div>

<div class="text-right">
    <button type="submit" class="btn btn-primary d-inline-block px-5">Save R2 storage</button>
</div>
