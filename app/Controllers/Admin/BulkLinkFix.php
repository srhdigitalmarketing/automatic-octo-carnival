<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;
use App\Libraries\VideoHostHealth;
use App\Libraries\UpnShareReplacement;
class BulkLinkFix extends BaseController
{
    public function run()
    {
        if (strtolower($this->request->getMethod()) !== 'post' || !$this->request->isAJAX()) return $this->response->setStatusCode(405)->setJSON(['message'=>'Invalid request']);
        $host = strtolower(trim((string)$this->request->getPost('host')));
        if (!filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME) || strpos($host,'.') === false) return $this->response->setStatusCode(422)->setJSON(['message'=>'Pilih satu hostname terlebih dahulu.']);
        $apis = array_filter((new ThirdPartyApi())->where('provider','upnshare')->where('status','active')->findAll(), static fn($api)=>VideoHostHealth::matchesHost('https://'.$host.'/',(string)$api->embed_domains));
        if (count($apis)!==1) return $this->response->setStatusCode(422)->setJSON(['message'=>'Hostname harus cocok dengan tepat satu API UPNShare aktif.']);
        $links = new LinkModel();
        if (!$links->supportsProviderStatus()) return $this->response->setStatusCode(409)->setJSON(['message'=>'Jalankan php spark migrate.']);
        $query = static function() use ($host) {
            return db_connect()->table('links')->where('type','stream')
                ->where("LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(link, '://', -1), '/', 1), '#', 1))",$host)
                ->groupStart()->where('provider_status','deleted')->orLike('provider_message','404')->orLike('last_error','404')->groupEnd();
        };
        if ($this->request->getPost('action') === 'start') {
            $row=$query()->select('COUNT(*) AS total, MAX(id) AS max_id',false)->get()->getRowArray();
            return $this->response->setJSON(['total'=>(int)$row['total'],'max_id'=>(int)$row['max_id']]);
        }
        $lock=fopen(WRITEPATH.'cache/bulk-link-fix.lock','c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) return $this->response->setStatusCode(409)->setJSON(['message'=>'Proses lain sedang berjalan. Coba lagi.']);
        try {
            $row=$query()->where('id >',max(0,(int)$this->request->getPost('cursor')))->where('id <=',max(0,(int)$this->request->getPost('max_id')))->orderBy('id','ASC')->get(1)->getRowArray();
            if (!$row) return $this->response->setJSON(['done'=>true]);
            $link=$links->find((int)$row['id']);
            try {
                $result=(new VideoHostHealth($links))->check($link);
                if ($result===null) $result=['status'=>'unknown','message'=>'API tidak tersedia.'];
                $result=(new UpnShareReplacement())->replace($link,$result);
                $state=isset($result['replacement_url'])?'success':(($result['status']??'')==='unknown'?'failed':'skipped');
                cache()->delete('stream_api_check_'.hash('sha256',$link->id.'|'.$link->link));
                return $this->response->setHeader('Cache-Control','no-store')->setJSON(['id'=>(int)$link->id,'state'=>$state,'message'=>$result['message']]);
            } catch (\Throwable $error) {
                return $this->response->setJSON(['id'=>(int)$link->id,'state'=>'failed','message'=>'Pemeriksaan gagal. Coba ulang nanti.']);
            }
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
