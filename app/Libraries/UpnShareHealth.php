<?php
namespace App\Libraries;
use App\Entities\Link;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;

class UpnShareHealth
{
    private $links;
    private $apis;
    private $clients = [];
    public function __construct(LinkModel $links) { $this->links = $links; }

    public static function videoId(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) { return ''; }
        $id = trim((string) ($parts['fragment'] ?? ''));
        if ($id === '') { $id = basename(rtrim((string) ($parts['path'] ?? ''), '/')); }
        return preg_match('/^[A-Za-z0-9_-]{3,128}$/', $id) ? $id : '';
    }
    public static function matchesHost(string $url, string $domains): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $allowed = preg_split('/[\s,]+/', strtolower(trim($domains)), -1, PREG_SPLIT_NO_EMPTY);
        return $host !== '' && in_array($host, $allowed, true);
    }

    /** Null means this link is not configured for UPNShare. */
    public function check(Link $link): ?array
    {
        if (! $this->links->supportsProviderStatus()) { return null; }
        if ($this->apis === null) { $this->apis = (new ThirdPartyApi())->where('provider', 'upnshare')->where('status', 'active')->findAll(); }
        $matches = [];
        foreach ($this->apis as $api) {
            if ((! empty($link->api_id) && (int)$link->api_id === (int)$api->id)
                || (empty($link->api_id) && self::matchesHost((string)$link->link, (string)$api->embed_domains))) { $matches[] = $api; }
        }
        if (count($matches) > 1) {
            return $this->persist($link, ['status'=>'unknown','message'=>'Multiple UPNShare accounts match this host; select the correct account on the link']);
        }
        if ($matches) {
            $api = $matches[0]; $key = 'api-' . $api->id;
            if (! isset($this->clients[$key])) {
                $config = new \Config\UpnShare(); $config->apiToken = (string)$api->api_token;
                $this->clients[$key] = new UpnShareClient($config);
            }
        } else {
            $config = config('UpnShare');
            if (empty($link->api_id) && !empty($config->apiToken) && self::matchesHost((string)$link->link, $config->linkHosts)) {
                $key = 'environment';
                if (!isset($this->clients[$key])) { $this->clients[$key] = new UpnShareClient($config); }
            } else { return null; }
        }
        $id = trim((string)$link->upnshare_video_id) ?: self::videoId((string)$link->link);
        return $this->persist($link, $this->clients[$key]->videoStatus($id));
    }

    private function persist(Link $link, array $result): array
    {
        $now = date('Y-m-d H:i:s');
        $status = $result['status'];
        // Failed checks cannot resurrect a previously confirmed deleted/error file.
        if ($status === 'unknown' && in_array($link->provider_status, ['deleted','error','processing'], true)) { $status = $link->provider_status; }
        $data = ['provider_status'=>$status, 'provider_message'=>$result['message'], 'provider_checked_at'=>$now, 'last_checked_at'=>$now];
        if (in_array($status, ['deleted','error','processing'], true)) {
            $data += ['is_broken'=>1, 'last_failure_at'=>$now, 'last_error'=>$result['message']];
        } elseif ($status === 'available') {
            $data += ['is_broken'=>0, 'failure_count'=>0, 'last_error'=>null, 'last_success_at'=>$now];
        }
        if (!$this->links->protect(false)->update((int)$link->id, $data)) { throw new \RuntimeException('UPNShare status could not be saved'); }
        $this->links->protect(true);
        foreach ($data as $field=>$value) { $link->$field=$value; }
        return ['status'=>$status,'message'=>$result['message']];
    }
}
