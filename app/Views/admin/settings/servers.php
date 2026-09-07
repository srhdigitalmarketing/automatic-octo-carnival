<?php $this->extend( 'admin/__layout/default' ) ?>


<?php $this->section('content') ?>

<div class="row">
    <div class="col-xl-10">

        <?= form_open('/admin/settings/servers/update', ['method'=>'post']) ?>

        <div class="x_panel server-settings-panel">
            <div class="x_title">
                <h2>Server directory <small>Display names and playback priority</small></h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">

                <?php if(! empty($servers)): ?>

                <p class="server-settings-intro">Manage the display name and playback priority for each video host. Saving a priority updates every stream link on that host at once. Deleting a host permanently removes its associated links.</p>

                <div class="server-settings-default">
                    <div>
                        <span class="server-settings-eyebrow">Playback preference</span>
                        <h3>Default server</h3>
                        <p>Choose the preferred server shown first when multiple links are available.</p>
                    </div>
                    <div class="server-settings-default__field">

                       <?= form_dropdown([
                               'name' => 'default_server',
                                'class' => 'form-control',
                                'options' => $serverOptions,
                                'selected' => get_config('default_server')
                       ]) ?>

                    </div>
                </div>

                <div class="server-table-wrap">
                    <table class="table server-settings-table">
                        <thead>
                            <tr>
                                <th>Host</th>
                                <th>Display name</th>
                                <th>Host priority</th>
                                <th>Total links</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($servers as $key => $val) : ?>
                        <?php $serverName = $val !== '' ? $val : $key; ?>
                        <tr data-server-row data-server-host="<?= esc($key, 'attr') ?>" data-server-links="<?= (int) ($serverLinkCounts[$key] ?? 0) ?>">
                            <td>
                                <strong><?= esc($key) ?></strong>
                                <?php if (get_config('default_server') === $serverName): ?>
                                    <span class="server-default-badge"><i class="fa fa-star"></i> Default</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= form_input([
                                    'name' => "renamed_servers[$key]",
                                    'class' => 'form-control server-alias-input',
                                    'data-server-alias' => 'true',
                                    'placeholder' => 'Use host name',
                                    'readonly' => 'readonly',
                                    'value' => $val
                                ]) ?>
                            </td>
                            <td class="server-priority-cell">
                                <?php
                                // Do not pass disabled=false to form_input(): HTML treats the
                                // mere presence of a disabled attribute as disabled.
                                $priorityAttributes = [
                                    'name' => "host_priorities[$key]",
                                    'type' => 'number',
                                    'min' => '0',
                                    'max' => '65535',
                                    'step' => '1',
                                    'class' => 'form-control server-priority-input',
                                    'value' => $serverPriorities[$key] ?? 100,
                                ];
                                if (! $streamHealthAvailable || empty($serverStreamLinkCounts[$key])) {
                                    $priorityAttributes['disabled'] = 'disabled';
                                }
                                ?>
                                <?= form_input($priorityAttributes) ?>
                            </td>
                            <td class="server-total-links">
                                <?= number_format($serverLinkCounts[$key] ?? 0) ?>
                            </td>
                            <td class="text-right server-row-actions">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-server-edit><i class="fa fa-pencil"></i> Edit</button>
                                <button class="btn btn-sm btn-outline-danger" type="button" data-server-delete><i class="fa fa-trash"></i> Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-right server-settings-actions">
                    <?= form_button([
                        'type' => 'submit',
                        'class' => 'btn btn-primary'
                    ], 'Save changes') ?>
                </div>

                <?php else: ?>
                     servers not found yet
                <?php endif; ?>



            </div>
        </div>

        <?= form_close() ?>

        <?= form_open('/admin/settings/servers/delete', ['method' => 'post', 'id' => 'server-delete-form', 'class' => 'd-none']) ?>
            <input type="hidden" name="host" value="">
        <?= form_close() ?>

    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
document.addEventListener('click', function (event) {
    var editButton = event.target.closest('[data-server-edit]');
    var deleteButton = event.target.closest('[data-server-delete]');
    var button = editButton || deleteButton;

    if (!button) {
        return;
    }

    var row = button.closest('[data-server-row]');
    var input = row.querySelector('[data-server-alias]');

    if (editButton) {
        input.removeAttribute('readonly');
        input.focus();
        input.select();
        row.classList.add('is-editing');
        return;
    }

    var host = row.getAttribute('data-server-host');
    var linkCount = Number(row.getAttribute('data-server-links') || 0);
    var message = 'Delete ' + host + '? This permanently removes ' + linkCount + ' linked video record' + (linkCount === 1 ? '' : 's') + ' and cannot be undone.';

    if (window.confirm(message)) {
        var deleteForm = document.getElementById('server-delete-form');
        deleteForm.querySelector('[name="host"]').value = host;
        deleteForm.submit();
    }
});
</script>
<?php $this->endSection() ?>
