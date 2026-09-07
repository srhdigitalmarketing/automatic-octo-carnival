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
            if ($api->provider === 'custom_http') {
                return array_merge($result, ['state'=>'paused','label'=>'Tanpa API','message'=>'Pemeriksaan HTTP per link. Gunakan Cek file pada Stream Links atau cron.']);
            }
            if ($api->provider === 'cloudflare_r2') {
                CloudflareR2Storage::checkConnection($api);
                return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Autentikasi dan akses bucket R2 berhasil. Izin upload dan URL publik belum diuji.']);
            }
            if (!in_array($api->provider, ['upnshare','vidhide'], true) || trim((string)$api->api_token) === '') { return $result; }
            if ($this->transport) { $response = ($this->transport)($api); }
            else {
                $options = ['timeout'=>8,'connect_timeout'=>4,'http_errors'=>false,'allow_redirects'=>false,'verify'=>true];
                if ($api->provider === 'upnshare') {
                    $options['headers'] = ['api-token'=>(string)$api->api_token];
                    $url = 'https://upnshare.com/api/v1/video/manage?page=1&perPage=1';
                } else {
                    $url = 'https://earnvidsapi.com/api/account/info';
                    $options['query'] = ['key'=>(string)$api->api_token];
                }
                $http = \Config\Services::curlrequest([], null, null, false)->get($url, $options);
                $response = ['http'=>(int)$http->getStatusCode(),'body'=>json_decode((string)$http->getBody(), true)];
            }
            $body = $response['body'] ?? null;
            $valid = ($response['http'] ?? 0) === 200 && is_array($body) && ($api->provider === 'upnshare'
                ? isset($body['data']) && is_array($body['data'])
                : (int)($body['status'] ?? 0) === 200 && is_array($body['result'] ?? null) && isset($body['result']['login']));
            if ($valid) { return array_merge($result, ['state'=>'connected','label'=>'Terhubung','message'=>'Autentikasi API berhasil.']); }
            $result['message'] = 'API menolak koneksi atau respons tidak valid (HTTP ' . (int)($response['http'] ?? 0) . '). Periksa token dan izin akses.';
        } catch (\Throwable $error) {
            // Exceptions and response bodies can contain API credentials. Never expose them.
        }
        return $result;
    }
}
