<?php $isExisting = !empty($tpAPI->id); ?>
<div class="x_panel host-api-form-panel"><div class="x_content">
    <?= form_hidden('provider', 'upnshare') ?>
    <h3>UPNShare video health checks</h3>
    <p>Token hanya digunakan di server untuk membaca status video. Gunakan token akun pemilik video.</p>
    <div class="form-group">
        <label for="upn-name">Display name</label>
        <?= form_input(['id'=>'upn-name','name'=>'name','class'=>'form-control','value'=>$tpAPI->name,'maxlength'=>128,'required'=>'required']) ?>
    </div>
    <div class="form-group">
        <label for="upn-token">API token</label>
        <?= form_input(['id'=>'upn-token','name'=>'api_token','type'=>'password','class'=>'form-control','value'=>'','autocomplete'=>'new-password','maxlength'=>255,'placeholder'=>$isExisting ? 'Leave blank to keep the saved token' : 'Token from UPNShare API Access','required'=>$isExisting ? null : 'required']) ?>
    </div>
    <div class="form-group">
        <label for="upn-domains">Embed hostnames</label>
        <?= form_input(['id'=>'upn-domains','name'=>'embed_domains','class'=>'form-control','value'=>$tpAPI->embed_domains ?: 'upnshare.com','maxlength'=>1000,'required'=>'required','placeholder'=>'upnshare.com, your-embed-domain.com']) ?>
        <small>Masukkan hostname dari link video, tanpa https:// atau path. Pisahkan dengan koma. Link yang cocok diperiksa otomatis; jika beberapa akun memakai domain sama, pilih akun pada Stream Links.</small>
    </div>
    <div class="form-group">
        <label for="upn-status">Status</label>
        <?= form_dropdown(['id'=>'upn-status','name'=>'status','options'=>['active'=>'Active','paused'=>'Paused'],'selected'=>$tpAPI->status ?: 'active','class'=>'form-control']) ?>
    </div>
    <p>Jalankan <code>php spark streams:health-check --limit 100</code> melalui cron untuk memperbarui badge. Status 404 berarti file tidak ditemukan pada akun yang dipilih; pastikan akun dan ID benar.</p>
</div></div>
<div class="text-right"><button type="submit" class="btn btn-primary">Save UPNShare</button></div>
