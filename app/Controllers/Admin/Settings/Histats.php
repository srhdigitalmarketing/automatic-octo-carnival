<?php
namespace App\Controllers\Admin\Settings;

class Histats extends BaseSettings
{
    public function update()
    {
        $target = admin_url('/settings/site');
        if (strtolower($this->request->getMethod()) !== 'post') { return redirect()->to($target); }
        $code = trim((string) $this->request->getPost('histats_code'));
        $enabled = $this->request->getPost('histats_enabled') === '1';
        $scope = $this->request->getPost('histats_scope') === 'public' ? 'public' : 'embed';
        if (($code !== '' && !\App\Libraries\HistatsCode::isValid($code)) || ($enabled && $code === '')) {
            return redirect()->to($target)->withInput()->with('errors', ['Tempel Counter Code HiStats versi async yang lengkap sebelum mengaktifkan.']);
        }
        $db = db_connect();
        $db->transStart();
        foreach (['histats_code'=>[$code, 'string'], 'histats_enabled'=>[$enabled ? '1' : '0', 'bool'], 'histats_scope'=>[$scope, 'string']] as $name=>$entry) {
            $data = ['value'=>$entry[0], 'data_type'=>$entry[1]];
            if ($db->table('settings')->where('name', $name)->countAllResults()) {
                $db->table('settings')->where('name', $name)->update($data);
            } else {
                $db->table('settings')->insert(['name'=>$name] + $data);
            }
        }
        $db->transComplete();
        if (!$db->transStatus()) { return redirect()->to($target)->with('errors', ['Pengaturan HiStats gagal disimpan.']); }
        return redirect()->to($target)->with('success', 'Pengaturan HiStats disimpan.');
    }
}
