<?php
namespace App\Libraries;

class ProviderConnection
{
    private $transport;
    public function __construct(?callable $transport = null) { $this->transport = $transport; }

    public function check(object $api): array
    {
        $result = ['state'=>'disconnected','label'=>'Tidak terhubung','message'=>'Koneksi gagal. Periksa kredensial, izin akses, dan jaringan server.', 'checked_at'=>gmdate('c')];
        if ($api->status !== 'active') {
            return array_merge($result, ['state'=>'paused','label'=>'Tidak diperiksa','message'=>'Konfigurasi sedang Paused.']);
        }
        try {
            if ($api->provider === 'serverdothost') {
                $page = (new ServerDotHostCatalog($this->transport))->page((string)$api->api_token, '', 1, (string)$api->embed_domains);
                return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Autentikasi ServerDotHost berhasil; '.count($page['items']).' video pada halaman pertama.']);
            }
            if ($api->provider === 'vod_catalog') {
                (new VodCatalog())->search((string)$api->api_base_url, 'ab-123');
                return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Endpoint VOD mengembalikan JSON list yang valid.']);
            }
            if ($api->provider === 'custom_http') {
                return array_merge($result, ['state'=>'paused','label'=>'Tanpa API','message'=>'Pemeriksaan HTTP per link. Gunakan Cek file pada Stream Links atau cron.']);
            }
            if ($api->provider === 'cloudflare_r2') {
                CloudflareR2Storage::checkConnection($api);
                return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Autentikasi dan akses bucket R2 berhasil. Izin upload dan URL publik belum diuji.']);
            }
            if (!in_array($api->provider, ['upnshare'], true) || trim((string)$api->api_token) === '') { return $result; }
            if ($this->transport) { $response = ($this->transport)($api); }
            else {
                $options = ['timeout'=>8,'connect_timeout'=>4,'http_errors'=>false,'allow_redirects'=>false,'verify'=>true];
                $options['headers'] = ['api-token'=>(string)$api->api_token];
                $url = 'https://upnshare.com/api/v1/video/manage?page=1&perPage=1';
                $http = \Config\Services::curlrequest([], null, null, false)->get($url, $options);
                $response = ['http'=>(int)$http->getStatusCode(),'body'=>json_decode((string)$http->getBody(), true)];
            }
            $body = $response['body'] ?? null;
            $valid = ($response['http'] ?? 0) === 200 && is_array($body) && isset($body['data']) && is_array($body['data']);
            if ($valid) { return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Autentikasi API berhasil.']); }
            $result['message'] = 'API menolak koneksi atau respons tidak valid (HTTP ' . (int)($response['http'] ?? 0) . '). Periksa token dan izin akses.';
        } catch (\Throwable $error) {
            // Exceptions and response bodies can contain API credentials. Never expose them.
        }
        return $result;
    }
}
