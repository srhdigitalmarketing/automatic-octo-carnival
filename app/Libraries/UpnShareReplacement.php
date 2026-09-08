<?php
namespace App\Libraries;
use App\Models\ThirdPartyApi;
use App\Models\MovieModel;

class UpnShareReplacement
{
    public function replace($link, array $result): array
    {
        if (($result['status'] ?? '') !== 'deleted' && !preg_match('/\b404\b/', (string) ($result['message'] ?? ''))) return $result;
        $matches = [];
        foreach ((new ThirdPartyApi())->where('provider','upnshare')->where('status','active')->findAll() as $api) {
            if (VideoHostHealth::matchesHost((string) $link->link, (string) $api->embed_domains)) $matches[] = $api;
        }
        if (count($matches) !== 1) return $result;
        $movie = (new MovieModel())->find((int) $link->movie_id);
        if (!$movie) return $result;
        $config = new \Config\UpnShare(); $config->apiToken = (string) $matches[0]->api_token;
        $id = (new UpnShareClient($config))->replacementId((string) $movie->title, VideoHostHealth::videoId((string) $link->link));
        if ($id === null) {
            $result['message'] .= ' Pengganti belum ditemukan: diperlukan satu judul yang sama persis dan video berstatus tersedia.';
            return $result;
        }
        $url = 'https://' . parse_url((string) $link->link, PHP_URL_HOST) . '/#' . rawurlencode($id);
        $db = db_connect();
        if ($db->table('links')->where('movie_id', $link->movie_id)->where('link',$url)->countAllResults()) {
            $result['message'] .= ' Link pengganti sudah ada pada video ini.'; return $result;
        }
        $now = date('Y-m-d H:i:s');
        $ok = $db->table('links')->where('id',$link->id)->where('link',$link->link)->update([
            'link'=>$url,'upnshare_video_id'=>$id,'api_id'=>$matches[0]->id,
            'provider_status'=>'available','provider_message'=>'Link UPNShare diganti otomatis sesuai judul.',
            'provider_checked_at'=>$now,'last_checked_at'=>$now,'last_success_at'=>$now,
            'is_broken'=>0,'failure_count'=>0,'last_error'=>null,'reports_not_working'=>0,
        ]);
        if (!$ok || $db->affectedRows() !== 1) {
            $result['message'] .= ' Link berubah saat pemeriksaan; muat ulang halaman.'; return $result;
        }
        return ['status'=>'available','message'=>'Link UPNShare berhasil diganti dan disimpan otomatis.','replacement_url'=>$url,'replacement_id'=>$id,'api_id'=>(int)$matches[0]->id];
    }
}
