<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Libraries\VideoHostHealth;
use App\Models\LinkModel;

class StreamHealth extends BaseController
{
    public function check()
    {
        if (strtolower($this->request->getMethod()) !== 'post') return $this->response->setStatusCode(405)->setJSON(['message'=>'Gunakan tombol Cek file.']);
        $links = new LinkModel();
        $link = $links->find((int)$this->request->getGet('id'));
        if ($link === null || $link->type !== 'stream') {
            return $this->response->setStatusCode(404)->setJSON(['message'=>'Stream link tidak ditemukan.']);
        }
        if (!$links->supportsProviderStatus()) {
            return $this->response->setStatusCode(409)->setJSON(['message'=>'Jalankan php spark migrate terlebih dahulu.']);
        }
        // Bounded admin-only checks. Saving a changed URL invalidates the cache.
        $key = 'stream_api_check_' . hash('sha256', $link->id . '|' . $link->link);
        $result = cache()->get($key);
        if (!is_array($result)) {
            $result = (new VideoHostHealth($links))->check($link);
            if ($result === null) {
                $result = ['status'=>'unknown','message'=>'Tidak ada konfigurasi host aktif yang cocok dengan hostname link ini. Periksa Embed hostnames di API & R2 Storage.'];
            } else {
                cache()->save($key, $result, 15);
            }
        }
        $result = (new \App\Libraries\UpnShareReplacement())->replace($link, $result);
        if (isset($result['replacement_url'])) cache()->delete($key);
        return $this->response->setHeader('Cache-Control','no-store')->setJSON($result);
    }
}
