<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="host-api-overview">
    <section>
        <span class="host-api-guide__eyebrow">R2 STORAGE</span>
        <h4>Cloudflare R2 storage</h4>
        <p>Manage the credentials used exclusively for banner image uploads.</p>
        <a href="<?= admin_url('/third-party-apis/new') ?>" class="btn btn-primary"><i class="fa fa-plus"></i> Add R2 storage</a>
    </section>
    <section class="host-api-overview__docs">
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
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($apis as $api) : ?>
            <tr>
                <td>
                    <strong><?= esc($api->name) ?></strong>
                    <small>Banner uploads are stored in R2</small>
                </td>
                <td><span class="host-api-provider-badge">Cloudflare R2</span></td>
                <td><span class="host-api-scope"><i class="fa fa-cloud-upload"></i> Banner storage</span></td>
                <td><?= format_date_time($api->created_at) ?></td>
                <td>
                    <span class="host-api-status-badge <?= $api->status == 'active' ? 'is-active' : 'is-paused' ?>">
                        <i class="fa fa-circle"></i> <?= esc($api->status) ?>
                    </span>
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
            <div class="host-api-empty"><i class="fa fa-cloud-upload"></i><strong>No R2 storage configured</strong><span>Add Cloudflare R2 credentials to store banner uploads in the cloud.</span></div>
        <?php endif; ?>
    </div>
</div>

<?php $this->endSection() ?>
