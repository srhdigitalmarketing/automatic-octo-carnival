<?php
namespace App\Libraries;
use RuntimeException;

/** Token-free, bounded HTTP check. Does not claim that a video inside a page is playable. */
class CustomHostClient
{
    private $transport;
    public function __construct(?callable $transport = null) { $this->transport = $transport; }
    public function videoStatus(string $url): array
    {
        try {
            $target = $this->publicTarget($url);
            if ($this->transport) { $status = (int)($this->transport)($url, $target); }
            else {
                $curl = curl_init($url);
                $received = 0;
                curl_setopt_array($curl, [
                    CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>4, CURLOPT_TIMEOUT=>8,
                    CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
                    CURLOPT_PROXY=>'', CURLOPT_RESOLVE=>[$target['host'].':'.$target['port'].':'.$target['ip']],
                    CURLOPT_HTTPHEADER=>['Range: bytes=0-8191'],
                    CURLOPT_WRITEFUNCTION=>static function($handle,$chunk) use (&$received) {
                        $received += strlen($chunk); return $received > 8192 ? 0 : strlen($chunk);
                    },
                ]);
                curl_exec($curl);
                $status = (int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
                curl_close($curl);
            }
        } catch (\Throwable $error) {
            return ['status'=>'unknown','skip_playback'=>true,'message'=>'Custom hostname: URL tidak valid, bukan alamat publik, atau koneksi gagal.'];
        }
        if ($status >= 200 && $status < 300) {
            return ['status'=>'reachable','message'=>'HTTP '.$status.' reachable. Ketersediaan video di dalam player belum terverifikasi.'];
        }
        return ['status'=>'unknown','skip_playback'=>$status === 0 || $status >= 400,
            'message'=>'Custom hostname check failed (HTTP '.$status.'). Redirect tidak diikuti; gunakan URL embed langsung.'];
    }

    public function publicTarget(string $url): array
    {
        if (strlen($url)>4096 || !filter_var($url,FILTER_VALIDATE_URL)) { throw new RuntimeException('Invalid URL'); }
        $parts=parse_url($url); $scheme=strtolower($parts['scheme'] ?? ''); $host=strtolower($parts['host'] ?? '');
        $port=$parts['port'] ?? ($scheme==='https' ? 443 : 80);
        if (!in_array($scheme,['http','https'],true) || $host==='' || isset($parts['user']) || isset($parts['pass']) || !in_array($port,[80,443],true) || strpos($host,':')!==false) { throw new RuntimeException('Invalid target'); }
        $ips=filter_var($host,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) ? [$host] : @gethostbynamel($host);
        if (!$ips) { throw new RuntimeException('DNS failure'); }
        foreach ($ips as $ip) {
            if (!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)
                || preg_match('/^(0\.|127\.|169\.254\.|192\.0\.0\.|198\.(18|19)\.|22[4-9]\.|23[0-9]\.)/',$ip)
                || (ip2long($ip)>=ip2long('100.64.0.0') && ip2long($ip)<=ip2long('100.127.255.255'))) { throw new RuntimeException('Private/reserved address'); }
        }
        return ['host'=>$host,'port'=>$port,'ip'=>$ips[0]];
    }
}
