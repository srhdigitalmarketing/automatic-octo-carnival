<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Class ThirdPartyApi
 * @package App\Models
 * @author John Antonio
 */
class ThirdPartyApi extends Model
{
    protected $table            = 'third_party_apis';
    protected $returnType       = 'App\Entities\ThirdPartyApi';
    protected $allowedFields    = ['name', 'provider', 'api_token', 'api_base_url', 'embed_domains', 'r2_account_id', 'r2_access_key_id', 'r2_secret_access_key', 'r2_bucket', 'r2_public_url', 'status'];
    protected $useTimestamps = true;

    // Validation
    protected $validationRules      = [
        'name' => 'required|max_length[128]',
        'provider' => 'required|in_list[cloudflare_r2,upnshare,custom_http,vod_catalog,serverdothost]',
        'status' => 'permit_empty|in_list[active,paused]'
    ];


    /** Read-only capability check for installations upgraded without column migrations. */
    public function schemaError(): string
    {
        try {
            if (!$this->db->tableExists($this->table)) {
                return 'Tabel third_party_apis belum tersedia. Periksa database website.';
            }
            $required = array_merge($this->allowedFields, ['id', 'created_at', 'updated_at']);
            $missing = array_diff($required, $this->db->getFieldNames($this->table));
            return $missing ? 'Kolom API belum lengkap: '.implode(', ',$missing).'. Lengkapi struktur menggunakan database-update-api-schema.sql setelah backup database. Data lama tidak perlu dihapus.' : '';
        } catch (\Throwable $e) {
            return 'Struktur database API tidak dapat diperiksa. Periksa koneksi, izin database dan log server.';
        }
    }

    public function getApi($id)
    {
        return $this->where('id', $id)
                    ->first();
    }

}
