<?php
namespace App\Controllers\Admin\Settings;

class Cdn extends BaseSettings
{
    public function index()
    {
        return view('admin/settings/cdn', ['title' => 'CDN Settings']);
    }

    public function update()
    {
        if (strtolower($this->request->getMethod()) !== 'post') {
            return redirect()->to(admin_url('/settings/cdn'));
        }
        $host = strtolower(trim((string) $this->request->getPost('player_cdn_hostname')));
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strpos($host, '.') === false || strlen($host) > 253) {
            return redirect()->to(admin_url('/settings/cdn'))->withInput()->with('errors', ['Masukkan hostname CDN saja, tanpa https://, port atau path.']);
        }
        $db = db_connect();
        $db->transStart();
        foreach ([
            'player_cdn_hostname' => [$host, 'string'],
            'player_bunny_cdn_enabled' => [$this->request->getPost('player_bunny_cdn_enabled') === '1' ? '1' : '0', 'bool'],
        ] as $name => $entry) {
            $data = ['value' => $entry[0], 'data_type' => $entry[1]];
            if ($db->table('settings')->where('name', $name)->countAllResults()) {
                $db->table('settings')->where('name', $name)->update($data);
            } else {
                $db->table('settings')->insert(['name' => $name] + $data);
            }
        }
        $db->transComplete();
        if (!$db->transStatus()) {
            return redirect()->to(admin_url('/settings/cdn'))->withInput()->with('errors', ['Pengaturan CDN gagal disimpan.']);
        }
        return redirect()->to(admin_url('/settings/cdn'))->with('success', 'Pengaturan CDN berhasil disimpan.');
    }
}
