<?php $isExisting = !empty($tpAPI->id); $providerName = $tpAPI->provider === 'vidhide' ? 'EarnVids' : 'UPNShare'; ?>
<div class="x_panel host-api-form-panel"><div class="x_content">
    <?= form_hidden('provider', $tpAPI->provider) ?>
    <h3><?= esc($providerName) ?> video health checks</h3>
    <?php if ($tpAPI->provider === 'vidhide'): ?><p>Website akun: earnvids.com. Endpoint API: https://earnvidsapi.com/api. Gunakan API key dari akun EarnVids Anda.</p><?php endif ?>
    <p>Token hanya digunakan di server untuk membaca status video. Gunakan token akun pemilik video.</p>
    <div class="form-group">
        <label for="upn-name">Display name</label>
        <?= form_input(['id'=>'upn-name','name'=>'name','class'=>'form-control','value'=>$tpAPI->name,'maxlength'=>128,'required'=>'required']) ?>
    </div>
    <div class="form-group">
        <label for="upn-token">API token</label>
        <?= form_input(['id'=>'upn-token','name'=>'api_token','type'=>'password','class'=>'form-control','value'=>'','autocomplete'=>'new-password','maxlength'=>255,'placeholder'=>$isExisting ? 'Leave blank to keep the saved token' : 'Provider API token/key','required'=>$isExisting ? null : 'required']) ?>
    </div>
    <div class="form-group">
        <label for="upn-domains">Embed hostnames</label>
        <?= form_input(['id'=>'upn-domains','name'=>'embed_domains','class'=>'form-control','value'=>$tpAPI->embed_domains ?: '','maxlength'=>1000,'required'=>'required','placeholder'=>'player.example.com, other-player.example.com']) ?>
        <small>Masukkan hostname dari link video, tanpa https:// atau path. Pisahkan dengan koma. API dipilih otomatis berdasarkan hostname ini. Satu hostname hanya boleh digunakan oleh satu konfigurasi provider aktif.</small>
    </div>
    <div class="form-group">
        <label for="upn-status">Status</label>
        <?= form_dropdown(['id'=>'upn-status','name'=>'status','options'=>['active'=>'Active','paused'=>'Paused'],'selected'=>$tpAPI->status ?: 'active','class'=>'form-control']) ?>
    </div>
    <p>Jalankan <code>php spark streams:health-check --limit 100</code> melalui cron untuk memperbarui badge. Status 404 berarti file tidak ditemukan pada akun provider yang sesuai hostname; pastikan akun dan ID benar.</p>
</div></div>
<div class="text-right"><button type="submit" class="btn btn-primary">Save <?= esc($providerName) ?></button></div>
