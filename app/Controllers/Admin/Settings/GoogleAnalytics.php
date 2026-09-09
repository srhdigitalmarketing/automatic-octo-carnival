<?php
namespace App\Controllers\Admin\Settings;

class GoogleAnalytics extends BaseSettings
{
    public function update()
    {
        $target = admin_url('/settings/site');
        if (strtolower($this->request->getMethod()) !== 'post') { return redirect()->to($target); }
        $id = strtoupper(trim((string) $this->request->getPost('ga4_measurement_id')));
        $enabled = $this->request->getPost('ga4_enabled') === '1';
        $scope = $this->request->getPost('ga4_scope') === 'public' ? 'public' : 'embed';
        if (($id !== '' && !preg_match('/^G-[A-Z0-9]{4,20}$/D', $id)) || ($enabled && $id === '')) {
            return redirect()->to($target)->withInput()->with('errors', ['Isi Measurement ID GA4 yang valid, misalnya G-XXXXXXXXXX, sebelum mengaktifkan.']);
        }
        $db = db_connect();
        $db->transStart();
        foreach (['ga4_measurement_id'=>[$id, 'string'], 'ga4_enabled'=>[$enabled ? '1' : '0', 'bool'], 'ga4_scope'=>[$scope, 'string']] as $name=>$entry) {
            $data = ['value'=>$entry[0], 'data_type'=>$entry[1]];
            if ($db->table('settings')->where('name', $name)->countAllResults()) {
                $db->table('settings')->where('name', $name)->update($data);
            } else {
                $db->table('settings')->insert(['name'=>$name] + $data);
            }
        }
        $db->transComplete();
        if (!$db->transStatus()) { return redirect()->to($target)->with('errors', ['Pengaturan Google Analytics gagal disimpan.']); }
        return redirect()->to($target)->with('success', 'Pengaturan Google Analytics disimpan.');
    }
}
