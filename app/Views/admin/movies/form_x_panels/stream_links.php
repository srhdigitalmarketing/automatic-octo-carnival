<?php
$streamServerStatus = static function ($link): array {
    $host = parse_url((string) $link->link, PHP_URL_HOST);
    $host = is_string($host) && $host !== '' ? preg_replace('/^www\./i', '', $host) : 'Unknown server';

    if ((bool) ($link->is_broken ?? false) || ! empty($link->last_error)) {
        return [$host, 'is-broken', 'fa-times', 'Unavailable', 'The last availability check failed'];
    }

    if (! empty($link->last_checked_at) && ! empty($link->last_success_at)) {
        return [$host, 'is-healthy', 'fa-check', 'Healthy', 'Video link passed the latest check'];
    }

    return [$host, 'is-unchecked', 'fa-clock-o', 'Not checked', 'Waiting for the first availability check'];
};
?>

<div class="x_panel">
    <div class="x_title">
        <h2>Stream Links</h2>
        <ul class="nav navbar-right panel_toolbox">
            <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
            </li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <div class="x_content" id="st-group-content">

        <?php if(! empty($streamLinks)): ?>
            <?php foreach ($streamLinks as $key => $link):
                $key += 1; ?>
                <div class="form-group st-group">

                    <?= form_label("Link {$key}:", "", ['class'=>'font-weight-bold']) ?>

                    <div class="link-meta-info mb-1">
                        <span class="requests-count">Requests :  <?= $link->requests ?></span>
                        <span class="status float-right">Status : <?= format_links_status( $link->is_broken ) ?> </span>
                    </div>


                    <div class="input-group">
                        <?php $fields = [
                            'type' => 'url',
                            'name' => "st_links[{$key}][url]",
                            'class' => 'form-control link',
                            'value' => old("st_links.{$key}.url", $link->link)
                        ]; if( $link->isApiBased() ) $fields['readonly'] = 'readonly' ?>
                        <?= form_input($fields) ?>

                        <span class="input-group-btn ml-2">
                                <?= form_button( [
                                    'class' => 'btn btn-light clone-st-group'
                                ],
                                    '<i class="fa fa-plus"></i>'
                                ) ?>
                            </span>
                    </div>
                    <div class="row mt-2 stream-health-fields">
                        <div class="col-md-4">
                            <?= form_label('Host priority', '') ?>
                            <?= form_input([
                                'type' => 'number',
                                'min' => '0',
                                'max' => '65535',
                                'name' => "st_links[{$key}][host_priority]",
                                'class' => 'form-control stream-priority',
                                'value' => old("st_links.{$key}.host_priority", $link->host_priority ?? 100)
                            ]) ?>
                            <small class="form-text text-muted">Higher values are tried first; the next link is used automatically if playback fails.</small>
                        </div>
                        <div class="col-md-8">
                            <?php [$serverHost, $serverStatusClass, $serverStatusIcon, $serverStatusLabel, $serverStatusHelp] = $streamServerStatus($link); ?>
                            <?= form_label('Server status', '') ?>
                            <div class="stream-server-status">
                                <span class="stream-server-host"><i class="fa fa-server"></i> <?= esc($serverHost) ?></span>
                                <span class="stream-server-badge <?= esc($serverStatusClass) ?>" title="<?= esc($serverStatusHelp) ?>">
                                    <i class="fa <?= esc($serverStatusIcon) ?>"></i> <?= esc($serverStatusLabel) ?>
                                </span>
                            </div>
                            <?= form_hidden("st_links[{$key}][upnshare_video_id]", old("st_links.{$key}.upnshare_video_id", $link->upnshare_video_id ?? '')) ?>
                        </div>
                    </div>

                    <?= form_hidden("st_links[{$key}][id]", $link->id); ?>
                    <?=  ! empty($link->api_id) ?  form_hidden("st_links[{$key}][api_id]", $link->api_id) : '' ?>


                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php for($i = 1; $i <= 3; $i++) : ?>
                <div class="form-group st-group" >
                    <?= form_label("Link {$i}:") ?>
                    <div class="input-group">
                        <?= form_input([
                            'type' => 'url',
                            'name' => "st_links[{$i}][url]",
                            'class' => 'form-control link',
                            'value' => old("st_links.{$i}.url")
                        ]) ?>

                        <span class="input-group-btn ml-2">
                                <?= form_button( [
                                    'class' => 'btn btn-light clone-st-group'
                                ],
                                    '<i class="fa fa-plus"></i>'
                                ) ?>
                        </span>
                    </div>
                    <div class="row mt-2 stream-health-fields">
                        <div class="col-md-4">
                            <?= form_label('Host priority', '') ?>
                            <?= form_input([
                                'type' => 'number',
                                'min' => '0',
                                'max' => '65535',
                                'name' => "st_links[{$i}][host_priority]",
                                'class' => 'form-control stream-priority',
                                'value' => old("st_links.{$i}.host_priority", 100)
                            ]) ?>
                            <small class="form-text text-muted">Higher values are tried first; the next link is used automatically if playback fails.</small>
                        </div>
                        <div class="col-md-8">
                            <?= form_label('Server status', '') ?>
                            <div class="stream-server-status">
                                <span class="stream-server-host"><i class="fa fa-server"></i> Host detected after saving</span>
                                <span class="stream-server-badge is-unchecked"><i class="fa fa-clock-o"></i> Not checked</span>
                            </div>
                        </div>
                    </div>



                </div>
            <?php endfor; ?>
        <?php endif; ?>




    </div>
</div>
