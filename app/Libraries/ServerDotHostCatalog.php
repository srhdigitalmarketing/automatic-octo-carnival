<?php
namespace App\Libraries;

/** Read-only publisher API client. Credentials are sent only to the fixed API origin. */
class ServerDotHostCatalog
{
    public const HOST = 'serverdothost.com';

    private $transport;
    public function __construct(?callable $transport = null) { $this->transport = $transport; }

    public function page(string $token, string $term = '', int $page = 1, string $domains = 'bobaplayer.com,serverdothost.com'): array
    {
        return self::normalizePage($this->request(self::listUrl($term, $page), $token), 12, $domains);
    }

    public function detail(string $token, string $id, string $domains = 'bobaplayer.com,serverdothost.com'): array
    {
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/Di', $id)) throw new \InvalidArgumentException('UUID video tidak valid.');
        $body = $this->request('https://'.self::HOST.'/api/v1/videos/'.$id, $token);
        if (!is_array($body['data'] ?? null) || ($body['data']['id'] ?? null) !== $id) throw new \RuntimeException('Detail video ServerDotHost tidak cocok.');
        $page = self::normalizePage(['data'=>[$body['data']], 'meta'=>['current_page'=>1,'last_page'=>1]], 1, $domains);
        if (!$page['items']) throw new \RuntimeException('Detail video ServerDotHost tidak valid.');
        return $page['items'][0];
    }

    private function request(string $url, string $token): array
    {
        if (!preg_match('/^bkp_[a-zA-Z0-9]{64}$/D', $token)) throw new \InvalidArgumentException('Token ServerDotHost tidak valid. Gunakan token videos:read.');
        $headers = ['Accept: application/json', 'Authorization: Bearer '.$token];
        if ($this->transport) {
            $response = ($this->transport)($url, $headers);
        } else {
            $target = (new CustomHostClient())->publicTarget($url);
            $body = ''; $curl = curl_init($url);
            curl_setopt_array($curl, [CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>8,
                CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_PROXY=>'',
                CURLOPT_RESOLVE=>[$target['host'].':443:'.$target['ip']], CURLOPT_HTTPHEADER=>$headers,
                CURLOPT_WRITEFUNCTION=>static function($handle, $chunk) use (&$body) {
                    if (strlen($body) + strlen($chunk) > 2097152) return 0;
                    $body .= $chunk; return strlen($chunk);
                }]);
            $ok = curl_exec($curl); $http = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE); curl_close($curl);
            $response = ['http'=>$ok === false ? 0 : $http, 'body'=>json_decode($body, true)];
        }
        $status = (int)($response['http'] ?? 0);
        if ($status !== 200 || !is_array($response['body'] ?? null)) {
            throw new \RuntimeException('ServerDotHost tidak dapat dibaca (HTTP '.$status.'). Periksa token videos:read dan koneksi.', $status);
        }
        return $response['body'];
    }

    public static function listUrl(string $term = '', int $page = 1): string
    {
        if ($page < 1) throw new \InvalidArgumentException('Halaman API harus lebih besar dari nol.');
        $query = ['page'=>$page];
        if (trim($term) !== '') $query = ['q'=>mb_substr(trim($term), 0, 150)] + $query;
        return 'https://'.self::HOST.'/api/v1/videos?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public static function normalizePage($body, int $limit = 20, string $domains = 'bobaplayer.com,serverdothost.com'): array
    {
        if (!is_array($body) || !isset($body['data']) || !is_array($body['data'])
            || ($body['data'] !== [] && array_keys($body['data']) !== range(0, count($body['data']) - 1))) {
            throw new \RuntimeException('Format daftar video ServerDotHost tidak valid.');
        }
        $meta = $body['meta'] ?? null;
        if (!is_array($meta)) throw new \RuntimeException('Metadata halaman ServerDotHost tidak valid.');
        $current = filter_var($meta['current_page'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $last = filter_var($meta['last_page'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($current === false || $last === false || $current > $last) {
            throw new \RuntimeException('Nomor halaman ServerDotHost tidak valid.');
        }
        $items = []; $seen = [];
        foreach (array_slice($body['data'], 0, min(1000, max(1, $limit))) as $row) {
            if (!is_array($row)) continue;
            $id = self::text($row['id'] ?? '', 128);
            $title = self::text($row['title'] ?? '', 500);
            if ($id === '' || $title === '' || isset($seen[$id])) continue;
            $seen[$id] = true;
            $duration = $row['duration'] ?? null;
            $duration = (is_int($duration) || is_float($duration)) && is_finite((float)$duration) && $duration >= 0 ? (float)$duration : null;
            $embed = self::mediaUrl($row['embed_url'] ?? null, $domains, 'embed');
            $poster = self::mediaUrl($row['poster_url'] ?? null, $domains, 'poster');
            if (($row['moderation_status'] ?? '') === 'blocked') { $embed = ''; $poster = ''; }
            $items[] = [
                'title'=>$title, 'description'=>self::text($row['description'] ?? '', 10000),
                'poster_url'=>$poster, 'auto_poster_url'=>$poster, 'stream_urls'=>$embed === '' ? [] : [$embed], 'movie_code'=>'',
                'categories'=>[], 'year'=>0, 'quality'=>'', 'country'=>'', 'time'=>'',
                'source_provider'=>'serverdothost', 'source_id'=>$id,
                'visibility'=>self::text($row['visibility'] ?? '', 40),
                'processing_status'=>self::text($row['processing_status'] ?? '', 40),
                'moderation_status'=>self::text($row['moderation_status'] ?? '', 40),
                'duration_seconds'=>$duration,
                'source_created_at'=>self::text($row['created_at'] ?? '', 64),
                'source_updated_at'=>self::text($row['updated_at'] ?? '', 64),
            ];
        }
        // Never follow links.next: a response must not select the token's destination.
        return ['items'=>$items, 'current_page'=>(int)$current, 'last_page'=>(int)$last,
            'next_page'=>$current < $last ? (int)$current + 1 : null];
    }

    private static function mediaUrl($value, string $domains, string $kind): string
    {
        if (!is_string($value) || strlen($value) > 2048 || !filter_var($value, FILTER_VALIDATE_URL)) return '';
        $parts = parse_url($value);
        $allowed = preg_split('/[\s,]+/', strtolower(trim($domains)), -1, PREG_SPLIT_NO_EMPTY);
        if (($parts['scheme'] ?? '') !== 'https' || !in_array(strtolower($parts['host'] ?? ''), $allowed, true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || (isset($parts['port']) && $parts['port'] !== 443)) return '';
        $pattern = $kind === 'embed' ? '~^/embed/[a-zA-Z0-9]{16,128}$~D' : '~^/v/[a-zA-Z0-9]{16,128}/media/[a-f0-9-]{36}$~Di';
        return preg_match($pattern, $parts['path'] ?? '') ? $value : '';
    }

    private static function text($value, int $length): string
    {
        return is_string($value) ? mb_substr(trim(strip_tags($value)), 0, $length) : '';
    }
}
