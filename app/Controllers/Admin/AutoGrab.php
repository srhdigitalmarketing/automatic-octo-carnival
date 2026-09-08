<?php
namespace App\Controllers\Admin;
use App\Libraries\AutoGrabMatch;
use App\Libraries\CloudflareR2Storage;
use App\Libraries\RemoteBannerImage;
use App\Libraries\VodCatalog;
use App\Models\MovieModel;
use App\Models\ThirdPartyApi;
use App\Models\LinkModel;

class AutoGrab extends \App\Controllers\BaseController
{
    public function run()
    {
        if (!$this->request->isAJAX()) { return $this->response->setStatusCode(403)->setJSON(['error'=>'Invalid request']); }
        $this->response->setHeader('Cache-Control', 'no-store');
        $lock = fopen(WRITEPATH . 'cache/auto-grab.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); return $this->response->setStatusCode(409)->setJSON(['error'=>'Proses lain masih berjalan. Coba kembali.']); }
        $db = db_connect(); $uploaded = null; $storage = null; $transaction = false;
        try {
            $api = (new ThirdPartyApi())->where('provider','vod_catalog')->where('status','active')->find((int)$this->request->getPost('api_id'));
            $storage = CloudflareR2Storage::active();
            if (!$api || !$storage) { throw new \RuntimeException('Pilih API VOD aktif dan siapkan R2 aktif.'); }
            $query = $db->table('movies')->where('type','movie')->where(MovieModel::IMAGE_LINK_SQL . ' = 0', null, false);
            if ($this->request->getPost('action') === 'start') {
                $count = $query->countAllResults(false);
                $max = (int)($query->selectMax('id')->get()->getRowArray()['id'] ?? 0);
                return $this->response->setJSON(['total'=>$count,'max_id'=>$max]);
            }
            $cursor = max(0, (int)$this->request->getPost('cursor'));
            $max = max(0, (int)$this->request->getPost('max_id'));
            $movie = $query->where('id >',$cursor)->where('id <=',$max)->orderBy('id','ASC')->get(1)->getRowArray();
            if (!$movie) { return $this->response->setJSON(['done'=>true]); }
            $result = ['id'=>(int)$movie['id'], 'title'=>$movie['title'], 'state'=>'skipped'];
            $code = AutoGrabMatch::code($movie['title']);
            if ($code === '') { return $this->response->setJSON($result + ['message'=>'Kode tidak valid atau English-Subtitle.']); }
            try {
                $item = AutoGrabMatch::select($code, (new VodCatalog())->search($api->api_base_url, $code));
                if (!$item) { return $this->response->setJSON($result + ['message'=>'Tidak ada satu hasil dengan kode persis dan poster.']); }
                $uploaded = (new RemoteBannerImage())->upload($item['poster_url'], $storage);
                $db->transBegin(); $transaction = true;
                $db->table('movies')->where('id', $movie['id'])->where(MovieModel::IMAGE_LINK_SQL . ' = 0', null, false)->update(['banner'=>$uploaded]);
                if ($db->affectedRows() !== 1) { throw new \RuntimeException('Gambar sudah berubah; hasil grab tidak diterapkan.'); }
                $links = new LinkModel();
                foreach ($item['stream_urls'] as $url) {
                    if (!$db->table('links')->where('movie_id',$movie['id'])->where('type','stream')->where('link',$url)->countAllResults()) {
                        if (!$links->insert(['movie_id'=>$movie['id'],'type'=>'stream','link'=>$url,'host_priority'=>100])) { throw new \RuntimeException('Stream link gagal disimpan.'); }
                    }
                }
                if (!$db->transStatus() || !$db->transCommit()) { throw new \RuntimeException('Data gagal disimpan.'); }
                $transaction = false; $uploaded = null;
                $result['state'] = 'success';
                return $this->response->setJSON($result + ['message'=>'Poster tersimpan di R2; stream link ditambahkan.']);
            } catch (\Throwable $e) {
                if ($transaction) { $db->transRollback(); $transaction = false; }
                if ($uploaded) { try { $storage->deletePublicUrl($uploaded); } catch (\Throwable $ignored) {} $uploaded = null; }
                $result['state'] = 'failed';
                return $this->response->setJSON($result + ['message'=>'Grab gagal. Periksa API, gambar, dan koneksi R2.']);
            }
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(400)->setJSON(['error'=>'Tidak dapat memulai. Pastikan API VOD dan R2 aktif.']);
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }
}
