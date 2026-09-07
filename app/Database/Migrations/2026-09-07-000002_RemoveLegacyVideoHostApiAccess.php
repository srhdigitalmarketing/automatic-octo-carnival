<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveLegacyVideoHostApiAccess extends Migration
{
    public function up()
    {
        $legacyIds = $this->db->table('third_party_apis')
            ->select('id')
            ->where('provider !=', 'cloudflare_r2')
            ->get()
            ->getResultArray();

        $ids = array_column($legacyIds, 'id');
        if (! empty($ids)) {
            // Preserve existing stream links while removing their old provider
            // association and credentials.
            $this->db->table('links')->whereIn('api_id', $ids)->update(['api_id' => null]);
            $this->db->table('third_party_apis')->whereIn('id', $ids)->delete();
        }
    }

    public function down()
    {
        // Removed credentials are intentionally not restorable.
    }
}
