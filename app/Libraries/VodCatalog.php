<?php
namespace App\Libraries;

class VodCatalog
{
    public static function hostname(string $host): string
    {
        $host = strtolower(trim($host));
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strpos($host, '.') === false || strlen($host) > 253) {
            throw new \InvalidArgumentException('Masukkan hostname saja, tanpa https://, port, atau path.');
        }
        return $host;
    }

    public function search(string $host, string $term): array
    {
        $url = 'https://' . self::hostname($host) . '/api.php/provide/vod?' . http_build_query(['ac'=>'detail', 'wd'=>mb_substr($term, 0, 150)]);
        $target = (new CustomHostClient())->publicTarget($url);
        $body = ''; $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>7,
            CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_PROXY=>'',
            CURLOPT_RESOLVE=>[$target['host'].':443:'.$target['ip']], CURLOPT_HTTPHEADER=>['Accept: application/json'],
            CURLOPT_WRITEFUNCTION=>static function($handle, $chunk) use (&$body) {
                if (strlen($body) + strlen($chunk) > 2097152) { return 0; }
                $body .= $chunk; return strlen($chunk);
            }]);
        $ok = curl_exec($curl); $status = curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
        if (!$ok || $status !== 200) { throw new \RuntimeException('API VOD tidak dapat dihubungi (HTTP ' . $status . ').'); }
        return self::normalize(json_decode($body, true));
    }

    public static function normalize($body): array
    {
        if (!is_array($body) || !isset($body['list']) || !is_array($body['list'])) { throw new \RuntimeException('Format respons API VOD tidak valid.'); }
        $items = [];
        foreach (array_slice($body['list'], 0, 20) as $row) {
            if (!is_array($row) || !is_scalar($row['vod_name'] ?? null)) { continue; }
            $poster = is_string($row['vod_pic'] ?? null) ? $row['vod_pic'] : '';
            if (!preg_match('~^https?://~i', $poster) || !filter_var($poster, FILTER_VALIDATE_URL)) { $poster = ''; }
            $items[] = ['title'=>mb_substr(strip_tags((string)$row['vod_name']),0,500), 'poster_url'=>$poster,
                'description'=>mb_substr(strip_tags(is_string($row['vod_content'] ?? null) ? $row['vod_content'] : ''),0,10000)];
        }
        return $items;
    }
}
