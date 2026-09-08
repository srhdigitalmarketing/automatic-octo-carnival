<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="host-api-overview">
    <section>
        <span class="host-api-guide__eyebrow">API & R2 STORAGE</span>
        <h4>API & R2 storage</h4>
        <p>Manage banner storage and video host health checks.</p>
        <a href="<?= admin_url('/settings/cdn#cdn-settings') ?>" class="btn btn-primary">Add CDN Hostname</a>
        <a href="<?= admin_url('/third-party-apis/new') ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add R2 storage</a>
    <a href="<?= admin_url('/third-party-apis/new?provider=upnshare') ?>" class="btn btn-primary">Add UPNShare</a>
        <a href="<?= admin_url('/third-party-apis/new?provider=custom_http') ?>" class="btn btn-primary">Add Custom hostname</a>
    </section>
    <section class="host-api-overview__docs">
        <a href="<?= admin_url('/third-party-apis/new?provider=vod_catalog') ?>" class="btn btn-primary">Add VOD API</a>
        <h5>Storage provider</h5>
        <p>Cloudflare R2 via the S3-compatible API.</p>
    </section>
</div>

<div class="x_panel host-api-list-panel">
    <div class="x_content">
        <table class="table host-api-table">
            <thead>
            <tr>
                <th>API access</th>
                <th>Provider</th>
                <th>Data scopes</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Result <small class="d-block">Cache maksimal 60 detik</small></th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($apis as $api) : ?>
            <tr>
                <td>
                    <strong><?= esc($api->name) ?></strong>
                    <small><?= $api->provider === 'vod_catalog' ? 'VOD title search' : (in_array($api->provider, ['upnshare','custom_http'], true) ? 'Video availability checks' : 'Banner uploads are stored in R2') ?></small>
                </td>
                <td><span class="host-api-provider-badge"><?= $api->provider === 'vod_catalog' ? 'VOD title search' : (in_array($api->provider, ['upnshare','custom_http'], true) ? ($api->provider === 'custom_http' ? 'Custom hostname' : ($api->provider === 'streamhg' ? 'StreamHG' : 'UPNShare')) : 'Cloudflare R2') ?></span></td>
                <td><span class="host-api-scope"><i class="fa fa-cloud-upload"></i> <?= $api->provider === 'vod_catalog' ? 'VOD title search' : (in_array($api->provider, ['upnshare','custom_http'], true) ? 'Read video status' : 'Banner storage') ?></span></td>
                <td><?= format_date_time($api->created_at) ?></td>
                <td>
                    <span class="host-api-status-badge <?= $api->status == 'active' ? 'is-active' : 'is-paused' ?>">
                        <i class="fa fa-circle"></i> <?= esc($api->status) ?>
                    </span>
                </td>
                <td class="provider-connection" data-url="<?= esc(admin_url('/third-party-apis/result?id=' . (int)$api->id), 'attr') ?>" aria-live="polite">
                    <span class="provider-result">Memeriksa…</span>
                    <small class="provider-result-detail d-block"></small>
                    <button type="button" class="btn btn-sm btn-light provider-recheck">Cek ulang</button>
                </td>
                <td class="text-center">
                    <div class="table-actions">
                        <a href="<?= admin_url("/third-party-apis/edit?id={$api->id}") ?>" class="btn btn-sm link-action-btn link-action-btn--edit"><i class="fa fa-pencil"></i> Edit</a>
                        <a href="javascript:void(0)" data-url="<?= admin_url("/third-party-apis/delete?id={$api->id}") ?>" class="btn btn-sm link-action-btn link-action-btn--delete del-item"><i class="fa fa-trash"></i> Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($apis)): ?>
            <div class="host-api-empty"><i class="fa fa-cloud-upload"></i><strong>No API access configured</strong><span>Add Cloudflare R2 credentials to store banner uploads in the cloud.</span></div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script src="<?= site_url('/admin-assets/js/provider-connection.js?v=20260908-1') ?>"></script>
<?php $this->endSection() ?>
