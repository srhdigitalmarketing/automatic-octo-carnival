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

    public function search(string $host, string $term, bool $latest = false): array
    {
        $url = 'https://' . self::hostname($host) . '/api.php/provide/vod?' . http_build_query($latest ? ['ac'=>'detail'] : ['ac'=>'detail', 'wd'=>mb_substr($term, 0, 150)]);
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
        if (!$ok || $status !== 200) { throw new \RuntimeException('API VOD tidak dapat dihubungi (HTTP ' . $status . ').', (int)$status); }
        return self::normalize(json_decode($body, true), $latest ? 1000 : 20);
    }

    public static function normalize($body, int $limit = 20): array
    {
        if (!is_array($body) || !isset($body['list']) || !is_array($body['list'])) { throw new \RuntimeException('Format respons API VOD tidak valid.'); }
        if (isset($body['code']) && (int)$body['code'] !== 1) { throw new \RuntimeException('API VOD melaporkan kegagalan.'); }
        $items = [];
        foreach (array_slice($body['list'], 0, min(1000, max(1, $limit))) as $row) {
            if (!is_array($row)) { continue; }
            $title = $row['name'] ?? $row['vod_name'] ?? null;
            if (!is_string($title) || trim($title) === '') { continue; }
            $poster = self::httpUrl($row['poster_url'] ?? $row['vod_pic'] ?? '') ?: self::httpUrl($row['thumb_url'] ?? '');
            $description = $row['description'] ?? $row['vod_content'] ?? '';
            $streams = [];
            $episodes = $row['episodes'] ?? [];
            if (is_array($episodes)) {
                $servers = isset($episodes['server_data']) ? [$episodes] : $episodes;
                foreach ($servers as $server) {
                    if (!is_array($server) || !is_array($server['server_data'] ?? null)) { continue; }
                    foreach ($server['server_data'] as $episode) {
                        $url = is_array($episode) ? self::httpUrl($episode['link_embed'] ?? '') : '';
                        if ($url !== '') { $streams[$url] = $url; }
                        if (count($streams) >= 20) { break 2; }
                    }
                }
            }
            $items[] = ['title'=>mb_substr(strip_tags($title),0,500), 'poster_url'=>$poster,
                'description'=>mb_substr(strip_tags(is_string($description) ? $description : ''),0,10000),
                'auto_poster_url'=>self::httpUrl($row['poster_url'] ?? ''),
                'movie_code'=>is_string($row['movie_code'] ?? null) ? $row['movie_code'] : '',
                'categories'=>array_values(array_unique(array_filter(array_map(static function($v) { return is_string($v) ? trim(strip_tags($v)) : ''; }, is_array($row['category'] ?? null) ? $row['category'] : [])))),
                'year'=>is_scalar($row['year'] ?? null) ? (int)$row['year'] : 0,
                'quality'=>is_string($row['quality'] ?? null) ? mb_substr($row['quality'],0,20) : '',
                'country'=>is_array($row['country'] ?? null) ? implode(', ',array_filter($row['country'],'is_string')) : '',
                'time'=>is_string($row['time'] ?? null) ? $row['time'] : '',
                'stream_urls'=>array_values($streams)];
        }
        return $items;
    }

    private static function httpUrl($value): string
    {
        if (!is_string($value)) { return ''; }
        $value = trim($value);
        // Accept Markdown-wrapped links copied from documentation or chat.
        if (preg_match('~^\[[^\]]*\]\((https?://[^\s]+)\)$~i', $value, $match)) { $value = $match[1]; }
        return preg_match('~^https?://~i', $value) && filter_var($value, FILTER_VALIDATE_URL) && !parse_url($value, PHP_URL_USER) && !parse_url($value, PHP_URL_PASS) ? $value : '';
    }
}
