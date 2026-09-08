<?php
namespace App\Controllers\Admin;
use App\Libraries\CloudflareR2Storage;
use App\Libraries\RemoteBannerImage;
use App\Libraries\VodCatalog;
use App\Libraries\AutoGrabMatch;
use App\Models\MovieModel;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;
class LatestGrab extends \App\Controllers\BaseController
{
    public function run()
    {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(403)->setJSON(['error'=>'Invalid request']);
        $this->response->setHeader('Cache-Control','no-store');
        $lock = fopen(WRITEPATH.'cache/latest-grab.lock','c');
        if (!$lock || !flock($lock,LOCK_EX | LOCK_NB)) { if ($lock) fclose($lock); return $this->response->setStatusCode(409)->setJSON(['error'=>'Impor lain masih berjalan.']); }
        try {
            if ($this->request->getPost('action') === 'categories') {
                $api = (new ThirdPartyApi())->where('provider','vod_catalog')->where('status','active')->find((int)$this->request->getPost('api_id'));
                if (!$api) throw new \RuntimeException('API tidak tersedia');
                $items = (new VodCatalog())->search($api->api_base_url, '', true);
                $categories = [];
                foreach ($items as $item) { foreach ($item['categories'] as $category) { $categories[$category] = $category; } }
                sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
                return $this->response->setJSON(['categories'=>array_values($categories)]);
            }
            $storage = CloudflareR2Storage::active();
            if (!$storage) throw new \RuntimeException('Siapkan R2 aktif terlebih dahulu.');
            $owner = hash('sha256', session_id());
            if ($this->request->getPost('action') === 'start') {
                $count = filter_var($this->request->getPost('count'), FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100]]);
                $api = (new ThirdPartyApi())->where('provider','vod_catalog')->where('status','active')->find((int)$this->request->getPost('api_id'));
                if (!$count || !$api) throw new \RuntimeException('Pilih API aktif dan jumlah 1–100.');
                $items = (new VodCatalog())->search($api->api_base_url, '', true);
                $category = trim((string)$this->request->getPost('category'));
                if ($category !== '') { $items = array_values(array_filter($items, static function($item) use ($category) { return in_array($category, $item['categories'], true); })); }
                $token = bin2hex(random_bytes(24));
                $job = ['owner'=>$owner,'items'=>$items,'cursor'=>0,'success'=>0,'target'=>$count];
                if (!cache()->save('latest_'.$token,$job,7200)) throw new \RuntimeException('Cache proses tidak tersedia.');
                return $this->response->setJSON(['token'=>$token,'total'=>count($items),'target'=>$count]);
            }
            $token = (string)$this->request->getPost('token');
            if (!preg_match('/^[a-f0-9]{48}$/D',$token)) throw new \RuntimeException('Proses tidak valid.');
            $job = cache()->get('latest_'.$token);
            if (!$job || !hash_equals($job['owner'],$owner)) throw new \RuntimeException('Proses kedaluwarsa. Mulai kembali.');
            if ($job['cursor'] >= count($job['items']) || $job['success'] >= $job['target']) return $this->response->setJSON(['done'=>true]);
            $item = $job['items'][$job['cursor']++];
            $result = $this->importItem($item, $storage);
            if ($result['state'] === 'success') $job['success']++;
            cache()->save('latest_'.$token,$job,7200);
            return $this->response->setJSON($result + ['title'=>$item['title'],'processed'=>$job['cursor'],'success'=>$job['success'],'done'=>$job['cursor'] >= count($job['items']) || $job['success'] >= $job['target']]);
        } catch (\Throwable $e) { return $this->response->setStatusCode(400)->setJSON(['error'=>'Proses tidak dapat dilanjutkan. Periksa jumlah, API, R2 dan cache.']); }
        finally { flock($lock,LOCK_UN); fclose($lock); }
    }
    private function importItem(array $item, $storage): array
    {
        $code = AutoGrabMatch::code($item['movie_code'] ?? '');
        if (!$code || stripos($item['title'],'English-Subtitle') !== false || empty($item['auto_poster_url']) || empty($item['stream_urls'])) return ['state'=>'skipped','message'=>'Kode, poster atau stream tidak lengkap / subtitle.'];
        // Internal deterministic Video ID, not an asserted IMDb catalog identifier.
        $videoId = 'tt'.base_convert(substr(hash('sha256',$code),0,12),16,10);
        $db = db_connect();
        if ($db->table('movies')->where('imdb_id',$videoId)->countAllResults() || $db->table('links')->whereIn('link',$item['stream_urls'])->countAllResults()) return ['state'=>'skipped','message'=>'Video sudah tersedia.'];
        $url = null; $transaction = false;
        try {
            $url = (new RemoteBannerImage())->upload($item['auto_poster_url'],$storage);
            $db->transBegin(); $transaction = true;
            $data = ['imdb_id'=>$videoId,'title'=>'['.$code.'] '.mb_substr($item['title'],0,180),'description'=>$item['description'], 'banner'=>$url,'type'=>'movie','status'=>'public'];
            if ($item['year'] >= 1900 && $item['year'] <= 2050) $data['year']=$item['year'];
            if ($item['quality']) $data['quality']=$item['quality'];
            if ($item['country']) $data['country']=mb_substr($item['country'],0,100);
            if (preg_match('/^(\d{1,3}):(\d{2}):(\d{2})$/',$item['time'],$m)) $data['duration']=(int)ceil(((int)$m[1]*3600+(int)$m[2]*60+(int)$m[3])/60);
            $model = new MovieModel(); $id = $model->insert($data);
            if (!$id) throw new \RuntimeException('Movie insert failed');
            foreach ($item['stream_urls'] as $stream) {
                if (!(new LinkModel())->insert(['movie_id'=>$id,'link'=>$stream,'type'=>'stream','host_priority'=>100])) throw new \RuntimeException('Link insert failed');
            }
            if (!$db->transStatus() || !$db->transCommit()) throw new \RuntimeException('Commit failed');
            $transaction=false;
            return ['state'=>'success','message'=>'Video #'.$id.' dibuat; poster R2 dan stream tersimpan.'];
        } catch (\Throwable $e) {
            if ($transaction) $db->transRollback();
            if ($url) { try { $storage->deletePublicUrl($url); } catch (\Throwable $ignored) {} }
            return ['state'=>'failed','message'=>'Gagal mengambil poster atau menyimpan video.'];
        }
    }
}
