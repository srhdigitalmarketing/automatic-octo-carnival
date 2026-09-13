<?php
namespace App\Libraries;

/** Per-account overrides stored in existing settings; no API schema migration is required. */
class HostFileChecks
{
    private const PREFIX = 'host_file_check_';
    private static $overrides;
    private static bool $readable = true;

    public static function supported(object $api): bool
    {
        return in_array($api->provider ?? '', ['upnshare','custom_http','vod_catalog','serverdothost'], true);
    }

    public static function enabled(object $api, bool $fresh = false): bool
    {
        if (!self::supported($api)) return false;
        $default = $api->provider !== 'serverdothost';
        if (empty($api->id)) return $default;
        if ($fresh) self::reset();
        self::load();
        return self::$readable && (self::$overrides[(int)$api->id] ?? $default);
    }

    public static function available(): bool
    {
        self::load();
        return self::$readable;
    }

    public static function reset(): void
    {
        self::$overrides = null;
        self::$readable = true;
    }

    private static function load(): void
    {
        if (self::$overrides !== null) return;
        self::$overrides = [];
        try {
            $db = \Config\Database::connect();
            // Old installations without saved overrides keep the previous provider defaults.
            if (!$db->tableExists('settings')) return;
            $rows = $db->table('settings')->select('name,value')->like('name', self::PREFIX, 'after')->get()->getResultArray();
            foreach ($rows as $row) {
                if (preg_match('/^host_file_check_([1-9][0-9]*)$/D', $row['name'], $match)) {
                    self::$overrides[(int)$match[1]] = in_array($row['value'], ['1','true'], true);
                }
            }
        } catch (\Throwable $error) {
            self::$readable = false;
        }
    }

    public static function save(object $api, bool $enabled): void
    {
        if (!self::supported($api) || empty($api->id)) throw new \InvalidArgumentException('Host tidak mendukung pengaturan cek file.');
        $db = \Config\Database::connect();
        if (!$db->tableExists('settings') || array_diff(['name','value','data_type'], $db->getFieldNames('settings'))) {
            throw new \RuntimeException('Tabel settings belum tersedia atau tidak lengkap.');
        }
        if (!$db->transBegin()) throw new \RuntimeException('Pengaturan tidak dapat disimpan.');
        try {
            // Serialize concurrent switches for the same account, without locking video rows.
            $table = $db->protectIdentifiers($db->prefixTable('third_party_apis'));
            $current = $db->query('SELECT id,provider FROM '.$table.' WHERE id = ? FOR UPDATE', [(int)$api->id])->getRowArray();
            if (!$current || $current['provider'] !== $api->provider) throw new \RuntimeException('Host sudah diubah atau dihapus.');
            $key = self::PREFIX.(int)$api->id;
            $data = ['value'=>$enabled ? '1' : '0', 'data_type'=>'bool'];
            $query = $db->table('settings')->where('name', $key);
            $ok = $query->countAllResults() ? $db->table('settings')->where('name', $key)->update($data)
                : $db->table('settings')->insert(['name'=>$key] + $data);
            if (!$ok || !$db->transStatus() || !$db->transCommit()) throw new \RuntimeException('Pengaturan tidak dapat disimpan.');
        } catch (\Throwable $error) {
            $db->transRollback();
            throw $error;
        }
        self::reset();
        RegisteredStreamHost::reset();
    }
}
