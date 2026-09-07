<?php

namespace App\Models;

use CodeIgniter\Model;

class PopupAdUnitModel extends Model
{
    protected $table = 'popup_ad_units';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'page',
        'provider',
        'name',
        'ad_code',
        'zone_id',
        'api_token',
        'weight',
        'status',
    ];

    public function activeForEmbed(): array
    {
        // Credentials are only used by the admin revenue integration and must
        // never be passed to the public embed page.
        return $this->select('id, page, provider, name, ad_code, status, created_at, updated_at')
            ->where('page', 'embed')
            ->where('status', 'active')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
