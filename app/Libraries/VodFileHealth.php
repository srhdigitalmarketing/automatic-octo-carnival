<?php
namespace App\Libraries;
class VodFileHealth
{
    public function check(string $host, string $url): array
    {
        $id = VideoHostHealth::videoId($url);
        if ($id === '') { return ['status'=>'unknown','message'=>'VOD: kode video tidak dapat dibaca dari URL.']; }
        try { return self::classify($url, (new VodCatalog())->search($host, $id)); }
        catch (\Throwable $e) {
            $code = (int)$e->getCode();
            return ['status'=>'unknown','skip_playback'=>in_array($code,[404,410,522],true),
                'message'=>'VOD check failed' . ($code ? ' (HTTP '.$code.')' : ': koneksi atau respons tidak valid.')];
        }
    }
    public static function classify(string $url, array $items): array
    {
        foreach ($items as $item) {
            if (in_array($url, $item['stream_urls'] ?? [], true)) {
                return ['status'=>'unknown','message'=>'Link embed ditemukan persis di katalog VOD. Playback file belum diverifikasi.'];
            }
        }
        return ['status'=>'unknown','message'=>'Link embed tidak ditemukan dalam hasil VOD. Hasil kosong bukan bukti file dihapus.'];
    }
}
