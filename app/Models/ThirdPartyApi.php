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
        'provider' => 'required|in_list[cloudflare_r2,upnshare,streamhg,custom_http,vod_catalog]',
        'status' => 'permit_empty|in_list[active,paused]'
    ];


    public function getApi($id)
    {
        return $this->where('id', $id)
                    ->first();
    }

}
