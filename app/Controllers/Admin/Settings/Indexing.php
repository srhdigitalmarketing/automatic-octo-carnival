<?php
namespace App\Controllers\Admin\Settings;

use App\Libraries\SiteIndexing;

class Indexing extends BaseSettings
{
    public function update()
    {
        $target = admin_url('/settings/site');
        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setStatusCode(405)->setHeader('Allow', 'POST');
        }
        $value = $this->request->getPost(SiteIndexing::KEY);
        if (!in_array($value, ['0','1'], true)) {
            return redirect()->to($target)->with('errors', ['Pilih Index atau No Index sebelum menyimpan.']);
        }
        try {
            SiteIndexing::save($value === '1');
        } catch (\Throwable $error) {
            return redirect()->to($target)->with('errors', ['Pengaturan indeks gagal disimpan. Periksa tabel settings dan coba lagi.']);
        }
        $message = $value === '1' ? 'No Index aktif untuk seluruh halaman website.'
            : 'Index aktif untuk halaman publik. Halaman admin, login dan error tetap No Index.';
        return redirect()->to($target)->with('success', $message);
    }
}
