<?php
namespace App\Libraries;

use App\Models\ThirdPartyApi;

/** Read-only, request-local lookup for admin stream health controls. */
class RegisteredStreamHost
{
    private static $domains;
    private static $available = false;

    public static function available(): bool
    {
        self::matches('');
        return self::$available;
    }

    public static function matches(string $url): bool
    {
        if (self::$domains === null) {
            self::$domains = [];
            try {
                $model = new ThirdPartyApi();
                $db = \Config\Database::connect();
                if (!$db->tableExists('third_party_apis') || array_diff(['provider','status','embed_domains'], $db->getFieldNames('third_party_apis'))) return false;
                $apis = $model->whereIn('provider', ['upnshare','custom_http','vod_catalog'])->where('status','active')->findAll();
                foreach ($apis as $api) self::$domains[] = (string)$api->embed_domains;
                self::$available = true;
            } catch (\Throwable $error) { return false; }
        }
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http','https'], true)) return false;
        foreach (self::$domains as $domains) {
            if (VideoHostHealth::matchesHost($url, $domains)) return true;
        }
        return false;
    }
}
