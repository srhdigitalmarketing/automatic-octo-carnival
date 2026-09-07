<div class="x_panel host-api-form-panel"><div class="x_content">
    <?= form_hidden('provider', 'custom_http') ?>
    <h3>Custom hostname health checks</h3>
    <p>Pemeriksaan URL publik tanpa API token. Respons error seperti 404, 410, atau 522 membuat sistem mencoba host berikutnya.</p>
    <div class="form-group">
        <label for="custom-name">Display name</label>
        <?= form_input(['id'=>'custom-name','name'=>'name','class'=>'form-control','value'=>$tpAPI->name,'maxlength'=>128,'required'=>'required']) ?>
    </div>
    <div class="form-group">
        <label for="custom-domains">Embed hostnames</label>
        <?= form_input(['id'=>'custom-domains','name'=>'embed_domains','class'=>'form-control','value'=>$tpAPI->embed_domains,'maxlength'=>1000,'required'=>'required','placeholder'=>'player.example.com, other-player.example.com']) ?>
        <small>Hostname saja, tanpa https:// atau path. Pisahkan dengan koma. Jangan gunakan hostname yang sudah terdaftar pada provider aktif lain.</small>
    </div>
    <div class="form-group">
        <label for="custom-status">Status</label>
        <?= form_dropdown(['id'=>'custom-status','name'=>'status','options'=>['active'=>'Active','paused'=>'Paused'],'selected'=>$tpAPI->status ?: 'active','class'=>'form-control']) ?>
    </div>
    <p><strong>HTTP reachable bukan bukti video dapat diputar.</strong> Halaman error player dapat merespons HTTP 200. Isi video melalui JavaScript, fragmen URL (#ID), atau stream di dalam iframe tidak dapat dipastikan dengan pemeriksaan HTTP ini.</p>
    <p>Simpan URL embed lengkap pada Stream Links lalu klik <strong>Cek file</strong>, atau gunakan cron mingguan yang sudah ada. Redirect tidak diikuti; gunakan URL embed langsung.</p>
</div></div>
<div class="text-right"><button type="submit" class="btn btn-primary">Save Custom hostname</button></div>
