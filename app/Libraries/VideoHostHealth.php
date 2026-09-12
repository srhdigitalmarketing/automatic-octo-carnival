<?php
namespace App\Libraries;
use App\Entities\Link;
use App\Models\LinkModel;
use App\Models\ThirdPartyApi;

class VideoHostHealth
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

    /** Null means this link is not configured for a supported provider. */
    public function check(Link $link): ?array
    {
        if (!RegisteredStreamHost::matches((string)$link->link) || ! $this->links->supportsProviderStatus()) { return null; }
        if ($this->apis === null) { $this->apis = (new ThirdPartyApi())->whereIn('provider', ['upnshare', 'custom_http', 'vod_catalog'])->where('status', 'active')->findAll(); }
        $matches = [];
        foreach ($this->apis as $api) {
            if (self::matchesHost((string)$link->link, (string)$api->embed_domains)) { $matches[] = $api; }
        }
        if (count($matches) > 1) {
            return $this->persist($link, ['status'=>'unknown','message'=>'Multiple active APIs match this hostname; keep this hostname on only one active provider configuration']);
        }
        if ($matches && $matches[0]->provider === 'vod_catalog') {
            return $this->persist($link, (new VodFileHealth())->check((string)$matches[0]->api_base_url, (string)$link->link));
        }
        if ($matches && $matches[0]->provider === 'custom_http') {
            return $this->persist($link, (new CustomHostClient())->videoStatus((string)$link->link));
        }
        if ($matches) {
            $api = $matches[0]; $key = 'api-' . $api->id;
            if (! isset($this->clients[$key])) {
                $config = new \Config\UpnShare(); $config->apiToken = (string)$api->api_token;
                $this->clients[$key] = new UpnShareClient($config);
            }
        } else { return null; }
        $id = self::videoId((string)$link->link);
        if ($matches && $matches[0]->provider === 'streamhg') {
            $path = (string)parse_url((string)$link->link, PHP_URL_PATH);
            if (preg_match('~(?:^|/)(?:embed-)?([A-Za-z0-9_-]{3,128})\.html$~', $path, $m)) { $id = $m[1]; }
        }
        return $this->persist($link, $this->clients[$key]->videoStatus($id));
    }

    private function persist(Link $link, array $result): array
    {
        $now = date('Y-m-d H:i:s');
        $status = $result['status'];
        // Failed checks cannot resurrect a previously confirmed deleted/error file.
        if ($status === 'unknown' && in_array($link->provider_status, ['deleted','error','processing'], true)) { $status = $link->provider_status; }
        $data = ['provider_status'=>$status, 'provider_message'=>$result['message'], 'provider_checked_at'=>$now, 'last_checked_at'=>$now];
        if (in_array($status, ['deleted','error','processing'], true) || !empty($result['skip_playback'])) {
            $data += ['is_broken'=>1, 'last_failure_at'=>$now, 'last_error'=>$result['message']];
        } elseif (in_array($status, ['available','reachable'], true)) {
            $data += ['is_broken'=>0, 'failure_count'=>0, 'last_error'=>null, 'last_success_at'=>$now];
        }
        if (preg_match('/\b404\b/', (string)$result['message'])) {
            $data['reports_not_working'] = max(1, (int)$link->reports_not_working);
        } elseif (in_array($status, ['available','reachable'], true)) {
            $data['reports_not_working'] = 0;
        }
        if (!$this->links->protect(false)->update((int)$link->id, $data)) { throw new \RuntimeException('Provider status could not be saved'); }
        $this->links->protect(true);
        foreach ($data as $field=>$value) { $link->$field=$value; }
        return ['status'=>$status,'message'=>$result['message']];
    }
}
